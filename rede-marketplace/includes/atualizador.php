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

/**
 * Última release publicada, em cache por 6 horas.
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
		$release = rtm_token() ? array() : rtm_release_pela_pagina();
		// Sem resposta: guarda vazio por 1h para não bater no GitHub a cada tela do painel.
		set_site_transient( RTM_CACHE, $release, $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
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
		set_site_transient( RTM_CACHE, array(), HOUR_IN_SECONDS );
		return array();
	}

	$release = array(
		'versao' => ltrim( (string) $dados['tag_name'], 'vV' ),
		'zip'    => $zip,
		'notas'  => isset( $dados['body'] ) ? (string) $dados['body'] : '',
		'data'   => isset( $dados['published_at'] ) ? (string) $dados['published_at'] : '',
	);
	set_site_transient( RTM_CACHE, $release, 6 * HOUR_IN_SECONDS );
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

/** Painel › Atualizações › "Verificar novamente" (force-check=1) ignora o cache de 6 h. */
function rtm_forcar_consulta( $get ) {
	return ! empty( $get['force-check'] );
}

/** É aqui que o WordPress descobre que existe versão nova. */
add_filter( 'pre_set_site_transient_update_plugins', function ( $transiente ) {
	if ( ! is_object( $transiente ) ) {
		return $transiente;
	}
	$release = rtm_release( rtm_forcar_consulta( $_GET ) ); // phpcs:ignore -- só lê a flag do próprio WordPress
	if ( empty( $release['versao'] ) ) {
		return $transiente;
	}
	$ficha = rtm_ficha( $release );
	if ( version_compare( $release['versao'], RTM_VERSION, '>' ) ) {
		$transiente->response[ RTM_BASENAME ] = $ficha;
		unset( $transiente->no_update[ RTM_BASENAME ] );
	} else {
		$transiente->no_update[ RTM_BASENAME ] = $ficha;
	}
	return $transiente;
} );

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

/** Depois de atualizar, esquece o cache para a tela já mostrar a versão certa. */
add_action( 'upgrader_process_complete', function ( $upgrader, $extra ) {
	if ( isset( $extra['type'] ) && 'plugin' === $extra['type'] ) {
		delete_site_transient( RTM_CACHE );
	}
}, 10, 2 );
