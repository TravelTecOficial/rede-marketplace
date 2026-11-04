/* Rede Marketplace — página do agente: janela do Cadastur (abre no selo, fecha no X, no fundo e no Esc). */
( function () {
	'use strict';
	var botao  = document.querySelector( '.rtm-cad-abrir' );
	var janela = document.getElementById( 'rtm-cad' );
	if ( ! botao || ! janela ) { return; }
	// Fora do container do Elementor, para o fundo escuro cobrir a página inteira.
	document.body.appendChild( janela );

	function abrir() {
		if ( typeof janela.showModal === 'function' ) { janela.showModal(); } else { janela.setAttribute( 'open', '' ); }
	}
	function fechar() {
		if ( typeof janela.close === 'function' ) { janela.close(); } else { janela.removeAttribute( 'open' ); }
		botao.focus();
	}
	botao.addEventListener( 'click', abrir );
	janela.querySelector( '.rtm-cad-fechar' ).addEventListener( 'click', fechar );
	janela.addEventListener( 'click', function ( e ) { if ( e.target === janela ) { fechar(); } } );
	janela.addEventListener( 'cancel', function ( e ) { e.preventDefault(); fechar(); } );
}() );
