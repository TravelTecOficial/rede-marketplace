<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

$GLOBALS['rt_posts'][] = (object) array( 'ID' => 10, 'post_type' => 'destinos', 'post_status' => 'publish', 'post_title' => 'Gramado', 'post_name' => 'gramado' );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 12, 'post_type' => 'destinos', 'post_status' => 'draft', 'post_title' => 'Rascunho', 'post_name' => 'rascunho' );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 5, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => 'Mariana Kloss', 'post_name' => 'mariana-kloss' );
$GLOBALS['rt_meta'][5] = array( 'rtm_ativo' => '1' );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 40, 'post_type' => 'rt_sugestao', 'post_status' => 'draft', 'post_title' => 'Nova', 'post_name' => 'nova' );

ok( in_array( 'rtm_salvar_sugestao', $GLOBALS['rt_ganchos']['save_post_rt_sugestao'], true ), 'save_post_rt_sugestao -> rtm_salvar_sugestao' );
ok( isset( $GLOBALS['rt_ganchos']['manage_rt_sugestao_posts_columns'] ), 'colunas da lista de sugestões' );

$l = rtm_limpar_sugestao( array(
	'agente' => '5', 'destino' => 'gramado', 'tipo' => 'Família', 'texto_card' => str_repeat( 'é', 200 ),
	'galeria' => '31,32,31,x,33,34,35,36,37,38,39', 'inclui' => array( 'Hotel', '', ' Transfer ' ),
	'dias' => array( array( 'titulo' => 'Chegada', 'descricao' => str_repeat( 'a', 250 ) ), array( 'titulo' => '', 'descricao' => 'sem título' ) ),
	'preco' => 'R$ 2.890', 'ordem' => '3', 'duracao' => '<b>4 dias</b>',
) );
igual( 5, $l['agente'], 'agente: id de rt_agente' );
igual( 'gramado', $l['destino'], 'destino publicado' );
igual( 'Família', $l['tipo'], 'tipo válido' );
igual( 140, mb_strlen( $l['texto_card'] ), 'texto_card cortado em 140' );
igual( array( 31, 32, 33, 34, 35, 36, 37, 38 ), $l['galeria'], 'galeria: ids, sem repetição, máx 8' );
igual( array( 'Hotel', 'Transfer' ), $l['inclui'], 'inclui sem vazios' );
igual( 1, count( $l['dias'] ), 'dias sem título descartados' );
igual( 200, mb_strlen( $l['dias'][0]['descricao'] ), 'descrição do dia máx 200' );
igual( 2890, $l['preco'], 'preço "R$ 2.890" -> 2890' );
igual( 3, $l['ordem'], 'ordem inteira' );
igual( '4 dias', $l['duracao'], 'texto sanitizado' );

$x = rtm_limpar_sugestao( array( 'agente' => '10', 'destino' => 'rascunho', 'tipo' => 'Inventado', 'preco' => 'abc' ) );
igual( 0, $x['agente'], 'agente que não é rt_agente -> 0' );
igual( '', $x['destino'], 'destino em rascunho -> vazio' );
igual( '', $x['tipo'], 'tipo desconhecido -> vazio' );
igual( '', $x['preco'], 'preço inválido -> vazio' );
igual( 2890, rtm_limpar_sugestao( array( 'preco' => '2890' ) )['preco'], 'preço "2890"' );
igual( 2890, rtm_limpar_sugestao( array( 'preco' => '2.890' ) )['preco'], 'preço "2.890"' );
igual( array(), rtm_limpar_sugestao( array() )['inclui'], 'inclui ausente -> lista vazia' );

// Salvar
$_POST = array( 'rtm_s' => array( 'agente' => '5', 'duracao' => '4 dias' ) );
rtm_salvar_sugestao( 40 );
ok( ! isset( $GLOBALS['rt_meta'][40]['rtm_agente'] ), 'sem nonce: não grava' );
$_POST['rtm_sugestao'] = 'ok_rtm_sugestao';
rtm_salvar_sugestao( 40 );
igual( 5, $GLOBALS['rt_meta'][40]['rtm_agente'], 'com nonce: grava agente' );
igual( '4 dias', $GLOBALS['rt_meta'][40]['rtm_duracao'], 'com nonce: grava duração' );
$_POST = array();

// Caixa
ob_start(); rtm_caixa_sugestao( get_post( 40 ) ); $cx = ob_get_clean();
contem( '<select id="rtm_s_agente" name="rtm_s[agente]"', $cx, 'select de agente' );
contem( '<option value="5" selected="selected">Mariana Kloss</option>', $cx, 'agente atual marcado' );
contem( 'name="rtm_s[galeria]"', $cx, 'campo da galeria' );
contem( 'data-rtm-repetidor="inclui"', $cx, 'repetidor de inclui' );
contem( 'data-rtm-repetidor="dias"', $cx, 'repetidor de dias' );
contem( 'name="rtm_sugestao" value="ok_rtm_sugestao"', $cx, 'nonce' );
