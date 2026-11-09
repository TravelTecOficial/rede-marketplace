<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $tipo, $meta = array() ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => 'publish', 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => '' );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
rtm_t_p( 5, 'Serra Real', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00', 'google_place_id' => 'ChIJ_abc-123', 'endereco' => 'Av. Borges, 1' ) );
rtm_t_p( 6, 'Sem Perfil', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00' ) );
rtm_t_p( 7, 'Mariana Kloss', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00', 'teste' => '1' ) );

// Opções: chave limpa
$o = rtm_limpar_opcoes( array( 'url_cotacao' => 'https://exemplo.test/c/', 'google_chave' => " AIza_Sy-123<script> " ) );
igual( 'AIza_Sy-123script', $o['google_chave'], 'chave: só letras, números, _ e -' );
igual( '', rtm_opcoes()['google_chave'], 'sem chave salva: vazio' );
ok( isset( rtm_campos()['google_place_id'] ) && true === rtm_campos()['google_place_id']['publico'], 'campo Place ID (público)' );
igual( 'ChIJ_abc-123', rtm_limpar_campos( array( 'google_place_id' => ' ChIJ_abc-123 ' ) )['google_place_id'], 'Place ID limpo' );
igual( '', rtm_limpar_campos( array( 'google_place_id' => 'https://maps.google.com/x y' ) )['google_place_id'], 'Place ID inválido -> vazio' );

$a = rtm_agente( 5 );
ok( ! rtm_google_ativo( $a ), 'sem chave: Google desligado' );
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'google_chave' => 'CHAVE1' );
ok( rtm_google_ativo( $a ), 'chave + Place ID: Google ligado' );
ok( ! rtm_google_ativo( rtm_agente( 6 ) ), 'sem Place ID: Google desligado' );

// Avaliações: bloco que o navegador preenche
$av = rtm_secao( 'avaliacoes', $a );
contem( 'id="avaliacoes"', $av, 'bloco de avaliações do Google' );
contem( 'data-rtm-google="ChIJ_abc-123"', $av, 'bloco leva o Place ID' );
contem( 'data-chave="CHAVE1"', $av, 'bloco leva a chave (restrita ao site)' );
contem( ' hidden', $av, 'escondido até o Google responder' );
nao_contem( 'Exemplo para o ensaio', $av, 'sem avaliações fictícias' );
$topo = rtm_secao( 'topo', $a );
contem( 'data-rtm-google-nota', $topo, 'nota do Google no topo (preenchida no navegador)' );
contem( 'data-rtm-google-ver', $topo, 'botão Ver avaliações espera o Google' );

// Onde atende: mapa e Como chegar
$onde = rtm_secao( 'onde', $a );
contem( 'src="https://www.google.com/maps/embed/v1/place?key=CHAVE1&amp;q=place_id:ChIJ_abc-123"', $onde, 'mapa do Maps Embed' );
contem( 'loading="lazy"', $onde, 'mapa carrega só quando aparece' );
contem( 'https://www.google.com/maps/dir/?api=1&amp;destination=Serra+Real&amp;destination_place_id=ChIJ_abc-123', $onde, 'link Como chegar' );
contem( 'Av. Borges, 1', $onde, 'endereço digitado continua' );
ok( '' !== rtm_secao( 'onde', rtm_agente( 5 ) ), 'com Google, Onde atende aparece' );

// Sem Google: como hoje
igual( '', rtm_secao( 'onde', rtm_agente( 6 ) ), 'sem Google e sem endereço: sem Onde atende' );
$t = rtm_secao( 'avaliacoes', rtm_agente( 7 ) );
nao_contem( 'Exemplo para o ensaio', $t, 'agente de teste: sem avaliações fictícias' );

// Script só quando o Google está ligado
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 1, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'x', 'post_name' => 'x', 'post_content' => '' );
$GLOBALS['rt_atual'] = 5; $GLOBALS['rt_scripts'] = array();
rtm_estilos();
ok( isset( $GLOBALS['rt_scripts']['rtm-google'] ), 'agente com Google: carrega assets/google.js' );
$GLOBALS['rt_atual'] = 6; $GLOBALS['rt_scripts'] = array();
rtm_estilos();
ok( ! isset( $GLOBALS['rt_scripts']['rtm-google'] ), 'agente sem Google: não carrega' );
$GLOBALS['rt_atual'] = 0;

// Tela de configurações: campo da chave
ob_start(); rtm_tela_configuracoes(); $tela = ob_get_clean();
contem( 'name="rtm_opcoes[google_chave]"', $tela, 'campo da chave nas configurações' );

// Onde atende ocupa a largura toda, depois da grade de duas colunas
$corpo = rtm_corpo_agente( rtm_agente( 5 ) );
ok( strpos( $corpo, 'rtm-onde' ) > strpos( $corpo, 'rtm-col-lateral' ), 'Onde atende vem depois da lateral' );
contem( '<div class="rtm-largura-total">', $corpo, 'Onde atende fora das colunas' );
contem( '.rtm-largura-total .rtm-onde .rtm-mapa{aspect-ratio:21/9}', file_get_contents( '/p/assets/rede-marketplace.css' ), 'mapa mais alto na largura toda' );
