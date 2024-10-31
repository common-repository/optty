<?php
/**
 * Handle Optty Footer Widget
 *
 * @package Optty
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Optty_Footer_Widget
 *
 * @extends WP_Widget
 */
class Optty_Footer_Widget extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
		// Base ID of your widget.
			'optty_footer_widget',
			// Widget name will appear in UI.
			__( 'Optty Footer Widget', 'optty' ),
			// Widget description.
			array( 'description' => __( 'Optty footer widget', 'optty' ) )
		);
	}

	/**
	 * Function widget
	 *
	 * @param array $args args.
	 * @param array $instance instance.
	 * @return void
	 */
	public function widget( $args, $instance ): void {
		// Creating widget front-end.
		include_once OPTTY_PLUGIN_PATH . '/src/templates/widgets/widget-footer.php';
	}

	/**
	 * Function form
	 *
	 * @param array $instance instance.
	 * @return void
	 */
	public function form( $instance ): void {
		// Widget Backend.
	}

	/**
	 * Function update
	 *
	 * @param array $new_instance new_instance.
	 * @param array $old_instance old_instance.
	 * @return void
	 */
	public function update( $new_instance, $old_instance ): void {
		// Updating widget replacing old instances with new.
	}

}

