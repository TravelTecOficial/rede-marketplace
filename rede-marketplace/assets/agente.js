/* Rede Marketplace — página do agente: janela do Cadastur (abre no selo verde ou no selo oficial; fecha no X, no fundo e no Esc). */
( function () {
	'use strict';
	var botoes = document.querySelectorAll( '.rtm-cad-abrir' );
	var janela = document.getElementById( 'rtm-cad' );
	var origem = null;
	if ( ! botoes.length || ! janela ) { return; }
	// Fora do container do Elementor, para o fundo escuro cobrir a página inteira.
	document.body.appendChild( janela );

	function abrir( e ) {
		origem = e.currentTarget;
		if ( typeof janela.showModal === 'function' ) { janela.showModal(); } else { janela.setAttribute( 'open', '' ); }
	}
	function fechar() {
		if ( typeof janela.close === 'function' ) { janela.close(); } else { janela.removeAttribute( 'open' ); }
		if ( origem ) { origem.focus(); }
	}
	Array.prototype.forEach.call( botoes, function ( b ) { b.addEventListener( 'click', abrir ); } );
	janela.querySelector( '.rtm-cad-fechar' ).addEventListener( 'click', fechar );
	janela.addEventListener( 'click', function ( e ) { if ( e.target === janela ) { fechar(); } } );
	janela.addEventListener( 'cancel', function ( e ) { e.preventDefault(); fechar(); } );
}() );
