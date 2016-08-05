<?php
/**
 * Integrate WPE WC Toolbox with WooCommerce.
 *
 * @package  WPE_WC_Toolbox_Integration
 * @category Integration
 * @author   Patrick Garman
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPE_WC_Toolbox_Integration' ) ) :

	class WPE_WC_Toolbox_Integration extends WC_Integration {

		private $tools = array();

		public $customer_order_index = false;

		public $auto_logout = false;

		public $kpi_log = false;

		public $form_fields = array();

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			$this->id                 = 'wpe-wc-toolbox';
			$this->method_title       = __( 'WP Engine Ecommerce Toolkit for WooCommerce', 'wpe-wc-toolbox' );

			$this->tools = apply_filters( 'wpe_wc_toolbox_tools', array(
				/**
				 * The type "section" is a custom setting that allows us to have
				 * section headings. See $this->generate_section_html for more information.
				 */
				'performance_section' => array(
						'name' => __( 'Performance Options', 'wpe-wc-toolbox' ),
						'type' => 'section',
						'desc' => __( 'A set of options to improve the performance of your WooCommerce site.', 'wpe-wc-toolbox' ),
				),
				'auto_logout' => array(
						'class' => 'WPE_WC_Tool_Auto_Logout',
						'name' => __( 'Auto Logout', 'wpe-wc-toolbox' ),
						'desc' => __( 'Auto log customers out when they are not on relevant (cart, checkout, account) pages.', 'wpe-wc-toolbox' ),
				),
				'guest_attribution' => array(
						'class' => 'WPE_WC_Tool_Guest_Attribution',
						'name' => __( 'Guest Attribution', 'wpe-wc-toolbox' ),
						'desc' => __( 'When a guest checks out, if a user exists for their email assign that user to the order. Great to use with the `Auto Logout` tool!', 'wpe-wc-toolbox' ),
				),
				'remove_admin_counts' => array(
						'class' => 'WPE_WC_Tool_Remove_Admin_Counts',
						'name' => __( 'Remove Admin Counts', 'wpe-wc-toolbox' ),
						'desc' => __( 'For site with more orders the counts in the admin sidebar can become taxing queries, by disabling them substantial speed gains can be made.', 'wpe-wc-toolbox' ),
				),
				'customer_order_index' => array(
						'class' => 'WPE_WC_Tool_Customer_Order_Index',
						'name' => __( 'Customer Order Index', 'wpe-wc-toolbox' ),
						'desc' => __( 'Queries such as the ones which power the My Account page can be inefficient, slow, and performance draining. By using a more efficient index of order and customer data these performance issues can be resolved.', 'wpe-wc-toolbox' ),
				),
				'dequeue_cart_fragments' => array(
						'class' => 'WPE_WC_Tool_Dequeue_Cart_Fragments',
						'name' => __( 'Disable Cart Fragments JS', 'wpe-wc-toolbox' ),
						'desc' => __( 'This single javascript file is responsible for a secondary ajax call on most page loads, if you do not make use of a cart in your header or sidebar this can (usually) safely be disabled without impacting your site.', 'wpe-wc-toolbox' ),
				),
				'reporting_section' => array(
						'name' => __( 'Reporting Options', 'woocommerce-integration-demo' ),
						'type' => 'section',
						'desc' => __( 'An option to track key business and performance metrics for your WooCommerce site. This is not recommended for long term production use.', 'wpe-wc-toolbox' ),
				),
				'kpi_log' => array(
						'class' => 'WPE_WC_Tool_KPI_Log',
						'name' => __( 'KPI Log', 'wpe-wc-toolbox' ),
						'desc' => __( 'Log specific KPIs for further review later. Not recommended for long term production use.', 'wpe-wc-toolbox' ),
				),
			) );

			// Setup Tools
			foreach ( $this->tools as $key => $tool ) {
				if ( isset( $tool['class'] ) && class_exists( $tool['class'] ) ) {
					$this->form_fields[ $key ] = array(
							'title'             => $tool['name'],
							'type'              => 'checkbox',
							'desc_tip'          => true,
							'default'           => 'no',
					);

					if ( isset( $tool['desc'] ) && ! empty( $tool['desc'] ) ) {
						$this->form_fields[ $key ]['description'] = $tool['desc'];
					}

					// Only initialize the class if the option is enabled.
					if ( 'yes' === $this->get_option( $key ) ) {
						$this->$key = new $tool['class']( $key );
					}
				} else if ( ! isset( $tool['class'] ) ) { // Not a tool or a setting, just HTML.
					$this->form_fields[ $key ] = array(
							'title'             => $tool['name'],
							'type'              => $tool['type'],
							'desc_tip'          => false,
							'default'           => 'no',
							'description'       => $tool['desc'],
					);
				}
			}

			// Actions.
			add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		}

		/**
		 * Initialize integration settings form fields.
		 */
		public function init_form_fields() {
		}

		/**
		 * Allow for public tool access
		 *
		 * @param $key
		 */
		public function get_tool( $key ) {
			return isset( $this->tools[ $key ] ) && isset( $this->$key ) && $this->$key instanceof $this->tools[ $key ]['class'] ? $this->$key : null;
		}

		/**
		 * Generate section heading HTML.
		 *
		 * This is a custom setting that WooCommerce will call when the form_fields
		 * type is set to "section".
		 *
		 * https://docs.woocommerce.com/document/implementing-wc-integration/#creating-your-own-settings
		 *
		 * @access public
		 * @param mixed $key
		 * @param mixed $data
		 * @return string
		*/
		public function generate_section_html( $key, $data ) {
			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'desc_tip'          => false,
				'description'       => '',
				'title'             => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc" style="padding: 0px;">
					<h3><?php echo wp_kses_post( $data['title'] ); ?></h3>
					<?php echo $this->get_tooltip_html( $data ); ?>

				</th>
				<td>
					<p><?php echo $this->get_description_html( $data ); ?></p>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}
	}

endif;
