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

	public function get_product_from_order_id( $id ) {
		global $wpdb;

		$where = $wpdb->prepare( '`id`=%s', $id );
		$sql    = "SELECT * FROM $wpdb->orders WHERE $where";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
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
}
