<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Opções do plugin: padrões mesclados com o que foi salvo em `rtm_opcoes`. */
function rtm_opcoes() {
	$padrao = array(
		'url_cotacao' => home_url( '/cotacao-especialistas/' ),
		'ensaio'      => true,
		'google_chave' => '', // chave do Google Maps (restrita ao site); vai para o navegador
		'ci_ligado'    => true, // agentes e sugestões vêm do Cliente Ideal (sincronizar.php)
		'ci_api'       => '',   // endereço da API pública; vazio = RTM_CI_API_PADRAO
		'ci_cotacao'   => '',   // endereço do envio do pedido; vazio = RTM_CI_COTACAO_PADRAO
		'ci_segredo'   => '',   // segredo do webhook de sincronização (criado sozinho)
		'url_cadastro' => '',   // onde o agente se cadastra (Cliente Ideal); vazio = RTM_CADASTRO_PADRAO
		'ci_chave'     => '',   // chave secreta do envio (X-Rede-Chave): só no servidor, nunca no navegador
	);
	$salvo = get_option( 'rtm_opcoes', array() );
	return array_merge( $padrao, is_array( $salvo ) ? $salvo : array() );
}
