<?php
/** Instala o modelo do Theme Builder da página do agente (templates/agente.json). Uma vez só. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rtm_ler_modelo() {
	$arquivo = RTM_DIR . 'templates/agente.json';
	if ( ! file_exists( $arquivo ) ) {
		return null;
	}
	$m = json_decode( (string) file_get_contents( $arquivo ), true );
	return is_array( $m ) && isset( $m['content'] ) ? $m : null;
}

/**
 * Cria o modelo "Especialistas — página do agente" (tipo single, condição todos os rt_agente) e publica.
 * Se o modelo já existe (e não está na lixeira), só devolve o id: nunca sobrescreve edição do dono.
 */
function rtm_instalar_modelo() {
	$id = (int) get_option( 'rtm_modelo_agente', 0 );
	$p  = $id ? get_post( $id ) : null;
	if ( $p && 'elementor_library' === $p->post_type && 'trash' !== $p->post_status ) {
		return $id;
	}
	$m = rtm_ler_modelo();
	if ( ! $m ) {
		return 0;
	}
	$id = wp_insert_post( array(
		'post_type'   => 'elementor_library',
		'post_status' => 'publish',
		'post_title'  => $m['titulo'],
	), true );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', $m['tipo'] );
	update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
	update_post_meta( $id, '_elementor_page_settings', $m['page_settings'] );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $m['content'] ) ) );
	update_post_meta( $id, '_elementor_conditions', $m['condicoes'] );
	wp_set_object_terms( $id, $m['tipo'], 'elementor_library_type' );
	update_option( 'rtm_modelo_agente', (int) $id, false );

	// O Elementor Pro guarda as condições num cache próprio; sem refazer, o modelo não vale.
	if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
		try {
			\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate();
		} catch ( \Throwable $e ) {
			// Sem cache refeito, o dono salva as condições no Elementor; a ativação não pode quebrar.
		}
	}
	return (int) $id;
}

register_activation_hook( RTM_DIR . 'rede-marketplace.php', 'rtm_instalar_modelo' );
