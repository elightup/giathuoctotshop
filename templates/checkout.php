<div id="cart" class="cart--checkout"><?= __( 'Updating the cart', 'gtt-shop' );?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
<script type="text/html" id="tmpl-cart">
	<#
	let total = 0;
	let id = 0;
	let giam_gia = 0;
	if ( data.products.length == 0 ) {
		#>
		<div class="alert"><?= __( 'There are no products in your cart.', 'gtt-shop' );?> <a href="<?= home_url( '/' ); ?>"><?php esc_html_e( 'Go to home', 'gtt-shop' );?></a></div>
		<#
	} else {
		#>
		<div class="template-checkout">
			<div class="row">
				<div class="col-lg-6 float-left checkout-info">
					<div class="checkout-title-cart"><?= __( 'Your Details', 'gtt-shop' );?></div>
					<div class="form-info info-details">
						<div class="form-info__fields form-info__fields__name">
							<p><?php esc_html_e( 'Name', 'gtt-shop' );?></p>
							<input class="form-info__name" type="text" name="checkout_info[name]" value="" required>
						</div>
						<!-- <div class="form-info__fields">
							<p><?php esc_html_e( 'Email', 'gtt-shop' );?></p>
							<input class="form-info__email" type="email" name="checkout_info[email]" value="">
						</div> -->
						<div class="form-info__fields form-info__fields__phone">
							<p><?php esc_html_e( 'Phone', 'gtt-shop' );?></p>
							<input class="form-info__phone" type="text" name="checkout_info[phone]" value="" required>
						</div>
						<div class="form-info__fields">
							<p><?= __( 'Address', 'gtt-shop' );?></p>
							<textarea class="form-info__address" type="text" name="checkout_info[address]"></textarea>
						</div>
					</div>
				</div>
				<!-- <div class="col-lg-4 template-checkout__payments float-left">
					<div class="col-lg-12 custom">
						<div class="checkout-title-cart"><?= __( 'Payments', 'gtt-shop' );?></div>
						<div class="form-info check-deliverytype form-info--pay">
							<?php $payment_methods = ps_setting( 'payment_methods' ); ?>
							<?php foreach ( $payment_methods as $payment_method ) : ?>
								<div class="form-info__fields">
									<label class="form-info__input">
										<input type="radio" name="pay_form_info" value="<?php echo $payment_method['payment_method_title']; ?>">
										<?= wp_kses_post( $payment_method['payment_method_title'] ); ?>
									</label>
									<div class="radio-info pay-in-cash hidden">
										<?= wp_kses_post( $payment_method['payment_method_description'] ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="col-lg-12 ship">
						<div class="checkout-title-cart"><?= __( 'Shipping', 'gtt-shop' );?></div>
						<div class="form-info check-deliverytype form-info--ship">
							<?php $shipping_methods = ps_setting( 'shipping_methods' ); ?>
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
				</div> -->
				<div class="col-lg-6 template-checkout__cart float-left">
					<table class="cart table">
						<thead class="thead-dark">
						    <tr>
						      <th scope="col"><?= __( '#', 'gtt-shop' );?></th>
						      <th scope="col"><?= __( 'Product', 'gtt-shop' );?></th>
						      <th scope="col"><?= __( 'Quantity', 'gtt-shop' );?></th>
						      <th scope="col"><?= __( 'Total', 'gtt-shop' );?></th>
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
					<div class="total"><?= __( 'Total:', 'gtt-shop' );?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( cart_subtotal )) }}</span> <?= $symbol; ?></div>
					<p class="order-note">
						<label for="order-note"><?= __( 'Order note', 'gtt-shop' );?></label>
						<textarea id="order-note"></textarea>
					</p>
					<button class="place-checkout btn-success pay-coins"><?= __( 'Place order', 'gtt-shop' );?></button>
				</div>
			</div>
		</div>
		<#
	}
	#>
</script>
