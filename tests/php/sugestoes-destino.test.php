<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $tipo, $meta = array() ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => 'publish', 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => '' );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
rtm_t_p( 10, 'Gramado', 'destinos' );
rtm_t_p( 11, 'Bariloche', 'destinos' );
rtm_t_p( 5, 'Mariana Kloss', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00' ) );
rtm_t_p( 6, 'Rafael Antunes', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00' ) );
rtm_t_p( 7, 'Inativa', 'rt_agente', array( 'ativo' => '0' ) );
for ( $i = 0; $i < 7; $i++ ) {
	rtm_t_p( 20 + $i, 'Sugestão ' . $i, 'rt_sugestao', array( 'agente' => $i % 2 ? 6 : 5, 'destino' => 'gramado', 'ordem' => $i ) );
}
rtm_t_p( 30, 'De inativa', 'rt_sugestao', array( 'agente' => 7, 'destino' => 'gramado' ) );
rtm_t_p( 31, 'Outro destino', 'rt_sugestao', array( 'agente' => 5, 'destino' => 'canela' ) );
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => false );

ok( isset( $GLOBALS['rt_shortcodes']['rt_sugestoes_destino'] ), 'shortcode rt_sugestoes_destino' );
$GLOBALS['rt_atual'] = 10;
$h = rtm_sugestoes_destino_html( array( 'destino' => 'atual' ) );
contem( '<h2 class="rtm-sd-titulo">Sugestões dos especialistas para Gramado</h2>', $h, 'título com o destino da página' );
igual( 6, substr_count( $h, 'class="rtm-sug"' ), 'no máximo 6 sugestões' );
nao_contem( 'De inativa', $h, 'sem sugestão de agente inativo' );
nao_contem( 'Outro destino', $h, 'só do destino da página' );
contem( '<span class="rtm-sug-agente">por Mariana Kloss</span>', $h, 'nome do agente no cartão' );
contem( 'href="https://exemplo.test/especialistas/?destino=gramado"', $h, 'link para os especialistas do destino' );
contem( 'Ver especialistas em Gramado', $h, 'texto do link' );

$GLOBALS['rt_atual'] = 11;
igual( '', rtm_sugestoes_destino_html( array() ), 'destino sem sugestões: nada' );
$GLOBALS['rt_atual'] = 0;
igual( '', rtm_sugestoes_destino_html( array() ), 'fora de uma página de destino: nada' );

// Ensaio: só quem edita vê
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = true;
$GLOBALS['rt_atual'] = 10;
$GLOBALS['rt_pode'] = false;
igual( '', rtm_sugestoes_destino_html( array() ), 'ensaio: visitante não vê' );
$GLOBALS['rt_pode'] = true;
contem( 'rtm-sug', rtm_sugestoes_destino_html( array() ), 'ensaio: quem edita vê' );

// Bloco vazio do Elementor não deixa faixa em branco no destino
ok( in_array( 'rtm_sugestoes_destino_esconder_vazio', $GLOBALS['rt_ganchos']['wp_head'], true ), 'wp_head esconde o bloco vazio' );
ob_start(); rtm_sugestoes_destino_esconder_vazio(); $css = ob_get_clean();
contem( '.e-con.rtm-c-sugestoes-destino:not(:has(.rtm-sd)){display:none}', $css, 'bloco sem sugestões some' );
$GLOBALS['rt_atual'] = 5;
ob_start(); rtm_sugestoes_destino_esconder_vazio(); igual( '', ob_get_clean(), 'fora do destino: nada' );
$GLOBALS['rt_atual'] = 0;

// O cartão do perfil continua sem o nome do agente
nao_contem( 'rtm-sug-agente', rtm_card_sugestao( rtm_sugestao( 20 ) ), 'cartão do perfil sem "por <agente>"' );
