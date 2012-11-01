<?php

class TwoCheckout extends PaymentMethodAbstract
{
	const GATEWAY_API_URL = 'https://www.2checkout.com/checkout/spurchase';

	public function isLive()
	{
		if($this->use_demo_mode === 'Y') return FALSE;
		return TRUE;
	}

	/**
	 * 2Checkout form action
	 *
	 * @return string
	 */
	public function getPaymentFormAction()
	{
		return TwoCheckout::GATEWAY_API_URL;
	}

	/**
	 * Text that will be displayed on the payment form Submit button
	 *
	 * @return string
	 */
	public function getPaymentSubmitButtonText()
	{
		return "Proceed to 2checkout.com to pay";
	}

	/**
	 * Retrieve payment info from 2checkout and create a new order
	 *
	 * @param $cart
	 * @param $module_srl
	 */
	public function onOrderConfirmationPageLoad($cart, $module_srl)
	{
		// first of all, check that the data received
		// is actually from 2checkout
		$key = Context::get('key');

		// Create expected key
		$secret_word = $this->secret_word;
		$account_number = $this->sid;
		$order_number = Context::get('order_number');
		$total = Context::get('total');
		$expected_key = strtoupper(md5($secret_word . $account_number . $order_number . $total));

		if($key != $expected_key)
		{
			ShopLogger::log("Invalid 2 checkout message received - key " . $key . ' ' . print_r($_REQUEST, true));
			throw new PaymentProcessingException("There was a problem processing your transaction");
		}

	}

	/**
	 * Checks is custom plugin parameters are set and valid;
	 * If no validation is needed, just return true;
	 * @return mixed
	 */
	public function isConfigured(&$error_message = 'msg_invalid_request')
	{
		if(!isset($this->sid)) return FALSE;
		if(!isset($this->secret_word)) return FALSE;
		return TRUE;
	}

	/**
	 * Nothing happens here since user is redirected to 2checkout's page
	 *
	 * @param Cart $cart
	 * @param      $error_message
	 */
	public function processPayment(Cart $cart, &$error_message)
	{
	}
}