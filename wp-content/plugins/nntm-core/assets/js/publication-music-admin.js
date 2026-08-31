( function () {
	'use strict';

	var root = document.querySelector( '[data-nntm-music-admin]' );
	if ( ! root ) { return; }

	var list     = root.querySelector( '[data-nntm-music-list]' );
	var template = root.querySelector( '[data-nntm-music-template]' );
	var empty    = root.querySelector( '[data-nntm-music-empty]' );
	var strings  = window.nntmPublicationMusicAdmin || {};

	function rows() {
		return Array.prototype.slice.call( list.querySelectorAll( '[data-nntm-music-row]' ) );
	}

	function refresh() {
		rows().forEach( function ( row, index ) {
			row.querySelectorAll( '[name]' ).forEach( function ( field ) {
				field.name = field.name.replace( /nntm_music\[[^\]]+\]/, 'nntm_music[' + index + ']' );
			} );
		} );

		if ( empty ) { empty.hidden = rows().length > 0; }
	}

	function choose( row ) {
		if ( ! window.wp || ! wp.media ) { return; }

		var frame = wp.media( {
			title: strings.chooseTitle || 'Chọn tệp nhạc',
			button: { text: strings.chooseButton || 'Dùng tệp nhạc này' },
			library: { type: 'audio' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var idField    = row.querySelector( '[data-nntm-music-id]' );
			var titleField = row.querySelector( '[data-nntm-music-title]' );
			var audio      = row.querySelector( '[data-nntm-music-audio]' );
			var source     = audio ? audio.querySelector( 'source' ) : null;
			var fileName   = row.querySelector( '[data-nntm-music-filename]' );
			var button     = row.querySelector( '[data-nntm-music-choose]' );

			if ( idField ) { idField.value = attachment.id || ''; }
			if ( titleField && ! titleField.value.trim() ) { titleField.value = attachment.title || attachment.filename || ''; }
			if ( source ) { source.src = attachment.url || ''; }
			if ( audio ) { audio.hidden = false; audio.load(); }
			if ( fileName ) { fileName.textContent = attachment.filename || attachment.url || ''; }
			if ( button ) { button.textContent = 'Đổi tệp'; }
		} );

		frame.open();
	}

	root.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( 'button' );
		if ( ! button ) { return; }

		var row = button.closest( '[data-nntm-music-row]' );

		if ( button.matches( '[data-nntm-music-add]' ) ) {
			var index = rows().length;
			list.insertAdjacentHTML( 'beforeend', template.innerHTML.replace( /__INDEX__/g, String( index ) ) );
			refresh();
			list.lastElementChild.querySelector( '[data-nntm-music-choose]' ).focus();
			return;
		}

		if ( ! row ) { return; }

		if ( button.matches( '[data-nntm-music-choose]' ) ) {
			choose( row );
		} else if ( button.matches( '[data-nntm-music-remove]' ) ) {
			row.remove();
			refresh();
		} else if ( button.matches( '[data-nntm-music-up]' ) && row.previousElementSibling ) {
			list.insertBefore( row, row.previousElementSibling );
			refresh();
		} else if ( button.matches( '[data-nntm-music-down]' ) && row.nextElementSibling ) {
			list.insertBefore( row.nextElementSibling, row );
			refresh();
		}
	} );

	var form = root.querySelector( 'form' );
	if ( form ) { form.addEventListener( 'submit', refresh ); }

	refresh();
}() );
