<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Agentes de teste para o ensaio: 3 agentes fictícios (marcados com o meta `rtm_teste` = '1'),
 * criados e apagados por dois botões em Configurações › Rede Marketplace.
 * Nenhum anexo é criado: a foto vem de um SVG do próprio plugin (`rtm_foto_url_teste`).
 */

/**
 * Avaliações fictícias para ver o layout antes da chave do Google (só agentes de teste, marcadas como exemplo).
 * Com a chave, as avaliações reais virão do Perfil do Google da agência.
 */
function rtm_avaliacoes_teste( $nome ) {
	$dados = array(
		'Mariana Kloss'  => array( 'nota' => 4.9, 'total' => 87, 'itens' => array(
			array( 'autor' => 'Juliana P.', 'quando' => 'há 2 semanas', 'nota' => 5, 'texto' => 'A Mariana pensou em tudo para a nossa viagem com duas crianças. Hotel ótimo, ingressos do Snowland já comprados e dicas de restaurante perfeitas.' ),
			array( 'autor' => 'Roberto C.', 'quando' => 'há 1 mês', 'nota' => 5, 'texto' => 'Fomos no Natal Luz com meus pais. Ela conseguiu lugar no Grande Desfile e um hotel a duas quadras da Rua Coberta. Recomendo muito.' ),
			array( 'autor' => 'Fernanda L.', 'quando' => 'há 3 meses', 'nota' => 4, 'texto' => 'Atendimento rápido pelo WhatsApp e roteiro bem montado. Só o transfer atrasou um pouco na chegada.' ),
		) ),
		'Rafael Antunes' => array( 'nota' => 4.8, 'total' => 54, 'itens' => array(
			array( 'autor' => 'Camila e André', 'quando' => 'há 3 semanas', 'nota' => 5, 'texto' => 'Nossa lua de mel foi perfeita. O chalé tinha lareira e banheira, e o jantar no Vale dos Vinhedos foi o ponto alto da viagem.' ),
			array( 'autor' => 'Patrícia M.', 'quando' => 'há 2 meses', 'nota' => 5, 'texto' => 'O Rafael entendeu exatamente o que a gente queria: pouco roteiro, muito descanso. Hotel boutique lindo.' ),
			array( 'autor' => 'Lucas T.', 'quando' => 'há 5 meses', 'nota' => 4, 'texto' => 'Ótimas indicações de vinícolas. A resposta demorou um dia na primeira mensagem, depois foi tudo rápido.' ),
		) ),
		'Camila Duarte'  => array( 'nota' => 5.0, 'total' => 31, 'itens' => array(
			array( 'autor' => 'Grupo Amigas da Mooca', 'quando' => 'há 1 semana', 'nota' => 5, 'texto' => 'Éramos 18 pessoas e a Camila cuidou de tudo: ônibus, hotel e ingressos do Natal Luz. Ninguém precisou se preocupar com nada.' ),
			array( 'autor' => 'Sérgio R.', 'quando' => 'há 1 mês', 'nota' => 5, 'texto' => 'Levei meus pais de 78 e 80 anos. Passeios no ritmo deles e acompanhamento pelo WhatsApp o tempo todo.' ),
			array( 'autor' => 'Igreja Batista Central', 'quando' => 'há 4 meses', 'nota' => 5, 'texto' => 'Excursão muito bem organizada, horários cumpridos e preço justo. Já fechamos a do ano que vem.' ),
		) ),
	);
	return isset( $dados[ $nome ] ) ? $dados[ $nome ] + array( 'exemplo' => true ) : array();
}

/** Os 3 agentes de teste. `destinos` lista os slugs dos destinos que devem existir no site. */
function rtm_dados_agentes_teste() {
	return array(
		array(
			'slug'     => 'mariana-kloss',
			'nome'     => 'Mariana Kloss',
			'svg'      => 'mariana',
			'tipos'    => array( 'Família', 'Natal Luz', 'Melhor idade' ),
			'destinos' => array( 'gramado' ),
			'campos'   => array(
				'cargo'               => 'Especialista em Gramado e Serra Gaúcha',
				'texto_card'          => 'Há 12 anos levo famílias para a Serra Gaúcha. Conheço cada parque, café colonial e chocolateria de Gramado.',
				'apresentacao'        => "Nasci em Nova Petrópolis e trabalho com turismo na Serra Gaúcha há 12 anos. Comecei montando viagens para famílias de amigos e hoje cuido de roteiros completos: hotel, passeios, ingressos e os cafés coloniais que valem a viagem.\n\nGosto de entender o ritmo de cada família. Com criança pequena, monto dias mais leves; com avós, escolho hotéis perto da Rua Coberta para ninguém se cansar.",
				'cadastur'            => '21.123.456/0001-00',
				'instagram'           => 'https://www.instagram.com/redeturistica/',
				'facebook'            => 'https://www.facebook.com/redeturistica',
				'site'                => 'https://redeturistica.com.br/',
				'anos_experiencia'    => 12,
				'viajantes_atendidos' => 1400,
				'idiomas'             => array( 'Português', 'Espanhol' ),
				'especialidades'      => array(
					array( 'titulo' => 'Família', 'descricao' => 'Parques, Snowland, Mini Mundo e hotéis com recreação.' ),
					array( 'titulo' => 'Natal Luz', 'descricao' => 'Ingressos para o Grande Desfile e o Nativitaten, com hotel perto do centro.' ),
					array( 'titulo' => 'Melhor idade', 'descricao' => 'Roteiros sem pressa, transfers e restaurantes acessíveis.' ),
					array( 'titulo' => 'Gastronomia', 'descricao' => 'Café colonial, fondue e vinícolas do Vale dos Vinhedos.' ),
				),
				'agencia_nome'        => 'Serra Viagens',
				'agencia_cidade'      => 'Gramado/RS',
				'agencia_desde'       => 2009,
				'endereco'            => 'Av. Borges de Medeiros, 1500 · Centro, Gramado/RS',
				'horario'             => 'Seg a sex, 9h às 18h · Sáb, 9h às 13h',
				'whatsapp'            => '11900000001',
				'email'               => 'teste+mariana@redeturistica.com.br',
			),
		),
		array(
			'slug'     => 'rafael-antunes',
			'nome'     => 'Rafael Antunes',
			'svg'      => 'rafael',
			'tipos'    => array( 'Casal / Lua de mel', 'Luxo' ),
			'destinos' => array( 'gramado' ),
			'campos'   => array(
				'cargo'               => 'Especialista em lua de mel e viagens de luxo na Serra Gaúcha',
				'texto_card'          => 'Monto roteiros românticos com hotéis boutique em Gramado e um dia no Vale dos Vinhedos.',
				'apresentacao'        => "Sou de Porto Alegre e há 9 anos desenho viagens a dois pela Serra Gaúcha. Meu foco são casais que querem tempo de qualidade: hotéis boutique, jantares reservados e passeios sem fila.\n\nCada roteiro tem um momento especial, como um piquenique nos vinhedos ou um fondue em um restaurante escondido. Antes de fechar, converso com o casal para acertar o ritmo e o orçamento.",
				'cadastur'            => '34.567.890/0001-12',
				'anos_experiencia'    => 9,
				'viajantes_atendidos' => 820,
				'idiomas'             => array( 'Português', 'Inglês' ),
				'especialidades'      => array(
					array( 'titulo' => 'Casal / Lua de mel', 'descricao' => 'Chalés românticos, jantares a dois e passeios sem pressa.' ),
					array( 'titulo' => 'Luxo', 'descricao' => 'Hotéis cinco estrelas, spa e experiências exclusivas.' ),
					array( 'titulo' => 'Vinícolas', 'descricao' => 'Degustações e almoços harmonizados no Vale dos Vinhedos.' ),
					array( 'titulo' => 'Hotéis boutique', 'descricao' => 'Pousadas charmosas, pequenas e com atendimento próximo.' ),
				),
				'agencia_nome'        => 'Rota Sul Turismo',
				'agencia_cidade'      => 'Porto Alegre/RS',
				'agencia_desde'       => 2014,
				'endereco'            => 'Rua Padre Chagas, 220 · Moinhos de Vento, Porto Alegre/RS',
				'horario'             => 'Seg a sex, 9h às 18h',
				'whatsapp'            => '11900000002',
				'email'               => 'teste+rafael@redeturistica.com.br',
			),
		),
		array(
			'slug'     => 'camila-duarte',
			'nome'     => 'Camila Duarte',
			'svg'      => 'camila',
			'tipos'    => array( 'Grupo', 'Natal Luz', 'Melhor idade' ),
			'destinos' => array( 'gramado', 'canela' ),
			'campos'   => array(
				'cargo'               => 'Especialista em grupos e excursões para o Natal Luz',
				'texto_card'          => 'Especialista em grupos para o Natal Luz desde 2015: hospedagem, ingressos e transporte resolvidos.',
				'apresentacao'        => "Organizo grupos para Gramado e Canela desde 2015: famílias grandes, turmas de amigos e excursões da melhor idade. Cuido de tudo, da saída em São Paulo à volta para casa, com hospedagem, ingressos e transporte combinados com antecedência.\n\nNo Natal Luz, os grupos têm lugar garantido nos principais espetáculos. Eu acompanho o grupo pelo WhatsApp durante toda a viagem.",
				'cadastur'            => '45.678.901/0001-23',
				'anos_experiencia'    => 10,
				'viajantes_atendidos' => 2300,
				'idiomas'             => array( 'Português' ),
				'especialidades'      => array(
					array( 'titulo' => 'Grupos', 'descricao' => 'Roteiros para turmas de amigos, igrejas e empresas.' ),
					array( 'titulo' => 'Natal Luz', 'descricao' => 'Pacotes com ingressos, hospedagem e transporte para o Natal Luz.' ),
					array( 'titulo' => 'Melhor idade', 'descricao' => 'Passeios tranquilos, com paradas e acompanhamento.' ),
					array( 'titulo' => 'Excursões', 'descricao' => 'Saídas em grupo com ônibus, guia e hotel incluídos.' ),
				),
				'agencia_nome'        => 'Bem Viajar',
				'agencia_cidade'      => 'São Paulo/SP',
				'agencia_desde'       => 2015,
				'endereco'            => 'Av. Paulista, 1000 · Bela Vista, São Paulo/SP',
				'horario'             => 'Seg a sex, 9h às 18h · Sáb, 9h às 12h',
				'whatsapp'            => '11900000003',
				'email'               => 'teste+camila@redeturistica.com.br',
			),
		),
	);
}

/** Destino publicado (tipo `destinos`) pelo slug: ['id'=>, 'slug'=>] ou null. */
function rtm_teste_destino( $slug ) {
	$achados = get_posts( array(
		'post_type'      => 'destinos',
		'post_status'    => 'publish',
		'name'           => $slug,
		'posts_per_page' => 1,
	) );
	return $achados ? array( 'id' => (int) $achados[0]->ID, 'slug' => (string) $achados[0]->post_name ) : null;
}

/** Verdadeiro se o destino `gramado` não está publicado (os agentes de teste ficam sem destinos). */
function rtm_teste_sem_destino() {
	return null === rtm_teste_destino( 'gramado' );
}

/** Agentes de teste já existentes (qualquer status, menos lixeira). */
function rtm_teste_posts() {
	return get_posts( array(
		'post_type'      => 'rt_agente',
		'post_status'    => 'any',
		'meta_key'       => 'rtm_teste',
		'meta_value'     => '1',
		'posts_per_page' => -1,
	) );
}

/** Cria os agentes de teste (idempotente: reaproveita o que já existe por slug + rtm_teste). Devolve os ids. */
function rtm_criar_agentes_teste() {
	rtm_instalar_tipos();
	$existentes = array();
	foreach ( rtm_teste_posts() as $p ) {
		$existentes[ $p->post_name ] = (int) $p->ID;
	}
	$hoje = gmdate( 'Y-m-d' );
	$ids  = array();
	foreach ( rtm_dados_agentes_teste() as $a ) {
		if ( isset( $existentes[ $a['slug'] ] ) ) {
			$id = $existentes[ $a['slug'] ];
		} else {
			$id = wp_insert_post( array(
				'post_type'   => 'rt_agente',
				'post_status' => 'publish',
				'post_title'  => $a['nome'],
				'post_name'   => $a['slug'],
			) );
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
		}
		$destinos = array();
		foreach ( $a['destinos'] as $slug ) {
			$d = rtm_teste_destino( $slug );
			if ( $d ) {
				$destinos[] = $d;
			}
		}
		$campos                          = $a['campos'];
		$campos['destinos']              = $destinos;
		$campos['destino_principal']     = $destinos ? $destinos[0]['slug'] : '';
		$campos['foto_url_teste']        = RTM_URL . 'assets/teste/' . $a['svg'] . '.svg';
		$campos['company_id']            = '';
		$campos['ativo']                 = '1';
		$campos['cadastur_conferido_em'] = $hoje;
		foreach ( $campos as $chave => $valor ) {
			update_post_meta( $id, 'rtm_' . $chave, $valor );
		}
		update_post_meta( $id, 'rtm_teste', '1' );
		wp_set_object_terms( $id, $a['tipos'], 'rt_tipo_viagem' );
		$ids[] = (int) $id;
	}
	// wp_insert_post purgou antes dos metas: limpa de novo com o agente completo (página dele e cotação).
	foreach ( $ids as $id ) {
		rtm_purgar_cache( $id );
	}
	return $ids;
}

/** Apaga de vez (force) só os agentes com rtm_teste = '1'. Devolve quantos. */
function rtm_apagar_agentes_teste() {
	$n = 0;
	foreach ( rtm_teste_posts() as $p ) {
		if ( '1' !== (string) get_post_meta( $p->ID, 'rtm_teste', true ) ) {
			continue;
		}
		if ( wp_delete_post( $p->ID, true ) ) {
			$n++;
		}
	}
	rtm_purgar_cotacao(); // apagar de vez não passa por save_post_rt_agente
	return $n;
}

/** Endereço da tela de configurações com o aviso para mostrar ao voltar. */
function rtm_teste_voltar( array $args ) {
	return add_query_arg( array_merge( array( 'page' => 'rede-marketplace' ), $args ), admin_url( 'options-general.php' ) );
}

function rtm_acao_criar_teste() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'rtm_criar_teste' );
	rtm_criar_agentes_teste();
	$args = array( 'rtm_teste' => 'criados' );
	if ( rtm_teste_sem_destino() ) {
		$args['rtm_sem_destino'] = '1';
	}
	wp_safe_redirect( rtm_teste_voltar( $args ) );
	exit;
}

function rtm_acao_apagar_teste() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'rtm_apagar_teste' );
	$n = rtm_apagar_agentes_teste();
	wp_safe_redirect( rtm_teste_voltar( array( 'rtm_teste' => 'apagados', 'rtm_n' => $n ) ) );
	exit;
}

add_action( 'admin_post_rtm_criar_teste', 'rtm_acao_criar_teste' );
add_action( 'admin_post_rtm_apagar_teste', 'rtm_acao_apagar_teste' );
