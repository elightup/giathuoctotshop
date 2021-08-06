( function ( $, window, document, localStorage, CartParams ) {
	const $d = $( document );

	let cart = {
		data: {},
		key: `cart-${ CartParams.userId }`,
		init() {
			cart.updateMiniCart();
			cart.updateQuantityInputs();
			cart.addEventListeners();

			// For logged in users, get from server.
			if ( ! CartParams.userId ) {
				return;
			}
			$.get( CartParams.ajaxUrl, {
				action: 'get_cart',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId
			}, response => {
				if ( ! response.success ) {
					alert( 'Có lỗi xảy ra, vui lòng thử lại' );
					return;
				}
				cart.data = Array.isArray( response.data ) ? {} : response.data;
				cart.updateMiniCart();
				cart.updateQuantityInputs();

				$d.trigger( 'cart-loaded' );
			} );
		},
		addEventListeners() {
			// Click button plus and minus
			$d.on( 'click', '.button-plus', cart.onIncreaseDecrease );
			$d.on( 'click', '.button-minus', cart.onIncreaseDecrease );

			// Update quantity when input change.
			$d.on( 'change', '.quantity_products', cart.onChangeQuantity );
		},
		
		clear() {
			cart.data = {};
			$.post( CartParams.ajaxUrl, {
				action: 'cart_clear',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId,
			}, cart.updateCartFromAjax );
		},
		hasProduct( id ) {
			return id && cart.data.hasOwnProperty( id );
		},
		getProduct( id ) {
			return cart.hasProduct( id ) ? cart.data[ id ] : null;
		},
		addProduct( productId, quantity ) {
			$.post( CartParams.ajaxUrl, {
				action: 'cart_add_product',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId,
				product_id: productId,
				quantity: quantity
			}, cart.updateCartFromAjax );
		},
		updateProduct( productId, quantity ) {
			$.post( CartParams.ajaxUrl, {
				action: 'cart_update_product',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId,
				product_id: productId,
				quantity: quantity
			}, cart.updateCartFromAjax );
		},
		removeProduct( productId, callback ) {
			$.post( CartParams.ajaxUrl, {
				action: 'cart_remove_product',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId,
				product_id: productId,
			}, function ( response ) {
				cart.updateCartFromAjax( response );
				if ( callback ) {
					callback();
				}
			} );
		},
		updateCartFromAjax( response ) {
			if ( ! response.success ) {
				alert( 'Có lỗi xảy ra, vui lòng thử lại' );
				return;
			}
			cart.data = Array.isArray( response.data ) ? {} : response.data;
			cart.updateMiniCart();
		},
		updateMiniCart() {
			let count = Object.values( cart.data ).length;
			let total = 0;
			Object.values( cart.data ).forEach( product => {
				let price = product['price'];
				switch( CartParams.role ) {
					case 'vip2':
						price = product['price_vip2'];
						break;
					case 'vip3':
						price = product['price_vip3'];
						break;
					case 'vip4':
						price = product['price_vip4'];
						break;
					case 'vip5':
						price = product['price_vip5'];
						break;
					case 'vip6':
						price = product['price_vip6'];
						break;
				}

				total += price ? parseInt( price ) * parseInt( product['quantity'] ) : 0;
				if ( product['quantity'] == 0 ) {
					cart.removeProduct( product['id'] );
				}
			} );

			$( '.mini-cart-count span' ).html( count );

			// Update cart on sidebar for quick order & cart page.
			$( '.product-cart__detail .color-secondary' ).html( count );
			let voucher = JSON.parse( localStorage.getItem( 'voucher' ) ),
				giam_gia = 0;

			if ( $( 'body' ).hasClass( 'cart-page' ) && voucher ) {
				let $voucher_type = voucher['voucher_type'] == 'by_price';
				giam_gia = $voucher_type ? parseInt( voucher['voucher_price'] ) : parseInt( voucher['voucher_price'] * total / 100 );
				let cartSubtotal = total - giam_gia;
				total = eFormatNumber( 0, 3, '.', ',', parseFloat( total ) );
				giam_gia = eFormatNumber( 0, 3, '.', ',', parseFloat( giam_gia ) );
				cartSubtotal = eFormatNumber( 0, 3, '.', ',', parseFloat( cartSubtotal ) );

				$( '.total-pay-product .has-voucher span' ).html( total );
				$( '.total-pay-product .giam_gia span' ).html( giam_gia );
				$( '.total-pay-product .no-voucher span' ).html( cartSubtotal );
			} else {
				total = eFormatNumber( 0, 3, '.', ',', parseFloat( total ) );
				$( '.product-cart__detail .color-primary span' ).html( total );
				$( '.total-pay-product .total__number span' ).html( total );
			}
		},
		updateQuantityInputs() {
			$( '.quantity_products' ).each( function() {
				const $this = $( this ),
					id = $this.parent().data( 'product' );

				if ( cart.hasProduct( id ) ) {
					product = cart.getProduct( id );
					$this.val( product.quantity );
				}
			} );
		},
		onIncreaseDecrease( e ) {
			e.preventDefault();

			const $this        = $( this ),
				$parent        = $this.parent(),
				$quantityInput = $parent.find( '.quantity_products' ),
				amount         = $this.hasClass( 'button-minus' ) ? -1 : 1;

			let quantity = parseInt( $quantityInput.val(), 10 );
			quantity = quantity + amount;
			if ( quantity < 0 ) {
				quantity = 0;
			}

			$quantityInput.val( quantity );

			const productId = $parent.data( 'product' );
			if ( cart.hasProduct( productId ) ) {
				cart.updateProduct( productId, quantity );
			} else {
				cart.addProduct( productId, quantity );
			}
		},
		onChangeQuantity( e ) {
			e.preventDefault();

			const $this = $( this ),
				quantity = $this.val(),
				$parent = $this.parent(),
				productId = $parent.data( 'product' );

			if ( cart.hasProduct( productId ) ) {
				cart.updateProduct( productId, quantity );
			} else {
				cart.addProduct( productId, quantity );
			}
		}
	};

	cart.init();

	// Export cart object.
	window.cart = cart;
} )( jQuery, window, document, localStorage, CartParams );

function eFormatNumber(n, x, s, c, number) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
		num = number.toFixed(Math.max(0, ~~n));
	return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
}
