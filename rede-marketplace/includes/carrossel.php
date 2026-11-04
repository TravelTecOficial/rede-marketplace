<?php
/**
 * [rt_especialistas]: carrossel "Conheça nossos especialistas" (home, entre o banner e o buscador).
 * Modo ensaio: só quem pode editar o site vê (os visitantes continuam com a home como era).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rtm_carrossel_html() {
	$ensaio = rtm_ensaio();
	if ( $ensaio && ! current_user_can( 'edit_posts' ) ) {
		return '';
	}
	$agentes = rtm_agentes_ativos();
	if ( ! $agentes ) {
		return '';
	}
	rtm_enfileirar_estilo();
	wp_enqueue_script( 'rtm-carrossel', RTM_URL . 'assets/carrossel.js', array(), RTM_VERSION, true );

	$cards = '';
	foreach ( $agentes as $a ) {
		$cards .= '<article class="rtm-car-card">' . rtm_html_foto( $a, 'rtm-car-foto' ) . '<div class="rtm-car-corpo">'
			. rtm_html_cadastur( $a, false )
			. '<h3 class="rtm-car-nome">' . esc_html( $a['nome'] ) . '</h3>'
			. ( '' !== trim( (string) $a['cargo'] ) ? '<p class="rtm-car-cargo">' . esc_html( $a['cargo'] ) . '</p>' : '' )
			. rtm_html_nota( $a )
			. '<a class="rtm-car-ver" href="' . esc_url( $a['url'] ) . '">Ver perfil</a></div></article>';
	}
	$h  = '<section class="rtm-carrossel" aria-roledescription="carrossel" aria-label="Conheça nossos especialistas"><div class="rtm-car-in">';
	if ( $ensaio ) {
		$h .= '<p class="rtm-car-ensaio">Visível só para você (modo ensaio): os visitantes ainda não veem este carrossel.</p>';
	}
	$h .= '<div class="rtm-car-cab"><div><h2 class="rtm-car-titulo">Conheça nossos especialistas</h2><p class="rtm-car-sub">Agentes certificados que montam sua viagem</p></div>'
		. '<div class="rtm-car-setas"><button type="button" class="rtm-car-ant" aria-label="Especialista anterior">‹</button><button type="button" class="rtm-car-prox" aria-label="Próximo especialista">›</button></div></div>';
	$h .= '<div class="rtm-car-trilho" tabindex="0">' . $cards . '</div>';
	$h .= '<div class="rtm-car-rodape"><a class="rtm-car-cta" href="' . esc_url( (string) rtm_opcoes()['url_cotacao'] ) . '">Pedir cotação com especialistas</a></div>';
	return $h . '</div></section>';
}
add_shortcode( 'rt_especialistas', 'rtm_carrossel_html' );

/**
 * Para o visitante no ensaio o shortcode volta vazio, mas o bloco do Elementor continua na página com o
 * espaçamento dele (faixa em branco entre o banner e o buscador). Este CSS mínimo, só na home, esconde o bloco vazio.
 */
function rtm_carrossel_esconder_vazio() {
	if ( is_front_page() ) {
		echo '<style id="rtm-carrossel-vazio">.e-con.rtm-c-carrossel:not(:has(.rtm-carrossel)){display:none}</style>' . "\n";
	}
}
add_action( 'wp_head', 'rtm_carrossel_esconder_vazio' );
