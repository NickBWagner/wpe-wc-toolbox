<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPE_Toolbox_WC_Report_High_Impact
 */
class WPE_Toolbox_WC_Report_High_Impact extends WPE_Toolbox_WC_Report_All_KPIs {

	public $high_impact_kpis = array(
		'order-processed',
		'add-to-cart',
		'view-account-orders',
		'search-product',
	);

	public function __construct() {
		$this->high_impact_kpis = apply_filters( 'wpe_wc_toolbox_high_impact_kpis', $this->high_impact_kpis );

		add_filter( 'wpe_wc_toolbox_kpi_report_kpis', array( $this, 'filter_kpis' ), 20, 2 );
	}

	public function filter_kpis( $kpis, $report ) {
		foreach ( $kpis as $kpi => $data ) {
			if ( ! in_array( $kpi, $this->high_impact_kpis ) ) {
				unset( $kpis[ $kpi ] );
			}
		}

		return $kpis;
	}
}
