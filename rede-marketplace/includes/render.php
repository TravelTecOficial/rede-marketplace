<?php
/** HTML público do agente: seções da página e card da cotação. Só usa os dados de rtm_agente(). */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Link da cotação já com o destino principal e o agente: `?destino=<slug>&agente=<slug>`. */
function rtm_url_cotacao( array $agente ) {
	$base = (string) rtm_opcoes()['url_cotacao'];
	$q    = array();
	if ( ! empty( $agente['destino_principal'] ) ) {
		$q[] = 'destino=' . rawurlencode( (string) $agente['destino_principal'] );
	}
	$q[] = 'agente=' . rawurlencode( (string) $agente['slug'] );
	return $base . ( false === strpos( $base, '?' ) ? '?' : '&' ) . implode( '&', $q );
}

function rtm_primeiro_nome( array $agente ) {
	$p = preg_split( '/\s+/u', trim( (string) $agente['nome'] ), -1, PREG_SPLIT_NO_EMPTY );
	return $p ? $p[0] : '';
}

/** Foto do agente (img) ou bloco com as iniciais. */
function rtm_html_foto( array $agente, $classe ) {
	$url = esc_url( (string) $agente['foto'] );
	if ( '' !== $url ) {
		$img = '<img src="' . $url . '" alt="' . esc_attr( 'Foto de ' . $agente['nome'] ) . '" loading="lazy" decoding="async">';
		return '<div class="' . esc_attr( $classe ) . '">' . $img . '</div>';
	}
	return '<div class="' . esc_attr( $classe ) . ' rtm-sem-foto"><span class="rtm-iniciais" aria-hidden="true">' . esc_html( $agente['iniciais'] ) . '</span></div>';
}

/** Bloco da agência (logo ou iniciais + nome + cidade/desde). '' sem nome da agência. */
function rtm_html_agencia( array $agente, $com_desde ) {
	if ( '' === trim( (string) $agente['agencia_nome'] ) ) {
		return '';
	}
	$url  = esc_url( (string) $agente['logo'] );
	$logo = '' !== $url
		? '<img class="rtm-logo" src="' . $url . '" alt="" loading="lazy" decoding="async">'
		: '<span class="rtm-logo" aria-hidden="true">' . esc_html( $agente['agencia_iniciais'] ) . '</span>';
	$info = array();
	if ( '' !== trim( (string) $agente['agencia_cidade'] ) ) {
		$info[] = esc_html( $agente['agencia_cidade'] );
	}
	if ( $com_desde && (int) $agente['agencia_desde'] > 0 ) {
		$info[] = 'desde ' . (int) $agente['agencia_desde'];
	}
	return '<div class="rtm-agencia">' . $logo . '<span><strong>' . esc_html( $agente['agencia_nome'] ) . '</strong>' . implode( ' · ', $info ) . '</span></div>';
}

/** Selo "Cadastur verificado" — só quando a data de conferência existe. */
function rtm_html_cadastur( array $agente, $com_numero ) {
	if ( empty( $agente['cadastur_verificado'] ) ) {
		return '';
	}
	$txt = 'Cadastur verificado';
	if ( $com_numero && '' !== trim( (string) $agente['cadastur'] ) ) {
		$txt .= ' · ' . $agente['cadastur'];
	}
	return '<span class="rtm-cadastur"><i aria-hidden="true">✓</i>' . esc_html( $txt ) . '</span>';
}

function rtm_html_chips( array $itens ) {
	$h = '';
	foreach ( $itens as $t ) {
		if ( '' !== trim( (string) $t ) ) {
			$h .= '<span class="rtm-chip">' . esc_html( $t ) . '</span>';
		}
	}
	return $h;
}

/** HTML de uma seção da página do agente; '' quando não há dado para mostrar. */
function rtm_secao( $secao, array $agente ) {
	$nome  = rtm_primeiro_nome( $agente );
	$url   = esc_url( rtm_url_cotacao( $agente ) );
	$botao = '<a class="rtm-btn" href="' . $url . '">' . esc_html( 'Pedir cotação com ' . $nome ) . '</a>';

	switch ( $secao ) {
		case 'topo':
			$h  = '<section class="rtm-topo">' . rtm_html_foto( $agente, 'rtm-foto' ) . '<div class="rtm-dados">';
			$h .= rtm_html_cadastur( $agente, true );
			$h .= '<h1 class="rtm-nome">' . esc_html( $agente['nome'] ) . '</h1>';
			if ( '' !== trim( (string) $agente['cargo'] ) ) {
				$h .= '<p class="rtm-cargo">' . esc_html( $agente['cargo'] ) . '</p>';
			}
			$chips = rtm_html_chips( $agente['tipos'] );
			if ( '' !== $chips ) {
				$h .= '<div class="rtm-chips">' . $chips . '</div>';
			}
			$num  = '';
			$anos = (int) $agente['anos_experiencia'];
			if ( $anos > 0 ) {
				$num .= '<div><b>' . $anos . ( 1 === $anos ? ' ano' : ' anos' ) . '</b><span>de experiência</span></div>';
			}
			$viaj = (int) $agente['viajantes_atendidos'];
			if ( $viaj > 0 ) {
				$num .= '<div><b>+' . number_format( $viaj, 0, ',', '.' ) . '</b><span>viajantes atendidos</span></div>';
			}
			$idiomas = array_values( array_filter( array_map( 'trim', array_map( 'strval', $agente['idiomas'] ) ), 'strlen' ) );
			if ( $idiomas ) {
				$num .= '<div><b>' . esc_html( implode( ' · ', $idiomas ) ) . '</b><span>' . ( count( $idiomas ) > 1 ? 'idiomas' : 'idioma' ) . '</span></div>';
			}
			if ( '' !== $num ) {
				$h .= '<div class="rtm-numeros">' . $num . '</div>';
			}
			$h .= '<div class="rtm-acoes">' . $botao . '</div>';
			$h .= rtm_html_agencia( $agente, true );
			return $h . '</div></section>';

		case 'sobre':
			$texto = trim( (string) $agente['apresentacao'] );
			if ( '' === $texto ) {
				return '';
			}
			return '<section class="rtm-bloco rtm-sobre"><h2>' . esc_html( 'Sobre ' . $nome ) . '</h2>' . wp_kses_post( wpautop( $texto ) ) . '</section>';

		case 'especialidades':
			$esp = '';
			foreach ( rtm_especialidades_normalizar( $agente['especialidades'] ) as $item ) {
				$esp .= '<div class="rtm-esp"><b class="rtm-esp-titulo">' . esc_html( $item['titulo'] ) . '</b>'
					. ( '' !== $item['descricao'] ? '<span class="rtm-esp-desc">' . esc_html( $item['descricao'] ) . '</span>' : '' ) . '</div>';
			}
			$nomes = array();
			foreach ( $agente['destinos'] as $d ) {
				$titulo = is_array( $d ) && ! empty( $d['id'] ) ? get_the_title( (int) $d['id'] ) : '';
				if ( '' !== (string) $titulo ) {
					$nomes[] = $titulo;
				}
			}
			$dest = rtm_html_chips( $nomes );
			if ( '' === $esp && '' === $dest ) {
				return '';
			}
			$h = '<section class="rtm-bloco rtm-especialidades"><h2>' . ( '' !== $esp ? 'Especialidades' : 'Destinos' ) . '</h2>';
			if ( '' !== $esp ) {
				$h .= '<div class="rtm-lista-esp">' . $esp . '</div>';
			}
			if ( '' !== $dest ) {
				$h .= '<div class="rtm-destinos">' . $dest . '</div>';
			}
			return $h . '</section>';

		case 'onde':
			$itens = '';
			if ( '' !== trim( (string) $agente['endereco'] ) ) {
				$itens .= '<div><b>Endereço</b>' . esc_html( $agente['endereco'] ) . '</div>';
			}
			if ( '' !== trim( (string) $agente['horario'] ) ) {
				$itens .= '<div><b>Horário</b>' . esc_html( $agente['horario'] ) . '</div>';
			}
			if ( '' === $itens ) {
				return '';
			}
			return '<section class="rtm-bloco rtm-onde"><h2>Onde atende</h2><div class="rtm-endereco">' . $itens . '</div></section>';

		case 'cotacao':
			return '<aside class="rtm-lateral"><div class="rtm-bloco">'
				. '<h3>' . esc_html( 'Peça uma cotação com ' . $nome ) . '</h3>'
				. '<p>Você conta sua viagem e recebe uma proposta pelo WhatsApp. Sem custo.</p>'
				. '<ul><li>Resposta em até 2h úteis</li><li>Pode incluir mais 2 especialistas</li><li>Sem compromisso</li></ul>'
				. '<a class="rtm-btn" href="' . $url . '">Pedir cotação</a>'
				. '</div></aside>';

		case 'barra':
			return '<div class="rtm-barra-espaco" aria-hidden="true"></div><div class="rtm-barra"><div class="rtm-barra-in">' . $botao . '</div></div>';
	}
	return '';
}

/** Card horizontal do agente na cotação. Atributos data-* só públicos: id, slug, destinos, tipos. */
function rtm_card( array $agente ) {
	$h  = '<div class="rtm-card" role="button" tabindex="0" aria-pressed="false"';
	$h .= ' data-id="' . (int) $agente['id'] . '"';
	$h .= ' data-slug="' . esc_attr( $agente['slug'] ) . '"';
	$h .= ' data-destinos="' . esc_attr( implode( ' ', $agente['destinos_slugs'] ) ) . '"';
	$h .= ' data-tipos="' . esc_attr( implode( '|', $agente['tipos'] ) ) . '">';
	$h .= rtm_html_foto( $agente, 'rtm-card-foto' );
	$h .= '<span class="rtm-selo" aria-hidden="true">✓</span><div class="rtm-corpo">';
	$h .= rtm_html_cadastur( $agente, false );
	$h .= '<h3 class="rtm-card-nome">' . esc_html( $agente['nome'] ) . '</h3>';
	$chips = rtm_html_chips( $agente['tipos'] );
	if ( '' !== $chips ) {
		$h .= '<div class="rtm-chips">' . $chips . '</div>';
	}
	if ( '' !== trim( (string) $agente['texto_card'] ) ) {
		$h .= '<p class="rtm-bio">' . esc_html( $agente['texto_card'] ) . '</p>';
	}
	$h .= '<div class="rtm-rodape">' . rtm_html_agencia( $agente, false );
	$h .= '<a class="rtm-perfil" href="' . esc_url( (string) $agente['url'] ) . '">Ver perfil</a></div>';
	return $h . '</div></div>';
}

/** A página atual tem o shortcode [rt_cotacao (no conteúdo ou nos dados do Elementor)? */
function rtm_pagina_tem_cotacao() {
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	$p  = get_post( $id );
	if ( ! $p ) {
		return false;
	}
	if ( isset( $p->post_content ) && false !== strpos( (string) $p->post_content, '[rt_cotacao' ) ) {
		return true;
	}
	$dados = get_post_meta( $id, '_elementor_data', true );
	return is_string( $dados ) && false !== strpos( $dados, '[rt_cotacao' );
}

/** CSS só nas páginas do agente e na página da cotação. */
function rtm_estilos() {
	if ( is_singular( 'rt_agente' ) || rtm_pagina_tem_cotacao() ) {
		wp_enqueue_style( 'rede-marketplace', RTM_URL . 'assets/rede-marketplace.css', array(), RTM_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'rtm_estilos' );
