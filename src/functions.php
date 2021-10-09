<?php
function ps_setting( $name ) {
	if ( ! function_exists( 'rwmb_meta' ) ) {
		return false;
	}
	return rwmb_meta( $name, [ 'object_type' => 'setting' ], 'gtt_shop' );
}

define('DV_CITIES', file_get_contents( plugin_dir_url(__FILE__) . '/User/cities.json' ) );
function get_cities_array() {
	$cities = json_decode( DV_CITIES );
	return json_decode( json_encode( $cities ), true );
}