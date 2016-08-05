<?php
/**
 * @package  WPE_WC_Tool
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool' ) ) :

	abstract class WPE_WC_Tool {

		private $key = null;

		public function __construct() {
		}

		public function setup( $key ) {
			$this->key = $key;

			add_action( 'init', array( $this, 'init' ) );
		}

		public function init() {
			// Handle DB Install
			add_action( 'woocommerce_update_options_integration_' . wpe_wc_toolbox()->id, array( $this, 'maybe_install' ) );
		}

		public function enabled() {
			return 'yes' == wpe_wc_toolbox()->get_option( $this->key );
		}

		public function should_install() {
			// Only install if the requirements are met
			if (
					method_exists( $this, 'get_install_sql' )
					&& method_exists( $this, 'get_db_version' )
					&& method_exists( $this, 'get_db_version_option_key' )
					&& method_exists( $this, 'get_db_table_name' )
					&& ! $this->get_db_version()
			) {
				return false;
			}

			$version = absint( get_option( $this->get_db_version_option_key() ) );

			return $this->get_db_version() > $version && $this->enabled();
		}

		public function maybe_install() {
			if ( $this->should_install() ) {
				$this->install();
			}
		}

		public function install() {
			global $wpdb;

			$sql = $this->get_install_sql();

			update_option( $this->get_db_version_option_key(), $this->get_db_version() );

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

endif;
