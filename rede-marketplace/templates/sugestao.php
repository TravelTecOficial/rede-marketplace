<?php
/** Página da sugestão de viagem: cabeçalho do tema + galeria, detalhes, lateral do agente + rodapé. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rtm_sugestao = rtm_sugestao( get_queried_object_id() );
get_header();
if ( $rtm_sugestao ) {
	echo '<main class="rtm-pagina rtm-pagina-sugestao"><div class="rtm-wrap">' . rtm_pagina_sugestao_html( $rtm_sugestao ) . '</div></main>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escapado em rtm_pagina_sugestao_html()
}
get_footer();
