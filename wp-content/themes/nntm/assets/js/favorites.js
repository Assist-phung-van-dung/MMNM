 
( function () {
	'use strict';

	var config = window.nntmFavorites || null;
	if ( ! config ) {
		return;
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-nntm-favorite]' ) : null;
		if ( ! button ) {
			return;
		}

		if ( ! config.isLoggedIn ) {
			return;
		}

		event.preventDefault();
		if ( button.disabled || button.classList.contains( 'is-loading' ) ) {
			return;
		}

		var objectId = parseInt( button.getAttribute( 'data-nntm-favorite' ), 10 ) || 0;
		if ( objectId <= 0 ) {
			return;
		}

		var form = new FormData();
		form.append( 'action', 'nntm_section_toggle_favorite' );
		form.append( 'nonce', config.nonce );
		form.append( 'object_id', String( objectId ) );

		button.disabled = true;
		button.classList.add( 'is-loading' );

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: form,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data ) {
					throw new Error( payload && payload.data && payload.data.message ? payload.data.message : config.errorText );
				}

				setState( button, !! payload.data.favorited );

				var sameButtons = document.querySelectorAll( '[data-nntm-favorite="' + objectId + '"]' );
				for ( var i = 0; i < sameButtons.length; i++ ) {
					setState( sameButtons[ i ], !! payload.data.favorited );
				}

				if ( ! payload.data.favorited && document.body.classList.contains( 'page-yeu-thich' ) ) {
					window.setTimeout( function () { window.location.reload(); }, 120 );
				}
			} )
			.catch( function ( error ) {
				button.setAttribute( 'title', error && error.message ? error.message : config.errorText );
			} )
			.finally( function () {
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			} );
	} );

	function setState( button, favorited ) {
		button.classList.toggle( 'is-active', favorited );
		button.setAttribute( 'aria-pressed', favorited ? 'true' : 'false' );
		button.removeAttribute( 'title' );

		var state = button.querySelector( '.nntm-favorite-button__state' );
		if ( state ) {
			state.textContent = favorited ? config.activeText : config.inactiveText;
		}
	}
} )();
