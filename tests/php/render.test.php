<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_post( $id, $titulo, $meta = array(), $status = 'publish', $tipo = 'rt_agente', $conteudo = '' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => $conteudo );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
function rtm_t_todas( $a ) {
	$h = '';
	foreach ( array( 'topo', 'sobre', 'especialidades', 'onde', 'cotacao', 'barra' ) as $s ) { $h .= rtm_secao( $s, $a ); }
	return $h . rtm_card( $a );
}

// Destinos (post type do Cliente Ideal)
rtm_t_post( 10, 'Gramado', array(), 'publish', 'destinos' );
rtm_t_post( 11, 'Canela', array(), 'publish', 'destinos' );

// Agente completo, sem foto
rtm_t_post( 5, 'Mariana Kloss', array(
	'whatsapp' => '11999990000', 'email' => 'mariana@segredo.com', 'company_id' => 'company-x', 'ativo' => '1',
	'cargo' => 'Especialista em Gramado e Serra Gaúcha', 'texto_card' => 'Há 12 anos levo famílias para a Serra Gaúcha.',
	'apresentacao' => "Nasci em Nova Petrópolis.\n\nGosto de entender o ritmo de cada família.",
	'cadastur' => '21.123.456/0001-00', 'anos_experiencia' => '12', 'viajantes_atendidos' => '1400',
	'idiomas' => array( 'Português', 'Espanhol' ), 'especialidades' => array( 'Parques e Snowland', 'Ingressos do Natal Luz' ),
	'destinos' => array( array( 'id' => 10, 'slug' => 'gramado' ), array( 'id' => 11, 'slug' => 'canela' ) ),
	'destino_principal' => 'gramado', 'agencia_nome' => 'Serra Viagens', 'agencia_cidade' => 'Gramado/RS', 'agencia_desde' => '2009',
	'endereco' => 'Av. Borges de Medeiros, 1500', 'horario' => 'Seg a sex, 9h às 18h', 'google_perfil' => 'https://g.page/serra',
) );
$GLOBALS['rt_termos'][5]['rt_tipo_viagem'] = array( (object) array( 'name' => 'Família' ), (object) array( 'name' => 'Natal Luz' ) );
$a    = rtm_agente( 5 );
$topo = rtm_secao( 'topo', $a );

igual( 'https://exemplo.test/cotacao-especialistas/?destino=gramado&agente=mariana-kloss', rtm_url_cotacao( $a ), 'rtm_url_cotacao com destino e agente' );
contem( 'Mariana Kloss', $topo, 'topo: nome' );
contem( 'Pedir cotação com Mariana', $topo, 'topo: botão de cotação com o primeiro nome' );
contem( 'destino=gramado&amp;agente=mariana-kloss', $topo, 'topo: link da cotação com destino e agente' );
contem( 'Especialista em Gramado e Serra Gaúcha', $topo, 'topo: cargo' );
contem( 'Família', $topo, 'topo: chips de tipos' );
contem( '12 anos', $topo, 'topo: anos de experiência' );
contem( '+1.400', $topo, 'topo: viajantes atendidos' );
contem( 'Português · Espanhol', $topo, 'topo: idiomas' );
contem( 'Serra Viagens', $topo, 'topo: agência' );
contem( 'desde 2009', $topo, 'topo: agência desde' );
contem( '>SV<', $topo, 'topo: logo vazio vira iniciais da agência' );
contem( '>MK<', $topo, 'topo: sem foto vira iniciais (Review Focus 3)' );
nao_contem( '<img', $topo, 'topo: sem foto, nenhuma imagem' );
nao_contem( 'Cadastur verificado', $topo, 'sem cadastur_conferido_em: sem selo' );
nao_contem( 'Ver avaliações', $topo, 'sem botão "Ver avaliações"' );

$todas = rtm_t_todas( $a );
foreach ( array( '11999990000', 'mariana@segredo.com', 'company-x' ) as $privado ) {
	nao_contem( $privado, $todas, "privado $privado fora de todas as seções e do card" );
}
nao_contem( 'Google', $todas, 'nenhuma seção fala de Google' );
nao_contem( 'g.page', $todas, 'perfil do Google só guardado, não exibido' );
nao_contem( 'avalia', $todas, 'sem avaliações' );

$sobre = rtm_secao( 'sobre', $a );
contem( 'Sobre Mariana', $sobre, 'sobre: título' );
contem( '<p>Nasci em Nova Petrópolis.</p>', $sobre, 'sobre: apresentação em parágrafos' );
$esp = rtm_secao( 'especialidades', $a );
contem( 'Ingressos do Natal Luz', $esp, 'especialidades: itens' );
contem( '>Canela<', $esp, 'especialidades: destinos pelo nome' );
$onde = rtm_secao( 'onde', $a );
contem( 'Av. Borges de Medeiros, 1500', $onde, 'onde: endereço' );
contem( 'Seg a sex, 9h às 18h', $onde, 'onde: horário' );
$lat = rtm_secao( 'cotacao', $a );
contem( 'destino=gramado&amp;agente=mariana-kloss', $lat, 'lateral: link da cotação' );
contem( 'Peça uma cotação com Mariana', $lat, 'lateral: título' );
$barra = rtm_secao( 'barra', $a );
contem( 'rtm-barra', $barra, 'barra fixa do celular' );
contem( 'Pedir cotação com Mariana', $barra, 'barra: botão' );
igual( '', rtm_secao( 'inexistente', $a ), 'seção desconhecida → vazio' );

// Cadastur verificado
update_post_meta( 5, 'rtm_cadastur_conferido_em', '2026-11-02' );
$a2 = rtm_agente( 5 );
contem( 'Cadastur verificado', rtm_secao( 'topo', $a2 ), 'com data: selo no topo' );
contem( '21.123.456/0001-00', rtm_secao( 'topo', $a2 ), 'selo com o número' );
contem( 'Cadastur verificado', rtm_card( $a2 ), 'com data: selo no card' );

// Card
$card = rtm_card( $a );
igual( 1, substr_count( $card, 'class="rtm-card"' ), 'card: uma só ocorrência de class="rtm-card"' );
contem( 'data-id="5"', $card, 'card: data-id' );
contem( 'data-slug="mariana-kloss"', $card, 'card: data-slug' );
contem( 'data-destinos="gramado canela"', $card, 'card: data-destinos com espaço' );
contem( 'data-tipos="Família|Natal Luz"', $card, 'card: data-tipos com |' );
preg_match_all( '/\sdata-([a-z0-9_-]+)=/i', $card, $m );
igual( array( 'id', 'slug', 'destinos', 'tipos' ), $m[1], 'card: só os 4 atributos data-*' );
contem( 'Há 12 anos levo famílias', $card, 'card: texto do cartão' );
contem( 'Ver perfil', $card, 'card: link do perfil' );
contem( '>MK<', $card, 'card: sem foto vira iniciais' );
nao_contem( 'Cadastur verificado', $card, 'card: sem data, sem selo' );

// Foto e logo presentes; URL perigosa
$GLOBALS['rt_anexos'] = array( 77 => 'https://exemplo.test/logo.png', 88 => 'https://exemplo.test/foto.jpg' );
$GLOBALS['rt_meta'][5]['_thumbnail_id'] = 88;
$GLOBALS['rt_meta'][5]['rtm_agencia_logo'] = 77;
$a3 = rtm_agente( 5 );
contem( 'src="https://exemplo.test/foto.jpg"', rtm_secao( 'topo', $a3 ), 'topo: foto' );
contem( 'src="https://exemplo.test/logo.png"', rtm_secao( 'topo', $a3 ), 'topo: logo' );
contem( 'src="https://exemplo.test/foto.jpg"', rtm_card( $a3 ), 'card: foto' );
nao_contem( '>MK<', rtm_secao( 'topo', $a3 ), 'com foto: sem iniciais' );

rtm_t_post( 9, 'Teste Url', array( 'ativo' => '1', 'foto_url_teste' => 'javascript:alert(1)' ) );
$a9 = rtm_agente( 9 );
nao_contem( 'javascript:', rtm_secao( 'topo', $a9 ) . rtm_card( $a9 ), 'URL de foto com javascript: não sai' );
contem( '>TU<', rtm_secao( 'topo', $a9 ), 'URL de foto inválida: iniciais' );

// Agente vazio (Review Focus 3)
rtm_t_post( 6, 'Ana', array( 'ativo' => '1' ) );
$v = rtm_agente( 6 );
igual( '', rtm_secao( 'sobre', $v ), 'sem apresentação: sobre vazio' );
igual( '', rtm_secao( 'especialidades', $v ), 'sem especialidades e destinos: vazio' );
igual( '', rtm_secao( 'onde', $v ), 'sem endereço e horário: onde vazio' );
igual( 'https://exemplo.test/cotacao-especialistas/?agente=ana', rtm_url_cotacao( $v ), 'sem destino principal: só o agente' );
$tv = rtm_secao( 'topo', $v );
contem( '>AN<', $tv, 'agente vazio: iniciais' );
nao_contem( 'rtm-agencia', $tv, 'sem agência: bloco da agência some' );
nao_contem( 'rtm-numeros', $tv, 'sem números: bloco some' );
contem( '>AN<', rtm_card( $v ), 'card vazio: iniciais' );
contem( 'data-destinos=""', rtm_card( $v ), 'card vazio: data-destinos vazio' );

// Escape (Review Focus 4)
rtm_t_post( 8, '<script>x</script>', array(
	'ativo' => '1', 'texto_card' => '<script>y</script>curto', 'cargo' => '<img src=x onerror=alert(1)>',
	'apresentacao' => '<p>Olá</p><script>z</script>', 'agencia_nome' => '<b>Ag</b>',
) );
$e  = rtm_agente( 8 );
$te = rtm_t_todas( $e );
nao_contem( '<script>x', rtm_secao( 'topo', $e ), 'nome com <script> escapado no topo' );
nao_contem( '<script>', $te, 'nenhum <script> nas seções e no card' );
nao_contem( '<img src=x', $te, 'cargo com HTML escapado' );
nao_contem( '<b>Ag</b>', $te, 'agência com HTML escapada' );
contem( '<p>Olá</p>', rtm_secao( 'sobre', $e ), 'apresentação mantém HTML permitido' );

// Shortcode [rt_agente secao="..."]
ok( isset( $GLOBALS['rt_shortcodes']['rt_agente'] ), 'shortcode rt_agente registrado' );
$sc = $GLOBALS['rt_shortcodes']['rt_agente'];
$GLOBALS['rt_atual'] = 5;
contem( 'Mariana Kloss', call_user_func( $sc, array( 'secao' => 'topo' ) ), 'shortcode no agente: topo' );
igual( '', call_user_func( $sc, array( 'secao' => 'xyz' ) ), 'shortcode com seção inválida: vazio' );
$GLOBALS['rt_atual'] = 10;
igual( '', call_user_func( $sc, array( 'secao' => 'topo' ) ), 'shortcode fora de rt_agente: vazio' );

// 404 do agente publicado e inativo
ok( in_array( 'rtm_404_inativo', $GLOBALS['rt_ganchos']['template_redirect'], true ), 'template_redirect -> rtm_404_inativo' );
rtm_t_post( 7, 'Desativado', array( 'ativo' => '0' ) );
$GLOBALS['rt_atual'] = 7;
rtm_404_inativo();
ok( $GLOBALS['wp_query']->is_404, 'agente inativo: 404' );
igual( 404, $GLOBALS['rt_status'], 'agente inativo: status 404' );
$GLOBALS['wp_query']  = new RT_Query_Falsa();
$GLOBALS['rt_status'] = 200;
$GLOBALS['rt_atual']  = 5;
rtm_404_inativo();
ok( ! $GLOBALS['wp_query']->is_404 && 200 === $GLOBALS['rt_status'], 'agente ativo: sem 404' );
$GLOBALS['rt_atual'] = 10;
rtm_404_inativo();
ok( ! $GLOBALS['wp_query']->is_404, 'outro tipo de post: sem 404' );

// Modelo reserva (sem Elementor)
ok( in_array( 'rtm_template_reserva', $GLOBALS['rt_ganchos']['template_include'], true ), 'template_include -> rtm_template_reserva' );
$GLOBALS['rt_atual'] = 10;
igual( '/tema/page.php', rtm_template_reserva( '/tema/page.php' ), 'fora de rt_agente: modelo do tema' );
$GLOBALS['rt_atual'] = 5;
$r = rtm_template_reserva( '/tema/single.php' );
igual( '/p/templates/agente.php', $r, 'rt_agente sem modelo do Elementor: modelo reserva' );
ok( file_exists( $r ), 'modelo reserva existe' );

// CSS só em rt_agente e na página com [rt_cotacao
ok( in_array( 'rtm_estilos', $GLOBALS['rt_ganchos']['wp_enqueue_scripts'], true ), 'wp_enqueue_scripts -> rtm_estilos' );
ok( file_exists( '/p/assets/rede-marketplace.css' ), 'CSS existe' );
$GLOBALS['rt_atual'] = 5;
rtm_estilos();
ok( isset( $GLOBALS['rt_estilos']['rede-marketplace'] ), 'CSS em rt_agente' );
$GLOBALS['rt_estilos'] = array();
rtm_t_post( 20, 'Cotação', array(), 'publish', 'page', '[rt_cotacao]' );
$GLOBALS['rt_atual'] = 20;
rtm_estilos();
ok( isset( $GLOBALS['rt_estilos']['rede-marketplace'] ), 'CSS na página com [rt_cotacao]' );
$GLOBALS['rt_estilos'] = array();
rtm_t_post( 21, 'Contato', array(), 'publish', 'page', 'Fale conosco' );
$GLOBALS['rt_atual'] = 21;
rtm_estilos();
ok( empty( $GLOBALS['rt_estilos'] ), 'CSS fora das outras páginas' );
rtm_t_post( 22, 'Cotação Elementor', array(), 'publish', 'page', '' );
$GLOBALS['rt_meta'][22]['_elementor_data'] = '[{"settings":{"shortcode":"[rt_cotacao]"}}]';
$GLOBALS['rt_atual'] = 22;
ok( rtm_pagina_tem_cotacao(), 'detecta [rt_cotacao] nos dados do Elementor' );
$css = file_get_contents( '/p/assets/rede-marketplace.css' );
contem( '--e-global-typography-primary-font-family', $css, 'CSS usa a fonte do kit' );
contem( '#f08a12', $css, 'CSS: laranja de reserva' );
nao_contem( 'Bricolage', $css, 'CSS sem fontes da prévia' );
