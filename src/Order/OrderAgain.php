<?php
namespace ELUSHOP\Order;
use ELUSHOP\Assets;

class OrderAgain {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_ajax_place_checkout_again', [ $this, 'place_checkout_again' ] );
	}

	public function enqueue() {
		if ( ! ( is_page() && get_the_ID() == ps_setting( 'confirmation_page' ) ) ) {
			return;
		}
		Assets::enqueue_script( 'order-again' );
		Assets::localize( 'order-again', [
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'userId'     => get_current_user_id(),
			'oldOrderId' => intval( $_GET['id'] ),
			'nonce'      => wp_create_nonce( 'order-again' ),
		], 'OrderAgain' );
	}

	public function place_checkout_again() {
		global $wpdb;
		$user_id      = get_current_user_id();
		$old_order_id = (int)$_POST['old_order_id'];
		$item         = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $old_order_id ) );
		$data_product = $item->data;
		$amount       = (int)$item->amount;
		$info         = $item->info;
		$voucher      = $item->voucher;
		$note         = $item->note;

		$wpdb->insert(
			$wpdb->orders,
			[
				'date'         => current_time( 'mysql' ),
				'status'       => 'pending',
				'push_erp'     => 'pending',
				'push_message' => '',
				'user'         => $user_id,
				'amount'       => $amount,
				'note'         => $note,
				'info'         => $info,
				'data'         => $data_product,
				'voucher'      => $voucher,
			]
		);

		ERP::push( $wpdb->insert_id );

		$url = add_query_arg(
			[
				'view' => 'order',
				'id'   => $wpdb->insert_id,
				'type' => 'checkout',
			],
			get_permalink( ps_setting( 'confirmation_page' ) )
		);
		wp_send_json_success( $url );
	}
}
