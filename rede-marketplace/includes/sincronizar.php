<?php
/**
 * Agentes e sugestões vindos do Cliente Ideal (módulo Rede Turística).
 * Quando o Cliente Ideal avisa (webhook POST /wp-json/rede-marketplace/v1/sincronizar), no botão "Sincronizar agora" e,
 * como reserva, de hora em hora, lê a API pública do Cliente Ideal e copia para os tipos
 * rt_agente e rt_sugestao do WordPress. As telas do site continuam lendo do WordPress (rápido, com cache).
 * Agente ou sugestão que saiu da API vai para rascunho (nunca é apagado).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RTM_CI_API_PADRAO', 'https://mrkvvgofjyvlutqpvedt.supabase.co/functions/v1/rede-publica' );

function rtm_ci_ligado() {
	$o = rtm_opcoes();
	return ! empty( $o['ci_ligado'] );
}

function rtm_ci_url() {
	$o   = rtm_opcoes();
	$url = isset( $o['ci_api'] ) ? trim( (string) $o['ci_api'] ) : '';
	return untrailingslashit( '' !== $url ? $url : RTM_CI_API_PADRAO );
}

/** Texto simples de um campo da API ('' se faltar ou não for texto/número). */
function rtm_ci_txt( array $a, $k ) {
	return isset( $a[ $k ] ) && is_scalar( $a[ $k ] ) ? sanitize_text_field( (string) $a[ $k ] ) : '';
}

/** Texto com parágrafos (apresentação). */
function rtm_ci_texto_longo( array $a, $k ) {
	return isset( $a[ $k ] ) && is_scalar( $a[ $k ] ) ? sanitize_textarea_field( (string) $a[ $k ] ) : '';
}

/** Número inteiro positivo, ou '' (campo vazio). */
function rtm_ci_num( array $a, $k ) {
	$n = isset( $a[ $k ] ) && is_numeric( $a[ $k ] ) ? (int) $a[ $k ] : 0;
	return $n > 0 ? $n : '';
}

/** URL http(s) de imagem, ou ''. */
function rtm_ci_img( $v ) {
	$v = is_scalar( $v ) ? trim( (string) $v ) : '';
	return preg_match( '#^https?://#i', $v ) ? esc_url_raw( $v ) : '';
}

/** Lista de textos simples. */
function rtm_ci_lista( array $a, $k ) {
	$res = array();
	foreach ( isset( $a[ $k ] ) && is_array( $a[ $k ] ) ? $a[ $k ] : array() as $v ) {
		$t = is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';
		if ( '' !== $t ) {
			$res[] = $t;
		}
	}
	return $res;
}

/** Primeiro post do tipo que bate com o filtro (qualquer status, menos lixeira), ou 0. */
function rtm_ci_achar( $tipo, array $filtro ) {
	$p = get_posts( array_merge( array( 'post_type' => $tipo, 'post_status' => 'any', 'posts_per_page' => 1 ), $filtro ) );
	return $p ? (int) $p[0]->ID : 0;
}

/** Cria ou atualiza o post (publicado) e devolve o id, ou 0. */
function rtm_ci_gravar_post( $id, $tipo, $titulo, $slug ) {
	$dados = array( 'post_type' => $tipo, 'post_status' => 'publish', 'post_title' => $titulo, 'post_name' => $slug );
	if ( $id ) {
		$dados['ID'] = $id;
		$r           = wp_update_post( $dados, true );
	} else {
		$r = wp_insert_post( $dados, true );
	}
	return is_wp_error( $r ) ? 0 : (int) $r;
}

/** Copia um agente da API para o WordPress. Devolve [id do agente, ids das sugestões]. */
function rtm_ci_salvar_agente( array $a ) {
	$ci_id = rtm_ci_txt( $a, 'id' );
	$slug  = sanitize_title( rtm_ci_txt( $a, 'slug' ) );
	$nome  = rtm_ci_txt( $a, 'nome' );
	if ( '' === $ci_id || '' === $slug || '' === $nome ) {
		return array( 0, array() );
	}
	$id = rtm_ci_achar( 'rt_agente', array( 'meta_key' => 'rtm_ci_id', 'meta_value' => $ci_id ) );
	if ( ! $id ) {
		$id = rtm_ci_achar( 'rt_agente', array( 'name' => $slug ) ); // agente que já existia aqui (os de teste): assume
	}
	$sem_sug = $a;
	unset( $sem_sug['sugestoes'] );
	$hash = md5( (string) wp_json_encode( $sem_sug ) );

	if ( ! $id || $hash !== (string) get_post_meta( $id, 'rtm_ci_hash', true ) || 'publish' !== get_post_status( $id ) ) {
		$id = rtm_ci_gravar_post( $id, 'rt_agente', $nome, $slug );
		if ( ! $id ) {
			return array( 0, array() );
		}
		$destinos = array();
		foreach ( isset( $a['destinos'] ) && is_array( $a['destinos'] ) ? $a['destinos'] : array() as $d ) {
			$local = is_array( $d ) && ! empty( $d['slug'] ) ? rtm_teste_destino( sanitize_title( (string) $d['slug'] ) ) : null;
			if ( $local ) {
				$destinos[] = $local;
			}
		}
		$principal = sanitize_title( rtm_ci_txt( $a, 'destino_principal' ) );
		$conferido = (string) get_post_meta( $id, 'rtm_cadastur_conferido_em', true );
		$campos    = array(
			'cargo'                 => rtm_ci_txt( $a, 'cargo' ),
			'texto_card'            => rtm_ci_txt( $a, 'texto_card' ),
			'apresentacao'          => rtm_ci_texto_longo( $a, 'apresentacao' ),
			'cadastur'              => rtm_ci_txt( $a, 'cadastur' ),
			'cadastur_validade'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', rtm_ci_txt( $a, 'cadastur_validade' ) ) ? rtm_ci_txt( $a, 'cadastur_validade' ) : '',
			'cadastur_conferido_em' => ! empty( $a['cadastur_verificado'] ) ? ( '' !== $conferido ? $conferido : gmdate( 'Y-m-d' ) ) : '',
			'anos_experiencia'      => rtm_ci_num( $a, 'anos_experiencia' ),
			'viajantes_atendidos'   => rtm_ci_num( $a, 'viajantes_atendidos' ),
			'idiomas'               => rtm_ci_lista( $a, 'idiomas' ),
			'especialidades'        => rtm_especialidades_normalizar( isset( $a['especialidades'] ) ? $a['especialidades'] : array() ),
			'destinos'              => $destinos,
			'destino_principal'     => $principal && rtm_teste_destino( $principal ) ? $principal : ( $destinos ? $destinos[0]['slug'] : '' ),
			'agencia_nome'          => rtm_ci_txt( $a, 'agencia_nome' ),
			'agencia_cidade'        => rtm_ci_txt( $a, 'agencia_cidade' ),
			'agencia_desde'         => rtm_ci_num( $a, 'agencia_desde' ),
			'endereco'              => rtm_ci_txt( $a, 'endereco' ),
			'horario'               => rtm_ci_txt( $a, 'horario' ),
			'google_perfil'         => rtm_ci_img( isset( $a['google_perfil'] ) ? $a['google_perfil'] : '' ),
			'google_place_id'       => preg_replace( '/[^A-Za-z0-9_\-]/', '', rtm_ci_txt( $a, 'google_place_id' ) ),
			'foto_url'              => rtm_ci_img( isset( $a['foto'] ) ? $a['foto'] : '' ),
			'logo_url'              => rtm_ci_img( isset( $a['logo'] ) ? $a['logo'] : '' ),
			'certificado_url'       => rtm_ci_img( isset( $a['certificado'] ) ? $a['certificado'] : '' ),
			'ativo'                 => '1',
			'ci_id'                 => $ci_id,
			'ci_hash'               => $hash,
		);
		foreach ( array( 'instagram', 'facebook', 'tiktok', 'youtube', 'linkedin', 'site' ) as $rede ) {
			$campos[ $rede ] = rtm_rede_url( $rede, rtm_ci_txt( $a, $rede ) );
		}
		foreach ( $campos as $k => $v ) {
			update_post_meta( $id, 'rtm_' . $k, $v );
		}
		wp_set_object_terms( $id, rtm_ci_lista( $a, 'tipos' ), 'rt_tipo_viagem' );
		rtm_purgar_cache( $id ); // o save_post rodou antes dos metas
	}

	$sugs = array();
	foreach ( isset( $a['sugestoes'] ) && is_array( $a['sugestoes'] ) ? $a['sugestoes'] : array() as $i => $s ) {
		$sid = is_array( $s ) ? rtm_ci_salvar_sugestao( $s, $id, $i + 1 ) : 0;
		if ( $sid ) {
			$sugs[] = $sid;
		}
	}
	return array( $id, $sugs );
}

/** Copia uma sugestão da API (do agente $agente_id). Devolve o id, ou 0. */
function rtm_ci_salvar_sugestao( array $s, $agente_id, $ordem_padrao ) {
	$ci_id  = rtm_ci_txt( $s, 'id' );
	$slug   = sanitize_title( rtm_ci_txt( $s, 'slug' ) );
	$titulo = rtm_ci_txt( $s, 'titulo' );
	if ( '' === $ci_id || '' === $slug || '' === $titulo ) {
		return 0;
	}
	$id = rtm_ci_achar( 'rt_sugestao', array( 'meta_key' => 'rtm_ci_id', 'meta_value' => $ci_id ) );
	if ( ! $id ) {
		foreach ( get_posts( array( 'post_type' => 'rt_sugestao', 'post_status' => 'any', 'name' => $slug, 'posts_per_page' => -1 ) ) as $p ) {
			if ( (int) get_post_meta( $p->ID, 'rtm_agente', true ) === (int) $agente_id ) {
				$id = (int) $p->ID;
				break;
			}
		}
	}
	$hash = md5( (string) wp_json_encode( $s ) . '|' . (int) $agente_id );
	if ( $id && $hash === (string) get_post_meta( $id, 'rtm_ci_hash', true ) && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	$id = rtm_ci_gravar_post( $id, 'rt_sugestao', $titulo, $slug );
	if ( ! $id ) {
		return 0;
	}
	$dias = array();
	foreach ( isset( $s['dias'] ) && is_array( $s['dias'] ) ? $s['dias'] : array() as $d ) {
		if ( ! is_array( $d ) ) {
			continue;
		}
		$dt = rtm_ci_txt( $d, 'titulo' );
		$dd = '' !== rtm_ci_txt( $d, 'texto' ) ? rtm_ci_txt( $d, 'texto' ) : rtm_ci_txt( $d, 'descricao' );
		if ( '' !== $dt || '' !== $dd ) {
			$dias[] = array( 'titulo' => $dt, 'descricao' => $dd );
		}
	}
	$fotos = array();
	foreach ( isset( $s['galeria'] ) && is_array( $s['galeria'] ) ? $s['galeria'] : array() as $u ) {
		$u = rtm_ci_img( $u );
		if ( $u ) {
			$fotos[] = $u;
		}
	}
	$destino = sanitize_title( rtm_ci_txt( $s, 'destino' ) );
	$ordem   = rtm_ci_num( $s, 'ordem' );
	$campos  = array(
		'agente'         => (int) $agente_id,
		'destino'        => $destino && rtm_teste_destino( $destino ) ? $destino : '',
		'destinos_texto' => rtm_ci_txt( $s, 'destinos_texto' ),
		'tipo'           => rtm_ci_txt( $s, 'tipo' ),
		'texto_card'     => rtm_ci_txt( $s, 'texto_card' ),
		'resumo'         => rtm_ci_txt( $s, 'resumo' ),
		'duracao'        => rtm_ci_txt( $s, 'duracao' ),
		'ideal_para'     => rtm_ci_txt( $s, 'ideal_para' ),
		'melhor_epoca'   => rtm_ci_txt( $s, 'melhor_epoca' ),
		'saida'          => rtm_ci_txt( $s, 'saida' ),
		'inclui'         => array_slice( rtm_ci_lista( $s, 'inclui' ), 0, 12 ),
		'dias'           => array_slice( $dias, 0, 10 ),
		'preco'          => rtm_ci_num( $s, 'preco' ),
		'ordem'          => '' !== $ordem ? $ordem : (int) $ordem_padrao,
		'galeria_urls'   => array_slice( $fotos, 0, 8 ),
		'ci_id'          => $ci_id,
		'ci_hash'        => $hash,
	);
	foreach ( $campos as $k => $v ) {
		update_post_meta( $id, 'rtm_' . $k, $v );
	}
	rtm_purgar_sugestao( $id );
	return $id;
}

/** Tira do ar (rascunho) os posts publicados do tipo que não estão em $manter. Devolve quantos. */
function rtm_ci_despublicar_resto( $tipo, array $manter ) {
	$n = 0;
	foreach ( get_posts( array( 'post_type' => $tipo, 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $p ) {
		if ( in_array( (int) $p->ID, $manter, true ) ) {
			continue;
		}
		wp_update_post( array( 'ID' => (int) $p->ID, 'post_status' => 'draft' ) );
		$n++;
	}
	return $n;
}

/** Lê a API e copia tudo. Devolve o resumo gravado em `rtm_ci_status`. */
function rtm_ci_sincronizar() {
	if ( ! rtm_ci_ligado() ) {
		return null;
	}
	$status   = array( 'quando' => time(), 'ok' => false, 'msg' => '', 'agentes' => 0, 'sugestoes' => 0, 'tirados' => 0 );
	$resposta = wp_remote_get( rtm_ci_url() . '/agentes', array( 'timeout' => 15, 'headers' => array( 'Accept' => 'application/json' ) ) );
	$codigo   = is_wp_error( $resposta ) ? 0 : (int) wp_remote_retrieve_response_code( $resposta );
	$lista    = 200 === $codigo ? json_decode( (string) wp_remote_retrieve_body( $resposta ), true ) : null;
	if ( ! is_array( $lista ) || ( $lista && array_values( $lista ) !== $lista ) ) {
		$status['msg'] = is_wp_error( $resposta ) ? $resposta->get_error_message() : ( 200 === $codigo ? 'resposta inválida' : 'HTTP ' . $codigo );
		update_option( 'rtm_ci_status', $status, false );
		return $status;
	}
	$agentes = array();
	$sugs    = array();
	foreach ( $lista as $a ) {
		if ( ! is_array( $a ) ) {
			continue;
		}
		list( $id, $ids_sug ) = rtm_ci_salvar_agente( $a );
		if ( $id ) {
			$agentes[] = $id;
			$sugs      = array_merge( $sugs, $ids_sug );
		}
	}
	if ( $agentes ) {
		$status['tirados'] = rtm_ci_despublicar_resto( 'rt_agente', $agentes ) + rtm_ci_despublicar_resto( 'rt_sugestao', $sugs );
	} else {
		$status['msg'] = 'a API não devolveu nenhum agente; nada foi tirado do ar';
	}
	$status['ok']        = true;
	$status['agentes']   = count( $agentes );
	$status['sugestoes'] = count( $sugs );
	update_option( 'rtm_ci_status', $status, false );
	return $status;
}

/** Liga ou desliga a rodada de reserva (de hora em hora) conforme a opção; troca a agenda antiga de 10 min. */
function rtm_ci_agendar() {
	$agendado = wp_next_scheduled( 'rtm_ci_sincronizar_evento' );
	if ( $agendado && 'hourly' !== wp_get_schedule( 'rtm_ci_sincronizar_evento' ) ) {
		wp_clear_scheduled_hook( 'rtm_ci_sincronizar_evento' );
		$agendado = false;
	}
	if ( rtm_ci_ligado() && ! $agendado ) {
		wp_schedule_event( time() + 300, 'hourly', 'rtm_ci_sincronizar_evento' );
	} elseif ( ! rtm_ci_ligado() && $agendado ) {
		wp_clear_scheduled_hook( 'rtm_ci_sincronizar_evento' );
	}
}

/**
 * Webhook do Cliente Ideal: POST /wp-json/rede-marketplace/v1/sincronizar (corpo ignorado).
 * Sem senha: só manda o site reler a API pública. Uma rodada a cada 20 s; aviso que chega no meio
 * agenda mais uma rodada logo depois, para a última mudança nunca se perder.
 */
function rtm_ci_webhook() {
	if ( ! rtm_ci_ligado() ) {
		return new WP_REST_Response( array( 'ok' => false, 'msg' => 'desligado' ), 409 );
	}
	if ( get_transient( 'rtm_ci_trava' ) ) {
		if ( ! wp_next_scheduled( 'rtm_ci_sincronizar_unica' ) ) {
			wp_schedule_single_event( time() + 30, 'rtm_ci_sincronizar_unica' );
		}
		return new WP_REST_Response( array( 'ok' => true, 'msg' => 'agendado' ), 202 );
	}
	set_transient( 'rtm_ci_trava', 1, 20 );
	$st = rtm_ci_sincronizar();
	return new WP_REST_Response( array( 'ok' => ! empty( $st['ok'] ), 'agentes' => (int) $st['agentes'], 'sugestoes' => (int) $st['sugestoes'] ), ! empty( $st['ok'] ) ? 200 : 502 );
}

function rtm_ci_rotas() {
	register_rest_route( 'rede-marketplace/v1', '/sincronizar', array(
		'methods'             => 'POST',
		'callback'            => 'rtm_ci_webhook',
		'permission_callback' => '__return_true',
	) );
}

function rtm_ci_url_webhook() {
	return rest_url( 'rede-marketplace/v1/sincronizar' );
}

function rtm_ci_acao_sincronizar() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'rtm_ci_sincronizar' );
	rtm_ci_sincronizar();
	wp_safe_redirect( add_query_arg( array( 'page' => 'rede-marketplace', 'rtm_ci' => '1' ), admin_url( 'options-general.php' ) ) );
	exit;
}

/** Linha de situação + botão na tela de configurações. */
function rtm_ci_html_status() {
	if ( ! rtm_ci_ligado() ) {
		return '<p>Os agentes vêm do cadastro do WordPress (ligação com o Cliente Ideal desligada).</p>';
	}
	$s = get_option( 'rtm_ci_status', array() );
	if ( ! is_array( $s ) || empty( $s['quando'] ) ) {
		$txt = 'Ainda não sincronizou.';
	} elseif ( empty( $s['ok'] ) ) {
		$txt = 'Última tentativa em ' . wp_date( 'd/m/Y H:i', (int) $s['quando'] ) . ' falhou (' . $s['msg'] . '). O site continua com os dados anteriores.';
	} else {
		$txt = 'Sincronizado em ' . wp_date( 'd/m/Y H:i', (int) $s['quando'] ) . ': ' . (int) $s['agentes'] . ' agentes e ' . (int) $s['sugestoes'] . ' sugestões.'
			. ( $s['tirados'] ? ' ' . (int) $s['tirados'] . ' tirados do ar (rascunho).' : '' ) . ( $s['msg'] ? ' Aviso: ' . $s['msg'] . '.' : '' );
	}
	return '<h2>Cliente Ideal</h2><p>' . esc_html( $txt ) . '</p><p>O Cliente Ideal avisa o site a cada mudança (webhook <code>' . esc_html( rtm_ci_url_webhook() ) . '</code>, POST). De hora em hora o site confere sozinho, como reserva.</p>'
		. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rtm_ci_sincronizar">'
		. wp_nonce_field( 'rtm_ci_sincronizar', '_wpnonce', true, false )
		. '<button type="submit" class="button">Sincronizar agora</button></form>';
}

function rtm_ci_mostrar_status() {
	echo rtm_ci_html_status(); // phpcs:ignore WordPress.Security.EscapeOutput -- montado com esc_* acima
}

/** Aviso na edição do agente/sugestão que vem do Cliente Ideal. */
function rtm_ci_aviso_edicao( $post ) {
	if ( ! $post || ! in_array( $post->post_type, array( 'rt_agente', 'rt_sugestao' ), true ) || '' === (string) get_post_meta( $post->ID, 'rtm_ci_id', true ) ) {
		return;
	}
	echo '<div class="notice notice-warning inline"><p>Este cadastro vem do <b>Cliente Ideal</b>. Mude lá: o que for alterado aqui é substituído na próxima sincronização.</p></div>';
}

add_action( 'init', 'rtm_ci_agendar' );
add_action( 'rtm_ci_sincronizar_evento', 'rtm_ci_sincronizar' );
add_action( 'rtm_ci_sincronizar_unica', 'rtm_ci_sincronizar' );
add_action( 'rest_api_init', 'rtm_ci_rotas' );
add_action( 'admin_post_rtm_ci_sincronizar', 'rtm_ci_acao_sincronizar' );
add_action( 'rtm_configuracoes_extra', 'rtm_ci_mostrar_status', 5 );
add_action( 'edit_form_top', 'rtm_ci_aviso_edicao' );
