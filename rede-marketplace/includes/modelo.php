<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registra o tipo de post do agente e a taxonomia de tipos de viagem. */
function rtm_registrar() {
	register_post_type( 'rt_agente', array(
		'labels'              => array( 'name' => 'Agentes', 'singular_name' => 'Agente' ),
		'public'              => true,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => array( 'slug' => 'especialistas', 'with_front' => false ),
		'show_in_rest'        => false,
		'menu_icon'           => 'dashicons-groups',
		'supports'            => array( 'title', 'thumbnail' ),
	) );
	register_taxonomy( 'rt_tipo_viagem', array( 'rt_agente' ), array(
		'labels'       => array( 'name' => 'Tipos de viagem', 'singular_name' => 'Tipo de viagem' ),
		'hierarchical' => false,
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => false,
		'rewrite'      => false,
	) );
}

function rtm_tipos_iniciais() {
	return array( 'Família', 'Casal / Lua de mel', 'Natal Luz', 'Grupo', 'Melhor idade', 'Luxo' );
}

/** Cria os tipos de viagem que faltam (idempotente). */
function rtm_instalar_tipos() {
	foreach ( rtm_tipos_iniciais() as $nome ) {
		if ( ! term_exists( $nome, 'rt_tipo_viagem' ) ) {
			wp_insert_term( $nome, 'rt_tipo_viagem' );
		}
	}
}

/** Campos do agente (meta `rtm_<chave>`). Os não públicos nunca saem em rtm_agente(). */
function rtm_campos() {
	$c = function ( $rotulo, $tipo, $publico = true, $max = null ) {
		return array( 'rotulo' => $rotulo, 'tipo' => $tipo, 'publico' => $publico, 'max' => $max );
	};
	return array(
		'cargo'                 => $c( 'Cargo', 'texto' ),
		'texto_card'            => $c( 'Texto do cartão', 'textarea', true, 180 ),
		'apresentacao'          => $c( 'Apresentação', 'textarea' ),
		'cadastur'              => $c( 'Cadastur (número)', 'texto' ),
		'anos_experiencia'      => $c( 'Anos de experiência', 'numero' ),
		'viajantes_atendidos'   => $c( 'Viajantes atendidos', 'numero' ),
		'idiomas'               => $c( 'Idiomas', 'lista' ),
		'especialidades'        => $c( 'Especialidades', 'especialidades', true, 4 ),
		'destinos'              => $c( 'Destinos', 'destinos' ),
		'destino_principal'     => $c( 'Destino principal', 'texto' ),
		'agencia_nome'          => $c( 'Agência', 'texto' ),
		'agencia_logo'          => $c( 'Logo da agência', 'imagem' ),
		'agencia_cidade'        => $c( 'Cidade da agência', 'texto' ),
		'agencia_desde'         => $c( 'Agência desde', 'numero' ),
		'endereco'              => $c( 'Endereço', 'texto' ),
		'horario'               => $c( 'Horário de atendimento', 'texto' ),
		'google_perfil'         => $c( 'Perfil no Google (URL)', 'texto' ),
		'instagram'             => $c( 'Instagram', 'rede' ),
		'facebook'              => $c( 'Facebook', 'rede' ),
		'tiktok'                => $c( 'TikTok', 'rede' ),
		'youtube'               => $c( 'YouTube', 'rede' ),
		'linkedin'              => $c( 'LinkedIn', 'rede' ),
		'site'                  => $c( 'Site', 'rede' ),
		'foto_url_teste'        => $c( 'URL da foto (só agentes de teste)', 'texto' ),
		'whatsapp'              => $c( 'WhatsApp', 'telefone', false ),
		'email'                 => $c( 'E-mail', 'email', false ),
		'company_id'            => $c( 'ID da empresa no CRM', 'texto', false ),
		'ativo'                 => $c( 'Ativo', 'checkbox', false ),
		'cadastur_conferido_em' => $c( 'Cadastur conferido em', 'data', false ),
	);
}

/** Iniciais (2 letras): primeira e última palavra; nome único usa as 2 primeiras letras. */
function rtm_iniciais( $nome ) {
	$p = preg_split( '/\s+/u', trim( (string) $nome ), -1, PREG_SPLIT_NO_EMPTY );
	if ( ! $p ) {
		return '';
	}
	if ( count( $p ) > 1 ) {
		$i = mb_substr( $p[0], 0, 1 ) . mb_substr( $p[ count( $p ) - 1 ], 0, 1 );
	} else {
		$i = mb_substr( $p[0], 0, 2 );
	}
	return mb_strtoupper( $i );
}

/**
 * Link de rede social: aceita URL completa, endereço sem https:// ou @usuario (Instagram e TikTok).
 * Só http(s); qualquer outra coisa vira ''.
 */
function rtm_rede_url( $rede, $valor ) {
	$v = trim( (string) $valor );
	if ( '' === $v ) {
		return '';
	}
	if ( preg_match( '/^@([A-Za-z0-9._]{1,30})$/', $v, $m ) ) {
		if ( 'instagram' === $rede ) {
			return 'https://www.instagram.com/' . $m[1] . '/';
		}
		if ( 'tiktok' === $rede ) {
			return 'https://www.tiktok.com/@' . $m[1];
		}
		return '';
	}
	if ( ! preg_match( '#^[a-z][a-z0-9+.\-]*://#i', $v ) ) {
		$v = 'https://' . $v;
	}
	if ( ! preg_match( '#^https?://[^\s/?\#]+\.[^\s/?\#]+#i', $v ) ) {
		return '';
	}
	return esc_url_raw( $v );
}

/** Normaliza especialidades para uma lista (máx. 4) de { titulo, descricao }; texto simples antigo vira título sem descrição. */
function rtm_especialidades_normalizar( $bruto, $max = 4 ) {
	$lista = array();
	foreach ( is_array( $bruto ) ? $bruto : array() as $item ) {
		if ( is_array( $item ) ) {
			$titulo = isset( $item['titulo'] ) && is_scalar( $item['titulo'] ) ? sanitize_text_field( (string) $item['titulo'] ) : '';
			$desc   = isset( $item['descricao'] ) && is_scalar( $item['descricao'] ) ? sanitize_text_field( (string) $item['descricao'] ) : '';
		} elseif ( is_string( $item ) ) {
			$titulo = sanitize_text_field( $item );
			$desc   = '';
		} else {
			continue;
		}
		if ( '' === $titulo ) {
			continue;
		}
		$lista[] = array( 'titulo' => $titulo, 'descricao' => mb_substr( $desc, 0, 120 ) );
	}
	return array_slice( $lista, 0, (int) $max );
}

/** Dados públicos do agente, ou null se não existir, não for rt_agente, não estiver publicado ou ativo. */
function rtm_agente( $post_id ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );
	if ( ! $post || 'rt_agente' !== $post->post_type || 'publish' !== get_post_status( $post_id ) ) {
		return null;
	}
	if ( '1' !== (string) get_post_meta( $post_id, 'rtm_ativo', true ) ) {
		return null;
	}
	$a = array();
	foreach ( rtm_campos() as $chave => $def ) {
		if ( ! $def['publico'] ) {
			continue;
		}
		$v = get_post_meta( $post_id, 'rtm_' . $chave, true );
		if ( in_array( $def['tipo'], array( 'lista', 'destinos', 'especialidades' ), true ) ) {
			$v = is_array( $v ) ? $v : array();
		}
		if ( 'especialidades' === $def['tipo'] ) {
			$v = rtm_especialidades_normalizar( $v, $def['max'] ? $def['max'] : 4 );
		}
		$a[ $chave ] = $v;
	}
	$slugs = array();
	foreach ( $a['destinos'] as $d ) {
		if ( is_array( $d ) && ! empty( $d['slug'] ) ) {
			$slugs[] = (string) $d['slug'];
		}
	}
	$tipos  = array();
	$termos = get_the_terms( $post_id, 'rt_tipo_viagem' );
	if ( is_array( $termos ) ) {
		foreach ( $termos as $t ) {
			$tipos[] = $t->name;
		}
	}
	$foto = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( ! $foto ) {
		$foto = (string) $a['foto_url_teste'];
	}
	$logo = $a['agencia_logo'] ? wp_get_attachment_image_url( (int) $a['agencia_logo'], 'medium' ) : '';

	$a['id']                  = $post_id;
	$a['slug']                = $post->post_name;
	$a['nome']                = $post->post_title;
	$a['url']                 = get_permalink( $post_id );
	$a['foto']                = $foto ? $foto : '';
	$a['logo']                = $logo ? $logo : '';
	$a['iniciais']            = rtm_iniciais( $post->post_title );
	$a['agencia_iniciais']    = rtm_iniciais( (string) $a['agencia_nome'] );
	$a['tipos']               = $tipos;
	$a['destinos_slugs']      = $slugs;
	$a['cadastur_verificado'] = '' !== (string) get_post_meta( $post_id, 'rtm_cadastur_conferido_em', true );
	$a['avaliacoes']          = '1' === (string) get_post_meta( $post_id, 'rtm_teste', true ) ? rtm_avaliacoes_teste( $post->post_title ) : array();
	return $a;
}

/** Todos os agentes publicados e ativos, por título. */
function rtm_agentes_ativos() {
	$posts = get_posts( array(
		'post_type'      => 'rt_agente',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	$res = array();
	foreach ( $posts as $p ) {
		$a = rtm_agente( $p->ID );
		if ( $a ) {
			$res[] = $a;
		}
	}
	usort( $res, function ( $x, $y ) {
		return strcmp( mb_strtolower( remove_accents( $x['nome'] ) ), mb_strtolower( remove_accents( $y['nome'] ) ) );
	} );
	return $res;
}

/** O post (objeto ou id) tem o shortcode [rt_cotacao no conteúdo ou nos dados do Elementor? */
function rtm_post_tem_cotacao( $post ) {
	$p = is_object( $post ) ? $post : get_post( (int) $post );
	if ( ! $p ) {
		return false;
	}
	if ( isset( $p->post_content ) && false !== strpos( (string) $p->post_content, '[rt_cotacao' ) ) {
		return true;
	}
	$dados = get_post_meta( (int) $p->ID, '_elementor_data', true );
	return is_string( $dados ) && false !== strpos( $dados, '[rt_cotacao' );
}

/**
 * Id da página publicada que tem [rt_cotacao (0 se nenhuma). Primeiro tenta a URL das configurações,
 * depois procura nas páginas. Fica em transient; salvar qualquer página ou as opções renova.
 */
function rtm_id_pagina_cotacao() {
	$salvo = get_transient( 'rtm_pagina_cotacao' );
	if ( false !== $salvo ) {
		return (int) $salvo;
	}
	$id = (int) url_to_postid( (string) rtm_opcoes()['url_cotacao'] );
	if ( ! $id || 'publish' !== get_post_status( $id ) || ! rtm_post_tem_cotacao( $id ) ) {
		$id = 0;
		$paginas = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );
		foreach ( $paginas as $p ) {
			if ( rtm_post_tem_cotacao( $p ) ) {
				$id = (int) $p->ID;
				break;
			}
		}
	}
	set_transient( 'rtm_pagina_cotacao', $id, DAY_IN_SECONDS );
	return $id;
}

function rtm_esquecer_pagina_cotacao() {
	delete_transient( 'rtm_pagina_cotacao' );
}

/**
 * Limpa a página de cotação no LiteSpeed: a URL base e o post (a etiqueta do post cobre todas as
 * variantes ?destino=&agente=, que o LiteSpeed guarda separadas).
 */
function rtm_purgar_cotacao() {
	$o = rtm_opcoes();
	do_action( 'litespeed_purge_url', $o['url_cotacao'] );
	$id = rtm_id_pagina_cotacao();
	if ( $id ) {
		do_action( 'litespeed_purge_post', $id );
	}
}

/** Ao salvar um agente: limpa o post, a URL dele (um 404 de quando estava inativo fica em cache) e a cotação. */
function rtm_purgar_cache( $post_id ) {
	$post_id = (int) $post_id;
	do_action( 'litespeed_purge_post', $post_id );
	$url = get_permalink( $post_id );
	if ( $url ) {
		do_action( 'litespeed_purge_url', $url );
	}
	rtm_purgar_cotacao();
}

/** Apagar de vez não passa por save_post: limpa antes (a URL ainda existe)... */
function rtm_purgar_ao_apagar( $post_id ) {
	$p = get_post( $post_id );
	if ( $p && 'rt_agente' === $p->post_type ) {
		rtm_purgar_cache( $post_id );
	}
}

/** ...e a cotação de novo depois, caso alguém a tenha visitado no meio. */
function rtm_purgar_depois_de_apagar( $post_id, $post = null ) {
	if ( is_object( $post ) && isset( $post->post_type ) && 'rt_agente' === $post->post_type ) {
		rtm_purgar_cotacao();
	}
}

/**
 * rt_agente não aparece em listagens do site (busca, feed, /?post_type=rt_agente): só a página de cada agente.
 * Com post_type=rt_agente sozinho a consulta fica sem resultado e deixa de ser "página inicial", e o WP responde 404.
 */
function rtm_fora_das_listas( $q ) {
	if ( is_admin() || ! $q->is_main_query() || $q->is_singular() ) {
		return;
	}
	$tipo = $q->get( 'post_type' );
	if ( is_array( $tipo ) && in_array( 'rt_agente', $tipo, true ) ) {
		$resto = array_values( array_diff( $tipo, array( 'rt_agente' ) ) );
		if ( $resto ) {
			$q->set( 'post_type', $resto );
			return;
		}
		$tipo = 'rt_agente';
	}
	if ( 'rt_agente' === $tipo ) {
		$q->set( 'post__in', array( 0 ) );
		$q->is_home = false;
	}
}

add_action( 'init', 'rtm_registrar' );
add_action( 'save_post_rt_agente', 'rtm_purgar_cache', 20 );
add_action( 'before_delete_post', 'rtm_purgar_ao_apagar' );
add_action( 'deleted_post', 'rtm_purgar_depois_de_apagar', 10, 2 );
add_action( 'save_post_page', 'rtm_esquecer_pagina_cotacao' );
add_action( 'update_option_rtm_opcoes', 'rtm_esquecer_pagina_cotacao' );
add_action( 'pre_get_posts', 'rtm_fora_das_listas' );
register_activation_hook( RTM_DIR . 'rede-marketplace.php', function () {
	rtm_registrar();
	rtm_instalar_tipos();
	flush_rewrite_rules();
} );
