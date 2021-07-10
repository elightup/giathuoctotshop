<?php
namespace ELUSHOP;

use ELUSHOP\Order\ERP;
use ELUSHOP\SaveLog\SaveLog;

class Checkout {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'the_content', [ $this, 'filter_content' ] );
		add_action( 'wp_ajax_place_checkout', [ $this, 'place_checkout' ] );

		add_action( 'wp_ajax_check_voucher', [ $this, 'check_voucher' ] );
		add_action( 'wp_ajax_check_remove_voucher', [ $this, 'check_remove_voucher' ] );

		add_filter( 'body_class', [ $this, 'add_class_body' ] );

		add_action( 'wp_ajax_get_checkout', [ $this, 'ajax_get_checkout' ] );
		add_action( 'wp_ajax_set_checkout', [ $this, 'ajax_set_checkout' ] );
	}

	public function enqueue() {
		if ( ! is_cart_page() ) {
			return;
		}
		wp_enqueue_script( 'checkout', trailingslashit( ELU_SHOP_URL ) . 'assets/js/checkout.js', [ 'cart', 'wp-util' ], uniqid(), true );
	}

	public function filter_content( $content ) {
		if ( ! is_cart_page() ) {
			return $content;
		}
		ob_start();
		if ( is_cart_page() ) {
			TemplateLoader::instance()->get_template_part( 'cart' );
		}
		return $content . ob_get_clean();
	}

	public function add_class_body( $classes ) {
		if ( is_cart_page() ) {
			$classes[] = 'cart-page';
		}
		return $classes;
	}

	public function place_checkout() {
		$user           = wp_get_current_user();
		$id             = get_current_user_id();
		$data           = get_user_meta( $id, 'cart', true );
		$voucher        = filter_input( INPUT_POST, 'voucher', FILTER_SANITIZE_STRING );
		$note           = filter_input( INPUT_POST, 'note', FILTER_SANITIZE_STRING );
		$payment_method = filter_input( INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING );

		if ( empty( $data ) ) {
			wp_send_json_error( 'Giỏ hàng trống' );
		}

		$amount = 0;

		// Always refresh the product info, because users might update their prices.
		foreach ( $data as $product_id => &$product ) {
			if ( empty( $product['quantity'] ) ) {
				unset( $data[ $product_id ] );
				continue;
			}
			$product['quantity'] = (int) $product['quantity'];
			$product = array_merge( $product, Cart::get_product_info( $product_id ) );

			$amount += $product['price'] * $product['quantity'];
		}

		$info = [
			'name'           => get_user_meta( $id, 'user_name', true ),
			'phone'          => $user->user_login,
			'address'        => get_user_meta( $id, 'user_address', true ),
			'payment_method' => $payment_method,
		];

		global $wpdb;
		$wpdb->insert(
			$wpdb->orders,
			[
				'date'          => current_time( 'mysql' ),
				'status'        => 'pending',
				'push_erp'      => 'pending',
				'push_message'  => '',
				'user'          => $id,
				'amount'        => $amount,
				'note'          => $note,
				'info'          => json_encode( $info ),
				'data'          => json_encode( $data ),
				'voucher'       => $voucher,
			]
		);

		$order_id = $wpdb->insert_id;

		// Clear cart.
		delete_user_meta( $id, 'cart' );
		delete_user_meta( $id, 'checkout' );

		ERP::push( $order_id );

		$data_insert_log = [
			'object_type' => 'Đơn hàng',
			'object_id'   => $order_id,
			'user_update' => $id,
			'action'      => 'Đặt hàng',
		];
		SaveLog::insert_logs_table( $data_insert_log );

		$url = add_query_arg(
			[
				'view' => 'order',
				'id'   => $order_id,
				'type' => 'checkout',
			],
			get_permalink( ps_setting( 'confirmation_page' ) )
		);
		wp_send_json_success( $url );
	}

	public function check_voucher() {
		$voucher_choice = isset( $_POST['voucher'] ) ? $_POST['voucher'] : '';
		$total_price    = isset( $_POST['total_price'] ) ? $_POST['total_price'] : '';
		$result = [];
		$vouchers = ps_setting( 'vouchers_group' );
		foreach ( $vouchers as $voucher ) {
			if ( $voucher_choice == $voucher['voucher_id'] && $total_price > $voucher['voucher_dieukien'] ) {
				$result['voucher_id']       = $voucher['voucher_id'];
				$result['voucher_type']     = $voucher['voucher_type'];
				$result['voucher_price']    = $voucher['voucher_price'];
				$result['voucher_dieukien'] = $voucher['voucher_dieukien'];
			}
		}
		if ( empty( $result ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( $result );
	}

	public function check_remove_voucher() {
		$voucher = isset( $_POST['voucher'] ) ? $_POST['voucher'] : '';

		if ( empty( $voucher ) ) {
			wp_send_json_error();
		}
		$result = 'Đã xoá thành công';

		wp_send_json_success( $result );
	}

	public function ajax_get_checkout() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$data = get_user_meta( $id, 'checkout', true );
		if ( empty( $data ) ) {
			$data = [];
		}

		wp_send_json_success( $data );
	}

	public function ajax_set_checkout() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$data = $_POST['data'] ?? [];
		if ( empty( $data ) ) {
			$data = [];
		}

		update_user_meta( $id, 'checkout', $data );

		wp_send_json_success();
	}
}
