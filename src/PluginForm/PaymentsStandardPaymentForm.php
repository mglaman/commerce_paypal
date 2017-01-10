<?php

namespace Drupal\commerce_paypal\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PaymentsStandardPaymentForm extends PaymentOffsiteForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\PaymentsStandard $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $payment->getOrder();

    $data = [
      // Specify the checkout experience to present to the user.
      'cmd' => '_cart',
      // Signify we're passing in a shopping cart from our system.
      'upload' => 1,
      // The store's PayPal e-mail address.
      'business' => \Drupal::config('commerce_payment.commerce_payment_gateway.plugin.paypal_payments_standard')->get('business'),
      // The path PayPal should send the IPN to.
      'notify_url' => $payment_gateway_plugin->getNotifyUrl()->toString(),
      // The application generating the API request.
      'bn' => 'CommerceGuys_Cart_PPS',
      // Set the correct character set.
      'charset' => 'utf-8',
      // Do not display a comments prompt at PayPal.
      'no_note' => 1,
      // Do not display a shipping address prompt at PayPal.
      'no_shipping' => 1,
      // Return to the review page when payment is canceled.
      'cancel_return' => $payment_gateway_plugin->getPaymentRedirectCancelUrl($order)
        ->toString(),
      // Return to the payment redirect page for processing successful payments.
      'return' => $payment_gateway_plugin->getPaymentRedirectReturnUrl($order)
        ->toString(),
      // Return to this site with payment data in the POST.
      'rm' => 2,
      // The type of payment action PayPal should take with this order.
      // @todo sale or authorization
      'paymentaction' => 'sale',
      // Set the currency and language codes.
      'currency_code' => $payment->getAmount()->getCurrencyCode(),
      // @todo port old commerce_paypal_wps_languages().
      'lc' => 'US',
      'invoice' => $order->id() . '-' . REQUEST_TIME,
      // Define a single item in the cart representing the whole order.
      'item_name_1' => t('Order @order_number at @store', [
        '@order_number' => $order->getOrderNumber(),
        '@store' => $order->getStore()->label()
      ]),
      'amount_1' => \Drupal::getContainer()
        ->get('commerce_price.rounder')
        ->round($order->getTotalPrice())
        ->getNumber(),
      'on0_1' => t('Product count'),
      'os0_1' => $order->get('order_items')->count(),
    ];

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }

    $mode = $payment_gateway_plugin->getMode();
    if ($mode == 'live') {
      $redirect_url = 'https://www.paypal.com/cgi-bin/webscr';
    }
    else {
      $redirect_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, 'post');
  }

}
