( function ( $, cart, wp, CheckoutParams ) {
	const $d = $( document );

	let checkout = {
		data: {},
		key: `checkout-${ CartParams.userId }`,
		init: function() {
			checkout.load();
			checkout.addEventListeners();
		},
		load: function () {
			const data = localStorage.getItem( checkout.key );
			if ( data ) {
				checkout.data = JSON.parse( data );
			}
		},
		addEventListeners: function() {
			$d.on( 'change', '#order-note', function() {
				checkout.saveNote( $( this ).val() );
			} );
			setTimeout( function() {
				$( '#order-note' ).val( checkout.data.note );
			}, 100 );
		},

		update: function () {
			localStorage.setItem( checkout.key, JSON.stringify( checkout.data ) );
		},
		clear: function () {
			checkout.data = {};
			checkout.update();
		},
		saveNote: function ( note ) {
			checkout.data.note = note;
			checkout.update();
		}
	};

	checkout.init();

	$( function () {
		const $cart = $( '#cart' ),
			// Underscore template for cart.
			cartTemplate = wp.template( 'cart' );

		function updateCartHtml() {
			$cart.html( cartTemplate( {
				products: Object.values( cart.data ),
				voucher: JSON.parse( localStorage.getItem( 'voucher' ) ),
				budget: parseInt( CheckoutParams.budget )
			} ) );
		}

		updateCartHtml();
		$d.on( 'cart-loaded', updateCartHtml );

		// Remove an item from cart.
		$cart.on( 'click', '.cart__remove', function( e ) {
			e.preventDefault();
			const productId = $( this ).data( 'product_id' );
			cart.removeProduct( productId );
			updateCartHtml();
		} );

		// Place checkout.
		$d.on( 'click', '.place-checkout', function( e ) {
			let payment = $( 'input[name="payment_method"]:checked' );
			if ( payment.length < 1 ) {
				alert( 'Bạn hãy chọn phương thức thanh toán' );
				return false;
			}

			$( this ).prop( 'disabled', true ).text( 'Đang đặt hàng...' );

			e.preventDefault();

			var name            = $ ( '.info-details .form-info__name' ).val(),
				phone 	        = $ ( '.info-details .form-info__phone' ).val(),
				address         = $ ( '.info-details .form-info__address' ).val(),
				payment_method  = $( 'input[name="payment_method"]:checked' ).val(),

				name_shipping    = $ ( '.form-info__other_name' ).val(),
				phone_shipping   = $ ( '.form-info__other_phone' ).val(),
				address_shipping = $ ( '.form-info__other_address' ).val(),

				info            = {
					name,
					phone,
					address,
					payment_method,
				},
				info_shipping   = {
					name_shipping,
					phone_shipping,
					address_shipping,
				},
				voucher = localStorage.getItem( 'voucher' );

			$.post( CheckoutParams.ajaxUrl, {
				action: 'place_checkout',
				cart: cart.data,
				voucher: voucher,
				note: $( '#order-note' ).val(),
				info: info,
				info_shipping: info_shipping,
			}, function ( response ) {
				if ( ! response.success ) {
					return;
				}
				localStorage.removeItem( 'voucher' );
				cart.clear();
				checkout.clear();

				// Redirect user to confirmation page.
				location.href = response.data;
			}, 'json' );
		} );

		// Check vouchers.

		$d.on( 'click', '.voucher_button', function( e ) {
			e.preventDefault();
			var voucher = $( '.voucher_input' ).val();
			let total = 0;
			$.each( cart.data, function( key, value ) {
				const subtotal = value.price * value.quantity;
				total += subtotal;
			} );
			$.post( CheckoutParams.ajaxUrl, {
				action: 'check_voucher',
				voucher: voucher,
				total_price: total,
			}, function ( response ) {
				if ( response.success ) {
					localStorage.setItem( 'voucher', JSON.stringify( response.data ) );
					updateCartHtml();
					$( '.vouchers_message' ).html( 'Đã áp dụng mã voucher thành công' );
				} else {
					$( '.vouchers_message' ).html( 'Mã voucher không khớp' );
				}
				$( '.vouchers_message' ).addClass( 'vouchers_repsonse' );
			}, 'json' );
		} );
		$d.on( 'click', '.remove-voucher', function( e ) {
			e.preventDefault();
			voucher = localStorage.getItem( 'voucher' );
			$.post( CheckoutParams.ajaxUrl, {
				action: 'check_remove_voucher',
				voucher: voucher,
			}, function ( response ) {
				if ( response.success ) {
					localStorage.removeItem( 'voucher' );
					updateCartHtml();
					$( '.vouchers_message' ).addClass( 'vouchers_repsonse' );
					$( '.vouchers_message' ).html( 'Mã ưu đãi đã được gỡ bỏ' );
				}
			}, 'json' );
		} );


		const $voucher = JSON.parse( localStorage.getItem( 'voucher' ) );

		let total = 0;
		$.each( cart.data, function( key, value ) {
			const subtotal = value.price * value.quantity;
			total += subtotal;
		} );

		if ( $voucher && $voucher.voucher_dieukien > total ) {
			localStorage.removeItem( 'voucher' );
			updateCartHtml();
		}
	} );
} )( jQuery, cart, wp, CheckoutParams );
