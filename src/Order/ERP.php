<?php
namespace ELUSHOP\Order;

class ERP {
	public static function push( $id ) {
		$order = self::get_order( $id );

		$data     = $order['data'];
		$data     = json_decode( $data, true );
		$products = [];
		foreach ( $data as $product ) {
			$products[] = [
				'product_code' => $product['ma_sp'],
				'qty'          => (int) $product['quantity'],
				'unit_price'   => (int) $product['price'] / 1000,
			];
		}

		$info = $order['info'];
		$info = json_decode( $info, true );

		$amount  = $order['amount'];
		$voucher = $order['voucher'];
		$voucher = json_decode( $voucher, true );
		if ( ! $voucher ) {
			$discount = 0;
		}
		if ( $voucher['voucher_type'] == 'by_price' ) {
			$discount = $voucher['voucher_price'] / 1000;
		} else {
			$discount = ( $voucher['voucher_price'] * $amount / 100 ) / 1000;
		}

		$body = json_encode( [
			'note'         => $order['note'],
			'payment_term' => $info['payment_method'],
			'products'     => $products,
			'discount'     => $discount,
			'giathuoc_id'  => (int) $id,
			'giathuoctot'  => 'True',
		], JSON_UNESCAPED_UNICODE );

		$token   = json_decode( self::get_user_token( $order['user'] ) );
		$request = wp_remote_get( 'https://erp.hapu.vn/api/v1/private/pre_order/create', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token->data->access_token,
			],
			'method'  => 'POST',
			'body'    => $body,
			'timeout' => 15,
		] );
		$response = json_decode( $request['body'], true );
		$message  = $response['message'] ?: $response['name'];
		$message  = $response['code'] == 1 ? '' : $message;
		$status   = $response['code'] == 1 ? 'completed' : 'pending';

		self::update_status( $id, $status, $message );

		return compact( 'status', 'message' );
	}

	private static function get_order( $id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $id );

		return $wpdb->get_row( $sql, 'ARRAY_A' );
	}

	private static function get_user_token( $user_id ) {
		$phone  = get_user_meta( $user_id, 'user_sdt', true );
		$prefix = rwmb_meta( 'prefix_user_erp', ['object_type' => 'setting'], 'setting' );

		$body = json_encode( [
			'login'    => $prefix . $phone,
			'password' => '111111',
		], JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'https://erp.hapu.vn/api/v1/public/Authentication/login', [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'method'  => 'POST',
			'body'    => $body,
			'timeout' => 15,
		] );

		return wp_remote_retrieve_body( $request );
	}

	private static function update_status( $id, $status, $message ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[
				'push_erp'     => $status,
				'push_message' => $message
			],
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
}