<?php
/**
 * Optty Refund Handler
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
 * Class Refund
 */
class Refund {

	/**
	 * Process the payment order refund
	 *
	 * @param array $payload refund data.
	 * @return array
	 */
	public static function process_refund( array $payload ): array {
		Logs::info( 'Starting refund', (object) array() );
		try {
			$base_url      = Settings::get( 'api_url' );
			$headers       = array(
				'Authorization' => 'Bearer ' . Authentication::get_access_token(),
				'Content-Type'  => 'application/json',
			);
			$refund_amount = array(
				'amount'   => $payload['amount'],
				'currency' => $payload['currency'],
			);
			$request_body  = array(
				'refundAmount'      => $refund_amount,
				'refundDescription' => $payload['description'],
				'refundReference'   => $payload['refundReference'],
			);

			Logs::debug( 'Refund payload', (object) $request_body );

			$request_url = $base_url . '/orders/' . $payload['orderReference'] . '/refund';
			$response    = Request::post( $request_url, $headers, 'json', $request_body );

			Logs::debug( 'Refund response', (object) $response );

			$is_successful = 201 === $response['status_code'];
			return array(
				'data'    => $response['body'],
				'status'  => $is_successful,
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
}
