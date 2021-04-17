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
			if ( quantity > 1 ) {
				cart.data[productInfo.id].quantity = quantity;
			} else {
				cart.data[productInfo.id].quantity = 1;
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
			mini_cart_count = 0;
			price_total     = 0;
			quantity        = $( '.quantity_products', add_cart_group ).val();
		const productInfo = $( this ).data( 'info' );

		$.each( cart['data'], function( key, value ) {
			mini_cart_count += parseInt( value['quantity'] );
			price_total += parseInt( value['price'] ) * parseInt( value['quantity'] );
			cart_id.push( value['id'] );
			if ( value['id'] == productInfo['id'] ) {
				$old_quantity = value['quantity'];
				$new_quantity = parseInt( $old_quantity ) + parseInt( quantity );
			}
		});

		// add or update product cart.
		if ( cart_id.includes( productInfo['id'] ) ) {
			cart.updateProduct( productInfo['id'], $new_quantity );
		} else {
			cart.addProduct( productInfo, quantity );
		}

		// add count to minicart when click add to cart button.
		mini_cart_count += parseInt( quantity );
		price_total += parseInt( quantity ) * productInfo['price'];
		$( '.mini-cart-count span' ).html( mini_cart_count );
		if ( $( 'body' ).hasClass( 'page-template-page-quick-order' ) ) {
			$( '.product-cart__detail .color-secondary' ).html( mini_cart_count );
			$( '.product-cart__detail .color-primary span' ).html( eFormatNumber(0, 3, '.', ',', parseFloat( price_total )) );
		}

		// Notify when click add to cart button
		var add_success = $( this ).data('type');
        setTimeout(function(){
			$( '.load-icon', '.add-to-cart' ).remove();
			new $.notification('<i class="fa fa-shopping-cart"></i> ' + add_success , {"class" : 'alert-notification', timeout : 2000, click : null, close : false});
		}, 1000);
	}

	cart.load();

	// add mini cart count.
	$mini_cart_count = 0;
	$price_total = 0;
	$.each( cart['data'], function( key, value ) {
		$mini_cart_count += parseInt( value['quantity'] );
		$price_total += parseInt( value['price'] ) * parseInt( value['quantity'] );
	});
	$( '.mini-cart-count span' ).html( $mini_cart_count );
	if ( $( 'body' ).hasClass( 'page-template-page-quick-order' ) ) {
		$( '.product-cart__detail .color-secondary' ).html( $mini_cart_count );
		$( '.product-cart__detail .color-primary span' ).html( eFormatNumber(0, 3, '.', ',', parseFloat( $price_total )) );
	}

	// $( function() {
	// 	// $( '.cart-button .view-cart' ).css( 'display', 'none' );
	// 	// $( document ).on( 'click', '.add-to-cart.buy-now', clickviewcart );
	// 	$( document ).on( 'click', '.add-to-cart', clickHandle );
	// } );


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

	$( '.quantity' ).on( 'click', '.button-plus', function(e) {
		incrementValue(e);
	} );
	$( document ).on( 'click', '.button-plus', clickHandle );

	$( '.quantity' ).on('click', '.button-minus', function(e) {
		decrementValue(e);
	} );
	$( document ).on( 'click', '.button-minus', clickHandle );

	// Export cart object.
	window.cart = cart;
} )( jQuery, window, document, localStorage, CartParams );

function eFormatNumber(n, x, s, c, number) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
		num = number.toFixed(Math.max(0, ~~n));
	return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
}
