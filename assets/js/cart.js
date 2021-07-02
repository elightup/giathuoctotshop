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
		update() {
			cart.updateMiniCart();

			// Update to server.
			if ( ! CartParams.userId ) {
				return;
			}
			$.post( CartParams.ajaxUrl, {
				action: 'set_cart',
				_ajax_nonce: CartParams.nonce,
				id: CartParams.userId,
				data: cart.data
			}, response => {
				if ( ! response.success ) {
					alert( 'Có lỗi xảy ra, vui lòng thử lại' );
					return;
				}
				cart.data = Array.isArray( response.data ) ? {} : response.data;
				cart.updateMiniCart();
			} );
		},
		clear() {
			cart.data = {};
			cart.update();
		},
		hasProduct( id ) {
			return id && cart.data.hasOwnProperty( id );
		},
		getProduct( id ) {
			return cart.hasProduct( id ) ? cart.data[ id ] : null;
		},
		addProduct( productId, quantity ) {
			if ( ! productId ) {
				return;
			}
			if ( quantity >= 1 ) {
				cart.data[productId] = { quantity };
			} else {
				cart.removeProduct( productInfo.id );
			}
			cart.update();
		},
		updateProduct( productId, quantity ) {
			if ( ! productId ) {
				return;
			}
			cart.data[productId].quantity = quantity;
			cart.update();
		},
		removeProduct( productId ) {
			delete cart.data[productId];
			cart.update();
		},
		updateMiniCart() {
			let count = 0;
			let total = 0;
			Object.values( cart.data ).forEach( product => {
				count += parseInt( product['quantity'] );
				total += product['price'] ? parseInt( product['price'] ) * parseInt( product['quantity'] ) : 0;
				if ( product['quantity'] == 0 ) {
					cart.removeProduct( product['id'] );
				}
			} );
			total = eFormatNumber( 0, 3, '.', ',', parseFloat( total ) );
			$( '.mini-cart-count span' ).html( count );

			// Update cart on sidebar for quick order & cart page.
			$( '.product-cart__detail .color-secondary' ).html( count );
			$( '.product-cart__detail .color-primary span' ).html( total );
			$( '.total-pay-product .total__number' ).html( total );
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
