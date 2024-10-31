<?php
/**
 * Optty authentication Handler
 *
 * @package Optty
 */

namespace Optty\classes;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Authentication
 */
class Authentication {
	/**
	 * Get Access Token
	 *
	 * @param string $scope scope.
	 * @param bool   $force_network Set true to ignore cache.
	 *
	 * @return string
	 * @throws Exception Throws an exception.
	 */
	public static function request_access_token( string $scope = 'api-user', bool $force_network = false ) {
		if ( ! $force_network ) {
			$token = Cache::get( 'optty_merchant_token' );

			if ( ! empty( $token ) ) {
				return $token;
			}
		}
		Cache::delete( 'optty_merchant_token' );

		$client_id      = Settings::get( 'client_id' );
		$client_secret  = Settings::get( 'client_secret' );
		$token_auth_url = Settings::get( 'auth_url' );
		$body_params    = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'client_credentials',
			'response_type' => 'token',
			'scope'         => $scope,
		);

		$response_body = Request::post( $token_auth_url, array(), 'form_params', $body_params );

		$token = self::parse_access_token_response( $response_body );

		if ( isset( $token['access_token'] ) ) {
			Cache::set( 'optty_merchant_token', $token['access_token'], $token['life_time'] - ( 60 * 10 ) );
			return $token['access_token'];
		}

		throw new Exception( 'Please confirm merchant credentials and try again' );
	}

	/**
	 * Function parse_access_token_response
	 *
	 * @param array|null $response_body response_body.
	 * @return array
	 */
	private static function parse_access_token_response( ?array $response_body ): array {
		$token = array(
			'status' => 0,
		);

		if ( $response_body && isset( $response_body['body'] ) ) {
			$data = $response_body['body'];

			if ( ! is_array( $data ) ) {
				$token['message'] = 'Unable to parse response';
			} elseif ( isset( $data['message'] ) ) {
				$token['message'] = 'Error in retrieving token: "' . $data['message'] . '"';
			} else {
				$token['access_token'] = $data['access_token'];
				$token['life_time']    = $data['expires_in'];
				$token['token_type']   = $data['token_type'];
				$token['scope']        = $data['scope'];
				$token['status']       = 1;
			}
		}

		return $token;
	}

	/**
	 * Function to retrieve access token from cache
	 *
	 * @return string
	 * @throws Exception Throws an exception.
	 */
	public static function get_access_token(): string {
		$access_token = Cache::get( 'optty_merchant_token' );
		if ( false === $access_token ) {
			$token = self::request_access_token();
			if ( isset( $token['access_token'] ) ) {
				$access_token = $token['access_token'];
				Cache::set( 'optty_merchant_token', $access_token, $token['life_time'] - ( 60 * 10 ) );
			}
		}
		return $access_token;
	}

}
