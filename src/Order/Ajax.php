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
		wp_send_json_success( [
			'button' => '<a href="#" class="gtt-button gtt-open" data-id="' . $id . '" title="Đánh dấu đang xử lý"><span class="dashicons dashicons-hourglass"></span></a>',
			'status' => '<span class="badge badge--success">Đã hoàn thành</span>'
		] );
	}

	public function order_open() {
		check_ajax_referer( 'open' );
		$id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			wp_send_json_error( 'Yêu cầu không hợp lệ' );
		}
		$this->update_order_status( $id, 'pending' );
		wp_send_json_success( [
			'button' => '<a href="#" class="gtt-button gtt-close" data-id="' . $id . '" title="Đánh dấu hoàn thành"><span class="dashicons dashicons-yes"></span></a>',
			'status' => '<span class="badge">Đang xử lý</span>'
		] );
	}

	public function order_repush() {
		check_ajax_referer( 'repush' );
		$id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			wp_send_json_error( 'Yêu cầu không hợp lệ' );
		}
		$data = ERP::push( $id );

		$statuses = [
			'pending'   => [ 'badge', __( 'Có lỗi khi đẩy lên ERP', 'elu-shop' ) ],
			'completed' => [ 'badge badge--success', __( 'Đã đẩy lên ERP', 'elu-shop' ) ],
		];
		$status = $statuses[ $data['status'] ];
		$status = sprintf( '<span class="%s">%s</span><br>%s', $status[0], $status[1], $data['message'] );
		$data['status'] = $status;

		wp_send_json_success( $data );
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