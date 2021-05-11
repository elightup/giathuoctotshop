<?php
global $wpdb;
$id   = intval( $_GET['id'] );
$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $id ) );
$info = json_decode( $item->info, true );
?>

<?php if ( isset( $_GET['type'] ) && 'checkout' === $_GET['type'] ) : ?>
	<div class="alert alert-info">Cảm ơn bạn đã đặt hàng của bạn. Đơn đặt hàng của bạn hiện đang được xử lý. Dưới đây là thông tin đặt hàng. Chúng tôi sẽ liên hệ với bạn ngay khi có thể!</div>
<?php endif; ?>
<div class="info-order text-center">
	<?php esc_html_e( 'Chi tiết đơn hàng', 'gtt-shop' ); ?>
</div>
<div class="detail-order">
	<div class="line-items float-left col-lg-6">
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