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
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1', 'whatsapp' => '11999990000', 'email' => 'm@segredo.com', 'company_id' => 'company-x', 'destinos' => $g ) );
rtm_t_post( 6, 'Rafael Antunes', array( 'ativo' => '1', 'destinos' => $g ) );
rtm_t_post( 7, 'Inativa', array( 'ativo' => '0', 'destinos' => array( array( 'id' => 12, 'slug' => 'paris' ) ) ) );
rtm_t_post( 8, 'Agente de Canela', array( 'ativo' => '1', 'destinos' => array( array( 'id' => 13, 'slug' => 'canela' ) ) ) );

ok( isset( $GLOBALS['rt_shortcodes']['rt_cotacao'] ), 'shortcode rt_cotacao registrado' );
$GLOBALS['rt_estilos'] = array();
$html = call_user_func( $GLOBALS['rt_shortcodes']['rt_cotacao'] );
ok( isset( $GLOBALS['rt_estilos']['rede-marketplace'] ), 'shortcode enfileira o CSS (shortcode num modelo, fora do conteúdo da página)' );
ok( isset( $GLOBALS['rt_scripts']['rtm-cotacao'] ), 'shortcode enfileira o JS' );
igual( $html, rtm_cotacao_html(), 'shortcode devolve rtm_cotacao_html()' );

// Estrutura e IDs (contrato com cotacao.js)
contem( '<form id="rtm-cotacao" novalidate>', $html, 'form com id e novalidate' );
nao_contem( 'action=', $html, 'sem atributo action' );
foreach ( array( 'rtm-titulo-destino', 'rtm-destino', 'rtm-origem', 'rtm-tipos', 'rtm-lista', 'rtm-vazio', 'rtm-rede', 'rtm-datas', 'rtm-ida', 'rtm-volta', 'rtm-mes', 'rtm-adultos', 'rtm-criancas', 'rtm-bebes', 'rtm-inclui', 'rtm-orcamento', 'rtm-obs', 'rtm-nome', 'rtm-whats', 'rtm-email', 'rtm-horario', 'rtm-lgpd', 'rtm-aviso-ensaio', 'rtm-contagem', 'rtm-enviar' ) as $id ) {
	contem( 'id="' . $id . '"', $html, "id $id presente" );
}
foreach ( array( 1, 2, 3, 4 ) as $n ) { contem( 'data-etapa="' . $n . '"', $html, "etapa $n" ); }
contem( 'Pedir cotações', $html, 'botão Pedir cotações' );
contem( 'Receba até 3 cotações de especialistas', $html, 'título neutro' );
preg_match( '/<[a-z]+ [^>]*id="rtm-vazio"[^>]*>/', $html, $m );
contem( 'hidden', $m[0], 'rtm-vazio hidden' );
preg_match( '/<[a-z]+ [^>]*id="rtm-aviso-ensaio"[^>]*>/', $html, $m );
contem( 'hidden', $m[0], 'rtm-aviso-ensaio hidden' );
preg_match( '/<label [^>]*>[^<]*<input [^>]*id="rtm-mes"[^>]*>/', $html, $m ) || preg_match( '/<label [^>]*hidden[^>]*for="rtm-mes"|<label [^>]*for="rtm-mes"[^>]*hidden/', $html, $m );
ok( ! empty( $m ), 'mês escondido por padrão (label hidden)' );
contem( 'type="month"', $html, 'campo de mês' );
contem( 'value="2" selected', $html, 'adultos padrão 2' );
contem( 'Autorizo a Rede Turística a enviar meus dados aos especialistas escolhidos para que preparem as cotações, conforme a', $html, 'texto LGPD' );
contem( 'Prefiro que a <b>Rede Turística</b> escolha os especialistas para mim', $html, 'texto da Rede' );
foreach ( array( 'certas', 'mes', 'aberto' ) as $v ) { contem( 'data-v="' . $v . '"', $html, "data pílula $v" ); }

// Orçamento, tipos, inclui, horário
foreach ( array( 'Até R$ 2.000', 'R$ 2.000 a R$ 3.500', 'R$ 3.500 a R$ 5.000', 'R$ 5.000 a R$ 8.000', 'Acima de R$ 8.000', 'Ainda não defini' ) as $o ) {
	contem( '>' . $o . '<', $html, "orçamento: $o" );
}
igual( 6, preg_match_all( '/<option value="[^"]*"[^>]*>(?:Até|R\$|Acima|Ainda)[^<]*<\/option>/u', $html ), 'exatamente 6 opções de orçamento' );
foreach ( array( 'Família', 'Casal / Lua de mel', 'Natal Luz', 'Grupo', 'Melhor idade', 'Luxo' ) as $t ) {
	contem( 'data-tipo="' . $t . '"', $html, "tipo: $t" );
}
igual( 6, substr_count( $html, 'data-tipo=' ), 'seis pílulas de tipo' );
foreach ( array( 'Aéreo', 'Hospedagem', 'Passeios', 'Ingressos Natal Luz', 'Transfer', 'Carro', 'Qualquer horário', 'Manhã', 'Tarde', 'Noite' ) as $t ) {
	contem( '>' . $t . '<', $html, "opção: $t" );
}

// Tipos vêm dos termos quando existem
$GLOBALS['rt_taxonomias']['rt_tipo_viagem']['criados'] = array( 'Aventura' => 1, 'Família' => 2 );
$h2 = rtm_cotacao_html();
contem( 'data-tipo="Aventura"', $h2, 'tipos: usa os termos de rt_tipo_viagem' );
nao_contem( 'data-tipo="Luxo"', $h2, 'tipos: termos substituem a lista inicial' );
unset( $GLOBALS['rt_taxonomias']['rt_tipo_viagem'] );

// Destinos: só publicados com agente ativo
preg_match( '/<select [^>]*id="rtm-destino".*?<\/select>/s', $html, $sel );
contem( '<option value="gramado">Gramado</option>', $sel[0], 'destino: gramado listado (value=slug, label=título)' );
nao_contem( 'paris', $sel[0], 'destino: paris (só agente inativo) não listado' );
nao_contem( 'canela', $sel[0], 'destino: canela (rascunho) não listado' );

// Cards: só ativos
igual( 3, substr_count( $html, 'class="rtm-card"' ), '3 cards (ativos)' );
nao_contem( 'Inativa', $html, 'agente inativo fora' );
contem( 'data-slug="mariana-kloss"', $html, 'card da Mariana' );

// Privados
foreach ( array( '11999990000', 'company-', 'segredo.com', 'company_id', 'rtm_whatsapp' ) as $p ) { nao_contem( $p, $html, "privado $p fora" ); }

// Política de privacidade
nao_contem( '<a href=', substr( $html, strpos( $html, 'id="rtm-lgpd"' ) ), 'sem política configurada: sem link' );
contem( 'Política de Privacidade', $html, 'texto Política de Privacidade' );
$GLOBALS['rt_privacidade'] = 'https://exemplo.test/politica-de-privacidade/';
contem( '<a href="https://exemplo.test/politica-de-privacidade/"', rtm_cotacao_html(), 'link da política do site' );
$GLOBALS['rt_privacidade'] = 'javascript:alert(1)';
nao_contem( 'javascript:', rtm_cotacao_html(), 'URL da política escapada' );
$GLOBALS['rt_privacidade'] = '';

// Script enfileirado
ok( isset( $GLOBALS['rt_scripts']['rtm-cotacao'] ), 'script rtm-cotacao enfileirado' );
igual( 'https://exemplo.test/wp-content/plugins/rede-marketplace/assets/cotacao.js', $GLOBALS['rt_scripts']['rtm-cotacao']['src'], 'src do script' );
igual( RTM_VERSION, $GLOBALS['rt_scripts']['rtm-cotacao']['ver'], 'versão do script' );
ok( true === $GLOBALS['rt_scripts']['rtm-cotacao']['rodape'], 'script no rodapé' );
ok( file_exists( '/p/assets/cotacao.js' ), 'cotacao.js existe' );

// Sem agentes: página não quebra
$GLOBALS['rt_posts'] = array();
$vazio = rtm_cotacao_html();
contem( 'id="rtm-lista"', $vazio, 'sem agentes: lista vazia presente' );
igual( 0, substr_count( $vazio, 'class="rtm-card"' ), 'sem agentes: nenhum card' );

// CSS do formulário com escopo
$css = file_get_contents( '/p/assets/rede-marketplace.css' );
foreach ( array( '.rtm-cotacao h1.rtm-titulo', '.rtm-cotacao label.rtm-rot', '.rtm-cotacao input[type=text]', '.rtm-cotacao .rtm-cbarra button.rtm-enviar', '.rtm-cotacao h2.rtm-etapa-h' ) as $s ) {
	contem( $s, $css, "CSS: $s" );
}
