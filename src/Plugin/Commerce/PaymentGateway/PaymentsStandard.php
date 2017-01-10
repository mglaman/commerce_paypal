<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the PaymentStandard payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_payments_standard",
 *   label = "PayPal (Payments Standard)",
 *   display_label = "PayPal",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_paypal\PluginForm\PaymentsStandardPaymentForm",
 *   },
 *   payment_method_types = {"paypal"},
 * )
 */
class PaymentsStandard extends OffsitePaymentGatewayBase implements PaymentsStandardInterface {

  public function onNotify(Request $request) {
    // mc_gross = 89.50
    // invoice = 3-TIMESTAMP
    // protection_eligibility
    // item_number1
    // payer_id
    // tax
    // payment_date
    // payment_status (Pending)
    // notify_version
    // payer_status
    // payer_email
    // verify_sign
    // tnx_id
    // pending_reason
    // payment_gross
    // auth

    // Create the payment.
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      //'amount' => $order->getTotalPrice(),
      // Gateway plugins cannot reach their matching config entity directly.
      //'payment_gateway' => $order->payment_gateway->entity->id(),
      //'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $request->request->get('txn_id'),
      'remote_state' => $request->request->get('payment_status'),
      'authorized' => REQUEST_TIME,
    ]);
    $payment->save();
  }

}
