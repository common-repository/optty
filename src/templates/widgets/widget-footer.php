<?php // @codingStandardsIgnoreFile.
/**
 * Template file for inject Optty Footer widget
 *
 * @package Optty
 */

if (!defined('ABSPATH')) {
	exit;
}

echo "
       <div class='footer-widget' style=''>
            <div class='optty-footer-widget'></div>
            <script>
                mw('footer-widget', {
                });
            </script>
        </div>

	";

