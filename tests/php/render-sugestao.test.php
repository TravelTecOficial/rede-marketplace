<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_p( $id, $titulo, $tipo, $meta = array(), $status = 'publish' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ), 'post_content' => '' );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}
rtm_t_p( 10, 'Gramado', 'destinos' );
rtm_t_p( 5, 'Mariana Kloss', 'rt_agente', array( 'ativo' => '1', 'destinos' => array( array( 'id' => 10, 'slug' => 'gramado' ) ), 'destino_principal' => 'gramado', 'especialidades' => array( array( 'titulo' => 'Família', 'descricao' => '' ) ) ) );
rtm_t_p( 6, 'Rafael Antunes', 'rt_agente', array( 'ativo' => '1' ) );
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
