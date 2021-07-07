jQuery( function( $ ) {
	const $d = $( document );

	// Mark as completed.
	$d.on( 'click', '.gtt-close', function( e ) {
		e.preventDefault();

		const $this = $( this ),
			id = $this.data( 'id' );
		$.post( ajaxurl, {
			action: 'gtt_order_close',
			id,
			_ajax_nonce: OrderList.nonce.close,
		}, response => {
			if ( ! response.success ) {
				alert( response.data );
				return;
			}
			$this.closest( 'tr' ).find( '.column-status' ).html( response.data.status );
			$this.replaceWith( response.data.button );
		} );
	} );

	// Mark as pending.
	$d.on( 'click', '.gtt-open', function( e ) {
		e.preventDefault();

		const $this = $( this ),
			id = $this.data( 'id' );
		$.post( ajaxurl, {
			action: 'gtt_order_open',
			id,
			_ajax_nonce: OrderList.nonce.open,
		}, response => {
			if ( ! response.success ) {
				alert( response.data );
				return;
			}
			$this.closest( 'tr' ).find( '.column-status' ).html( response.data.status );
			$this.replaceWith( response.data.button );
		} );
	} );
} );