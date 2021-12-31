<?php
namespace ELUSHOP\Order;

class ERP {
	public static function push( $id ) {
		$order = self::get_order( $id );

		$data     = $order['data'];
		$data     = json_decode( $data, true );
		$products = [];
		$role     = is_user_logged_in() ? get_userdata( $order['user'] )->roles[0] : '';
		foreach ( $data as $product ) {
			$price = $product['price'];
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
			$package = $product['package'];
			if ( $package['price'] > 0 && $package['number'] > 0 ) {
				if ( $product['quantity'] >= $package['number'] ) {
					$price = $package['price'];
				}
			}
			$products[] = [
				'product_code' => $product['ma_sp'],
				'qty'          => (int) $product['quantity'],
				'unit_price'   => (int) $price / 1000,
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

		$voucher_name = isset( $voucher ) ? $voucher['voucher_id'] : '';
		$user_meta    = get_user_meta( $order['user'] );
		$prefix_user  = rwmb_meta( 'prefix_user_erp', ['object_type' => 'setting'], 'setting' );

		$body = json_encode( [
			'login_name'   => $prefix_user . $user_meta['user_sdt'][0],
			'note'         => $order['note'],
			'payment_term' => $info['payment_method'],
			'products'     => $products,
			'discount'     => $discount,
			'voucher_name' => $voucher_name,
			'giathuoc_id'  => (int) $id,
			'giathuoctot'  => 'True',
		], JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'https://erp.hapu.vn/rest_api/public/Sale Order/create', [
			'headers' => [
				'Content-Type'  => 'application/json',
			],
			'method'  => 'POST',
			'body'    => $body,
			'timeout' => 15,
		] );
		$response = json_decode( $request['body'], true );
		$status   = 'completed';
		$message  = '';

		if ( $response['code'] !== 1 ) {
			$status  = 'pending';
			$message = $response['message'] ?? $response['name'];
		}

		self::update_status( $id, $status, $message );

		return compact( 'status', 'message', 'response' );
	}

	private static function get_order( $id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $id );

		return $wpdb->get_row( $sql, 'ARRAY_A' );
	}

	private static function update_status( $id, $status, $message ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[
				'push_erp'     => $status,
				'push_message' => $message,
			],
			[ 'id' => $id ]
		);
	}
}
