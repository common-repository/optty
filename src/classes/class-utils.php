<?php
/**
 * Optty Utils
 *
 * @package Optty
 */

namespace Optty\classes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utils
 */
class Utils {

	/**
	 * Generate random numbers
	 *
	 * @param int $length_of_string length_of_string.
	 * @return string
	 */
	public static function generate_random_characters( $length_of_string = 64 ): string {
		$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		return substr( str_shuffle( $str_result ), 0, $length_of_string );
	}

	/**
	 * Generate reference
	 *
	 * @return string
	 */
	public static function generate_reference(): string {
		$reference = uniqid( '', true );
		return preg_replace( '/[^A-Za-z0-9]/', '', $reference );
	}
}
