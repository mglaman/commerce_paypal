<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Paypal PaymentsPro payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_payments_pro",
 *   label = "PayPal (PaymentsPro)",
 *   display_label = "PayPal",
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class PaymentsPro extends OnsitePaymentGatewayBase {

  /**
   * Paypal API URL.
   */
  const PAYPAL_API_URL = 'https://api.sandbox.paypal.com/v1';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager);
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_id' => '',
      'client_secret' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configuration['client_id'],
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $this->configuration['client_secret'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['client_id'] = $values['client_id'];
      $this->configuration['client_secret'] = $values['client_secret'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Needs kernel test
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    if ($payment->getState()->value != 'new') {
      throw new \InvalidArgumentException('The provided payment is in an invalid state.');
    }
    $payment_method = $payment->getPaymentMethod();
    if (empty($payment_method)) {
      throw new \InvalidArgumentException('The provided payment has no payment method referenced.');
    }
    if (REQUEST_TIME >= $payment_method->getExpiresTime()) {
      throw new HardDeclineException('The provided payment method has expired');
    }
    throw new HardDeclineException('The provided payment method has expired');
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    try {
      $address = $payment_method->getBillingProfile()->address->first();
      $owner = $payment_method->getOwner();

      // Prepare an array of parameters to sent to the vault endpoint.
      $parameters = [
        'number' => $payment_details['number'],
        'type' => $payment_details['type'],
        'expire_month' => $payment_details['expiration']['month'],
        'expire_year' => $payment_details['expiration']['year'],
        'cvv2' => $payment_details['security_code'],
        'first_name' => $address->getGivenName(),
        'last_name' => $address->getFamilyName(),
        'billing_address' => [
          'line1' => $address->getAddressLine1(),
          'city' => $address->getLocality(),
          'country_code' => $address->getCountryCode(),
          'postal_code' => $address->getPostalCode(),
          'state' => $address->getAdministrativeArea(),
        ],
      ];

      // Should we send the UUID instead?
      if ($owner->isAuthenticated()) {
        $parameters['external_customer_id'] = $owner->id();
      }

      // TODO: Include the merchant_id parameter.
      $response = $this->httpClient->post(self::PAYPAL_API_URL . '/vault/credit-cards', [
        'headers' => [
          'Content-type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ],
        'json' => $parameters,
      ]);

      // Check the response code, checking 201 should be enough.
      if (in_array($response->getStatusCode(), [200, 201])) {
        $data = json_decode($response->getBody(), TRUE);

        $payment_method->card_type = $payment_details['type'];
        // Only the last 4 numbers are safe to store.
        $payment_method->card_number = substr($payment_details['number'], -4);
        $payment_method->card_exp_month = $payment_details['expiration']['month'];
        $payment_method->card_exp_year = $payment_details['expiration']['year'];
        $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
        
        // Store the remote ID returned by the request.
        $payment_method->setRemoteId($data['id']);
        $payment_method->setExpiresTime($expires);
        $payment_method->save();
      }
      else {
        throw new HardDeclineException("Unable to store the credit card");
      }
    }
    catch (RequestException $e) {
      throw new HardDeclineException("Unable to store the credit card");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  protected function getAccessToken() {
    $configuration = $this->getConfiguration();
    if (!empty($configuration['access_token'])) {
      return $configuration['access_token'];
    }
    else {
      try {
        $response = $this->httpClient->post(self::PAYPAL_API_URL . '/oauth2/token', [
          'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
          'auth' => [
            $this->configuration['client_id'],
            $this->configuration['client_secret'],
          ],
          'form_params' => [
            'grant_type' => 'client_credentials',
          ],
        ]);
        $data = json_decode($response->getBody(), TRUE);

        // Store the access token.
        if ($response->getStatusCode() === 200 && isset($data['access_token'])) {
          $configuration['access_token'] = $data['access_token'];
          $this->setConfiguration($configuration);
          return $configuration['access_token'];
        }
      }
      catch (RequestException $e) {
      }
    }
  }

}
