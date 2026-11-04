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

/**
 * Sugestões de exemplo por agente de teste (slug do agente => lista). Ilustrações do próprio plugin
 * (assets/teste/sug-*.svg), nada de foto de terceiros. Textos da Mariana iguais à prévia aprovada (04/11/2026).
 */
function rtm_dados_sugestoes_teste() {
	$fam_dias = array(
		array( 'titulo' => 'Chegada e Rua Coberta', 'descricao' => 'Transfer até o hotel, fim de tarde no centro e chocolate quente.' ),
		array( 'titulo' => 'Snowland', 'descricao' => 'Manhã na neve indoor; tarde livre no hotel com recreação.' ),
		array( 'titulo' => 'Canela e Mini Mundo', 'descricao' => 'Catedral de Pedra, Caracol e Mini Mundo; jantar de fondue.' ),
		array( 'titulo' => 'Café colonial e volta', 'descricao' => 'Café colonial tranquilo antes do transfer para o aeroporto.' ),
	);
	return array(
		'mariana-kloss'  => array(
			array( 'slug' => 'gramado-em-familia-4-dias', 'titulo' => 'Gramado em família, 4 dias', 'img' => 'sug-familia', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado e Canela', 'tipo' => 'Família', 'preco' => 2890,
				'texto_card'     => 'Parques, Snowland e café colonial num ritmo que funciona com criança.',
				'resumo'         => 'Parques, Snowland e café colonial num ritmo que funciona com criança: um passeio grande por dia e tarde livre no hotel com recreação.',
				'duracao'        => '4 dias · 3 noites', 'ideal_para' => 'Família com crianças', 'melhor_epoca' => 'Mar a jun · Ago a out', 'saida' => 'De qualquer cidade',
				'inclui'         => array( '3 noites em hotel com recreação infantil', 'Ingressos Snowland e Mini Mundo', 'Transfer aeroporto de Porto Alegre', 'Tour Gramado e Canela com guia', 'Café colonial para a família', 'Aéreo cotado à parte, se quiser' ),
				'dias'           => $fam_dias,
			) ),
			array( 'slug' => 'natal-luz-com-ingressos-garantidos', 'titulo' => 'Natal Luz com ingressos garantidos', 'img' => 'sug-natal', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado', 'tipo' => 'Natal Luz', 'preco' => 3450,
				'texto_card'     => '3 noites perto da Rua Coberta e lugar no Grande Desfile.',
				'resumo'         => 'Três noites a poucas quadras da Rua Coberta, com ingressos do Grande Desfile e do Nativitaten já garantidos.',
				'duracao'        => '4 dias · 3 noites', 'ideal_para' => 'Casais e famílias', 'melhor_epoca' => 'Nov a jan',
				'inclui'         => array( 'Hotel no centro de Gramado', 'Ingressos Grande Desfile e Nativitaten', 'Transfer aeroporto', 'Jantar de Natal típico' ),
				'dias'           => array(
					array( 'titulo' => 'Chegada e luzes da cidade', 'descricao' => 'Passeio a pé pela Rua Coberta iluminada.' ),
					array( 'titulo' => 'Grande Desfile', 'descricao' => 'Lugar reservado na arquibancada do desfile de Natal.' ),
					array( 'titulo' => 'Nativitaten', 'descricao' => 'Espetáculo no Lago Joaquina Rita Bier à noite.' ),
				),
			) ),
			array( 'slug' => 'serra-gaucha-sem-pressa-5-dias', 'titulo' => 'Serra Gaúcha sem pressa, 5 dias', 'img' => 'sug-serra', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado, Canela e Bento Gonçalves', 'tipo' => 'Melhor idade', 'preco' => 3980,
				'texto_card'     => 'Hotéis no centro, transfers e vinícolas do Vale dos Vinhedos.',
				'resumo'         => 'Dias leves, hotel perto de tudo e transfers em todos os passeios, com um dia no Vale dos Vinhedos.',
				'duracao'        => '5 dias · 4 noites', 'ideal_para' => 'Melhor idade', 'melhor_epoca' => 'Abr a jun · Set a nov',
				'inclui'         => array( 'Hotel no centro, com elevador', 'Transfers em todos os passeios', 'Dia no Vale dos Vinhedos com almoço', 'Café colonial' ),
			) ),
		),
		'rafael-antunes' => array(
			array( 'slug' => 'lua-de-mel-em-gramado-4-noites', 'titulo' => 'Lua de mel em Gramado, 4 noites', 'img' => 'sug-romance', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado', 'tipo' => 'Casal / Lua de mel', 'preco' => 4200,
				'texto_card'     => 'Chalé com lareira, jantar a dois e um dia sem roteiro.',
				'resumo'         => 'Quatro noites num chalé com lareira e banheira, jantares reservados e um dia livre para o casal.',
				'duracao'        => '5 dias · 4 noites', 'ideal_para' => 'Recém-casados', 'melhor_epoca' => 'O ano todo',
				'inclui'         => array( 'Chalé com lareira e banheira', 'Jantar romântico de fondue', 'Transfer privativo', 'Espumante de boas-vindas' ),
			) ),
			array( 'slug' => 'vale-dos-vinhedos-a-dois', 'titulo' => 'Vale dos Vinhedos a dois, 3 dias', 'img' => 'sug-vinho', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Bento Gonçalves e Gramado', 'tipo' => 'Luxo', 'preco' => 3600,
				'texto_card'     => 'Degustações, almoço harmonizado e pousada entre as videiras.',
				'resumo'         => 'Duas noites numa pousada entre as videiras, com degustações guiadas e um almoço harmonizado.',
				'duracao'        => '3 dias · 2 noites', 'ideal_para' => 'Casais', 'melhor_epoca' => 'Jan a mar (vindima)',
				'inclui'         => array( 'Pousada boutique no Vale', 'Três degustações guiadas', 'Almoço harmonizado', 'Motorista nos passeios' ),
			) ),
			array( 'slug' => 'gramado-luxo-com-spa', 'titulo' => 'Gramado de luxo com spa', 'img' => 'sug-chale', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado', 'tipo' => 'Luxo', 'preco' => 5900,
				'texto_card'     => 'Hotel cinco estrelas, dia de spa e restaurantes premiados.',
				'resumo'         => 'Hotel cinco estrelas, um dia inteiro de spa e reservas nos restaurantes mais disputados da cidade.',
				'duracao'        => '4 dias · 3 noites', 'ideal_para' => 'Casais', 'melhor_epoca' => 'Mai a ago',
				'inclui'         => array( 'Hotel cinco estrelas', 'Circuito de spa para dois', 'Reservas em restaurantes', 'Transfer privativo' ),
			) ),
		),
		'camila-duarte'  => array(
			array( 'slug' => 'natal-luz-em-grupo-saindo-de-sao-paulo', 'titulo' => 'Natal Luz em grupo, saindo de São Paulo', 'img' => 'sug-grupo', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado', 'tipo' => 'Grupo', 'preco' => 2490,
				'texto_card'     => 'Ônibus de São Paulo, hotel e ingressos para o grupo todo.',
				'resumo'         => 'Saída de São Paulo de ônibus, hotel para o grupo e ingressos dos espetáculos garantidos com antecedência.',
				'duracao'        => '6 dias · 3 noites', 'ideal_para' => 'Grupos de 15 a 40 pessoas', 'melhor_epoca' => 'Nov a jan', 'saida' => 'São Paulo/SP',
				'inclui'         => array( 'Ônibus de turismo ida e volta', 'Hotel para o grupo', 'Ingressos do Natal Luz', 'Guia acompanhante' ),
			) ),
			array( 'slug' => 'gramado-e-canela-melhor-idade', 'titulo' => 'Gramado e Canela para a melhor idade', 'img' => 'sug-canela', 'destino' => 'canela', 'campos' => array(
				'destinos_texto' => 'Gramado e Canela', 'tipo' => 'Melhor idade', 'preco' => 2790,
				'texto_card'     => 'Passeios tranquilos, paradas e acompanhamento o tempo todo.',
				'resumo'         => 'Passeios no ritmo do grupo, com paradas, restaurantes acessíveis e acompanhamento pelo WhatsApp.',
				'duracao'        => '5 dias · 4 noites', 'ideal_para' => 'Melhor idade', 'melhor_epoca' => 'Mar a jun',
				'inclui'         => array( 'Hotel acessível no centro', 'Passeios com guia', 'Café colonial', 'Acompanhamento pelo WhatsApp' ),
			) ),
			array( 'slug' => 'excursao-de-onibus-natal-luz', 'titulo' => 'Excursão de ônibus para o Natal Luz', 'img' => 'sug-onibus', 'destino' => 'gramado', 'campos' => array(
				'destinos_texto' => 'Gramado', 'tipo' => 'Natal Luz', 'preco' => 1890,
				'texto_card'     => 'Fim de semana do Natal Luz com ônibus, hotel e guia.',
				'resumo'         => 'Um fim de semana do Natal Luz com tudo resolvido: ônibus, hotel, guia e o Grande Desfile.',
				'duracao'        => '4 dias · 2 noites', 'ideal_para' => 'Turmas de amigos', 'melhor_epoca' => 'Nov a jan', 'saida' => 'São Paulo/SP',
				'inclui'         => array( 'Ônibus ida e volta', 'Duas noites de hotel', 'Ingresso Grande Desfile', 'Guia acompanhante' ),
			) ),
		),
	);
}

/** Cria (ou atualiza) as sugestões de teste de um agente de teste. */
function rtm_criar_sugestoes_teste( $agente_id, $agente_slug ) {
	$dados = rtm_dados_sugestoes_teste();
	if ( empty( $dados[ $agente_slug ] ) ) {
		return;
	}
	$existentes = array();
	foreach ( get_posts( array( 'post_type' => 'rt_sugestao', 'post_status' => 'any', 'meta_key' => 'rtm_teste', 'meta_value' => '1', 'posts_per_page' => -1 ) ) as $p ) {
		$existentes[ $p->post_name ] = (int) $p->ID;
	}
	foreach ( $dados[ $agente_slug ] as $ordem => $s ) {
		$id = isset( $existentes[ $s['slug'] ] ) ? $existentes[ $s['slug'] ] : wp_insert_post( array(
			'post_type'   => 'rt_sugestao',
			'post_status' => 'publish',
			'post_title'  => $s['titulo'],
			'post_name'   => $s['slug'],
		) );
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		$campos              = $s['campos'];
		$campos['agente']    = (int) $agente_id;
		$campos['destino']   = rtm_teste_destino( $s['destino'] ) ? $s['destino'] : '';
		$campos['ordem']     = (int) $ordem + 1;
		$campos['img_teste'] = RTM_URL . 'assets/teste/' . $s['img'] . '.svg';
		foreach ( $campos as $chave => $valor ) {
			update_post_meta( $id, 'rtm_' . $chave, $valor );
		}
		update_post_meta( $id, 'rtm_teste', '1' );
	}
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
		$campos['cadastur_validade']     = '2027-03-25';
		foreach ( $campos as $chave => $valor ) {
			update_post_meta( $id, 'rtm_' . $chave, $valor );
		}
		update_post_meta( $id, 'rtm_teste', '1' );
		wp_set_object_terms( $id, $a['tipos'], 'rt_tipo_viagem' );
		rtm_criar_sugestoes_teste( $id, $a['slug'] );
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
	foreach ( get_posts( array( 'post_type' => 'rt_sugestao', 'post_status' => 'any', 'meta_key' => 'rtm_teste', 'meta_value' => '1', 'posts_per_page' => -1 ) ) as $p ) {
		wp_delete_post( $p->ID, true );
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
