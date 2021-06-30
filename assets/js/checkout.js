( function ( $, cart, wp, CartParams ) {
	const $d = $( document );

	let checkout = {
		data: {},
		key: `checkout-${ CartParams.userId }`,
		init: function() {
			checkout.load();
			checkout.addEventListeners();
		},
		load: function () {
			// Get from local storage first: for current user and guests.
			const data = localStorage.getItem( checkout.key );
			if ( data ) {
				checkout.data = JSON.parse( data );
			}

			// For logged in users, get from server.
			if ( ! CartParams.userId ) {
				return;
			}
			$.get( CartParams.ajaxUrl, {
				action: 'get_checkout',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId
			}, response => {
				if ( response.success ) {
					checkout.data = Array.isArray( response.data ) ? {} : response.data;

					setTimeout( function() {
						$( '#order-note' ).val( checkout.data.note );
					}, 100 );
				}
			} );
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
			// Update to local storage first.
			localStorage.setItem( checkout.key, JSON.stringify( checkout.data ) );

			// Update to server.
			if ( ! CartParams.userId ) {
				return;
			}
			$.post( CartParams.ajaxUrl, {
				action: 'set_checkout',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId,
				data: checkout.data
			} );
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
				voucher: JSON.parse( localStorage.getItem( 'voucher' ) )
			} ) );
		}

		updateCartHtml();
		$d.on( 'cart-loaded', updateCartHtml );

		// Remove an item from cart.
		$d.on( 'click', '.cart__remove', function( e ) {
			e.preventDefault();
			const productId = $( this ).data( 'product_id' );
			cart.removeProduct( productId );
			updateCartHtml();
		} );

		// Place checkout.
		$d.on( 'click', '.place-checkout', function( e ) {
			e.preventDefault();

			let $payment = $( 'input[name="payment_method"]:checked' );
			if ( $payment.length < 1 ) {
				alert( 'Bạn hãy chọn phương thức thanh toán' );
				return false;
			}

			let payment_method = $payment.val(),
				voucher = localStorage.getItem( 'voucher' );

			$( this ).prop( 'disabled', true ).text( 'Đang đặt hàng...' );

			$.post( CartParams.ajaxUrl, {
				action: 'place_checkout',
				voucher,
				payment_method,
				note: checkout.data.note
			}, function ( response ) {
				if ( ! response.success ) {
					alert( response.data );
					return;
				}
				localStorage.removeItem( 'voucher' );
				cart.clear();
				checkout.clear();

				// Redirect user to confirmation page.
				location.href = response.data;
			}, 'json' );
		} );

		// Check voucher.
		$d.on( 'click', '.voucher button', function( e ) {
			e.preventDefault();
			const voucher = $( '.voucher input' ).val();
			let total = 0;
			$.each( cart.data, function( key, value ) {
				const subtotal = value.price * value.quantity;
				total += subtotal;
			} );
			$.post( CartParams.ajaxUrl, {
				action: 'check_voucher',
				voucher: voucher,
				total_price: total,
			}, function ( response ) {
				if ( response.success ) {
					localStorage.setItem( 'voucher', JSON.stringify( response.data ) );
					updateCartHtml();
					$( '.voucher__message' ).html( 'Đã áp dụng mã voucher thành công' );
				} else {
					$( '.voucher__message' ).html( 'Mã voucher không khớp' );
				}
			}, 'json' );
		} );
		$d.on( 'click', '.remove-voucher', function( e ) {
			e.preventDefault();
			voucher = localStorage.getItem( 'voucher' );
			$.post( CartParams.ajaxUrl, {
				action: 'check_remove_voucher',
				voucher: voucher,
			}, function ( response ) {
				if ( response.success ) {
					localStorage.removeItem( 'voucher' );
					updateCartHtml();
					$( '.voucher__message' ).html( 'Mã ưu đãi đã được gỡ bỏ' );
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
} )( jQuery, cart, wp, CartParams );
