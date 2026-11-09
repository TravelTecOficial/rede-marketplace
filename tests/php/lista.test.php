<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $tipo, $meta = array(), $conteudo = '' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => 'publish', 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => $conteudo );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
rtm_t_p( 10, 'Gramado', 'destinos' );
rtm_t_p( 11, 'Canela', 'destinos' );
$g = array( array( 'id' => 10, 'slug' => 'gramado' ) );
rtm_t_p( 5, 'Mariana Kloss', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00', 'cargo' => 'Especialista em Gramado', 'destinos' => $g, 'destino_principal' => 'gramado', 'cadastur' => '21.123.456/0001-00' ) );
rtm_t_p( 6, 'Camila Duarte', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00', 'destinos' => array( array( 'id' => 10, 'slug' => 'gramado' ), array( 'id' => 11, 'slug' => 'canela' ) ), 'destino_principal' => 'gramado' ) );
rtm_t_p( 7, 'Inativo', 'rt_agente', array( 'ativo' => '0' ) );
$GLOBALS['rt_termos'][5]['rt_tipo_viagem'] = array( (object) array( 'name' => 'Família' ) );
$GLOBALS['rt_termos'][6]['rt_tipo_viagem'] = array( (object) array( 'name' => 'Grupo' ), (object) array( 'name' => 'Natal Luz' ) );
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => false, 'url_cotacao' => 'https://exemplo.test/cotacao-de-viagens/' );

ok( isset( $GLOBALS['rt_shortcodes']['rt_especialistas_lista'] ), 'shortcode rt_especialistas_lista' );
$GLOBALS['rt_estilos'] = array(); $GLOBALS['rt_scripts'] = array();
$h = rtm_lista_html();
ok( isset( $GLOBALS['rt_estilos']['rede-marketplace'] ) && isset( $GLOBALS['rt_scripts']['rtm-lista'] ), 'carrega CSS e JS da lista' );
contem( '<h1 class="rtm-lista-titulo">Nossos especialistas</h1>', $h, 'título h1' );
igual( 2, substr_count( $h, 'class="rtm-lista-card"' ), 'só agentes ativos' );
contem( 'data-destinos="gramado canela"', $h, 'card com destinos para o filtro' );
contem( 'data-tipos="Grupo|Natal Luz"', $h, 'card com tipos para o filtro' );
contem( 'Atende: Gramado, Canela', $h, 'destinos atendidos pelo nome' );
contem( '<option value="canela">Canela</option>', $h, 'filtro de destino com os destinos que têm especialista' );
contem( 'data-tipo="Natal Luz"', $h, 'filtro por tipo com os tipos dos agentes' );
nao_contem( 'data-tipo="Luxo"', $h, 'tipo sem agente não aparece no filtro' );
contem( '>Ver perfil</a>', $h, 'botão Ver perfil' );
contem( 'href="https://exemplo.test/cotacao-de-viagens/?destino=gramado&amp;agente=mariana-kloss"', $h, 'Pedir cotação vai só com aquele agente' );
contem( 'id="rtm-lista-vazio" hidden', $h, 'aviso de filtro sem resultado começa escondido' );
contem( 'id="rtm-lista-conta"', $h, 'contagem de especialistas' );
contem( '<input type="search" id="rtm-lista-busca"', $h, 'campo de busca por nome ou agência' );
contem( 'placeholder="Nome do agente ou da agência"', $h, 'dica do campo de busca' );
contem( 'data-busca="mariana kloss', $h, 'card com o texto de busca (nome + agência)' );

// Ensaio: visitante não vê os agentes
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = true;
$GLOBALS['rt_pode'] = false;
$v = rtm_lista_html();
nao_contem( 'rtm-lista-card', $v, 'ensaio: visitante não vê os agentes' );
contem( 'Em breve', $v, 'ensaio: visitante vê "Em breve"' );
$GLOBALS['rt_pode'] = true;
contem( 'Visível só para você (modo ensaio)', rtm_lista_html(), 'ensaio: aviso para quem edita' );

// Menu: item "Especialistas" escondido do visitante no ensaio
$itens = array( (object) array( 'title' => 'Home', 'classes' => array( '' ) ), (object) array( 'title' => 'Especialistas', 'classes' => array( 'rtm-menu-especialistas' ) ) );
ok( in_array( 'rtm_menu_especialistas', $GLOBALS['rt_ganchos']['wp_nav_menu_objects'], true ), 'filtro do menu registrado' );
$GLOBALS['rt_pode'] = false;
igual( array( 'Home' ), wp_list_pluck( array_values( rtm_menu_especialistas( $itens ) ), 'title' ), 'ensaio: visitante não vê o item' );
$GLOBALS['rt_pode'] = true;
igual( 2, count( rtm_menu_especialistas( $itens ) ), 'quem edita vê o item' );
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = false; $GLOBALS['rt_pode'] = false;
igual( 2, count( rtm_menu_especialistas( $itens ) ), 'fora do ensaio: todos veem' );

// Ensaio: página da lista com noindex
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = true;
rtm_t_p( 40, 'Especialistas', 'page', array(), '[rt_especialistas_lista]' );
$GLOBALS['rt_atual'] = 40;
ok( rtm_pagina_marketplace(), 'página da lista é do marketplace (noindex no ensaio)' );
$GLOBALS['rt_atual'] = 0;

// Links para a lista
igual( 'https://exemplo.test/especialistas/', rtm_url_especialistas(), 'endereço da lista' );
$GLOBALS['rt_pode'] = true;
contem( 'href="https://exemplo.test/especialistas/">Especialistas</a>', rtm_secao( 'trilha', rtm_agente( 5 ) ), 'trilha do agente leva à lista' );
contem( 'href="https://exemplo.test/especialistas/">Ver todos os especialistas</a>', rtm_carrossel_html(), 'carrossel: Ver todos' );
