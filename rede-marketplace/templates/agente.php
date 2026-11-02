<?php
/** Modelo reserva da página do agente (sem modelo do Elementor ativo): cabeçalho do tema + seções + rodapé. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rtm_agente = rtm_agente( get_queried_object_id() );
get_header();
if ( $rtm_agente ) {
	echo '<main class="rtm-pagina"><div class="rtm-wrap">';
	echo rtm_secao( 'trilha', $rtm_agente ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado em rtm_secao()
	echo rtm_secao( 'topo', $rtm_agente ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="rtm-grade"><div class="rtm-principal">';
	foreach ( array( 'sobre', 'especialidades', 'onde' ) as $rtm_s ) {
		echo rtm_secao( $rtm_s, $rtm_agente ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>' . rtm_secao( 'cotacao', $rtm_agente ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>' . rtm_secao( 'barra', $rtm_agente ) . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
get_footer();
