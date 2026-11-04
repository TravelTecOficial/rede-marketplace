/* Rede Marketplace — carrossel "Conheça nossos especialistas": passa sozinho a cada 4 s, para com o mouse,
 * toque ou foco, setas no computador e arrastar no celular (rolagem nativa). Sem movimento se o sistema pedir. */
( function () {
	'use strict';
	var raiz = document.querySelector( '.rtm-carrossel' );
	if ( ! raiz ) { return; }
	var trilho = raiz.querySelector( '.rtm-car-trilho' );
	var card   = trilho && trilho.querySelector( '.rtm-car-card' );
	if ( ! trilho || ! card ) { return; }
	var GAP    = 16;
	var parado = false;

	function passo() { return ( card.offsetWidth || 280 ) + GAP; }
	function avancar() {
		if ( trilho.scrollLeft + trilho.clientWidth >= trilho.scrollWidth - 4 ) {
			trilho.scrollTo( { left: 0, behavior: 'smooth' } );
		} else {
			trilho.scrollBy( { left: passo(), behavior: 'smooth' } );
		}
	}
	var ant  = raiz.querySelector( '.rtm-car-ant' );
	var prox = raiz.querySelector( '.rtm-car-prox' );
	if ( prox ) { prox.addEventListener( 'click', function () { trilho.scrollBy( { left: passo(), behavior: 'smooth' } ); } ); }
	if ( ant ) { ant.addEventListener( 'click', function () { trilho.scrollBy( { left: -passo(), behavior: 'smooth' } ); } ); }

	var reduzido = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( reduzido ) { return; }
	[ 'mouseenter', 'focusin', 'touchstart' ].forEach( function ( ev ) { raiz.addEventListener( ev, function () { parado = true; }, { passive: true } ); } );
	[ 'mouseleave', 'focusout' ].forEach( function ( ev ) { raiz.addEventListener( ev, function () { parado = false; } ); } );
	window.setInterval( function () {
		if ( ! parado && ! document.hidden ) { avancar(); }
	}, 4000 );
}() );
