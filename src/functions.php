<?php
function ps_setting( $name ) {
	if ( ! function_exists( 'rwmb_meta' ) ) {
		return false;
	}
	return rwmb_meta( $name, [ 'object_type' => 'setting' ], 'gtt_shop' );
}

function get_cities_array() {
	$arr_context = [
		'ssl' => [
			'verify_peer'      => false,
			'verify_peer_name' => false,
		],
	];

	$cities = json_decode( file_get_contents(
		plugin_dir_url( __FILE__ ) . '/User/cities.json',
		false,
		stream_context_create( $arr_context )
	) );
	return json_decode( json_encode( $cities ), true );
}
