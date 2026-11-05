<?php
/**
 * Sugestões de viagem do agente (tipo rt_sugestao): viagens-modelo que o agente costuma montar.
 * URL própria: /especialistas/<agente>/<sugestao>/. Só existe se o agente existir e estiver ativo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Campos da sugestão (meta `rtm_<chave>`). */
function rtm_campos_sugestao() {
	$c = function ( $rotulo, $tipo, $max = null ) {
		return array( 'rotulo' => $rotulo, 'tipo' => $tipo, 'max' => $max );
	};
	return array(
		'agente'         => $c( 'Agente', 'agente' ),
		'destino'        => $c( 'Destino principal', 'destino' ),
		'destinos_texto' => $c( 'Destinos (texto do selo)', 'texto' ),
		'tipo'           => $c( 'Tipo de viagem', 'tipo' ),
		'texto_card'     => $c( 'Texto curto (cartão)', 'textarea', 140 ),
		'resumo'         => $c( 'Resumo', 'textarea', 400 ),
		'galeria'        => $c( 'Galeria', 'galeria', 8 ),
		'duracao'        => $c( 'Duração', 'texto' ),
		'ideal_para'     => $c( 'Ideal para', 'texto' ),
		'melhor_epoca'   => $c( 'Melhor época', 'texto' ),
		'saida'          => $c( 'Saída', 'texto' ),
		'inclui'         => $c( 'O que inclui', 'lista', 12 ),
		'dias'           => $c( 'Roteiro (dias)', 'dias', 10 ),
		'preco'          => $c( 'A partir de (R$ por pessoa)', 'preco' ),
		'ordem'          => $c( 'Ordem', 'numero' ),
		'img_teste'      => $c( 'URL da imagem (só sugestões de teste)', 'texto' ),
	);
}

/** Registra o tipo e a regra de URL. Chamado por rtm_registrar(). */
function rtm_registrar_sugestao() {
	register_post_type( 'rt_sugestao', array(
		'labels'              => array( 'name' => 'Sugestões', 'singular_name' => 'Sugestão', 'add_new_item' => 'Nova sugestão', 'edit_item' => 'Editar sugestão' ),
		'public'              => true,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => 'rt_sugestao',
		'show_in_rest'        => false,
		'show_in_menu'        => 'edit.php?post_type=rt_agente',
		'supports'            => array( 'title', 'thumbnail' ),
	) );
	add_rewrite_rule( '^especialistas/([^/]+)/([^/]+)/?$', 'index.php?rt_sugestao=$matches[2]&rtm_sug_agente=$matches[1]', 'top' );
}

function rtm_sugestao_query_vars( $vars ) {
	$vars[] = 'rtm_sug_agente';
	return $vars;
}

/** As regras de URL só valem depois de renovadas; a atualização pelo GitHub não reativa o plugin. */
function rtm_renovar_regras() {
	if ( RTM_VERSION === get_option( 'rtm_regras_versao' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'rtm_regras_versao', RTM_VERSION, true );
}

/** "R$ 2.890" (inteiro em reais); '' sem valor. */
function rtm_preco_txt( $reais ) {
	$n = (int) $reais;
	return $n > 0 ? 'R$ ' . number_format( $n, 0, ',', '.' ) : '';
}

function rtm_url_sugestao( array $agente, $slug ) {
	return home_url( '/especialistas/' . $agente['slug'] . '/' . $slug . '/' );
}

/** Dados públicos da sugestão, ou null (não é sugestão, não publicada, agente inexistente ou inativo). */
function rtm_sugestao( $post_id ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );
	if ( ! $post || 'rt_sugestao' !== $post->post_type || 'publish' !== get_post_status( $post_id ) ) {
		return null;
	}
	$agente = rtm_agente( (int) get_post_meta( $post_id, 'rtm_agente', true ) );
	if ( ! $agente ) {
		return null;
	}
	$s = array();
	foreach ( rtm_campos_sugestao() as $chave => $def ) {
		$v = get_post_meta( $post_id, 'rtm_' . $chave, true );
		if ( in_array( $def['tipo'], array( 'lista', 'dias', 'galeria' ), true ) ) {
			$v = is_array( $v ) ? $v : array();
		}
		$s[ $chave ] = $v;
	}
	$urls   = get_post_meta( $post_id, 'rtm_galeria_urls', true ); // fotos vindas do Cliente Ideal
	$urls   = is_array( $urls ) ? array_values( array_filter( $urls, 'is_string' ) ) : array();
	$imagem = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( ! $imagem && $urls ) {
		$imagem = $urls[0];
	}
	if ( ! $imagem ) {
		$imagem = (string) $s['img_teste'];
	}
	$fotos = $imagem ? array( $imagem ) : array();
	foreach ( $urls as $u ) {
		if ( ! in_array( $u, $fotos, true ) ) {
			$fotos[] = $u;
		}
	}
	foreach ( $s['galeria'] as $id ) {
		$u = wp_get_attachment_image_url( (int) $id, 'large' );
		if ( $u && ! in_array( $u, $fotos, true ) ) {
			$fotos[] = $u;
		}
	}
	$s['id']     = $post_id;
	$s['slug']   = $post->post_name;
	$s['titulo'] = $post->post_title;
	$s['url']    = rtm_url_sugestao( $agente, $post->post_name );
	$s['imagem'] = $imagem ? $imagem : '';
	$s['fotos']  = $fotos;
	$s['agente'] = $agente;
	return $s;
}

/** Sugestões publicadas do agente, por ordem e título. */
function rtm_sugestoes_do_agente( $agente_id ) {
	$posts = get_posts( array(
		'post_type'      => 'rt_sugestao',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'rtm_agente',
		'meta_value'     => (int) $agente_id,
	) );
	$res = array();
	foreach ( $posts as $p ) {
		$s = rtm_sugestao( $p->ID );
		if ( $s ) {
			$res[] = $s;
		}
	}
	usort( $res, function ( $a, $b ) {
		$o = (int) $a['ordem'] - (int) $b['ordem'];
		return 0 !== $o ? $o : strcmp( $a['titulo'], $b['titulo'] );
	} );
	return $res;
}

/** O agente da URL é o dono da sugestão? */
function rtm_sugestao_da_url( $agente_slug, $sugestao_id ) {
	$s = rtm_sugestao( $sugestao_id );
	return $s && (string) $agente_slug === $s['agente']['slug'];
}

function rtm_link_sugestao( $url, $post ) {
	if ( ! is_object( $post ) || 'rt_sugestao' !== $post->post_type ) {
		return $url;
	}
	$agente = rtm_agente( (int) get_post_meta( $post->ID, 'rtm_agente', true ) );
	return $agente ? rtm_url_sugestao( $agente, $post->post_name ) : $url;
}

/** Sugestão sem agente ativo, ou pedida com o agente errado na URL: 404. */
function rtm_404_sugestao() {
	if ( ! is_singular( 'rt_sugestao' ) ) {
		return;
	}
	$id = get_queried_object_id();
	if ( rtm_sugestao_da_url( (string) get_query_var( 'rtm_sug_agente' ), $id ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

/** URLs das sugestões (qualquer status) de um agente, para limpar o cache. */
function rtm_urls_sugestoes_do_agente( $agente_id ) {
	$agente = rtm_agente( (int) $agente_id );
	$posts  = get_posts( array( 'post_type' => 'rt_sugestao', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => 'rtm_agente', 'meta_value' => (int) $agente_id ) );
	$urls   = array();
	foreach ( $posts as $p ) {
		if ( $agente && '' !== (string) $p->post_name ) {
			$urls[] = rtm_url_sugestao( $agente, $p->post_name );
		}
	}
	return $urls;
}

/**
 * Limpa o LiteSpeed depois de salvar/apagar uma sugestão (save_post_rt_sugestao, prioridade 20):
 * a sugestão, o agente dono (cartões), as outras sugestões dele e a cotação (JSON das sugestões).
 */
function rtm_purgar_sugestao( $post_id ) {
	$post_id = (int) $post_id;
	do_action( 'litespeed_purge_post', $post_id );
	$agente_id = (int) get_post_meta( $post_id, 'rtm_agente', true );
	if ( $agente_id ) {
		rtm_purgar_cache( $agente_id ); // agente + URLs das sugestões dele + cotação
	} else {
		rtm_purgar_cotacao();
	}
}

add_filter( 'query_vars', 'rtm_sugestao_query_vars' );
add_action( 'save_post_rt_sugestao', 'rtm_purgar_sugestao', 20 );
add_action( 'init', 'rtm_renovar_regras', 99 );
add_filter( 'post_type_link', 'rtm_link_sugestao', 10, 2 );
add_action( 'template_redirect', 'rtm_404_sugestao' );
