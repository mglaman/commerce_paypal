<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
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
class PaymentsStandard extends OffsitePaymentGatewayBase implements PaymentsStandardInterface {

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
  }

  /**
   * {@inheritdoc}
   */
  public function onRedirectReturn(OrderInterface $order) {
    // Create and save payment method.
    $stop = null;
  }

  /**
   * {@inheritdoc}
   */
  public function onRedirectCancel(OrderInterface $order) {
    // Nothing to do.
  }

}
