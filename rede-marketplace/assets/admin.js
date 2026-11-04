/* Rede Marketplace — editor do agente: seletor de mídia do logo e repetidor de especialidades (até 4). */
( function ( $ ) {
	'use strict';

	// Imagens (logo da agência, certificado Cadastur): guarda o id do anexo. Um quadro de mídia por campo.
	$( document ).on( 'click', '.rtm-logo-escolher', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.rtm-logo' );
		if ( ! window.wp || ! wp.media ) { return; }
		var quadro = $box.data( 'rtmQuadro' );
		if ( ! quadro ) {
			quadro = wp.media( { title: $box.data( 'titulo' ) || 'Imagem', button: { text: $box.data( 'botao' ) || 'Usar esta imagem' }, library: { type: 'image' }, multiple: false } );
			$box.data( 'rtmQuadro', quadro );
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
		$bloco.find( '.rtm-esp-item' ).each( function ( i ) {
			$( this ).find( 'input' ).each( function () {
				this.name = this.name.replace( /\[especialidades\]\[\d+\]/, '[especialidades][' + i + ']' );
			} );
		} );
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
	// Sugestões: repetidores (o que inclui, dias do roteiro) — renumera rtm_s[<chave>][i].
	function atualizarRep( $rep ) {
		var chave = $rep.data( 'rtm-repetidor' );
		var re    = new RegExp( '\\[' + chave + '\\]\\[\\d+\\]' );
		$rep.find( '.rtm-rep-item' ).each( function ( i ) {
			$( this ).find( 'input' ).each( function () { this.name = this.name.replace( re, '[' + chave + '][' + i + ']' ); } );
		} );
		var max = parseInt( $rep.data( 'max' ), 10 ) || 10;
		var n   = $rep.find( '.rtm-rep-item' ).length;
		$rep.find( '.rtm-rep-adicionar' ).prop( 'disabled', n >= max );
		$rep.find( '.rtm-rep-remover' ).prop( 'hidden', n <= 1 );
	}
	$( document ).on( 'click', '.rtm-rep-adicionar', function ( e ) {
		e.preventDefault();
		var $rep   = $( this ).closest( '.rtm-repetidor' );
		var $lista = $rep.find( '.rtm-rep-lista' );
		if ( $lista.find( '.rtm-rep-item' ).length >= ( parseInt( $rep.data( 'max' ), 10 ) || 10 ) ) { return; }
		var $novo = $lista.find( '.rtm-rep-item' ).first().clone();
		$novo.find( 'input' ).val( '' );
		$lista.append( $novo );
		$novo.find( 'input' ).first().trigger( 'focus' );
		atualizarRep( $rep );
	} );
	$( document ).on( 'click', '.rtm-rep-remover', function ( e ) {
		e.preventDefault();
		var $rep  = $( this ).closest( '.rtm-repetidor' );
		var $item = $( this ).closest( '.rtm-rep-item' );
		if ( $rep.find( '.rtm-rep-item' ).length > 1 ) { $item.remove(); } else { $item.find( 'input' ).val( '' ); }
		atualizarRep( $rep );
	} );

	// Sugestões: galeria (várias fotos; guarda os ids separados por vírgula, no máximo 8).
	var quadroGaleria;
	$( document ).on( 'click', '.rtm-galeria-escolher', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.rtm-galeria-campo' );
		if ( ! window.wp || ! wp.media ) { return; }
		if ( ! quadroGaleria ) {
			quadroGaleria = wp.media( { title: 'Fotos da sugestão', button: { text: 'Usar estas fotos' }, library: { type: 'image' }, multiple: true } );
		}
		quadroGaleria.off( 'select' ).on( 'select', function () {
			var fotos = quadroGaleria.state().get( 'selection' ).toJSON().slice( 0, 8 );
			$box.find( 'input[type=hidden]' ).val( fotos.map( function ( a ) { return a.id; } ).join( ',' ) );
			var $prev = $box.find( '.rtm-galeria-previa' ).empty();
			fotos.forEach( function ( a ) {
				var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
				$prev.append( $( '<img>', { src: url, alt: '' } ).css( { width: '64px', height: '64px', objectFit: 'cover', margin: '0 6px 6px 0', borderRadius: '6px' } ) );
			} );
			$box.find( '.rtm-galeria-limpar' ).prop( 'hidden', ! fotos.length );
		} );
		quadroGaleria.open();
	} );
	$( document ).on( 'click', '.rtm-galeria-limpar', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.rtm-galeria-campo' );
		$box.find( 'input[type=hidden]' ).val( '' );
		$box.find( '.rtm-galeria-previa' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	$( function () {
		$( '.rtm-especialidades' ).each( function () { atualizar( $( this ) ); } );
		$( '.rtm-repetidor' ).each( function () { atualizarRep( $( this ) ); } );
	} );
} )( jQuery );
