<?php

namespace ELUSHOP;

class Checkout {
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'the_content', [ $this, 'filter_content' ] );
		add_action( 'wp_ajax_place_order', [ $this, 'place_order' ] );
		add_action( 'wp_ajax_nopriv_place_order', [ $this, 'place_order' ] );
		add_action( 'wp_ajax_place_checkout', [ $this, 'place_checkout' ] );
		add_action( 'wp_ajax_nopriv_place_checkout', [ $this, 'place_checkout' ] );

		add_action( 'wp_ajax_check_voucher', [ $this, 'check_voucher' ] );
		add_action( 'wp_ajax_nopriv_check_voucher', [ $this, 'check_voucher' ] );
		add_action( 'wp_ajax_check_remove_voucher', [ $this, 'check_remove_voucher' ] );
		add_action( 'wp_ajax_nopriv_check_remove_voucher', [ $this, 'check_remove_voucher' ] );
	}

	public function enqueue() {
		if ( ( ! is_cart_page() ) && ( ! is_checkout_page() ) ) {
			return;
		}
		if ( is_checkout_page() ) {
			wp_enqueue_style( 'checkout', ELU_SHOP_URL . 'assets/css/checkout.css' );
		}
		wp_enqueue_script( 'checkout', ELU_SHOP_URL . 'assets/js/checkout.js', [ 'cart', 'wp-util' ], '', true );
		wp_localize_script(
			'checkout',
			'CheckoutParams',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	public function filter_content( $content ) {
		if ( ! is_cart_page() && ! is_checkout_page() ) {
			return $content;
		}
		ob_start();
		if ( is_cart_page() ) {
			TemplateLoader::instance()->get_template_part( 'cart' );
		}
		if ( is_checkout_page() ) {
			TemplateLoader::instance()->get_template_part( 'checkout' );
		}
		return ob_get_clean();
	}

	public function place_order() {
		$url = add_query_arg(
			[
				'userid' => get_current_user_id(),
			],
			get_permalink( ps_setting( 'checkout_page' ) )
		);
		wp_send_json_success( $url );
	}

	public function place_checkout() {
		$data = isset( $_POST['cart'] ) ? $_POST['cart'] : [];
		$info = isset( $_POST['info'] ) ? $_POST['info'] : '';
		$info_shipping = isset( $_POST['info_shipping'] ) ? $_POST['info_shipping'] : '';
		$voucher = isset( $_POST['voucher'] ) ? $_POST['voucher'] : '';
		$voucher = json_decode( wp_unslash( $voucher ), true );
		$giam_gia = 0;
		$note = filter_input( INPUT_POST, 'note', FILTER_SANITIZE_STRING );

		if ( empty( $data ) ) {
			wp_send_json_error();
		}
		$amount = 0;
		foreach ( $data as $product ) {
			$amount += $product['price'] * $product['quantity'];
		}

		if ( ! empty( $voucher ) ) {
			if( $voucher['voucher_type'] == 'by_price' ) {
				$giam_gia = $voucher['voucher_price'];
			} else {
				$giam_gia = $voucher['voucher_price'] * $amount / 100;
			}
			$amount = $amount - $giam_gia;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->orders,
			[
				'date'          => current_time( 'mysql' ),
				'status'        => 'pending',
				'user'          => get_current_user_id(),
				'amount'        => $amount,
				'note'          => $note,
				'info'          => json_encode( $info ),
				'info_shipping' => json_encode( $info_shipping ),
				'data'          => json_encode( $data, JSON_UNESCAPED_UNICODE ),
			]
		);
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
	public function check_voucher() {
		$voucher_choice = isset( $_POST['voucher'] ) ? $_POST['voucher'] : '';

		$result = [];
		$vouchers = ps_setting( 'vouchers_group' );
		foreach ( $vouchers as $voucher ) {
			if ( $voucher_choice == $voucher['voucher_id'] ) {
				$result['voucher_id'] = $voucher['voucher_id'];
				$result['voucher_type'] = $voucher['voucher_type'];
				$result['voucher_price'] = $voucher['voucher_price'];
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
}
