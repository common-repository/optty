<?php // @codingStandardsIgnoreFile.

/**
 * Template file for inject Optty Product Details widget
 *
 * @package Optty
 */

use Optty\classes\Settings;
use Optty\classes\Authentication;
use Optty\classes\Logs;

if (!defined('ABSPATH')) {
	exit;
}

global $product;

$price = $product->get_price();
$token = Authentication::request_access_token();
$currency = get_woocommerce_currency();

echo "<div class='product-box-widget'></div>
      <script>
        mw('product-box-widget', { token: '" . esc_attr($token) . "', currency: '" . esc_attr($currency) . "', amount: '" . esc_attr($price) . "' })
      </script>";
