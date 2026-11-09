<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_ag( $id, $nome, $ci, $destinos ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => $nome, 'post_name' => sanitize_title( $nome ), 'post_content' => '' );
	$d = array();
	foreach ( $destinos as $s ) { $d[] = array( 'id' => 0, 'slug' => $s, 'nome' => ucfirst( $s ) ); }
	$GLOBALS['rt_meta'][ $id ] = array( 'rtm_ativo' => '1', 'rtm_cadastur' => '21.123.456/0001-00', 'rtm_ci_id' => $ci, 'rtm_destinos' => $d );
}
rtm_t_ag( 5, 'Mariana Kloss', 'ci-mariana', array( 'gramado' ) );
rtm_t_ag( 6, 'Rafael Antunes', 'ci-rafael', array( 'gramado' ) );
rtm_t_ag( 7, 'Camila Duarte', 'ci-camila', array( 'bahia' ) );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 8, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => 'Local Sem CI', 'post_name' => 'local-sem-ci', 'post_content' => '' );
$GLOBALS['rt_meta'][8] = array( 'rtm_ativo' => '1', 'rtm_cadastur' => '21.123.456/0001-00' );

$GLOBALS['rt_opcoes']['rtm_opcoes'] = array( 'ensaio' => true, 'ci_chave' => '' );
$GLOBALS['rt_pode'] = true;
ok( ! rtm_envio_ativo(), 'sem chave: envio desligado' );
igual( '', rtm_envio_atributos(), 'sem chave: formulário sem data-envio' );
$r = rtm_envio_receber( new RT_Req_Falsa( array() ) );
igual( 403, $r->status, 'sem chave: 403' );

$GLOBALS['rt_opcoes']['rtm_opcoes']['ci_chave'] = 'segredo123';
$GLOBALS['rt_pode'] = false;
ok( ! rtm_envio_ativo(), 'ensaio: visitante não envia' );
$GLOBALS['rt_pode'] = true;
ok( rtm_envio_ativo(), 'ensaio: quem edita envia' );
$at = rtm_envio_atributos();
contem( 'data-envio="https://exemplo.test/wp-json/rede-marketplace/v1/cotacao"', $at, 'formulário com data-envio' );
nao_contem( 'segredo123', $at, 'chave nunca vai ao navegador' );
nao_contem( 'data-nonce', $at, 'sem login: sem nonce (página em cache)' );
$GLOBALS['rt_logado'] = true;
contem( 'data-nonce="ok_wp_rest"', rtm_envio_atributos(), 'logado: nonce do REST' );
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = false;
$GLOBALS['rt_pode'] = false;
ok( rtm_envio_ativo(), 'fora do ensaio: todos enviam' );

rtm_envio_rotas();
ok( isset( $GLOBALS['rt_rotas']['rede-marketplace/v1/cotacao'] ), 'rota da cotação' );
igual( 'POST', $GLOBALS['rt_rotas']['rede-marketplace/v1/cotacao']['methods'], 'só POST' );

$base = array( 'agentes' => array( 'mariana-kloss', 'rafael-antunes' ), 'nome' => 'Ana Souza', 'telefone' => '(11) 99999-9999', 'email' => 'ana@exemplo.com', 'lgpd' => true,
	'destino' => 'Gramado', 'destino_slug' => 'gramado', 'tipo' => 'Família', 'saida' => 'Campinas/SP', 'datas' => 'Ida 2026-12-10 · volta 2026-12-15', 'pessoas' => '2 adulto(s)',
	'inclui' => 'Aéreo, Hospedagem', 'orcamento' => 'Até R$ 5 mil', 'mensagem' => "Aniversário\nda filha", 'horario' => 'Noite', 'sugestao' => 'natal-luz',
	'origem_url' => 'https://exemplo.test/cotacao-de-viagens/?utm_source=google', 'utm' => array( 'utm_source' => 'google', 'gclid' => 'abc', 'x' => 'y' ), 'site' => '' );

list( $corpo, $ags ) = rtm_envio_corpo( $base );
igual( array( 'ci-mariana', 'ci-rafael' ), $corpo['agentes'], 'ids do Cliente Ideal dos escolhidos' );
igual( '11999999999', $corpo['telefone'], 'telefone só números' );
igual( 'https://exemplo.test/cotacao-de-viagens/?utm_source=google', $corpo['origem_url'], 'origem do site' );
igual( array( 'utm_source' => 'google', 'gclid' => 'abc' ), (array) $corpo['utm'], 'só UTMs conhecidas' );
contem( 'Tipo de viagem: Família', $corpo['mensagem'], 'mensagem com tipo' );
contem( 'Observações: Aniversário', $corpo['mensagem'], 'mensagem com observações' );
igual( 'natal-luz', $corpo['sugestao'], 'sugestão' );

$x = $base; $x['agentes'] = array( 'a', 'b', 'mariana-kloss', 'rafael-antunes', 'camila-duarte' );
list( $c2 ) = rtm_envio_corpo( $x );
igual( array( 'ci-mariana' ), $c2['agentes'], 'no máximo 3 slugs considerados; inexistentes ignorados' );
$x = $base; $x['agentes'] = array( 'local-sem-ci' );
igual( 'agentes', rtm_envio_corpo( $x )->get_error_code(), 'agente sem Cliente Ideal não vale' );
$x = $base; $x['agentes'] = array(); $x['rede'] = true; $x['destino_slug'] = 'bahia';
list( $c3 ) = rtm_envio_corpo( $x );
igual( array( 'ci-camila' ), $c3['agentes'], 'a Rede escolhe: quem atende o destino' );
contem( 'Rede Turística escolhesse', $c3['mensagem'], 'avisa que a Rede escolheu' );
$x['destino_slug'] = 'paris';
list( $c4 ) = rtm_envio_corpo( $x );
igual( 3, count( $c4['agentes'] ), 'a Rede escolhe sem ninguém no destino: até 3' );
$x = $base; $x['origem_url'] = 'https://outro.site/x';
list( $c5 ) = rtm_envio_corpo( $x );
igual( 'https://exemplo.test/', $c5['origem_url'], 'origem de fora vira a home' );
$x = $base; $x['site'] = 'http://spam';
igual( 'robo', rtm_envio_corpo( $x )->get_error_code(), 'campo isca recusa' );
$x = $base; $x['nome'] = '';
igual( 'nome', rtm_envio_corpo( $x )->get_error_code(), 'nome obrigatório' );
$x = $base; $x['nome'] = 'Ana 123';
igual( 'nome', rtm_envio_corpo( $x )->get_error_code(), 'nome com números recusado' );
$x = $base; $x['nome'] = "  José   D'Ávila  ";
list( $cn ) = rtm_envio_corpo( $x );
igual( "José D'Ávila", $cn['nome'], 'nome com acento e espaços arrumados' );
$x = $base; $x['telefone'] = '12';
igual( 'whats', rtm_envio_corpo( $x )->get_error_code(), 'WhatsApp curto recusado' );
$x = $base; $x['telefone'] = '(11) 89999-9999';
igual( 'whats', rtm_envio_corpo( $x )->get_error_code(), 'celular sem o 9 recusado' );
$x = $base; $x['telefone'] = '+55 (11) 99999-9999';
list( $ct ) = rtm_envio_corpo( $x );
igual( '11999999999', $ct['telefone'], '+55 removido' );
igual( '1133334444', rtm_envio_tel( '(11) 3333-4444' ), 'fixo aceito' );
$x = $base; $x['email'] = 'x';
igual( 'email', rtm_envio_corpo( $x )->get_error_code(), 'e-mail inválido recusado' );
$x = $base; $x['email'] = 'ana@exemplo';
igual( 'email', rtm_envio_corpo( $x )->get_error_code(), 'e-mail sem domínio recusado' );
$x = $base; $x['email'] = 'Ana@Exemplo.COM';
list( $ce ) = rtm_envio_corpo( $x );
igual( 'ana@exemplo.com', $ce['email'], 'e-mail em minúsculas' );
$d = function ( $k ) { return date( 'Y-m-d', time() + $k * DAY_IN_SECONDS ); };
$x = $base; $x['ida'] = $d( 10 ); $x['volta'] = $d( 5 );
igual( 'datas', rtm_envio_corpo( $x )->get_error_code(), 'volta antes da ida recusada' );
$x['ida'] = $d( -5 ); $x['volta'] = $d( 5 );
igual( 'datas', rtm_envio_corpo( $x )->get_error_code(), 'ida no passado recusada' );
$x['ida'] = '2026-02-31'; $x['volta'] = '';
igual( 'datas', rtm_envio_corpo( $x )->get_error_code(), 'data inexistente recusada' );
$x['ida'] = $d( 10 ); $x['volta'] = $d( 15 );
list( $cd ) = rtm_envio_corpo( $x );
igual( 'Ida ' . date( 'd/m/Y', strtotime( $d( 10 ) ) ) . ' · volta ' . date( 'd/m/Y', strtotime( $d( 15 ) ) ), $cd['datas'], 'datas em dd/mm/aaaa' );
$x = $base; $x['lgpd'] = false;
igual( 'lgpd', rtm_envio_corpo( $x )->get_error_code(), 'LGPD obrigatória' );

// Envio de verdade (resposta falsa do Cliente Ideal)
$GLOBALS['rt_transients'] = array();
$GLOBALS['rt_http_log'] = array();
resposta_falsa( 201, array( 'pedido_id' => 'p1', 'envios' => array( array( 'agente_id' => 'ci-mariana', 'status' => 'novo' ) ) ) );
$r = rtm_envio_receber( new RT_Req_Falsa( $base ) );
igual( 200, $r->status, 'enviado' );
ok( $r->data['ok'], 'ok' );
igual( array( 'Mariana Kloss', 'Rafael Antunes' ), $r->data['agentes'], 'nomes para o obrigado' );
igual( RTM_CI_COTACAO_PADRAO, $GLOBALS['rt_http_log'][0][0], 'chama a função rede-cotacao' );
igual( 'segredo123', $GLOBALS['rt_http_log'][0][1]['headers']['X-Rede-Chave'], 'manda a chave no cabeçalho' );
$enviado = json_decode( $GLOBALS['rt_http_log'][0][1]['body'], true );
igual( 'Ana Souza', $enviado['nome'], 'corpo em JSON' );

resposta_falsa( 404, array( 'error' => 'agente_fora_do_ar' ) );
$r = rtm_envio_receber( new RT_Req_Falsa( $base ) );
igual( 409, $r->status, 'agente fora do ar: 409' );
contem( 'não está mais disponível', $r->data['msg'], 'mensagem para recarregar' );
igual( 'agente_fora_do_ar', get_option( 'rtm_envio_ultimo' )['erro'], 'erro guardado para conferência' );
resposta_falsa( 500, array( 'error' => 'erro_interno' ) );
igual( 502, rtm_envio_receber( new RT_Req_Falsa( $base ) )->status, 'falha do Cliente Ideal: 502' );
$r = rtm_envio_receber( new RT_Req_Falsa( array( 'nome' => '' ) ) );
igual( 400, $r->status, 'pedido inválido: 400' );
// Limite: 5 por IP a cada 10 minutos
resposta_falsa( 201, array() );
igual( 200, rtm_envio_receber( new RT_Req_Falsa( $base ) )->status, '5º pedido passa' );
igual( 429, rtm_envio_receber( new RT_Req_Falsa( $base ) )->status, '6º pedido seguido: 429' );

// Configurações: chave nunca aparece; em branco mantém
$o = rtm_limpar_opcoes( array( 'ci_chave' => '' ) );
igual( 'segredo123', $o['ci_chave'], 'campo em branco mantém a chave' );
$o = rtm_limpar_opcoes( array( 'ci_chave' => '', 'ci_chave_apagar' => '1' ) );
igual( '', $o['ci_chave'], 'apagar limpa a chave' );
$o = rtm_limpar_opcoes( array( 'ci_chave' => 'nova<b>' ) );
igual( 'novab', $o['ci_chave'], 'chave nova limpa' );
$GLOBALS['rt_pode'] = true;
ob_start(); rtm_tela_configuracoes(); $tela = ob_get_clean();
nao_contem( 'segredo123', $tela, 'tela não mostra a chave' );
contem( 'Guardada (deixe em branco para manter)', $tela, 'tela diz que a chave está guardada' );

// Formulário com campo isca
$GLOBALS['rt_opcoes']['rtm_opcoes']['ensaio'] = true;
$html = rtm_cotacao_html( array() );
contem( 'id="rtm-site"', $html, 'campo isca no formulário' );
contem( 'data-envio=', $html, 'formulário com envio para quem edita' );
