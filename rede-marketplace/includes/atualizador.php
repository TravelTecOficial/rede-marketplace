<?php
/**
 * Atualização automática pelo GitHub.
 *
 * O site do cliente pergunta ao GitHub qual é a última versão publicada (release) e,
 * se for maior que a instalada, o WordPress mostra "Atualizar agora" na tela de Plugins
 * como faz com qualquer plugin do repositório oficial.
 *
 * A release precisa ter um anexo chamado exatamente rede-marketplace.zip, com a pasta
 * rede-marketplace/ dentro — é o que o empacotar.py gera. O zipball automático do GitHub
 * não serve: ele vem com o nome do repositório e da tag na pasta raiz.
 *
 * Repositório privado: basta definir no wp-config.php do site
 *     define( 'RTM_GITHUB_TOKEN', 'ghp_...' );
 * com um token de leitura. Em repositório público não precisa de nada.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RTM_REPO', 'TravelTecOficial/rede-marketplace' );
define( 'RTM_ANEXO', 'rede-marketplace.zip' );
define( 'RTM_CACHE', 'rtm_release' );

/** Token opcional, só para repositório privado. */
function rtm_token() {
	return defined( 'RTM_GITHUB_TOKEN' ) && RTM_GITHUB_TOKEN ? RTM_GITHUB_TOKEN : '';
}

/** Guarda o resultado da última consulta ao GitHub (aparece em Configurações › Rede Marketplace). */
function rtm_registrar_consulta( $fonte, $detalhe, $versao ) {
	update_option( 'rtm_atualizacao', array( 'quando' => time(), 'fonte' => $fonte, 'detalhe' => (string) $detalhe, 'versao' => (string) $versao ), false );
}

/**
 * Última release publicada, em cache por 1 hora.
 * Devolve array vazio quando não dá para consultar (sem internet, limite do GitHub, repo privado sem token).
 */
function rtm_release( $forcar = false ) {
	if ( ! $forcar ) {
		$cache = get_site_transient( RTM_CACHE );
		if ( is_array( $cache ) ) {
			return $cache;
		}
	}

	$cabecalhos = array(
		'Accept'     => 'application/vnd.github+json',
		'User-Agent' => 'Rede-Marketplace/' . RTM_VERSION,
	);
	if ( rtm_token() ) {
		$cabecalhos['Authorization'] = 'Bearer ' . rtm_token();
	}

	$resposta = wp_remote_get(
		'https://api.github.com/repos/' . RTM_REPO . '/releases/latest',
		array( 'timeout' => 15, 'headers' => $cabecalhos )
	);

	// A API falhou — em hospedagem compartilhada é quase sempre o limite de 60 consultas/hora por IP, dividido
	// com os outros sites do servidor. Repositório público: descobre a versão pela página (sem limite).
	if ( is_wp_error( $resposta ) || 200 !== (int) wp_remote_retrieve_response_code( $resposta ) ) {
		$motivo  = is_wp_error( $resposta ) ? $resposta->get_error_message() : 'HTTP ' . (int) wp_remote_retrieve_response_code( $resposta );
		$release = rtm_token() ? array() : rtm_release_pela_pagina();
		rtm_registrar_consulta( $release ? 'pagina' : 'falha', 'API: ' . $motivo, $release ? $release['versao'] : '' );
		// Sem resposta: guarda vazio por 15 min para não bater no GitHub a cada tela do painel.
		set_site_transient( RTM_CACHE, $release, $release ? HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
		return $release;
	}

	$dados = json_decode( wp_remote_retrieve_body( $resposta ), true );
	$zip   = '';
	foreach ( (array) ( isset( $dados['assets'] ) ? $dados['assets'] : array() ) as $anexo ) {
		if ( isset( $anexo['name'] ) && RTM_ANEXO === $anexo['name'] ) {
			// Em repo privado o download é pela API, com token; em público, pela URL direta.
			$zip = rtm_token() ? $anexo['url'] : $anexo['browser_download_url'];
			break;
		}
	}
	if ( empty( $dados['tag_name'] ) || ! $zip ) {
		rtm_registrar_consulta( 'falha', 'Release sem o anexo ' . RTM_ANEXO, '' );
		set_site_transient( RTM_CACHE, array(), 15 * MINUTE_IN_SECONDS );
		return array();
	}

	$release = array(
		'versao' => ltrim( (string) $dados['tag_name'], 'vV' ),
		'zip'    => $zip,
		'notas'  => isset( $dados['body'] ) ? (string) $dados['body'] : '',
		'data'   => isset( $dados['published_at'] ) ? (string) $dados['published_at'] : '',
	);
	rtm_registrar_consulta( 'api', 'OK', $release['versao'] );
	set_site_transient( RTM_CACHE, $release, HOUR_IN_SECONDS );
	return $release;
}

/**
 * Última versão sem a API: github.com/<repo>/releases/latest redireciona para .../releases/tag/v<versão>,
 * e o anexo fica em .../releases/download/v<versão>/rede-marketplace.zip.
 */
function rtm_release_pela_pagina() {
	$resposta = wp_remote_head(
		'https://github.com/' . RTM_REPO . '/releases/latest',
		array( 'timeout' => 15, 'redirection' => 0, 'headers' => array( 'User-Agent' => 'Rede-Marketplace/' . RTM_VERSION ) )
	);
	$destino = is_wp_error( $resposta ) ? '' : (string) wp_remote_retrieve_header( $resposta, 'location' );
	if ( ! preg_match( '#/releases/tag/(v?(\d+(?:\.\d+)+))$#', $destino, $m ) ) {
		return array();
	}
	return array(
		'versao' => $m[2],
		'zip'    => 'https://github.com/' . RTM_REPO . '/releases/download/' . $m[1] . '/' . RTM_ANEXO,
		'notas'  => '',
		'data'   => '',
	);
}

/** Download do anexo em repositório privado: a API exige token e Accept de arquivo binário. */
add_filter( 'http_request_args', function ( $args, $url ) {
	$api = 'https://api.github.com/repos/' . RTM_REPO . '/releases/assets/';
	if ( rtm_token() && 0 === strpos( $url, $api ) ) {
		$args['headers']['Authorization'] = 'Bearer ' . rtm_token();
		$args['headers']['Accept']        = 'application/octet-stream';
	}
	return $args;
}, 10, 2 );

/**
 * Cabeçalhos para seguir um redirecionamento. A API (api.github.com) responde ao download do anexo com
 * um redirect para uma URL assinada em *.githubusercontent.com, que recusa o cabeçalho Authorization:
 * o nosso token só segue se o destino ainda for api.github.com. Authorization de outra requisição não é mexido.
 */
function rtm_cabecalhos_redirecionamento( array $cabecalhos, $destino ) {
	$token = rtm_token();
	if ( '' === $token ) {
		return $cabecalhos;
	}
	$host = strtolower( (string) wp_parse_url( (string) $destino, PHP_URL_HOST ) );
	if ( 'api.github.com' === $host ) {
		return $cabecalhos;
	}
	foreach ( $cabecalhos as $nome => $valor ) {
		if ( 'authorization' === strtolower( (string) $nome ) && 'Bearer ' . $token === (string) $valor ) {
			unset( $cabecalhos[ $nome ] );
		}
	}
	return $cabecalhos;
}

/**
 * Gancho do Requests antes de seguir um redirect. O WP_HTTP_Requests_Hooks (WP 4.7+) repassa cada gancho
 * do Requests como a ação requests-<gancho>, com os parâmetros por referência ($location, $headers, ...).
 */
function rtm_antes_do_redirecionamento( $destino, &$cabecalhos ) {
	if ( is_array( $cabecalhos ) ) {
		$cabecalhos = rtm_cabecalhos_redirecionamento( $cabecalhos, $destino );
	}
}
add_action( 'requests-requests.before_redirect', 'rtm_antes_do_redirecionamento', 10, 2 );

/** Ficha do plugin, com as notas da release. */
function rtm_ficha( $release ) {
	return (object) array(
		'id'             => RTM_REPO,
		'slug'           => 'rede-marketplace',
		'plugin'         => RTM_BASENAME,
		'new_version'    => $release['versao'],
		'version'        => $release['versao'],
		'url'            => 'https://github.com/' . RTM_REPO,
		'package'        => $release['zip'],
		'requires_php'   => '7.4',
		'icons'          => array(),
		'banners'        => array(),
		'banners_rtl'    => array(),
		'compatibility'  => new stdClass(),
	);
}

/**
 * Consulta o GitHub sem cache: "Verificar novamente" (force-check=1) e qualquer abertura das telas
 * Painel › Atualizações e Plugins. Assim uma versão publicada há minutos já aparece ali.
 */
function rtm_forcar_consulta( $get, $pagina = '' ) {
	return ! empty( $get['force-check'] ) || in_array( (string) $pagina, array( 'update-core.php', 'plugins.php' ), true );
}

/** Põe (ou tira) este plugin na lista de atualizações do WordPress, conforme a última release. */
function rtm_injetar_atualizacao( $transiente, $forcar = false ) {
	if ( ! is_object( $transiente ) ) {
		return $transiente;
	}
	$release = rtm_release( $forcar );
	if ( empty( $release['versao'] ) ) {
		return $transiente;
	}
	$ficha = rtm_ficha( $release );
	if ( version_compare( $release['versao'], RTM_VERSION, '>' ) ) {
		if ( ! isset( $transiente->response ) || ! is_array( $transiente->response ) ) {
			$transiente->response = array();
		}
		$transiente->response[ RTM_BASENAME ] = $ficha;
		unset( $transiente->no_update[ RTM_BASENAME ] );
	} else {
		unset( $transiente->response[ RTM_BASENAME ] );
		$transiente->no_update[ RTM_BASENAME ] = $ficha;
	}
	return $transiente;
}

/** É aqui que o WordPress descobre que existe versão nova (quando ele mesmo refaz a checagem). */
add_filter( 'pre_set_site_transient_update_plugins', function ( $transiente ) {
	return rtm_injetar_atualizacao( $transiente, rtm_forcar_consulta( $_GET, isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '' ) ); // phpcs:ignore -- só lê a flag do próprio WordPress
} );

/**
 * Também na leitura: a tela Plugins só refaz a checagem do WordPress de hora em hora, então uma release
 * publicada há minutos só aparecia em Painel › Atualizações. Aqui ela vale em qualquer tela (cache de 1 hora).
 */
function rtm_ler_atualizacao( $transiente ) {
	return rtm_injetar_atualizacao( $transiente, false );
}
add_filter( 'site_transient_update_plugins', 'rtm_ler_atualizacao' );

/** Ao abrir a tela Plugins, pergunta de novo ao GitHub (no máximo a cada 2 minutos). */
function rtm_tela_plugins() {
	if ( ! get_site_transient( 'rtm_release_tela' ) ) {
		set_site_transient( 'rtm_release_tela', 1, 2 * MINUTE_IN_SECONDS );
		delete_site_transient( RTM_CACHE );
	}
}
add_action( 'load-plugins.php', 'rtm_tela_plugins' );

/** Janela "Ver detalhes da versão". */
add_filter( 'plugins_api', function ( $resultado, $acao, $args ) {
	if ( 'plugin_information' !== $acao || empty( $args->slug ) || 'rede-marketplace' !== $args->slug ) {
		return $resultado;
	}
	$release = rtm_release();
	if ( empty( $release['versao'] ) ) {
		return $resultado;
	}
	$ficha                = rtm_ficha( $release );
	$ficha->name          = 'Rede Marketplace';
	$ficha->author        = 'TravelTec';
	$ficha->homepage      = $ficha->url;
	$ficha->download_link = $release['zip'];
	$ficha->last_updated  = $release['data'];
	$ficha->sections      = array(
		'description' => 'Marketplace de agentes de viagens da Rede Turística.',
		'changelog'   => wpautop( esc_html( $release['notas'] ) ),
	);
	return $ficha;
}, 10, 3 );

/**
 * Atualização automática: o próprio WordPress instala a versão nova (rotina de atualizações, ~2 vezes por dia),
 * sem ninguém clicar. Vale só para este plugin.
 */
function rtm_auto_atualizar( $atualizar, $item ) {
	if ( is_object( $item ) && isset( $item->plugin ) && RTM_BASENAME === $item->plugin ) {
		return true;
	}
	return $atualizar;
}
add_filter( 'auto_update_plugin', 'rtm_auto_atualizar', 10, 2 );

/** Esquece o cache e pergunta de novo ao GitHub e ao WordPress agora. */
function rtm_procurar_agora() {
	delete_site_transient( RTM_CACHE );
	delete_site_transient( 'update_plugins' );
	if ( function_exists( 'wp_update_plugins' ) ) {
		wp_update_plugins();
	}
	return rtm_release();
}

/** Botão "Procurar atualização agora" (admin-post.php, nonce + update_plugins). */
function rtm_acao_procurar() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'rtm_procurar_atualizacao' );
	rtm_procurar_agora();
	wp_safe_redirect( admin_url( 'options-general.php?page=rede-marketplace&rtm_procurou=1' ) );
	exit;
}
add_action( 'admin_post_rtm_procurar_atualizacao', 'rtm_acao_procurar' );

/** Quadro "Atualizações" na tela de configurações. */
function rtm_quadro_atualizacao() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}
	$u      = get_option( 'rtm_atualizacao', array() );
	$u      = is_array( $u ) ? $u : array();
	$ultima = isset( $u['versao'] ) ? (string) $u['versao'] : '';
	echo '<hr><h2>Atualizações</h2>';
	if ( ! empty( $_GET['rtm_procurou'] ) ) {
		echo '<div class="notice notice-info"><p>Consulta ao GitHub feita agora.</p></div>';
	}
	echo '<p>Versão instalada: <strong>' . esc_html( RTM_VERSION ) . '</strong>';
	if ( '' !== $ultima ) {
		echo ' · Última no GitHub: <strong>' . esc_html( $ultima ) . '</strong>';
	}
	echo '</p>';
	if ( ! empty( $u['quando'] ) ) {
		$fontes = array( 'api' => 'API do GitHub', 'pagina' => 'página do GitHub (API indisponível)', 'falha' => 'não foi possível consultar' );
		$fonte  = isset( $fontes[ $u['fonte'] ] ) ? $fontes[ $u['fonte'] ] : '';
		echo '<p class="description">Última consulta: ' . esc_html( wp_date( 'd/m/Y H:i', (int) $u['quando'] ) ) . ' · ' . esc_html( $fonte ) . ( isset( $u['detalhe'] ) && 'OK' !== $u['detalhe'] ? ' · ' . esc_html( $u['detalhe'] ) : '' ) . '</p>';
	}
	if ( '' !== $ultima && version_compare( $ultima, RTM_VERSION, '>' ) ) {
		echo '<p>Há versão nova. O WordPress instala sozinho em algumas horas, ou agora em <a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">Painel › Atualizações</a>.</p>';
	} else {
		echo '<p class="description">As versões novas são instaladas automaticamente pelo WordPress.</p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rtm_procurar_atualizacao">';
	wp_nonce_field( 'rtm_procurar_atualizacao', '_wpnonce', false );
	echo '<button type="submit" class="button">Procurar atualização agora</button></form>';
}
add_action( 'rtm_configuracoes_extra', 'rtm_quadro_atualizacao', 5 );

/** Depois de atualizar, esquece o cache para a tela já mostrar a versão certa. */
add_action( 'upgrader_process_complete', function ( $upgrader, $extra ) {
	if ( isset( $extra['type'] ) && 'plugin' === $extra['type'] ) {
		delete_site_transient( RTM_CACHE );
	}
}, 10, 2 );
