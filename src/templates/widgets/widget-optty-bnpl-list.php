<?php // @codingStandardsIgnoreFile.
/**
 * Template file for injecting the Optty Product Listing widget
 *
 * @package Optty
 */

if (!defined('ABSPATH')) {
	exit;
}

global $product;

$id = $product->get_id();
$price = $product->get_price();

echo "
 	<div id='product-listing-widget-" . esc_attr($id) . "' class='product-listing-widget' data-amount='" . esc_attr($price) . "'></div>
	<script type='text/javascript'>
		mw( 'product-listing-widget', { amount: '" . esc_attr($price) . "' } );
	</script>
	";