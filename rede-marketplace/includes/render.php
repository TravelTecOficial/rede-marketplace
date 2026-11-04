<?php
/** HTML público do agente: seções da página e card da cotação. Só usa os dados de rtm_agente(). */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Link da cotação já com o destino principal e o agente: `?destino=<slug>&agente=<slug>`. */
function rtm_url_cotacao( array $agente ) {
	$base = (string) rtm_opcoes()['url_cotacao'];
	$q    = array();
	if ( ! empty( $agente['destino_principal'] ) ) {
		$q[] = 'destino=' . rawurlencode( (string) $agente['destino_principal'] );
	}
	$q[] = 'agente=' . rawurlencode( (string) $agente['slug'] );
	return $base . ( false === strpos( $base, '?' ) ? '?' : '&' ) . implode( '&', $q );
}

function rtm_primeiro_nome( array $agente ) {
	$p = preg_split( '/\s+/u', trim( (string) $agente['nome'] ), -1, PREG_SPLIT_NO_EMPTY );
	return $p ? $p[0] : '';
}

/** Foto do agente (img) ou bloco com as iniciais. */
function rtm_html_foto( array $agente, $classe ) {
	$url = esc_url( (string) $agente['foto'] );
	if ( '' !== $url ) {
		$img = '<img src="' . $url . '" alt="' . esc_attr( 'Foto de ' . $agente['nome'] ) . '" loading="lazy" decoding="async">';
		return '<div class="' . esc_attr( $classe ) . '">' . $img . '</div>';
	}
	return '<div class="' . esc_attr( $classe ) . ' rtm-sem-foto"><span class="rtm-iniciais" aria-hidden="true">' . esc_html( $agente['iniciais'] ) . '</span></div>';
}

/** Bloco da agência (logo ou iniciais + nome + cidade/desde). '' sem nome da agência. */
function rtm_html_agencia( array $agente, $com_desde ) {
	if ( '' === trim( (string) $agente['agencia_nome'] ) ) {
		return '';
	}
	$url  = esc_url( (string) $agente['logo'] );
	$logo = '' !== $url
		? '<img class="rtm-logo" src="' . $url . '" alt="" loading="lazy" decoding="async">'
		: '<span class="rtm-logo" aria-hidden="true">' . esc_html( $agente['agencia_iniciais'] ) . '</span>';
	$info = array();
	if ( '' !== trim( (string) $agente['agencia_cidade'] ) ) {
		$info[] = esc_html( $agente['agencia_cidade'] );
	}
	if ( $com_desde && (int) $agente['agencia_desde'] > 0 ) {
		$info[] = 'desde ' . (int) $agente['agencia_desde'];
	}
	return '<div class="rtm-agencia">' . $logo . '<span><strong>' . esc_html( $agente['agencia_nome'] ) . '</strong>' . implode( ' · ', $info ) . '</span></div>';
}

/** Selo "Cadastur verificado" — só quando a data de conferência existe. */
function rtm_html_cadastur( array $agente, $com_numero ) {
	if ( empty( $agente['cadastur_verificado'] ) ) {
		return '';
	}
	$txt = 'Cadastur verificado';
	if ( $com_numero && '' !== trim( (string) $agente['cadastur'] ) ) {
		$txt .= ' · ' . $agente['cadastur'];
	}
	return '<span class="rtm-cadastur"><i aria-hidden="true">✓</i>' . esc_html( $txt ) . '</span>';
}

/** Estrelas cheias/vazias (arredondadas) para uma nota de 0 a 5. */
function rtm_estrelas( $nota ) {
	$cheias = max( 0, min( 5, (int) round( (float) $nota ) ) );
	return str_repeat( '★', $cheias ) . str_repeat( '☆', 5 - $cheias );
}

function rtm_nota_txt( $nota ) {
	return number_format( (float) $nota, 1, ',', '' );
}

/** Linha "★★★★★ 4,9 · 87 avaliações no Google" (topo e card); '' sem avaliações. */
function rtm_html_nota( array $agente ) {
	$av = isset( $agente['avaliacoes'] ) && is_array( $agente['avaliacoes'] ) ? $agente['avaliacoes'] : array();
	if ( empty( $av['nota'] ) || empty( $av['total'] ) ) {
		return '';
	}
	$total = (int) $av['total'];
	return '<div class="rtm-nota"><span class="rtm-estrelas" aria-hidden="true">' . rtm_estrelas( $av['nota'] ) . '</span>'
		. '<b>' . rtm_nota_txt( $av['nota'] ) . '</b> · ' . number_format( $total, 0, ',', '.' ) . ( 1 === $total ? ' avaliação' : ' avaliações' ) . ' no Google</div>';
}

/** Redes sociais na ordem em que aparecem na página. */
function rtm_redes() {
	return array( 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'site' => 'Site' );
}

/** Ícone (SVG simples, na cor do texto) de cada rede. */
function rtm_icone_rede( $rede ) {
	$p = array(
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.3" cy="6.7" r="1.3"/>',
		'facebook'  => '<path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H8v3h2.5V21z"/>',
		'tiktok'    => '<path d="M16.6 3c.4 2.2 1.7 3.6 3.9 3.8v3a7 7 0 0 1-3.9-1.2v6.1a5.7 5.7 0 1 1-5.7-5.7l.9.1v3.1a2.7 2.7 0 1 0 1.8 2.5V3z"/>',
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z" fill="#fff"/>',
		'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7 10h2.2v7H7zM8.1 6.6a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6zM10.7 10h2.1v1c.4-.7 1.3-1.2 2.4-1.2 2.2 0 2.6 1.4 2.6 3.3V17h-2.2v-3.4c0-.8 0-1.9-1.2-1.9s-1.4.9-1.4 1.8V17h-2.3z" fill="#fff"/>',
		'site'      => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 12h18M12 3c2.5 2.5 3.6 5.5 3.6 9s-1.1 6.5-3.6 9c-2.5-2.5-3.6-5.5-3.6-9S9.5 5.5 12 3z" fill="none" stroke="currentColor" stroke-width="1.6"/>',
	);
	return '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">' . $p[ $rede ] . '</svg>';
}

/** Links das redes preenchidas; '' quando nenhuma. */
function rtm_html_redes( array $agente ) {
	$h = '';
	foreach ( rtm_redes() as $rede => $nome ) {
		$url = isset( $agente[ $rede ] ) ? esc_url( (string) $agente[ $rede ] ) : '';
		if ( '' === $url ) {
			continue;
		}
		$rotulo = $nome . ' de ' . rtm_primeiro_nome( $agente ) . ' (abre em nova aba)';
		$h     .= '<a class="rtm-rede rtm-rede-' . $rede . '" href="' . $url . '" target="_blank" rel="noopener nofollow" aria-label="' . esc_attr( $rotulo ) . '" title="' . esc_attr( $nome ) . '">' . rtm_icone_rede( $rede ) . '</a>';
	}
	return '' === $h ? '' : '<div class="rtm-redes">' . $h . '</div>';
}

/**
 * Selo "Cadastur verificado" da página do agente: botão que abre uma janela com os dados do cadastro, o
 * certificado (imagem enviada pela agência, se houver) e o link para conferir no site oficial.
 */
function rtm_html_cadastur_janela( array $agente ) {
	if ( empty( $agente['cadastur_verificado'] ) ) {
		return '';
	}
	$numero = trim( (string) $agente['cadastur'] );
	$h      = '<button type="button" class="rtm-cadastur rtm-cad-abrir" aria-haspopup="dialog" aria-controls="rtm-cad"><i aria-hidden="true">✓</i>'
		. esc_html( 'Cadastur verificado' . ( '' !== $numero ? ' · ' . $numero : '' ) ) . '<span class="rtm-cad-ver">ver certificado</span></button>';
	$h     .= '<dialog id="rtm-cad" class="rtm-cad-dialogo" aria-labelledby="rtm-cad-titulo"><div class="rtm-cad-caixa">'
		. '<button type="button" class="rtm-cad-fechar" aria-label="Fechar">×</button>'
		. '<h2 id="rtm-cad-titulo">Cadastur verificado</h2>'
		. '<p class="rtm-cad-intro">Cadastro no Ministério do Turismo conferido pela Rede Turística.</p>';
	$cert = esc_url( (string) $agente['certificado'] );
	if ( '' !== $cert ) {
		$h .= '<img class="rtm-cad-img" src="' . $cert . '" alt="' . esc_attr( 'Certificado Cadastur de ' . (string) $agente['agencia_nome'] ) . '" loading="lazy" decoding="async">';
	}
	$dados = array();
	if ( '' !== trim( (string) $agente['agencia_nome'] ) ) {
		$dados['Prestador'] = $agente['agencia_nome'];
	}
	if ( '' !== $numero ) {
		$dados['Número do cadastro'] = $numero;
	}
	$val = (string) $agente['cadastur_validade'];
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $val, $m ) ) {
		$dados['Válido até'] = $m[3] . '/' . $m[2] . '/' . $m[1];
	}
	if ( $dados ) {
		$h .= '<dl class="rtm-cad-dados">';
		foreach ( $dados as $rot => $v ) {
			$h .= '<div><dt>' . esc_html( $rot ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
		}
		$h .= '</dl>';
	}
	$h .= '<a class="rtm-cad-link" href="https://cadastur.turismo.gov.br/" target="_blank" rel="noopener">Conferir no site oficial do Cadastur</a>';
	return $h . '</div></dialog>';
}

/** Selo oficial "Somos certificados Cadastur" (imagem enviada pelo dono), clicável: abre a mesma janela. */
function rtm_html_cadastur_selo( array $agente ) {
	if ( empty( $agente['cadastur_verificado'] ) ) {
		return '';
	}
	return '<button type="button" class="rtm-cad-selo rtm-cad-abrir" aria-haspopup="dialog" aria-controls="rtm-cad">'
		. '<img src="' . esc_url( RTM_URL . 'assets/cadastur-selo.png' ) . '" width="81" height="36" alt="Somos certificados Cadastur: ver o certificado" decoding="async"></button>';
}

function rtm_html_chips( array $itens ) {
	$h = '';
	foreach ( $itens as $t ) {
		if ( '' !== trim( (string) $t ) ) {
			$h .= '<span class="rtm-chip">' . esc_html( $t ) . '</span>';
		}
	}
	return $h;
}

/**
 * Nome de um destino guardado como {id, slug}. O Cliente Ideal pode recriar o destino (id novo, mesmo slug):
 * vale o id se ainda for um destino publicado; senão, procura pelo slug. '' se não existir mais.
 */
function rtm_titulo_destino( $d ) {
	if ( ! is_array( $d ) ) {
		return '';
	}
	$p = ! empty( $d['id'] ) ? get_post( (int) $d['id'] ) : null;
	if ( ! $p || 'destinos' !== $p->post_type || 'publish' !== $p->post_status ) {
		$p       = null;
		$achados = ! empty( $d['slug'] ) ? get_posts( array(
			'post_type'      => 'destinos',
			'post_status'    => 'publish',
			'name'           => (string) $d['slug'],
			'posts_per_page' => 1,
		) ) : array();
		if ( $achados ) {
			$p = $achados[0];
		}
	}
	return $p ? (string) $p->post_title : '';
}

/** HTML de uma seção da página do agente; '' quando não há dado para mostrar. */
function rtm_secao( $secao, array $agente ) {
	$nome  = rtm_primeiro_nome( $agente );
	$url   = esc_url( rtm_url_cotacao( $agente ) );
	$botao = '<a class="rtm-btn" href="' . $url . '">' . esc_html( 'Pedir cotação com ' . $nome ) . '</a>';

	switch ( $secao ) {
		case 'trilha':
			// Não existe página de arquivo dos especialistas: "Especialistas" fica sem link.
			return '<nav class="rtm-trilha" aria-label="Trilha de navegação">'
				. '<a class="rtm-trilha-link" href="' . esc_url( home_url( '/' ) ) . '">Rede Turística</a>'
				. ' <span class="rtm-trilha-sep" aria-hidden="true">›</span> <span>Especialistas</span>'
				. ' <span class="rtm-trilha-sep" aria-hidden="true">›</span> <span aria-current="page">' . esc_html( $agente['nome'] ) . '</span>'
				. '</nav>';

		case 'topo':
			// Coluna da esquerda: foto e, abaixo dela, a agência. Direita: dados do agente, redes e selo Cadastur.
			$lado = rtm_html_agencia( $agente, true );
			$h  = '<section class="rtm-topo"><div class="rtm-lado">' . rtm_html_foto( $agente, 'rtm-foto' );
			$h .= '' !== $lado ? '<div class="rtm-lado-info">' . $lado . '</div>' : '';
			$h .= '</div><div class="rtm-dados">';
			// Selo verde e, ao lado, o selo oficial pequeno: os dois abrem a janela do certificado.
			$cad = rtm_html_cadastur_janela( $agente );
			if ( '' !== $cad ) {
				$h .= '<div class="rtm-cad-linha">' . rtm_html_cadastur_selo( $agente ) . $cad . '</div>';
			}
			$h .= '<h1 class="rtm-nome">' . esc_html( $agente['nome'] ) . '</h1>';
			if ( '' !== trim( (string) $agente['cargo'] ) ) {
				$h .= '<p class="rtm-cargo">' . esc_html( $agente['cargo'] ) . '</p>';
			}
			$nota = rtm_html_nota( $agente );
			$h   .= $nota;
			$chips = rtm_html_chips( $agente['tipos'] );
			if ( '' !== $chips ) {
				$h .= '<div class="rtm-chips">' . $chips . '</div>';
			}
			$num  = '';
			$anos = (int) $agente['anos_experiencia'];
			if ( $anos > 0 ) {
				$num .= '<div><b>' . $anos . ( 1 === $anos ? ' ano' : ' anos' ) . '</b><span>de experiência</span></div>';
			}
			$viaj = (int) $agente['viajantes_atendidos'];
			if ( $viaj > 0 ) {
				$num .= '<div><b>+' . number_format( $viaj, 0, ',', '.' ) . '</b><span>viajantes atendidos</span></div>';
			}
			$idiomas = array_values( array_filter( array_map( 'trim', array_map( 'strval', $agente['idiomas'] ) ), 'strlen' ) );
			if ( $idiomas ) {
				$num .= '<div><b>' . esc_html( implode( ' · ', $idiomas ) ) . '</b><span>' . ( count( $idiomas ) > 1 ? 'idiomas' : 'idioma' ) . '</span></div>';
			}
			if ( '' !== $num ) {
				$h .= '<div class="rtm-numeros">' . $num . '</div>';
			}
			$h .= '<div class="rtm-acoes">' . $botao . ( '' !== $nota ? '<a class="rtm-btn-sec" href="#avaliacoes">Ver avaliações</a>' : '' ) . '</div>';
			$h .= rtm_html_redes( $agente );
			return $h . '</div></section>';

		case 'sobre':
			$texto = trim( (string) $agente['apresentacao'] );
			if ( '' === $texto ) {
				return '';
			}
			return '<section class="rtm-bloco rtm-sobre"><h2>' . esc_html( 'Sobre ' . $nome ) . '</h2>' . wp_kses_post( wpautop( $texto ) ) . '</section>';

		case 'especialidades':
			$esp = '';
			foreach ( rtm_especialidades_normalizar( $agente['especialidades'] ) as $item ) {
				$esp .= '<div class="rtm-esp"><b class="rtm-esp-titulo">' . esc_html( $item['titulo'] ) . '</b>'
					. ( '' !== $item['descricao'] ? '<span class="rtm-esp-desc">' . esc_html( $item['descricao'] ) . '</span>' : '' ) . '</div>';
			}
			$nomes = array();
			foreach ( $agente['destinos'] as $d ) {
				$titulo = rtm_titulo_destino( $d );
				if ( '' !== $titulo ) {
					$nomes[] = $titulo;
				}
			}
			$dest = rtm_html_chips( $nomes );
			if ( '' === $esp && '' === $dest ) {
				return '';
			}
			$h = '<section class="rtm-bloco rtm-especialidades"><h2>' . ( '' !== $esp ? 'Especialidades' : 'Destinos' ) . '</h2>';
			if ( '' !== $esp ) {
				$h .= '<div class="rtm-lista-esp">' . $esp . '</div>';
			}
			if ( '' !== $dest ) {
				$h .= '<div class="rtm-destinos">' . $dest . '</div>';
			}
			return $h . '</section>';

		case 'avaliacoes':
			$av = isset( $agente['avaliacoes'] ) && is_array( $agente['avaliacoes'] ) ? $agente['avaliacoes'] : array();
			if ( empty( $av['nota'] ) || empty( $av['itens'] ) ) {
				return '';
			}
			$total = (int) $av['total'];
			$h  = '<section class="rtm-bloco rtm-avaliacoes" id="avaliacoes"><div class="rtm-av-topo"><h2>Avaliações</h2>';
			$h .= '<span class="rtm-google"><i>Google</i> avaliações do perfil da agência</span></div>';
			if ( ! empty( $av['exemplo'] ) ) {
				$h .= '<p class="rtm-av-exemplo">Exemplo para o ensaio: avaliações fictícias.</p>';
			}
			$h .= '<div class="rtm-av-resumo"><b>' . rtm_nota_txt( $av['nota'] ) . '</b><div><span class="rtm-estrelas" aria-hidden="true">' . rtm_estrelas( $av['nota'] ) . '</span>'
				. '<span>' . number_format( $total, 0, ',', '.' ) . ( 1 === $total ? ' avaliação' : ' avaliações' ) . '</span></div></div><div class="rtm-avals">';
			$cores = array( '#7a4fa3', '#2f6b8a', '#a35a2f', '#3f7d4e', '#8a2f55' );
			foreach ( array_slice( (array) $av['itens'], 0, 5 ) as $i => $it ) {
				$autor = isset( $it['autor'] ) ? (string) $it['autor'] : '';
				$texto = isset( $it['texto'] ) ? trim( (string) $it['texto'] ) : '';
				if ( '' === $autor || '' === $texto ) {
					continue;
				}
				$n     = isset( $it['nota'] ) ? (int) $it['nota'] : 5;
				$h    .= '<div class="rtm-av"><div class="rtm-av-cab"><span class="rtm-av-ini" style="background:' . $cores[ $i % count( $cores ) ] . '" aria-hidden="true">' . esc_html( rtm_iniciais( $autor ) ) . '</span>'
					. '<div><b>' . esc_html( $autor ) . '</b><small>' . esc_html( isset( $it['quando'] ) ? (string) $it['quando'] : '' ) . ' · <span aria-label="' . $n . ' de 5 estrelas">' . rtm_estrelas( $n ) . '</span></small></div></div>'
					. '<p>' . esc_html( $texto ) . '</p></div>';
			}
			$h  .= '</div>';
			$url = esc_url( (string) $agente['google_perfil'] );
			if ( '' !== $url ) {
				$h .= '<a class="rtm-av-link" href="' . $url . '" target="_blank" rel="noopener nofollow">Ver todas as avaliações no Google</a>';
			}
			return $h . '</section>';

		case 'onde':
			$itens = '';
			if ( '' !== trim( (string) $agente['endereco'] ) ) {
				$itens .= '<div><b>Endereço</b>' . esc_html( $agente['endereco'] ) . '</div>';
			}
			if ( '' !== trim( (string) $agente['horario'] ) ) {
				$itens .= '<div><b>Horário</b>' . esc_html( $agente['horario'] ) . '</div>';
			}
			if ( '' === $itens ) {
				return '';
			}
			return '<section class="rtm-bloco rtm-onde"><h2>Onde atende</h2><div class="rtm-endereco">' . $itens . '</div></section>';

		case 'sugestoes':
			return rtm_html_sugestoes( $agente, 'lateral' );

		case 'cotacao':
			return '<aside class="rtm-lateral"><div class="rtm-bloco">'
				. '<h3>' . esc_html( 'Peça uma cotação com ' . $nome ) . '</h3>'
				. '<p>Você conta sua viagem e recebe uma proposta pelo WhatsApp. Sem custo.</p>'
				. '<ul><li>Resposta em até 2h úteis</li><li>Pode incluir mais 2 especialistas</li><li>Sem compromisso</li></ul>'
				. '<a class="rtm-btn" href="' . $url . '">Pedir cotação</a>'
				. '</div></aside>';

		case 'barra':
			return '<div class="rtm-barra-espaco" aria-hidden="true"></div><div class="rtm-barra"><div class="rtm-barra-in">' . $botao . '</div></div>';
	}
	return '';
}

/** Card horizontal do agente na cotação. Atributos data-* só públicos: id, slug, destinos, tipos. */
function rtm_card( array $agente ) {
	$h  = '<div class="rtm-card" role="button" tabindex="0" aria-pressed="false"';
	$h .= ' data-id="' . (int) $agente['id'] . '"';
	$h .= ' data-slug="' . esc_attr( $agente['slug'] ) . '"';
	$h .= ' data-destinos="' . esc_attr( implode( ' ', $agente['destinos_slugs'] ) ) . '"';
	$h .= ' data-tipos="' . esc_attr( implode( '|', $agente['tipos'] ) ) . '">';
	$h .= rtm_html_foto( $agente, 'rtm-card-foto' );
	$h .= '<span class="rtm-selo" aria-hidden="true">✓</span><div class="rtm-corpo">';
	$h .= rtm_html_cadastur( $agente, false );
	$h .= '<h3 class="rtm-card-nome">' . esc_html( $agente['nome'] ) . '</h3>';
	$h .= rtm_html_nota( $agente );
	$chips = rtm_html_chips( $agente['tipos'] );
	if ( '' !== $chips ) {
		$h .= '<div class="rtm-chips">' . $chips . '</div>';
	}
	if ( '' !== trim( (string) $agente['texto_card'] ) ) {
		$h .= '<p class="rtm-bio">' . esc_html( $agente['texto_card'] ) . '</p>';
	}
	$h .= '<div class="rtm-rodape">' . rtm_html_agencia( $agente, false );
	$h .= '<a class="rtm-perfil" href="' . esc_url( (string) $agente['url'] ) . '">Ver perfil</a></div>';
	return $h . '</div></div>';
}

/** A página atual tem o shortcode [rt_cotacao (no conteúdo ou nos dados do Elementor)? */
function rtm_pagina_tem_cotacao() {
	return is_singular() && rtm_post_tem_cotacao( get_queried_object_id() );
}

function rtm_enfileirar_estilo() {
	wp_enqueue_style( 'rede-marketplace', RTM_URL . 'assets/rede-marketplace.css', array(), RTM_VERSION );
}

/** CSS só nas páginas do agente e na página da cotação. */
function rtm_estilos() {
	if ( is_singular( 'rt_agente' ) || rtm_pagina_tem_cotacao() ) {
		rtm_enfileirar_estilo();
	}
	if ( is_singular( 'rt_agente' ) ) {
		wp_enqueue_script( 'rtm-agente', RTM_URL . 'assets/agente.js', array(), RTM_VERSION, true );
	}
}
add_action( 'wp_enqueue_scripts', 'rtm_estilos' );
