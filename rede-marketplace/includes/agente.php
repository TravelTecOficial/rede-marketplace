<?php
/** Página pública do agente (/especialistas/<slug>/): shortcode de seção, 404 do inativo e modelo reserva. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rtm_secoes() {
	return array( 'topo', 'sobre', 'especialidades', 'onde', 'cotacao', 'barra' );
}

/** [rt_agente secao="topo"]: seção do agente do post atual; fora de rt_agente (ou agente inativo) → ''. */
function rtm_shortcode_agente( $atts ) {
	$atts  = shortcode_atts( array( 'secao' => '' ), $atts, 'rt_agente' );
	$secao = (string) $atts['secao'];
	if ( ! in_array( $secao, rtm_secoes(), true ) ) {
		return '';
	}
	$p = get_post( get_the_ID() );
	if ( ! $p || 'rt_agente' !== $p->post_type ) {
		return '';
	}
	$agente = rtm_agente( $p->ID );
	return $agente ? rtm_secao( $secao, $agente ) : '';
}
add_shortcode( 'rt_agente', 'rtm_shortcode_agente' );

/** Agente publicado mas inativo não tem página: responde 404. */
function rtm_404_inativo() {
	if ( ! is_singular( 'rt_agente' ) ) {
		return;
	}
	$id = get_queried_object_id();
	if ( 'publish' !== get_post_status( $id ) || rtm_agente( $id ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'rtm_404_inativo' );

/** Há um modelo do Theme Builder (Elementor Pro / PRO Elements) valendo para a página atual? */
function rtm_tem_modelo_elementor() {
	if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
		return false;
	}
	try {
		$docs = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_documents_for_location( 'single' );
	} catch ( \Throwable $e ) {
		return false;
	}
	return ! empty( $docs );
}

/** Sem modelo do Elementor ativo, a página do agente usa templates/agente.php (cabeçalho + seções + rodapé). */
function rtm_template_reserva( $template ) {
	if ( ! is_singular( 'rt_agente' ) || rtm_tem_modelo_elementor() ) {
		return $template;
	}
	return RTM_DIR . 'templates/agente.php';
}
add_filter( 'template_include', 'rtm_template_reserva', 20 );
