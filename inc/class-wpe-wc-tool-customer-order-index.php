<?php
/**
 * @package  WPE_WC_Tool_Customer_Order_Index
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool_Customer_Order_Index' ) ) :

	class WPE_WC_Tool_Customer_Order_Index extends WPE_WC_Tool {

		/**
		 * Internal hint if we should even consider filtering posts_request
		 *
		 * @var bool
		 */
		private $maybe_account_query = false;

		/**
		 * Setup the tool
		 */
		public function __construct( $key ) {
			$this->setup( $key );

			// Hook into add/update post meta to keep index updated on the fly
			add_action( 'add_post_meta', array( $this, 'update_index_from_meta' ), 10, 3 );
			add_action( 'update_post_meta', array( $this, 'update_index_from_meta_update' ), 10, 4 );

			// Queue up the filters for query replacement
			add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'customize_my_account_query_args' ) );

			// Filter WP_Query to override My Accounts query dynamically, then remove our filters
			add_filter( 'posts_request', array( $this, 'filter_wp_query' ), 20, 2 );
		}

		public function get_db_version() {
			return 1;
		}

		public function get_db_version_option_key() {
			return 'WPE_WC_TOOLBOX_COI_DB';
		}

		public function get_db_table_name() {
			global $wpdb;

			return $wpdb->prefix . 'woocommerce_customer_order_index';
		}

		public function get_install_sql() {
			global $wpdb;

			return "CREATE TABLE `{$this->get_db_table_name()}` (
                `order_id` int(11) unsigned NOT NULL,
                `user_id` int(11) DEFAULT NULL,
                `email` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`order_id`),
                KEY `user_id` (`user_id`),
                KEY `email` (`email`)
            ) {$wpdb->get_charset_collate()};";
		}

		public function customize_my_account_query_args( $args ) {
			$this->maybe_account_query = true;

			$this->queue_filters();

			return $args;
		}

		public function filter_wp_query( $query, $wp_query ) {
			if ( $this->maybe_account_query ) {
				$this->queue_filters( false );

				$this->maybe_account_query = false;
			}

			return $query;
		}

		public function queue_filters( $turning_on = true ) {
			// Are we turning filters on, or off?
			$function = $turning_on ? 'add_filter' : 'remove_filter';

			// Hook it up!
			$function( 'posts_where', array( $this, 'filter_posts_where' ), 20, 2 );
			$function( 'posts_join', array( $this, 'filter_posts_join' ), 20, 2 );
		}

		public function filter_posts_where( $where, $wp_query ) {
			global $wpdb;

			$user_id = get_current_user_id();

			$where = "AND {$wpdb->posts}.post_type IN ('" . implode( "','", wc_get_order_types( 'reports' ) ) . "') AND {$this->get_column_for_query( 'user_id' )} = {$user_id}";

			return $where;
		}

		public function filter_posts_join( $join, $wp_query ) {
			global $wpdb;

			$join = "JOIN {$this->get_db_table_name()} ON ( {$wpdb->posts}.ID = {$this->get_column_for_query( 'order_id' )} )";

			return $join;
		}

		public function get_column_for_query( $column ) {
			return $this->get_db_table_name() . '.' . $column;
		}

		public function update_index_from_meta( $object_id, $meta_key, $meta_value ) {
			if ( 'shop_order' == get_post_type( $object_id ) && '_customer_user' == $meta_key ) {
				$this->update_order_user_index( $object_id, absint( $meta_value ) );
			}

			if ( 'shop_order' == get_post_type( $object_id ) && '_billing_email' == $meta_key ) {
				$this->update_order_email_index( $object_id, absint( $meta_value ) );
			}
		}

		public function update_index_from_meta_update( $meta_id, $object_id, $meta_key, $meta_value ) {
			$this->update_index_from_meta( $object_id, $meta_key, $meta_value );
		}

		/**
		 * Update order index
		 *
		 * @param  int    $order_id Order ID
		 * @param  int    $user_id  User ID
		 * @param  string $email Order email
		 *
		 * @return bool           If the index is updated, true will be returned. If no update was performed (either due to a failure or if the index was already up to date) false will be returned.
		 */
		public function update_order_index( $order_id, $user_id = null, $email = null ) {
			global $wpdb;

			$user_id = absint( $user_id );
			$email = sanitize_email( $email );

			$sql = $wpdb->prepare( "INSERT INTO `{$this->get_db_table_name()}` (`order_id`,`user_id`, `email`) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE `user_id` = %d, `email` = %s;", $order_id, $user_id, $email, $user_id, $email );

			$result = $wpdb->query( $sql );

			if ( ! is_wp_error( $result ) ) {
				return (bool) $result;
			} else {
				return false;
			}
		}

		/**
		 * Update order user index
		 *
		 * @param  int $order_id Order ID
		 * @param  int $user_id  User ID
		 *
		 * @return bool           If the index is updated, true will be returned. If no update was performed (either due to a failure or if the index was already up to date) false will be returned.
		 */
		public function update_order_user_index( $order_id, $user_id = null ) {
			global $wpdb;

			$user_id = absint( $user_id );

			$sql = $wpdb->prepare( "INSERT INTO `{$this->get_db_table_name()}` (`order_id`,`user_id`) VALUES (%d, %d) ON DUPLICATE KEY UPDATE `user_id` = %d;", $order_id, $user_id, $user_id );

			$result = $wpdb->query( $sql );

			if ( ! is_wp_error( $result ) ) {
				return (bool) $result;
			} else {
				return false;
			}
		}

		/**
		 * Update order email index
		 *
		 * @param  int    $order_id Order ID
		 * @param  string $email Order email
		 *
		 * @return bool           If the index is updated, true will be returned. If no update was performed (either due to a failure or if the index was already up to date) false will be returned.
		 */
		public function update_order_email_index( $order_id, $email = null ) {
			global $wpdb;

			$email = sanitize_email( $email );

			$sql = $wpdb->prepare( "INSERT INTO `{$this->get_db_table_name()}` (`order_id`,`email`) VALUES (%d, %s) ON DUPLICATE KEY UPDATE `email` = %s;", $order_id, $email, $email );

			$result = $wpdb->query( $sql );

			if ( ! is_wp_error( $result ) ) {
				return (bool) $result;
			} else {
				return false;
			}
		}

		/**
		 * Get customer who placed an order
		 *
		 * @param  int $order_id Order ID
		 * @return int           Customer ID who placed order
		 */
		public function get_order_customer_id( $order_id ) {
			global $wpdb;

			$sql    = $wpdb->prepare( "SELECT `user_id` FROM {$this->get_db_table_name()} WHERE `order_id` = %d", $order_id );
			$result = $wpdb->get_var( $sql );

			if ( ! is_wp_error( $result ) ) {
				return absint( $result );
			} else {
				return 0;
			}
		}

		/**
		 * Get customer who placed an order
		 *
		 * @param  int $order_id Order ID
		 * @return int           Customer ID who placed order
		 */
		public function get_order_customer_email( $order_id ) {
			global $wpdb;

			$sql    = $wpdb->prepare( "SELECT `email` FROM {$this->get_db_table_name()} WHERE `order_id` = %d", $order_id );
			$result = $wpdb->get_var( $sql );

			if ( ! is_wp_error( $result ) ) {
				return sanitize_email( $result );
			} else {
				return null;
			}
		}

		/**
		 * Get all orders placed with a customers ID
		 *
		 * @param  int $user_id Customer's user ID
		 * @return array           Array of all orders placed by customer
		 */
		public function get_order_by_customer_id( $user_id, $limit = 10, $offset ) {
			global $wpdb;

			// Don't pull all guest orders this way, things may break.
			if ( 0 == $user_id ) {
				return array();
			}

			$sql    = $wpdb->prepare( "SELECT `order_id` FROM {$this->get_db_table_name()} WHERE `user_id` = %d ORDER BY `order_id` DESC LIMIT %d, %d", $user_id, $offset, $limit );
			$result = $wpdb->get_col( $sql );

			if ( ! is_wp_error( $result ) && is_array( $result ) ) {
				return $result;
			} else {
				return array();
			}
		}

		/**
		 * Get all orders placed with a customers email
		 *
		 * @param  int $user_id Customer's user ID
		 * @return array           Array of all orders placed by customer
		 */
		public function get_order_by_customer_email( $email, $limit = 10, $offset = 0 ) {
			global $wpdb;

			$sql    = $wpdb->prepare( "SELECT `order_id` FROM {$this->get_db_table_name()} WHERE `email` = %s ORDER BY `order_id` DESC LIMIT %d, %d", $email, $offset, $limit );
			$result = $wpdb->get_col( $sql );

			if ( ! is_wp_error( $result ) && is_array( $result ) ) {
				return $result;
			} else {
				return array();
			}
		}

		/**
		 * Server intense function to get all orders that were placed by a guest.
		 *
		 * @return array Array of returned order IDs
		 */
		public function get_guest_orders( $limit = 0, $offset = 0 ) {
			global $wpdb;

			$sql    = $wpdb->prepare( "SELECT `order_id` FROM {$this->get_db_table_name()} WHERE `user_id` = %d LIMIT %d, %d", 0, $offset, $limit );
			$result = $wpdb->get_col( $sql );

			if ( ! is_wp_error( $result ) && is_array( $result ) ) {
				return $result;
			} else {
				return array();
			}
		}
	}

endif;
