<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;

/**
 * Provides the Onsite payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_payments_standard",
 *   label = "PayPal Standard",
 *   display_label = "PayPal",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_paypal\PluginForm\PaymentsStandardPaymentForm",
 *   },
 *   payment_method_types = {"paypal"},
 * )
 */
class PaymentsStandard extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function doAutomaticRedirect() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function onRedirectSuccess() {
    // TODO: Implement onRedirectSuccess() method.
  }

  /**
   * {@inheritdoc}
   */
  public function onRedirectCancel() {
    // TODO: Implement onRedirectCancel() method.
  }

}
