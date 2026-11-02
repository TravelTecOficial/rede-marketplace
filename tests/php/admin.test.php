<?php
require '/t/bootstrap.php';
require '/p/rede-marketplace.php';

function rtm_t_dest( $id, $nome, $status = 'publish' ) {
	$GLOBALS['rt_posts'][] = (object) array( 'ID' => $id, 'post_type' => 'destinos', 'post_status' => $status, 'post_title' => $nome, 'post_name' => sanitize_title( $nome ) );
}
rtm_t_dest( 10, 'Gramado' );
rtm_t_dest( 11, 'Canela' );
rtm_t_dest( 12, 'Rascunho', 'draft' );
$GLOBALS['rt_posts'][] = (object) array( 'ID' => 50, 'post_type' => 'rt_agente', 'post_status' => 'publish', 'post_title' => 'Ana', 'post_name' => 'ana' );

// Ganchos
ok( in_array( 'rtm_salvar', $GLOBALS['rt_ganchos']['save_post_rt_agente'], true ), 'save_post_rt_agente -> rtm_salvar' );
$i = array_search( 'rtm_salvar', $GLOBALS['rt_ganchos']['save_post_rt_agente'], true );
igual( 10, $GLOBALS['rt_prioridades']['save_post_rt_agente'][ $i ], 'rtm_salvar com prioridade 10' );
ok( isset( $GLOBALS['rt_ganchos']['add_meta_boxes'] ) && isset( $GLOBALS['rt_ganchos']['admin_menu'] ) && isset( $GLOBALS['rt_ganchos']['admin_init'] ), 'ganchos de caixa, menu e opções registrados' );
ok( isset( $GLOBALS['rt_ganchos']['manage_rt_agente_posts_columns'] ) && isset( $GLOBALS['rt_ganchos']['manage_rt_agente_posts_custom_column'] ), 'colunas registradas' );

// Limpeza
$l = rtm_limpar_campos( array( 'whatsapp' => '(11) 99999-0000' ) );
igual( '11999990000', $l['whatsapp'], 'whatsapp só dígitos' );
igual( '', rtm_limpar_campos( array( 'whatsapp' => '123' ) )['whatsapp'], 'whatsapp curto -> vazio' );
igual( '', rtm_limpar_campos( array( 'whatsapp' => '551199999000012' ) )['whatsapp'], 'whatsapp longo -> vazio' );
igual( '1133334444', rtm_limpar_campos( array( 'whatsapp' => '(11) 3333-4444' ) )['whatsapp'], 'whatsapp fixo 10 dígitos' );
igual( '', rtm_limpar_campos( array( 'email' => 'x@' ) )['email'], 'email inválido -> vazio' );
igual( 'a@b.com', rtm_limpar_campos( array( 'email' => ' a@b.com ' ) )['email'], 'email válido' );
igual( 180, mb_strlen( rtm_limpar_campos( array( 'texto_card' => str_repeat( 'é', 250 ) ) )['texto_card'] ), 'texto_card cortado em 180' );
$e = rtm_limpar_campos( array( 'especialidades' => array(
	array( 'titulo' => 'a', 'descricao' => 'da' ), array( 'titulo' => '', 'descricao' => 'sem título' ), array( 'titulo' => 'b' ), array( 'titulo' => ' ', 'descricao' => 'x' ),
	array( 'titulo' => 'c', 'descricao' => ' <b>dc</b> ' ), array( 'titulo' => 'd', 'descricao' => '' ), array( 'titulo' => 'e', 'descricao' => 'de' ), array( 'titulo' => 'f', 'descricao' => 'df' ),
) ) )['especialidades'];
igual( array(
	array( 'titulo' => 'a', 'descricao' => 'da' ), array( 'titulo' => 'b', 'descricao' => '' ),
	array( 'titulo' => 'c', 'descricao' => 'dc' ), array( 'titulo' => 'd', 'descricao' => '' ),
), $e, 'especialidades: {titulo,descricao}, sem título descartada, sanitizada, máx 4' );
$e = rtm_limpar_campos( array( 'especialidades' => array( array( 'titulo' => 'Família', 'descricao' => str_repeat( 'é', 200 ) ) ) ) )['especialidades'];
igual( 120, mb_strlen( $e[0]['descricao'] ), 'especialidades: descrição cortada em 120 (mb)' );
igual( array( array( 'titulo' => 'Luxo', 'descricao' => '' ), array( 'titulo' => 'Grupos', 'descricao' => '' ) ), rtm_limpar_campos( array( 'especialidades' => array( 'Luxo', '', 'Grupos' ) ) )['especialidades'], 'especialidades: texto simples antigo vira {titulo, descricao vazia}' );
igual( array(), rtm_limpar_campos( array() )['especialidades'], 'especialidades ausentes -> lista vazia' );
$d = rtm_limpar_campos( array( 'destinos' => array( '10', '999', '12', '10', '11' ), 'destino_principal' => 'paris' ) );
igual( array( array( 'id' => 10, 'slug' => 'gramado' ), array( 'id' => 11, 'slug' => 'canela' ) ), $d['destinos'], 'destinos: só publicados existentes, sem repetição, {id,slug}' );
igual( 'gramado', $d['destino_principal'], 'destino_principal inválido -> primeiro' );
igual( 'canela', rtm_limpar_campos( array( 'destinos' => array( 10, 11 ), 'destino_principal' => 'canela' ) )['destino_principal'], 'destino_principal válido mantido' );
$d2 = rtm_limpar_campos( array( 'destinos' => array( 999 ), 'destino_principal' => 'x' ) );
igual( array( array(), '' ), array( $d2['destinos'], $d2['destino_principal'] ), 'sem destinos válidos -> vazio' );
igual( array(), rtm_limpar_campos( array() )['destinos'], 'destinos ausentes -> lista vazia' );
igual( '', rtm_limpar_campos( array( 'cadastur_conferido_em' => '31/02/2026' ) )['cadastur_conferido_em'], 'data inválida -> vazio' );
igual( '', rtm_limpar_campos( array( 'cadastur_conferido_em' => '2026-02-31' ) )['cadastur_conferido_em'], 'data inexistente -> vazio' );
igual( '2026-03-05', rtm_limpar_campos( array( 'cadastur_conferido_em' => '2026-03-05' ) )['cadastur_conferido_em'], 'data válida' );
igual( array( 12, 0, '', '' ), array(
	rtm_limpar_campos( array( 'anos_experiencia' => '12' ) )['anos_experiencia'],
	rtm_limpar_campos( array( 'viajantes_atendidos' => '0' ) )['viajantes_atendidos'],
	rtm_limpar_campos( array( 'anos_experiencia' => '-3' ) )['anos_experiencia'],
	rtm_limpar_campos( array( 'agencia_desde' => 'abc' ) )['agencia_desde'],
), 'números: inteiro >= 0 ou vazio' );
igual( '1', rtm_limpar_campos( array( 'ativo' => '1' ) )['ativo'], 'ativo 1' );
igual( '0', rtm_limpar_campos( array() )['ativo'], 'ativo ausente -> 0' );
igual( '0', rtm_limpar_campos( array( 'ativo' => 'sim' ) )['ativo'], 'ativo estranho -> 0' );
igual( 'Dir', rtm_limpar_campos( array( 'cargo' => ' <b>Dir</b> ' ) )['cargo'], 'texto sanitizado' );
nao_contem( '<script', rtm_limpar_campos( array( 'apresentacao' => 'oi<script>x</script>' ) )['apresentacao'], 'apresentação passa por kses' );
igual( 7, rtm_limpar_campos( array( 'agencia_logo' => '7' ) )['agencia_logo'], 'logo = id do anexo' );
igual( '', rtm_limpar_campos( array( 'agencia_logo' => 'x' ) )['agencia_logo'], 'logo inválido -> vazio' );
igual( array( 'pt', 'en' ), rtm_limpar_campos( array( 'idiomas' => 'pt, en,, ' ) )['idiomas'], 'idiomas separados por vírgula' );
igual( array_keys( rtm_campos() ), array_keys( rtm_limpar_campos( array() ) ), 'devolve todas as chaves de rtm_campos()' );

// Salvamento
$_POST = array( 'rtm_agente' => 'errado', 'rtm' => array( 'cargo' => 'X' ) );
rtm_salvar( 50 );
igual( array(), get_post_meta( 50 ), 'sem nonce válido não grava' );
$_POST = array( 'rtm_agente' => 'ok_rtm_agente', 'rtm' => array( 'cargo' => 'Diretora', 'whatsapp' => '(11) 99999-0000', 'ativo' => '1', 'destinos' => array( '10' ) ) );
$GLOBALS['rt_pode'] = false;
rtm_salvar( 50 );
igual( array(), get_post_meta( 50 ), 'sem permissão não grava' );
$GLOBALS['rt_pode'] = true;
$GLOBALS['rt_revisoes'][50] = true;
rtm_salvar( 50 );
igual( array(), get_post_meta( 50 ), 'revisão não grava' );
$GLOBALS['rt_revisoes'] = array();
$_POST['rtm_agente'] = 'ok_rtm_agente';
rtm_salvar( 50 );
igual( 'Diretora', get_post_meta( 50, 'rtm_cargo', true ), 'com nonce e permissão grava rtm_cargo' );
igual( '11999990000', get_post_meta( 50, 'rtm_whatsapp', true ), 'grava whatsapp limpo' );
igual( '1', get_post_meta( 50, 'rtm_ativo', true ), 'grava ativo' );
igual( array( array( 'id' => 10, 'slug' => 'gramado' ) ), get_post_meta( 50, 'rtm_destinos', true ), 'grava destinos {id,slug}' );
igual( 'gramado', get_post_meta( 50, 'rtm_destino_principal', true ), 'grava destino principal' );
igual( 'publish', get_post( 10 )->post_status, 'destinos não é alterado' );

// Imagem destacada/logo não são tocadas e pós-autosave nada grava
$GLOBALS['rt_meta'][50] = array();
define( 'DOING_AUTOSAVE', true );
rtm_salvar( 50 );
igual( array(), get_post_meta( 50 ), 'autosave não grava' );

// Configurações
$o = rtm_limpar_opcoes( array( 'url_cotacao' => ' https://exemplo.test/cot/ ', 'ensaio' => '1' ) );
igual( array( 'https://exemplo.test/cot/', true ), array( $o['url_cotacao'], $o['ensaio'] ), 'opções: url e ensaio ligado' );
$o = rtm_limpar_opcoes( array() );
igual( false, $o['ensaio'], 'opções: checkbox ausente -> desligado' );
igual( home_url( '/cotacao-especialistas/' ), $o['url_cotacao'], 'opções: url vazia -> padrão' );
