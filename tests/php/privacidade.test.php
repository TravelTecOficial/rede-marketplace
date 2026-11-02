<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_post( $id, $titulo, $tipo = 'rt_agente', $conteudo = '' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => 'publish', 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => $conteudo );
	$GLOBALS['rt_meta'][ $id ] = array( 'rtm_ativo' => '1' );
}
rtm_t_post( 5, 'Mariana Kloss' );
rtm_t_post( 20, 'Cotação', 'page', 'Antes [rt_cotacao] depois' );
rtm_t_post( 21, 'Contato', 'page', 'Fale conosco' );
$GLOBALS['rt_urls']['https://exemplo.test/cotacao-especialistas/'] = 20;

ok( in_array( 'rtm_robots', $GLOBALS['rt_ganchos']['wp_robots'], true ), 'wp_robots -> rtm_robots' );
ok( in_array( 'rtm_robots_rank_math', $GLOBALS['rt_ganchos']['rank_math/frontend/robots'], true ), 'Rank Math: robots' );
ok( in_array( 'rtm_sitemaps_tipos', $GLOBALS['rt_ganchos']['wp_sitemaps_post_types'], true ), 'wp_sitemaps_post_types -> rtm_sitemaps_tipos' );
ok( in_array( 'rtm_sitemaps_args', $GLOBALS['rt_ganchos']['wp_sitemaps_posts_query_args'], true ), 'wp_sitemaps_posts_query_args -> rtm_sitemaps_args' );
ok( in_array( 'rtm_rank_math_excluir_tipo', $GLOBALS['rt_ganchos']['rank_math/sitemap/exclude_post_type'], true ), 'Rank Math: exclude_post_type' );
ok( in_array( 'rtm_rank_math_entrada', $GLOBALS['rt_ganchos']['rank_math/sitemap/entry'], true ), 'Rank Math: sitemap/entry' );

// Ensaio ligado (padrão)
$GLOBALS['rt_atual'] = 5;
$r = rtm_robots( array() );
ok( ! empty( $r['noindex'] ) && ! empty( $r['nofollow'] ), 'ensaio: rt_agente noindex, nofollow' );
$GLOBALS['rt_atual'] = 20;
$r = rtm_robots( array() );
ok( ! empty( $r['noindex'] ) && ! empty( $r['nofollow'] ), 'ensaio: página com [rt_cotacao] noindex, nofollow' );
$GLOBALS['rt_atual'] = 21;
igual( array( 'max-image-preview' => 'large' ), rtm_robots( array( 'max-image-preview' => 'large' ) ), 'ensaio: outra página intocada' );

$GLOBALS['rt_atual'] = 5;
$rm = rtm_robots_rank_math( array( 'index' => 'index', 'follow' => 'follow' ) );
igual( 'noindex', $rm['index'], 'Rank Math: noindex no agente' );
igual( 'nofollow', $rm['follow'], 'Rank Math: nofollow no agente' );

$tipos = rtm_sitemaps_tipos( array( 'post' => 'x', 'page' => 'y', 'rt_agente' => 'z' ) );
igual( array( 'post', 'page' ), array_keys( $tipos ), 'ensaio: sitemap do WP sem rt_agente' );
igual( true, rtm_rank_math_excluir_tipo( false, 'rt_agente' ), 'ensaio: Rank Math exclui rt_agente' );
igual( false, rtm_rank_math_excluir_tipo( false, 'post' ), 'Rank Math: outros tipos intocados' );
$args = rtm_sitemaps_args( array(), 'page' );
igual( array( 20 ), $args['post__not_in'], 'ensaio: página de cotação fora do sitemap do WP' );
igual( array(), rtm_sitemaps_args( array(), 'post' ), 'sitemap de posts intocado' );
igual( false, rtm_rank_math_entrada( array( 'loc' => 'x' ), 'post', (object) array( 'ID' => 20 ) ), 'ensaio: página de cotação fora do Rank Math' );
igual( array( 'loc' => 'x' ), rtm_rank_math_entrada( array( 'loc' => 'x' ), 'post', (object) array( 'ID' => 21 ) ), 'Rank Math: outra página intocada' );

// Ensaio desligado
update_option( 'rtm_opcoes', array( 'ensaio' => false ) );
$GLOBALS['rt_atual'] = 5;
igual( array(), rtm_robots( array() ), 'sem ensaio: rt_agente indexável' );
$GLOBALS['rt_atual'] = 20;
igual( array(), rtm_robots( array() ), 'sem ensaio: cotação indexável' );
igual( array( 'index' => 'index' ), rtm_robots_rank_math( array( 'index' => 'index' ) ), 'sem ensaio: Rank Math intocado' );
igual( array( 'post', 'rt_agente' ), array_keys( rtm_sitemaps_tipos( array( 'post' => 1, 'rt_agente' => 1 ) ) ), 'sem ensaio: rt_agente no sitemap' );
igual( false, rtm_rank_math_excluir_tipo( false, 'rt_agente' ), 'sem ensaio: Rank Math mantém rt_agente' );
igual( array(), rtm_sitemaps_args( array(), 'page' ), 'sem ensaio: cotação no sitemap' );
