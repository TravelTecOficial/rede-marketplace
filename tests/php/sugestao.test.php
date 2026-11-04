<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $tipo, $meta = array(), $status = 'publish' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => '' );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => true );
rtm_t_p( 5, 'Mariana Kloss', 'rt_agente', array( 'ativo' => '1' ) );
rtm_t_p( 6, 'Rafael Antunes', 'rt_agente', array( 'ativo' => '1' ) );
rtm_t_p( 7, 'Inativo Silva', 'rt_agente', array( 'ativo' => '0' ) );
rtm_t_p( 20, 'Gramado em família', 'rt_sugestao', array( 'agente' => 5, 'preco' => 2890, 'ordem' => 2, 'destino' => 'gramado', 'galeria' => array( 31, 32 ) ) );
rtm_t_p( 21, 'Natal Luz', 'rt_sugestao', array( 'agente' => 5, 'ordem' => 1 ) );
rtm_t_p( 22, 'Rascunho', 'rt_sugestao', array( 'agente' => 5 ), 'draft' );
rtm_t_p( 23, 'De inativo', 'rt_sugestao', array( 'agente' => 7 ) );
$GLOBALS['rt_meta'][20]['_thumbnail_id'] = 30;
$GLOBALS['rt_anexos'] = array( 30 => 'https://exemplo.test/capa.jpg', 31 => 'https://exemplo.test/g1.jpg', 32 => 'https://exemplo.test/g2.jpg' );

rtm_registrar();
ok( isset( $GLOBALS['rt_tipos']['rt_sugestao'] ), 'tipo rt_sugestao registrado' );
ok( false === $GLOBALS['rt_tipos']['rt_sugestao']['show_in_rest'], 'rt_sugestao fora da REST' );
ok( true === $GLOBALS['rt_tipos']['rt_sugestao']['exclude_from_search'], 'rt_sugestao fora da busca' );
igual( 'index.php?rt_sugestao=$matches[2]&rtm_sug_agente=$matches[1]', $GLOBALS['rt_regras']['^especialistas/([^/]+)/([^/]+)/?$'], 'regra da URL /especialistas/<agente>/<sugestao>/' );

igual( null, rtm_sugestao( 22 ), 'rascunho: null' );
igual( null, rtm_sugestao( 23 ), 'agente inativo: null' );
igual( null, rtm_sugestao( 5 ), 'não é sugestão: null' );
$s = rtm_sugestao( 20 );
igual( 'Mariana Kloss', $s['agente']['nome'], 'sugestão traz o agente' );
igual( 'https://exemplo.test/especialistas/mariana-kloss/gramado-em-familia/', $s['url'], 'URL da sugestão' );
igual( 'https://exemplo.test/capa.jpg', $s['imagem'], 'imagem = destacada' );
igual( array( 'https://exemplo.test/capa.jpg', 'https://exemplo.test/g1.jpg', 'https://exemplo.test/g2.jpg' ), $s['fotos'], 'fotos = destacada + galeria' );
igual( '', rtm_sugestao( 21 )['imagem'], 'sem imagem: vazio' );
igual( array( 21, 20 ), wp_list_pluck( rtm_sugestoes_do_agente( 5 ), 'id' ), 'do agente: por ordem, sem rascunho' );
igual( array(), rtm_sugestoes_do_agente( 6 ), 'agente sem sugestões' );

igual( 'R$ 2.890', rtm_preco_txt( 2890 ), 'preço formatado' );
igual( '', rtm_preco_txt( 0 ), 'preço 0: vazio' );
igual( '', rtm_preco_txt( '' ), 'preço vazio' );

ok( true === rtm_sugestao_da_url( 'mariana-kloss', 20 ), 'URL com o agente certo' );
ok( false === rtm_sugestao_da_url( 'rafael-antunes', 20 ), 'URL com outro agente' );

igual( array( 'page' => 1 ), rtm_sitemaps_tipos( array( 'rt_sugestao' => 1, 'page' => 1 ) ), 'ensaio: rt_sugestao fora do sitemap' );
ok( true === rtm_rank_math_excluir_tipo( false, 'rt_sugestao' ), 'ensaio: fora do sitemap do Rank Math' );
$GLOBALS['rt_atual'] = 20;
ok( rtm_pagina_marketplace(), 'página da sugestão é do marketplace (noindex no ensaio)' );
$GLOBALS['rt_atual'] = 0;

unset( $GLOBALS['rt_opcoes']['rtm_regras_versao'] );
$GLOBALS['rt_flush'] = 0;
rtm_renovar_regras();
igual( 1, $GLOBALS['rt_flush'], 'versão nova: renova as regras' );
igual( RTM_VERSION, get_option( 'rtm_regras_versao' ), 'grava a versão' );
rtm_renovar_regras();
igual( 1, $GLOBALS['rt_flush'], 'mesma versão: não renova de novo' );

// Lista do site sem rt_sugestao
$q = new RT_Consulta( array( 'post_type' => 'rt_sugestao' ) );
rtm_fora_das_listas( $q );
igual( array( 0 ), $q->get( 'post__in' ), 'listagem de rt_sugestao vazia' );
