<?php
namespace ELUSHOP\Order;

use ELUSHOP\Assets;
use ELUSHOP\SaveLog\SaveLog;
use ELUSHOP\Cart;

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
			'oldOrderId' => intval( $_GET['id'] ),
			'nonce'      => wp_create_nonce( 'order-again' ),
		], 'OrderAgain' );
	}

	public function place_checkout_again() {
		global $wpdb;
		$user_id      = get_current_user_id();
		$old_order_id = (int) $_POST['old_order_id'];
		$item         = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $old_order_id ) );
		$data         = $item->data;
		$data         = json_decode( $data, true );

		$url = add_query_arg(
			[
				'view' => 'order',
				'id'   => $wpdb->insert_id,
				'type' => 'checkout',
			],
			get_permalink( ps_setting( 'confirmation_page' ) )
		);

		// B1: lấy sp trong giỏ hàng đang có
		$cart = get_user_meta( $user_id, 'cart', true );
		if ( empty( $cart ) || ! is_array( $cart ) ) {
			$cart = [];
		}

		// B2: merge cart và data hiện đang có. Giá trị $cart sẽ được update.
		foreach ( $data as $key => $value ) {
			if ( ! isset( $cart[ $key ] ) ) {
				$cart[ $key ] = $value;
				continue;
			}
			if ( ! isset( $cart[ $key ]['quantity'] ) ) {
				$cart[ $key ]['quantity'] = 0;
			}
			if ( isset( $value['quantity'] ) ) {
				$cart[ $key ]['quantity'] += $value['quantity'];
			}
		}

		// B3: update cart.

		Cart::refresh_cart_data( $cart );
		update_user_meta( $user_id, 'cart', $cart );
		$return = [
			'cart' => $cart,
			'url'  => get_permalink( ps_setting( 'cart_page' ) ),
		];
		// wp_send_json_success( $url );
		wp_send_json_success( $return );
	}
}
