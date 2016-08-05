<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPE_Toolbox_WC_Report_Views
 */
class WPE_Toolbox_WC_Report_Views extends WPE_Toolbox_WC_Report_All_KPIs {

	public function __construct() {
		add_filter( 'wpe_wc_toolbox_kpi_report_kpis', array( $this, 'filter_kpis' ), 20, 2 );
	}

	public function filter_kpis( $kpis, $report ) {
		foreach ( $kpis as $kpi => $data ) {
			if ( false === strpos( $kpi, 'view' ) ) {
				unset( $kpis[ $kpi ] );
			}
		}

		return $kpis;
	}
}
