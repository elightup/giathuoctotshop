<?php
global $wpdb;
$order_id      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$item          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $order_id ) );
$info          = json_decode( $item->info, true );
$info_shipping = json_decode( $item->info_shipping, true );
$voucher       = json_decode( $item->voucher, true );
?>

<?php if ( isset( $_GET['type'] ) && 'checkout' === $_GET['type'] ) : ?>
	<div class="alert alert-info" style="font-weight: 700; color: var(--color-dark); font-size: 24px; margin-bottom: 50px; ">Đơn hàng của bạn đã được đặt thành công. Chúng tôi sẽ liên hệ lại để xác nhận đơn hàng của bạn. Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của Giá Thuốc Hapu.</div>
<?php endif; ?>
<div class="info-order text-center">
	<?php esc_html_e( 'Chi tiết đơn hàng', 'gtt-shop' ); ?>
</div>
<div class="detail-order row col-lg-12">
	<div class="line-items col-lg-6">
		<h4><?php esc_html_e( 'Đơn hàng số', 'gtt-shop' ); ?> #<?= esc_html( $order_id ); ?></h4>
		<table class="order table">
			<tr>
				<th>Thời gian:</th>
				<td><?= esc_html( $item->date ); ?></td>
			</tr>
			<tr>
				<th>Trạng thái:</th>
				<td>
					<?php
					$statuses     = [
						'pending'   => [ 'badge', __( 'Đang xử lý', 'gtt-shop' ) ],
						'completed' => [ 'badge badge--success', __( 'Hoàn thành', 'gtt-shop' ) ],
						'trash'     => [ 'badge badge--danger', __( 'Đã xoá', 'gtt-shop' ) ],
					];
					$order_status = $statuses[ $item->status ];
					printf( '<span class="%s">%s</span>', esc_attr( $order_status[0] ), esc_html( $order_status[1] ) );
					?>
				</td>
			</tr>
			<tr>
				<th>Phương thức thanh toán</th>
				<td><?= esc_html( $info['payment_method'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Ghi chú', 'gtt-shop' ) ?></th>
				<td><?= esc_html( $item->note ); ?></td>
			</tr>
			<tr>
				<th>Tổng tiền:</th>
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
	<div class="customer-details float-left col-lg-6 ">
		<h4>Thông tin khách hàng</h4>
		<table class="customer table">
			<tr>
				<th>Họ tên</th>
				<td><?= esc_html( $info['name'] ); ?></td>
			</tr>
			<tr>
				<th>Số điện thoại:</th>
				<td><?= esc_html( $info['phone'] ); ?></td>
			</tr>
			<tr>
				<th>Địa chỉ:</th>
				<td><?= esc_html( $info['address'] ); ?></td>
			</tr>
		</table>
	</div>
</div>

<?php if ( $item->data_update ) : ?>
	<div class="order-list col-lg-12 clear">
		<h4>Danh sách sản phẩm chưa giao</h4>
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
			$products_update     = json_decode( $item->data_update, true );
			$products            = json_decode( $item->data, true );
			$total_not_delivered = 0;

			// Clone products[] to products_not_delivered[].
			$products_not_delivered = [];
			foreach ( $products as $key => $product ) {
				$ma_sp = rwmb_meta( 'ma_sp', '', $key );
				if ( empty( $products_update[ $ma_sp ] ) ) {
					$products_not_delivered[ $key ] = $product;
				}
				if ( $product['quantity'] > $products_update[ $ma_sp ]['quantity'] ) {
					$quantity_not_delivered                     = $product['quantity'] - $products_update[ $ma_sp ]['quantity'];
					$products_not_delivered[ $key ]             = $product;
					$products_not_delivered[ $key ]['quantity'] = $quantity_not_delivered;
				}
			}


			foreach ( $products_not_delivered as $key => $product ) :
				$product_id = $wpdb->get_row( $wpdb->prepare( "SELECT `post_id` FROM {$wpdb->prefix}postmeta WHERE `meta_key` = 'ma_sp' AND `meta_value`=%d", $key ) )->post_id;
				$price      = $product['price'];
				$user_role  = is_user_logged_in() ? get_userdata( $item->user )->roles[0] : '';
				$package    = $product['package'];
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
								$total_not_delivered += $product['quantity'] * $package['price'];
								echo esc_html( number_format_i18n( $product['quantity'] * $package['price'], 0 ) ) . esc_html( ps_setting( 'currency' ) );
							} else {
								$total_not_delivered += $product['quantity'] * $price;
								echo esc_html( number_format_i18n( $product['quantity'] * $price, 0 ) ) . esc_html( ps_setting( 'currency' ) );
							}
						} else {
							$total_not_delivered += $product['quantity'] * $price;
							echo esc_html( number_format_i18n( $product['quantity'] * $price, 0 ) ) . esc_html( ps_setting( 'currency' ) );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div class="total-not-delivered" style="text-align: right;">
			<b>Tổng tiền: </b>
			<?php echo esc_html( number_format_i18n( $total_not_delivered ) ) . esc_html( ps_setting( 'currency' ) ); ?>
		</div>
	</div>
<?php endif; ?>
<div class="order-list col-lg-12 clear">
	<h4>Danh sách sản phẩm đã đặt</h4>
	<table class="order-products table">
		<thead>
		<tr>
			<th>Tên sản phẩm</th>
			<th>Số lượng</th>
			<th>Số lượng thực nhận</th>
			<th>Giá</th>
			<th>Tổng tiền</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$total_delivered = 0;
		$products        = json_decode( $item->data, true );
		foreach ( $products as $key => $product ) :
			$price              = $product['price'];
			$ma_sp              = rwmb_meta( 'ma_sp', '', $key );
			$quantity_delivered = empty( $products_update[ $ma_sp ] ) ? 0 : $products_update[ $ma_sp ]['quantity'];
			$user_role          = is_user_logged_in() ? get_userdata( $item->user )->roles[0] : '';
			$package            = $product['package'];
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
				<td><?= esc_html( $quantity_delivered ); ?></td>
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
							$total_delivered += $product['quantity'] * $package['price'];
							echo esc_html( number_format_i18n( $product['quantity'] * $package['price'], 0 ) ) . esc_html( ps_setting( 'currency' ) );
						} else {
							$total_delivered += $product['quantity'] * $price;
							echo esc_html( number_format_i18n( $product['quantity'] * $price, 0 ) ) . esc_html( ps_setting( 'currency' ) );
						}
					} else {
						$total_delivered += $product['quantity'] * $price;
						echo esc_html( number_format_i18n( $product['quantity'] * $price, 0 ) ) . esc_html( ps_setting( 'currency' ) );
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<div class="total-delivered" style="text-align: right;">
		<p>
			<b>Tạm tính: </b>
			<?php echo esc_html( number_format_i18n( $total_delivered ) ) . esc_html( ps_setting( 'currency' ) ); ?>
		</p>
		<p>
			<b>SP Chưa giao: </b>
			<?php echo esc_html( number_format_i18n( $total_not_delivered ) ) . esc_html( ps_setting( 'currency' ) ); ?>
		</p>
		<p style="font-size: 16px; font-weight: 700">
			<b>Tổng cộng: </b>
			<?php echo esc_html( number_format_i18n( $total_delivered - $total_not_delivered ) ) . esc_html( ps_setting( 'currency' ) ); ?>
		</p>
	</div>
</div>
<?php if ( ! isset( $_GET['type'] ) ) : ?>
	<button class="place-checkout-again btn-success">Đặt hàng lại</button>
<?php endif; ?>
