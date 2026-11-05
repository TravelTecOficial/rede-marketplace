<?php
/**
 * [rt_especialistas_lista]: página /especialistas/ com todos os agentes ativos e filtros por destino e tipo de
 * viagem (filtro no navegador, funciona com cache). Item "Especialistas" do menu: classe rtm-menu-especialistas.
 * Modo ensaio: só quem pode editar o site vê os agentes e o item do menu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rtm_url_especialistas() {
	return home_url( '/especialistas/' );
}

/** Quem pode ver os agentes agora (fora do ensaio, todos; no ensaio, só quem edita o site). */
function rtm_mostra_agentes_publico() {
	return ! rtm_ensaio() || current_user_can( 'edit_posts' );
}

function rtm_card_lista( array $a ) {
	$nomes = array();
	foreach ( $a['destinos'] as $d ) {
		$n = rtm_titulo_destino( $d );
		if ( '' !== $n ) {
			$nomes[] = $n;
		}
	}
	$busca = mb_strtolower( remove_accents( trim( $a['nome'] . ' ' . (string) $a['agencia_nome'] ) ) );
	return '<article class="rtm-lista-card" data-slug="' . esc_attr( $a['slug'] ) . '" data-busca="' . esc_attr( $busca ) . '" data-destinos="' . esc_attr( implode( ' ', $a['destinos_slugs'] ) ) . '" data-tipos="' . esc_attr( implode( '|', $a['tipos'] ) ) . '">'
		. rtm_html_foto( $a, 'rtm-lista-foto' ) . '<div class="rtm-lista-corpo">'
		. rtm_html_cadastur( $a, false )
		. '<h2 class="rtm-lista-nome">' . esc_html( $a['nome'] ) . '</h2>'
		. ( '' !== trim( (string) $a['cargo'] ) ? '<p class="rtm-lista-cargo">' . esc_html( $a['cargo'] ) . '</p>' : '' )
		. rtm_html_nota( $a )
		. ( $a['tipos'] ? '<div class="rtm-chips">' . rtm_html_chips( $a['tipos'] ) . '</div>' : '' )
		. ( $nomes ? '<p class="rtm-lista-atende">' . esc_html( 'Atende: ' . implode( ', ', $nomes ) ) . '</p>' : '' )
		. '<div class="rtm-lista-acoes"><a class="rtm-lista-ver" href="' . esc_url( $a['url'] ) . '">Ver perfil</a>'
		. '<a class="rtm-lista-cot" href="' . esc_url( rtm_url_cotacao( $a ) ) . '">Pedir cotação</a></div>'
		. rtm_html_agencia( $a, false ) . '</div></article>';
}

function rtm_lista_html() {
	rtm_enfileirar_estilo();
	if ( ! rtm_mostra_agentes_publico() ) {
		return '<section class="rtm-lista"><div class="rtm-lista-in"><h1 class="rtm-lista-titulo">Nossos especialistas</h1><p class="rtm-lista-sub">Em breve: agentes certificados que montam sua viagem.</p></div></section>';
	}
	wp_enqueue_script( 'rtm-lista', RTM_URL . 'assets/lista.js', array(), RTM_VERSION, true );
	$agentes  = rtm_agentes_ativos();
	$destinos = rtm_destinos_cotacao( $agentes );
	$tipos    = array();
	foreach ( $agentes as $a ) {
		foreach ( $a['tipos'] as $t ) {
			$tipos[ $t ] = $t;
		}
	}
	ksort( $tipos );

	$h = '<section class="rtm-lista"><div class="rtm-lista-in">';
	if ( rtm_ensaio() ) {
		$h .= '<p class="rtm-car-ensaio">Visível só para você (modo ensaio): os visitantes veem "Em breve".</p>';
	}
	$h .= '<h1 class="rtm-lista-titulo">Nossos especialistas</h1><p class="rtm-lista-sub">Agentes de viagem certificados, cada um com seu destino e seu jeito de viajar. Escolha quem vai montar a sua viagem.</p>';
	$h .= '<div class="rtm-lista-filtros"><label class="rtm-rot rtm-lista-busca-rot" for="rtm-lista-busca">Buscar<input type="search" id="rtm-lista-busca" placeholder="Nome do agente ou da agência" autocomplete="off"></label>'
		. '<label class="rtm-rot" for="rtm-lista-destino">Destino<select id="rtm-lista-destino"><option value="">Todos os destinos</option>';
	foreach ( $destinos as $slug => $nome ) {
		$h .= '<option value="' . esc_attr( $slug ) . '">' . esc_html( $nome ) . '</option>';
	}
	$h .= '</select></label>';
	if ( $tipos ) {
		$h .= '<div class="rtm-grupo" role="group" aria-label="Tipo de viagem"><span>Tipo de viagem</span><div class="rtm-pilulas" id="rtm-lista-tipos">' . rtm_cot_pilulas( $tipos, 'data-tipo' ) . '</div></div>';
	}
	$h .= '</div><p class="rtm-lista-conta" id="rtm-lista-conta" aria-live="polite">' . count( $agentes ) . ( 1 === count( $agentes ) ? ' especialista' : ' especialistas' ) . '</p>';
	$h .= '<div class="rtm-lista-grade">';
	foreach ( $agentes as $a ) {
		$h .= rtm_card_lista( $a );
	}
	$h .= '</div><p class="rtm-lista-vazio" id="rtm-lista-vazio" hidden>Nenhum especialista com esse filtro ainda. <a href="' . esc_url( (string) rtm_opcoes()['url_cotacao'] ) . '">Peça a cotação e a Rede Turística indica o melhor para você.</a></p>';
	return $h . '</div></section>';
}
add_shortcode( 'rt_especialistas_lista', 'rtm_lista_html' );

/** Item "Especialistas" do menu (classe rtm-menu-especialistas): no ensaio, só quem edita o site vê. */
function rtm_menu_especialistas( $itens ) {
	if ( rtm_mostra_agentes_publico() ) {
		return $itens;
	}
	return array_filter( (array) $itens, function ( $i ) {
		return ! ( is_object( $i ) && in_array( 'rtm-menu-especialistas', (array) $i->classes, true ) );
	} );
}
add_filter( 'wp_nav_menu_objects', 'rtm_menu_especialistas' );
