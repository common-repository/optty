<?php
/**
 * Optty Request Handler.
 *
 * @package Optty
 */

namespace Optty\classes;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Request
 */
class Request {
	/**
	 * Variable Client
	 *
	 * @var Client
	 */
	private static Client $client;

	/**
	 * Make API request with provided params
	 *
	 * @param string $request_uri request_uri.
	 * @param array  $params params.
	 * @param string $method request_method.
	 * @param array  $headers headers.
	 * @param string $content_type content_type.
	 *
	 * @return array|WP_Error
	 */
	private static function make_request(
		string $request_uri,
		array $params,
		string $method,
		array $headers,
		string $content_type

	) {
		if ( empty( self::$client ) ) {
			self::$client = new Client();
		}

		try {
			$response = self::$client->request(
				$method,
				$request_uri,
				array(
					'headers'     => $headers,
					$content_type => $params,
				)
			);

			return self::process_response( $response );
		} catch ( GuzzleException | Exception $e ) {
			/**
			 * Guzzle response
			 *
			 * @var Response $response
			 */
			return (array) new WP_Error( 'Network Error', $e->getMessage() );
		}
	}

	/**
	 * Process the response and return an array
	 *
	 * @param ResponseInterface $response response.
	 *
	 * @return array
	 */
	private static function process_response( ResponseInterface $response ): array {
		return array(
			'headers'     => $response->getHeaders(),
			'body'        => json_decode( (string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR ),
			'status_code' => $response->getStatusCode(),
			'message'     => $response->getReasonPhrase(),
		);
	}

	/**
	 * Send GET requests
	 *
	 * @param string $uri Request URI.
	 * @param array  $headers Request headers.
	 * @param array  $params URL params.
	 *
	 * @return array
	 * @throws Exception If API request fails.
	 */
	public static function get( string $uri, array $headers, array $params = array() ): array {
		return self::make_request( $uri, $params, 'GET', $headers, 'query' );
	}

	/**
	 * Send POST requests
	 *
	 * @param string $uri Request URI.
	 * @param array  $headers Request headers.
	 * @param string $body_type request body type, supports json and form_params.
	 * @param array  $params request data.
	 *
	 * @return array
	 * @throws Exception If API request fails.
	 */
	public static function post( string $uri, array $headers, string $body_type, array $params = array() ): array {
		return self::make_request( $uri, $params, 'POST', $headers, $body_type );
	}

	/**
	 * Send PUT requests
	 *
	 * @param string $uri Request URI.
	 * @param array  $headers Request headers.
	 * @param array  $params Request data.
	 *
	 * @return array
	 * @throws Exception If API request fails.
	 */
	public static function put( string $uri, array $headers, array $params = array() ): array {
		return self::make_request( $uri, $params, 'PUT', $headers, 'json' );
	}
}
