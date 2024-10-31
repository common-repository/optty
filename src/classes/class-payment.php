<?php
/**
 * Optty Payment Handler
 *
 * @package Optty
 */

namespace Optty\classes;

use GuzzleHttp\Exception\GuzzleException;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Payment
 */
class Payment {

	/**
	 * Generate Customer token
	 *
	 * @return array
	 */
	protected static function generate_customer_token(): array {
		try {
			$base_url = Settings::get( 'api_url' );
			$headers  = array(
				'Authorization' => 'Bearer ' . Authentication::get_access_token(),
				'Content-Type'  => 'application/json',
			);

			$request_body = array(
				'customerIdentifier' => Settings::get( 'hash_secret' ),
			);

			$request_url = $base_url . '/merchants/customer/sessions/';
			$response    = Request::post( $request_url, $headers, 'json', $request_body );
			return array(
				'data'    => $response['body'],
				'status'  => $response['status_code'],
				'message' => $response['message'],
			);
		} catch ( GuzzleException | Exception $e ) {
			Logs::error( $e->getMessage(), (object) $e->getTraceAsString() );
			return array(
				'status'  => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Payment payload
	 *
	 * @param object $order order_payload.
	 *
	 * @return array
	 */
	public static function payment_payload( object $order ): array {
		// added phpcs:ignore because woocommerce already verified nonce.
		// phpcs:ignore
		$bnpl_provider = sanitize_text_field(wp_unslash($_POST['selected_bnpl']));

		global $woocommerce;

		$customer_token = self::generate_customer_token();

		$order_reference = $order->get_order_number() . '-' . time() . Utils::generate_random_characters( 4 );

		$order_amount      = (float) $order->get_total();
		$shipping_amount   = (float) $order->get_shipping_total();
		$purchase_country  = $order->get_billing_country();
		$purchase_currency = $order->get_currency();
		$discount_amount   = (float) $order->get_discount_total();
		$tax_amount        = (float) $order->get_total_tax();

		$customer = array(
			'firstName'   => $order->get_billing_first_name(),
			'lastName'    => $order->get_billing_last_name(),
			'email'       => $order->get_billing_email(),
			'phoneNumber' => $order->get_billing_phone(),
		);

		$billing_address = array(
			'firstName'      => $order->get_billing_first_name(),
			'lastName'       => $order->get_billing_last_name(),
			'email'          => $order->get_billing_email(),
			'phoneNumber'    => $order->get_billing_phone(),
			'streetAddress'  => $order->get_billing_address_1(),
			'streetAddress2' => $order->get_billing_address_2(),
			'city'           => $order->get_billing_city(),
			'state'          => $order->get_billing_state(),
			'country'        => $order->get_billing_country(),
			'region'         => $order->get_billing_state(),
			'postalCode'     => $order->get_billing_postcode(),
		);

		$order->set_payment_method_title( explode( '_', $bnpl_provider )[0] );
		$order->save();

		/**
		 * If all products in cart are virtual products, use billing address as shipping address.
		 */
		$is_all_virtual_products = true;
		foreach ( $woocommerce->cart->get_cart_contents() as $cart_item_key => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$is_all_virtual_products = false;
				break;
			}
		}

		if ( $is_all_virtual_products ) {
			$shipping_address = $billing_address;
		} else {
			$shipping_address = array(
				'firstName'      => $order->get_shipping_first_name(),
				'lastName'       => $order->get_shipping_last_name(),
				'email'          => $order->get_billing_email(),
				'phoneNumber'    => $order->get_billing_phone(),
				'streetAddress'  => $order->get_shipping_address_1(),
				'streetAddress2' => $order->get_shipping_address_2(),
				'city'           => $order->get_shipping_city(),
				'state'          => $order->get_shipping_state(),
				'country'        => $order->get_shipping_country(),
				'region'         => $order->get_shipping_state(),
				'postalCode'     => $order->get_shipping_postcode(),
			);
		}

		$order_items = array();
		foreach ( $order->get_items() as $item_id => $item ) {

			/**
			 * Amount here should be subtotaled, as it is the amount without discounts calculated
			 * Else the API throws a validation error because quantity * unitPrice would then not be equal
			 * to totalAmount
			 */
			$product       = $item->get_product();
			$order_items[] = array(
				'name'        => $item->get_name(),
				'quantity'    => (float) $item->get_quantity(),
				'sku'         => $product->get_sku(),
				'unitPrice'   => (float) $product->get_price(),
				/**
				 * Amount here should be subtotaled, as it is the amount without discounts calculated
				 * Else the API throws a validation error because quantity * unitPrice would then not be equal
				 * to totalAmount
				 */
				'totalAmount' => (float) $item->get_subtotal(),
			);
		}
		return array(
			'bnplProvider'     => $bnpl_provider,
			'locale'           => get_locale(),
			'customerToken'    => $customer_token['data'] ? $customer_token['data']['token'] : '',
			'orderReference'   => $order_reference,
			'orderAmount'      => $order_amount,
			'taxAmount'        => $tax_amount,
			'shippingAmount'   => $shipping_amount,
			'discountAmount'   => $discount_amount,
			'orderItems'       => $order_items,
			'purchaseCountry'  => $purchase_country,
			'purchaseCurrency' => $purchase_currency,
			'customer'         => $customer,
			'billingAddress'   => $billing_address,
			'shippingAddress'  => $shipping_address,
		);
	}

	/**
	 * Process payment
	 *
	 * @param array $payload validated payload.
	 * @return array
	 */
	public static function process_payment( array $payload ): array {
		Logs::info( 'Starting Payment', (object) array() );
		try {
			$base_url = Settings::get( 'api_url' );
			$headers  = array(
				'Authorization' => 'Bearer ' . Authentication::get_access_token(),
				'Content-Type'  => 'application/json',
			);

			$request_body = $payload;

			Logs::debug( 'Payment payload', (object) $request_body );

			$request_url = $base_url . '/orders/';
			$response    = Request::post( $request_url, $headers, 'json', $request_body );

			Logs::debug( 'Payment response', (object) $response );

			return array(
				'data'    => $response['body'],
				'status'  => $response['status_code'],
				'message' => $response['message'],
			);
		} catch ( GuzzleException | Exception $e ) {
			Logs::error( $e->getMessage(), (object) $e->getTraceAsString() );
			return array(
				'status'  => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Get order status
	 *
	 * @param string $reference Order Reference.
	 *
	 * @throws Exception Throws exception.
	 */
	public static function get_order_status( string $reference ): array {
		$base_url    = Settings::get( 'api_url' );
		$headers     = array(
			'Authorization' => 'Bearer ' . Authentication::get_access_token(),
			'Content-Type'  => 'application/json',
		);
		$request_url = $base_url . '/orders/' . $reference;
		try {
			return Request::get( $request_url, $headers )['body'];
		} catch ( Exception $e ) {
			Logs::error( $e->getMessage(), (object) $e->getTraceAsString() );
			return array(
				'status'  => false,
				'message' => $e->getMessage(),
			);
		}
	}
}
