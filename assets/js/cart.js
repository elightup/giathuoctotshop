( function ( $, window, document, localStorage, CartParams ) {
	let cart = {
		data: {},
		load: function () {
			const data = localStorage.getItem( 'cart' );
			if ( data ) {
				cart.data = JSON.parse( data );
			}
		},
		update: function () {
			localStorage.setItem( 'cart', JSON.stringify( cart.data ) );
		},
		clear: function() {
			cart.data = {};
			cart.update();
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
		const data = localStorage.getItem( 'cart' );
		if ( data ) {
			cart.data = JSON.parse( data );
		}

		var add_cart_group  = $(this).parent();
			cart_id         = [];
			quantity        = $( '.quantity_products', add_cart_group ).val();
			button_plus     = $(this).attr('class');
		const productInfo = $( this ).data( 'info' );

		$.each( cart['data'], function( key, value ) {
			cart_id.push( value['id'] );
			if ( value['id'] != productInfo['id'] ) {
				return;
			}
			
			$old_quantity = value['quantity'];
			if ( button_plus == 'button-plus' ) {
				$new_quantity = parseInt( $old_quantity ) + 1;
			} else {
				$new_quantity = parseInt( $old_quantity ) == 0 ? 0 : parseInt( $old_quantity ) - 1;
			}
			
		});

		// add or update product cart.
		if ( cart_id.includes( productInfo['id'] ) ) {
			cart.updateProduct( productInfo['id'], $new_quantity );
		} else {
			cart.addProduct( productInfo, quantity );
		}

		// add count to minicart when click add to cart button.
		miniCart();
	}

	cart.load();

	// mini cart count when load.
	miniCart();


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
		if ( $( 'body' ).hasClass( 'page-template-page-quick-order' ) ) {
			$( '.product-cart__detail .color-secondary' ).html( $mini_cart_count );
			$( '.product-cart__detail .color-primary span' ).html( eFormatNumber(0, 3, '.', ',', parseFloat( $price_total )) );
		}
	}

	function incrementValue( e ) {
		e.preventDefault();
		var fieldName = $( e.target ).data( 'field' );
		var parent = $( e.target ).closest( 'div' );
		var currentVal = parseInt( parent.find( 'input[name=' + fieldName + ']' ).val(), 10 );

		if ( ! isNaN( currentVal ) ) {
			parent.find( 'input[name=' + fieldName + ']' ).val( currentVal + 1 );
		} else {
			parent.find( 'input[name=' + fieldName + ']' ).val( 0 );
		}
	}

	function decrementValue( e ) {
		e.preventDefault();
		var fieldName = $( e.target ).data( 'field' );
		var parent = $( e.target ).closest( 'div' );
		var currentVal = parseInt( parent.find( 'input[name=' + fieldName + ']' ).val(), 10 );

		if ( ! isNaN( currentVal ) && currentVal > 0 ) {
			parent.find( 'input[name=' + fieldName + ']' ).val( currentVal - 1 );
		} else {
			parent.find( 'input[name=' + fieldName + ']' ).val( 0 );
		}
	}

	// addQuantityToInput
	function addQuantityToInput() {
		var button_plus = $('.quantity .button-plus');
		$.each( button_plus, function( key, value ) {
			var info = $(this).attr('data-info'),
				product_id = JSON.parse( info )['id'],
				fieldName = $( this ).data( 'field' ),
				parent = $( this ).closest( 'div' ),
				currentVal = parseInt( parent.find( 'input[name=' + fieldName + ']' ).val(), 10 );
			$.each( cart['data'], function( key, value ) {
				if ( value['id'] == product_id ) {
					parent.find( 'input[name=' + fieldName + ']' ).val( value['quantity'] );
				}
			});
		});
	}

	// Click button plus and minus
	$( '.quantity' ).on( 'click', '.button-plus', function(e) {
		incrementValue(e);
	} );
	$( document ).on( 'click', '.button-plus', clickHandle );

	$( '.quantity' ).on('click', '.button-minus', function(e) {
		decrementValue(e);
	} );
	$( document ).on( 'click', '.button-minus', clickHandle );

	addQuantityToInput();

	// Export cart object.
	window.cart = cart;
} )( jQuery, window, document, localStorage, CartParams );

function eFormatNumber(n, x, s, c, number) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
		num = number.toFixed(Math.max(0, ~~n));
	return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
}
