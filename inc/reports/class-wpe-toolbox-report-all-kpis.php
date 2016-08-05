<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPE_Toolbox_WC_Report_All_KPIs
 */
class WPE_Toolbox_WC_Report_All_KPIs extends WC_Admin_Report {

	/**
	 * Chart colours.
	 *
	 * @var array
	 */
	public $chart_colors = array();

	/**
	 * The report data.
	 *
	 * @var stdClass
	 */
	private $report_data;

	/**
	 * Get report data.
	 *
	 * @return stdClass
	 */
	public function get_report_data() {
		if ( empty( $this->report_data ) ) {
			$this->query_report_data();
		}
		return $this->report_data;
	}

	/**
	 * Get all data needed for this report and store in the class.
	 */
	private function query_report_data() {
		$this->group_by_query = 'action, ' . str_replace( 'posts.post_date', 'kpi_log.date', $this->group_by_query );

		$this->report_data = new stdClass;

		foreach ( $this->get_kpis() as $kpi => $data ) {
			$clean_kpi = $this->clean_kpi( $kpi );

			$this->report_data->$clean_kpi = (array) $this->get_kpi_report_data( $kpi, array(
				'data' => array(
					'action' => array(
						'function' => '',
						'name'     => 'action',
					),
					'ID' => array(
						'function' => 'COUNT',
						'name'     => 'count',
					),
					'date' => array(
						'function' => '',
						'name'     => 'date',
					),
				),
				'group_by'            => $this->group_by_query,
				'order_by'            => 'date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
			) );

			$this->report_data->{'total_' . $clean_kpi} = absint( array_sum( wp_list_pluck( $this->report_data->$clean_kpi, 'count' ) ) );
		}

	}

	private function get_kpi_report_data( $kpi, $args ) {
		global $wpdb;

		$default_args = array(
			'actions'             => array( $kpi ),
			'users'               => array(),
			'data'                => array(),
			'where'               => array(),
			'where_meta'          => array(),
			'query_type'          => 'get_row',
			'group_by'            => '',
			'order_by'            => '',
			'limit'               => '',
			'filter_range'        => false,
			'nocache'             => false,
			'debug'               => false,
			'parent_order_status' => false,
		);

		$args = wp_parse_args( $args, $default_args );

		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$query  = array();
		$select = array();

		foreach ( $data as $key => $value ) {
			$distinct = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}

			if ( $value['function'] ) {
				$get = "{$value['function']}({$distinct} {$key})";
			} else {
				$get = "{$distinct} {$key}";
			}

			$select[] = "{$get} as '{$value['name']}'";
		}

		$table = wpe_wc_toolbox()->get_tool( 'kpi_log' )->get_db_table_name();

		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = "FROM {$table} AS kpi_log";

		$query['where']  = "
			WHERE 	kpi_log.action 	IN ( '" . implode( "','", $actions ) . "' )
			";

		if ( ! empty( $users ) ) {
			$query['where'] .= '
                AND     kpi_log.user_id     IN ( ' . implode( ',', array_map( 'abint', $users ) ) . ' )
            ';
		}

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	kpi_log.date >= '" . date( 'Y-m-d', $this->start_date ) . "'
				AND 	kpi_log.date < '" . date( 'Y-m-d', strtotime( '+1 DAY', $this->end_date ) ) . "'
			";
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		$query          = apply_filters( 'woocommerce_reports_get_order_report_query', $query );
		$query          = implode( ' ', $query );
		$query_hash     = md5( $query_type . $query );
		$cached_results = get_transient( strtolower( get_class( $this ) ) );

		if ( $debug ) {
			echo '<pre>';
			print_r( $query );
			echo '</pre>';
		}

		if ( $debug || $nocache || false === $cached_results || ! isset( $cached_results[ $query_hash ] ) ) {
			// Enable big selects for reports
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			$cached_results[ $query_hash ] = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
			set_transient( strtolower( get_class( $this ) ), $cached_results, HOUR_IN_SECONDS );
		}

		$result = $cached_results[ $query_hash ];

		return $result;
	}

	/**
	 * Get the current range and calculate the start and end dates.
	 *
	 * @param  string $current_range
	 */
	public function calculate_current_range( $current_range ) {

		switch ( $current_range ) {

			case 'custom' :
				$this->start_date = strtotime( sanitize_text_field( $_GET['start_date'] ) );
				$this->end_date   = strtotime( 'midnight', strtotime( sanitize_text_field( $_GET['end_date'] ) ) );

				if ( ! $this->end_date ) {
					$this->end_date = current_time( 'timestamp' );
				}

				$interval = 0;
				$min_date = $this->start_date;

				while ( ( $min_date = strtotime( '+1 MONTH', $min_date ) ) <= $this->end_date ) {
					$interval ++;
				}

				// 3 months max for day view
				if ( $interval > 3 ) {
					$this->chart_groupby = 'month';
				} else {
					$this->chart_groupby = 'day';
				}
				break;

			case 'year' :
				$this->start_date    = strtotime( date( 'Y-01-01', current_time( 'timestamp' ) ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'month';
				break;

			case 'last_month' :
				$first_day_current_month = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
				$this->start_date        = strtotime( date( 'Y-m-01', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->end_date          = strtotime( date( 'Y-m-t', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->chart_groupby     = 'day';
				break;

			case 'month' :
				$this->start_date    = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'day';
				break;

			case '7day' :
				$this->start_date    = strtotime( '-6 days', current_time( 'timestamp' ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'day';
				break;

			case 'today' :
				$this->start_date    = strtotime( 'yesterday midnight', current_time( 'timestamp' ) );
				$this->end_date      = strtotime( 'today midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'hour';
				break;
		}

		// Group by
		switch ( $this->chart_groupby ) {

			case 'hour' :
				$this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date), HOUR(posts.post_date)';
				$this->chart_interval = ceil( max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 ) ) );
				$this->barwidth       = 60 * 60 * 1000;
				break;

			case 'day' :
				$this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';
				$this->chart_interval = ceil( max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) ) );
				$this->barwidth       = 60 * 60 * 24 * 1000;
				break;

			case 'month' :
				$this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date)';
				$this->chart_interval = 0;
				$min_date             = $this->start_date;

				while ( ( $min_date   = strtotime( '+1 MONTH', $min_date ) ) <= $this->end_date ) {
					$this->chart_interval ++;
				}

				$this->barwidth = 60 * 60 * 24 * 7 * 4 * 1000;
				break;
		}
	}

	/**
	 * Get the legend for the main chart sidebar.
	 *
	 * @return array
	 */
	public function get_chart_legend() {
		$legend = array();
		$this->get_report_data();

		$series = 0;

		foreach ( $this->get_kpis() as $kpi => $data ) {
			$legend[ $kpi ] = array(
				'title'            => sprintf( __( '%s %s actions', 'wpe-wc-toolbox' ), '<strong>' . $this->get_kpi_total( $kpi ) . '</strong>', strtolower( $data['header'] ) ),
				'header'           => $data['header'],
				'placeholder'      => $data['placeholder'],
				'color'            => $data['color'],
				'highlight_series' => $series++,
			);
		}

		return $legend;
	}

	public function get_kpis() {
		$kpis = array();

		$kpis['add-to-cart'] = array(
			'header'           => __( 'Add to Carts', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the add to carts.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'add-to-cart' ),
		);

		$kpis['order-placed'] = array(
			'header'           => __( 'Orders Placed', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the orders attempted to be placed. Some orders will be attempted to be placed but returned an error due to data validation or other reasons without actually placing an order.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'order-placed' ),
		);

		$kpis['order-processed'] = array(
			'header'           => __( 'Orders Processed', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the orders that should have been inserted into the database, but might not necceasrily have been paid for.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'order-processed' ),
		);

		$kpis['view-cart'] = array(
			'header'           => __( 'Cart Views', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the views of the cart page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-cart' ),
		);

		$kpis['view-checkout'] = array(
			'header'           => __( 'Checkout Views', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the views of the checkout page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-checkout' ),
		);

		$kpis['view-order-thank-you'] = array(
			'header'           => __( 'Order Thank You Pages', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the views of the order Thank You page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-order-thank-you' ),
		);

		$kpis['view-account-orders'] = array(
			'header'           => __( 'Account - Orders Views', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the views to the orders listing of the My Account page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-account-orders' ),
		);

		$kpis['view-account-address'] = array(
			'header'           => __( 'Account - Address Views', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the views of the edit address screen of the My Account page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-account-address' ),
		);

		$kpis['view-account-edit'] = array(
			'header'           => __( 'Account - Edit Views', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the edit screen of the My Account page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-account-edit' ),
		);

		$kpis['view-account'] = array(
			'header'           => __( 'Account Views', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the views of the root my account page.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'view-account' ),
		);

		$kpis['search-product'] = array(
			'header'           => __( 'Product Searches', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the product searches of the site.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'search-product' ),
		);

		$kpis['search'] = array(
			'header'           => __( 'Searches', 'wpe-wc-toolbox' ),
			'placeholder'      => __( 'This is the count of the general searches of the site.', 'wpe-wc-toolbox' ),
			'color'            => $this->get_chart_color( 'search' ),
		);

		$logged_kpis = wpe_wc_toolbox()->get_tool( 'kpi_log' )->get_logged_actions();

		foreach ( $logged_kpis as $kpi ) {
			if ( ! array_key_exists( $kpi, $kpis ) ) {

				$kpis[ $kpi ] = array(
					'header'           => $kpi,
					'placeholder'      => sprintf( __( 'This is the count of the %s actions.', 'wpe-wc-toolbox' ), $kpi ),
					'color'            => $this->get_chart_color( $kpi ),
				);

			}
		}

		// We are reversing here to be able to plot the series backwards, because the relevant points are at the top
		// However when the top points get plotted first, the not as relevant points at the bottom are on top of them
		// Making the important lines hidden... sometimes.
		return apply_filters( 'wpe_wc_toolbox_kpi_report_kpis', array_reverse( $kpis ), $this );
	}

	private function get_kpi_total( $kpi ) {
		return isset( $this->report_data->{'total_' . $this->clean_kpi( $kpi ) } ) ? absint( $this->report_data->{'total_' . $this->clean_kpi( $kpi ) } )  : 0;
	}

	private function get_chart_color( $kpi ) {
		$this->chart_colors = array(
			'add-to-cart' => '#6f308a',
			'order-placed' => '#d96826',
			'order-processed' => '#98cde5',
			'view-cart' => '#c0bc82',
			'view-checkout' => '#7f7e80',
			'view-order-thank-you' => '#d386b1',
			'view-account-orders' => '#4578b4',
			'view-account-address' => '#dd8465',
			'view-account-edit' => '#7d1615',
			'view-account' => '#e8e756',
			'search-product' => '#6e3413',
			'search' => '#ff00ff',
		);

		return array_key_exists( $kpi, $this->chart_colors ) ? $this->chart_colors[ $kpi ] : '#ecf0f1';
	}

	private function get_chart_data( $kpi ) {
		return array_values( $this->prepare_data( $kpi ) );
	}

	public function prepare_data( $kpi ) {
		$kpi = $this->clean_kpi( $kpi );
		return $this->prepare_chart_data( $this->report_data->$kpi, 'date', 'count', $this->chart_interval, $this->start_date, $this->chart_groupby );
	}

	public function clean_kpi( $kpi ) {
		return str_replace( '-', '_', $kpi );
	}

	/**
	 * Output the report.
	 */
	public function output_report() {
		$ranges = array(
			'year' => __( 'Year', 'wpe-wc-toolbox' ),
			'last_month' => __( 'Last Month', 'wpe-wc-toolbox' ),
			'month' => __( 'This Month', 'wpe-wc-toolbox' ),
			'7day'  => __( 'Last 7 Days', 'wpe-wc-toolbox' ),
		);

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
		// The list from $ranges above plus custom.
		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
			$current_range = '7day';
		}

		$this->calculate_current_range( $current_range );

		include( 'views/html-report-all-kpis.php' );
	}

	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time( 'timestamp' ) ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'wpe-wc-toolbox' ); ?>"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'wpe-wc-toolbox' ); ?>
		</a>
		<?php
	}

	/**
	 * Round our totals correctly.
	 *
	 * @param  string $amount
	 * @return string
	 */
	private function round_chart_totals( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}

	/**
	 * Get the main chart.
	 *
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		$chart_series = array();
		foreach ( $this->get_chart_legend() as $kpi => $data ) {
			$chart_series[ $data['highlight_series'] ] = array(
				'label' => esc_js( $data['header'] ),
				'data' => $this->get_chart_data( $kpi ),
				'color' => $data['color'],
				'yaxis' => 1,
				'points' => array(
					'show' => true,
					'radius' => 7,
					'lineWidth' => 4,
					'fillColor' => '#ffffff',
					'fill' => true,
				),
				'lines' => array(
					'show' => true,
					'lineWidth' => 5,
					'fill' => false,
				),
				'shadowSize' => 0,
				'hoverable' => false,
			);
		}

		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var drawGraph = function( highlight ) {
					var series = jQuery.parseJSON( '<?php echo json_encode( $chart_series ); ?>' );

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars ) {
							highlight_series.bars.fillColor = '#9c5d90';
						}

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery('.chart-placeholder.main'),
						series,
						{
							legend: {
								show: false
							},
							grid: {
								color: '#aaa',
								borderColor: 'transparent',
								borderWidth: 0,
								hoverable: true
							},
							xaxes: [ {
								color: '#aaa',
								position: "bottom",
								tickColor: 'transparent',
								mode: "time",
								timeformat: "<?php echo $this->get_chart_date_format(); ?>",
								monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
								tickLength: 1,
								minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
								font: {
									color: "#aaa"
								}
							} ],
							yaxes: [
								{
									min: 0,
									minTickSize: 1,
									tickDecimals: 0,
									color: '#d4d9dc',
									font: { color: "#aaa" }
								},
								{
									position: "right",
									min: 0,
									tickDecimals: 2,
									alignTicksWithAxis: 1,
									color: 'transparent',
									font: { color: "#aaa" }
								}
							],
						}
					);

					jQuery('.chart-placeholder').resize();
				}

				drawGraph();

				jQuery('.highlight_series').hover(
					function() {
						drawGraph( jQuery(this).data('series') );
					},
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}

	public function get_chart_date_format() {
		switch ( $this->chart_groupby ) {
			case 'hour':
				return '%d %b %H:00';
			case 'day':
				return '%d %b';
			default:
				return '%b';
		}
	}
}
