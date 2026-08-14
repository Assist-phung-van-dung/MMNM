( function () {
  'use strict';
  document.querySelectorAll( '.nntm-dt__slider' ).forEach( function ( slider ) {
    var root = slider.closest( '.nntm-dt' );
    var slides = Array.from( slider.querySelectorAll( '.nntm-dt__slide' ) );
    if ( slides.length < 2 ) return;
    var current = 0, timer = null, startX = 0;
    function paint() {
      var total = slides.length;
      slides.forEach( function ( slide, index ) {
        var delta = ( index - current + total ) % total;
        if ( delta > total / 2 ) delta -= total;
        slide.style.setProperty( '--dt-offset', String( delta ) );
        slide.classList.toggle( 'is-active', delta === 0 );
        slide.setAttribute( 'aria-hidden', delta === 0 ? 'false' : 'true' );
      } );
    }
    function go( offset ) { current = ( current + offset + slides.length ) % slides.length; paint(); restart(); }
    function restart() { if ( timer ) clearInterval( timer ); if ( root.dataset.autoplay === '1' && ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) timer = setInterval( function(){ go( 1 ); }, ( parseInt( root.dataset.interval, 10 ) || 6 ) * 1000 ); }
    slider.querySelector( '[data-dt-prev]' ).addEventListener( 'click', function(){ go( -1 ); } );
    slider.querySelector( '[data-dt-next]' ).addEventListener( 'click', function(){ go( 1 ); } );
    slider.querySelector( '[data-dt-track]' ).addEventListener( 'keydown', function( e ){ if ( e.key === 'ArrowLeft' ) go( -1 ); if ( e.key === 'ArrowRight' ) go( 1 ); } );
    slider.addEventListener( 'pointerdown', function( e ){ startX = e.clientX; } );
    slider.addEventListener( 'pointerup', function( e ){ if ( Math.abs( e.clientX - startX ) > 45 ) go( e.clientX < startX ? 1 : -1 ); } );
    slider.addEventListener( 'mouseenter', function(){ if ( timer ) clearInterval( timer ); } );
    slider.addEventListener( 'mouseleave', restart );
    paint(); restart();
  } );
} )();
