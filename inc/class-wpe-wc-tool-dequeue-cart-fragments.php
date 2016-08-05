<?php
/**
 * @package  WPE_WC_Tool_Dequeue_Cart_Fragments
 * @category Tool
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Tool_Dequeue_Cart_Fragments' ) ) :

	class WPE_WC_Tool_Dequeue_Cart_Fragments extends WPE_WC_Tool {

		/**
		 * Setup the tool
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_cart_fragments' ), 20 );
		}


		function dequeue_cart_fragments() {
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	}

endif;
