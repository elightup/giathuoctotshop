( function ( $, window, document, localStorage, CartParams ) {
	const $d = $( document );

	let cart = {
		data: {},
		key: 'cart',
		setKey: function () {
			const userId = CartParams.user_id;
			cart.key = userId ? `cart-${ userId }` : 'cart';
		},
		load: function () {
			const data = localStorage.getItem( cart.key );
			if ( data ) {
				cart.data = JSON.parse( data );
			}
		},
		update: function () {
			localStorage.setItem( cart.key, JSON.stringify( cart.data ) );
		},

		clear: function() {
			cart.data = {};
			cart.update();
		},
		hasProduct: function( id ) {
			return cart.data.hasOwnProperty( id );
		},
		getProduct: function( id ) {
			return cart.hasProduct( id ) ? cart.data[ id ] : null;
		},
		addProduct: function ( productInfo, quantity ) {
			cart.data[productInfo.id] = productInfo;
			if ( quantity >= 1 ) {
				cart.data[productInfo.id].quantity = quantity;
			} else {
				// cart.data[productInfo.id].quantity = 1;
				cart.removeProduct( productInfo.id );
			}
			cart.update();
		},
		updateProduct: function( productId, quantity ) {
			cart.data[productId].quantity = quantity;
			cart.update();
		},
		removeProduct: function ( productId ) {
			delete cart.data[productId];
			cart.update();
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

		// add count to minicart when click add to cart button.
		miniCart();
	}

	function miniCart() {
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
			console.log( $price_total);
		if ( $( 'body' ).hasClass( 'page-template-page-quick-order' ) || $( 'body' ).hasClass( 'page-id-12' ) ) {
			$( '.product-cart__detail .color-secondary' ).html( $mini_cart_count );
			$( '.product-cart__detail .color-primary span' ).html( eFormatNumber(0, 3, '.', ',', parseFloat( $price_total )) );
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

	cart.setKey();
	cart.load();

	// mini cart count when load.
	miniCart();

	// Click button plus and minus
	$d.on( 'click', '.button-plus', clickHandle );
	$d.on( 'click', '.button-minus', clickHandle );

	addQuantityToInput();

	// Export cart object.
	window.cart = cart;
} )( jQuery, window, document, localStorage, CartParams );

function eFormatNumber(n, x, s, c, number) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
		num = number.toFixed(Math.max(0, ~~n));
	return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
}
