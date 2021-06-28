<div id="cart"><?= esc_attr__( 'Updating the cart', 'gtt-shop' );?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
<script type="text/html" id="tmpl-cart">
	<#
	let total = 0;
	let total_quantity = 0;
	let id = 0;
	let giam_gia = 0;
	let cart_subtotal = 0;
	if ( data.products.length == 0 ) {
		#>
		<div class="alert">Không có sản phẩm trong giỏ hàng <a href="<?= home_url( '/' ); ?>">Trở về trang chủ</a></div>
		<#
	} else {
		#>
		<div class="d-flex">
			<div class="product-list col-md-7">
				<#
				data.products.forEach( product => {
					var subtotal = product.price * product.quantity;
					total += subtotal;
					total_quantity += parseFloat( product.quantity );
					id += 1;
					#>
					<div class="product-item">
						<a class="post-thumbnail" href="{{ product.link }}" aria-hidden="true" tabindex="-1">
							<img src="{{product.url}}" loading="lazy">
						</a>

						<div class="product-title">
							<h3 class="entry-title"><a href="{{ product.link }}">{{ product.title }}</a></h3>
						</div>
						<div class="product-price">
							<p class="price">{{ eFormatNumber(0, 3, '.', ',', parseFloat( product.price )) }} <?= $symbol; ?></p>
						</div>
						<div class="cart-button">
							<div class="quantity cart__quantity">
								<span class="button-minus" data-info="{{ JSON.stringify( product ) }}">-</span>
								<input type="text" class="quantity_products" min="0" name="quantity" size="4" pattern="[0-9]*" value="{{ product.quantity }}">
								<span class="button-plus" data-info="{{ JSON.stringify( product ) }}">+</span>
							</div>
						</div>

						<div class="cart__remove-product">
							<span class="cart__remove" data-product_id="{{ product.id }}" title="Xóa sản phẩm này">&times;</span>
						</div>
					</div>
					<#
				} );
				#>
				<div class="product-back">Để thêm sản phẩm vào giỏ hàng, vui lòng quay về trang <a href="/dat-hang-nhanh/">Đặt hàng nhanh</a>.</div>
			</div>
			<div class="product-cart col-md-5">
				<div class="product-cart__wrapper">
					<div class="product-cart__detail d-flex">
						<div class="col-md-5">
							<p>Số lượng</p>
							<p class="color-secondary">{{ total_quantity }}</p>
						</div>
						<div class="col-md-7">
							<p>Thành tiền</p>
							<div class="total-pay-product text-right">
								<#
								if( data.voucher ) {
									if( data.voucher.voucher_type == 'by_price' ) {
										giam_gia = data.voucher.voucher_price;
									} else {
										giam_gia = data.voucher.voucher_price * total / 100;
									}
									#>
									<p><?php esc_html_e( 'Tạm tính:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( total )) }} <?= $symbol; ?></span></p>
									<p><?php esc_html_e( 'Giảm giá:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( giam_gia )) }} <?= $symbol; ?></span><a href="" class="remove-voucher" data-coupon="xtybwpq5">[Xóa]</a></p>
									<#
								}
								cart_subtotal = total - giam_gia;
								#>
								<p class="color-primary"><?php esc_html_e( 'Tổng:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( cart_subtotal )) }} <?= $symbol; ?></span></p>
							</div>
						</div>
					</div>
					<div class="vouchers">
						<input type="text" name="voucher_code" class="voucher_input" value placeholder="Mã ưu đãi">
						<button type="submit" class="btn voucher_button" name="apply_voucher" value="Áp dụng"><?php esc_html_e( 'Áp dụng', 'gtt-shop' ); ?></button>
						<div class="vouchers_message"></div>
					</div>
					<div class="product-cart__button">
						<button class="btn-secondary wp-block-button__link place-order">
							Tiếp tục thanh toán
						</button>
					</div>
				</div>
			</div>
		</div>
		<#
	}
	#>
</script>
