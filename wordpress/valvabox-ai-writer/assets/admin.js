/* Valvabox AI Writer: settings "Test connection" and Tools > AI Writer playground. */
( function ( apiFetch, i18n ) {
	'use strict';

	document.querySelectorAll( 'input[name="vbai_settings[provider]"]' ).forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			document.querySelectorAll( '.vbai-card' ).forEach( function ( card ) {
				card.classList.toggle( 'is-active', card.contains( radio ) );
			} );
		} );
	} );

	document.querySelectorAll( '.vbai-test' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var out = button.nextElementSibling;
			out.className = 'vbai-test-result';
			out.textContent = i18n.testing;
			button.disabled = true;
			apiFetch( { path: '/vbai/v1/test', method: 'POST', data: { provider: button.dataset.provider } } )
				.then( function ( res ) {
					out.classList.add( res.ok ? 'is-ok' : 'is-error' );
					out.textContent = res.ok ? '✓ ' + res.reply : '✗ ' + res.message;
				} )
				.catch( function ( err ) {
					out.classList.add( 'is-error' );
					out.textContent = '✗ ' + ( ( err && err.message ) || 'Error' );
				} )
				.finally( function () {
					button.disabled = false;
				} );
		} );
	} );

	var go = document.getElementById( 'vbai-play-go' );
	if ( ! go ) {
		return;
	}
	var out = document.getElementById( 'vbai-play-out' );
	var status = document.getElementById( 'vbai-play-status' );

	go.addEventListener( 'click', function () {
		go.disabled = true;
		status.textContent = i18n.working;
		apiFetch( {
			path: '/vbai/v1/generate',
			method: 'POST',
			data: {
				action: document.getElementById( 'vbai-play-action' ).value,
				text: document.getElementById( 'vbai-play-text' ).value,
				tone: document.getElementById( 'vbai-play-tone' ).value,
				language: document.getElementById( 'vbai-play-language' ).value,
				fresh: true,
			},
		} )
			.then( function ( res ) {
				out.value = res.text;
				status.textContent = '✓ ' + res.provider;
			} )
			.catch( function ( err ) {
				status.textContent = '✗ ' + ( ( err && err.message ) || 'Error' );
			} )
			.finally( function () {
				go.disabled = false;
			} );
	} );

	document.getElementById( 'vbai-play-copy' ).addEventListener( 'click', function () {
		out.select();
		document.execCommand( 'copy' );
		status.textContent = i18n.copied;
	} );
} )( window.wp.apiFetch, window.vbaiAdmin );
