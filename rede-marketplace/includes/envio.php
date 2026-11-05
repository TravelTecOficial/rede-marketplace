<?php
/**
 * Envio do pedido de cotação para o Cliente Ideal (módulo Rede Turística).
 * O navegador manda para o próprio site (POST /wp-json/rede-marketplace/v1/cotacao); o servidor do WordPress
 * repassa para a função rede-cotacao do Cliente Ideal com a chave secreta (X-Rede-Chave), que nunca vai ao navegador.
 * O Cliente Ideal grava o pedido e cria o lead no CRM de cada agência escolhida (1 a 3).
 * No modo ensaio só quem edita o site envia de verdade.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RTM_CI_COTACAO_PADRAO', 'https://mrkvvgofjyvlutqpvedt.supabase.co/functions/v1/rede-cotacao' );

function rtm_envio_url_ci() {
	$o = rtm_opcoes();
	return '' !== (string) $o['ci_cotacao'] ? (string) $o['ci_cotacao'] : RTM_CI_COTACAO_PADRAO;
}

/** Este visitante pode enviar de verdade? (chave guardada; fora do ensaio, ou quem edita o site) */
function rtm_envio_ativo() {
	$o = rtm_opcoes();
	return '' !== (string) $o['ci_chave'] && ( ! rtm_ensaio() || current_user_can( 'edit_posts' ) );
}

function rtm_envio_rota() {
	return rest_url( 'rede-marketplace/v1/cotacao' );
}

/** Texto simples de um campo do pedido, cortado em $max caracteres. */
function rtm_envio_txt( array $p, $k, $max = 200 ) {
	$v = isset( $p[ $k ] ) && is_scalar( $p[ $k ] ) ? sanitize_text_field( (string) $p[ $k ] ) : '';
	return mb_substr( $v, 0, $max );
}

/**
 * Agentes do pedido: slugs escolhidos (1 a 3, ativos e vindos do Cliente Ideal) => ids do Cliente Ideal.
 * "A Rede escolhe" (sem agente marcado): até 3 que atendem o destino; sem nenhum, os 3 primeiros.
 */
function rtm_envio_agentes( array $slugs, $rede, $destino_slug ) {
	$ativos = array();
	foreach ( rtm_agentes_ativos() as $a ) {
		$ci = (string) get_post_meta( $a['id'], 'rtm_ci_id', true );
		if ( '' !== $ci ) {
			$ativos[ $a['slug'] ] = array( 'ci' => $ci, 'nome' => $a['nome'], 'destinos' => $a['destinos_slugs'] );
		}
	}
	$escolhidos = array();
	foreach ( array_slice( array_unique( array_map( 'sanitize_title', $slugs ) ), 0, 3 ) as $s ) {
		if ( isset( $ativos[ $s ] ) ) {
			$escolhidos[ $s ] = $ativos[ $s ];
		}
	}
	if ( ! $escolhidos && $rede ) {
		foreach ( $ativos as $s => $a ) {
			if ( '' !== $destino_slug && in_array( $destino_slug, $a['destinos'], true ) ) {
				$escolhidos[ $s ] = $a;
			}
		}
		if ( ! $escolhidos ) {
			$escolhidos = $ativos;
		}
		$escolhidos = array_slice( $escolhidos, 0, 3, true );
	}
	return $escolhidos;
}

/** Monta o corpo do Cliente Ideal a partir do que o formulário mandou. Devolve [corpo, agentes] ou WP_Error. */
function rtm_envio_corpo( array $p ) {
	if ( '' !== rtm_envio_txt( $p, 'site' ) ) { // campo isca: robô preenche, pessoa não vê
		return new WP_Error( 'robo', 'Pedido recusado.' );
	}
	$nome  = rtm_envio_txt( $p, 'nome', 120 );
	$email = sanitize_email( rtm_envio_txt( $p, 'email', 160 ) );
	$tel   = preg_replace( '/\D+/', '', rtm_envio_txt( $p, 'telefone', 30 ) );
	if ( '' === $nome ) {
		return new WP_Error( 'nome', 'Informe o seu nome.' );
	}
	if ( ! is_email( $email ) && ( strlen( $tel ) < 10 || strlen( $tel ) > 13 ) ) {
		return new WP_Error( 'contato', 'Informe um WhatsApp ou e-mail válido.' );
	}
	if ( empty( $p['lgpd'] ) ) {
		return new WP_Error( 'lgpd', 'É preciso autorizar o envio dos dados aos especialistas.' );
	}
	$slugs   = isset( $p['agentes'] ) && is_array( $p['agentes'] ) ? array_filter( $p['agentes'], 'is_scalar' ) : array();
	$agentes = rtm_envio_agentes( $slugs, ! empty( $p['rede'] ), sanitize_title( rtm_envio_txt( $p, 'destino_slug' ) ) );
	if ( ! $agentes ) {
		return new WP_Error( 'agentes', 'Escolha de 1 a 3 especialistas.' );
	}
	$utm = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid' ) as $k ) {
		$v = isset( $p['utm'][ $k ] ) && is_scalar( $p['utm'][ $k ] ) ? mb_substr( sanitize_text_field( (string) $p['utm'][ $k ] ), 0, 200 ) : '';
		if ( '' !== $v ) {
			$utm[ $k ] = $v;
		}
	}
	$origem = isset( $p['origem_url'] ) && is_scalar( $p['origem_url'] ) ? esc_url_raw( (string) $p['origem_url'] ) : '';
	if ( 0 !== strpos( $origem, home_url( '/' ) ) ) {
		$origem = home_url( '/' );
	}
	$msg = array();
	foreach ( array( 'tipo' => 'Tipo de viagem', 'saida' => 'Saída', 'inclui' => 'Precisa na cotação', 'horario' => 'Melhor horário', 'mensagem' => 'Observações' ) as $k => $rot ) {
		$v = 'mensagem' === $k && isset( $p[ $k ] ) && is_scalar( $p[ $k ] ) ? mb_substr( sanitize_textarea_field( (string) $p[ $k ] ), 0, 2000 ) : rtm_envio_txt( $p, $k, 300 );
		if ( '' !== $v ) {
			$msg[] = $rot . ': ' . $v;
		}
	}
	if ( ! empty( $p['rede'] ) ) {
		$msg[] = 'O viajante pediu que a Rede Turística escolhesse os especialistas.';
	}
	$corpo = array(
		'agentes'    => array_values( wp_list_pluck( $agentes, 'ci' ) ),
		'nome'       => $nome,
		'email'      => is_email( $email ) ? $email : '',
		'telefone'   => $tel,
		'destino'    => rtm_envio_txt( $p, 'destino', 120 ),
		'datas'      => rtm_envio_txt( $p, 'datas', 120 ),
		'pessoas'    => rtm_envio_txt( $p, 'pessoas', 120 ),
		'orcamento'  => rtm_envio_txt( $p, 'orcamento', 80 ),
		'mensagem'   => implode( "\n", $msg ),
		'origem_url' => $origem,
		'sugestao'   => sanitize_title( rtm_envio_txt( $p, 'sugestao', 200 ) ),
		'utm'        => (object) $utm,
	);
	return array( $corpo, $agentes );
}

/** IP do visitante (o Cloudflare manda o original em CF-Connecting-IP). */
function rtm_envio_ip() {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR' ) as $k ) {
		if ( ! empty( $_SERVER[ $k ] ) && filter_var( wp_unslash( $_SERVER[ $k ] ), FILTER_VALIDATE_IP ) ) {
			return (string) wp_unslash( $_SERVER[ $k ] );
		}
	}
	return '';
}

/** POST /wp-json/rede-marketplace/v1/cotacao */
function rtm_envio_receber( $req ) {
	if ( ! rtm_envio_ativo() ) {
		return new WP_REST_Response( array( 'ok' => false, 'msg' => 'Envio ainda não liberado (modo ensaio).' ), 403 );
	}
	$trava = 'rtm_envio_' . md5( rtm_envio_ip() );
	$n     = (int) get_transient( $trava );
	if ( $n >= 5 ) {
		return new WP_REST_Response( array( 'ok' => false, 'msg' => 'Muitos pedidos seguidos. Tente de novo em alguns minutos.' ), 429 );
	}
	set_transient( $trava, $n + 1, 10 * MINUTE_IN_SECONDS );

	$p = $req->get_json_params();
	$r = rtm_envio_corpo( is_array( $p ) ? $p : array() );
	if ( is_wp_error( $r ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'msg' => $r->get_error_message() ), 400 );
	}
	list( $corpo, $agentes ) = $r;
	$resp   = wp_remote_post( rtm_envio_url_ci(), array(
		'timeout' => 20,
		'headers' => array( 'Content-Type' => 'application/json', 'X-Rede-Chave' => (string) rtm_opcoes()['ci_chave'] ),
		'body'    => wp_json_encode( $corpo ),
	) );
	$codigo = is_wp_error( $resp ) ? 0 : (int) wp_remote_retrieve_response_code( $resp );
	$dados  = $codigo ? json_decode( (string) wp_remote_retrieve_body( $resp ), true ) : null;
	update_option( 'rtm_envio_ultimo', array( 'quando' => time(), 'codigo' => $codigo, 'erro' => is_array( $dados ) && isset( $dados['error'] ) ? (string) $dados['error'] : ( is_wp_error( $resp ) ? $resp->get_error_message() : '' ) ), false );
	if ( 201 !== $codigo && 200 !== $codigo ) {
		$msg = 404 === $codigo ? 'Um dos especialistas escolhidos não está mais disponível. Recarregue a página e escolha de novo.' : 'Não conseguimos enviar agora. Tente de novo em alguns minutos ou fale com a gente pelo WhatsApp.';
		return new WP_REST_Response( array( 'ok' => false, 'msg' => $msg ), 404 === $codigo ? 409 : 502 );
	}
	return new WP_REST_Response( array(
		'ok'      => true,
		'pedido'  => is_array( $dados ) && isset( $dados['pedido_id'] ) ? (string) $dados['pedido_id'] : '',
		'agentes' => array_values( wp_list_pluck( $agentes, 'nome' ) ),
	), 200 );
}

function rtm_envio_rotas() {
	register_rest_route( 'rede-marketplace/v1', '/cotacao', array(
		'methods'             => 'POST',
		'callback'            => 'rtm_envio_receber',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'rtm_envio_rotas' );

/** Atributos do formulário que ligam o envio (vazio quando este visitante não envia de verdade). */
function rtm_envio_atributos() {
	if ( ! rtm_envio_ativo() ) {
		return '';
	}
	$h = ' data-envio="' . esc_url( rtm_envio_rota() ) . '"';
	if ( is_user_logged_in() ) { // página de quem está logado não vai para o cache
		$h .= ' data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '"';
	}
	return $h;
}
