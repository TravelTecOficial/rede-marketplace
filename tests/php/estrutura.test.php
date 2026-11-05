<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';
igual( '0.15.0', RTM_VERSION, 'RTM_VERSION é 0.15.0' );
ok( defined( 'RTM_DIR' ) && defined( 'RTM_URL' ) && defined( 'RTM_BASENAME' ), 'constantes RTM_DIR, RTM_URL e RTM_BASENAME definidas' );
foreach ( array( 'opcoes', 'modelo', 'admin', 'privacidade', 'render', 'agente', 'cotacao', 'instalador', 'teste', 'atualizador' ) as $n ) {
	ok( file_exists( '/p/includes/' . $n . '.php' ), "includes/$n.php existe" );
}
$cab = file_get_contents( '/p/rede-marketplace.php' );
contem( 'Requires PHP: 7.4', $cab, 'cabeçalho exige PHP 7.4' );
contem( 'Version:     0.15.0', $cab, 'cabeçalho com Version 0.15.0' );
contem( 'Plugin Name: Rede Marketplace', $cab, 'cabeçalho do plugin' );
$at = file_get_contents( '/p/includes/atualizador.php' );
contem( "'TravelTecOficial/rede-marketplace'", $at, 'atualizador aponta para o repositório certo' );
contem( "'rede-marketplace.zip'", $at, 'atualizador procura o anexo certo' );
nao_contem( 'vtec', strtolower( $at ), 'nada do Viator Tec sobrou no atualizador' );
ok( rtm_forcar_consulta( array( 'force-check' => '1' ) ), '"Verificar novamente" força consultar o GitHub' );
ok( ! rtm_forcar_consulta( array() ), 'sem force-check usa o cache' );
