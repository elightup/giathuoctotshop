<?php
global $wpdb;
$id            = intval( $_GET['id'] );
$item          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $id ) );
$info          = json_decode( $item->info, true );
$info_shipping = json_decode( $item->info_shipping, true );
$voucher       = json_decode( $item->voucher, true );
?>

<?php if ( isset( $_GET['type'] ) && 'checkout' === $_GET['type'] ) : ?>
	<div class="alert alert-info">Đơn hàng của bạn đã được đặt thành công. Chúng tôi sẽ liên hệ lại để xác nhận đơn hàng của bạn. Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của Giá Thuốc Hapu.</div>
<?php endif; ?>
<div class="info-order text-center">
	<?php esc_html_e( 'Chi tiết đơn hàng', 'gtt-shop' ); ?>
</div>
<div class="detail-order">
	<div class="line-items col-lg-6">
		<h4><?php esc_html_e( 'Đơn hàng số', 'gtt-shop' ); ?> #<?= $id; ?></h4>
		<table class="order table">
			<tr>
				<th>Thời gian:</th>
				<td><?= $item->date; ?></td>
			</tr>
			<tr>
				<th>Trạng thái:</th>
				<td>
					<?php
					$statuses = [
						'pending' => [ 'badge', __( 'Đang xử lý', 'gtt-shop' ) ],
						'completed'  => [ 'badge badge--success', __( 'Hoàn thành', 'gtt-shop' ) ],
						'trash'   => [ 'badge badge--danger', __( 'Đã xoá', 'gtt-shop' ) ],
					];
					$status   = $statuses[ $item->status ];
					printf( '<span class="%s">%s</span>', $status[0], $status[1] );
					?>
				</td>
			</tr>
			<tr>
				<th>Phương thức thanh toán</th>
				<td><?= $info['payment_method']; ?></td>
			</tr>
			<tr>
				<th>Tổng tiền:</th>
				<td><?= number_format( $item->amount, 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
			</tr>
			<?php if ( $voucher ) :
				$giam_gia = 0;
				if( $voucher['voucher_type'] == 'by_price' ) {
					$giam_gia = $voucher['voucher_price'];
				} else {
					$giam_gia = $voucher['voucher_price'] * $item->amount / 100;
				}
				$amount = $item->amount - $giam_gia;
			?>
				<tr>
					<th>Voucher:</th>
					<td><?= number_format( $giam_gia, 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?> ( Mã: <?= $voucher['voucher_id']; ?> )</td>
				</tr>
				<tr>
					<th>Thành tiền:</th>
					<td><?= number_format( $amount, 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
				</tr>
			<?php endif; ?>

		</table>
	</div>
	<div class="customer-details float-left col-lg-6 ">
		<h4>Thông tin khách hàng</h4>
		<table class="customer table">
			<tr>
				<th>Họ tên</th>
				<td><?= $info['name']; ?></td>
			</tr>
			<!-- <tr>
				<th><?php esc_html_e( 'Email', 'gtt-shop' ); ?>:</th>
				<td><?= $info['email']; ?></td>
			</tr> -->
			<tr>
				<th>Số điện thoại:</th>
				<td><?= $info['phone']; ?></td>
			</tr>
			<tr>
				<th>Địa chỉ:</th>
				<td><?= $info['address']; ?></td>
			</tr>
		</table>
	</div>
	<div class="customer-details float-left col-lg-6 ">
		<h4>Thông tin nhận hàng</h4>
		<table class="customer table">
			<tr>
				<th>Họ tên người nhận</th>
				<td><?= $info_shipping['name_shipping']; ?></td>
			</tr>
			<tr>
				<th>Số điện thoại người nhận:</th>
				<td><?= $info_shipping['phone_shipping']; ?></td>
			</tr>
			<tr>
				<th>Địa chỉ người nhận:</th>
				<td><?= $info_shipping['address_shipping']; ?></td>
			</tr>
			<tr>
				<th>Ghi chú:</th>
				<td><?= $item->note; ?></td>
			</tr>
		</table>
	</div>
</div>
<div class="order-list col-lg-12 clear">
<h4>Chi tiết sản phẩm</h4>
	<table class="order-products table">
		<thead>
		<tr>
			<th>Tên sản phẩm</th>
			<th>Số lượng</th>
			<th>Giá</th>
			<th>Tổng tiền</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$products = json_decode( $item->data, true );
		foreach ( $products as $product ) :
			?>
			<tr>
				<td><?= $product['title']; ?></td>
				<td><?= $product['quantity']; ?></td>
				<td><?= number_format( $product['price'], 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
				<td><?= number_format( $product['quantity'] * $product['price'], 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>