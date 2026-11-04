<?php
/** HTML das sugestões: cartões no perfil do agente e página da sugestão. Tudo escapado aqui. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cartão da sugestão (lateral do perfil e "Outras sugestões"). */
function rtm_card_sugestao( array $s ) {
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
