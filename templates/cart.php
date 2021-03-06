<div id="cart" class="cart"><?= esc_attr__( 'Updating the cart', 'gtt-shop' ); ?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
<script type="text/html" id="tmpl-cart">
	<#
	let total = 0,
		giam_gia = 0,
		cartSubtotal = 0;

	if ( data.products.length == 0 ) {
		#>
		<div class="alert">Không có sản phẩm trong giỏ hàng <a href="/">Trở về trang chủ</a></div>
		<#
	} else {
		#>
		<div class="d-flex">
			<div class="col-md-7">
				<#
				data.products.forEach( product => {
					let package = product.package,
						price   = product.price;
					switch( CartParams.role ) {
						case 'vip2':
							price = product.price_vip2;
							break;
						case 'vip3':
							price = product.price_vip3;
							break;
						case 'vip4':
							price = product.price_vip4;
							break;
						case 'vip5':
							price = product.price_vip5;
							break;
						case 'vip6':
							price = product.price_vip6;
							break;
					}
					if ( package.price > 0 && package.number > 0 ) {
						if ( product.quantity >= package.number ) {
							price = package.price;
						}
					}

					total += price * product.quantity;
					#>
					<div class="product-item">
						<a class="post-thumbnail" href="{{ product.link }}">
							<img src="{{product.url}}" loading="lazy">
						</a>

						<div class="product-title">
							<h3 class="entry-title"><a href="{{ product.link }}">{{ product.title }}</a></h3>
						</div>
						<div class="product-price">
							<p class="price">{{ eFormatNumber(0, 3, '.', ',', parseFloat( price )) }} <?= esc_html( $symbol ); ?></p>
						</div>
						<div class="product-last-column">
							<div class="quantity" data-product="{{ product.id }}" data-max-number="{{ product.max_number }}">
								<span class="button-minus">-</span>
								<input type="text" class="quantity_products" size="4" pattern="[0-9]*" value="{{ product.quantity }}">
								<#
								if ( product.quantity >= product.max_number && product.max_number ) {
									#>
									<span class="button-plus btn-disabled" disabled>+</span>
									<#
								} else {
									#>
									<span class="button-plus">+</span>
									<#
								}
								#>
							</div>
							<#
							if ( product.max_number ) {
								#>
								<p class="product-max-number">Đặt tối đa {{ product.max_number }} </p>
								<#
							}
							#>
						</div>
						<div class="cart__remove-product">
							<span class="cart__remove" data-product_id="{{ product.id }}" title="Xóa sản phẩm này">&times;</span>
						</div>
					</div>
					<#
				} );
				#>
			</div>
			<div class="col-md-5">
				<div class="product-cart__wrapper">
					<div class="product-cart__detail d-flex">
						<div class="col-md-5">
							<p>Số lượng</p>
							<p class="color-secondary">{{ data.products.length }}</p>
						</div>
						<div class="col-md-7">
							<p>Thành tiền</p>
							<div class="total-pay-product">
								<#
								if ( data.voucher ) {
									if( data.voucher.voucher_type == 'by_price' ) {
										giam_gia = data.voucher.voucher_price;
									} else {
										giam_gia = data.voucher.voucher_price * total / 100;
									}
									#>
									<p>Tạm tính: <span class="total__number has-voucher"><span>{{ eFormatNumber(0, 3, '.', ',', parseFloat( total )) }}</span> <?= esc_html( $symbol ); ?></span></p>
									<p>Giảm giá: <span class="total__number giam_gia"><span>{{ eFormatNumber(0, 3, '.', ',', parseFloat( giam_gia )) }}</span> <?= esc_html( $symbol ); ?></span><a href="" class="remove-voucher">[Xóa]</a></p>
									<#
								}
								cartSubtotal = total - giam_gia;
								#>
								<p>Tổng: <span class="total__number no-voucher"><span>{{ eFormatNumber(0, 3, '.', ',', parseFloat( cartSubtotal )) }}</span> <?= esc_html( $symbol ); ?></span></p>
							</div>
						</div>
					</div>
					<div class="field voucher">
						<input type="text" placeholder="Mã ưu đãi">
						<button>Áp dụng</button>
						<div class="voucher__message"></div>
					</div>

					<div class="field">
						<label for="order-note">Ghi chú đơn hàng</label>
						<textarea id="order-note"></textarea>
					</div>

					<div class="field payment-methods">
						<?php $payment_methods = ps_setting( 'payment_methods' ); ?>
						<?php if ( $payment_methods ) : ?>
							<label>Phương thức thanh toán</label>
							<?php foreach ( $payment_methods as $payment_method ) : ?>
								<?php $payment_id = $payment_method['payment_method_title'] === 'Thanh toán tiền mặt' ? 'cash' : 'bank'; ?>
								<div class="payment-methods__fields">
									<label class="payment-method">
										<input type="radio" name="payment_method" value="<?= esc_attr( $payment_id ) ?>">
										<?= wp_kses_post( $payment_method['payment_method_title'] ); ?>
									</label>
									<div class="payment-description hidden">
										<?php
										if ( isset( $payment_method['payment_method_description'] ) ) {
											echo $payment_method['payment_method_description'];
										}
										?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif ?>
					</div>

					<div class="field field-button">
						<a class="btn-secondary wp-block-button__link" href="<?php echo esc_url( home_url() ); ?>/dat-hang-nhanh/">Thêm sản phẩm</a>
						<button class="place-checkout">Đặt hàng</button>
					</div>
				</div>
			</div>
		</div>
		<#
	}
	#>
</script>
