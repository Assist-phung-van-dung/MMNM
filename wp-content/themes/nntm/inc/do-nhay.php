<?php

defined( 'ABSPATH' ) || exit;

function nntm_do_nhay_bat(): bool {
	if ( is_admin() ) {
		return false;
	}

	return isset( $_GET['nntm-do-nhay'] ) && '1' === $_GET['nntm-do-nhay'];
}

function nntm_do_nhay_script(): void {
	if ( ! nntm_do_nhay_bat() ) {
		return;
	}
	?>
	<script>
	( function () {
		if ( ! window.PerformanceObserver ) {
			return;
		}

		var root = document.documentElement;
		var ds = [];

		function ten( node ) {
			if ( ! node || 1 !== node.nodeType ) {
				return '(?)';
			}

			var c = ( node.className || '' ).toString().split( /\s+/ ).filter( function ( x ) {
				return x && 0 !== x.indexOf( 'nntm-reveal' ) && 'is-hien' !== x;
			} )[ 0 ];

			return node.tagName.toLowerCase() + ( c ? '.' + c : '' );
		}

		try {
			new window.PerformanceObserver( function ( ds_ ) {
				ds_.getEntries().forEach( function ( e ) {
					if ( e.hadRecentInput || e.value < 0.0005 ) {
						return;
					}

					( e.sources || [] ).forEach( function ( s ) {
						ds.push( {
							giay: Math.round( e.startTime ) / 1000,
							diem: Math.round( e.value * 10000 ) / 10000,
							el: ten( s.node ),
							truoc: Math.round( s.previousRect.top ),
							sau: Math.round( s.currentRect.top )
						} );
					} );
				} );

				ds.sort( function ( a, b ) {
					return b.diem - a.diem;
				} );

				root.setAttribute( 'data-nntm-nhay', ds.slice( 0, 5 ).map( function ( x ) {
					return x.giay + 's ' + x.el + ' ' + x.truoc + '->' + x.sau + ' (' + x.diem + ')';
				} ).join( ' | ' ) );

				window.console && console.table && console.table( ds.slice( 0, 12 ) );
			} ).observe( { type: 'layout-shift', buffered: true } );
		} catch ( e ) {}
	} )();
	</script>
	<?php
}
add_action( 'wp_head', 'nntm_do_nhay_script', 2 );
