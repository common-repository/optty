<?php
/**
 * Optty plugin main file.
 *
 * @package Optty
 */

use Dotenv\Dotenv;
use Optty\classes\Authentication;
use Optty\classes\Logs;
use Optty\classes\Settings;

/**
 * Optty plugin details declaration.
 *
 * @package           Optty
 *
 * @wordpress-plugin
 * Plugin Name:       Optty
 * Plugin URI:        https://optty.com
 * Description:       One platform integrating you to the world of Buy Now Pay Later Payment gateways globally.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Optty
 * Author URI:        https://optty.com
 * Developer:         Optty
 * Developer URI:     https://optty.com
 * Text Domain:       optty
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

define( 'OPTTY_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'OPTTY_PLUGIN_VER', '1.0.0' );

if ( ! class_exists( 'Optty' ) ) {
	/**
	 * Class Optty
	 */
	class Optty {

		/**
		 * Singleton instance of optty class
		 *
		 * @var Optty|null $instance
		 */
		private static ? Optty $instance = null;

		/**
		 * Creates an instance of Optty class
		 *
		 * @return Optty
		 */
		public static function get_instance() : Optty {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 *  Construct
		 * Prevent cloning of object
		 */
		private function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope', 'optty' ), '1.0' );
		}

		/**
		 * See PHP docs
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope', 'optty' ), '1.0' );
		}

		/**
		 * Register object in hook when instantiated
		 *
		 * @return void
		 */
		protected function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Class initialization function
		 */
		public function init(): void {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			include_once OPTTY_PLUGIN_PATH . '/src/enums/class-status.php';

			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-cache.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-logs.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-request.php';

			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-authentication.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-settings.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-refund.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-utils.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-payment.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-optty-footer-widget.php';
			include_once OPTTY_PLUGIN_PATH . '/src/classes/class-optty-gateway.php';

			Logs::init();
			add_action( 'wp_enqueue_scripts', array( $this, 'register_script' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_script' ) );
			add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'product_list_bnpl_container' ) );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway_class' ) );
			add_action( 'woocommerce_after_order_notes', array( $this, 'checkout_hidden_field' ) );
			add_action( 'widgets_init', array( $this, 'load_footer_widget' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_action( 'woocommerce_single_product_summary', array( $this, 'optty_product_detail_widget' ) );
			add_action( 'woocommerce_proceed_to_checkout', array( $this, 'optty_cart_widget' ) );
			add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'optty_cart_widget' ) );
		}

		/**
		 * Register the widget related JS.
		 */
		public function register_script(): void {
			try {
				$src      = Settings::get( 'widget_url' );
				$token    = Authentication::request_access_token();
				$currency = get_woocommerce_currency();

				$script_path  = '/build/index.js';
				$script_asset = array(
					'dependencies' => array(
						'jQuery',
					),
					'version'      => 'df820a0ee7382369cc921611b2d7bca7',
				);
				$script_url   = plugins_url( $script_path, __FILE__ );

				wp_enqueue_script( 'optty', $script_url, array( 'jquery' ), $script_asset['version'], false );
				wp_localize_script( 'optty', 'script_vars', array( 'widget_url' => $src ) );

				wp_add_inline_script( 'optty', '!function(e,t,n,o,d,i){e["Optty-Widget-SDK"]=o,e.mw=e.mw||function(){(e.mw.q=e.mw.q||[]).push(arguments)},d=t.createElement(n),i=t.getElementsByTagName(n)[0],d.id=o,d.src="' . $src . '",d.async=1,jQuery(window).on("load",function(){i.parentNode.insertBefore(d,i)})}(window,document,"script","mw");' );
				wp_add_inline_script( 'optty', "mw('init', { token: '" . $token . "', currency: '" . $currency . "', initialAmount: 0, mode: 'live' })" );
			} catch ( Exception $e ) {
				Logs::error( 'Could not get access token using merchant credentials', $e );
			}

		}

		/**
		 * Register the admin related JS.
		 */
		public function admin_register_script(): void {
			wp_enqueue_style( 'jsonview_css', plugins_url( '/src/assets/scss/jsonview.bundle.css', __FILE__ ), array(), '1.0.0' );
			wp_enqueue_script( 'jsonview_js', plugins_url( '/src/assets/js/jsonview.bundle.js', __FILE__ ), array(), '1.0.0', false );
		}

		/**
		 * Add the container for BNPL information to the product listing products
		 */
		public function product_list_bnpl_container(): void {
			include OPTTY_PLUGIN_PATH . '/src/templates/widgets/widget-optty-bnpl-list.php';
		}

		/**
		 * Function optty_add_gateway_class
		 *
		 * @param array $gateways gateways.
		 * @return array
		 */
		public function add_gateway_class( array $gateways ): array {
			$gateways[] = 'Optty_Gateway';
			return $gateways;
		}

		/**
		 * Returns the template to render the product details widget
		 *
		 * @return void
		 */
		public function optty_product_detail_widget() {
			include_once OPTTY_PLUGIN_PATH . '/src/templates/widgets/widget-optty-product-details.php';
		}

		/**
		 * Returns the template to render the cart box widget
		 *
		 * @return void
		 */
		public function optty_cart_widget() {
			include_once OPTTY_PLUGIN_PATH . '/src/templates/widgets/widget-optty-cart.php';
		}

		/**
		 * Add hidden field for selected BNPL provider
		 *
		 * @param mixed $checkout Instance of WC_Checkout.
		 */
		public function checkout_hidden_field( $checkout ): void {
			echo '<div id="selected_bnpl_hidden_checkout_field"><input type="hidden" class="input-hidden" name="selected_bnpl" id="selected_bnpl" value=""></div>';
		}

		/**
		 * Add Optty settings and logs link to plugin page
		 *
		 * @param array $links An array of existing links.
		 *
		 * @return array
		 */
		public function plugin_action_links( array $links ): array {
			$setting_link = $this->get_setting_link();
			$logs_link    = $this->get_logs_link();
			$plugin_links = array(
				'<a href="' . $setting_link . '">' . __( 'Settings', 'optty' ) . '</a>',
				'<a href="' . $logs_link . '">' . __( 'Logs', 'optty' ) . '</a>',
			);

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Get setting link.
		 *
		 * @return string Setting link
		 */
		public function get_setting_link(): string {
			$section_slug = 'optty';

			$params = array(
				'page'    => 'wc-settings',
				'tab'     => 'checkout',
				'section' => $section_slug,
			);

			return add_query_arg( $params, 'admin.php' );
		}

		/**
		 * Get logs link
		 *
		 * @return string Logs Link
		 */
		public function get_logs_link(): string {
			$params = array(
				'page' => 'wc-optty-logs',
			);

			return add_query_arg( $params, 'admin.php' );
		}

		/**
		 * Register Optty Footer Widget
		 */
		public function load_footer_widget() {
			register_widget( 'Optty_Footer_Widget' );
		}
	}

	Optty::get_instance();
}


/**
 * Main instance Optty WooCommerce.
 *
 * Returns the main instance of Optty.
 *
 * @return Optty
 */
function optty(): Optty {
	return Optty::get_instance();
}
