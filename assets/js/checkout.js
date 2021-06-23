( function ( $, cart, wp, CheckoutParams ) {
	const $d = $( document );

	let checkout = {
		data: {},
		key: 'checkout',
		init: function() {
			checkout.setKey();
			checkout.load();
			checkout.addEventListeners();
		},
		setKey: function () {
			const userId = CartParams.user_id;
			checkout.key = userId ? `checkout-${ userId }` : 'checkout';
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

		// Phương thức thanh toán radio
		$( '.radio-info', '.form-info.form-info--pay .form-info__fields:nth-child(1)' ).removeClass('hidden');

		$( 'input[type=radio]', '.form-info.form-info--pay' ).on( 'click', function( e ) {
			var radio_class = $( this ).parent().parent(),
				radio_info = $('.radio-info', radio_class );
				$( '.radio-info', '.form-info.form-info--pay' ).addClass('hidden');

				if ( $(this).attr('checked', true)) {
					radio_info.removeClass('hidden');
				}

		} ).change();

		// Remove an item from cart.
		$cart.on( 'click', '.cart__remove', function( e ) {
			e.preventDefault();
			const productId = $( this ).data( 'product_id' );
			cart.removeProduct( productId );
			updateCartHtml();
		} );

		// redict page checkout
		$( '.page' ).on( 'click', '.place-order',  function( e ) {
			e.preventDefault();
			$.post( CheckoutParams.ajaxUrl, {
				action: 'place_order',
			}, function ( response ) {
				location.href = response.data;
			}, 'json' )
		} );

		// Place checkout.
		$( '.place-checkout' ).on( 'click', function( e ) {
			let payment = $( 'input[name="pay_form_info"]:checked' ).val();
			if ( ! payment ) {
				alert( 'Bạn hãy chọn phương thức thanh toán' );
				return false;
			}

			$( this ).prop( 'disabled', true ).text( 'Đang đặt hàng...' );

			e.preventDefault();

			var name            = $ ('.info-details .form-info__name').val(),
				phone 	        = $ ('.info-details .form-info__phone').val(),
				address         = $ ('.info-details .form-info__address').val(),
				payment_method  = $( '.form-info__input input:checked', '.form-info--pay').val(),

				name_shipping    = $ ('.form-info__other_name').val(),
				phone_shipping   = $ ('.form-info__other_phone').val(),
				address_shipping = $ ('.form-info__other_address').val(),

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

		$( document ).on( 'click', '.voucher_button', function( e ) {
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
		$( document ).on( 'click', '.remove-voucher', function( e ) {
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
