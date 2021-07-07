jQuery( function( $ ) {
	const $d = $( document );

	const get_current_page_action = () => {
		const params = new URLSearchParams( window.location.search );
		const status = params.get( 'status' );
		const statuses = {
			'pending': 'close',
			'completed': 'open'
		};

		return statuses[ status ];
	};

	const toggleStatus = action => {
		$d.on( 'click', `.gtt-${ action }`, function( e ) {
			e.preventDefault();

			const $this = $( this ),
				id = $this.data( 'id' );
			$.post( ajaxurl, {
				action: `gtt_order_${ action }`,
				id,
				_ajax_nonce: OrderList.nonce[ action ],
			}, response => {
				if ( !response.success ) {
					alert( response.data );
					return;
				}
				if ( get_current_page_action() === action ) {
					$this.closest( 'tr' ).css( 'background', '#ff8383' ).hide( 'slow', function() {
						$( this ).remove();
					} );
					return;
				}

				$this.closest( 'tr' ).find( '.column-status' ).html( response.data.status );
				$this.replaceWith( response.data.button );
			} );
		} );
	};

	toggleStatus( 'close' );
	toggleStatus( 'open' );

	// Repush to ERP.
	$d.on( 'click', '.gtt-repush', function( e ) {
		e.preventDefault();

		const $this = $( this ),
			id = $this.data( 'id' );
		$.post( ajaxurl, {
			action: 'gtt_order_repush',
			id,
			_ajax_nonce: OrderList.nonce.repush,
		}, response => {
			if ( !response.success ) {
				alert( response.data );
				return;
			}
			$this.closest( 'tr' ).find( '.column-erp' ).html( response.data.status );
		} );
	} );
} );