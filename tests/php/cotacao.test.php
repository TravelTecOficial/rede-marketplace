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
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1', 'cadastur_conferido_em' => '2026-11-02', 'whatsapp' => '11999990000', 'email' => 'm@segredo.com', 'company_id' => 'company-x', 'destinos' => $g ) );
rtm_t_post( 6, 'Rafael Antunes', array( 'ativo' => '1', 'cadastur_conferido_em' => '2026-11-02', 'destinos' => $g ) );
rtm_t_post( 7, 'Inativa', array( 'ativo' => '0', 'destinos' => array( array( 'id' => 12, 'slug' => 'paris' ) ) ) );
rtm_t_post( 8, 'Agente de Canela', array( 'ativo' => '1', 'cadastur_conferido_em' => '2026-11-02', 'destinos' => array( array( 'id' => 13, 'slug' => 'canela' ) ) ) );

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
contem( '<input type="text" id="rtm-destino" name="destino" list="rtm-destinos-lista"', $html, 'destino: campo de digitar com sugestões' );
contem( 'nos melhores destinos</span>', $html, 'título genérico sem destino' );
preg_match( '/<datalist id="rtm-destinos-lista">.*?<\/datalist>/s', $html, $sel );
contem( '<option value="Gramado" data-slug="gramado"></option>', $sel[0], 'destino: gramado sugerido (value=título, data-slug)' );
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
contem( 'Estamos reunindo os primeiros especialistas', $vazio, 'sem agentes: estado vazio amigável' );
contem( '/seja-um-agente/', $vazio, 'sem agentes: link para Seja um agente' );
nao_contem( '<form', $vazio, 'sem agentes: sem formulário' );
// Agente sem Cadastur conferido não conta
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1' ) );
contem( 'Estamos reunindo', rtm_cotacao_html(), 'sem Cadastur conferido: não aparece' );
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1', 'cadastur_conferido_em' => '2026-11-02' ) );
rtm_t_post( 6, 'Rafael Antunes', array( 'ativo' => '1', 'cadastur_conferido_em' => '2026-11-02', 'cadastur_validade' => '2020-01-01' ) );
nao_contem( 'Rafael Antunes', rtm_cotacao_html(), 'Cadastur vencido: não aparece' );

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
rtm_t_post( 5, 'Mariana Kloss', array( 'ativo' => '1', 'cadastur_conferido_em' => '2026-11-02' ) );
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

