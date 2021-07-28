<?php
namespace ELUSHOP;

use ELUSHOP\Order\ERP;

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
		add_action( 'mb_settings_page_load', [ $this, 'save_setting_to_option' ], 20 );
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

	public function save_setting_to_option( $args ) {
		if ( $args['id'] !== 'gtt-shop-voucher' ) {
			return;
		}
		$vouchers = ps_setting( 'vouchers_group' );
		foreach ( $vouchers as $voucher ) {
			add_option( 'voucher_' . $voucher['voucher_id'], 0, '', 'yes' );
		}
	}

	public function place_checkout() {
		$user           = wp_get_current_user();
		$id             = get_current_user_id();
		$data           = get_user_meta( $id, 'cart', true );
		$voucher        = isset( $_POST['voucher'] ) ? $_POST['voucher'] : '';
		$voucher        = wp_unslash( $voucher );
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

			$price = $product['price'];
			$role = is_user_logged_in() ? wp_get_current_user()->roles[0] : '';
			switch ( $role ) {
				case 'vip2':
					$price = $product['price_vip2'];
					break;
				case 'vip3':
					$price = $product['price_vip3'];
					break;
				case 'vip4':
					$price = $product['price_vip4'];
					break;
				case 'vip5':
					$price = $product['price_vip5'];
					break;
				case 'vip6':
					$price = $product['price_vip6'];
					break;
			}
			$amount += $price * $product['quantity'];
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

		if ( $voucher ) {
			$voucher_id  = json_decode( $voucher )->voucher_id;
			$old_voucher = (int)get_option( 'voucher_' . $voucher_id );
			update_option( 'voucher_' . $voucher_id, $old_voucher + 1 );
		}

		ERP::push( $order_id );

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
		$result         = [];
		$message        = '';
		$vouchers       = ps_setting( 'vouchers_group' );
		$i              = 0;
		foreach ( $vouchers as $voucher ) {
			$true_choice     = $voucher_choice == $voucher['voucher_id'];
			$time_now        = strtotime( current_time( 'mysql' ) );
			$expiration_date = (int) $voucher['voucher_expiration_date']['timestamp'];
			if ( $true_choice ) {
				$result['voucher_id']       = $voucher['voucher_id'];
				$result['voucher_type']     = $voucher['voucher_type'];
				$result['voucher_price']    = $voucher['voucher_price'];
				$result['voucher_dieukien'] = $voucher['voucher_dieukien'];
				$i ++;
			}

			if ( $true_choice && $total_price < $voucher['voucher_dieukien'] ) {
				$message = 'Voucher không hợp lệ';
				$result = [];
			}

			if ( $true_choice && $expiration_date < $time_now ) {
				$message = 'Mã voucher đã hết hạn';
				$result = [];
			}

			$voucher_used   = (int) get_option( 'voucher_' . $voucher['voucher_id'] );
			$voucher_number = (int) $voucher['voucher_soluong'];
			if ( $true_choice && $voucher_used >= $voucher_number ) {
				$message = 'Đã hết số lượng voucher này';
				$result = [];
			}
		}
		if ( $i == 0 ) {
			$message = 'Mã voucher không khớp';
			$result = [];
		}
		if ( empty( $result ) ) {
			wp_send_json_error( $message );
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
