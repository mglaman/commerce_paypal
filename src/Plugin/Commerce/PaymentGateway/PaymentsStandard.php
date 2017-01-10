<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Url;
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

  public function getPaymentRedirectCancelUrl(OrderInterface $order) {
    $route_parameters = array(
      'commerce_order' => $order->id(),
      'step' => 'review',
    );
    $options = array('absolute' => TRUE);
    return Url::fromRoute('commerce_checkout.form', $route_parameters, $options);
  }

  public function getPaymentRedirectReturnUrl(OrderInterface $order) {
    $route_parameters = array(
      'commerce_order' => $order->id(),
      'step' => 'complete',
    );
    $options = array('absolute' => TRUE);
    return Url::fromRoute('commerce_checkout.form', $route_parameters, $options);
  }

  public function onNotify(Request $request) {

    $ipn = new PaypalIPN();
    if ($this->getMode() != 'live') {
      $ipn->useSandbox();
    }
    $verified = $ipn->verifyIPN();
    if (!$verified) {
      \Drupal::logger('commerce_paypal')->notice('Faulty IPN received.');
      return;
    }

    $txn_id = $request->request->get('txn_id');
    $invoice = $request->request->get('invoice');
    if ($invoice) {
      $invoice_parts = explode('-', invoice);
      $order_id = array_shift($invoice_parts);
    } else {
      $order_id = 'Unknown';
    }

    $payment_status = $request->request->get('payment_status');
    if ($payment_status == 'Completed') {

      // @todo check that txn_id has not been previously processed
      // @todo check that receiver_email is your Primary PayPal email
      // @todo check that payment_amount/payment_currency are correct

      $order = Order::load($order_id);
      $payment_storage = \Drupal::entityTypeManager()
        ->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'state' => 'authorization',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->id(),
        'order_id' => $order->id(),
        'test' => ($this->getMode() != 'live'),
        'remote_id' => $request->request->get('txn_id'),
        'remote_state' => $request->request->get('payment_status'),
        'authorized' => REQUEST_TIME,
      ]);
      $payment->save();
    }

    \Drupal::logger('commerce_paypal')->notice('IPN processed for Order @order_id with ID @txn_id.', array(
      '@txn_id' => $txn_id,
      '@order_id' => $order_id
    ));
  }
}
