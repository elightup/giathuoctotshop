<?php
namespace ELUSHOP\Order;

class Ajax {
	public function __construct() {
		add_action( 'wp_ajax_gtt_order_close', [ $this, 'order_close' ] );
		add_action( 'wp_ajax_gtt_order_open', [ $this, 'order_open' ] );
		add_action( 'wp_ajax_gtt_order_repush', [ $this, 'order_repush' ] );
	}

	public function order_close() {
		check_ajax_referer( 'close' );
		$id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			wp_send_json_error( 'Yêu cầu không hợp lệ' );
		}
		$this->update_order_status( $id, 'completed' );
		wp_send_json_success();
	}

	public function order_open() {
		check_ajax_referer( 'open' );
		$id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			wp_send_json_error( 'Yêu cầu không hợp lệ' );
		}
		$this->update_order_status( $id, 'pending' );
		wp_send_json_success();
	}

	public function order_repush() {
		check_ajax_referer( 'repush' );
		$id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			wp_send_json_error( 'Yêu cầu không hợp lệ' );
		}
		ERP::push( $id );
		wp_send_json_success();
	}

	private function update_order_status( $id, $status ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ]
		);
	}
}