<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_post( $id, $titulo, $meta = array(), $status = 'publish', $tipo = 'rt_agente', $conteudo = '' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => $conteudo );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}

rtm_t_post( 10, 'Gramado', array(), 'publish', 'destinos' );
rtm_t_post( 12, 'Paris', array(), 'publish', 'destinos' );
rtm_t_post( 13, 'Canela', array(), 'draft', 'destinos' );
$g = array( array( 'id' => 10, 'slug' => 'gramado' ) );
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00', 'whatsapp' => '11999990000', 'email' => 'm@segredo.com', 'company_id' => 'company-x', 'destinos' => $g ) );

// CSS do formulário com escopo
$css = file_get_contents( '/p/assets/rede-marketplace.css' );
foreach ( array( '.rtm-cotacao h1.rtm-titulo', '.rtm-cotacao label.rtm-rot', '.rtm-cotacao input[type=text]', '.rtm-cotacao .rtm-cbarra button.rtm-enviar', '.rtm-cotacao h2.rtm-etapa-h' ) as $s ) {
	contem( $s, $css, "CSS: $s" );
}

// Dentro da página de Destino: destino da própria página
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 950, 'post_type' => 'destinos', 'post_status' => 'publish', 'post_title' => 'Bariloche', 'post_name' => 'bariloche', 'post_content' => '' );
$GLOBALS['rt_atual'] = 950;
$hd = rtm_cotacao_html( array( 'destino' => 'atual' ) );
contem( 'class="rtm-cotacao rtm-cotacao-destino" data-destino="bariloche"', $hd, 'destino: raiz marca o destino da página' );
contem( '<option value="Bariloche" data-slug="bariloche"></option>', $hd, 'destino sem especialista aparece nas sugestões' );
contem( 'value="Bariloche">', $hd, 'destino da página já preenchido' );
contem( ' em Bariloche</span>', $hd, 'título com o destino da página' );
contem( '<h2 class="rtm-titulo">', $hd, 'destino: título vira h2 (a página já tem h1)' );
nao_contem( '<h1', $hd, 'destino: sem h1' );
igual( $hd, rtm_cotacao_html( array() ), 'sem atributo, numa página de destino, também usa o destino da página' );
$GLOBALS['rt_atual'] = 0;
nao_contem( 'data-destino=', rtm_cotacao_html(), 'página de cotação comum: sem destino fixo' );
contem( '<h1 class="rtm-titulo">', rtm_cotacao_html(), 'página de cotação comum: h1' );

// Sugestões: dados para a cotação marcar a sugestão vinda da URL (?sugestao=)
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00' ) );
rtm_t_post( 7, 'Inativa', array( 'ativo' => '0' ) );
rtm_t_post( 60, 'Gramado em família, 4 dias', array( 'agente' => 5, 'destino' => 'gramado' ), 'publish', 'rt_sugestao' );
rtm_t_post( 61, 'De inativa', array( 'agente' => 7 ), 'publish', 'rt_sugestao' );
$hs = rtm_cotacao_html();
preg_match( '#<script type="application/json" id="rtm-sugestoes-dados">(.*?)</script>#s', $hs, $m );
$dados = json_decode( isset( $m[1] ) ? $m[1] : '', true );
igual( array( 'titulo' => 'Gramado em família, 4 dias', 'agente' => 'mariana-kloss', 'agente_nome' => 'Mariana Kloss', 'destino' => 'gramado' ), isset( $dados['gramado-em-familia-4-dias'] ) ? $dados['gramado-em-familia-4-dias'] : null, 'JSON com a sugestão publicada' );
ok( ! isset( $dados['de-inativa'] ), 'JSON sem sugestão de agente inativo' );
preg_match( '/<div [^>]*id="rtm-sugestao-aviso"[^>]*>/', $hs, $av );
contem( 'hidden', isset( $av[0] ) ? $av[0] : '', 'aviso da sugestão começa escondido' );
contem( 'id="rtm-sugestao-tirar"', $hs, 'botão de tirar a sugestão' );
contem( '.rtm-cotacao .rtm-sugestao-aviso[hidden]{display:none}', file_get_contents( '/p/assets/rede-marketplace.css' ), 'aviso da sugestão escondido de verdade (o display:flex não passa por cima)' );

