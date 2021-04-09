<div id="cart"><?= esc_attr__( 'Updating the cart', 'gtt-shop' );?></div>

<?php $symbol = ps_setting( 'currency' ); ?>
<script type="text/html" id="tmpl-cart">
	<#
	let total = 0;
	let total_vnd = 0;
	let id = 0;
	if ( data.products.length == 0 ) {
		#>
		<div class="alert"><?= __( 'There are no products in your cart.', 'gtt-shop' );?> <a href="<?= home_url( '/' ); ?>"><?php esc_html_e( 'Go to home', 'gtt-shop' );?></a></div>
		<#
	} else {
		#>
		<table class="cart cart-checkout table table-bordered">
			<thead class="thead-dark">
			    <tr>
			      <th scope="col"><?= __( '#', 'gtt-shop' );?></th>
			      <th scope="col"><?= __( 'Product', 'gtt-shop' );?></th>
			      <th scope="col"><?= __( 'Quantity', 'gtt-shop' );?></th>
			      <th scope="col"><?= __( 'Price', 'gtt-shop' );?></th>
			      <th scope="col"><?= __( 'Total', 'gtt-shop' );?></th>
			      <th scope="col"><?= __( 'Delete', 'gtt-shop' );?></th>
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
		<div class="total-pay-product text-right"><?php esc_html_e( 'Total:', 'gtt-shop' ) ?> <span class="total__number">{{ eFormatNumber(0, 3, '.', ',', parseFloat( total )) }} <?= $symbol; ?></span>
				</div>
		<div class="submit-cart-shop text-right">
			<button class="place-order btn btn-success"><?= __( 'Checkout', 'gtt-shop' );?></button>
		</div>
		<#
	}
	#>
</script>
