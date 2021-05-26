<div id="cart" class="cart--checkout"><?= __( 'Updating the cart', 'gtt-shop' );?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
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
				<div class="col-lg-6 float-left checkout-info">
					<div class="checkout-title-cart">Thông tin thanh toán</div>
					<div class="form-info info-details">
						<div class="form-info__fields form-info__fields__name">
							<p>Họ tên</p>
							<input class="form-info__name" type="text" name="checkout_info[name]" value="" required>
						</div>
						<!-- <div class="form-info__fields">
							<p><?php esc_html_e( 'Email', 'gtt-shop' );?></p>
							<input class="form-info__email" type="email" name="checkout_info[email]" value="">
						</div> -->
						<div class="form-info__fields form-info__fields__phone">
							<p>Số điện thoại</p>
							<input class="form-info__phone" type="text" name="checkout_info[phone]" value="" required>
						</div>
						<div class="form-info__fields">
							<p>Địa chỉ</p>
							<textarea class="form-info__address" type="text" name="checkout_info[address]"></textarea>
						</div>
					</div>
				</div>

				<div class="col-lg-6 template-checkout__payments float-left">
					<div class="checkout-title-cart">Địa chỉ giao hàng</div>
					<div class="ship check-deliverytype form-info--ship">
						<div class="form-info__fields form-info__fields__name">
							<p>Họ tên người nhận hàng</p>
							<input class="form-info__other_name" type="text" name="checkout_info[other_name]" value="" required>
						</div>
						<div class="form-info__fields form-info__fields__phone">
							<p>Số điện thoại người nhận hàng</p>
							<input class="form-info__other_phone" type="text" name="checkout_info[other_phone]" value="" required>
						</div>
						<div class="form-info__fields">
							<p>Địa chỉ nhận hàng</p>
							<textarea class="form-info__other_address" type="text" name="checkout_info[other_address]"></textarea>
						</div>
					</div>
					<div class="order-note">
						<div class="form-info__fields">
							<p>Ghi chú đơn hàng</p>
							<textarea id="order-note"></textarea>
						</div>
					</div>
				</div>

				<div class="col-lg-12 template-checkout__cart float-left">
					<div class="checkout-title-cart">Thông tin đơn hàng</div>
					<table class="cart table">
						<thead class="thead-dark">
						    <tr>
						      <th scope="col"><?= __( '#', 'gtt-shop' );?></th>
						      <th scope="col">Tên sản phẩm</th>
						      <th scope="col">Số lượng</th>
						      <th scope="col">Giá</th>
						    </tr>
					  	</thead>
						<#
						data.products.forEach( product => {
							let subtotal = product.price * product.quantity;
							total += subtotal;
							id += 1;
							#>
							<tr>
								<td class="cart__stt">{{ id }}</td>

								<td class="cart__title">
									<div class="pull-left">
										<a href="{{ product.link }}">{{ product.title }}</a>
									</div>
								</td>
								<td class="cart__quantity"><input type="number" value="{{ product.quantity }}" min="1" data-product_id="{{ product.id }}" style="width: 70px;text-align: center;"></td>
								<td class="cart__subtotal"><span class="cart__subtotal__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( subtotal )) }}</span> <?= $symbol; ?></td>
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
				</div>

				<div class="col-lg-12 template-checkout__payments float-left">
					<?php $payment_methods = ps_setting( 'payment_methods' ); ?>
					<?php if ( $payment_methods ): ?>
						<div class="col-lg-12 custom">
							<div class="checkout-title-cart">Phương thức thanh toán</div>
							<div class="form-info check-deliverytype form-info--pay">
								<?php foreach ( $payment_methods as $payment_method ) : ?>
									<div class="form-info__fields">
										<label class="form-info__input">
											<input type="radio" name="pay_form_info" value="<?php echo $payment_method['payment_method_title']; ?>">
											<?= wp_kses_post( $payment_method['payment_method_title'] ); ?>
										</label>
										<?php if ( ! empty( $payment_method['payment_method_description'] ) ): ?>
											<div class="radio-info pay-in-cash hidden">
												<?= wp_kses_post( $payment_method['payment_method_description'] ); ?>
											</div>
										<?php endif ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif ?>

					<?php $shipping_methods = ps_setting( 'shipping_methods' ); ?>
					<?php if ( $shipping_methods ): ?>
						<div class="col-lg-12 ship">
							<div class="checkout-title-cart">Phương thức giao hàng</div>
							<div class="form-info check-deliverytype form-info--ship">
								<?php foreach ( $shipping_methods as $shipping_method ) : ?>
									<div class="form-info__fields">
										<label class="form-info__input">
											<input type="radio" name="checkout_info" data-check="ship-agree" value="<?= wp_kses_post( $shipping_method ); ?>">
											<?= wp_kses_post( $shipping_method ); ?>
										</label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif ?>

					<button class="place-checkout btn-success pay-coins">Đặt hàng</button>
				</div>
			</div>
		</div>
		<#
	}
	#>
</script>
