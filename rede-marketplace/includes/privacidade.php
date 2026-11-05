<?php
/** Modo ensaio: páginas do agente e da cotação fora do Google (noindex) e fora dos sitemaps. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rtm_ensaio() {
	return (bool) rtm_opcoes()['ensaio'];
}

/** Página atual é do marketplace (agente, sugestão ou página com [rt_cotacao])? */
function rtm_pagina_marketplace() {
	if ( is_singular( array( 'rt_agente', 'rt_sugestao' ) ) || rtm_pagina_tem_cotacao() ) {
		return true;
	}
	$p = is_singular() ? get_post( get_queried_object_id() ) : null;
	return $p && isset( $p->post_content ) && false !== strpos( (string) $p->post_content, '[rt_especialistas_lista' );
}

function rtm_robots( $robots ) {
	if ( rtm_ensaio() && rtm_pagina_marketplace() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}

function rtm_robots_rank_math( $robots ) {
	if ( rtm_ensaio() && rtm_pagina_marketplace() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'nofollow';
	}
	return $robots;
}

function rtm_sitemaps_tipos( $tipos ) {
	if ( rtm_ensaio() ) {
		unset( $tipos['rt_agente'], $tipos['rt_sugestao'] );
	}
	return $tipos;
}

function rtm_sitemaps_args( $args, $tipo = '' ) {
	$id = ( rtm_ensaio() && 'page' === $tipo ) ? rtm_id_pagina_cotacao() : 0;
	if ( $id ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), array( $id ) );
	}
	return $args;
}

function rtm_rank_math_excluir_tipo( $excluir, $tipo = '' ) {
	return ( rtm_ensaio() && in_array( $tipo, array( 'rt_agente', 'rt_sugestao' ), true ) ) ? true : $excluir;
}

function rtm_rank_math_entrada( $url, $tipo = '', $objeto = null ) {
	if ( rtm_ensaio() && is_object( $objeto ) && isset( $objeto->ID ) ) {
		$id = rtm_id_pagina_cotacao();
		if ( $id && (int) $objeto->ID === $id ) {
			return false;
		}
	}
	return $url;
}

add_filter( 'wp_robots', 'rtm_robots' );
add_filter( 'rank_math/frontend/robots', 'rtm_robots_rank_math' );
add_filter( 'wp_sitemaps_post_types', 'rtm_sitemaps_tipos' );
add_filter( 'wp_sitemaps_posts_query_args', 'rtm_sitemaps_args', 10, 2 );
add_filter( 'rank_math/sitemap/exclude_post_type', 'rtm_rank_math_excluir_tipo', 10, 2 );
add_filter( 'rank_math/sitemap/entry', 'rtm_rank_math_entrada', 10, 3 );
