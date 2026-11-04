<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_post( $id, $tipo, $slug, $status = 'publish', $titulo = '' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo ? $titulo : $slug, 'post_name' => $slug );
}
function rtm_t_contar( $tipo ) {
	$n = 0;
	foreach ( $GLOBALS['rt_posts'] as $p ) {
		if ( $p->post_type === $tipo ) {
			$n++;
		}
	}
	return $n;
}
function rtm_t_disparar( $acao, $post = array() ) {
	$_REQUEST               = $post;
	$GLOBALS['rt_redirect'] = '';
	try {
		do_action( 'admin_post_' . $acao );
	} catch ( RT_Saida_Redirect $e ) {
		return $GLOBALS['rt_redirect'];
	} catch ( Exception $e ) {
		return $e->getMessage();
	}
	return $GLOBALS['rt_redirect'];
}

rtm_t_post( 10, 'destinos', 'gramado', 'publish', 'Gramado' );
rtm_t_post( 11, 'destinos', 'canela', 'publish', 'Canela' );
rtm_t_post( 50, 'rt_agente', 'ana', 'publish', 'Ana Real' ); // agente real, sem rtm_teste
update_post_meta( 50, 'rtm_ativo', '1' );

// Criar
$ids = rtm_criar_agentes_teste();
igual( 3, count( $ids ), 'cria 3 agentes' );
igual( 4, rtm_t_contar( 'rt_agente' ), '3 de teste + 1 real' );
$ids2 = rtm_criar_agentes_teste();
igual( $ids, $ids2, 'criar de novo devolve os mesmos ids' );
igual( 4, rtm_t_contar( 'rt_agente' ), 'não duplica ao criar duas vezes' );
igual( 9, rtm_t_contar( 'rt_sugestao' ), '3 sugestões por agente de teste, sem duplicar' );
$sugs_mariana = rtm_sugestoes_do_agente( $ids[0] );
igual( array( 'Gramado em família, 4 dias', 'Natal Luz com ingressos garantidos', 'Serra Gaúcha sem pressa, 5 dias' ), wp_list_pluck( $sugs_mariana, 'titulo' ), 'sugestões da Mariana, na ordem' );
$g = $sugs_mariana[0];
igual( array( 'Família', 2890, '4 dias · 3 noites', 6, 4 ), array( $g['tipo'], (int) $g['preco'], $g['duracao'], count( $g['inclui'] ), count( $g['dias'] ) ), 'Gramado em família: tipo, preço, duração, 6 itens, 4 dias' );
igual( 'Parques, Snowland e café colonial num ritmo que funciona com criança.', $g['texto_card'], 'texto do cartão igual à prévia' );
igual( 3450, (int) $sugs_mariana[1]['preco'], 'Natal Luz: R$ 3.450' );
igual( array( 'Melhor idade', 3980 ), array( $sugs_mariana[2]['tipo'], (int) $sugs_mariana[2]['preco'] ), 'Serra sem pressa: melhor idade, R$ 3.980' );
contem( 'assets/teste/sug-', $g['imagem'], 'imagem ilustrativa do plugin' );
foreach ( $GLOBALS['rt_posts'] as $p ) {
	if ( 'rt_sugestao' === $p->post_type ) {
		igual( '1', get_post_meta( $p->ID, 'rtm_teste', true ), "sugestão {$p->ID} marcada rtm_teste" );
		ok( in_array( (int) get_post_meta( $p->ID, 'rtm_agente', true ), $ids, true ), "sugestão {$p->ID} ligada a um agente de teste" );
	}
}

foreach ( $ids as $id ) {
	igual( '1', get_post_meta( $id, 'rtm_teste', true ), "post $id marcado rtm_teste" );
	igual( 'publish', get_post_status( $id ), "post $id publicado" );
	igual( '1', get_post_meta( $id, 'rtm_ativo', true ), "post $id ativo" );
	igual( date( 'Y-m-d' ), get_post_meta( $id, 'rtm_cadastur_conferido_em', true ), "post $id cadastur conferido hoje" );
	igual( '', get_post_meta( $id, 'rtm_company_id', true ), "post $id sem company_id" );
	nao_contem( '(teste)', get_the_title( $id ), "título de $id sem (teste)" );
	ok( ! empty( $GLOBALS['rt_obj_termos'][ $id ]['rt_tipo_viagem'] ), "post $id com tipos de viagem" );
	foreach ( $GLOBALS['rt_obj_termos'][ $id ]['rt_tipo_viagem'] as $t ) {
		ok( in_array( $t, rtm_tipos_iniciais(), true ), "tipo '$t' existe entre os tipos do plugin" );
	}
	$esps = get_post_meta( $id, 'rtm_especialidades', true );
	ok( count( $esps ) <= 4 && count( $esps ) > 0, "post $id: até 4 especialidades" );
	foreach ( $esps as $esp ) {
		ok( is_array( $esp ) && '' !== $esp['titulo'] && '' !== $esp['descricao'] && mb_strlen( $esp['descricao'] ) <= 120, "post $id: especialidade com título e descrição curta" );
	}
}
$por_slug = array();
foreach ( $ids as $id ) {
	$por_slug[ get_post( $id )->post_name ] = $id;
}
$slugs = array_keys( $por_slug );
sort( $slugs );
igual( array( 'camila-duarte', 'mariana-kloss', 'rafael-antunes' ), $slugs, 'slugs dos 3 agentes' );
$m = $por_slug['mariana-kloss'];
igual( 'Mariana Kloss', get_the_title( $m ), 'título Mariana' );
igual( 'Serra Viagens', get_post_meta( $m, 'rtm_agencia_nome', true ), 'agência Mariana' );
igual( 'Gramado/RS', get_post_meta( $m, 'rtm_agencia_cidade', true ), 'cidade Mariana' );
igual( '11900000001', get_post_meta( $m, 'rtm_whatsapp', true ), 'whatsapp Mariana' );
igual( 'teste+mariana@redeturistica.com.br', get_post_meta( $m, 'rtm_email', true ), 'e-mail Mariana' );
igual( array( array( 'id' => 10, 'slug' => 'gramado' ) ), get_post_meta( $m, 'rtm_destinos', true ), 'destinos Mariana = gramado {id,slug}' );
igual( 'gramado', get_post_meta( $m, 'rtm_destino_principal', true ), 'destino principal Mariana' );
igual( RTM_URL . 'assets/teste/mariana.svg', get_post_meta( $m, 'rtm_foto_url_teste', true ), 'foto de teste Mariana' );
igual( array( 'Família', 'Natal Luz', 'Melhor idade' ), $GLOBALS['rt_obj_termos'][ $m ]['rt_tipo_viagem'], 'tipos Mariana' );
$r = $por_slug['rafael-antunes'];
igual( '11900000002', get_post_meta( $r, 'rtm_whatsapp', true ), 'whatsapp Rafael' );
igual( 'Rota Sul Turismo', get_post_meta( $r, 'rtm_agencia_nome', true ), 'agência Rafael' );
$c = $por_slug['camila-duarte'];
igual( '11900000003', get_post_meta( $c, 'rtm_whatsapp', true ), 'whatsapp Camila' );
igual( 'São Paulo/SP', get_post_meta( $c, 'rtm_agencia_cidade', true ), 'cidade Camila' );
igual( array( array( 'id' => 10, 'slug' => 'gramado' ), array( 'id' => 11, 'slug' => 'canela' ) ), get_post_meta( $c, 'rtm_destinos', true ), 'Camila: gramado e canela' );
$pub = rtm_agente( $m );
ok( null !== $pub, 'agente de teste aparece em rtm_agente()' );
ok( ! isset( $pub['whatsapp'] ) && ! isset( $pub['email'] ) && ! isset( $pub['teste'] ), 'privados e marca de teste não saem em rtm_agente()' );
igual( RTM_URL . 'assets/teste/mariana.svg', $pub['foto'], 'foto pública = SVG de teste' );

// Os SVGs existem e são válidos
foreach ( array( 'mariana', 'rafael', 'camila' ) as $n ) {
	$f = "/p/assets/teste/$n.svg";
	ok( file_exists( $f ), "$n.svg existe" );
	$x = file_exists( $f ) ? file_get_contents( $f ) : '';
	contem( 'viewBox="0 0 300 340"', $x, "$n.svg com viewBox" );
	ok( false !== simplexml_load_string( $x ), "$n.svg é XML válido" );
}
foreach ( array( 'sug-familia', 'sug-natal', 'sug-serra', 'sug-romance', 'sug-vinho', 'sug-chale', 'sug-grupo', 'sug-canela', 'sug-onibus' ) as $n ) {
	$f = "/p/assets/teste/$n.svg";
	ok( file_exists( $f ) && false !== simplexml_load_string( file_get_contents( $f ) ), "$n.svg existe e é XML válido" );
}

// Apagar
igual( 3, rtm_apagar_agentes_teste(), 'apagar devolve 3' );
igual( 1, rtm_t_contar( 'rt_agente' ), 'só o agente real sobra' );
igual( 0, rtm_t_contar( 'rt_sugestao' ), 'apagar remove as sugestões de teste' );
ok( null !== get_post( 50 ), 'agente real não foi apagado' );
$forcados = array_filter( $GLOBALS['rt_apagados'], function ( $a ) {
	return true === $a[1];
} );
igual( 12, count( $forcados ), 'apagou com force (3 agentes + 9 sugestões)' );
igual( 0, rtm_apagar_agentes_teste(), 'apagar de novo devolve 0' );
igual( 3, count( rtm_criar_agentes_teste() ), 'recriar depois de apagar' );
igual( 4, rtm_t_contar( 'rt_agente' ), 'total 4 após recriar' );

// Sem o destino gramado: cria assim mesmo, sem destinos
rtm_apagar_agentes_teste();
$GLOBALS['rt_posts'] = array_values( array_filter( $GLOBALS['rt_posts'], function ( $p ) {
	return 'destinos' !== $p->post_type;
} ) );
$ids3 = rtm_criar_agentes_teste();
igual( 3, count( $ids3 ), 'sem destinos: ainda cria 3' );
igual( array(), get_post_meta( $ids3[0], 'rtm_destinos', true ), 'sem gramado: destinos vazio' );
igual( '', get_post_meta( $ids3[0], 'rtm_destino_principal', true ), 'sem gramado: destino principal vazio' );
igual( true, rtm_teste_sem_destino(), 'avisa que faltou o destino' );
rtm_apagar_agentes_teste();
rtm_t_post( 10, 'destinos', 'gramado', 'publish', 'Gramado' );
igual( false, rtm_teste_sem_destino(), 'com gramado publicado não avisa' );

// Criar e apagar agentes de teste limpam o cache (apagar com force não passa por save_post)
rtm_t_post( 40, 'page', 'cotacao-especialistas', 'publish', 'Cotação' );
$GLOBALS['rt_posts'][ count( $GLOBALS['rt_posts'] ) - 1 ]->post_content = '[rt_cotacao]';
delete_transient( 'rtm_pagina_cotacao' );
$purgas = array();
add_action( 'litespeed_purge_post', function ( $id ) use ( &$purgas ) { $purgas[] = array( 'post', $id ); } );
add_action( 'litespeed_purge_url', function ( $url ) use ( &$purgas ) { $purgas[] = array( 'url', $url ); } );
$ids4 = rtm_criar_agentes_teste();
ok( in_array( array( 'post', 40 ), $purgas, true ) && in_array( array( 'url', 'https://exemplo.test/cotacao-especialistas/' ), $purgas, true ), 'criar agentes de teste: purga a página de cotação' );
ok( in_array( array( 'url', get_permalink( $ids4[0] ) ), $purgas, true ), 'criar agentes de teste: purga a página do agente' );
$purgas = array();
rtm_apagar_agentes_teste();
ok( in_array( array( 'post', 40 ), $purgas, true ) && in_array( array( 'url', 'https://exemplo.test/cotacao-especialistas/' ), $purgas, true ), 'apagar agentes de teste: purga a página de cotação' );

// Botões e ações (admin-post)
ok( isset( $GLOBALS['rt_ganchos']['admin_post_rtm_criar_teste'] ) && isset( $GLOBALS['rt_ganchos']['admin_post_rtm_apagar_teste'] ), 'ações admin-post registradas' );
ok( isset( $GLOBALS['rt_ganchos']['rtm_configuracoes_extra'] ), 'botões ligados em rtm_configuracoes_extra' );
ob_start();
do_action( 'rtm_configuracoes_extra' );
$h = ob_get_clean();
contem( 'admin-post.php', $h, 'formulário envia para admin-post.php' );
contem( 'value="rtm_criar_teste"', $h, 'botão criar' );
contem( 'value="rtm_apagar_teste"', $h, 'botão apagar' );
contem( 'name="_wpnonce"', $h, 'nonce no formulário' );

$GLOBALS['rt_pode'] = false;
$antes              = rtm_t_contar( 'rt_agente' );
$res                = rtm_t_disparar( 'rtm_criar_teste', array( '_wpnonce' => 'ok_rtm_criar_teste' ) );
contem( 'wp_die', $res, 'sem permissão: wp_die' );
igual( $antes, rtm_t_contar( 'rt_agente' ), 'sem permissão: nada criado' );
$GLOBALS['rt_pode'] = true;
$res                = rtm_t_disparar( 'rtm_criar_teste', array( '_wpnonce' => 'errado' ) );
contem( 'nonce', $res, 'nonce inválido barrado' );
igual( $antes, rtm_t_contar( 'rt_agente' ), 'nonce inválido: nada criado' );
$res = rtm_t_disparar( 'rtm_criar_teste', array( '_wpnonce' => 'ok_rtm_criar_teste' ) );
igual( $antes + 3, rtm_t_contar( 'rt_agente' ), 'ação criar cria 3' );
contem( 'page=rede-marketplace', $res, 'volta à página de configurações' );
contem( 'rtm_teste=criados', $res, 'redireciona com aviso de criação' );
$res = rtm_t_disparar( 'rtm_apagar_teste', array( '_wpnonce' => 'ok_rtm_apagar_teste' ) );
igual( $antes, rtm_t_contar( 'rt_agente' ), 'ação apagar remove os 3' );
contem( 'rtm_teste=apagados', $res, 'redireciona com aviso de exclusão' );
contem( 'rtm_n=3', $res, 'aviso traz a quantidade' );

// Avisos na tela
$_GET = array( 'rtm_teste' => 'criados' );
ob_start();
do_action( 'rtm_configuracoes_extra' );
$h = ob_get_clean();
contem( '3 agentes de teste criados.', $h, 'aviso de criados' );
$_GET = array( 'rtm_teste' => 'apagados', 'rtm_n' => '2' );
ob_start();
do_action( 'rtm_configuracoes_extra' );
$h = ob_get_clean();
contem( '2 agentes de teste apagados.', $h, 'aviso de apagados' );
$_GET = array( 'rtm_teste' => 'criados', 'rtm_sem_destino' => '1' );
ob_start();
do_action( 'rtm_configuracoes_extra' );
$h = ob_get_clean();
contem( 'gramado', $h, 'aviso quando falta o destino gramado' );
$_GET = array( 'rtm_teste' => '<script>' );
ob_start();
do_action( 'rtm_configuracoes_extra' );
$h = ob_get_clean();
nao_contem( '<script>', $h, 'aviso ignora valor desconhecido' );

// Coluna "Teste" na lista de agentes
$cols = rtm_colunas( array( 'cb' => 'x', 'title' => 'Título' ) );
ok( isset( $cols['rtm_teste'] ), 'coluna Teste existe' );
ob_start();
rtm_coluna( 'rtm_teste', 50 );
$x = ob_get_clean();
nao_contem( 'Teste', $x, 'agente real sem marca' );
rtm_t_post( 77, 'rt_agente', 'x', 'publish' );
update_post_meta( 77, 'rtm_teste', '1' );
ob_start();
rtm_coluna( 'rtm_teste', 77 );
$x = ob_get_clean();
contem( 'Teste', $x, 'agente de teste com marca' );
