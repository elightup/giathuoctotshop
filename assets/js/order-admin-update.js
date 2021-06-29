jQuery( function ( $ ) {
	'use strict';
	const $d = $( document );
	let getUrlParameter = function getUrlParameter( sParam ) {
		var sPageURL = window.location.search.substring(1),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for ( i = 0; i < sURLVariables.length; i++ ) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
			}
		}
		return false;
	};
	function updateOrder() {
		$d.on( 'click', '.order-update', function( e ) {
			let order_id = getUrlParameter( 'id' ),
				name = $( '.info_name' ).val(),
				phone = $( '.info_phone' ).val(),
				address = $( '.info_address' ).val(),
				payment_method = 'cash',
				info            = {
					name,
					phone,
					address,
					payment_method,
				};
			$.post( OrderUpdate.ajaxUrl, {
				action: 'update_order_admin',
				order_id: order_id,
				info: info,
			}, function ( response ) {
				if ( ! response.success ) {
					return;
				}

				// Redirect user to current page.
				// location.href = response.data;
				console.log(response.data);
			} );
		} );
	}
	updateOrder();
} );
