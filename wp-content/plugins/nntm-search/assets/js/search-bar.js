/**
 * Header search bar: instant suggestions, keyboard shortcuts, search by image.
 *
 * Vanilla JS, no framework — matches the theme's convention. Attaches to the
 * form already in the markup; it does not build the search bar itself.
 */
( function () {
	'use strict';

	var form = document.querySelector( '.nntm-header__search-form' );

	if ( ! form || typeof nntmSearch === 'undefined' ) {
		return;
	}

	var field = form.querySelector( '.nntm-header__search-field' );

	if ( ! field ) {
		return;
	}

	var panel = document.createElement( 'div' );
	panel.className = 'nntm-suggest';
	panel.id = 'nntm-suggest';
	panel.setAttribute( 'role', 'listbox' );
	panel.hidden = true;
	form.appendChild( panel );

	field.setAttribute( 'autocomplete', 'off' );
	field.setAttribute( 'role', 'combobox' );
	field.setAttribute( 'aria-expanded', 'false' );
	field.setAttribute( 'aria-controls', 'nntm-suggest' );

	var timer = 0;
	var inFlight = null;
	var cursor = -1;

	/**
	 * Open or close the panel.
	 *
	 * @param {boolean} open Whether it should be visible.
	 */
	function setOpen( open ) {
		panel.hidden = ! open;
		field.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		if ( ! open ) {
			cursor = -1;
		}
	}

	/**
	 * Escape a string this script produced (i18n messages, keywords).
	 *
	 * Result content does NOT pass through here: the server already escaped it
	 * and deliberately left <mark> intact for highlighting.
	 *
	 * @param {string} value Raw string.
	 * @return {string} HTML-safe string.
	 */
	function escapeHtml( value ) {
		var box = document.createElement( 'div' );
		box.textContent = value;
		return box.innerHTML;
	}

	/**
	 * Show a status message in the panel.
	 *
	 * @param {string}  message  Text to show.
	 * @param {boolean} spinning Whether to show the spinner.
	 */
	function showStatus( message, spinning ) {
		panel.innerHTML =
			'<p class="nntm-suggest__status">' +
			( spinning ? '<span class="nntm-suggest__spinner" aria-hidden="true"></span>' : '' ) +
			escapeHtml( message ) +
			'</p>';
		setOpen( true );
	}

	/**
	 * Build the row markup for one result.
	 *
	 * @param {Object} item One result from the API.
	 * @return {string} HTML.
	 */
	function renderItem( item ) {
		var thumb = item.thumb
			? '<img class="nntm-suggest__thumb" src="' + item.thumb + '" alt="" loading="lazy">'
			: '<span class="nntm-suggest__thumb nntm-suggest__thumb--empty" aria-hidden="true"></span>';

		return (
			'<a class="nntm-suggest__item" role="option" href="' + item.permalink + '">' +
				thumb +
				'<span class="nntm-suggest__text">' +
					'<span class="nntm-suggest__title">' + item.title + '</span>' +
					'<span class="nntm-suggest__excerpt">' + item.excerpt + '</span>' +
				'</span>' +
				( item.label ? '<span class="nntm-suggest__badge">' + escapeHtml( item.label ) + '</span>' : '' ) +
			'</a>'
		);
	}

	/**
	 * Render a result list.
	 *
	 * @param {Object} data    API payload.
	 * @param {string} heading Optional heading HTML shown above the list.
	 */
	function renderResults( data, heading ) {
		if ( ! data.results || ! data.results.length ) {
			showStatus( nntmSearch.i18n.noResults, false );
			return;
		}

		var html = heading || '';

		html += data.results.map( renderItem ).join( '' );

		if ( data.see_all ) {
			html +=
				'<a class="nntm-suggest__all" href="' + data.see_all + '">' +
				escapeHtml( nntmSearch.i18n.seeAll ) + ' (' + data.total + ')' +
				'</a>';
		}

		panel.innerHTML = html;
		setOpen( true );
	}

	/**
	 * Fetch suggestions, cancelling any in-flight request so results cannot
	 * arrive out of order.
	 *
	 * @param {string} query Search text.
	 */
	function suggest( query ) {
		if ( inFlight ) {
			inFlight.abort();
		}

		inFlight = new AbortController();
		showStatus( nntmSearch.i18n.searching, true );

		fetch( nntmSearch.root + 'suggest?q=' + encodeURIComponent( query ), {
			signal: inFlight.signal,
			credentials: 'same-origin'
		} )
			.then( function ( response ) {
				if ( response.status === 429 ) {
					showStatus( nntmSearch.i18n.tooFast, false );
					return null;
				}
				if ( ! response.ok ) {
					showStatus( nntmSearch.i18n.failed, false );
					return null;
				}
				return response.json();
			} )
			.then( function ( data ) {
				if ( data ) {
					renderResults( data, '' );
				}
			} )
			.catch( function ( error ) {
				if ( error.name !== 'AbortError' ) {
					showStatus( nntmSearch.i18n.failed, false );
				}
			} );
	}

	// Suggest as the visitor types, waiting 300ms for them to stop.
	field.addEventListener( 'input', function () {
		var query = field.value.trim();

		window.clearTimeout( timer );

		if ( query.length < 2 ) {
			setOpen( false );
			return;
		}

		timer = window.setTimeout( function () {
			suggest( query );
		}, 300 );
	} );

	// Move through the list with the keyboard.
	field.addEventListener( 'keydown', function ( event ) {
		var items = panel.querySelectorAll( '.nntm-suggest__item, .nntm-suggest__all' );

		if ( event.key === 'Escape' ) {
			setOpen( false );
			return;
		}

		if ( ! items.length || panel.hidden ) {
			return;
		}

		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			cursor += ( event.key === 'ArrowDown' ? 1 : -1 );

			if ( cursor < 0 ) {
				cursor = items.length - 1;
			}
			if ( cursor >= items.length ) {
				cursor = 0;
			}

			Array.prototype.forEach.call( items, function ( item, index ) {
				item.classList.toggle( 'is-active', index === cursor );
			} );

			items[ cursor ].scrollIntoView( { block: 'nearest' } );
		}

		if ( event.key === 'Enter' && cursor >= 0 ) {
			event.preventDefault();
			window.location.href = items[ cursor ].getAttribute( 'href' );
		}
	} );

	// Shortcuts: Ctrl/⌘+K anywhere, or "/" when not already typing in a field.
	document.addEventListener( 'keydown', function ( event ) {
		var typing = /^(INPUT|TEXTAREA|SELECT)$/.test( event.target.tagName ) || event.target.isContentEditable;

		if ( ( event.key === 'k' && ( event.metaKey || event.ctrlKey ) ) || ( event.key === '/' && ! typing ) ) {
			event.preventDefault();
			field.focus();
			field.select();
		}
	} );

	// Click outside closes the panel.
	document.addEventListener( 'click', function ( event ) {
		if ( ! form.contains( event.target ) ) {
			setOpen( false );
		}
	} );

	/* =====================================================================
	 * Search by image. Config-gated: define( 'NNTM_SEARCH_IMAGE_ENABLED', false )
	 * in wp-config.php turns this whole block off, not just the button — an
	 * operator without tools/embed-service running wants no dead control at
	 * all, not a button that always fails.
	 * ===================================================================== */

	if ( nntmSearch.imageEnabled ) {
		var cameraButton = form.querySelector( '.nntm-header__search-camera' );
		var fileInput    = form.querySelector( '.nntm-header__search-file' );

		// The theme ships both elements hidden. Only the plugin reveals them, so
		// disabling the plugin removes the button instead of leaving a dead control.
		if ( cameraButton && fileInput ) {
			cameraButton.hidden = false;

			cameraButton.addEventListener( 'click', function () {
				fileInput.click();
			} );

			fileInput.addEventListener( 'change', function () {
				if ( fileInput.files && fileInput.files[ 0 ] ) {
					searchByImage( fileInput.files[ 0 ] );
				}
				// Clearing the value lets the SAME file fire 'change' again.
				fileInput.value = '';
			} );
		}

		// Drag an image onto the search bar.
		[ 'dragenter', 'dragover' ].forEach( function ( name ) {
			form.addEventListener( name, function ( event ) {
				event.preventDefault();
				form.classList.add( 'is-dropping' );
			} );
		} );

		[ 'dragleave', 'drop' ].forEach( function ( name ) {
			form.addEventListener( name, function () {
				form.classList.remove( 'is-dropping' );
			} );
		} );

		form.addEventListener( 'drop', function ( event ) {
			event.preventDefault();

			var file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[ 0 ] : null;

			if ( file && file.type.indexOf( 'image/' ) === 0 ) {
				searchByImage( file );
			}
		} );

		// Paste an image (Ctrl+V) while the field has focus — screenshot straight in,
		// no need to save it to disk first.
		field.addEventListener( 'paste', function ( event ) {
			var items = event.clipboardData ? event.clipboardData.items : null;

			if ( ! items ) {
				return;
			}

			for ( var i = 0; i < items.length; i++ ) {
				if ( items[ i ].type.indexOf( 'image/' ) === 0 ) {
					event.preventDefault();
					searchByImage( items[ i ].getAsFile() );
					return;
				}
			}
		} );
	}

	/**
	 * Build the "this picture shows: …" heading from the detected keywords.
	 *
	 * Showing them is the point, not decoration: the visitor can see why these
	 * results came back, and click a word to search it on its own.
	 *
	 * @param {Array}  keywords Detected keywords.
	 * @param {string} mode     'keyword' or 'similar'.
	 * @return {string} HTML.
	 */
	function renderKeywords( keywords, mode ) {
		var hasKeywords = keywords && keywords.length;

		if ( ! hasKeywords && mode !== 'similar' ) {
			return '';
		}

		// Three distinct situations, and saying which one it is matters: "read no
		// keyword" and "read a keyword nobody writes about" look identical to the
		// visitor but mean opposite things about their next move.
		var label = nntmSearch.i18n.imageReads;

		if ( mode === 'similar' ) {
			label = hasKeywords ? nntmSearch.i18n.noTextMatch : nntmSearch.i18n.noKeyword;
		}

		if ( ! hasKeywords ) {
			return (
				'<div class="nntm-suggest__keywords">' +
					'<span class="nntm-suggest__keywords-label">' + escapeHtml( label ) + '</span>' +
				'</div>'
			);
		}

		var chips = keywords.map( function ( item ) {
			var percent = Math.round( item.score * 100 );

			return (
				'<button type="button" class="nntm-suggest__chip" data-word="' + escapeHtml( item.word ) + '">' +
					escapeHtml( item.word ) +
					'<span class="nntm-suggest__chip-score">' + percent + '%</span>' +
				'</button>'
			);
		} ).join( '' );

		return (
			'<div class="nntm-suggest__keywords">' +
				'<span class="nntm-suggest__keywords-label">' + escapeHtml( label ) + '</span>' +
				'<span class="nntm-suggest__chips">' + chips + '</span>' +
			'</div>'
		);
	}

	// Clicking a keyword runs it as an ordinary text search.
	panel.addEventListener( 'click', function ( event ) {
		var chip = event.target.closest ? event.target.closest( '.nntm-suggest__chip' ) : null;

		if ( ! chip ) {
			return;
		}

		event.preventDefault();
		field.value = chip.getAttribute( 'data-word' );
		suggest( field.value );
	} );

	/**
	 * Send an image and show what came back.
	 *
	 * @param {File} file Image chosen, dropped or pasted.
	 */
	function searchByImage( file ) {
		if ( ! file ) {
			return;
		}

		// Checked here to save a round trip. The server checks again with finfo —
		// this check is a courtesy, not a security measure, since anyone can edit
		// the JavaScript.
		if ( file.size > 5 * 1024 * 1024 ) {
			showStatus( nntmSearch.i18n.imageTooBig, false );
			return;
		}

		if ( inFlight ) {
			inFlight.abort();
		}

		inFlight = new AbortController();
		showStatus( nntmSearch.i18n.readingImage, true );

		var payload = new FormData();
		payload.append( 'anh', file );

		fetch( nntmSearch.root + 'image', {
			method: 'POST',
			body: payload,
			signal: inFlight.signal,
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nntmSearch.nonce }
		} )
			.then( function ( response ) {
				if ( response.status === 429 ) {
					showStatus( nntmSearch.i18n.tooFast, false );
					return null;
				}
				if ( response.status === 413 ) {
					showStatus( nntmSearch.i18n.imageTooBig, false );
					return null;
				}
				if ( response.status === 415 ) {
					showStatus( nntmSearch.i18n.imageBadType, false );
					return null;
				}
				if ( ! response.ok ) {
					showStatus( nntmSearch.i18n.failed, false );
					return null;
				}
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data ) {
					return;
				}

				var heading = renderKeywords( data.keywords, data.mode );

				if ( ! data.results || ! data.results.length ) {
					// Still show the words: knowing what the picture was read as
					// explains the empty result far better than "nothing found".
					panel.innerHTML = heading +
						'<p class="nntm-suggest__status">' + escapeHtml( nntmSearch.i18n.noImageMatch ) + '</p>';
					setOpen( true );
					return;
				}

				renderResults( data, heading );
			} )
			.catch( function ( error ) {
				if ( error.name !== 'AbortError' ) {
					showStatus( nntmSearch.i18n.failed, false );
				}
			} );
	}
}() );
