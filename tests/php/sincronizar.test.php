<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

$GLOBALS['rt_posts'][] = (object) array( 'ID' => 10, 'post_type' => 'destinos', 'post_status' => 'publish', 'post_title' => 'Gramado', 'post_name' => 'gramado', 'post_content' => '' );
// Agente de teste que já existia com o mesmo slug: é assumido (não duplica)
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 50, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => 'Camila Duarte', 'post_name' => 'camila-duarte', 'post_content' => '' );
$GLOBALS['rt_meta'][50] = array( 'rtm_teste' => '1', 'rtm_ativo' => '1', 'rtm_foto_url_teste' => 'https://exemplo.test/velha.svg' );
// Agente local que não está na API: vai para rascunho
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 51, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => 'Fulano', 'post_name' => 'fulano', 'post_content' => '' );
$GLOBALS['rt_meta'][51] = array( 'rtm_ativo' => '1' );
$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => true );

ok( rtm_ci_ligado(), 'ligado por padrão' );
igual( RTM_CI_API_PADRAO, rtm_ci_url(), 'API padrão (DEV)' );

$api = fixture( 'ci-agentes' );
resposta_falsa( 200, $api );
$st = rtm_ci_sincronizar();
contem( '/rede-publica/agentes', $GLOBALS['rt_http_log'][0][0], 'chama /agentes' );
ok( $st['ok'], 'sincronizou' );
igual( 3, $st['agentes'], '3 agentes' );
igual( 9, $st['sugestoes'], '9 sugestões' );

$cam = get_posts( array( 'post_type' => 'rt_agente', 'post_status' => 'any', 'name' => 'camila-duarte' ) );
igual( 1, count( $cam ), 'Camila não duplicou' );
igual( 50, (int) $cam[0]->ID, 'assumiu o post que já existia' );
$a = rtm_agente( 50 );
igual( $api[0]['foto'], $a['foto'], 'foto do Cliente Ideal tem prioridade' );
igual( $api[0]['certificado'], $a['certificado'], 'certificado do Cliente Ideal' );
igual( 'Bem Viajar', $a['agencia_nome'], 'agência' );
ok( false !== strpos( $a['apresentacao'], "\n\n" ), 'apresentação mantém parágrafos' );
igual( array( array( 'id' => 10, 'slug' => 'gramado' ) ), $a['destinos'], 'destino ligado ao post local' );
ok( ! $a['cadastur_verificado'], 'Cadastur não verificado segue a API' );
igual( array( 'Grupo', 'Natal Luz', 'Melhor idade' ), $GLOBALS['rt_obj_termos'][50]['rt_tipo_viagem'], 'tipos de viagem' );
igual( 'draft', get_post_status( 51 ), 'agente fora da API foi para rascunho' );

// Sugestões
$sugs = rtm_sugestoes_do_agente( 50 );
igual( 3, count( $sugs ), 'Camila com 3 sugestões' );
igual( 'natal-luz-em-grupo-saindo-de-sao-paulo', $sugs[0]['slug'], 'ordem da API' );
igual( $api[0]['sugestoes'][0]['galeria'][0], $sugs[0]['imagem'], 'imagem da galeria por URL' );
igual( 'gramado', $sugs[0]['destino'], 'destino da sugestão' );
igual( '', $sugs[1]['destino'], 'destino inexistente fica vazio' );
$mar = get_posts( array( 'post_type' => 'rt_agente', 'name' => 'mariana-kloss' ) );
$sm  = rtm_sugestoes_do_agente( $mar[0]->ID );
igual( 'Chegada e Rua Coberta', $sm[0]['dias'][0]['titulo'], 'dia: título' );
contem( 'Transfer', $sm[0]['dias'][0]['descricao'], 'dia: texto vira descrição' );

// Segunda rodada sem mudança: não regrava nada
$GLOBALS['rt_atualizados'] = array();
resposta_falsa( 200, $api );
rtm_ci_sincronizar();
igual( array(), $GLOBALS['rt_atualizados'], 'sem mudança, nada é regravado' );

// Sugestão removida na API vai para rascunho
$api2 = $api;
$sid  = $sugs[2]['id'];
array_pop( $api2[0]['sugestoes'] );
resposta_falsa( 200, $api2 );
rtm_ci_sincronizar();
igual( 'draft', get_post_status( $sid ), 'sugestão que saiu da API foi para rascunho' );

// Falha da API: nada muda
resposta_falsa( 500, 'erro' );
$st = rtm_ci_sincronizar();
ok( ! $st['ok'], 'falha registrada' );
igual( 'HTTP 500', $st['msg'], 'motivo' );
igual( 'publish', get_post_status( 50 ), 'agente continua no ar' );

// Lista vazia não tira ninguém do ar
resposta_falsa( 200, '[]' );
$st = rtm_ci_sincronizar();
igual( 0, $st['tirados'], 'lista vazia: nada tirado do ar' );
igual( 'publish', get_post_status( 50 ), 'lista vazia: agente continua' );

// Desligado: não chama a API
$GLOBALS['rt_opcoes']['rtm_opcoes']['ci_ligado'] = false;
$n = count( $GLOBALS['rt_http_log'] );
igual( null, rtm_ci_sincronizar(), 'desligado: não sincroniza' );
igual( $n, count( $GLOBALS['rt_http_log'] ), 'desligado: sem chamada' );
contem( 'cadastro do WordPress', rtm_ci_html_status(), 'status desligado' );

// Agendamento
$GLOBALS['rt_opcoes']['rtm_opcoes']['ci_ligado'] = true;
rtm_ci_agendar();
ok( false !== wp_next_scheduled( 'rtm_ci_sincronizar_evento' ), 'agenda quando ligado' );
$GLOBALS['rt_opcoes']['rtm_opcoes']['ci_ligado'] = false;
rtm_ci_agendar();
ok( false === wp_next_scheduled( 'rtm_ci_sincronizar_evento' ), 'desagenda quando desligado' );
$GLOBALS['rt_opcoes']['rtm_opcoes']['ci_ligado'] = true;
igual( 600, rtm_ci_intervalo( array() )['rtm_10min']['interval'], 'intervalo de 10 minutos' );

// Opções
$o = rtm_limpar_opcoes( array( 'ci_ligado' => '1', 'ci_api' => 'http://inseguro.test' ) );
ok( $o['ci_ligado'], 'opção ligada' );
igual( '', $o['ci_api'], 'API só https' );
$o = rtm_limpar_opcoes( array() );
ok( ! $o['ci_ligado'], 'checkbox desmarcado desliga' );

// Status e aviso
contem( 'Sincronizar agora', rtm_ci_html_status(), 'botão sincronizar' );
contem( 'nenhum agente', rtm_ci_html_status(), 'status mostra a última rodada' );
ob_start(); rtm_ci_aviso_edicao( get_post( 50 ) ); contem( 'vem do <b>Cliente Ideal</b>', ob_get_clean(), 'aviso na edição' );
ob_start(); rtm_ci_aviso_edicao( get_post( 51 ) ); igual( '', ob_get_clean(), 'sem aviso no agente local' );
