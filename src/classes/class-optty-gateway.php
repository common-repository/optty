<?php
/**
 * Handle registering Optty as a payment gateway
 *
 * @package Optty
 */

use Optty\classes\Authentication;
use Optty\classes\Cache;
use Optty\classes\Logs;
use Optty\classes\Settings;
use Optty\enums\Status;
use Optty\classes\Refund;
use Optty\classes\Payment;
use Optty\classes\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Optty_Gateway
 *
 * @extends WC_Payment_Gateway
 */
class Optty_Gateway extends WC_Payment_Gateway {
	const NOTICE_MESSAGE_CACHE_SUFFIX = '_notice_message';

	/**
	 * Register object in hook when instantiated
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'optty';
		$this->icon               = 'https://widgets.optty.com/images/optty/black/optty-sub.svg';
		$this->has_fields         = true;
		$this->method_title       = 'Optty';
		$this->method_description = 'One platform integrating you to the world of Buy Now Pay Later Payment gateways globally.';

		$this->supports = array(
			'products',
			'refunds',
		);

		// Method with all the options fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->title         = 'Optty Payment';
		$this->description   = $this->get_option( 'description' );
		$this->auth_url      = $this->get_option( 'auth_url' );
		$this->api_url       = $this->get_option( 'api_url' );
		$this->widget_url    = $this->get_option( 'widget_url' );
		$this->client_id     = $this->get_option( 'client_id' );
		$this->client_secret = $this->get_option( 'client_secret' );
		$this->hash_secret   = $this->get_option( 'hash_secret' );

		// This action hook saves the settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		/**
		 * This action hook registers the callback handler for this payment method.
		 *
		 * The callback url becomes HOSTNAME/wc-api/optty_gateway if pretty permalinks is enabled
		 * or HOSTNAME/?wc-api=optty_gateway if you do not want to care about that.
		 */
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );
		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'optty_thankyou_message' ), 10, 2 );
	}

	/**
	 * Function init_form_fields
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'description'   => array(
				'title'       => 'Description',
				'type'        => 'text',
				'description' => 'Description to show to store users',
				'desc_tip'    => true,
				'default'     => 'One platform integrating you to the world of Buy Now Pay Later Payment gateways globally.',
			),
			'auth_url'      => array(
				'type'        => 'text',
				'title'       => 'Authentication URL',
				'description' => 'Authentication URL',
				'desc_tip'    => true,
				'default'     => 'https://auth.optty.com/token',
			),
			'api_url'       => array(
				'type'        => 'text',
				'title'       => 'Merchant API URL',
				'description' => 'Merchant API URL',
				'desc_tip'    => true,
				'default'     => 'https://api.optty.com',
			),
			'widget_url'    => array(
				'type'        => 'text',
				'title'       => 'Widget SDK URL',
				'description' => 'Widget SDK URL',
				'desc_tip'    => true,
				'default'     => 'https://widgets.optty.com/widget-loader.js',
			),
			'client_id'     => array(
				'title'       => 'Client ID',
				'type'        => 'text',
				'description' => 'Your Optty client ID.',
				'desc_tip'    => true,
				'default'     => '',
			),
			'client_secret' => array(
				'title'       => 'Client Secret',
				'type'        => 'text',
				'description' => 'Your Optty client secret',
				'desc_tip'    => true,
				'default'     => '',
			),
			'hash_secret'   => array(
				'title'       => 'Hash Secret',
				'type'        => 'text',
				'description' => 'Your Optty hash secret',
				'desc_tip'    => true,
				'default'     => '',
			),
		);
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return void
	 * @throws Exception Throws an exception.
	 */
	public function process_admin_options(): void {
		try {
			parent::process_admin_options();
			Authentication::request_access_token( 'api-user', true );
		} catch ( Exception $e ) {
			Logs::error( $e->getMessage(), (object) $e->getTrace() );
		}
	}

	/**
	 * Process order refund
	 *
	 * @param int    $order_id order reference.
	 * @param float  $amount refund amount.
	 * @param string $reason refund description.
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		if ( empty( $reason ) ) {
			$reason = 'Refund for order ' . $order_id;
		}

		$order           = wc_get_order( $order_id );
		$payload         = array(
			'amount'          => (float) $amount,
			'currency'        => $order->get_currency(),
			'orderReference'  => $order->get_transaction_id(),
			'refundReference' => Utils::generate_reference(),
			'description'     => $reason,
		);
		$refund_response = Refund::process_refund( $payload );
		$is_successful   = $refund_response['status'];
		$response_data   = $refund_response['data'];

		if ( $is_successful ) {
			$order->add_meta_data( 'refund_reference', $response_data['refundReference'], false );
			$order->add_meta_data( 'refund_amount', $response_data['refundedAmount'], false );
			$order->save_meta_data();
			$order->add_order_note( 'Refund of ' . $response_data['refundedAmount'] . ' successfully approved.' );

			// when full amount is refunded, update status to refunded.
			if ( $order->get_total() === $order->get_total_refunded() ) {
				if ( ! $order->has_status( Status::WC_REFUNDED ) ) {
					$order->update_status( Status::WC_REFUNDED );
				} else {
					$order->add_order_note( 'Order completely refunded.' );
				}
			}
			return true;
		}
		$order->add_order_note( 'Refund failed.' );
		return false;
	}

	/**
	 * Validate Field
	 *
	 * @return bool
	 */
	public function validate_fields(): bool {
		// added phpcs:ignore because woocommerce already verified nonce.
		// phpcs:ignore
		if (empty($_POST['selected_bnpl'])) {
			wc_add_notice( 'Please select a BNPL!', 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Process payment.
	 *
	 * @param int $order_id Order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order   = wc_get_order( $order_id );
		$payload = Payment::payment_payload( $order );

		$order->add_order_note( 'Creating order, reference: ' . $payload['orderReference'] );

		$payment_response = Payment::process_payment( $payload );
		if ( 201 === $payment_response['status'] ) {
			$order->add_order_note( 'Order created successfully.' );
			return array(
				'result'   => 'success',
				'redirect' => $payment_response['data']['redirectUrl'],
			);
		}
		$order->add_order_note( 'Unable to create order.' );
		wc_add_notice( 'Error processing request. Please try again.', 'error' );
		return array();
	}

	/**
	 * Function payment_fields.
	 *
	 * This is where we will inject the Optty Easy Checkout widget.
	 */
	public function payment_fields(): void {
		include_once OPTTY_PLUGIN_PATH . '/src/templates/widgets/widget-optty-easy-checkout.php';
	}

	/**
	 * Handles callbacks for the optty payment gateway
	 */
	public function callback_handler(): void {
		// @codingStandardsIgnoreStart
		if (isset($_GET['status'], $_GET['hash'], $_GET['reference'])) {
			$reference = sanitize_text_field(wp_unslash($_GET['reference']));
			$hash = sanitize_text_field(wp_unslash($_GET['hash']));
			$status = sanitize_text_field(wp_unslash($_GET['status']));
			// @codingStandardsIgnoreStart
			$payload = $status . '|' . $reference;
			$hash_secret = Settings::get('hash_secret');
			if ($this->is_valid_signature($hash, $payload, $hash_secret)) {
				$order_on_api = Payment::get_order_status($reference);
				$status_on_api = wc_strtolower($order_on_api['status']);
				$order_id = explode("-", $reference)[0];
				$order = wc_get_order($order_id);

				if (!$order) {
					Logs::error("No order with reference $order_id", new stdClass());
					wc_add_notice(sprintf(__('No order with reference %s'), $order_id), 'error');
					wp_safe_redirect(wc_get_cart_url());
					return;
				}

				if (Status::DECLINED === $status_on_api || Status::FAILED === $status_on_api || Status::CANCELED === $status_on_api) {
					$notice = sprintf(__('Payment via Optty %s (Transaction Reference: %s)'), $status_on_api, $reference);
					$order->add_order_note($notice);
					wc_add_notice($notice, 'error');
					wp_safe_redirect(wc_get_checkout_url());
					return;
				}

				if (Status::PENDING === $status_on_api) {
					$notice = sprintf(__('Payment via Optty %s (Transaction Reference: %s), requires payment status verification'), $status_on_api, $_GET['reference']);
					$order->add_order_note($notice);
					$order->update_status(Status::WC_ON_HOLD);
					Cache::set(
						$order->get_order_key() . self::NOTICE_MESSAGE_CACHE_SUFFIX,
						['notice_message' => $notice, 'notice_type' => 'notice']
					);
					wc_empty_cart();
					wc_add_notice($notice);
					wp_safe_redirect($order->get_checkout_order_received_url());
					return;
				}

				// Customer didn't pay full amount for product
				if (Status::SUCCESSFUL === $status_on_api && $order->get_total() > $order_on_api['amount']) {
					$order->update_status(Status::WC_ON_HOLD);
					$order->add_order_note(sprintf(__('Order has been placed on hold as customers wasn\'t charged full amount for order. Amount Paid: %d, Order Amount: %d. (Transaction Reference: %s)'), $order_on_api['amount'], $order->get_total(), $reference));
					// Add order note to notify customer of amount discrepancy
					$customer_notice = sprintf(__('Your order has been placed on hold as you were not charged the full order amount. Amount Paid: %d, Order Amount: %d. (Transaction Reference: %s)'), $order_on_api['amount'], $order->get_total(), $reference);
					$order->add_order_note($customer_notice, 1);
					Cache::set(
						$order->get_order_key() . self::NOTICE_MESSAGE_CACHE_SUFFIX,
						['notice_message' => $customer_notice, 'notice_type' => 'notice']
					);
					wc_empty_cart();
					wp_safe_redirect($order->get_checkout_order_received_url());
					return;
				}

				if (Status::SUCCESSFUL === $status_on_api && $order->get_total() == $order_on_api['amount']) {
					// Status is updated as processing for shippable products and completed for downloadable products.
					$order->payment_complete($reference);
					$order->add_order_note(sprintf(__('Payment via Optty successful (Transaction Reference: %s)'), $reference));
					wc_empty_cart();
					wp_safe_redirect($order->get_checkout_order_received_url());
					return;
				}
			}
		} else {
			wc_add_notice(__('Invalid callback'), 'error');
			wp_safe_redirect(wc_get_cart_url());
			return;
		}
	}

	/**
	 * Verify hash
	 *
	 * @param string $hash hash to verify.
	 * @param string $payload hash payload.
	 * @param string $hash_secret hash secret.
	 * @return bool
	 */
	private function is_valid_signature(string $hash, string $payload, string $hash_secret): bool {
		$computed_hash = hash_hmac('sha512', $payload, $hash_secret);
		$computed_hash = str_replace('-', '', $computed_hash);
		return $computed_hash === $hash;
	}

	/**
	 * Filters the thank you text on the order received page. wc_add_notice doesn't display a notice on the thank you page,
	 * Hence the use of the cache to avoid global variables.
	 *
	 * @param string $wc_thankyou_text Default woocommerce thank you text
	 * @param WC_Order $order The order
	 * @return mixed|string
	 */
	public function optty_thankyou_message(string $wc_thankyou_text, WC_Order $order) {
		$notice = Cache::get($order->get_order_key() . self::NOTICE_MESSAGE_CACHE_SUFFIX);
		if (Status::WC_PROCESSING !== $order->get_status() && Status::WC_COMPLETED !== $order->get_status()) {
			Cache::delete($order->get_order_key() . self::NOTICE_MESSAGE_CACHE_SUFFIX);
			return $notice['notice_message'];
		}
		return $wc_thankyou_text;
	}
}
