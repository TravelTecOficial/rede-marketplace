<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $meta = array() ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => '' );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
rtm_t_p( 5, 'Mariana Kloss', array( 'ativo' => '1', 'cargo' => 'Especialista em Gramado e Serra Gaúcha', 'cadastur_conferido_em' => '2026-11-02', 'foto_url_teste' => 'https://exemplo.test/m.svg', 'teste' => '1' ) );
rtm_t_p( 6, 'Rafael Antunes', array( 'ativo' => '1', 'cargo' => 'Lua de mel' ) );
rtm_t_p( 7, 'Inativa Silva', array( 'ativo' => '0' ) );
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => false, 'url_cotacao' => 'https://exemplo.test/cotacao-de-viagens/' );

ok( isset( $GLOBALS['rt_shortcodes']['rt_especialistas'] ), 'shortcode rt_especialistas' );
$GLOBALS['rt_estilos'] = array(); $GLOBALS['rt_scripts'] = array();
$h = rtm_carrossel_html();
ok( isset( $GLOBALS['rt_estilos']['rede-marketplace'] ) && isset( $GLOBALS['rt_scripts']['rtm-carrossel'] ), 'carrega CSS e JS do carrossel' );
contem( '<h2 class="rtm-car-titulo">Conheça nossos especialistas</h2>', $h, 'título' );
contem( 'Agentes certificados que montam sua viagem', $h, 'texto curto' );
igual( 2, substr_count( $h, 'class="rtm-car-card"' ), 'só agentes ativos (2)' );
contem( 'Cadastur verificado', $h, 'selo Cadastur de quem foi conferido' );
contem( 'Especialista em Gramado e Serra Gaúcha', $h, 'especialidade (cargo)' );
contem( '★★★★★', $h, 'nota quando existe (exemplo do agente de teste)' );
contem( '>Ver perfil</a>', $h, 'botão Ver perfil' );
contem( 'href="https://exemplo.test/cotacao-de-viagens/"', $h, 'botão para a cotação' );
contem( 'Pedir cotação com especialistas', $h, 'texto do botão da cotação' );
contem( 'aria-roledescription="carrossel"', $h, 'acessível como carrossel' );
contem( 'class="rtm-car-ant"', $h, 'seta anterior' );
contem( 'class="rtm-car-prox"', $h, 'seta próxima' );

// Ensaio: só quem está logado no painel vê
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = true;
$GLOBALS['rt_pode'] = false;
igual( '', rtm_carrossel_html(), 'ensaio: visitante não vê o carrossel' );
$GLOBALS['rt_pode'] = true;
contem( 'rtm-car-card', rtm_carrossel_html(), 'ensaio: logado vê' );
contem( 'Visível só para você (modo ensaio)', rtm_carrossel_html(), 'ensaio: aviso para o logado' );

// Sem agentes ativos: nada
$GLOBALS['rt_meta'][5]['rtm_ativo'] = '0'; $GLOBALS['rt_meta'][6]['rtm_ativo'] = '0';
igual( '', rtm_carrossel_html(), 'sem agentes: nada' );

// Visitante no ensaio: o bloco do Elementor fica vazio e não pode deixar faixa em branco na home
ok( in_array( 'rtm_carrossel_esconder_vazio', $GLOBALS['rt_ganchos']['wp_head'], true ), 'wp_head esconde o bloco vazio' );
$GLOBALS['rt_inicio'] = true;
ob_start(); rtm_carrossel_esconder_vazio(); $css = ob_get_clean();
contem( '.e-con.rtm-c-carrossel:not(:has(.rtm-carrossel)){display:none}', $css, 'bloco sem carrossel some' );
$GLOBALS['rt_inicio'] = false;
ob_start(); rtm_carrossel_esconder_vazio(); igual( '', ob_get_clean(), 'fora da home: nada' );
contem( '.elementor .e-con.rtm-c-carrossel{', file_get_contents( '/p/assets/rede-marketplace.css' ), 'bloco do carrossel sem espaçamento do Elementor' );

