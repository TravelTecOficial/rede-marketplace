/* Rede Marketplace — página /especialistas/: filtro por destino e tipo de viagem (no navegador). */
( function () {
	'use strict';
	var cards = Array.prototype.slice.call( document.querySelectorAll( '.rtm-lista-card' ) );
	if ( ! cards.length ) { return; }
	var sel   = document.getElementById( 'rtm-lista-destino' );
	var conta = document.getElementById( 'rtm-lista-conta' );
	var vazio = document.getElementById( 'rtm-lista-vazio' );
	var tipos = Array.prototype.slice.call( document.querySelectorAll( '#rtm-lista-tipos button.rtm-pilula' ) );
	var tipo  = '';

	function filtrar() {
		var dest = sel ? sel.value : '';
		var n = 0;
		cards.forEach( function ( c ) {
			var okDest = ! dest || ( c.getAttribute( 'data-destinos' ) || '' ).split( /\s+/ ).indexOf( dest ) !== -1;
			var okTipo = ! tipo || ( c.getAttribute( 'data-tipos' ) || '' ).split( '|' ).indexOf( tipo ) !== -1;
			c.hidden = ! ( okDest && okTipo );
			if ( ! c.hidden ) { n++; }
		} );
		if ( conta ) { conta.textContent = n + ( n === 1 ? ' especialista' : ' especialistas' ); }
		if ( vazio ) { vazio.hidden = n > 0; }
	}

	tipos.forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var ligado = b.getAttribute( 'aria-pressed' ) === 'true';
			tipos.forEach( function ( o ) { o.setAttribute( 'aria-pressed', 'false' ); } );
			tipo = ligado ? '' : b.getAttribute( 'data-tipo' );
			b.setAttribute( 'aria-pressed', ligado ? 'false' : 'true' );
			filtrar();
		} );
	} );
	if ( sel ) {
		sel.addEventListener( 'change', filtrar );
		var pedido = '';
		try { pedido = new URLSearchParams( window.location.search ).get( 'destino' ) || ''; } catch ( e ) { pedido = ''; }
		for ( var i = 0; i < sel.options.length; i++ ) {
			if ( sel.options[ i ].value && sel.options[ i ].value === pedido ) { sel.value = pedido; }
		}
	}
	filtrar();
}() );
