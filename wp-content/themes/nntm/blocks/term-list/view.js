( function () {
	'use strict';
	document.querySelectorAll( '.nntm-term-list--phap-toa' ).forEach( function ( root ) {
		var track = root.querySelector( '[data-term-track]' );
		var prev = root.querySelector( '[data-term-prev]' );
		var next = root.querySelector( '[data-term-next]' );
		if ( ! track || ! prev || ! next ) return;
		var timer = null;
		var originals = Array.from( track.querySelectorAll( '.nntm-term-card' ) );
		var current = 0;
		originals.forEach( function ( card ) {
			var clone = card.cloneNode( true );
			clone.setAttribute( 'aria-hidden', 'true' );
			clone.setAttribute( 'tabindex', '-1' );
			clone.classList.add( 'is-clone' );
			track.appendChild( clone );
		} );
		function stepSize() {
			var card = originals[ 0 ];
			var gap = parseFloat( window.getComputedStyle( track ).gap ) || 20;
			// offsetWidth chứ không phải rect: responsive.css thu nhỏ khung bằng
			// `zoom` dưới 1366 nên rect lệch hệ đo với track.scrollTo().
			return ( card ? card.offsetWidth : 350 ) + gap;
		}
		function move( direction ) {
			var step = stepSize();
			if ( direction < 0 && current === 0 ) {
				current = originals.length;
				track.scrollTo( { left: current * step, behavior: 'auto' } );
			}
			current += direction;
			track.scrollTo( { left: current * step, behavior: 'smooth' } );
			if ( current >= originals.length ) {
				window.setTimeout( function () {
					current = 0;
					track.scrollTo( { left: 0, behavior: 'auto' } );
				}, 650 );
			}
		}
		function stop() { if ( timer ) { window.clearInterval( timer ); timer = null; } }
		function start() {
			stop();
			if ( root.dataset.autoplay !== '1' || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) return;
			var seconds = Math.max( 2, Math.min( 20, parseInt( root.dataset.interval, 10 ) || 5 ) );
			timer = window.setInterval( function () { move( 1 ); }, seconds * 1000 );
		}
		prev.addEventListener( 'click', function () { move( -1 ); start(); } );
		next.addEventListener( 'click', function () { move( 1 ); start(); } );
		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );
		root.addEventListener( 'focusin', stop );
		root.addEventListener( 'focusout', function ( event ) { if ( ! event.relatedTarget || ! root.contains( event.relatedTarget ) ) start(); } );
		document.addEventListener( 'visibilitychange', function () { if ( document.hidden ) stop(); else start(); } );
		start();
	} );
} )();
