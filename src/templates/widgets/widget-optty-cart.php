<?php // @codingStandardsIgnoreFile.
/**
 * Template file for Optty cart box widget
 *
 * @package Optty
 */

if (!defined('ABSPATH')) {
	exit;
}

global $woocommerce;
$amount = $woocommerce->cart->get_total('');
echo "
	<div class='cart-box-widget'></div>
	<!-- The data in this input tag is needed for when the cart page has fragments rerendered -->
	<input type='hidden' id='cart-total' value='" . esc_attr($amount) . "'>
	<script type='text/javascript'>
		mw( 'cart-box-widget', { amount: " . esc_attr($amount) . " } ) 
	</script>
";

