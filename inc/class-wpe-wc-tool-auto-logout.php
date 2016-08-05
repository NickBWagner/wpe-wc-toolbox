<?php
/**
 * @package  WPE_WC_Tool_Auto_Logout
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool_Auto_Logout' ) ) :

	class WPE_WC_Tool_Auto_Logout extends WPE_WC_Tool {

		/**
		 * Setup the tool
		 */
		public function __construct() {
			add_action( 'template_redirect', array( $this, 'maybe_logout' ) );
		}

		public function maybe_logout() {
			if ( is_user_logged_in() && ! current_user_can( 'manage_woocommerce' ) && ! $this->is_relevent_page() ) {
				$this->force_logout();
			}
		}

		public function force_logout() {
			wp_destroy_current_session();
			wp_clear_auth_cookie();
		}

		public function is_relevent_page() {
			return (
				// WooCommerce
				is_account_page() || is_cart() || is_checkout() || is_wc_endpoint_url() ||

				// Child Pages of My Account
				wc_get_page_id( 'myaccount' ) == wp_get_post_parent_id( get_the_ID() )
			);
		}
	}

endif;
