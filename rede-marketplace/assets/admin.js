/* Rede Marketplace — editor do agente: seletor de mídia do logo e repetidor de especialidades (até 4). */
( function ( $ ) {
	'use strict';

	// Logo da agência (guarda o id do anexo).
	var quadro;
	$( document ).on( 'click', '.rtm-logo-escolher', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.rtm-logo' );
		if ( ! window.wp || ! wp.media ) { return; }
		if ( ! quadro ) {
			quadro = wp.media( { title: 'Logo da agência', button: { text: 'Usar este logo' }, library: { type: 'image' }, multiple: false } );
		}
		quadro.off( 'select' ).on( 'select', function () {
			var a   = quadro.state().get( 'selection' ).first().toJSON();
			var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
			$box.find( 'input[type=hidden]' ).val( a.id );
			$box.find( '.rtm-logo-previa' ).empty().append( $( '<img>', { src: url, alt: '' } ).css( { maxWidth: '120px', maxHeight: '80px' } ) );
			$box.find( '.rtm-logo-remover' ).prop( 'hidden', false );
		} );
		quadro.open();
	} );
	$( document ).on( 'click', '.rtm-logo-remover', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.rtm-logo' );
		$box.find( 'input[type=hidden]' ).val( '' );
		$box.find( '.rtm-logo-previa' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	// Especialidades: no máximo 4 linhas.
	function atualizar( $bloco ) {
		var max = parseInt( $bloco.data( 'max' ), 10 ) || 4;
		var n   = $bloco.find( '.rtm-esp-item' ).length;
		$bloco.find( '.rtm-esp-adicionar' ).prop( 'disabled', n >= max );
		$bloco.find( '.rtm-esp-remover' ).prop( 'hidden', n <= 1 );
	}
	$( document ).on( 'click', '.rtm-esp-adicionar', function ( e ) {
		e.preventDefault();
		var $bloco = $( this ).closest( '.rtm-especialidades' );
		var max    = parseInt( $bloco.data( 'max' ), 10 ) || 4;
		var $lista = $bloco.find( '.rtm-esp-lista' );
		if ( $lista.find( '.rtm-esp-item' ).length >= max ) { return; }
		var $novo = $lista.find( '.rtm-esp-item' ).first().clone();
		$novo.find( 'input' ).val( '' );
		$lista.append( $novo );
		$novo.find( 'input' ).trigger( 'focus' );
		atualizar( $bloco );
	} );
	$( document ).on( 'click', '.rtm-esp-remover', function ( e ) {
		e.preventDefault();
		var $bloco = $( this ).closest( '.rtm-especialidades' );
		var $item  = $( this ).closest( '.rtm-esp-item' );
		if ( $bloco.find( '.rtm-esp-item' ).length > 1 ) { $item.remove(); } else { $item.find( 'input' ).val( '' ); }
		atualizar( $bloco );
	} );
	$( function () {
		$( '.rtm-especialidades' ).each( function () { atualizar( $( this ) ); } );
	} );
} )( jQuery );
