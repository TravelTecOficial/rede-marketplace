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
igual( array( array( 'id' => 10, 'slug' => 'gramado', 'nome' => 'Gramado' ) ), $a['destinos'], 'destino ligado ao post local' );
ok( ! $a['cadastur_verificado'], 'Cadastur não verificado segue a API' );
igual( array( 'Grupo', 'Natal Luz', 'Melhor idade' ), $GLOBALS['rt_obj_termos'][50]['rt_tipo_viagem'], 'tipos de viagem' );
igual( 'draft', get_post_status( 51 ), 'agente fora da API foi para rascunho' );

// Avaliações: sem Place ID vêm null -> nada; as fictícias acabaram
igual( array(), $a['avaliacoes'], 'sem avaliações do Google: vazio (nada de fictícias)' );

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
igual( 409, rtm_ci_webhook()->status, 'desligado: webhook recusa' );
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
rtm_ci_agendar();
igual( 'hourly', $GLOBALS['rt_agenda_rec']['rtm_ci_sincronizar_evento'], 'reserva de hora em hora' );
// Agenda antiga de 10 min é trocada
$GLOBALS['rt_agenda_rec']['rtm_ci_sincronizar_evento'] = 'rtm_10min';
rtm_ci_agendar();
igual( 'hourly', $GLOBALS['rt_agenda_rec']['rtm_ci_sincronizar_evento'], 'troca a agenda antiga' );

// Webhook
rtm_ci_rotas();
ok( isset( $GLOBALS['rt_rotas']['rede-marketplace/v1/sincronizar'] ), 'rota do webhook' );
igual( 'POST', $GLOBALS['rt_rotas']['rede-marketplace/v1/sincronizar']['methods'], 'só POST' );
$GLOBALS['rt_transients'] = array();
resposta_falsa( 200, $api );
$r = rtm_ci_webhook();
igual( 200, $r->status, 'webhook sincroniza na hora' );
igual( 3, $r->data['agentes'], 'webhook: 3 agentes' );
$n = count( $GLOBALS['rt_http_log'] );
$r = rtm_ci_webhook();
igual( 202, $r->status, 'segundo aviso em seguida: agenda' );
igual( $n, count( $GLOBALS['rt_http_log'] ), 'segundo aviso não chama a API na hora' );
ok( false !== wp_next_scheduled( 'rtm_ci_sincronizar_unica' ), 'rodada extra agendada' );
contem( '/wp-json/rede-marketplace/v1/sincronizar', rtm_ci_html_status(), 'tela mostra o endereço do webhook' );

// Opções
$o = rtm_limpar_opcoes( array( 'ci_ligado' => '1', 'ci_api' => 'http://inseguro.test' ) );
ok( $o['ci_ligado'], 'opção ligada' );
igual( '', $o['ci_api'], 'API só https' );
$o = rtm_limpar_opcoes( array() );
ok( ! $o['ci_ligado'], 'checkbox desmarcado desliga' );

// Status e aviso
contem( 'Sincronizar agora', rtm_ci_html_status(), 'botão sincronizar' );
contem( 'Sincronizado em', rtm_ci_html_status(), 'status mostra a última rodada' );
ob_start(); rtm_ci_aviso_edicao( get_post( 50 ) ); contem( 'vem do <b>Cliente Ideal</b>', ob_get_clean(), 'aviso na edição' );
ob_start(); rtm_ci_aviso_edicao( get_post( 51 ) ); igual( '', ob_get_clean(), 'sem aviso no agente local' );

// Avaliações reais do Google vindas da API
$api3 = $api;
$api3[0]['avaliacoes'] = array( 'fonte' => 'google', 'nota' => 4.1, 'total' => 1226, 'link' => 'https://maps.google.com/?cid=1', 'itens' => array(
	array( 'autor' => 'Ana <b>S</b>', 'autor_foto' => 'https://lh3.googleusercontent.com/a.png', 'autor_link' => 'https://www.google.com/maps/contrib/1', 'nota' => 5, 'texto' => 'Ótimo atendimento.', 'quando' => 'há 2 semanas' ),
	array( 'autor' => '', 'nota' => 5, 'texto' => 'sem autor' ),
) );
$GLOBALS['rt_transients'] = array();
resposta_falsa( 200, $api3 );
rtm_ci_sincronizar();
$a = rtm_agente( 50 );
igual( 4.1, $a['avaliacoes']['nota'], 'nota do Google' );
igual( 1226, $a['avaliacoes']['total'], 'total do Google' );
igual( 1, count( $a['avaliacoes']['itens'] ), 'avaliação sem autor fica de fora' );
igual( 'Ana S', $a['avaliacoes']['itens'][0]['autor'], 'autor sem HTML' );
$GLOBALS['rt_opcoes']['rtm_opcoes']['google_chave'] = 'chave';
$GLOBALS['rt_meta'][50]['rtm_google_place_id'] = 'ChIJx';
$a = rtm_agente( 50 );
ok( ! rtm_avaliacoes_no_navegador( $a ), 'com dados do Cliente Ideal não busca no navegador' );
$h = rtm_secao( 'avaliacoes', $a );
contem( 'href="https://maps.google.com/?cid=1"', $h, 'link para o Google Maps' );
contem( '<a href="https://www.google.com/maps/contrib/1" target="_blank" rel="noopener nofollow">Ana S</a>', $h, 'autor com link' );
contem( 'src="https://lh3.googleusercontent.com/a.png"', $h, 'foto do autor' );
contem( '<i>Google</i>', $h, 'atribuição Google' );
nao_contem( 'fictícias', $h, 'sem aviso de exemplo' );
contem( '4,1', rtm_html_nota( $a ), 'nota no topo' );

// Destino que é só categoria do blog (sem página de Destino)
$api4 = $api;
$api4[0]['destinos'][] = array( 'slug' => 'comandatuba', 'nome' => 'Comandatuba' );
$api4[0]['destino_principal'] = 'comandatuba';
resposta_falsa( 200, $api4 );
rtm_ci_sincronizar();
$a = rtm_agente( 50 );
igual( array( 'id' => 0, 'slug' => 'comandatuba', 'nome' => 'Comandatuba' ), $a['destinos'][1], 'categoria sem página é mantida' );
igual( 'comandatuba', $a['destino_principal'], 'destino principal pode ser categoria' );
ok( in_array( 'comandatuba', $a['destinos_slugs'], true ), 'filtro por destino inclui a categoria' );
igual( 'Comandatuba', rtm_titulo_destino( $a['destinos'][1] ), 'nome da categoria nos chips' );
$dc = rtm_destinos_cotacao( array( $a ) );
igual( array( 'comandatuba' => 'Comandatuba', 'gramado' => 'Gramado' ), $dc, 'lista de destinos com página e categoria, por nome' );
