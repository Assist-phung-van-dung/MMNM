/**
 * Soketi/Pusher Presence cho Thiền Đường.
 *
 * Tổng trang: presence-nntm-thien-duong.
 * Đang nghe bài: presence-nntm-thien-duong-track-{POST_ID}.
 *
 * Một user mở nhiều tab vẫn được Presence Channel gom theo user_id ở tầng
 * Pusher/Soketi. Track channel chỉ được subscribe khi audio ở trạng thái
 * playing; pause/ended/đổi bài sẽ unsubscribe.
 */
( function () {
	'use strict';

	var cfg = window.NNTMZenTrackRealtime || null;
	if ( ! cfg || ! cfg.enabled ) {
		return;
	}

	var players = document.querySelectorAll( '[data-nntm-thien-duong]' );
	if ( ! players.length ) {
		return;
	}

	function setText( root, selector, value ) {
		var node = root.querySelector( selector );
		if ( node ) {
			node.textContent = String( value );
		}
	}

	function setConnectionState( state ) {
		for ( var i = 0; i < players.length; i++ ) {
			players[ i ].setAttribute( 'data-nntm-realtime-state', state );
			var live = players[ i ].querySelector( '.nntm-thien-duong__live-status' );
			if ( live ) {
				live.hidden = false;
			}
		}
	}

	if ( 'function' !== typeof window.Pusher ) {
		setConnectionState( 'failed' );
		return;
	}

	var options = {
		cluster: 'mt1',
		wsHost: cfg.wsHost,
		wsPort: cfg.wsPort || 6001,
		wssPort: cfg.wssPort || 443,
		forceTLS: !! cfg.forceTLS,
		enabledTransports: [ 'ws' ],
		enableStats: false,
		channelAuthorization: {
			endpoint: cfg.authEndpoint,
			transport: 'ajax',
			params: {
				action: cfg.authAction,
				nonce: cfg.authNonce,
			},
		},
	};
	if ( cfg.wsPath ) {
		options.wsPath = cfg.wsPath;
	}

	var pusher;
	try {
		pusher = new window.Pusher( cfg.appKey, options );
	} catch ( error ) {
		setConnectionState( 'failed' );
		return;
	}

	setConnectionState( 'connecting' );
	pusher.connection.bind( 'state_change', function ( states ) {
		setConnectionState( states && states.current ? states.current : 'unknown' );
	} );
	pusher.connection.bind( 'error', function () {
		setConnectionState( 'error' );
	} );

	function updatePageCount( count ) {
		count = Math.max( 0, parseInt( count, 10 ) || 0 );
		for ( var i = 0; i < players.length; i++ ) {
			setText( players[ i ], '[data-nntm-page-presence-count]', count );
		}
	}

	var pageChannel = pusher.subscribe( cfg.pageChannel );
	function refreshPageCount() {
		if ( pageChannel && pageChannel.members ) {
			updatePageCount( pageChannel.members.count );
		}
	}
	pageChannel.bind( 'pusher:subscription_succeeded', function ( members ) {
		updatePageCount( members && 'number' === typeof members.count ? members.count : 0 );
	} );
	pageChannel.bind( 'pusher:member_added', refreshPageCount );
	pageChannel.bind( 'pusher:member_removed', refreshPageCount );
	pageChannel.bind( 'pusher:subscription_error', function () {
		for ( var i = 0; i < players.length; i++ ) {
			setText( players[ i ], '[data-nntm-page-presence-count]', '—' );
		}
	} );

	function initTrackPresence( root ) {
		var state = {
			trackId: 0,
			channelName: '',
			channel: null,
		};

		function setTrackCount( value ) {
			setText( root, '[data-nntm-track-presence-count]', value );
		}

		function leaveTrackChannel() {
			if ( state.channelName ) {
				try {
					pusher.unsubscribe( state.channelName );
				} catch ( ignore ) {}
			}
			state.channelName = '';
			state.channel = null;
			// Không bịa số khi chính client không còn subscribe kênh bài.
			setTrackCount( '—' );
		}

		function joinTrackChannel( trackId ) {
			trackId = parseInt( trackId, 10 ) || 0;
			if ( ! trackId ) {
				leaveTrackChannel();
				return;
			}

			var channelName = cfg.trackPrefix + String( trackId );
			if ( state.channelName === channelName && state.channel ) {
				return;
			}

			leaveTrackChannel();
			state.trackId = trackId;
			state.channelName = channelName;
			state.channel = pusher.subscribe( channelName );

			var channel = state.channel;
			function refreshTrackCount() {
				if ( state.channel === channel && channel.members ) {
					setTrackCount( channel.members.count );
				}
			}
			channel.bind( 'pusher:subscription_succeeded', function ( members ) {
				if ( state.channel === channel ) {
					setTrackCount( members && 'number' === typeof members.count ? members.count : 0 );
				}
			} );
			channel.bind( 'pusher:member_added', refreshTrackCount );
			channel.bind( 'pusher:member_removed', refreshTrackCount );
			channel.bind( 'pusher:subscription_error', function () {
				if ( state.channel === channel ) {
					setTrackCount( '—' );
				}
			} );
		}

		root.addEventListener( 'nntm:thien-duong-track-change', function ( event ) {
			state.trackId = event && event.detail ? ( parseInt( event.detail.trackId, 10 ) || 0 ) : 0;
			// Nếu chỉ đổi/chọn bài mà chưa phát thì chưa phải "đang nghe".
			if ( ! event.detail || ! event.detail.playing ) {
				leaveTrackChannel();
			}
		} );

		root.addEventListener( 'nntm:thien-duong-playback-state', function ( event ) {
			var detail = event && event.detail ? event.detail : {};
			state.trackId = parseInt( detail.trackId, 10 ) || state.trackId || 0;
			if ( detail.playing && state.trackId ) {
				joinTrackChannel( state.trackId );
			} else {
				leaveTrackChannel();
			}
		} );

		// View script có thể khởi tạo trước realtime.js; đọc lại trạng thái hiện tại.
		state.trackId = parseInt( root.getAttribute( 'data-current-track-id' ), 10 ) || 0;
		var audio = root.querySelector( '.nntm-thien-duong__audio' );
		if ( audio && state.trackId && ! audio.paused && ! audio.ended ) {
			joinTrackChannel( state.trackId );
		}
	}

	for ( var i = 0; i < players.length; i++ ) {
		initTrackPresence( players[ i ] );
	}

	window.addEventListener( 'pagehide', function () {
		try {
			pusher.disconnect();
		} catch ( ignore ) {}
	}, { once: true } );
} )();
