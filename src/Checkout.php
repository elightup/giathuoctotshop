<?php
namespace ELUSHOP;

class Checkout {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'the_content', [ $this, 'filter_content' ] );
		add_action( 'wp_ajax_place_checkout', [ $this, 'place_checkout' ] );

		add_action( 'wp_ajax_check_voucher', [ $this, 'check_voucher' ] );
		add_action( 'wp_ajax_check_remove_voucher', [ $this, 'check_remove_voucher' ] );

		add_filter( 'body_class', [ $this, 'add_class_body' ] );
	}

	public function enqueue() {
		if ( ! is_cart_page() ) {
			return;
		}
		wp_enqueue_script( 'checkout', ELU_SHOP_URL . 'assets/js/checkout.js', [ 'cart', 'wp-util' ], '', true );
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
		foreach ( $data as $product ) {
			$amount += $product['price'] * $product['quantity'];
		}

		$info = [
			'name'           => get_user_meta( $id, 'user_name', true ),
			'phone'          => $user->user_login,
			'address'        => get_user_meta( $id, 'user_address', true ),
			'payment_method' => $payment_method,
		];

		ray( $info );
		ray( $data );

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

		// Clear cart.
		delete_user_meta( $id, 'cart' );

		$this->push_to_erp( $wpdb->insert_id );

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

	public function push_to_erp( $id ) {
		$products = $this->get_product_from_order_id( $id );
		$products = reset( $products );

		$amount  = $products['amount'];
		$voucher = $products['voucher'];
		$voucher = json_decode( $voucher, true );
		if ( ! $voucher ) {
			$giam_gia = 0;
		}
		if ( $voucher['voucher_type'] == 'by_price' ) {
			$giam_gia = $voucher['voucher_price'] / 1000;
		} else {
			$giam_gia = ( $voucher['voucher_price'] * $amount / 100 ) / 1000;
		}

		$data_product = $products['data'];
		$data_product = json_decode( $data_product, true );

		$data_customer = $products['info'];
		$data_customer = json_decode( $data_customer, true );

		$products_api = [];
		foreach ( $data_product as $product ) {
			$products_api[] = [
				'product_code' => $product['ma_sp'],
				'qty'          => (int)$product['quantity'],
				'unit_price'   => (int)$product['price'] / 1000,
			];
		}

		$data_string = json_encode( array(
			'note'         => $products['note'],
			'payment_term' => $data_customer['payment_method'],
			'products'     => $products_api,
			'discount'     => $giam_gia,
			'giathuoc_id'  => (int)$id,
			'giathuoctot'  => 'True',
		), JSON_UNESCAPED_UNICODE );

		$token = json_decode( $this->get_token_api() );
		$data = wp_remote_get( 'https://erp.hapu.vn/api/v1/private/pre_order/create', array(
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token->data->access_token,
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );
		$response    = json_decode( $data['body'], true );
		$erp_message = $response['message'] ? $response['message'] : $response['name'];
		$erp_message = $response['code'] == 1 ? '' : $erp_message;
		$erp_status  = $response['code'] == 1 ? 'completed' : 'pending';
		global $wpdb;
		$this->update_push_erp_status( $wpdb->insert_id, $erp_status, $erp_message );
	}

	public function get_product_from_order_id( $id ) {
		global $wpdb;

		$where = $wpdb->prepare( '`id`=%s', $id );
		$sql    = "SELECT * FROM $wpdb->orders WHERE $where";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_token_api() {
		$user_id     = get_current_user_id();
		$user_meta   = get_user_meta( $user_id );
		$prefix_user = rwmb_meta( 'prefix_user_erp', ['object_type' => 'setting'], 'setting' );

		$data_string = json_encode( array(
			'login'    => $prefix_user . $user_meta['user_sdt'][0],
			'password' => '111111',
		), JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'https://erp.hapu.vn/api/v1/public/Authentication/login', array(
			'headers' => [
				'Content-Type'  => 'application/json',
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );

		return wp_remote_retrieve_body( $request );
	}

	public function update_push_erp_status( $id, $status, $message ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[
				'push_erp'   => $status,
				'push_message' => $message
			],
			[ 'id' => $id ],
			[ '%s' ]
		);
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
}
