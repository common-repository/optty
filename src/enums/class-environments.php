<?php
/**
 * This file exists for reference purposes only, it is not being used anywhere.
 * Environment related variables.
 *
 * @package Optty
 */

namespace Optty\enums;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Environment constants
 */
abstract class Environments {
	public const LOCAL       = 'local';
	public const DEVELOPMENT = 'development';
	public const STAGING     = 'staging';
	public const UAT         = 'sandbox';
	public const PRODUCTION  = 'production';

	public const AUTH_URL = array(
		self::LOCAL       => 'http://host.docker.internal:3500/token',
		self::DEVELOPMENT => 'https://auth.dev.optty.com/token',
		self::STAGING     => 'https://auth.staging.optty.com/token',
		self::UAT         => 'https://auth.qa.optty.com/token',
		self::PRODUCTION  => 'https://auth.optty.com/token',
	);

	public const API_URL = array(
		self::LOCAL       => 'http://host.docker.internal:3000',
		self::DEVELOPMENT => 'https://api.dev.optty.com',
		self::STAGING     => 'https://api.staging.optty.com',
		self::UAT         => 'https://api.qa.optty.com',
		self::PRODUCTION  => 'https://api.optty.com',
	);

	public const WIDGET_URL = array(
		self::LOCAL       => 'http://localhost:9000/widget-loader.js',
		self::DEVELOPMENT => 'https://widgets.dev.optty.com/widget-loader.js',
		self::STAGING     => 'https://widgets.staging.optty.com/widget-loader.js',
		self::UAT         => 'https://widgets.qa.optty.com/widget-loader.js',
		self::PRODUCTION  => 'https://widgets.optty.com/widget-loader.js',
	);
}
