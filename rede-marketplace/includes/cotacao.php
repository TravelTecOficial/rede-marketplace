<?php
/** Página de cotação: formulário em 4 etapas (HTML do servidor). O comportamento fica em assets/cotacao.js. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Faixas de orçamento por pessoa (texto exato; vai como o rótulo e o valor). */
function rtm_orcamentos() {
	return array( 'Até R$ 2.000', 'R$ 2.000 a R$ 3.500', 'R$ 3.500 a R$ 5.000', 'R$ 5.000 a R$ 8.000', 'Acima de R$ 8.000', 'Ainda não defini' );
}

/** Tipos de viagem: termos de rt_tipo_viagem, ou a lista inicial se ainda não houver termos. */
function rtm_tipos_cotacao() {
	$nomes  = array();
	$termos = get_terms( array( 'taxonomy' => 'rt_tipo_viagem', 'hide_empty' => false ) );
	if ( is_array( $termos ) ) {
		foreach ( $termos as $t ) {
			if ( is_object( $t ) && '' !== trim( (string) $t->name ) ) {
				$nomes[] = (string) $t->name;
			}
		}
	}
	return $nomes ? $nomes : rtm_tipos_iniciais();
}

/** Destinos com ao menos um agente ativo: slug => nome (título da página de Destino, se houver; senão o da categoria). */
function rtm_destinos_cotacao( array $agentes ) {
	$res = array();
	foreach ( $agentes as $a ) {
		foreach ( $a['destinos'] as $d ) {
			if ( is_array( $d ) && ! empty( $d['slug'] ) && ! isset( $res[ (string) $d['slug'] ] ) ) {
				$nome = rtm_titulo_destino( $d );
				if ( '' !== $nome ) {
					$res[ (string) $d['slug'] ] = $nome;
				}
			}
		}
	}
	uasort( $res, function ( $x, $y ) {
		return strcmp( mb_strtolower( remove_accents( $x ) ), mb_strtolower( remove_accents( $y ) ) );
	} );
	return $res;
}

/** Sugestões publicadas de agentes ativos, para a cotação marcar a que veio na URL (?sugestao=). */
function rtm_sugestoes_cotacao() {
	$res = array();
	foreach ( get_posts( array( 'post_type' => 'rt_sugestao', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $p ) {
		$s = rtm_sugestao( $p->ID );
		if ( $s ) {
			$res[ $s['slug'] ] = array( 'titulo' => $s['titulo'], 'agente' => $s['agente']['slug'], 'agente_nome' => $s['agente']['nome'], 'destino' => (string) $s['destino'] );
		}
	}
	return $res;
}

/** Pílulas (botões alternáveis). $itens: valor => rótulo. */
function rtm_cot_pilulas( array $itens, $atributo, array $marcados = array() ) {
	$h = '';
	foreach ( $itens as $valor => $rotulo ) {
		$h .= '<button type="button" class="rtm-pilula" ' . $atributo . '="' . esc_attr( $valor ) . '" aria-pressed="' . ( in_array( (string) $valor, $marcados, true ) ? 'true' : 'false' ) . '">' . esc_html( $rotulo ) . '</button>';
	}
	return $h;
}

function rtm_cot_select( $id, array $opcoes, $padrao, $rotulo, $cheio = false ) {
	$h = '<label class="rtm-rot' . ( $cheio ? ' rtm-cheio' : '' ) . '" for="' . esc_attr( $id ) . '">' . esc_html( $rotulo ) . '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( substr( $id, 4 ) ) . '">';
	foreach ( $opcoes as $o ) {
		$h .= '<option value="' . esc_attr( $o ) . '"' . ( (string) $o === (string) $padrao ? ' selected' : '' ) . '>' . esc_html( $o ) . '</option>';
	}
	return $h . '</select></label>';
}

function rtm_cot_etapa_titulo( $n, $titulo, $dica ) {
	return '<div class="rtm-etapa-tit"><span class="rtm-num" aria-hidden="true">' . (int) $n . '</span><div><h2 class="rtm-etapa-h">' . esc_html( $titulo ) . '</h2><p class="rtm-etapa-dica">' . esc_html( $dica ) . '</p></div></div>';
}

/**
 * Destino fixo do shortcode: destino="atual" (ou vazio) dentro de uma página do tipo `destinos` usa o próprio
 * destino; destino="<slug>" usa esse. Devolve [slug, título] ou null (página de cotação comum).
 */
function rtm_cotacao_destino_fixo( $atributo ) {
	$atributo = trim( (string) $atributo );
	if ( '' === $atributo || 'atual' === $atributo ) {
		if ( ! is_singular( 'destinos' ) ) {
			return null;
		}
		$p = get_post( get_queried_object_id() );
		return $p ? array( (string) $p->post_name, (string) $p->post_title ) : null;
	}
	$posts = get_posts( array( 'post_type' => 'destinos', 'post_status' => 'publish', 'name' => sanitize_title( $atributo ), 'posts_per_page' => 1 ) );
	return $posts ? array( (string) $posts[0]->post_name, (string) $posts[0]->post_title ) : null;
}

/**
 * Shortcode [rt_cotacao]: HTML completo da página de cotação. Nada é enviado pela rede a partir daqui.
 * No modelo da página de Destino ([rt_cotacao destino="atual"]) o destino vem da página, o título vira h2
 * (a página já tem h1) e a barra de envio só acompanha enquanto o formulário está na tela.
 */
function rtm_cotacao_html( $atts = array() ) {
	$atts = shortcode_atts( array( 'destino' => '' ), $atts, 'rt_cotacao' );
	// Também aqui (e não só em rtm_estilos): o shortcode pode estar num modelo do Elementor, fora do conteúdo da página.
	rtm_enfileirar_estilo();
	wp_enqueue_script( 'rtm-cotacao', RTM_URL . 'assets/cotacao.js', array(), RTM_VERSION, true );

	$agentes  = rtm_agentes_ativos();
	$destinos = rtm_destinos_cotacao( $agentes );
	$fixo     = rtm_cotacao_destino_fixo( $atts['destino'] );
	if ( $fixo && ! isset( $destinos[ $fixo[0] ] ) ) {
		$destinos = array( $fixo[0] => $fixo[1] ) + $destinos; // destino ainda sem especialista: aparece assim mesmo
	}
	$tag      = $fixo ? 'h2' : 'h1';
	$tipos    = rtm_tipos_cotacao();
	$politica = esc_url( (string) get_privacy_policy_url() );
	$inclui   = array( 'Aéreo', 'Hospedagem', 'Passeios', 'Ingressos Natal Luz', 'Transfer', 'Carro' );

	$h  = '<div class="rtm-cotacao' . ( $fixo ? ' rtm-cotacao-destino" data-destino="' . esc_attr( $fixo[0] ) : '' ) . '">';
	$h .= '<div class="rtm-intro"><small>Pedido de cotação</small>';
	$h .= '<' . $tag . ' class="rtm-titulo">Receba até 3 cotações de especialistas<span id="rtm-titulo-destino">' . esc_html( $fixo ? ' em ' . $fixo[1] : ' nos melhores destinos' ) . '</span></' . $tag . '>';
	$h .= '<p class="rtm-lead">Conte como é a sua viagem, escolha quem vai montá-la e receba as propostas no seu WhatsApp. Sem custo e sem compromisso.</p>';
	$h .= '<div class="rtm-como"><span><b>1.</b> Conte sua viagem</span><span><b>2.</b> Escolha os especialistas</span><span><b>3.</b> Compare e decida</span></div></div>';

	$h .= '<div class="rtm-sugestao-aviso" id="rtm-sugestao-aviso" role="status" hidden><span id="rtm-sugestao-texto"></span>'
		. '<button type="button" class="rtm-sugestao-tirar" id="rtm-sugestao-tirar">Tirar</button></div>';
	$h .= '<script type="application/json" id="rtm-sugestoes-dados">' . wp_json_encode( (object) rtm_sugestoes_cotacao(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>';
	$h .= '<form id="rtm-cotacao" novalidate>';

	// Etapa 1: a viagem.
	$h .= '<section class="rtm-etapa" data-etapa="1">' . rtm_cot_etapa_titulo( 1, 'Sua viagem', 'O tipo de viagem mostra os especialistas certos para você.' );
	$h .= '<div class="rtm-campos">';
	// Destino digitável (qualquer lugar); a lista sugere os destinos que têm especialista.
	$h .= '<label class="rtm-rot" for="rtm-destino">Destino<input type="text" id="rtm-destino" name="destino" list="rtm-destinos-lista" autocomplete="off" placeholder="Para onde você quer ir?" value="' . esc_attr( $fixo ? $fixo[1] : '' ) . '"></label>';
	$h .= '<datalist id="rtm-destinos-lista">';
	foreach ( $destinos as $slug => $titulo ) {
		$h .= '<option value="' . esc_attr( $titulo ) . '" data-slug="' . esc_attr( $slug ) . '"></option>';
	}
	$h .= '</datalist>';
	$h .= '<label class="rtm-rot" for="rtm-origem">Cidade de saída<input type="text" id="rtm-origem" name="origem" placeholder="Ex.: Campinas/SP" autocomplete="address-level2"></label>';
	$h .= '<div class="rtm-grupo rtm-cheio" role="group" aria-labelledby="rtm-tipos-rot"><span id="rtm-tipos-rot">Tipo de viagem</span><div class="rtm-pilulas" id="rtm-tipos">'
		. rtm_cot_pilulas( array_combine( $tipos, $tipos ), 'data-tipo' ) . '</div></div>';
	$h .= '</div></section>';

	// Etapa 2: especialistas.
	$h .= '<section class="rtm-etapa" data-etapa="2">' . rtm_cot_etapa_titulo( 2, 'Escolha seus especialistas', 'Marque de 1 a 3. Cada um envia uma cotação.' );
	$h .= '<div class="rtm-lista" id="rtm-lista">';
	foreach ( $agentes as $a ) {
		$h .= rtm_card( $a );
	}
	$h .= '</div>';
	$h .= '<div class="rtm-vazio" id="rtm-vazio" hidden>Nenhum especialista com esse perfil ainda. Marque &quot;a Rede escolhe&quot; e indicamos o melhor para você.</div>';
	$h .= '<label class="rtm-rede" for="rtm-rede"><input type="checkbox" id="rtm-rede" name="rede"><span>Prefiro que a <b>Rede Turística</b> escolha os especialistas para mim</span></label>';
	$h .= '</section>';

	// Etapa 3: detalhes.
	$h .= '<section class="rtm-etapa" data-etapa="3">' . rtm_cot_etapa_titulo( 3, 'Detalhes da viagem', 'Quanto mais detalhes, mais certeira a cotação.' );
	$h .= '<div class="rtm-campos">';
	$h .= '<div class="rtm-grupo rtm-cheio" role="group" aria-labelledby="rtm-datas-rot"><span id="rtm-datas-rot">Datas</span><div class="rtm-pilulas" id="rtm-datas">'
		. rtm_cot_pilulas( array( 'certas' => 'Já tenho as datas', 'mes' => 'Só sei o mês', 'aberto' => 'Ainda não sei' ), 'data-v', array( 'certas' ) ) . '</div></div>';
	$h .= '<label class="rtm-rot" for="rtm-ida">Ida<input type="date" id="rtm-ida" name="ida"></label>';
	$h .= '<label class="rtm-rot" for="rtm-volta">Volta<input type="date" id="rtm-volta" name="volta"></label>';
	$h .= '<label class="rtm-rot rtm-cheio" for="rtm-mes" hidden>Mês da viagem<input type="month" id="rtm-mes" name="mes"></label>';
	$h .= '<div class="rtm-grupo rtm-cheio" role="group" aria-labelledby="rtm-quem-rot"><span id="rtm-quem-rot">Quem vai</span><div class="rtm-cont">'
		. rtm_cot_select( 'rtm-adultos', array( '1', '2', '3', '4', '5+' ), '2', 'Adultos' )
		. rtm_cot_select( 'rtm-criancas', array( '0', '1', '2', '3+' ), '0', 'Crianças (2–12)' )
		. rtm_cot_select( 'rtm-bebes', array( '0', '1', '2+' ), '0', 'Bebês (0–2)' ) . '</div></div>';
	$h .= '<div class="rtm-grupo rtm-cheio" role="group" aria-labelledby="rtm-inclui-rot"><span id="rtm-inclui-rot">O que precisa na cotação</span><div class="rtm-pilulas" id="rtm-inclui">'
		. rtm_cot_pilulas( array_combine( $inclui, $inclui ), 'data-item', array( 'Aéreo', 'Hospedagem' ) ) . '</div></div>';
	$h .= rtm_cot_select( 'rtm-orcamento', rtm_orcamentos(), 'Ainda não defini', 'Orçamento por pessoa', true );
	$h .= '<label class="rtm-rot rtm-cheio" for="rtm-obs">Algo mais que o especialista precisa saber? <span class="rtm-dica">Hotel desejado, aniversário, restrições, ritmo da viagem…</span><textarea id="rtm-obs" name="obs"></textarea></label>';
	$h .= '</div></section>';

	// Etapa 4: contato.
	$h .= '<section class="rtm-etapa" data-etapa="4">' . rtm_cot_etapa_titulo( 4, 'Seu contato', 'As cotações chegam por aqui.' );
	$h .= '<div class="rtm-campos">';
	$h .= '<label class="rtm-rot rtm-cheio" for="rtm-nome">Nome completo<input type="text" id="rtm-nome" name="nome" autocomplete="name"></label>';
	$h .= '<label class="rtm-rot" for="rtm-whats">WhatsApp<input type="tel" id="rtm-whats" name="whats" autocomplete="tel" placeholder="(11) 99999-9999"></label>';
	$h .= '<label class="rtm-rot" for="rtm-email">E-mail<input type="email" id="rtm-email" name="email" autocomplete="email"></label>';
	$h .= rtm_cot_select( 'rtm-horario', array( 'Qualquer horário', 'Manhã', 'Tarde', 'Noite' ), 'Qualquer horário', 'Melhor horário para contato', true );
	$link = '' !== $politica ? '<a href="' . $politica . '" target="_blank" rel="noopener">Política de Privacidade</a>' : 'Política de Privacidade';
	$h .= '<label class="rtm-lgpd rtm-cheio" for="rtm-lgpd"><input type="checkbox" id="rtm-lgpd" name="lgpd"><span>Autorizo a Rede Turística a enviar meus dados aos especialistas escolhidos para que preparem as cotações, conforme a ' . $link . '.</span></label>';
	$h .= '</div>';
	$h .= '<div class="rtm-aviso-ensaio" id="rtm-aviso-ensaio" role="status" hidden>Modo ensaio: seu pedido ainda não foi enviado a nenhum especialista.</div>';
	$h .= '</section>';
	$h .= '</form>';

	$h .= '<div class="rtm-cbarra"><div class="rtm-cbarra-in"><div class="rtm-contagem" id="rtm-contagem" aria-live="polite">Nenhum especialista escolhido <em>· até 3</em></div>'
		. '<button type="button" class="rtm-enviar" id="rtm-enviar" disabled>Pedir cotações</button></div></div>';
	return $h . '</div>';
}
add_shortcode( 'rt_cotacao', 'rtm_cotacao_html' );
