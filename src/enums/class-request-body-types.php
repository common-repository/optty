<?php
/**
 * Request body types
 *
 * @package Optty
 */

namespace Optty\enums;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Request_Body_Types
 */
abstract class Request_Body_Types {
	public const JSON        = 'json';
	public const FORM_PARAMS = 'form_params';
	public const QUERY       = 'query';
}
