<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $tipo, $meta = array(), $status = 'publish' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => '' );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
rtm_t_p( 10, 'Gramado', 'destinos' );
rtm_t_p( 5, 'Mariana Kloss', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00', 'destinos' => array( array( 'id' => 10, 'slug' => 'gramado' ) ), 'destino_principal' => 'gramado', 'especialidades' => array( array( 'titulo' => 'Família', 'descricao' => '' ) ) ) );
rtm_t_p( 6, 'Rafael Antunes', 'rt_agente', array( 'ativo' => '1', 'cadastur' => '21.123.456/0001-00' ) );
rtm_t_p( 20, 'Gramado em família, 4 dias', 'rt_sugestao', array( 'agente' => 5, 'tipo' => 'Família', 'texto_card' => 'Parques e café colonial.', 'preco' => 2890, 'ordem' => 1, 'img_teste' => 'https://exemplo.test/a.svg', 'destino' => 'gramado' ) );
rtm_t_p( 21, 'Natal Luz', 'rt_sugestao', array( 'agente' => 5, 'tipo' => 'Natal Luz', 'texto_card' => 'Desfile.', 'ordem' => 2 ) );

$a = rtm_agente( 5 );
$lat = rtm_html_sugestoes( $a, 'lateral' );
contem( 'Sugestões de Mariana', $lat, 'lateral: título com o nome' );
contem( 'Viagens que Mariana costuma montar', $lat, 'lateral: subtítulo com o nome' );
igual( 2, substr_count( $lat, 'class="rtm-sug"' ), 'lateral: 2 cartões' );
contem( 'href="https://exemplo.test/especialistas/mariana-kloss/gramado-em-familia-4-dias/"', $lat, 'cartão leva à sugestão' );
contem( 'a partir de <strong>R$ 2.890</strong> por pessoa', $lat, 'preço no cartão' );
igual( 1, substr_count( $lat, 'a partir de' ), 'sem preço: sem linha de preço' );
igual( 1, substr_count( $lat, 'rtm-sug-img' ), 'sem imagem: sem bloco de imagem' );
contem( 'Ver sugestão', $lat, 'chamada do cartão' );
contem( 'class="rtm-sugestoes rtm-sugestoes-lateral"', $lat, 'classe da lateral' );
contem( 'class="rtm-sugestoes rtm-sugestoes-celular', rtm_html_sugestoes( $a, 'celular' ), 'versão celular' );
igual( '', rtm_html_sugestoes( rtm_agente( 6 ), 'lateral' ), 'agente sem sugestões: nada' );
igual( rtm_html_sugestoes( $a, 'lateral' ), rtm_secao( 'sugestoes', $a ), '[rt_agente secao="sugestoes"] = lateral' );
ok( in_array( 'sugestoes', rtm_secoes(), true ), 'seção sugestoes no shortcode' );

$corpo = rtm_corpo_agente( $a );
$p_esp = strpos( $corpo, 'rtm-especialidades' );
$p_cel = strpos( $corpo, 'rtm-sugestoes-celular' );
$p_lat = strpos( $corpo, 'rtm-sugestoes-lateral' );
$p_cot = strpos( $corpo, 'class="rtm-lateral"' );
ok( $p_esp < $p_cel, 'celular: sugestões depois de Especialidades' );
ok( $p_lat < $p_cot, 'lateral: sugestões antes da cotação' );
contem( 'class="rtm-col-lateral"', $corpo, 'coluna lateral envolve sugestões e cotação' );
nao_contem( 'rtm-sugestoes', rtm_corpo_agente( rtm_agente( 6 ) ), 'sem sugestões: página como hoje' );

// ---- Página da sugestão
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'url_cotacao' => 'https://exemplo.test/cotacao-de-viagens/', 'ensaio' => true );
$GLOBALS['rt_meta'][20] += array( 'rtm_destinos_texto' => 'Gramado e Canela', 'rtm_resumo' => 'Parques no ritmo da criança.', 'rtm_duracao' => '4 dias · 3 noites', 'rtm_ideal_para' => 'Família com crianças',
	'rtm_inclui' => array( 'Hotel com recreação', 'Transfer' ), 'rtm_dias' => array( array( 'titulo' => 'Chegada', 'descricao' => 'Rua Coberta.' ) ), 'rtm_galeria' => array( 31, 32, 33 ) );
$GLOBALS['rt_anexos'] = array( 31 => 'https://exemplo.test/g1.jpg', 32 => 'https://exemplo.test/g2.jpg', 33 => 'https://exemplo.test/g3.jpg' );
$s  = rtm_sugestao( 20 );
$pg = rtm_pagina_sugestao_html( $s );
contem( 'href="' . esc_url( $s['agente']['url'] ) . '">Mariana Kloss</a>', $pg, 'trilha com link do agente' );
contem( 'data-fotos="4"', $pg, 'galeria com 4 fotos (imagem + 3)' );
contem( '+1 fotos', $pg, '"+N fotos" quando passa de 3' );
igual( 1, substr_count( $pg, '<h1' ), 'um só h1' );
contem( '>Gramado em família, 4 dias</h1>', $pg, 'título' );
contem( 'Gramado e Canela', $pg, 'selo de destinos' );
contem( '<small>Duração</small><b>4 dias · 3 noites</b>', $pg, 'fato: duração' );
nao_contem( 'Melhor época', $pg, 'fato vazio some' );
contem( 'O que inclui', $pg, 'inclui' );
contem( 'Roteiro sugerido', $pg, 'roteiro' );
contem( 'Mariana ajusta hotel, dias e passeios ao seu jeito na cotação.', $pg, 'nota do roteiro' );
contem( 'Outras sugestões de Mariana', $pg, 'outras sugestões' );
igual( 1, substr_count( $pg, 'class="rtm-sug"' ), 'outras: sem a atual' );
contem( '<b>R$ 2.890</b>', $pg, 'preço na lateral' );
contem( 'referência; o valor final vem na cotação', $pg, 'preço de referência' );
contem( 'href="https://exemplo.test/cotacao-de-viagens/?destino=gramado&amp;agente=mariana-kloss&amp;sugestao=gramado-em-familia-4-dias"', $pg, 'botão leva à cotação com a sugestão' );
igual( 2, substr_count( $pg, 'Quero uma cotação dessa sugestão' ), 'botão na lateral e na barra do celular' );
contem( 'Ver perfil de Mariana', $pg, 'link do perfil' );
$s2 = rtm_sugestao( 21 );
$p2 = rtm_pagina_sugestao_html( $s2 );
nao_contem( 'rtm-galeria', $p2, 'sem fotos: sem galeria' );
nao_contem( 'A partir de', $p2, 'sem preço: sem bloco de preço' );
nao_contem( 'O que inclui', $p2, 'inclui vazio some' );
nao_contem( 'Roteiro sugerido', $p2, 'roteiro vazio some' );
$GLOBALS['rt_atual'] = 20;
igual( '/p/templates/sugestao.php', rtm_template_sugestao( '/tema/single.php' ), 'rt_sugestao usa o modelo do plugin' );
$GLOBALS['rt_estilos'] = array(); $GLOBALS['rt_scripts'] = array();
rtm_estilos();
ok( isset( $GLOBALS['rt_estilos']['rede-marketplace'] ) && isset( $GLOBALS['rt_scripts']['rtm-sugestao'] ), 'página da sugestão carrega CSS e JS' );
$GLOBALS['rt_atual'] = 0;

// CSS: cotação continua fixa ao rolar quando não há sugestões; no celular a página da sugestão mostra preço e mini perfil
$css = file_get_contents( '/p/assets/rede-marketplace.css' );
nao_contem( '.rtm-col-lateral .rtm-lateral{position:static}', $css, 'sem sugestões a cotação continua sticky' );
contem( '.rtm-col-lateral .rtm-sugestoes-lateral ~ .rtm-lateral{position:static}', $css, 'com sugestões a cotação rola com a página' );
nao_contem( 'aside.rtm-sug-lateral{display:none}', $css, 'celular: preço e mini perfil da sugestão continuam visíveis' );

