<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => false );
ok( isset( $GLOBALS['rt_shortcodes']['rt_seja_agente'] ), 'shortcode rt_seja_agente' );
$h = rtm_seja_agente_html();
contem( 'Seja um agente da Rede Turística', $h, 'título' );
contem( 'Cadastur obrigatório', $h, 'exige Cadastur' );
contem( 'href="' . RTM_CADASTRO_PADRAO . '"', $h, 'botão leva ao cadastro padrão' );
$GLOBALS['rt_opcoes']['rtm_opcoes']['url_cadastro'] = 'https://cadastro.exemplo.test/';
contem( 'href="https://cadastro.exemplo.test/"', rtm_seja_agente_html(), 'cadastro configurável' );
igual( 'https://cadastro.exemplo.test/', rtm_limpar_opcoes( array( 'url_cadastro' => 'https://cadastro.exemplo.test/' ) )['url_cadastro'], 'opção salva' );
igual( '', rtm_limpar_opcoes( array( 'url_cadastro' => 'http://inseguro.test' ) )['url_cadastro'], 'só https' );

// Lista vazia: estado amigável
$GLOBALS['rt_posts'] = array();
$v = rtm_lista_html();
contem( 'Estamos reunindo os primeiros agentes', $v, 'lista: estado vazio' );
contem( '/seja-um-agente/', $v, 'lista: link Seja um agente' );
nao_contem( 'rtm-lista-filtros', $v, 'lista: sem filtros quando vazia' );
