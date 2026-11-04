<?php
/** Painel das sugestões de viagem: caixa "Dados da sugestão", gravação e colunas da lista. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Limpa o que veio do formulário (rtm_s[...]) para os campos de rtm_campos_sugestao(). */
function rtm_limpar_sugestao( array $entrada ) {
	$saida = array();
	foreach ( rtm_campos_sugestao() as $chave => $def ) {
		$bruto = isset( $entrada[ $chave ] ) ? $entrada[ $chave ] : null;
		$texto = is_scalar( $bruto ) ? trim( (string) $bruto ) : '';
		switch ( $def['tipo'] ) {
			case 'agente':
				$p               = ctype_digit( $texto ) ? get_post( (int) $texto ) : null;
				$saida[ $chave ] = ( $p && 'rt_agente' === $p->post_type ) ? (int) $p->ID : 0;
				break;

			case 'destino':
				$slugs           = wp_list_pluck( rtm_destinos_publicados(), 'slug' );
				$saida[ $chave ] = in_array( $texto, $slugs, true ) ? $texto : '';
				break;

			case 'tipo':
				$saida[ $chave ] = in_array( $texto, rtm_tipos_cotacao(), true ) ? $texto : '';
				break;

			case 'galeria':
				$ids = array();
				foreach ( is_array( $bruto ) ? $bruto : explode( ',', $texto ) as $id ) {
					$id = is_scalar( $id ) ? trim( (string) $id ) : '';
					if ( ctype_digit( $id ) && (int) $id > 0 && ! in_array( (int) $id, $ids, true ) ) {
						$ids[] = (int) $id;
					}
				}
				$saida[ $chave ] = array_slice( $ids, 0, (int) $def['max'] );
				break;

			case 'lista':
				$itens = array();
				foreach ( is_array( $bruto ) ? $bruto : array() as $item ) {
					$item = is_scalar( $item ) ? sanitize_text_field( (string) $item ) : '';
					if ( '' !== $item ) {
						$itens[] = $item;
					}
				}
				$saida[ $chave ] = array_slice( $itens, 0, (int) $def['max'] );
				break;

			case 'dias':
				$dias = array();
				foreach ( is_array( $bruto ) ? $bruto : array() as $d ) {
					$t = is_array( $d ) && isset( $d['titulo'] ) && is_scalar( $d['titulo'] ) ? sanitize_text_field( (string) $d['titulo'] ) : '';
					$x = is_array( $d ) && isset( $d['descricao'] ) && is_scalar( $d['descricao'] ) ? sanitize_text_field( (string) $d['descricao'] ) : '';
					if ( '' !== $t ) {
						$dias[] = array( 'titulo' => $t, 'descricao' => mb_substr( $x, 0, 200 ) );
					}
				}
				$saida[ $chave ] = array_slice( $dias, 0, (int) $def['max'] );
				break;

			case 'preco':
				$d               = preg_replace( '/\D+/', '', $texto );
				$saida[ $chave ] = ( '' !== $d && (int) $d > 0 ) ? (int) $d : '';
				break;

			case 'numero':
				$saida[ $chave ] = preg_match( '/^-?\d+$/', $texto ) ? (int) $texto : 0;
				break;

			default: // texto, textarea
				$t               = sanitize_text_field( $texto );
				$saida[ $chave ] = $def['max'] ? mb_substr( $t, 0, (int) $def['max'] ) : $t;
		}
	}
	return $saida;
}

/** Grava os campos ao salvar (save_post_rt_sugestao). */
function rtm_salvar_sugestao( $post_id ) {
	$post_id = (int) $post_id;
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$nonce = isset( $_POST['rtm_sugestao'] ) ? (string) wp_unslash( $_POST['rtm_sugestao'] ) : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'rtm_sugestao' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$entrada = ( isset( $_POST['rtm_s'] ) && is_array( $_POST['rtm_s'] ) ) ? wp_unslash( $_POST['rtm_s'] ) : array();
	foreach ( rtm_limpar_sugestao( $entrada ) as $chave => $valor ) {
		update_post_meta( $post_id, 'rtm_' . $chave, $valor );
	}
}

function rtm_adicionar_caixa_sugestao() {
	add_meta_box( 'rtm_sugestao_dados', 'Dados da sugestão', 'rtm_caixa_sugestao', 'rt_sugestao', 'normal', 'high' );
}

/** Select simples; $opcoes valor => rótulo. */
function rtm_sug_select( $chave, array $opcoes, $atual, $vazio ) {
	$h = '<select id="rtm_s_' . esc_attr( $chave ) . '" name="rtm_s[' . esc_attr( $chave ) . ']"><option value="">' . esc_html( $vazio ) . '</option>';
	foreach ( $opcoes as $valor => $rotulo ) {
		$h .= '<option value="' . esc_attr( $valor ) . '"' . selected( (string) $valor, (string) $atual, false ) . '>' . esc_html( $rotulo ) . '</option>';
	}
	return $h . '</select>';
}

function rtm_sug_texto( $chave, $valor, $ajuda = '' ) {
	$def = rtm_campos_sugestao()[ $chave ];
	$id  = 'rtm_s_' . $chave;
	$max = $def['max'] ? ' maxlength="' . (int) $def['max'] . '"' : '';
	$h   = 'textarea' === $def['tipo']
		? '<textarea class="large-text" rows="3" id="' . esc_attr( $id ) . '" name="rtm_s[' . esc_attr( $chave ) . ']"' . $max . '>' . esc_textarea( (string) $valor ) . '</textarea>'
		: '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="rtm_s[' . esc_attr( $chave ) . ']" value="' . esc_attr( (string) $valor ) . '"' . $max . '>';
	rtm_linha( $def['rotulo'], $id, $h, $ajuda );
}

/** Repetidor genérico (admin.js: data-rtm-repetidor). $linhas: lista de arrays campo => valor. */
function rtm_sug_repetidor( $chave, $rotulo, array $campos, array $linhas, $max, $botao ) {
	if ( ! $linhas ) {
		$linhas = array( array_fill_keys( array_keys( $campos ), '' ) );
	}
	echo '<div class="rtm-linha rtm-repetidor" data-rtm-repetidor="' . esc_attr( $chave ) . '" data-max="' . (int) $max . '"><strong>' . esc_html( $rotulo ) . '</strong> <span class="description">(até ' . (int) $max . ')</span><div class="rtm-rep-lista">';
	foreach ( array_values( $linhas ) as $i => $linha ) {
		echo '<p class="rtm-rep-item">';
		foreach ( $campos as $campo => $dica ) {
			$nome  = 'rtm_s[' . $chave . '][' . (int) $i . ']' . ( '' === $campo ? '' : '[' . $campo . ']' );
			$valor = is_array( $linha ) ? ( isset( $linha[ $campo ] ) ? $linha[ $campo ] : '' ) : $linha;
			echo '<input type="text" class="regular-text" name="' . esc_attr( $nome ) . '" value="' . esc_attr( (string) $valor ) . '" placeholder="' . esc_attr( $dica ) . '" aria-label="' . esc_attr( $dica ) . '"> ';
		}
		echo '<button type="button" class="button-link rtm-rep-remover">Remover</button></p>';
	}
	echo '</div><button type="button" class="button rtm-rep-adicionar">' . esc_html( $botao ) . '</button></div>';
}

function rtm_caixa_sugestao( $post ) {
	wp_nonce_field( 'rtm_sugestao', 'rtm_sugestao' );
	$v = array();
	foreach ( rtm_campos_sugestao() as $chave => $def ) {
		$v[ $chave ] = get_post_meta( $post->ID, 'rtm_' . $chave, true );
	}
	$agentes = array();
	foreach ( get_posts( array( 'post_type' => 'rt_agente', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		$agentes[ (int) $p->ID ] = $p->post_title;
	}
	$destinos = array();
	foreach ( rtm_destinos_publicados() as $d ) {
		$destinos[ $d['slug'] ] = $d['nome'];
	}
	$tipos = rtm_tipos_cotacao();

	echo '<div class="rtm-caixa">';
	echo '<h3>Sugestão</h3><p class="description">A imagem de destaque é a foto principal (caixa à direita).</p>';
	rtm_linha( 'Agente', 'rtm_s_agente', rtm_sug_select( 'agente', $agentes, $v['agente'], 'Escolha o agente' ) );
	rtm_linha( 'Destino principal', 'rtm_s_destino', rtm_sug_select( 'destino', $destinos, $v['destino'], 'Escolha o destino' ), 'Usado para filtrar a cotação.' );
	rtm_sug_texto( 'destinos_texto', $v['destinos_texto'], 'Ex.: Gramado e Canela (aparece no selo da página).' );
	rtm_linha( 'Tipo de viagem', 'rtm_s_tipo', rtm_sug_select( 'tipo', array_combine( $tipos, $tipos ), $v['tipo'], 'Escolha o tipo' ) );
	rtm_sug_texto( 'texto_card', $v['texto_card'], 'Até 140 caracteres; aparece no cartão do perfil.' );
	rtm_sug_texto( 'resumo', $v['resumo'], 'Aparece no alto da página da sugestão.' );

	$ids  = is_array( $v['galeria'] ) ? array_map( 'intval', $v['galeria'] ) : array();
	$prev = '';
	foreach ( $ids as $id ) {
		$u = wp_get_attachment_image_url( $id, 'thumbnail' );
		if ( $u ) {
			$prev .= '<img src="' . esc_url( $u ) . '" alt="" data-id="' . (int) $id . '" style="width:64px;height:64px;object-fit:cover;margin:0 6px 6px 0;border-radius:6px">';
		}
	}
	echo '<div class="rtm-linha rtm-galeria-campo"><strong>Galeria</strong> <span class="description">(até 8 fotos, além da imagem de destaque)</span><br>'
		. '<input type="hidden" id="rtm_s_galeria" name="rtm_s[galeria]" value="' . esc_attr( implode( ',', $ids ) ) . '">'
		. '<span class="rtm-galeria-previa">' . $prev . '</span><br>'
		. '<button type="button" class="button rtm-galeria-escolher">Escolher fotos</button> '
		. '<button type="button" class="button-link rtm-galeria-limpar"' . ( $ids ? '' : ' hidden' ) . '>Limpar</button></div>';

	echo '<h3>Detalhes</h3>';
	foreach ( array( 'duracao' => 'Ex.: 4 dias · 3 noites', 'ideal_para' => 'Ex.: Família com crianças', 'melhor_epoca' => 'Ex.: Mar a jun · Ago a out', 'saida' => 'Ex.: De qualquer cidade (opcional)' ) as $c => $ajuda ) {
		rtm_sug_texto( $c, $v[ $c ], $ajuda );
	}
	rtm_linha( 'A partir de (R$ por pessoa)', 'rtm_s_preco', '<input type="text" inputmode="numeric" class="small-text" id="rtm_s_preco" name="rtm_s[preco]" value="' . esc_attr( (string) $v['preco'] ) . '">', 'Valor de referência. Em branco, a página não mostra preço.' );
	rtm_sug_repetidor( 'inclui', 'O que inclui', array( '' => 'Item (ex.: Transfer do aeroporto)' ), is_array( $v['inclui'] ) ? $v['inclui'] : array(), 12, 'Adicionar item' );
	rtm_sug_repetidor( 'dias', 'Roteiro', array( 'titulo' => 'Título do dia (ex.: Snowland)', 'descricao' => 'O que acontece (até 200 caracteres)' ), is_array( $v['dias'] ) ? $v['dias'] : array(), 10, 'Adicionar dia' );
	rtm_linha( 'Ordem', 'rtm_s_ordem', '<input type="number" class="small-text" id="rtm_s_ordem" name="rtm_s[ordem]" value="' . esc_attr( (string) (int) $v['ordem'] ) . '">', 'Menor aparece primeiro.' );
	if ( '' !== (string) $v['img_teste'] ) {
		rtm_sug_texto( 'img_teste', $v['img_teste'] );
	}
	echo '</div>';
}

function rtm_colunas_sugestao( $colunas ) {
	$novas = array();
	if ( isset( $colunas['cb'] ) ) {
		$novas['cb'] = $colunas['cb'];
	}
	$novas['title']          = isset( $colunas['title'] ) ? $colunas['title'] : 'Título';
	$novas['rtm_sug_agente'] = 'Agente';
	$novas['rtm_sug_destino'] = 'Destino';
	$novas['rtm_sug_ordem']  = 'Ordem';
	return $novas;
}

function rtm_coluna_sugestao( $coluna, $post_id ) {
	if ( 'rtm_sug_agente' === $coluna ) {
		$a = get_post( (int) get_post_meta( $post_id, 'rtm_agente', true ) );
		echo $a ? esc_html( $a->post_title ) : '—';
	} elseif ( 'rtm_sug_destino' === $coluna ) {
		$d = (string) get_post_meta( $post_id, 'rtm_destino', true );
		echo '' !== $d ? esc_html( $d ) : '—';
	} elseif ( 'rtm_sug_ordem' === $coluna ) {
		echo (int) get_post_meta( $post_id, 'rtm_ordem', true );
	}
}

add_action( 'save_post_rt_sugestao', 'rtm_salvar_sugestao', 10 );
add_action( 'add_meta_boxes', 'rtm_adicionar_caixa_sugestao' );
add_filter( 'manage_rt_sugestao_posts_columns', 'rtm_colunas_sugestao' );
add_action( 'manage_rt_sugestao_posts_custom_column', 'rtm_coluna_sugestao', 10, 2 );
