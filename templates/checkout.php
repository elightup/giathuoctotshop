<div id="cart" class="cart--checkout"><?= __( 'Updating the cart', 'gtt-shop' );?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
<?php $user = wp_get_current_user() ?>
<script type="text/html" id="tmpl-cart">
	<#
	let total = 0;
	let id = 0;
	let giam_gia = 0;
	if ( data.products.length == 0 ) {
		#>
		<div class="alert">Không có sản phẩm trong giỏ hàng <a href="<?= home_url( '/' ); ?>">Trở về trang chủ</a></div>
		<#
	} else {
		#>
		<div class="template-checkout">
			<div class="row">
				<div class="col-lg-6">
					<h2 class="checkout-title">Thông tin thanh toán</h2>
					<div class="field">
						<label for="name">Họ tên</label>
						<input id="name" type="text" name="checkout_info[name]" value="<?= esc_attr( $user->display_name ) ?>" required>
					</div>
					<div class="field">
						<label for="phone">Số điện thoại</label>
						<input id="phone" type="text" name="checkout_info[phone]" value="<?= esc_attr( get_user_meta( $user->ID, 'phone', true ) ) ?>" required>
					</div>
					<div class="field">
						<label for="address">Địa chỉ</label>
						<textarea id="address" type="text" name="checkout_info[address]"><?= esc_textarea( get_user_meta( $user->ID, 'address', true ) ) ?></textarea>
					</div>
				</div>

				<div class="col-lg-6">
					<h2 class="checkout-title">Địa chỉ giao hàng</h2>
					<div class="field">
						<label for="ship-name">Họ tên người nhận hàng</label>
						<input disabled id="ship-name" type="text" name="checkout_info[other_name]" value="" required>
					</div>
					<div class="field">
						<label for="ship-phone">Số điện thoại người nhận hàng</label>
						<input disabled id="ship-phone" type="text" name="checkout_info[other_phone]" value="" required>
					</div>
					<div class="field">
						<label for="ship-address">Địa chỉ nhận hàng</label>
						<textarea disabled id="ship-address" type="text" name="checkout_info[other_address]"></textarea>
					</div>
					<div class="field">
						<label for="order-note">Ghi chú đơn hàng</label>
						<textarea id="order-note"></textarea>
					</div>
				</div>
			</div>

			<h2 class="checkout-title">Thông tin đơn hàng</h2>
			<table class="table">
				<thead>
					<tr>
						<th>#</th>
						<th>Tên sản phẩm</th>
						<th>Số lượng</th>
						<th>Giá</th>
					</tr>
				</thead>
				<#
				data.products.forEach( product => {
					let subtotal = product.price * product.quantity;
					total += subtotal;
					id += 1;
					#>
					<tr>
						<td>{{ id }}</td>
						<td><a href="{{ product.link }}">{{ product.title }}</a></td>
						<td align="center"><input class="quantity" type="text" value="{{ product.quantity }}"></td>
						<td>{{ eFormatNumber(0, 3, '.', ',', parseFloat( subtotal )) }} <?= $symbol; ?></td>
					</tr>
					<#
				} );

				#>
			</table>
			<#
			if( data.voucher ) {
				if( data.voucher.voucher_type == 'by_price' ) {
					giam_gia = data.voucher.voucher_price;
				} else {
					giam_gia = data.voucher.voucher_price * total / 100;
				}
				#>
				<div class="total">
					<p><?php esc_html_e( 'Tạm tính:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( total )) }} <?= $symbol; ?></span></p>
					<p><?php esc_html_e( 'Giảm giá:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( giam_gia )) }} <?= $symbol; ?></span></p>
				</div>
				<#
			}
			cart_subtotal = total - giam_gia;
			#>
			<div class="total"><?= __( 'Tổng:', 'gtt-shop' );?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( cart_subtotal )) }}</span> <?= $symbol; ?></div>

			<?php $payment_methods = ps_setting( 'payment_methods' ); ?>
			<?php if ( $payment_methods ): ?>
				<h2 class="checkout-title">Phương thức thanh toán</h2>
				<?php foreach ( $payment_methods as $payment_method ) : ?>
					<?php $payment_id = $payment_method['payment_method_title'] === 'Thanh toán tiền mặt' ? 'cash' : 'bank' ; ?>
					<div class="payment-method">
						<label>
							<input type="radio" name="payment_method" value="<?= esc_attr( $payment_id ) ?>">
							<?= wp_kses_post( $payment_method['payment_method_title'] ); ?>
						</label>
					</div>
				<?php endforeach; ?>
			<?php endif ?>

			<button class="place-checkout">Đặt hàng</button>
		</div>
		<#
	}
	#>
</script>
