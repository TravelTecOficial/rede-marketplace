/* Rede Marketplace — nota e avaliações do Perfil do Google da agência, buscadas no navegador a cada visita
 * (os termos do Google só deixam guardar o Place ID). Sem resposta, os blocos continuam escondidos. */
( function () {
	'use strict';
	var bloco = document.querySelector( '[data-rtm-google]' );
	if ( ! bloco ) { return; }
	var placeId = bloco.getAttribute( 'data-rtm-google' );
	var chave   = bloco.getAttribute( 'data-chave' );
	var nota    = document.querySelector( '[data-rtm-google-nota]' );
	var ver     = document.querySelector( '[data-rtm-google-ver]' );
	var cores   = [ '#7a4fa3', '#2f6b8a', '#a35a2f', '#3f7d4e', '#8a2f55' ];

	function el( tag, classe, texto ) {
		var e = document.createElement( tag );
		if ( classe ) { e.className = classe; }
		if ( texto !== undefined ) { e.textContent = texto; }
		return e;
	}
	function estrelas( n ) {
		var c = Math.max( 0, Math.min( 5, Math.round( Number( n ) || 0 ) ) );
		return '★★★★★'.slice( 0, c ) + '☆☆☆☆☆'.slice( 0, 5 - c );
	}
	function numero( n ) { return ( Math.round( n * 10 ) / 10 ).toFixed( 1 ).replace( '.', ',' ); }
	function iniciais( nome ) {
		var p = String( nome || '' ).trim().split( /\s+/ );
		return ( ( p[ 0 ] || '' ).charAt( 0 ) + ( p.length > 1 ? p[ p.length - 1 ].charAt( 0 ) : '' ) ).toUpperCase();
	}

	function mostrar( lugar ) {
		var total = Number( lugar.userRatingCount ) || 0;
		if ( ! lugar.rating || ! total ) { return; }
		var rotulo = total === 1 ? ' avaliação' : ' avaliações';
		var qtd    = total.toLocaleString( 'pt-BR' );

		if ( nota ) {
			nota.textContent = '';
			nota.appendChild( el( 'span', 'rtm-estrelas', estrelas( lugar.rating ) ) ).setAttribute( 'aria-hidden', 'true' );
			nota.appendChild( el( 'b', '', numero( lugar.rating ) ) );
			nota.appendChild( document.createTextNode( ' · ' + qtd + rotulo + ' no Google' ) );
			nota.hidden = false;
		}
		if ( ver ) { ver.hidden = false; }

		var resumo = bloco.querySelector( '.rtm-av-resumo' );
		resumo.textContent = '';
		resumo.appendChild( el( 'b', '', numero( lugar.rating ) ) );
		var col = resumo.appendChild( el( 'div' ) );
		col.appendChild( el( 'span', 'rtm-estrelas', estrelas( lugar.rating ) ) ).setAttribute( 'aria-hidden', 'true' );
		col.appendChild( el( 'span', '', qtd + rotulo ) );

		var lista = bloco.querySelector( '.rtm-avals' );
		lista.textContent = '';
		( lugar.reviews || [] ).filter( function ( r ) { return r && r.text; } ).slice( 0, 5 ).forEach( function ( r, i ) {
			var autor = ( r.authorAttribution && r.authorAttribution.displayName ) || 'Cliente Google';
			var av    = el( 'div', 'rtm-av' );
			var cab   = av.appendChild( el( 'div', 'rtm-av-cab' ) );
			var foto  = r.authorAttribution && r.authorAttribution.photoURI;
			if ( foto ) {
				var img = el( 'img', 'rtm-av-foto' );
				img.src = foto; img.alt = ''; img.loading = 'lazy'; img.referrerPolicy = 'no-referrer';
				cab.appendChild( img );
			} else {
				var ini = cab.appendChild( el( 'span', 'rtm-av-ini', iniciais( autor ) ) );
				ini.style.background = cores[ i % cores.length ];
				ini.setAttribute( 'aria-hidden', 'true' );
			}
			var info = cab.appendChild( el( 'div' ) );
			var nome = info.appendChild( el( 'b' ) );
			var uri  = r.authorAttribution && r.authorAttribution.uri;
			if ( uri ) {
				var a = nome.appendChild( el( 'a', '', autor ) );
				a.href = uri; a.target = '_blank'; a.rel = 'noopener nofollow';
			} else {
				nome.textContent = autor;
			}
			info.appendChild( el( 'small', '', ( r.relativePublishTimeDescription ? r.relativePublishTimeDescription + ' · ' : '' ) + estrelas( r.rating ) ) );
			av.appendChild( el( 'p', '', String( r.text ) ) );
			lista.appendChild( av );
		} );

		var link = bloco.querySelector( '.rtm-av-link' );
		if ( link && lugar.googleMapsURI ) { link.href = lugar.googleMapsURI; link.hidden = false; }
		bloco.hidden = false;
	}

	function buscar() {
		window.google.maps.importLibrary( 'places' ).then( function ( lib ) {
			var lugar = new lib.Place( { id: placeId, requestedLanguage: 'pt-BR' } );
			return lugar.fetchFields( { fields: [ 'rating', 'userRatingCount', 'reviews', 'googleMapsURI' ] } ).then( function () { mostrar( lugar ); } );
		} ).catch( function () { /* sem Google: a página fica como está, sem nota nem avaliações */ } );
	}

	if ( window.google && window.google.maps && window.google.maps.importLibrary ) {
		buscar();
		return;
	}
	window.rtmGooglePronto = buscar;
	var s = document.createElement( 'script' );
	s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent( chave ) + '&v=weekly&loading=async&language=pt-BR&callback=rtmGooglePronto';
	s.async = true;
	document.head.appendChild( s );
}() );
