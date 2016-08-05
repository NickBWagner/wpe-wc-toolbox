<?php
/**
 * @package  WPE_WC_Toolbox_CLI
 * @category CLI
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if WP_CLI exists, and only extend it if it does
if ( defined( 'WP_CLI' ) && WP_CLI && ! class_exists( 'WPE_WC_Toolbox_CLI' ) ) :

	/**
	 * WP Engine Ecommerce Toolkit for WooCommerce
	 */
	class WPE_WC_Toolbox_CLI extends WP_CLI_Command {
		/**
		 * Regenerates the customer order index.
		 *
		 * Queries such as the ones which power the My Account page can be inefficient,
		 * slow, and performance draining. By using a more efficient index of order and
		 * customer data these performance issues can be resolved. This WP-CLI command
		 * allows you to rebuild this index.
		 *
		 * ## OPTIONS
		 *
		 * [--batch=<batch>]
		 * : The number of orders to process.
		 * ---
		 * default: 10000
		 * ---
		 *
		 * [--page=<page>]
		 * : The page offset.
		 * ---
		 * default: 1
		 * ---
		 *
		 * ## EXAMPLES
		 *
		 *     wp wc-toolbox regenerate_customer_order_index --batch=1000 --page=1
		 *
		 */
		public function regenerate_customer_order_index( $args, $assoc_args ) {
			global $wpdb;

			$orders_batch = isset( $assoc_args['batch'] ) ? absint( $assoc_args['batch'] ) : 10000;
			$orders_page  = isset( $assoc_args['page'] )  ? absint( $assoc_args['page'] )  : 1;

			$order_count  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "','", wc_get_order_types( 'reports' ) ) . "') ORDER BY post_date DESC", 'shop_order' ) );

			$total_pages  = ceil( $order_count / $orders_batch );

			$progress = \WP_CLI\Utils\make_progress_bar( 'Updating Index', $order_count );

			$orders_sql   = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "','", wc_get_order_types( 'reports' ) ) . "') ORDER BY post_date DESC", 'shop_order' );
			$batches_processed = 0;

			$coi = wpe_wc_toolbox()->get_tool( 'customer_order_index' );

			if ( is_null( $coi ) || ! method_exists( $coi, 'update_order_index' ) ) {
				WP_CLI::error( __( 'Could not find `Customer Order Index` tool to regenerate index. Process aborted.', 'wpe-wc-toolbox' ) );
				die();
			}

			WP_CLI::log( sprintf( __( '%d orders to be indexed.', 'wpe-wc-toolbox' ), $order_count ) );

			for ( $page = $orders_page; $page <= $total_pages; $page++ ) {
				$offset = ( $page * $orders_batch ) - $orders_batch;

				$sql = $wpdb->prepare( $orders_sql . ' LIMIT %d OFFSET %d', $orders_batch, max( $offset, 0 ) );
				$orders = $wpdb->get_col( $sql );

				foreach ( $orders as $order ) {
					$user_id = get_post_meta( $order, '_customer_user', true );
					$billing_email = get_post_meta( $order, '_billing_email', true );

					$coi->update_order_index( $order, $user_id, $billing_email );

					$progress->tick();
				}

				$batches_processed++;
			}

			$progress->finish();

			WP_CLI::log( sprintf( __( '%d orders processed in %d batches.', 'wpe-wc-toolbox' ), $order_count, $batches_processed ) );
		}

		/**
		 * Clean up the KPI log.
		 *
		 * ## OPTIONS
		 *
		 * [--months=<months>]
		 * : How many months of history should we keep?
		 * ---
		 * default: 1
		 * ---
		 *
		 * ## EXAMPLES
		 *
		 *     wp wc-toolbox cleanup_kpi_log --months=2
		 *
		 */
		public function cleanup_kpi_log( $args, $assoc_args ) {
			// How many months of history to keep?
			$months  = isset( $assoc_args['months'] )  ? absint( $assoc_args['months'] )  : 1;

			// Run the command
			if ( wpe_wc_toolbox()->get_tool( 'kpi_log' )->cleanup_log( $months ) ) {
				WP_CLI::success( __( 'Log cleanup successful!' ) );
			} else {
				WP_CLI::error( __( 'There was an error cleaning the KPI log.' ) );
			}
		}
	}

	WP_CLI::add_command( 'wpe-wc-toolbox', 'WPE_WC_Toolbox_CLI' );

endif;
