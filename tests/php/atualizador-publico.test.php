<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

// Atualização automática só para este plugin
ok( true === rtm_auto_atualizar( false, (object) array( 'plugin' => RTM_BASENAME ) ), 'auto_update_plugin: liga para o Rede Marketplace' );
ok( false === rtm_auto_atualizar( false, (object) array( 'plugin' => 'outro/outro.php' ) ), 'auto_update_plugin: não mexe nos outros plugins' );
ok( null === rtm_auto_atualizar( null, (object) array( 'plugin' => 'outro/outro.php' ) ), 'auto_update_plugin: devolve a escolha original' );
ok( in_array( 'rtm_auto_atualizar', $GLOBALS['rt_ganchos']['auto_update_plugin'], true ), 'filtro auto_update_plugin registrado' );

// API OK: versão, zip direto e registro da consulta
resposta_falsa( 200, array( 'tag_name' => 'v9.1.0', 'assets' => array( array( 'name' => 'rede-marketplace.zip', 'browser_download_url' => 'https://github.com/x/rede-marketplace.zip', 'url' => 'https://api.github.com/x' ) ) ) );
$r = rtm_release( true );
igual( '9.1.0', $r['versao'], 'API: versão sem o v' );
igual( 'https://github.com/x/rede-marketplace.zip', $r['zip'], 'repo público: zip pela URL direta' );
$u = get_option( 'rtm_atualizacao' );
igual( array( 'api', 'OK', '9.1.0' ), array( $u['fonte'], $u['detalhe'], $u['versao'] ), 'API: consulta registrada' );
igual( HOUR_IN_SECONDS, $GLOBALS['rt_transients'][ RTM_CACHE ][1], 'cache de 1 hora' );

// API limitada (403): descobre pela página
resposta_falsa( 403, '{}' );
resposta_falsa( 302, '', array( 'location' => 'https://github.com/TravelTecOficial/rede-marketplace/releases/tag/v9.2.0' ) );
$r = rtm_release( true );
igual( '9.2.0', $r['versao'], 'API 403: versão pela página' );
$u = get_option( 'rtm_atualizacao' );
igual( array( 'pagina', 'API: HTTP 403', '9.2.0' ), array( $u['fonte'], $u['detalhe'], $u['versao'] ), 'API 403: registro mostra o motivo' );

// Tudo falha: vazio por 15 min e registro de falha
resposta_falsa( 500, '{}' );
resposta_falsa( 404, '' );
igual( array(), rtm_release( true ), 'sem GitHub: vazio' );
igual( 15 * MINUTE_IN_SECONDS, $GLOBALS['rt_transients'][ RTM_CACHE ][1], 'falha: tenta de novo em 15 min' );
igual( 'falha', get_option( 'rtm_atualizacao' )['fonte'], 'falha registrada' );

// Cache usado quando não força
$GLOBALS['rt_http_log'] = array();
rtm_release();
igual( 0, count( $GLOBALS['rt_http_log'] ), 'com cache: não consulta o GitHub' );

// Quadro na tela de configurações
update_option( 'rtm_atualizacao', array( 'quando' => 1762250000, 'fonte' => 'pagina', 'detalhe' => 'API: HTTP 403', 'versao' => '9.2.0' ) );
ob_start(); rtm_quadro_atualizacao(); $q = ob_get_clean();
contem( 'Versão instalada: <strong>' . RTM_VERSION . '</strong>', $q, 'quadro: versão instalada' );
contem( 'Última no GitHub: <strong>9.2.0</strong>', $q, 'quadro: última no GitHub' );
contem( 'API: HTTP 403', $q, 'quadro: motivo da falha da API' );
contem( 'Há versão nova', $q, 'quadro: avisa versão nova' );
contem( 'value="rtm_procurar_atualizacao"', $q, 'quadro: botão procurar' );
contem( 'name="_wpnonce"', $q, 'quadro: nonce' );
ok( in_array( 'rtm_quadro_atualizacao', $GLOBALS['rt_ganchos']['rtm_configuracoes_extra'], true ), 'quadro ligado à tela de configurações' );
ok( in_array( 'rtm_acao_procurar', $GLOBALS['rt_ganchos']['admin_post_rtm_procurar_atualizacao'], true ), 'ação procurar registrada' );

