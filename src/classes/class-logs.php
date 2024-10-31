<?php
/**
 * Optty logs handler
 *
 * @package Optty
 */

namespace Optty\classes;

use Exception;
use WC_Log_Levels;
use WC_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logs
 */
class Logs {

	/**
	 * Holds an instance of the WC_Logger class
	 *
	 * @var WC_Logger $logger
	 */
	private static WC_Logger $logger;


	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init(): void {
		add_filter( 'admin_menu', __CLASS__ . '::add_logs_tab' );
		add_action( 'woocommerce_settings_tabs_optty_logs', __CLASS__ . '::settings_tab', 99 );
	}

	/**
	 * Log to database
	 *
	 * @param string $message message to log.
	 * @param object $data log related data.
	 */
	private static function log_to_db( string $message, object $data ): void {
		$logs  = get_option( 'optty_logs', '' );
		$logs .= wp_json_encode( self::format_logs( $message, $data ) );
		$logs .= '<entry>';

		update_option( 'optty_logs', $logs );
	}

	/**
	 * Uses woocommerce logging class to store Optty related logs. This should
	 * never be called directly. Please use the debug(), info() and error() functions
	 *
	 * @param string $message message.
	 * @param string $log_level log level.
	 * @param object $payload log payload.
	 */
	private static function log( string $message, string $log_level, object $payload ): void {
		if ( empty( self::$logger ) ) {
			self::$logger = new WC_Logger( 'optty' );
		}

		$log = self::format_logs( $message, $payload );

		self::$logger->log( $log_level, wp_json_encode( $log ) );
		self::log_to_db( $message, $payload );
	}

	/**
	 * Format log into an associative array that can be json encoded
	 *
	 * @param string $message log message.
	 * @param object $payload log payload.
	 *
	 * @return array
	 */
	private static function format_logs( string $message, object $payload ): array {
		return array(
			'message' => $message,
			'data'    => $payload,
			'time'    => gmdate( ' d M Y H:i:s' ),
		);
	}

	/**
	 * Debug related logs
	 *
	 * @param string $message log message.
	 * @param object $data log related data.
	 */
	public static function debug( string $message, object $data ): void {
		self::log( $message, WC_Log_Levels::DEBUG, $data );
	}

	/**
	 * Informational logs
	 *
	 * @param string $message log message.
	 * @param object $data log related data.
	 */
	public static function info( string $message, object $data ): void {
		self::log( $message, WC_Log_Levels::INFO, $data );
	}

	/**
	 * Normal but significant related logs
	 *
	 * @param string $message log message.
	 * @param object $data log related data.
	 */
	public static function notice( string $message, object $data ): void {
		self::log( $message, WC_Log_Levels::NOTICE, $data );
	}

	/**
	 * Error related logs
	 *
	 * @param string $message log message.
	 * @param object $data log related data.
	 */
	public static function error( string $message, object $data ): void {
		self::log( $message, WC_Log_Levels::ERROR, $data );
	}

	/**
	 * Unusable state or application breaking logs
	 *
	 * @param string $message log message.
	 * @param object $data log related data.
	 */
	public static function emergency( string $message, object $data ): void {
		self::log( $message, WC_Log_Levels::EMERGENCY, $data );
	}

	/**
	 * Return array of logged entries.
	 *
	 * @return array
	 */
	private static function readable_logs(): array {
		$logs = get_option( 'optty_logs', array() );

		if ( empty( $logs ) ) {
			return $logs;
		}

		$entries     = array();
		$raw_entries = explode( '<entry>', $logs );

		try {
			foreach ( $raw_entries as $entry ) {
				$entries[] = json_decode( $entry, false, 512, JSON_THROW_ON_ERROR );
			}
		} catch ( Exception $e ) {
			self::error( 'Could not return logs', $e );
		}

		return $entries;
	}

	/**
	 * Filter readable_logs in showing current logs.
	 */
	private static function filter_array(): array {
		return array_filter(
			self::readable_logs(),
			function ( $data ) {
				$date                      = strtotime( $data->time );
				$current_date              = gmdate( 'Y-m-d' );
				$current_date_to_timestamp = strtotime( $current_date );
				if ( $date >= $current_date_to_timestamp ) {
					return true;
				}
				return false;
			}
		);
	}

	/**
	 * Add menu for Optty logs in Woocommerce.
	 */
	public static function add_logs_tab(): void {
		add_submenu_page(
			'woocommerce',
			'Optty Logs',
			'Optty Logs',
			'manage_options',
			'wc-optty-logs',
			__CLASS__ . '::log_page_callback'
		);
	}

	/**
	 *  Render the log page for Optty.
	 */
	public static function log_page_callback(): void {
		$filter_logs = self::filter_array();
		echo "<div style='margin: 2rem 5rem 2rem 1rem'>";
		echo '<script>let tree = data = [];</script>';
		foreach ( $filter_logs as $key => $value ) {
			// @codingStandardsIgnoreStart
			echo '
				<div style="background-color: #fff;margin: 1rem auto">
					<p style="padding: 0.5rem 1rem; background-color: #ddd; margin: 0 auto; display: block; font-size: 14px">Log Message: <b>' . esc_html($value->message) . '</b> <small>' . esc_html($value->time) . '</small></p>
					<p style="padding: 0 1rem;">Log Stack</p>
					<div style="padding: 0.3rem 0" id="jsonview_' . esc_attr($key) . '"></div>
				</div>
				<script>
					data[\'' . esc_attr($key) . '\'] = ' . _wp_specialchars(wp_json_encode(self::object_to_array($value->data))) . ';
					console.log(data[\'' . esc_attr($key) . '\']);
					tree[\'' . esc_attr($key) . '\'] = JsonView.createTree(data[\'' . esc_attr($key) . '\']);
				    JsonView.render(tree[\'' . esc_attr($key) . '\'], document.querySelector("#jsonview_' . esc_attr($key) . '"));
				</script>
			';
			// @codingStandardsIgnoreEnd
		}
		echo '</div>';
	}

	/**
	 * Convert object to array.
	 *
	 * @param mixed $data function parameter on type mixed.
	 *
	 * @return mixed
	 */
	private static function object_to_array( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			$result = array();

			foreach ( $data as $key => $value ) {
				$result[ $key ] = self::object_to_array( $value );
			}

			return $result;
		}

		return $data;
	}
}
