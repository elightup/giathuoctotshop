( function ( $, window, document, localStorage, CartParams ) {
	const $d = $( document );

	let cart = {
		data: {},
		key: 'cart',
		init() {
			cart.setKey();
			cart.load();
			cart.updateMiniCart();
		},
		setKey() {
			const userId = CartParams.user_id;
			cart.key = userId ? `cart-${ userId }` : 'cart';
		},
		load() {
			const data = localStorage.getItem( cart.key );
			if ( data ) {
				cart.data = JSON.parse( data );
			}
		},
		update() {
			localStorage.setItem( cart.key, JSON.stringify( cart.data ) );

			cart.updateMiniCart();
		},

		clear() {
			cart.data = {};
			cart.update();
		},
		hasProduct( id ) {
			return cart.data.hasOwnProperty( id );
		},
		getProduct( id ) {
			return cart.hasProduct( id ) ? cart.data[ id ] : null;
		},
		addProduct( productInfo, quantity ) {
			cart.data[productInfo.id] = productInfo;
			if ( quantity >= 1 ) {
				cart.data[productInfo.id].quantity = quantity;
			} else {
				// cart.data[productInfo.id].quantity = 1;
				cart.removeProduct( productInfo.id );
			}
			cart.update();
		},
		updateProduct( productId, quantity ) {
			cart.data[productId].quantity = quantity;
			cart.update();
		},
		removeProduct( productId ) {
			delete cart.data[productId];
			cart.update();
		},
		updateMiniCart() {
			$mini_cart_count = 0;
			$price_total = 0;
			$.each( cart['data'], function( key, value ) {
				$mini_cart_count += parseInt( value['quantity'] );
				$price_total += parseInt( value['price'] ) * parseInt( value['quantity'] );
				if ( value['quantity'] == 0 ) {
					cart.removeProduct( value['id'] );
				}
			});
			$( '.mini-cart-count span' ).html( $mini_cart_count );
			if ( $( 'body' ).hasClass( 'page-template-page-quick-order' ) || $( 'body' ).hasClass( 'cart-page' ) ) {
				$( '.product-cart__detail .color-secondary' ).html( $mini_cart_count );
				$( '.product-cart__detail .color-primary span' ).html( eFormatNumber(0, 3, '.', ',', parseFloat( $price_total )) );
			}
		}
	};

	function clickHandle( e ) {
		e.preventDefault();
		$('.add-to-cart', '.cart-button' ).append('<div class="load-icon"></div>');

		const $this        = $( this ),
			$quantityInput = $this.parent().find( '.quantity_products' ),
			amount         = $this.hasClass( 'button-minus' ) ? -1 : 1;

		let quantity = parseInt( $quantityInput.val(), 10 );
		quantity = quantity + amount;
		if ( quantity < 0 ) {
			quantity = 0;
		}

		$quantityInput.val( quantity );

		const productInfo = $( this ).data( 'info' );
		if ( cart.hasProduct( productInfo.id ) ) {
			cart.updateProduct( productInfo.id, quantity );
		} else {
			cart.addProduct( productInfo, quantity );
		}
	}

	function onChangeQuantity( e ) {
		e.preventDefault();

		const $this = $( this );
		const quantity = $this.val();
		const productInfo = $this.next().data( 'info' );

		console.log( quantity, productInfo );

		if ( cart.hasProduct( productInfo.id ) ) {
			cart.updateProduct( productInfo.id, quantity );
		} else {
			cart.addProduct( productInfo, quantity );
		}
	}

	// addQuantityToInput
	function addQuantityToInput() {
		$('.button-plus').each( function() {
			const $this = $( this ),
				info = $this.data('info'),
				id = info.id;

			if ( cart.hasProduct( id ) ) {
				product = cart.getProduct( id );
				$this.prev().val( product.quantity );
			}
		} );
	}

	cart.init();

	// Click button plus and minus
	$d.on( 'click', '.button-plus', clickHandle );
	$d.on( 'click', '.button-minus', clickHandle );

	// Update quantity when input change.
	$d.on( 'change', '.quantity_products', onChangeQuantity );

	addQuantityToInput();

	// Export cart object.
	window.cart = cart;
} )( jQuery, window, document, localStorage, CartParams );

function eFormatNumber(n, x, s, c, number) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
		num = number.toFixed(Math.max(0, ~~n));
	return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
}
