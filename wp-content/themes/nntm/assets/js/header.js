 
( function () {
	'use strict';

	var header = document.querySelector( '.nntm-header' );
	if ( ! header ) {
		return;
	}

	var FOCUSABLE_SELECTOR = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])'
	].join( ',' );

	 
	var MENU_MQ = '(max-width: 1151px)';

	var toggle = header.querySelector( '.nntm-header__menu-toggle' );
	var panel  = header.querySelector( '.nntm-header__panel' );

	if ( toggle && panel ) {

		function openMenu() {
			header.classList.add( 'nntm-header--menu-open' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			 
			document.documentElement.classList.add( 'nntm-menu-dang-mo' );
			document.addEventListener( 'keydown', onMenuKeydown, true );
			document.addEventListener( 'focus', trapFocus, true );

			var firstFocusable = panel.querySelector( FOCUSABLE_SELECTOR );
			if ( firstFocusable ) {
				firstFocusable.focus();
			}
		}

		function closeMenu( restoreFocus ) {
			header.classList.remove( 'nntm-header--menu-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			document.documentElement.classList.remove( 'nntm-menu-dang-mo' );
			document.removeEventListener( 'keydown', onMenuKeydown, true );
			document.removeEventListener( 'focus', trapFocus, true );

			if ( restoreFocus ) {
				toggle.focus();
			}
		}

		function trapFocus( event ) {
			if ( panel.contains( event.target ) ) {
				return;
			}

			var focusableItems = panel.querySelectorAll( FOCUSABLE_SELECTOR );
			if ( focusableItems.length ) {
				focusableItems[ 0 ].focus();
			}
		}

		function onMenuKeydown( event ) {
			if ( 'Escape' === event.key || 'Esc' === event.key ) {
				closeMenu( true );
			}
		}

		toggle.addEventListener( 'click', function () {
			var isOpen = header.classList.contains( 'nntm-header--menu-open' );
			if ( isOpen ) {
				closeMenu( false );
			} else {
				openMenu();
			}
		} );

		header.querySelectorAll( '[data-nntm-menu-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				closeMenu( true );
			} );
		} );

		panel.addEventListener( 'click', function ( event ) {
			var link = event.target.closest ? event.target.closest( 'a[href]' ) : null;
			if ( link && ! link.closest( '.nntm-header__account-panel' ) ) {
				closeMenu( false );
			}
		} );

		if ( window.matchMedia ) {
			var menuQuery = window.matchMedia( MENU_MQ );
			var onQueryChange = function ( event ) {
				if ( ! event.matches ) {
					closeMenu( false );
				}
			};

			if ( menuQuery.addEventListener ) {
				menuQuery.addEventListener( 'change', onQueryChange );
			} else if ( menuQuery.addListener ) {
				menuQuery.addListener( onQueryChange );
			}
		}



		 
		var parentItems = panel.querySelectorAll( '.menu-item-has-children' );

		parentItems.forEach( function ( item ) {
			var link = item.querySelector( ':scope > a' );
			if ( link ) {
				link.addEventListener( 'focus', function () {
					item.classList.add( 'nntm-main-nav__item--open' );
				} );

				link.addEventListener( 'click', function ( event ) {
					if ( ! window.matchMedia || ! window.matchMedia( MENU_MQ ).matches ) {
						return;
					}
					if ( item.classList.contains( 'nntm-main-nav__item--open' ) ) {
						return;
					}
					event.preventDefault();
					event.stopPropagation();
					item.classList.add( 'nntm-main-nav__item--open' );
				} );
			}

			item.addEventListener( 'focusout', function () {

				window.requestAnimationFrame( function () {
					if ( ! item.contains( document.activeElement ) ) {
						item.classList.remove( 'nntm-main-nav__item--open' );
					}
				} );
			} );

			item.addEventListener( 'keydown', function ( event ) {
				if ( 'Escape' === event.key || 'Esc' === event.key ) {
					item.classList.remove( 'nntm-main-nav__item--open' );
					if ( link ) {
						link.focus();
					}
				}
			} );
		} );
	}

	var accountToggle = header.querySelector( '.nntm-header__account-toggle' );
	var accountPanel  = header.querySelector( '.nntm-header__account-panel' );

	if ( accountToggle && accountPanel ) {

		function closeAccountMenu( restoreFocus ) {
			accountToggle.setAttribute( 'aria-expanded', 'false' );
			accountPanel.hidden = true;
			document.removeEventListener( 'click', onOutsideClick, true );
			document.removeEventListener( 'keydown', onAccountKeydown, true );

			if ( restoreFocus ) {
				accountToggle.focus();
			}
		}

		function openAccountMenu() {
			accountToggle.setAttribute( 'aria-expanded', 'true' );
			accountPanel.hidden = false;
			document.addEventListener( 'click', onOutsideClick, true );
			document.addEventListener( 'keydown', onAccountKeydown, true );
		}

		function onOutsideClick( event ) {
			if ( accountToggle.contains( event.target ) || accountPanel.contains( event.target ) ) {
				return;
			}
			closeAccountMenu( false );
		}

		function onAccountKeydown( event ) {
			if ( 'Escape' === event.key || 'Esc' === event.key ) {
				closeAccountMenu( true );
			}
		}

		accountToggle.addEventListener( 'click', function () {
			var isOpen = 'true' === accountToggle.getAttribute( 'aria-expanded' );
			if ( isOpen ) {
				closeAccountMenu( false );
			} else {
				openAccountMenu();
			}
		} );
	}

	 
	if ( header.classList.contains( 'nntm-header--sticky' ) && 'IntersectionObserver' in window ) {
		var sentinel = document.createElement( 'div' );
		sentinel.className = 'nntm-header__sentinel';
		sentinel.setAttribute( 'aria-hidden', 'true' );
		sentinel.style.position = 'absolute';
		sentinel.style.top = '0';
		sentinel.style.height = '1px';
		sentinel.style.width = '1px';
		sentinel.style.pointerEvents = 'none';
		sentinel.style.visibility = 'hidden';

		if ( header.parentNode ) {
			header.parentNode.insertBefore( sentinel, header );
		}

		var stuckObserver = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					header.classList.toggle( 'nntm-header--stuck', ! entry.isIntersecting );
				} );
			},
			{ threshold: 0 }
		);

		stuckObserver.observe( sentinel );
	}
} )();
