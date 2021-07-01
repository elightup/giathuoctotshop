jQuery( function( $ ) {
	const $d = $( document );
	// Check out again.
	$d.on( 'click', '.place-checkout-again', function( e ) {
		e.preventDefault();

		$( this ).prop( 'disabled', true ).text( 'Đang đặt hàng...' );

		$.post( OrderAgain.ajaxUrl, {
			action: 'place_checkout_again',
			old_order_id: OrderAgain.oldOrderId,
		}, function ( response ) {
			console.log( response );
			if ( ! response.success ) {
				alert( response.data );
				return;
			}
			// Redirect user to confirmation page.
			location.href = response.data;
		}, 'json' );
	} );
} );