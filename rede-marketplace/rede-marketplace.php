<?php
/**
 * Plugin Name: Rede Marketplace
 * Description: Marketplace de agentes de viagens da Rede Turística: página de cada agente, cotação e lead no CRM do Cliente Ideal.
 * Version:     0.14.0
 * Author:      TravelTec
 * Text Domain: rede-marketplace
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RTM_VERSION', '0.14.0' );
define( 'RTM_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTM_URL', plugin_dir_url( __FILE__ ) );
define( 'RTM_BASENAME', plugin_basename( __FILE__ ) );

foreach ( array( 'opcoes', 'modelo', 'sugestao', 'admin', 'admin-sugestao', 'privacidade', 'render', 'render-sugestao', 'agente', 'cotacao', 'carrossel', 'lista', 'instalador', 'teste', 'atualizador' ) as $rtm_arquivo ) {
	require_once RTM_DIR . 'includes/' . $rtm_arquivo . '.php';
}
