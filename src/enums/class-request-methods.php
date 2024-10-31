<?php
/**
 * Define request methods
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
abstract class Request_Methods {
	public const POST = 'POST';
	public const GET  = 'GET';
	public const PUT  = 'PUT';
}
