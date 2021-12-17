<?php
$order_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
if ( ! $order_id ) {
	return;
}

global $wpdb;
$item          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $order_id ) );
$info          = json_decode( $item->info, true );
$info_shipping = json_decode( $item->info_shipping, true );
$voucher       = json_decode( $item->voucher, true );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Chi tiết đơn hàng', 'gtt-shop' ) . ' #' . esc_html( $order_id ); ?></h1>
	<div class="info-order">
		<h3><?php esc_html_e( 'Thông tin đơn hàng', 'gtt-shop' ); ?></h3>
		<table class="widefat">
			<tr>
				<td><?php esc_html_e( 'Thời gian', 'gtt-shop' ); ?></td>
				<td><?= esc_html( $item->date ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Trạng thái', 'gtt-shop' ); ?></td>
				<td>
					<?php
					$statuses     = [
						'pending'   => [ 'badge', __( 'Đang xử lý', 'gtt-shop' ) ],
						'completed' => [ 'badge badge--success', __( 'Đã hoàn thành', 'gtt-shop' ) ],
						'trash'     => [ 'badge badge--danger', __( 'Đã Xoá', 'gtt-shop' ) ],
					];
					$order_status = $statuses[ $item->status ];
					$user         = get_userdata( $item->user );
					$payments     = $item->info;
					$payments     = json_decode( $payments, true );

					printf( '<span class="%s">%s</span>', esc_attr( $order_status[0] ), esc_html( $order_status[1] ) );

					if ( 'pending' === $item->status ) {
						printf( '<a href="%s" class="button">' . esc_html__( 'Đã hoàn thành', 'gtt-shop' ) . '</a>', add_query_arg( [
							'action'   => 'close',
							'id'       => $order_id,
							'_wpnonce' => wp_create_nonce( 'ps_close_order' ),
						], admin_url( 'edit.php?page=orders&post_type=product' ) ) );
					}
					if ( 'completed' === $item->status ) {
						printf( '<a href="%s" class="button">' . esc_html__( 'Đang xử lý', 'gtt-shop' ) . '</a>', add_query_arg( [
							'action'   => 'open',
							'id'       => $order_id,
							'_wpnonce' => wp_create_nonce( 'ps_open_order' ),
						], admin_url( 'edit.php?page=orders&post_type=product' ) ) );
					}
					?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Phương thức thanh toán', 'gtt-shop' ) ?></td>
				<td><?= esc_html( $info['payment_method'] ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Ghi chú', 'gtt-shop' ) ?></td>
				<td><?= esc_html( $item->note ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Tổng tiền', 'gtt-shop' ) ?></td>
				<td><?= esc_html( number_format_i18n( $item->amount, 0 ) ) . esc_html( ps_setting( 'currency' ) ); ?></td>
			</tr>
			<?php
			if ( $voucher ) :
				$giam_gia = 0;
				if ( $voucher['voucher_type'] == 'by_price' ) {
					$giam_gia = $voucher['voucher_price'];
				} else {
					$giam_gia = $voucher['voucher_price'] * $item->amount / 100;
				}
				$amount = $item->amount - $giam_gia;
				?>
				<tr>
					<th>Voucher:</th>
					<td><?= number_format( $giam_gia, 0, '', '.' ); ?> <?= esc_html( ps_setting( 'currency' ) ); ?> ( Mã: <?= esc_html( $voucher['voucher_id'] ); ?> )</td>
				</tr>
				<tr>
					<th>Thành tiền:</th>
					<td><?= number_format( $amount, 0, '', '.' ); ?> <?= esc_html( ps_setting( 'currency' ) ); ?></td>
				</tr>
			<?php endif; ?>
		</table>
	</div>
	<div class="info-user">
		<h3><?php esc_html_e( 'Thông tin khách hàng', 'gtt-shop' ) ?></h3>
		<table class="widefat">
			<thead>
			<tr>
				<td><?php esc_html_e( 'Họ tên', 'gtt-shop' ) ?></td>
				<td><?php esc_html_e( 'Số điện thoại', 'gtt-shop' ) ?></td>
				<td><?php esc_html_e( 'Địa chỉ', 'gtt-shop' ) ?></td>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td><?= esc_html( $info['name'] ); ?></td>
				<td><?= esc_html( $info['phone'] ); ?></td>
				<td><?= esc_html( $info['address'] ); ?></td>
			</tr>
			</tbody>
		</table>
	</div>
	<div class="info-product">
		<h3><?php esc_html_e( 'Sản phẩm', 'gtt-shop' ) ?></h3>
		<table class="widefat">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Tên Sản phẩm', 'gtt-shop' ) ?></th>
				<th><?php esc_html_e( 'Số lượng', 'gtt-shop' ) ?></th>
				<th><?php esc_html_e( 'Giá', 'gtt-shop' ) ?></th>
				<th><?php esc_html_e( 'Tổng tiền', 'gtt-shop' ) ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$products = json_decode( $item->data, true );
			foreach ( $products as $product ) :
				$price     = $product['price'];
				$user_role = is_user_logged_in() ? get_userdata( $item->user )->roles[0] : '';
				$package   = $product['package'];
				switch ( $user_role ) {
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
				?>
				<tr>
					<td><?= esc_html( $product['title'] ); ?></td>
					<td><?= esc_html( $product['quantity'] ); ?></td>
					<td>
						<?php
						echo esc_html( number_format_i18n( $price, 0 ) ) . esc_html( ps_setting( 'currency' ) ) . ' ';
						if ( $package['price'] > 0 && $package['number'] > 0 ) {
							if ( $product['quantity'] >= $package['number'] ) {
								echo '(Giá kiện: ' . esc_html( number_format_i18n( $package['price'] ) ) . esc_html( ps_setting( 'currency' ) ) . ')';
							}
						}
						?>
					</td>
					<td>
						<?php
						if ( $package['price'] > 0 && $package['number'] > 0 ) {
							if ( $product['quantity'] >= $package['number'] ) {
								echo esc_html( number_format_i18n( $product['quantity'] * $package['price'], 0 ) ) . esc_html( ps_setting( 'currency' ) );
							} else {
								echo esc_html( number_format_i18n( $product['quantity'] * $price, 0 ) ) . esc_html( ps_setting( 'currency' ) );
							}
						} else {
							echo esc_html( number_format_i18n( $product['quantity'] * $price, 0 ) ) . esc_html( ps_setting( 'currency' ) );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
