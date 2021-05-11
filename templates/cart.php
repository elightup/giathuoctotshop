<div id="cart"><?= esc_attr__( 'Updating the cart', 'gtt-shop' );?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
<script type="text/html" id="tmpl-cart">
	<#
	let total = 0;
	let id = 0;
	let giam_gia = 0;
	let cart_subtotal = 0;
	if ( data.products.length == 0 ) {
		#>
		<div class="alert">Không có sản phẩm trong giỏ hàng <a href="<?= home_url( '/' ); ?>">Trở về trang chủ</a></div>
		<#
	} else {
		#>
		<table class="cart cart-checkout table table-bordered">
			<thead class="thead-dark">
			    <tr>
			      <th scope="col">#</th>
			      <th scope="col">Tên sản phẩm</th>
			      <th scope="col">Số lượng</th>
			      <th scope="col">Giá</th>
			      <th scope="col">Tổng tiền</th>
			      <th scope="col">Thao tác</th>
			    </tr>
		  	</thead>
		  	<tbody>
			<#
			data.products.forEach( product => {
				var subtotal = product.price * product.quantity;
				total += subtotal;
				id += 1;
				#>
				<tr>
					<td class="cart__stt">{{ id }}</td>

					<td class="cart__title">
						<a href="{{ product.link }}"><img src="{{product.url}}" alt="{{product.title}}" />{{ product.title }}</a>
					</td>
					<td class="cart__quantity"><input type="number" value="{{ product.quantity }}" min="1" data-product_id="{{ product.id }}" style="width: 70px;float: initial;margin: auto;text-align: center;"></td>
					<td class="cart__price"><div class="price__coin">{{ eFormatNumber(0, 3, '.', ',', parseFloat( product.price )) }} <?= $symbol; ?></div></td>
					<td class="cart__subtotal"><span class="cart__subtotal__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( subtotal )) }}</span> <?= $symbol; ?></td>
					<td class="cart__remove-product"> <button class="cart__remove btn btn-link" data-product_id="{{ product.id }}" title="Xóa sản phẩm này">&times;</button> </td>
				</tr>
				<#
			} );

			#>
			</tbody>
		</table>
		<div class="col-12" style="display: flex; justify-content: space-between; padding: 0;">
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
				<p><?php esc_html_e( 'Tổng:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( cart_subtotal )) }} <?= $symbol; ?></span></p>
			</div>
			<div class="vouchers">
				<input type="text" name="voucher_code" class="voucher_input" value placeholder="Mã ưu đãi">
				<button type="submit" class="btn voucher_button" name="apply_voucher" value="Áp dụng"><?php esc_html_e( 'Áp dụng', 'gtt-shop' ); ?></button>
				<div class="vouchers_message"></div>
			</div>
		</div>
		<div class="submit-cart-shop text-right">
			<button class="place-order btn btn-success"><?= __( 'Tiến hành thanh toán', 'gtt-shop' );?></button>
		</div>
		<#
	}
	#>
</script>
