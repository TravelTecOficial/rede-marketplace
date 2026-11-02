<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registra o tipo de post do agente e a taxonomia de tipos de viagem. */
function rtm_registrar() {
	register_post_type( 'rt_agente', array(
		'labels'       => array( 'name' => 'Agentes', 'singular_name' => 'Agente' ),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'especialistas', 'with_front' => false ),
		'show_in_rest' => false,
		'menu_icon'    => 'dashicons-groups',
		'supports'     => array( 'title', 'thumbnail' ),
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

/** Ao salvar um agente, limpa o cache do post e da página de cotação (LiteSpeed). */
function rtm_purgar_cache( $post_id ) {
	do_action( 'litespeed_purge_post', (int) $post_id );
	$o = rtm_opcoes();
	do_action( 'litespeed_purge_url', $o['url_cotacao'] );
}

add_action( 'init', 'rtm_registrar' );
add_action( 'save_post_rt_agente', 'rtm_purgar_cache', 20 );
register_activation_hook( RTM_DIR . 'rede-marketplace.php', function () {
	rtm_registrar();
	rtm_instalar_tipos();
	flush_rewrite_rules();
} );
