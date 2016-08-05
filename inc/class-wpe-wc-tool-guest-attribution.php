<?php
/**
 * @package  WPE_WC_Tool_Guest_Attribution
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool_Guest_Attribution' ) ) :

	class WPE_WC_Tool_Guest_Attribution extends WPE_WC_Tool {

		/**
		 * Setup the tool
		 */
		public function __construct() {
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_attribution' ) );
		}

		public function process_attribution( $order_id ) {
			if ( ! is_user_logged_in() ) {
				$email = get_post_meta( $order_id, '_billing_email', true );
				$email = trim( $email );

				if ( ! empty( $email ) ) {
					$user = get_user_by( 'email', $email );

					if ( is_object( $user ) ) {
						update_post_meta( $order_id, '_customer_user', $user->ID );
					}
				}
			}
		}
	}

endif;
