<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * Limpeza e salvamento
 * ------------------------------------------------------------------------ */

/** Destinos publicados (post type `destinos`, somente leitura): [ ['id'=>, 'slug'=>, 'nome'=>], ... ]. */
function rtm_destinos_publicados() {
	$posts = get_posts( array(
		'post_type'      => 'destinos',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	$lista = array();
	foreach ( $posts as $p ) {
		$lista[] = array( 'id' => (int) $p->ID, 'slug' => (string) $p->post_name, 'nome' => (string) $p->post_title );
	}
	return $lista;
}

/** Normaliza o que veio do formulário (já sem barras) para o que será gravado. Devolve todas as chaves de rtm_campos(). */
function rtm_limpar_campos( array $entrada ) {
	$saida = array();
	foreach ( rtm_campos() as $chave => $def ) {
		$bruto = isset( $entrada[ $chave ] ) ? $entrada[ $chave ] : null;
		$texto = is_scalar( $bruto ) ? trim( (string) $bruto ) : '';

		switch ( $def['tipo'] ) {
			case 'telefone':
				$d = preg_replace( '/\D+/', '', $texto );
				$saida[ $chave ] = in_array( strlen( $d ), array( 10, 11 ), true ) ? $d : '';
				break;

			case 'email':
				$e = sanitize_email( $texto );
				$saida[ $chave ] = ( '' !== $e && is_email( $e ) ) ? $e : '';
				break;

			case 'numero':
				$saida[ $chave ] = ( '' !== $texto && ctype_digit( $texto ) ) ? (int) $texto : '';
				break;

			case 'checkbox':
				$saida[ $chave ] = '1' === $texto ? '1' : '0';
				break;

			case 'data':
				$ok = false;
				if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $m ) ) {
					$ok = checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
				}
				$saida[ $chave ] = $ok ? $texto : '';
				break;

			case 'imagem':
				$n = ( '' !== $texto && ctype_digit( $texto ) ) ? (int) $texto : 0;
				$saida[ $chave ] = $n > 0 ? $n : '';
				break;

			case 'lista':
				$itens = is_array( $bruto ) ? $bruto : explode( ',', $texto );
				$lista = array();
				foreach ( $itens as $item ) {
					$item = is_scalar( $item ) ? sanitize_text_field( (string) $item ) : '';
					if ( '' !== $item ) {
						$lista[] = $item;
					}
				}
				$saida[ $chave ] = $lista;
				break;

			case 'especialidades':
				$saida[ $chave ] = rtm_especialidades_normalizar( $bruto, $def['max'] ? (int) $def['max'] : 4 );
				break;

			case 'destinos':
				$lista  = array();
				$vistos = array();
				foreach ( is_array( $bruto ) ? $bruto : array() as $id ) {
					$id = is_scalar( $id ) ? absint( $id ) : 0;
					if ( ! $id || isset( $vistos[ $id ] ) ) {
						continue;
					}
					$p = get_post( $id );
					if ( $p && 'destinos' === $p->post_type && 'publish' === $p->post_status ) {
						$vistos[ $id ] = true;
						$lista[]       = array( 'id' => $id, 'slug' => (string) $p->post_name );
					}
				}
				$saida[ $chave ] = $lista;
				break;

			case 'rede':
				$saida[ $chave ] = rtm_rede_url( $chave, $texto );
				break;

			case 'place_id':
				$saida[ $chave ] = preg_match( '/^[A-Za-z0-9_\-]{10,300}$/', $texto ) ? $texto : '';
				break;

			default: // texto, textarea
				if ( 'apresentacao' === $chave ) {
					$saida[ $chave ] = wp_kses_post( $texto );
				} else {
					$t = sanitize_text_field( $texto );
					if ( $def['max'] ) {
						$t = mb_substr( $t, 0, (int) $def['max'] );
					}
					$saida[ $chave ] = $t;
				}
		}
	}

	// O destino principal precisa estar entre os destinos escolhidos; senão, o primeiro.
	$slugs = array();
	foreach ( $saida['destinos'] as $d ) {
		$slugs[] = $d['slug'];
	}
	$saida['destino_principal'] = in_array( $saida['destino_principal'], $slugs, true ) ? $saida['destino_principal'] : ( $slugs ? $slugs[0] : '' );

	return $saida;
}

/** Grava os campos do agente ao salvar (save_post_rt_agente, prioridade 10). */
function rtm_salvar( $post_id ) {
	$post_id = (int) $post_id;
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$nonce = isset( $_POST['rtm_agente'] ) ? (string) wp_unslash( $_POST['rtm_agente'] ) : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'rtm_agente' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$entrada = ( isset( $_POST['rtm'] ) && is_array( $_POST['rtm'] ) ) ? wp_unslash( $_POST['rtm'] ) : array();
	$campos  = rtm_limpar_campos( $entrada );
	// Sem destinos publicados a caixa não mostra os selects (nem o marcador): não apaga o que já estava gravado.
	if ( empty( $entrada['destinos_no_form'] ) ) {
		unset( $campos['destinos'], $campos['destino_principal'] );
	}
	foreach ( $campos as $chave => $valor ) {
		update_post_meta( $post_id, 'rtm_' . $chave, $valor );
	}
}

/* ---------------------------------------------------------------------------
 * Caixa "Dados do agente"
 * ------------------------------------------------------------------------ */

function rtm_adicionar_caixa() {
	add_meta_box( 'rtm_dados', 'Dados do agente', 'rtm_caixa', 'rt_agente', 'normal', 'high' );
}

/** Uma linha do formulário; $html já vem montado e escapado. */
function rtm_linha( $rotulo, $id, $html, $ajuda = '' ) {
	echo '<p class="rtm-linha"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $rotulo ) . '</strong></label><br>' . $html;
	if ( '' !== $ajuda ) {
		echo '<br><span class="description">' . esc_html( $ajuda ) . '</span>';
	}
	echo '</p>';
}

/** Campo simples (texto, textarea, número, e-mail, data, telefone, lista, checkbox), com escape. */
function rtm_campo( $chave, $valor ) {
	$campos = rtm_campos();
	$def    = $campos[ $chave ];
	$id     = 'rtm_' . $chave;
	$nome   = 'rtm[' . $chave . ']';
	$rot    = $def['rotulo'];
	switch ( $def['tipo'] ) {
		case 'textarea':
			$max = $def['max'] ? ' maxlength="' . (int) $def['max'] . '"' : '';
			$h   = '<textarea class="large-text" rows="' . ( 'apresentacao' === $chave ? 6 : 3 ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nome ) . '"' . $max . '>' . esc_textarea( (string) $valor ) . '</textarea>';
			rtm_linha( $rot, $id, $h, $def['max'] ? 'No máximo ' . (int) $def['max'] . ' caracteres.' : '' );
			break;
		case 'checkbox':
			echo '<p class="rtm-linha"><label for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nome ) . '" value="1"' . checked( '1', (string) $valor, false ) . '> <strong>' . esc_html( $rot ) . '</strong></label><br><span class="description">Só agentes ativos e publicados aparecem no site.</span></p>';
			break;
		case 'lista':
			$v = is_array( $valor ) ? implode( ', ', $valor ) : (string) $valor;
			rtm_linha( $rot, $id, '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nome ) . '" value="' . esc_attr( $v ) . '">', 'Separe por vírgula. Ex.: Português, Inglês' );
			break;
		default:
			$tipos = array( 'numero' => 'number', 'email' => 'email', 'data' => 'date', 'telefone' => 'tel' );
			$t     = isset( $tipos[ $def['tipo'] ] ) ? $tipos[ $def['tipo'] ] : 'text';
			$min   = 'number' === $t ? ' min="0" step="1"' : '';
			$aj    = 'telefone' === $def['tipo'] ? 'Com DDD, só números. Ex.: 54999990000' : '';
			rtm_linha( $rot, $id, '<input type="' . esc_attr( $t ) . '" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nome ) . '" value="' . esc_attr( (string) $valor ) . '"' . $min . '>', $aj );
	}
}

function rtm_caixa( $post ) {
	wp_nonce_field( 'rtm_agente', 'rtm_agente' );
	$campos = rtm_campos();
	$v      = array();
	foreach ( $campos as $chave => $def ) {
		$v[ $chave ] = get_post_meta( $post->ID, 'rtm_' . $chave, true );
	}

	echo '<div class="rtm-caixa">';

	// Agente
	echo '<h3>Agente</h3><p class="description">A foto do agente é a imagem destacada (caixa à direita).</p>';
	foreach ( array( 'cargo', 'texto_card', 'apresentacao', 'cadastur', 'cadastur_validade' ) as $c ) {
		rtm_campo( $c, $v[ $c ] );
	}
	$cert_id  = is_numeric( $v['cadastur_certificado'] ) ? (int) $v['cadastur_certificado'] : 0;
	$cert_url = $cert_id ? wp_get_attachment_image_url( $cert_id, 'medium' ) : '';
	echo '<div class="rtm-linha rtm-logo" data-titulo="Certificado Cadastur" data-botao="Usar esta imagem"><strong>' . esc_html( $campos['cadastur_certificado']['rotulo'] ) . '</strong><br>'
		. '<input type="hidden" id="rtm_cadastur_certificado" name="rtm[cadastur_certificado]" value="' . esc_attr( $cert_id ? (string) $cert_id : '' ) . '">'
		. '<span class="rtm-logo-previa">' . ( $cert_url ? '<img src="' . esc_url( $cert_url ) . '" alt="" style="max-width:120px;max-height:80px;">' : '' ) . '</span><br>'
		. '<button type="button" class="button rtm-logo-escolher">Escolher imagem</button> '
		. '<button type="button" class="button-link rtm-logo-remover"' . ( $cert_id ? '' : ' hidden' ) . '>Remover</button>'
		. '<br><span class="description">Print ou PDF convertido em imagem do certificado emitido no site do Cadastur. Aparece ao clicar no selo "Cadastur".</span></div>';
	foreach ( array( 'anos_experiencia', 'viajantes_atendidos', 'idiomas', 'foto_url_teste' ) as $c ) {
		rtm_campo( $c, $v[ $c ] );
	}

	// Agência
	echo '<h3>Agência</h3>';
	rtm_campo( 'agencia_nome', $v['agencia_nome'] );
	$logo_id  = is_numeric( $v['agencia_logo'] ) ? (int) $v['agencia_logo'] : 0;
	$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
	echo '<div class="rtm-linha rtm-logo" data-titulo="Logo da agência" data-botao="Usar este logo"><strong>' . esc_html( $campos['agencia_logo']['rotulo'] ) . '</strong><br>'
		. '<input type="hidden" id="rtm_agencia_logo" name="rtm[agencia_logo]" value="' . esc_attr( $logo_id ? (string) $logo_id : '' ) . '">'
		. '<span class="rtm-logo-previa">' . ( $logo_url ? '<img src="' . esc_url( $logo_url ) . '" alt="" style="max-width:120px;max-height:80px;">' : '' ) . '</span><br>'
		. '<button type="button" class="button rtm-logo-escolher">Escolher logo</button> '
		. '<button type="button" class="button-link rtm-logo-remover"' . ( $logo_id ? '' : ' hidden' ) . '>Remover</button></div>';
	foreach ( array( 'agencia_cidade', 'agencia_desde', 'endereco', 'horario', 'google_perfil' ) as $c ) {
		rtm_campo( $c, $v[ $c ] );
	}
	rtm_linha( 'Place ID do Google', 'rtm_google_place_id', '<input type="text" class="regular-text" id="rtm_google_place_id" name="rtm[google_place_id]" value="' . esc_attr( (string) $v['google_place_id'] ) . '" autocomplete="off" spellcheck="false">', 'Código do Perfil do Google da agência (começa com ChIJ…). Encontre em developers.google.com/maps/documentation/places/web-service/place-id. Com a chave nas configurações, mostra nota, avaliações e mapa.' );

	// Redes sociais
	echo '<h3>Redes sociais</h3><p class="description">Cole o link do perfil (ou @usuario no Instagram e no TikTok). Em branco, o ícone não aparece.</p>';
	foreach ( array_keys( rtm_redes() ) as $c ) {
		rtm_campo( $c, $v[ $c ] );
	}

	// Destinos e especialidades
	echo '<h3>Destinos e especialidades</h3>';
	// Marcado pelo id ou pelo slug: o Cliente Ideal pode recriar o destino (id novo, mesmo slug).
	$sel_ids   = array();
	$sel_slugs = array();
	foreach ( is_array( $v['destinos'] ) ? $v['destinos'] : array() as $d ) {
		if ( is_array( $d ) && isset( $d['id'] ) ) {
			$sel_ids[] = (int) $d['id'];
		}
		if ( is_array( $d ) && ! empty( $d['slug'] ) ) {
			$sel_slugs[] = (string) $d['slug'];
		}
	}
	$destinos = rtm_destinos_publicados();
	if ( ! $destinos ) {
		echo '<p class="description">Nenhum destino publicado no site (tipo de conteúdo "destinos").</p>';
	} else {
		$h = '<input type="hidden" name="rtm[destinos_no_form]" value="1"><select multiple size="8" id="rtm_destinos" name="rtm[destinos][]" style="min-width:280px">';
		foreach ( $destinos as $d ) {
			$marcado = in_array( $d['id'], $sel_ids, true ) || in_array( $d['slug'], $sel_slugs, true );
			$h      .= '<option value="' . (int) $d['id'] . '"' . selected( $marcado, true, false ) . '>' . esc_html( $d['nome'] ) . '</option>';
		}
		$h .= '</select>';
		rtm_linha( $campos['destinos']['rotulo'], 'rtm_destinos', $h, 'Segure Ctrl (ou Cmd) para marcar vários.' );

		$h = '<select id="rtm_destino_principal" name="rtm[destino_principal]"><option value="">Automático (o primeiro escolhido)</option>';
		foreach ( $destinos as $d ) {
			$h .= '<option value="' . esc_attr( $d['slug'] ) . '"' . selected( (string) $v['destino_principal'], $d['slug'], false ) . '>' . esc_html( $d['nome'] ) . '</option>';
		}
		$h .= '</select>';
		rtm_linha( $campos['destino_principal']['rotulo'], 'rtm_destino_principal', $h, 'Precisa estar entre os destinos marcados; senão vale o primeiro.' );
	}
	$esp = rtm_especialidades_normalizar( $v['especialidades'] );
	if ( ! $esp ) {
		$esp = array( array( 'titulo' => '', 'descricao' => '' ) );
	}
	echo '<div class="rtm-linha rtm-especialidades" data-max="4"><strong>' . esc_html( $campos['especialidades']['rotulo'] ) . '</strong> <span class="description">(até 4)</span><div class="rtm-esp-lista">';
	foreach ( $esp as $i => $item ) {
		echo '<p class="rtm-esp-item"><input type="text" class="regular-text" name="rtm[especialidades][' . (int) $i . '][titulo]" value="' . esc_attr( $item['titulo'] ) . '" placeholder="Título (ex.: Natal Luz)" aria-label="Título"> '
			. '<input type="text" class="large-text rtm-esp-desc" maxlength="120" name="rtm[especialidades][' . (int) $i . '][descricao]" value="' . esc_attr( $item['descricao'] ) . '" placeholder="Descrição curta (até 120 caracteres)" aria-label="Descrição curta"> '
			. '<button type="button" class="button-link rtm-esp-remover">Remover</button></p>';
	}
	echo '</div><button type="button" class="button rtm-esp-adicionar">Adicionar especialidade</button></div>';
	echo '<p class="description">Os tipos de viagem ficam na caixa "Tipos de viagem", ao lado.</p>';

	// Privado
	echo '<h3>Privado — não aparece no site</h3><div class="rtm-privado">';
	foreach ( array( 'whatsapp', 'email', 'company_id', 'cadastur_conferido_em', 'ativo' ) as $c ) {
		rtm_campo( $c, $v[ $c ] );
	}
	echo '</div></div>';
}

function rtm_scripts_admin( $tela ) {
	$agente = in_array( $tela, array( 'post.php', 'post-new.php' ), true ) && in_array( get_post_type(), array( 'rt_agente', 'rt_sugestao' ), true );
	if ( ! $agente && 'settings_page_rede-marketplace' !== $tela ) {
		return;
	}
	wp_enqueue_style( 'rtm-admin', RTM_URL . 'assets/admin.css', array(), RTM_VERSION );
	if ( $agente ) {
		wp_enqueue_media();
		wp_enqueue_script( 'rtm-admin', RTM_URL . 'assets/admin.js', array( 'jquery' ), RTM_VERSION, true );
	}
}

/* ---------------------------------------------------------------------------
 * Configurações › Rede Marketplace
 * ------------------------------------------------------------------------ */

function rtm_limpar_opcoes( $entrada ) {
	$entrada = is_array( $entrada ) ? $entrada : array();
	$url     = isset( $entrada['url_cotacao'] ) ? esc_url_raw( trim( (string) $entrada['url_cotacao'] ) ) : '';
	$chave = isset( $entrada['google_chave'] ) && is_scalar( $entrada['google_chave'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $entrada['google_chave'] ) : '';
	$api = isset( $entrada['ci_api'] ) && is_scalar( $entrada['ci_api'] ) ? esc_url_raw( trim( (string) $entrada['ci_api'] ) ) : '';
	$cot = isset( $entrada['ci_cotacao'] ) && is_scalar( $entrada['ci_cotacao'] ) ? esc_url_raw( trim( (string) $entrada['ci_cotacao'] ) ) : '';
	$cad = isset( $entrada['url_cadastro'] ) && is_scalar( $entrada['url_cadastro'] ) ? esc_url_raw( trim( (string) $entrada['url_cadastro'] ) ) : '';
	$cad = preg_match( '#^https://#i', $cad ) ? $cad : '';
	// Chave do envio: campo em branco mantém a guardada; "apagar" marcado limpa.
	$antes = rtm_opcoes();
	$ch    = isset( $entrada['ci_chave'] ) && is_scalar( $entrada['ci_chave'] ) ? preg_replace( '/[^A-Za-z0-9_\-.]/', '', (string) $entrada['ci_chave'] ) : '';
	if ( '' === $ch ) {
		$ch = empty( $entrada['ci_chave_apagar'] ) ? (string) $antes['ci_chave'] : '';
	}
	return array(
		'url_cotacao'  => '' !== $url ? $url : home_url( '/cotacao-especialistas/' ),
		'ensaio'       => ! empty( $entrada['ensaio'] ),
		'google_chave' => $chave,
		'ci_ligado'    => ! empty( $entrada['ci_ligado'] ),
		'ci_api'       => preg_match( '#^https://#i', $api ) ? $api : '',
		'ci_cotacao'   => preg_match( '#^https://#i', $cot ) ? $cot : '',
		'ci_chave'     => $ch,
		'ci_segredo'   => (string) $antes['ci_segredo'],
		'url_cadastro' => $cad,
	);
}

function rtm_registrar_opcoes() {
	register_setting( 'rtm_opcoes_grupo', 'rtm_opcoes', array(
		'type'              => 'array',
		'sanitize_callback' => 'rtm_limpar_opcoes',
		'default'           => array(),
	) );
}

function rtm_menu_configuracoes() {
	add_options_page( 'Rede Marketplace', 'Rede Marketplace', 'manage_options', 'rede-marketplace', 'rtm_tela_configuracoes' );
}

function rtm_tela_configuracoes() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = rtm_opcoes();
	echo '<div class="wrap"><h1>Rede Marketplace</h1>';
	echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
	settings_fields( 'rtm_opcoes_grupo' );
	echo '<table class="form-table" role="presentation">'
		. '<tr><th scope="row"><label for="rtm_url_cotacao">Página de cotação</label></th><td><input type="url" class="regular-text" id="rtm_url_cotacao" name="rtm_opcoes[url_cotacao]" value="' . esc_attr( $o['url_cotacao'] ) . '"><p class="description">Endereço da página onde o viajante pede a cotação.</p></td></tr>'
		. '<tr><th scope="row"><label for="rtm_google_chave">Chave do Google Maps</label></th><td><input type="text" class="regular-text" id="rtm_google_chave" name="rtm_opcoes[google_chave]" value="' . esc_attr( (string) $o['google_chave'] ) . '" autocomplete="off" spellcheck="false"><p class="description">Chave restrita ao site (redeturistica.com.br) com Maps JavaScript, Places (New) e Maps Embed. Com ela e o Place ID no agente, a página mostra nota, avaliações e mapa do Google.</p></td></tr>'
		. '<tr><th scope="row">Cliente Ideal</th><td><label><input type="checkbox" name="rtm_opcoes[ci_ligado]" value="1"' . checked( ! empty( $o['ci_ligado'] ), true, false ) . '> Agentes e sugestões vêm do módulo Rede Turística do Cliente Ideal</label>'
		. '<p><input type="url" class="regular-text" id="rtm_ci_api" name="rtm_opcoes[ci_api]" value="' . esc_attr( (string) $o['ci_api'] ) . '" placeholder="' . esc_attr( RTM_CI_API_PADRAO ) . '"></p><p class="description">Endereço da API pública. Vazio = Cliente Ideal DEV.</p></td></tr>'
		. '<tr><th scope="row"><label for="rtm_ci_chave">Chave do envio</label></th><td><input type="password" class="regular-text" id="rtm_ci_chave" name="rtm_opcoes[ci_chave]" value="" autocomplete="new-password" spellcheck="false" placeholder="' . esc_attr( '' !== (string) $o['ci_chave'] ? 'Guardada (deixe em branco para manter)' : 'Cole a chave do Cliente Ideal' ) . '">'
		. ( '' !== (string) $o['ci_chave'] ? ' <label><input type="checkbox" name="rtm_opcoes[ci_chave_apagar]" value="1"> apagar</label>' : '' )
		. '<p class="description">Chave secreta que o Cliente Ideal fornece para receber os pedidos de cotação. Fica só no servidor.</p>'
		. '<p><input type="url" class="regular-text" name="rtm_opcoes[ci_cotacao]" value="' . esc_attr( (string) $o['ci_cotacao'] ) . '" placeholder="' . esc_attr( RTM_CI_COTACAO_PADRAO ) . '"></p><p class="description">Endereço do envio. Vazio = Cliente Ideal DEV.</p></td></tr>'
		. '<tr><th scope="row"><label for="rtm_url_cadastro">Cadastro do agente</label></th><td><input type="url" class="regular-text" id="rtm_url_cadastro" name="rtm_opcoes[url_cadastro]" value="' . esc_attr( (string) $o['url_cadastro'] ) . '" placeholder="' . esc_attr( RTM_CADASTRO_PADRAO ) . '"><p class="description">Para onde vai o botão "Quero ser um agente" da página <a href="' . esc_url( rtm_url_seja_agente() ) . '">Seja um agente</a>.</p></td></tr>'
		. '<tr><th scope="row">Modo ensaio</th><td><label><input type="checkbox" name="rtm_opcoes[ensaio]" value="1"' . checked( ! empty( $o['ensaio'] ), true, false ) . '> Ensaio (fora do Google; agentes e envio do pedido só para quem edita o site)</label></td></tr>'
		. '</table>';
	submit_button();
	echo '</form>';
	do_action( 'rtm_configuracoes_extra' ); // botões dos agentes de teste (rtm_botoes_teste)
	echo '</div>';
}

/** Aviso depois de criar/apagar os agentes de teste (vem na URL de volta; só valores conhecidos). */
function rtm_aviso_teste() {
	$q = isset( $_GET['rtm_teste'] ) ? (string) wp_unslash( $_GET['rtm_teste'] ) : '';
	if ( 'criados' === $q ) {
		$h = '<div class="notice notice-success"><p>3 agentes de teste criados.</p></div>';
		if ( ! empty( $_GET['rtm_sem_destino'] ) ) {
			$h .= '<div class="notice notice-warning"><p>O destino "gramado" não está publicado no site: os agentes de teste foram criados sem destinos.</p></div>';
		}
		echo $h; // HTML fixo, sem dados do usuário.
	} elseif ( 'apagados' === $q ) {
		$n = isset( $_GET['rtm_n'] ) ? absint( wp_unslash( $_GET['rtm_n'] ) ) : 0;
		echo '<div class="notice notice-success"><p>' . (int) $n . ' agentes de teste apagados.</p></div>';
	}
}

/** Botões "Criar/Apagar agentes de teste" (admin-post.php, nonce + manage_options na ação). */
function rtm_botoes_teste() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	rtm_aviso_teste();
	$acao = esc_url( admin_url( 'admin-post.php' ) );
	echo '<hr><h2>Agentes de teste</h2><p>Três agentes fictícios (Mariana, Rafael e Camila) para o ensaio. Apagar remove só os agentes de teste.</p>';
	foreach ( array( 'rtm_criar_teste' => 'Criar agentes de teste', 'rtm_apagar_teste' => 'Apagar agentes de teste' ) as $nome => $rotulo ) {
		echo '<form method="post" action="' . $acao . '" style="display:inline-block;margin-right:8px">'
			. '<input type="hidden" name="action" value="' . esc_attr( $nome ) . '">';
		wp_nonce_field( $nome, '_wpnonce', false );
		submit_button( $rotulo, 'rtm_apagar_teste' === $nome ? 'secondary' : 'primary', 'submit', false );
		echo '</form>';
	}
}

/* ---------------------------------------------------------------------------
 * Colunas da lista de agentes
 * ------------------------------------------------------------------------ */

function rtm_colunas( $colunas ) {
	$novas = array();
	if ( isset( $colunas['cb'] ) ) {
		$novas['cb'] = $colunas['cb'];
	}
	$novas['rtm_foto']     = 'Foto';
	$novas['title']        = isset( $colunas['title'] ) ? $colunas['title'] : 'Nome';
	$novas['rtm_agencia']  = 'Agência';
	$novas['rtm_destinos'] = 'Destinos';
	$novas['rtm_ativo']    = 'Ativo';
	$novas['rtm_cadastur'] = 'Cadastur';
	$novas['rtm_teste']    = 'Teste';
	return $novas;
}

function rtm_coluna( $coluna, $post_id ) {
	switch ( $coluna ) {
		case 'rtm_foto':
			$url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
			if ( ! $url ) {
				$url = (string) get_post_meta( $post_id, 'rtm_foto_url_teste', true );
			}
			echo $url ? '<img src="' . esc_url( $url ) . '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%">' : '—';
			break;
		case 'rtm_agencia':
			$n = (string) get_post_meta( $post_id, 'rtm_agencia_nome', true );
			echo '' !== $n ? esc_html( $n ) : '—';
			break;
		case 'rtm_destinos':
			$slugs = array();
			$d     = get_post_meta( $post_id, 'rtm_destinos', true );
			foreach ( is_array( $d ) ? $d : array() as $i ) {
				if ( is_array( $i ) && ! empty( $i['slug'] ) ) {
					$slugs[] = $i['slug'];
				}
			}
			echo $slugs ? esc_html( implode( ', ', $slugs ) ) : '—';
			break;
		case 'rtm_ativo':
			echo '1' === (string) get_post_meta( $post_id, 'rtm_ativo', true ) ? 'Sim' : 'Não';
			break;
		case 'rtm_cadastur':
			$d = (string) get_post_meta( $post_id, 'rtm_cadastur', true );
			echo '' !== $d ? esc_html( $d ) : '—';
			break;
		case 'rtm_teste':
			echo '1' === (string) get_post_meta( $post_id, 'rtm_teste', true ) ? '<span class="rtm-selo-teste">Teste</span>' : '';
			break;
	}
}

add_action( 'save_post_rt_agente', 'rtm_salvar', 10 );
add_action( 'add_meta_boxes', 'rtm_adicionar_caixa' );
add_action( 'admin_enqueue_scripts', 'rtm_scripts_admin' );
add_action( 'admin_init', 'rtm_registrar_opcoes' );
add_action( 'admin_menu', 'rtm_menu_configuracoes' );
add_filter( 'manage_rt_agente_posts_columns', 'rtm_colunas' );
add_action( 'manage_rt_agente_posts_custom_column', 'rtm_coluna', 10, 2 );
add_action( 'rtm_configuracoes_extra', 'rtm_botoes_teste' );
