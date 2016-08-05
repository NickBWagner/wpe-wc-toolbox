<?php
/**
 * Plugin Name:     WP Engine Ecommerce Toolkit for WooCommerce
 * Plugin URI:      http://wpengine.com
 * Description:     A toolbox of performance tweaks and reporting extensions for WooCommerce.
 * Version:         1.0.0
 * Author:          WP Engine
 * Author URI:      http://wpengine.com
 * Text Domain:     wpe-wc-toolbox
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Toolbox' ) ) :

	class WPE_WC_Toolbox {

		/**
		 * Construct the plugin.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		}

		/**
		 * Initialize the plugin.
		 */
		public function init() {
			// Checks if WooCommerce is installed.
			if ( class_exists( 'WC_Integration' ) ) {
				// Tool Abstract
				require_once 'inc/class-wpe-wc-tool.php';

				// Tools
				require_once 'inc/class-wpe-wc-tool-auto-logout.php';
				require_once 'inc/class-wpe-wc-tool-customer-order-index.php';
				require_once 'inc/class-wpe-wc-tool-remove-admin-counts.php';
				require_once 'inc/class-wpe-wc-tool-kpi-log.php';
				require_once 'inc/class-wpe-wc-tool-guest-attribution.php';
				require_once 'inc/class-wpe-wc-tool-dequeue-cart-fragments.php';

				// Integration Base
				require_once 'inc/class-wpe-wc-toolbox-integration.php';

				// CLI
				require_once 'inc/class-wpe-wc-toolbox-cli.php';

				// Register the integration.
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
			}
		}

		/**
		 * Add a new integration to WooCommerce.
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WPE_WC_Toolbox_Integration';
			return $integrations;
		}

		/**
		 * Add action links to plugins listing
		 */
		public function plugin_action_links( $links ) {
			$new_links[] = '<a href="'. esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=wpe-wc-toolbox' ) ) .'">Settings</a>';

			return array_merge( $new_links, $links );
		}
	}

	$wpe_wc_toolbox = new WPE_WC_Toolbox( __FILE__ );

	/**
	 * @internal
	 * grab the toolbox integration singleton
	 * presumes WooCommerce is loaded and did_action(woocommerce_integrations)
	 */
	function wpe_wc_toolbox() {

		if ( ! did_action( 'woocommerce_integrations' ) ) {
			error_log( 'wpe_wc_toolbox() called prior to WooCommerce waking up' );
		}

		return WC()->integrations->integrations['wpe-wc-toolbox'];
	}

endif;
