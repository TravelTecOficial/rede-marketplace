<?php
/** Modelo reserva da página do agente (sem modelo do Elementor ativo): cabeçalho do tema + seções + rodapé. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rtm_agente = rtm_agente( get_queried_object_id() );
get_header();
if ( $rtm_agente ) {
	echo '<main class="rtm-pagina"><div class="rtm-wrap">' . rtm_corpo_agente( $rtm_agente ) . '</div>' . rtm_secao( 'barra', $rtm_agente ) . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escapado em rtm_secao()
}
get_footer();
