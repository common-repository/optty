<?php
/**
 * Optty cache handler
 *
 * @package Optty
 */

namespace Optty\classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logs
 */
class Cache {

	/**
	 * Cache value using WP Transient API with default expiry of 1 hour.
	 *
	 * @param string $name Cache name.
	 * @param mixed  $value Cache value.
	 * @param int    $expiry Expiry in seconds.
	 *
	 * @return bool
	 */
	public static function set( string $name, $value, int $expiry = HOUR_IN_SECONDS ): bool {
		return set_transient( $name, $value, $expiry );
	}

	/**
	 * Return cached value using WP Transient API
	 *
	 * @param string $name Cache name.
	 *
	 * @return mixed
	 */
	public static function get( string $name ) {
		return get_transient( $name );
	}

	/**
	 * Delete cached item using WP Transient API
	 *
	 * @param string $name Cache name.
	 *
	 * @return bool
	 */
	public static function delete( string $name ): bool {
		return delete_transient( $name );
	}
}
