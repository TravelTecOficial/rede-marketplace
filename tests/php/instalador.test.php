<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

ok( in_array( 'rtm_instalar_modelo', $GLOBALS['rt_ativacao'], true ), 'ativação instala o modelo' );
$m = json_decode( (string) @file_get_contents( '/p/templates/agente.json' ), true );
ok( is_array( $m ) && isset( $m['content'] ), 'templates/agente.json válido' );

$id  = rtm_instalar_modelo();
$id2 = rtm_instalar_modelo();
ok( $id > 0, 'modelo criado' );
igual( $id, $id2, 'segunda chamada devolve o mesmo id' );
igual( 1, count( get_posts( array( 'post_type' => 'elementor_library' ) ) ), 'um único modelo' );
$p = get_post( $id );
igual( 'Especialistas — página do agente', $p->post_title, 'título do modelo' );
igual( 'publish', $p->post_status, 'modelo publicado' );
igual( array( 'include/singular/rt_agente' ), get_post_meta( $id, '_elementor_conditions', true ), 'condição: todos os rt_agente' );
igual( 'single', get_post_meta( $id, '_elementor_template_type', true ), 'tipo single' );
igual( 'builder', get_post_meta( $id, '_elementor_edit_mode', true ), 'modo builder' );
igual( array( 'single' ), $GLOBALS['rt_obj_termos'][ $id ]['elementor_library_type'], 'termo elementor_library_type single' );

// update_post_meta do WP tira as barras que wp_slash pôs.
$dados = json_decode( wp_unslash( get_post_meta( $id, '_elementor_data', true ) ), true );
ok( is_array( $dados ), '_elementor_data é JSON' );
function rtm_t_shortcodes( $els ) {
	$res = array();
	foreach ( $els as $e ) {
		if ( isset( $e['widgetType'] ) && 'shortcode' === $e['widgetType'] ) { $res[] = $e['settings']['shortcode']; }
		if ( ! empty( $e['elements'] ) ) { $res = array_merge( $res, rtm_t_shortcodes( $e['elements'] ) ); }
	}
	return $res;
}
$sc = array();
foreach ( array( 'trilha', 'topo', 'sobre', 'especialidades', 'onde', 'cotacao', 'barra' ) as $s ) { $sc[] = '[rt_agente secao="' . $s . '"]'; }
igual( $sc, rtm_t_shortcodes( (array) $dados ), 'shortcodes na ordem da prévia' );
foreach ( (array) $dados as $e ) { ok( 'container' === $e['elType'], 'raiz: contêiner V3' ); }
igual( array( '[rt_agente secao="trilha"]' ), rtm_t_shortcodes( array( $dados[0] ) ), 'trilha no 1º contêiner' );
igual( array( '[rt_agente secao="topo"]' ), rtm_t_shortcodes( array( $dados[1] ) ), 'topo no 2º contêiner' );
igual( 'full', $dados[1]['settings']['content_width'], 'topo em largura total' );
$grade = $dados[2]['elements'];
igual( 2, count( $grade ), 'grade: coluna principal + lateral' );
igual( array( '[rt_agente secao="sobre"]', '[rt_agente secao="especialidades"]', '[rt_agente secao="onde"]' ), rtm_t_shortcodes( array( $grade[0] ) ), 'coluna principal' );
igual( array( '[rt_agente secao="cotacao"]' ), rtm_t_shortcodes( array( $grade[1] ) ), 'lateral com a cotação' );
$ids  = array();
$cont = function ( $els ) use ( &$cont, &$ids ) { foreach ( $els as $e ) { $ids[] = $e['id']; $cont( $e['elements'] ); } };
$cont( (array) $dados );
igual( count( $ids ), count( array_unique( $ids ) ), 'ids dos elementos únicos' );

// Apagado (lixeira): cria de novo
foreach ( $GLOBALS['rt_posts'] as $pp ) { if ( $pp->ID === $id ) { $pp->post_status = 'trash'; } }
$id3 = rtm_instalar_modelo();
ok( $id3 !== $id && $id3 > 0, 'modelo na lixeira: cria outro' );
