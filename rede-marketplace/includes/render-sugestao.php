<?php
/** HTML das sugestões: cartões no perfil do agente e página da sugestão. Tudo escapado aqui. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cartão da sugestão (lateral do perfil, "Outras sugestões" e página de Destino, esta com o nome do agente). */
function rtm_card_sugestao( array $s, $com_agente = false ) {
	$h = '<a class="rtm-sug" href="' . esc_url( $s['url'] ) . '">';
	if ( '' !== $s['imagem'] ) {
		$h .= '<span class="rtm-sug-img"><img src="' . esc_url( $s['imagem'] ) . '" alt="" loading="lazy" decoding="async">'
			. ( '' !== (string) $s['tipo'] ? '<span class="rtm-sug-tag">' . esc_html( $s['tipo'] ) . '</span>' : '' ) . '</span>';
	}
	$h .= '<span class="rtm-sug-corpo">';
	if ( '' === $s['imagem'] && '' !== (string) $s['tipo'] ) {
		$h .= '<span class="rtm-sug-tag rtm-sug-tag-solta">' . esc_html( $s['tipo'] ) . '</span>';
	}
	$h .= '<b class="rtm-sug-titulo">' . esc_html( $s['titulo'] ) . '</b>';
	if ( $com_agente ) {
		$h .= '<span class="rtm-sug-agente">' . esc_html( 'por ' . $s['agente']['nome'] ) . '</span>';
	}
	if ( '' !== trim( (string) $s['texto_card'] ) ) {
		$h .= '<span class="rtm-sug-texto">' . esc_html( $s['texto_card'] ) . '</span>';
	}
	$preco = rtm_preco_txt( $s['preco'] );
	if ( '' !== $preco ) {
		$h .= '<span class="rtm-sug-preco">a partir de <strong>' . esc_html( $preco ) . '</strong> por pessoa</span>';
	}
	return $h . '<span class="rtm-sug-ver">Ver sugestão</span></span></a>';
}

/** Bloco "Sugestões de <nome>"; $onde = 'lateral' (desktop) ou 'celular'. '' sem sugestões. */
function rtm_html_sugestoes( array $agente, $onde ) {
	$lista = rtm_sugestoes_do_agente( (int) $agente['id'] );
	if ( ! $lista ) {
		return '';
	}
	$nome  = rtm_primeiro_nome( $agente );
	$cards = '';
	foreach ( $lista as $s ) {
		$cards .= rtm_card_sugestao( $s );
	}
	$classe = 'celular' === $onde ? 'rtm-sugestoes rtm-sugestoes-celular rtm-bloco' : 'rtm-sugestoes rtm-sugestoes-lateral';
	return '<section class="' . $classe . '"><div class="rtm-sugs-cab"><h2 class="rtm-sugs-titulo">' . esc_html( 'Sugestões de ' . $nome ) . '</h2>'
		. '<p class="rtm-sugs-sub">' . esc_html( 'Viagens que ' . $nome . ' costuma montar' ) . '</p></div>'
		. '<div class="rtm-sugs-lista">' . $cards . '</div></section>';
}

/** Cotação com destino, agente e sugestão marcados. */
function rtm_url_cotacao_sugestao( array $s ) {
	$base = (string) rtm_opcoes()['url_cotacao'];
	$dest = '' !== (string) $s['destino'] ? (string) $s['destino'] : (string) $s['agente']['destino_principal'];
	$q    = array();
	if ( '' !== $dest ) {
		$q[] = 'destino=' . rawurlencode( $dest );
	}
	$q[] = 'agente=' . rawurlencode( (string) $s['agente']['slug'] );
	$q[] = 'sugestao=' . rawurlencode( (string) $s['slug'] );
	return $base . ( false === strpos( $base, '?' ) ? '?' : '&' ) . implode( '&', $q );
}

/** Galeria: foto grande + 2 menores ("+N fotos") e o visualizador (assets/sugestao.js). '' sem fotos. */
function rtm_html_galeria( array $s ) {
	$fotos = $s['fotos'];
	if ( ! $fotos ) {
		return '';
	}
	$n = count( $fotos );
	$h = '<div class="rtm-galeria rtm-galeria-' . min( $n, 3 ) . '" data-fotos="' . (int) $n . '" data-lista="' . esc_attr( wp_json_encode( array_values( $fotos ) ) ) . '">';
	foreach ( array_slice( $fotos, 0, 3 ) as $i => $u ) {
		$h .= '<button type="button" class="rtm-galeria-foto" data-i="' . (int) $i . '" aria-label="' . esc_attr( 'Ver foto ' . ( $i + 1 ) . ' de ' . $n ) . '"><img src="' . esc_url( $u ) . '" alt="" decoding="async"' . ( $i ? ' loading="lazy"' : '' ) . '>';
		if ( 2 === $i && $n > 3 ) {
			$h .= '<span class="rtm-galeria-mais">+' . (int) ( $n - 3 ) . ' fotos</span>';
		}
		$h .= '</button>';
	}
	$h .= '</div>';
	$h .= '<dialog class="rtm-galeria-dialogo" aria-label="' . esc_attr( 'Fotos: ' . $s['titulo'] ) . '"><div class="rtm-galeria-caixa">'
		. '<img class="rtm-galeria-grande" alt="">'
		. '<button type="button" class="rtm-galeria-ant" aria-label="Foto anterior">‹</button>'
		. '<button type="button" class="rtm-galeria-prox" aria-label="Próxima foto">›</button>'
		. '<button type="button" class="rtm-galeria-fechar" aria-label="Fechar">×</button>'
		. '<span class="rtm-galeria-conta" aria-live="polite"></span></div></dialog>';
	return $h;
}

/** Página completa da sugestão (layout da prévia aprovada em 04/11/2026). */
function rtm_pagina_sugestao_html( array $s ) {
	$a     = $s['agente'];
	$nome  = rtm_primeiro_nome( $a );
	$cot   = esc_url( rtm_url_cotacao_sugestao( $s ) );
	$botao = '<a class="rtm-btn" href="' . $cot . '">Quero uma cotação dessa sugestão</a>';

	$h  = '<nav class="rtm-trilha" aria-label="Trilha de navegação"><a class="rtm-trilha-link" href="' . esc_url( home_url( '/' ) ) . '">Rede Turística</a>'
		. ' <span class="rtm-trilha-sep" aria-hidden="true">›</span> <a class="rtm-trilha-link" href="' . esc_url( rtm_url_especialistas() ) . '">Especialistas</a>'
		. ' <span class="rtm-trilha-sep" aria-hidden="true">›</span> <a class="rtm-trilha-link" href="' . esc_url( $a['url'] ) . '">' . esc_html( $a['nome'] ) . '</a>'
		. ' <span class="rtm-trilha-sep" aria-hidden="true">›</span> <span aria-current="page">' . esc_html( $s['titulo'] ) . '</span></nav>';
	$h .= rtm_html_galeria( $s );
	$h .= '<div class="rtm-grade rtm-sug-grade"><div class="rtm-principal">';

	$chips = rtm_html_chips( array( (string) $s['destinos_texto'], (string) $s['tipo'] ) );
	$h    .= '<header class="rtm-sug-cab">' . ( '' !== $chips ? '<div class="rtm-chips">' . $chips . '</div>' : '' )
		. '<h1 class="rtm-sug-h1">' . esc_html( $s['titulo'] ) . '</h1>'
		. ( '' !== trim( (string) $s['resumo'] ) ? '<p class="rtm-sug-lead">' . esc_html( $s['resumo'] ) . '</p>' : '' ) . '</header>';

	$fatos = '';
	foreach ( array( 'duracao' => 'Duração', 'ideal_para' => 'Ideal para', 'melhor_epoca' => 'Melhor época', 'saida' => 'Saída' ) as $c => $rot ) {
		if ( '' !== trim( (string) $s[ $c ] ) ) {
			$fatos .= '<div><small>' . esc_html( $rot ) . '</small><b>' . esc_html( $s[ $c ] ) . '</b></div>';
		}
	}
	if ( '' !== $fatos ) {
		$h .= '<div class="rtm-fatos">' . $fatos . '</div>';
	}
	if ( $s['inclui'] ) {
		$h .= '<section class="rtm-bloco rtm-sug-inclui"><h2>O que inclui</h2><ul class="rtm-inclui">';
		foreach ( $s['inclui'] as $item ) {
			$h .= '<li>' . esc_html( $item ) . '</li>';
		}
		$h .= '</ul></section>';
	}
	if ( $s['dias'] ) {
		$h .= '<section class="rtm-bloco rtm-sug-roteiro"><h2>Roteiro sugerido</h2><ol class="rtm-dias">';
		foreach ( array_values( $s['dias'] ) as $i => $d ) {
			$h .= '<li><span class="rtm-dia-n">Dia ' . (int) ( $i + 1 ) . '</span><div><b>' . esc_html( $d['titulo'] ) . '</b>'
				. ( '' !== trim( (string) $d['descricao'] ) ? '<span>' . esc_html( $d['descricao'] ) . '</span>' : '' ) . '</div></li>';
		}
		$h .= '</ol><p class="rtm-obs">' . esc_html( $nome . ' ajusta hotel, dias e passeios ao seu jeito na cotação.' ) . '</p></section>';
	}
	$outras = '';
	foreach ( rtm_sugestoes_do_agente( (int) $a['id'] ) as $o ) {
		if ( (int) $o['id'] !== (int) $s['id'] ) {
			$outras .= rtm_card_sugestao( $o );
		}
	}
	if ( '' !== $outras ) {
		$h .= '<section class="rtm-sug-outras"><h2>' . esc_html( 'Outras sugestões de ' . $nome ) . '</h2><div class="rtm-outras">' . $outras . '</div></section>';
	}
	$h .= '</div>';

	// Lateral: preço de referência, botão e mini perfil do agente.
	$preco = rtm_preco_txt( $s['preco'] );
	$h    .= '<aside class="rtm-lateral rtm-sug-lateral"><div class="rtm-bloco">';
	if ( '' !== $preco ) {
		$h .= '<div class="rtm-preco-ref"><small>A partir de</small><b>' . esc_html( $preco ) . '</b><small>por pessoa · referência; o valor final vem na cotação</small></div>';
	}
	$h .= $botao . '<div class="rtm-mini-agente">' . rtm_html_foto( $a, 'rtm-mini-foto' ) . '<div>' . rtm_html_cadastur( $a, false )
		. '<b>' . esc_html( $a['nome'] ) . '</b><small>' . esc_html( (string) $a['agencia_nome'] ) . '</small></div></div>'
		. '<a class="rtm-btn-sec" href="' . esc_url( $a['url'] ) . '">' . esc_html( 'Ver perfil de ' . $nome ) . '</a></div></aside>';
	$h .= '</div>';
	$h .= '<div class="rtm-barra-espaco" aria-hidden="true"></div><div class="rtm-barra"><div class="rtm-barra-in">' . $botao . '</div></div>';
	return $h;
}

/** Sugestões publicadas (de agentes ativos) para um destino, por ordem e título; no máximo $max. */
function rtm_sugestoes_do_destino( $slug, $max = 6 ) {
	$res = array();
	foreach ( get_posts( array( 'post_type' => 'rt_sugestao', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => 'rtm_destino', 'meta_value' => (string) $slug ) ) as $p ) {
		$s = rtm_sugestao( $p->ID );
		if ( $s ) {
			$res[] = $s;
		}
	}
	usort( $res, function ( $a, $b ) {
		$o = (int) $a['ordem'] - (int) $b['ordem'];
		return 0 !== $o ? $o : strcmp( $a['titulo'], $b['titulo'] );
	} );
	return array_slice( $res, 0, (int) $max );
}

/**
 * [rt_sugestoes_destino destino="atual"]: "Sugestões dos especialistas para <destino>" na página de Destino
 * (modelo #5968 da Rede, antes da cotação). No ensaio, só quem edita o site vê.
 */
function rtm_sugestoes_destino_html( $atts = array() ) {
	$atts = shortcode_atts( array( 'destino' => 'atual' ), $atts, 'rt_sugestoes_destino' );
	if ( ! rtm_mostra_agentes_publico() ) {
		return '';
	}
	$fixo = rtm_cotacao_destino_fixo( $atts['destino'] );
	if ( ! $fixo ) {
		return '';
	}
	$lista = rtm_sugestoes_do_destino( $fixo[0] );
	if ( ! $lista ) {
		return '';
	}
	rtm_enfileirar_estilo();
	$cards = '';
	foreach ( $lista as $s ) {
		$cards .= rtm_card_sugestao( $s, true );
	}
	return '<section class="rtm-sd"><div class="rtm-sd-in"><div class="rtm-sd-cab"><div><h2 class="rtm-sd-titulo">' . esc_html( 'Sugestões dos especialistas para ' . $fixo[1] ) . '</h2>'
		. '<p class="rtm-sd-sub">Viagens que os nossos agentes costumam montar. Cada uma vira uma cotação feita para você.</p></div>'
		. '<a class="rtm-sd-todos" href="' . esc_url( add_query_arg( array( 'destino' => $fixo[0] ), rtm_url_especialistas() ) ) . '">' . esc_html( 'Ver especialistas em ' . $fixo[1] ) . '</a></div>'
		. '<div class="rtm-sd-grade rtm-sugs-lista">' . $cards . '</div></div></section>';
}
add_shortcode( 'rt_sugestoes_destino', 'rtm_sugestoes_destino_html' );

/** Bloco do Elementor sem sugestões (destino sem sugestão, ou visitante no ensaio) não deixa faixa em branco. */
function rtm_sugestoes_destino_esconder_vazio() {
	if ( is_singular( 'destinos' ) ) {
		echo '<style id="rtm-sugestoes-destino-vazio">.e-con.rtm-c-sugestoes-destino:not(:has(.rtm-sd)){display:none}</style>' . "\n";
	}
}
add_action( 'wp_head', 'rtm_sugestoes_destino_esconder_vazio' );

