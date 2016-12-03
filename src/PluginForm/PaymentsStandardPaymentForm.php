<?php

namespace Drupal\commerce_paypal\PluginForm;

use Drupal\commerce_payment\PluginForm\OffsitePaymentForm;
use Drupal\Core\Form\FormStateInterface;


class PaymentsStandardPaymentForm extends OffsitePaymentForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $data = [
      // Specify the checkout experience to present to the user.
      'cmd' => '_cart',
      // Signify we're passing in a shopping cart from our system.
      'upload' => 1,
      // The store's PayPal e-mail address.
      // @todo make configurable
      'business' => 'nmd.matt@gmail.com',
      // The path PayPal should send the IPN to.
      // @todo define this
      'notify_url' => '',
      // The application generating the API request.
      'bn' => 'CommerceGuys_Cart_PPS',
      // Set the correct character set.
      'charset' => 'utf-8',
      // Do not display a comments prompt at PayPal.
      'no_note' => 1,
      // Do not display a shipping address prompt at PayPal.
      'no_shipping' => 1,

      // @todo Payment needs to define specific cancel/return so we can embed anywhere.
      // Return to the review page when payment is canceled.
      'cancel_return' => '',
      // Return to the payment redirect page for processing successful payments.
      'return' => '',

      // Return to this site with payment data in the POST.
      'rm' => 2,
      // The type of payment action PayPal should take with this order.
      // @todo sale or authorization
      'paymentaction' => 'sale',
      // Set the currency and language codes.
      'currency_code' => $payment->getAmount()->getCurrencyCode(),
      // @todo port old commerce_paypal_wps_languages().
      'lc' => 'US',
    ];

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }
    // @todo this is andbox
    $form['offiste_action'] = [
      '#type' => 'hidden',
      '#value' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
    ];
    // @todo live == https://www.paypal.com/cgi-bin/webscr
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Proceed to PayPal'),
    ];

    return $form;
  }

}
