<?php
/**
 * @package  WPE_WC_Tool_Remove_Admin_Counts
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool_Remove_Admin_Counts' ) ) :

	class WPE_WC_Tool_Remove_Admin_Counts extends WPE_WC_Tool {

		/**
		 * Setup the tool
		 */
		public function __construct() {
			add_filter( 'wp_count_comments', array( $this, 'unmoderated_comment_counts' ), 100, 2 );
			remove_filter( 'wp_count_comments', array( 'WC_Comments', 'wp_count_comments' ), 10 );

			add_filter( 'woocommerce_include_processing_order_count_in_menu', '__return_false' );
		}

		public function unmoderated_comment_counts( $stats, $post_id ) {
			if ( 0 === $post_id ) {
				$stats = json_decode( json_encode( array(
						'moderated'      => 0,
						'approved'       => 0,
						'post-trashed'   => 0,
						'trash'          => 0,
						'total_comments' => 0,
				) ) );
			}

			return $stats;
		}
	}

endif;
