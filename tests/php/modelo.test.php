<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_post( $id, $titulo, $meta = array(), $status = 'publish', $tipo = 'rt_agente' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => $tipo, 'post_status' => $status, 'post_title' => $titulo, 'post_name' => sanitize_title( $titulo ) );
	$GLOBALS['rt_meta'][ $id ] = array();
	foreach ( $meta as $k => $v ) { $GLOBALS['rt_meta'][ $id ][ 'rtm_' . $k ] = $v; }
}

// Registro
rtm_registrar();
$t = $GLOBALS['rt_tipos']['rt_agente'];
ok( $t['public'] && false === $t['has_archive'] && false === $t['show_in_rest'], 'rt_agente: público, sem arquivo, sem REST' );
igual( 'especialistas', $t['rewrite']['slug'], 'rt_agente: URL /especialistas/' );
igual( array( 'title', 'thumbnail' ), $t['supports'], 'rt_agente: supports title+thumbnail' );
$x = $GLOBALS['rt_taxonomias']['rt_tipo_viagem'];
ok( false === $x['args']['hierarchical'] && false === $x['args']['show_in_rest'] && false === $x['args']['public'], 'rt_tipo_viagem: plana, privada, sem REST' );
ok( isset( $GLOBALS['rt_ganchos']['init'] ) && in_array( 'rtm_registrar', $GLOBALS['rt_ganchos']['init'], true ), 'init -> rtm_registrar' );
$i = array_search( 'rtm_purgar_cache', $GLOBALS['rt_ganchos']['save_post_rt_agente'], true );
ok( false !== $i, 'save_post_rt_agente -> rtm_purgar_cache' );
igual( 20, $GLOBALS['rt_prioridades']['save_post_rt_agente'][ $i ], 'purga com prioridade 20' );
ok( ! empty( $GLOBALS['rt_ativacao'] ), 'gancho de ativação registrado' );

// Tipos iniciais
igual( array( 'Família', 'Casal / Lua de mel', 'Natal Luz', 'Grupo', 'Melhor idade', 'Luxo' ), rtm_tipos_iniciais(), 'tipos iniciais exatos' );
rtm_instalar_tipos();
rtm_instalar_tipos();
igual( 6, count( $GLOBALS['rt_taxonomias']['rt_tipo_viagem']['criados'] ), 'instalar tipos cria 6 e é idempotente' );

// Opções
igual( 'https://exemplo.test/cotacao-especialistas/', rtm_opcoes()['url_cotacao'], 'opções: url_cotacao padrão' );
igual( true, rtm_opcoes()['ensaio'], 'opções: ensaio padrão true' );
update_option( 'rtm_opcoes', array( 'ensaio' => false ) );
igual( false, rtm_opcoes()['ensaio'], 'opções: mescla com a salva' );
igual( 'https://exemplo.test/cotacao-especialistas/', rtm_opcoes()['url_cotacao'], 'opções: mantém padrão não salvo' );
update_option( 'rtm_opcoes', array() );

// Campos
$c = rtm_campos();
igual( 180, $c['texto_card']['max'], 'texto_card max 180' );
igual( 4, $c['especialidades']['max'], 'especialidades max 4' );
foreach ( array( 'whatsapp', 'email', 'company_id', 'ativo', 'cadastur_conferido_em' ) as $k ) { ok( false === $c[ $k ]['publico'], "$k é privado" ); }
foreach ( array( 'cargo', 'destinos', 'agencia_logo', 'google_perfil', 'foto_url_teste' ) as $k ) { ok( true === $c[ $k ]['publico'], "$k é público" ); }

// Agente
$GLOBALS['rt_anexos'] = array( 77 => 'https://exemplo.test/logo.png', 88 => 'https://exemplo.test/foto.jpg' );
rtm_t_post( 5, 'Mariana Kloss', array(
	'whatsapp' => '11999990000', 'email' => 'a@b.com', 'company_id' => 'company-x', 'ativo' => '1',
	'cadastur' => '21.123.456/0001-00', 'agencia_nome' => 'Viagens Serra', 'agencia_logo' => 77,
	'destinos' => array( array( 'id' => 10, 'slug' => 'gramado' ), array( 'id' => 11, 'slug' => 'canela' ) ),
) );
$GLOBALS['rt_meta'][5]['_thumbnail_id'] = 88;
$GLOBALS['rt_termos'][5]['rt_tipo_viagem'] = array( (object) array( 'name' => 'Família' ), (object) array( 'name' => 'Luxo' ) );
$a = rtm_agente( 5 );
foreach ( array( 'whatsapp', 'email', 'company_id', 'ativo', 'cadastur_conferido_em' ) as $k ) { ok( ! array_key_exists( $k, $a ), "privado $k fora da saída" ); }
nao_contem( '11999990000', wp_json_encode( $a ), 'whatsapp não vaza no JSON' );
nao_contem( 'a@b.com', wp_json_encode( $a ), 'e-mail não vaza no JSON' );
nao_contem( 'company-x', wp_json_encode( $a ), 'company_id não vaza no JSON' );
igual( false, $a['cadastur_verificado'], 'sem data: Cadastur não verificado' );
igual( array( 'gramado', 'canela' ), $a['destinos_slugs'], 'destinos_slugs' );
igual( 'MK', $a['iniciais'], 'iniciais Mariana Kloss' );
igual( 'VS', $a['agencia_iniciais'], 'iniciais da agência' );
igual( 'Mariana Kloss', $a['nome'], 'nome' );
igual( 'mariana-kloss', $a['slug'], 'slug' );
igual( 5, $a['id'], 'id' );
igual( 'https://exemplo.test/foto.jpg', $a['foto'], 'foto da imagem destacada' );
igual( 'https://exemplo.test/logo.png', $a['logo'], 'logo da agência' );
igual( array( 'Família', 'Luxo' ), $a['tipos'], 'tipos' );
ok( isset( $a['url'] ) && '' !== $a['url'], 'url' );

update_post_meta( 5, 'rtm_cadastur_conferido_em', '2026-11-02' );
igual( true, rtm_agente( 5 )['cadastur_verificado'], 'com data: Cadastur verificado' );

rtm_t_post( 6, 'Ana', array( 'ativo' => '1', 'foto_url_teste' => 'https://exemplo.test/teste.jpg' ) );
$b = rtm_agente( 6 );
igual( 'AN', $b['iniciais'], 'iniciais de nome único' );
igual( 'https://exemplo.test/teste.jpg', $b['foto'], 'foto_url_teste como fallback' );
igual( '', $b['logo'], 'sem logo: vazio' );
igual( array(), $b['destinos_slugs'], 'sem destinos: vazio' );

// Especialidades: {titulo, descricao}; texto simples antigo é convertido na leitura
rtm_t_post( 20, 'Leg', array( 'ativo' => '1', 'especialidades' => array( 'Luxo', array( 'titulo' => 'Família', 'descricao' => 'Parques' ), array( 'descricao' => 'sem título' ), '', 7, 'A', 'B', 'C' ) ) );
igual( array(
	array( 'titulo' => 'Luxo', 'descricao' => '' ), array( 'titulo' => 'Família', 'descricao' => 'Parques' ),
	array( 'titulo' => 'A', 'descricao' => '' ), array( 'titulo' => 'B', 'descricao' => '' ),
), rtm_agente( 20 )['especialidades'], 'rtm_agente: especialidades {titulo,descricao}, legado convertido, inválidas fora, máx 4' );
igual( array(), rtm_agente( 6 )['especialidades'], 'rtm_agente: sem especialidades -> lista vazia' );

// Inativo / não publicado / outro tipo / inexistente
rtm_t_post( 7, 'Zé Inativo', array( 'ativo' => '0' ) );
rtm_t_post( 8, 'Rascunho', array( 'ativo' => '1' ), 'draft' );
rtm_t_post( 9, 'Página', array( 'ativo' => '1' ), 'publish', 'page' );
igual( null, rtm_agente( 7 ), 'ativo=0 -> null' );
igual( null, rtm_agente( 8 ), 'rascunho -> null' );
igual( null, rtm_agente( 9 ), 'outro post type -> null' );
igual( null, rtm_agente( 999 ), 'inexistente -> null' );
igual( array( 'Ana', 'Leg', 'Mariana Kloss' ), array_column( rtm_agentes_ativos(), 'nome' ), 'ativos: só publicados e ativos, por título' );

// Purga de cache
// Página de cotação: achada pelo shortcode no conteúdo ou nos dados do Elementor (não basta a URL: o LiteSpeed guarda cada ?destino=&agente= à parte).
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 30, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Contato', 'post_name' => 'contato', 'post_content' => 'Fale conosco' );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 31, 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Rascunho', 'post_name' => 'rascunho', 'post_content' => '[rt_cotacao]' );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 32, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Cotação', 'post_name' => 'cotacao-especialistas', 'post_content' => '' );
$GLOBALS['rt_meta'][32]['_elementor_data'] = '[{"settings":{"shortcode":"[rt_cotacao]"}}]';
igual( 32, rtm_id_pagina_cotacao(), 'id da página de cotação: publicada, com [rt_cotacao (Elementor)' );
igual( 32, $GLOBALS['rt_transients']['rtm_pagina_cotacao'][0], 'id da página de cotação guardado em transient' );
$GLOBALS['rt_meta'][32]['_elementor_data'] = '';
igual( 32, rtm_id_pagina_cotacao(), 'id da página de cotação vem do cache' );
ok( isset( $GLOBALS['rt_ganchos']['save_post_page'] ) && in_array( 'rtm_esquecer_pagina_cotacao', $GLOBALS['rt_ganchos']['save_post_page'], true ), 'salvar página renova o cache da página de cotação' );
rtm_esquecer_pagina_cotacao();
igual( 0, rtm_id_pagina_cotacao(), 'sem página com [rt_cotacao: 0' );
rtm_esquecer_pagina_cotacao();
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 33, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Cotação 2', 'post_name' => 'cot2', 'post_content' => 'Peça: [rt_cotacao]' );
igual( 33, rtm_id_pagina_cotacao(), 'acha [rt_cotacao no conteúdo' );

// Purga: post do agente, URL do agente (404 antigo em cache), URL e post da página de cotação (todas as variantes ?destino=&agente=)
$ganchos_salvos = $GLOBALS['rt_ganchos'];
$GLOBALS['rt_ganchos'] = array();
$reg = array();
add_action( 'litespeed_purge_post', function ( $id ) use ( &$reg ) { $reg[] = array( 'post', $id ); } );
add_action( 'litespeed_purge_url', function ( $url ) use ( &$reg ) { $reg[] = array( 'url', $url ); } );
rtm_purgar_cache( 5 );
igual( array( array( 'post', 5 ), array( 'url', 'https://exemplo.test/?p=5' ), array( 'url', 'https://exemplo.test/cotacao-especialistas/' ), array( 'post', 33 ) ), $reg, 'purga do agente (post + URL) e da página de cotação (URL + post)' );
$reg = array();
rtm_purgar_ao_apagar( 5 );
igual( array( array( 'post', 5 ), array( 'url', 'https://exemplo.test/?p=5' ), array( 'url', 'https://exemplo.test/cotacao-especialistas/' ), array( 'post', 33 ) ), $reg, 'apagar agente de vez: purga antes' );
$reg = array();
rtm_purgar_ao_apagar( 30 );
igual( array(), $reg, 'apagar outra coisa: não purga' );
rtm_purgar_depois_de_apagar( 5, get_post( 5 ) );
igual( array( array( 'url', 'https://exemplo.test/cotacao-especialistas/' ), array( 'post', 33 ) ), $reg, 'depois de apagar agente: purga a cotação de novo' );
$reg = array();
rtm_purgar_depois_de_apagar( 30, get_post( 30 ) );
igual( array(), $reg, 'depois de apagar outra coisa: não purga' );
$GLOBALS['rt_ganchos'] = $ganchos_salvos;
ok( in_array( 'rtm_purgar_ao_apagar', $GLOBALS['rt_ganchos']['before_delete_post'], true ), 'before_delete_post -> rtm_purgar_ao_apagar' );
ok( in_array( 'rtm_purgar_depois_de_apagar', $GLOBALS['rt_ganchos']['deleted_post'], true ), 'deleted_post -> rtm_purgar_depois_de_apagar' );

// Busca e listagens: rt_agente fora
rtm_registrar();
igual( true, $GLOBALS['rt_tipos']['rt_agente']['exclude_from_search'], 'rt_agente fora da busca do site' );
ok( in_array( 'rtm_fora_das_listas', $GLOBALS['rt_ganchos']['pre_get_posts'], true ), 'pre_get_posts -> rtm_fora_das_listas' );
$q = new RT_Consulta( array( 'post_type' => 'rt_agente' ) );
rtm_fora_das_listas( $q );
igual( array( 0 ), $q->get( 'post__in' ), '/?post_type=rt_agente: nenhum resultado' );
igual( false, $q->is_home, '/?post_type=rt_agente: não vira página inicial (o WP responde 404)' );
$q = new RT_Consulta( array( 'post_type' => array( 'post', 'rt_agente' ) ) );
rtm_fora_das_listas( $q );
igual( array( 'post' ), $q->get( 'post_type' ), 'lista de tipos: tira rt_agente' );
$q = new RT_Consulta( array( 'post_type' => 'rt_agente', 's' => 'gramado' ), true, false, false );
$q->is_search = true;
rtm_fora_das_listas( $q );
igual( array( 0 ), $q->get( 'post__in' ), 'busca com post_type=rt_agente: nenhum resultado' );
$q = new RT_Consulta( array( 'post_type' => 'rt_agente', 'name' => 'mariana-kloss' ), true, true );
rtm_fora_das_listas( $q );
igual( null, $q->get( 'post__in' ), 'página do agente (singular): intocada' );
$q = new RT_Consulta( array( 'post_type' => 'rt_agente' ), false );
rtm_fora_das_listas( $q );
igual( null, $q->get( 'post__in' ), 'consulta secundária (rtm_agentes_ativos etc.): intocada' );
$GLOBALS['rt_admin'] = true;
$q = new RT_Consulta( array( 'post_type' => 'rt_agente' ) );
rtm_fora_das_listas( $q );
igual( null, $q->get( 'post__in' ), 'painel: intocado' );
$GLOBALS['rt_admin'] = false;
$q = new RT_Consulta( array( 'post_type' => 'post' ) );
rtm_fora_das_listas( $q );
igual( array( null, 'post' ), array( $q->get( 'post__in' ), $q->get( 'post_type' ) ), 'outras listagens: intocadas' );

// Ativação
$GLOBALS['rt_tipos'] = array();
foreach ( $GLOBALS['rt_ativacao'] as $cb ) { call_user_func( $cb ); }
ok( isset( $GLOBALS['rt_tipos']['rt_agente'] ) && ! empty( $GLOBALS['rt_flush'] ), 'ativação registra tipos e renova rewrite' );
