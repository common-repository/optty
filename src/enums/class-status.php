<?php
/**
 * Define statuses
 *
 * @package Optty
 */

namespace Optty\enums;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Request_Methods
 */
abstract class Status {
	public const SUCCESSFUL = 'successful';
	public const FAILED     = 'failed';
	public const DECLINED   = 'declined';
	public const CANCELED   = 'canceled';
	public const PENDING    = 'pending';

	// Woocommerce order status.
	public const WC_PROCESSING = 'processing';
	public const WC_ON_HOLD    = 'on-hold';
	public const WC_COMPLETED  = 'completed';
	public const WC_REFUNDED   = 'refunded';
}
