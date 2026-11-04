/* Rede Marketplace — página da sugestão: visualizador de fotos (abre na foto clicada; setas, Esc e X). */
( function () {
	'use strict';
	var galeria = document.querySelector( '.rtm-galeria' );
	var janela  = document.querySelector( '.rtm-galeria-dialogo' );
	if ( ! galeria || ! janela ) { return; }
	var fotos = [];
	try { fotos = JSON.parse( galeria.getAttribute( 'data-lista' ) || '[]' ); } catch ( e ) { fotos = []; }
	if ( ! fotos.length ) { return; }
	document.body.appendChild( janela ); // fundo escuro sobre a página inteira

	var grande = janela.querySelector( '.rtm-galeria-grande' );
	var conta  = janela.querySelector( '.rtm-galeria-conta' );
	var ant    = janela.querySelector( '.rtm-galeria-ant' );
	var prox   = janela.querySelector( '.rtm-galeria-prox' );
	var atual  = 0;
	var origem = null;
	ant.hidden  = fotos.length < 2;
	prox.hidden = fotos.length < 2;

	function mostrar( i ) {
		atual = ( i + fotos.length ) % fotos.length;
		grande.src = fotos[ atual ];
		conta.textContent = ( atual + 1 ) + ' de ' + fotos.length;
	}
	function abrir( i, botao ) {
		origem = botao;
		mostrar( i );
		if ( typeof janela.showModal === 'function' ) { janela.showModal(); } else { janela.setAttribute( 'open', '' ); }
	}
	function fechar() {
		if ( typeof janela.close === 'function' ) { janela.close(); } else { janela.removeAttribute( 'open' ); }
		if ( origem ) { origem.focus(); }
	}
	Array.prototype.forEach.call( galeria.querySelectorAll( '.rtm-galeria-foto' ), function ( b ) {
		b.addEventListener( 'click', function () { abrir( parseInt( b.getAttribute( 'data-i' ), 10 ) || 0, b ); } );
	} );
	ant.addEventListener( 'click', function () { mostrar( atual - 1 ); } );
	prox.addEventListener( 'click', function () { mostrar( atual + 1 ); } );
	janela.querySelector( '.rtm-galeria-fechar' ).addEventListener( 'click', fechar );
	janela.addEventListener( 'click', function ( e ) { if ( e.target === janela ) { fechar(); } } );
	janela.addEventListener( 'cancel', function ( e ) { e.preventDefault(); fechar(); } );
	janela.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'ArrowLeft' && fotos.length > 1 ) { mostrar( atual - 1 ); }
		if ( e.key === 'ArrowRight' && fotos.length > 1 ) { mostrar( atual + 1 ); }
	} );
}() );
