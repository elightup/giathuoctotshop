( function( $, cart ) {
	const $d = $( document );
	// Check out again.
	$d.on( 'click', '.place-checkout-again', function( e ) {
		e.preventDefault();

		$( this ).prop( 'disabled', true ).text( 'Đang đặt hàng lại...' );

		$.post( OrderAgain.ajaxUrl, {
			action: 'place_checkout_again',
			old_order_id: OrderAgain.oldOrderId,
		}, function ( response ) {
			if ( ! response.success ) {
				alert( response.data );
				return;
			}

			// Update lại cart.
			cart.data = Array.isArray( response.data.cart ) ? {} : response.data.cart;
			cart.updateMiniCart();
			cart.updateQuantityInputs();

			$d.trigger( 'cart-loaded' );

			// Redirect user to cart page.
			location.href = response.data.url;
		}, 'json' );
	} );
} )( jQuery, cart );