<?php
namespace ELUSHOP\Order;

class UpdateOrder {
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_api' ] );
	}
	public function register_rest_api() {
		register_rest_route( 'giathuoc', '/update-order/', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_order' ],
			'permission_callback' => '__return_true',
		) );
	}
	public function update_order( $params ) {
		global $wpdb;

		$output                 = [];
		$id                     = $params['id'];
		$products_not_delivered = $params['products_delivered'];

		if ( empty( $id ) ) {
			$output['message'] = 'Id order chưa có';
			return $output;
		}
		$wpdb->update(
			$wpdb->orders,
			[ 'data_update' => json_encode( $products_not_delivered ) ],
			[ 'id' => $id ]
		);
		$output['message'] = 'Đã update order thành công';

		return $output;
	}

}
