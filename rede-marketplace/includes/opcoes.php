<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Opções do plugin: padrões mesclados com o que foi salvo em `rtm_opcoes`. */
function rtm_opcoes() {
	$padrao = array(
		'url_cotacao' => home_url( '/cotacao-especialistas/' ),
		'ensaio'      => true,
	);
	$salvo = get_option( 'rtm_opcoes', array() );
	return array_merge( $padrao, is_array( $salvo ) ? $salvo : array() );
}
