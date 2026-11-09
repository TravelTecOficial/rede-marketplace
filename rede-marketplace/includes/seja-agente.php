<?php
/**
 * [rt_seja_agente]: página pública "Seja um agente" (explica a Rede, exige Cadastur e leva ao cadastro no Cliente Ideal).
 * A página /seja-um-agente/ é criada uma vez, sozinha, com o shortcode; depois o dono pode editar à vontade.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RTM_CADASTRO_PADRAO', 'https://clienteideal.online/' );

/** Endereço do cadastro do agente (configurável; vazio = padrão). */
function rtm_url_cadastro() {
	$u = trim( (string) rtm_opcoes()['url_cadastro'] );
	return '' !== $u ? $u : RTM_CADASTRO_PADRAO;
}

function rtm_url_seja_agente() {
	return home_url( '/seja-um-agente/' );
}

function rtm_seja_agente_html() {
	rtm_enfileirar_estilo();
	$passos = array(
		'Cadastre sua agência no Cliente Ideal e escolha a Rede Turística em Integrações.',
		'Informe o seu Cadastur (obrigatório) e monte o seu perfil: foto, destinos e especialidades.',
		'A Rede confere o Cadastur e aprova o perfil. Em seguida você aparece no site e recebe pedidos de cotação.',
	);
	$h  = '<section class="rtm-lista rtm-seja"><div class="rtm-lista-in">';
	$h .= '<h1 class="rtm-lista-titulo">Seja um agente da Rede Turística</h1>';
	$h .= '<p class="rtm-lista-sub">Tenha o seu perfil ao lado de agentes certificados e receba pedidos de cotação de viajantes que já escolheram um especialista.</p>';
	$h .= '<div class="rtm-seja-req"><span class="rtm-cadastur"><i aria-hidden="true">✓</i>Cadastur obrigatório</span>'
		. '<p>Só entram agentes com Cadastur ativo no Ministério do Turismo. Conferimos o seu cadastro e mostramos o selo no seu perfil: é a prova de credibilidade para o viajante.</p></div>';
	$h .= '<ol class="rtm-seja-passos">';
	foreach ( $passos as $p ) {
		$h .= '<li>' . esc_html( $p ) . '</li>';
	}
	$h .= '</ol>';
	$h .= '<p><a class="rtm-lista-cot rtm-seja-cta" href="' . esc_url( rtm_url_cadastro() ) . '">Quero ser um agente</a></p>';
	return $h . '</div></section>';
}
add_shortcode( 'rt_seja_agente', 'rtm_seja_agente_html' );

/** Cria a página uma única vez (a opção guarda o id; se o dono apagar, não recria). */
function rtm_instalar_pagina_seja_agente() {
	if ( get_option( 'rtm_pagina_seja_agente', 0 ) ) {
		return;
	}
	$existe = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'name' => 'seja-um-agente', 'posts_per_page' => 1 ) );
	if ( $existe ) {
		update_option( 'rtm_pagina_seja_agente', (int) $existe[0]->ID, false );
		return;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Seja um agente',
		'post_name'    => 'seja-um-agente',
		'post_content' => '[rt_seja_agente]',
	), true );
	update_option( 'rtm_pagina_seja_agente', is_wp_error( $id ) ? 0 : (int) $id, false );
}
add_action( 'init', 'rtm_instalar_pagina_seja_agente', 20 );
