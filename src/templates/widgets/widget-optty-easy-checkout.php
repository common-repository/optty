<?php // @codingStandardsIgnoreFile.
/**
 * Template file for inject Optty Easy Checkout widget
 *
 * @package Optty
 */

if (!defined('ABSPATH')) {
	exit;
}

$description = $this->get_description();
if ($description) {
	echo wpautop(wptexturize(esc_html($description)));
}

echo "
	<div class='optty-easy-checkout-widget'></div>
	<script type='text/javascript'>
		mw( 'optty-easy-checkout-widget', { initialAmount: '" . esc_attr(WC()->cart->total) . "' } ); 
        		
    	jQuery( window ).on( 'updated_checkout payment_method_selected', () => {
        	if('optty' === jQuery(\"input[name='payment_method']:checked\").val()){
        		window.dispatchEvent( new CustomEvent( 'reload_widget' , { detail: { widgetName: 'optty-easy-checkout-widget' } } ) )
        	}
        } );
        window.addEventListener( 'message', ( event ) => {
            if ( 'bnplSelected' === event.data.type ) {
                jQuery( '#selected_bnpl' ).val( event.data.bnplName )
            }
        } )
	</script>
	";

