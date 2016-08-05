<?php
/**
 * @package  WPE_WC_Tool_KPI_Log
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool_KPI_Log' ) ) :

	class WPE_WC_Tool_KPI_Log extends WPE_WC_Tool {

		/**
		 * Setup the tool
		 */
		public function __construct( $key ) {
			$this->setup( $key );

			add_action( 'woocommerce_add_to_cart', array( $this, 'log_add_to_cart' ), 10, 0 );

			add_action( 'woocommerce_before_checkout_process', array( $this, 'log_place_order' ), 10, 0 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'log_process_order' ), 10, 0 );

			add_action( 'valid-paypal-standard-ipn-request', array( $this, 'log_paypal_ipn' ), 10, 0 );

			// Logging page views on shut down, so we don't impact the live site.
			add_action( 'shutdown', array( $this, 'log_relevant_page_views' ), 1, 0 );

			add_filter( 'woocommerce_admin_reports', array( $this, 'add_kpi_reports' ) );
		}

		public function log_add_to_cart() {
			$this->log_kpi( 'add-to-cart' );
		}

		public function log_place_order() {
			$this->log_kpi( 'order-placed' );
		}

		public function log_process_order() {
			$this->log_kpi( 'order-processed' );
		}

		public function log_relevant_page_views() {
			/**
			 * Endpoints first so that we can log these over root pages
			 */
			if ( is_wc_endpoint_url( 'orders' ) ) {
				return $this->log_kpi( 'view-account-orders' );
			}

			if ( is_wc_endpoint_url( 'edit-address' ) ) {
				return $this->log_kpi( 'view-account-address' );
			}

			if ( is_wc_endpoint_url( 'edit-account' ) ) {
				return $this->log_kpi( 'view-account-edit' );
			}

			if ( is_wc_endpoint_url( 'order-received' ) ) {
				return $this->log_kpi( 'view-order-thank-you' );
			}

			/**
			 * Now pages if no endpoints were valid
			 */

			if ( is_cart() ) {
				return $this->log_kpi( 'view-cart' );
			}

			if ( is_checkout() ) {
				return $this->log_kpi( 'view-checkout' );
			}

			if ( is_account_page() ) {
				return $this->log_kpi( 'view-account' );
			}

			if ( is_search() ) {
				$suffix = isset( $_GET['post_type'] ) && ! empty( $_GET['post_type'] ) ? sprintf( '-%s', sanitize_title( $_GET['post_type'] ) ) : '';

				return $this->log_kpi( 'search' . $suffix );
			}
		}

		public function get_db_version() {
			return 1;
		}

		public function get_db_version_option_key() {
			return 'WPE_WC_TOOLBOX_KPILOG_DB';
		}

		public function get_db_table_name() {
			global $wpdb;

			return $wpdb->prefix . 'woocommerce_kpi_log';
		}

		public function get_install_sql() {
			global $wpdb;

			return "CREATE TABLE `{$this->get_db_table_name()}` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`action` varchar(32) NOT NULL DEFAULT '',
				`user_id` int(11) DEFAULT NULL,
				`date` datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `action` (`action`),
				KEY `user_id` (`date`)
            ) {$wpdb->get_charset_collate()};";
		}

		public function log_kpi( $action ) {
			global $wpdb;

			$sql = $wpdb->prepare( "INSERT INTO `{$this->get_db_table_name()}` (`action`,`user_id`) VALUES (%s, %d);", $action, get_current_user_id() );

			$result = $wpdb->query( $sql );

			if ( ! is_wp_error( $result ) ) {
				return (bool) $result;
			} else {
				return false;
			}
		}

		public function add_kpi_reports( $reports ) {
			$reports['wpe-wc-tool-kpi-log'] = array(
				'title' => __( 'KPI Log', 'wpe-wc-toolbox' ),
				'reports' => array(
						'high-impact' => array(
								'title'       => __( 'High Impact', 'wpe-wc-toolbox' ),
								'description' => 'These KPIs are considered high impact to the performance of your Ecommerce platform. Use these results to see how your store is performing.',
								'hide_title'  => true,
								'callback'    => array( $this, 'get_report' ),
						),
						'views' => array(
								'title'       => __( 'Views', 'wpe-wc-toolbox' ),
								'description' => 'These KPIs are related directly to logging the views on individual pages. Use these results to track your traffic.',
								'hide_title'  => true,
								'callback'    => array( $this, 'get_report' ),
						),
						'all-kpis' => array(
								'title'       => __( 'All KPIs', 'wpe-wc-toolbox' ),
								'description' => 'This is the comprehensive list of KPIs for your Ecommerce platform.',
								'hide_title'  => true,
								'callback'    => array( $this, 'get_report' ),
						),
				),
			);

			return $reports;
		}

		public function get_report( $name ) {
			$name  = sanitize_title( str_replace( '_', '-', $name ) );
			$class = 'WPE_Toolbox_WC_Report_' . str_replace( '-', '_', $name );

			require_once( apply_filters( 'wc_admin_reports_path', 'reports/class-wpe-toolbox-report-all-kpis.php', 'all-kpis', 'WPE_Toolbox_WC_Report_All_KPIs' ) );

			if ( 'all-kpis' != $name ) {
				include_once( apply_filters( 'wc_admin_reports_path', 'reports/class-wpe-toolbox-report-' . $name . '.php', $name, $class ) );
			}

			if ( ! class_exists( 'WPE_Toolbox_WC_Report_All_KPIs' ) || ! class_exists( $class ) ) {
				return; }

			$report = new $class();

			$report->output_report();
		}

		public function get_logged_actions() {
			global $wpdb;

			$kpis = $wpdb->get_col( "SELECT distinct(action) from {$this->get_db_table_name()}" );

			return ! is_wp_error( $kpis ) ? $kpis : array();
		}

		public function cleanup_log( $months = 1 ) {
			global $wpdb;

			if ( 0 == $months ) {
				$sql = "TRUNCATE TABLE {$this->get_db_table_name()}";
			} else {
				$date = Date( 'Y-m-d H:i:s', strtotime( '-' . $months . ' months' ) );
				$sql = $wpdb->prepare( "DELETE FROM {$this->get_db_table_name()} WHERE date < %s", $date );
			}

			$cleanup = $wpdb->query( $sql );

			return ! is_wp_error( $cleanup );
		}
	}

endif;
