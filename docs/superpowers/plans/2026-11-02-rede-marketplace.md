# Rede Turística Marketplace — passo 1 (agentes + cotação) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Plugin WordPress `rede-marketplace` que cria o cadastro de agentes, a página pública de cada agente e a página de cotação em 4 etapas com escolha de 1 a 3 agentes — sem enviar nada.

**Architecture:** Mesmo padrão do Viator Tec (`../plugin-viator`): PHP puro em `includes/`, um arquivo por responsabilidade, testes PHP por php-wasm e JS por jsdom, zip gerado por `empacotar.py`, atualização por release do GitHub. Agente = post type `rt_agente` com metadados; páginas montadas por shortcodes de seção, compostos num modelo do Theme Builder instalado pelo plugin (editável no Elementor). A cotação é um shortcode que entrega todos os agentes ativos; o filtro, o limite e a pré-seleção acontecem no navegador (funciona com o cache do LiteSpeed).

**Tech Stack:** PHP 7.4+ (WordPress, Elementor Pro/PRO Elements), JS puro (sem biblioteca), testes `@php-wasm/node` + `jsdom` + `node:test`, Python 3 para empacotar e conferir.

**Spec:** `Clientes/RedeTuristica/docs/superpowers/specs/2026-11-02-rede-marketplace-agentes-design.md`
Prévias aprovadas (referência visual e de comportamento): cotação https://claude.ai/artifact/Ho5aESVPAAChV4NVC46i37 · agente https://claude.ai/artifact/25JGPprFUNM7mdW6vbPiQ8 (os HTMLs das prévias estão no histórico da sessão; copiar estrutura e CSS de lá).

## Global Constraints

- Prefixo de funções/opções/metadados: `rtm_`; constantes `RTM_`; text domain `rede-marketplace`; pasta do plugin `rede-marketplace/`; zip `dist/rede-marketplace.zip`.
- Requires PHP: 7.4. Testes rodam em PHP 7.4 e 8.2.
- Post type `rt_agente`, URL `/especialistas/<slug>/`. Taxonomia `rt_tipo_viagem`, termos iniciais: Família · Casal / Lua de mel · Natal Luz · Grupo · Melhor idade · Luxo.
- Um agente por agência: dados da agência ficam no agente.
- Campos privados (`whatsapp`, `email`, `company_id`, `ativo`, `cadastur_conferido_em`) **nunca** aparecem no HTML público, em atributos `data-*`, na REST ou no JSON da cotação.
- "Cadastur verificado" só aparece com `cadastur_conferido_em` preenchido.
- Destinos: ler o post type `destinos` (gerado pelo Cliente Ideal) e **nunca gravar** nele; o agente guarda `[{id, slug}]`; filtros usam o slug.
- Nada de Google neste passo (sem chave): o campo `google_perfil` é só guardado.
- Nenhum envio de dados: o botão "Pedir cotações" só valida e mostra o aviso de ensaio. Nenhum `fetch`, XHR ou `form action` externo.
- Não tocar no formulário #1025, nas páginas de Destinos nem em formulários de outros sites.
- Modo ensaio (opção `rtm_ensaio`, padrão ligado): `noindex` em `rt_agente` e na página de cotação, fora dos sitemaps (WP e Rank Math), sem item de menu.
- Textos da interface em português do Brasil, como nas prévias. Orçamento por pessoa, exatamente: `Até R$ 2.000` · `R$ 2.000 a R$ 3.500` · `R$ 3.500 a R$ 5.000` · `R$ 5.000 a R$ 8.000` · `Acima de R$ 8.000` · `Ainda não defini`.
- Toda saída escapada (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` só na apresentação longa).

## Review Focus

1. `?destino=` com maiúscula, acento ou slug inexistente (`?destino=Gramado`, `?destino=gramadô`, `?destino=xyz`) → normaliza com `sanitize_title`; se não houver agente, mostra o aviso "Nenhum especialista com esse perfil ainda…" e a opção "a Rede escolhe" continua funcionando. (teste JS na Task 6)
2. `?agente=` de agente inativo, inexistente ou de outro destino → ignorado sem erro; nenhum card marcado. (teste JS na Task 6)
3. Agente sem foto, sem logo, sem especialidades ou sem endereço → card e página sem buraco: iniciais no lugar da foto/logo, seção some. (teste PHP na Task 4)
4. Texto com HTML/script na apresentação, no texto curto ou no nome → escapado no card, na página e no JSON. (teste PHP na Task 4 e Task 5)
5. Agente editado com página em cache → ao salvar, purga o cache do post e da página de cotação (`do_action( 'litespeed_purge_post', $id )` e `do_action( 'litespeed_purge_url', <url da cotação> )`). (teste PHP na Task 2)

---

### Task 1: Esqueleto do plugin, testes e empacotamento

**Files:**
- Create: `rede-marketplace/rede-marketplace.php`, `rede-marketplace/readme.txt`, `rede-marketplace/includes/atualizador.php`
- Create: `tests/run.mjs`, `tests/package.json`, `tests/php/bootstrap.php`, `tests/php/estrutura.test.php`, `empacotar.py`, `README.md`, `.gitignore`

**Interfaces:**
- Produces: constantes `RTM_VERSION` ('0.1.0'), `RTM_DIR`, `RTM_URL`, `RTM_BASENAME`; lista de carga `array( 'opcoes', 'modelo', 'admin', 'privacidade', 'render', 'agente', 'cotacao', 'instalador', 'teste', 'atualizador' )` em `includes/<nome>.php`; constante de teste `RTM_TESTE`; helpers de teste `ok()`, `igual()`, `contem()`, `nao_contem()` e o contador `$GLOBALS['rt_falhas']`.

- [ ] **Step 1:** Copiar `run.mjs`, `package.json` e `bootstrap.php` de `../plugin-viator/tests/`, trocando `viator-tec`→`rede-marketplace`, `VTEC_`→`RTM_`, `vt_falhas`→`rt_falhas`. Acrescentar ao bootstrap stubs de `register_post_type`, `register_taxonomy`, `get_post_meta`/`update_post_meta` (memória em `$GLOBALS['rt_meta']`), `get_posts` (filtra `$GLOBALS['rt_posts']` por `post_type`/`post_status`), `get_the_terms`, `wp_insert_term`, `term_exists`, `add_filter`/`add_action`/`do_action` (registram em `$GLOBALS['rt_ganchos']`), `wp_kses_post` (strip de `<script>`), `get_permalink`, `wp_get_attachment_image_url`.
- [ ] **Step 2: teste que falha** — `estrutura.test.php`: carrega `/p/rede-marketplace.php`; `igual( '0.1.0', RTM_VERSION, ... )`; para cada nome da lista de carga, `ok( file_exists( '/p/includes/'.$n.'.php' ) )`; o cabeçalho tem `Requires PHP: 7.4` e `Version: 0.1.0`.
- [ ] **Step 3:** Rodar `cd tests && npm install && node run.mjs estrutura` → FALHA (arquivos não existem).
- [ ] **Step 4:** Criar o arquivo principal no formato de `viator-tec.php` e um `includes/<nome>.php` vazio (só o guarda `ABSPATH`) para cada nome. `atualizador.php` copiado do Viator Tec com `RTM_REPO = 'TravelTecOficial/rede-marketplace'`, `RTM_ANEXO = 'rede-marketplace.zip'`, token opcional `RTM_GITHUB_TOKEN`. `empacotar.py` copiado trocando nomes e `INCLUIR = ["rede-marketplace.php", "readme.txt", "includes/*.php", "assets/*", "assets/teste/*", "templates/*.json"]`.
- [ ] **Step 5:** `node run.mjs` → `TUDO OK`. `python empacotar.py` → gera `dist/rede-marketplace.zip`.
- [ ] **Step 6: Commit** — `git init` na pasta `plugin-rede-marketplace/`, commit "esqueleto do plugin Rede Marketplace 0.1.0". (Criar o repositório privado `TravelTecOficial/rede-marketplace` no GitHub só na Task 8, com ok do dono.)

### Task 2: Modelo de dados — agentes, tipos de viagem e leitura pública

**Files:**
- Modify: `includes/modelo.php`, `includes/opcoes.php`
- Test: `tests/php/modelo.test.php`

**Interfaces:**
- Produces:
  - `rtm_registrar()` — registra `rt_agente` (public, `has_archive` false, `rewrite` slug `especialistas`, `show_in_rest` false, `supports` title+thumbnail) e `rt_tipo_viagem` (hierárquica false, `show_in_rest` false, `public` false).
  - `rtm_tipos_iniciais(): array` — os 6 nomes da Global Constraints; `rtm_instalar_tipos()` cria os que faltam (ativação).
  - `rtm_campos(): array` — `chave => array( 'rotulo', 'tipo' (texto|textarea|numero|imagem|lista|destinos|especialidades|data|email|telefone|checkbox), 'publico' bool, 'max' int|null )`. Chaves: `cargo`, `texto_card` (max 180), `apresentacao`, `cadastur`, `anos_experiencia`, `viajantes_atendidos`, `idiomas`, `especialidades` (max 4, itens `{titulo, descricao}`), `destinos` (`[{id, slug}]`), `destino_principal` (slug), `agencia_nome`, `agencia_logo` (id de anexo), `agencia_cidade`, `agencia_desde`, `endereco`, `horario`, `google_perfil`, `foto_url_teste` (só para os agentes de teste) — públicos; `whatsapp`, `email`, `company_id`, `ativo`, `cadastur_conferido_em` — privados.
  - `rtm_agente( int $post_id ): ?array` — só públicos + `id`, `slug`, `nome`, `url`, `foto` (url da imagem destacada ou `foto_url_teste` ou ''), `logo` (url ou ''), `iniciais` (2 letras), `agencia_iniciais`, `tipos` (array de nomes), `destinos_slugs` (array), `cadastur_verificado` (bool). `null` se o post não existir, não for `rt_agente`, não estiver publicado ou `ativo` não for '1'.
  - `rtm_agentes_ativos(): array` — `rtm_agente()` de todos os publicados e ativos, ordenados por título.
  - `rtm_purgar_cache( int $post_id )` — gancho `save_post_rt_agente`: dispara `litespeed_purge_post` do post e `litespeed_purge_url` da URL de cotação (`rtm_opcoes()['url_cotacao']`).
  - `rtm_opcoes(): array` — `array( 'url_cotacao' => home_url('/cotacao-especialistas/'), 'ensaio' => true )` mesclado com a opção `rtm_opcoes`.

- [ ] **Step 1: testes que falham** (`modelo.test.php`), com posts/metas falsos no bootstrap:
  - `rtm_agente()` de agente com `whatsapp`='11999990000', `email`='a@b.com', `company_id`='company-x': `ok( ! array_key_exists( 'whatsapp', $a ) )` e idem `email`, `company_id`, `ativo`, `cadastur_conferido_em`; e `nao_contem( '11999990000', wp_json_encode( $a ) )`.
  - `cadastur_verificado` é `false` com `cadastur`='21.123.456/0001-00' e sem data; `true` com `cadastur_conferido_em`='2026-11-02'.
  - agente com `ativo`='0' → `rtm_agente()` é `null` e não aparece em `rtm_agentes_ativos()`.
  - `destinos_slugs` de `[{id:10, slug:'gramado'},{id:11, slug:'canela'}]` é `array('gramado','canela')`.
  - `iniciais` de "Mariana Kloss" é "MK"; de "Ana" é "AN".
  - `rtm_purgar_cache(5)` registra `litespeed_purge_post` com 5 e `litespeed_purge_url` com `https://exemplo.test/cotacao-especialistas/` (Review Focus 5).
  - `rtm_tipos_iniciais()` igual à lista exata das Global Constraints.
- [ ] **Step 2:** `node run.mjs modelo` → FALHA.
- [ ] **Step 3:** Implementar as funções acima em `modelo.php` e `rtm_opcoes()` em `opcoes.php`; registrar `init` → `rtm_registrar`, `save_post_rt_agente` → `rtm_purgar_cache`, ativação → `rtm_registrar(); rtm_instalar_tipos(); flush_rewrite_rules();`.
- [ ] **Step 4:** `node run.mjs` → `TUDO OK`.
- [ ] **Step 5: Commit** "modelo: agentes, tipos de viagem e leitura pública".

### Task 3: Painel — campos do agente, salvamento e configurações

**Files:**
- Modify: `includes/admin.php`
- Create: `assets/admin.js`, `assets/admin.css`
- Test: `tests/php/admin.test.php`

**Interfaces:**
- Consumes: `rtm_campos()`, `rtm_opcoes()`.
- Produces:
  - `rtm_limpar_campos( array $entrada ): array` — normaliza o `$_POST`: `whatsapp` só dígitos (10 ou 11, senão ''); `email` via `sanitize_email`/`is_email` (inválido → ''); `texto_card` cortado em 180 caracteres; `especialidades` no máximo 4 itens não vazios; `destinos` = lista de ids válidos do post type `destinos` convertida em `[{id, slug}]` pelo `post_name` atual; `destino_principal` precisa estar entre os slugs, senão o primeiro; `anos_experiencia`/`viajantes_atendidos` inteiros ≥ 0 ou ''; `ativo` '1' ou '0'; `cadastur_conferido_em` `Y-m-d` válida ou ''; demais com `sanitize_text_field` (apresentação com `wp_kses_post`).
  - `rtm_salvar( int $post_id )` — gancho `save_post_rt_agente`; exige nonce `rtm_agente` e `current_user_can( 'edit_post', $post_id )`; grava cada chave de `rtm_campos()` com `update_post_meta( $id, 'rtm_'.$chave, ... )`.
  - Caixa "Dados do agente" no editor do `rt_agente` com os campos agrupados (Agente · Agência · Destinos e especialidades · Privado — "não aparece no site"); seletor de mídia do WordPress para o logo (a foto é a imagem destacada); destinos em lista de seleção múltipla com os posts publicados de `destinos`.
  - Página Configurações › Rede Marketplace: `url_cotacao`, `ensaio` (checkbox), e os botões da Task 7.
  - Colunas na lista de agentes: Foto, Agência, Destinos, Ativo, Cadastur conferido.

- [ ] **Step 1: testes que falham** (`admin.test.php`):
  - `whatsapp` '(11) 99999-0000' → '11999990000'; '123' → ''.
  - `email` 'x@' → ''.
  - `texto_card` de 250 caracteres → 180.
  - 6 especialidades (2 vazias) → 4 itens.
  - `destinos` `[10, 999]` com post 10 `post_name`='gramado' e 999 inexistente → `[{id:10, slug:'gramado'}]`; `destino_principal`='paris' → 'gramado'.
  - `cadastur_conferido_em` '31/02/2026' → ''.
  - `rtm_salvar()` sem nonce válido não grava nada.
- [ ] **Step 2:** `node run.mjs admin` → FALHA.
- [ ] **Step 3:** Implementar `admin.php` (caixa, salvamento, configurações, colunas) e `admin.js` (seletor de mídia, repetidor de até 4 especialidades). Os nomes exibidos seguem o rótulo de `rtm_campos()`.
- [ ] **Step 4:** `node run.mjs` → `TUDO OK`.
- [ ] **Step 5: Commit** "painel: campos do agente e configurações".

### Task 4: Página do agente — seções, privacidade e modelo do Elementor

**Files:**
- Modify: `includes/render.php`, `includes/agente.php`, `includes/privacidade.php`, `includes/instalador.php`
- Create: `assets/rede-marketplace.css`, `templates/agente.json`
- Test: `tests/php/render.test.php`, `tests/php/privacidade.test.php`, `tests/php/instalador.test.php`

**Interfaces:**
- Consumes: `rtm_agente()`, `rtm_opcoes()`.
- Produces:
  - `rtm_url_cotacao( array $agente ): string` — `url_cotacao` + `?destino=<destino_principal>&agente=<slug>`.
  - `rtm_secao( string $secao, array $agente ): string` — HTML de `topo` | `sobre` | `especialidades` | `onde` | `cotacao` (cartão lateral) | `barra` (rodapé fixo no celular). Classes `rtm-*`. Texto e ordem da prévia do agente, **sem** nota, avaliações, mapa do Google e sem o botão "Ver avaliações". Seção sem dado → '' (`sobre` sem apresentação; `especialidades` sem itens e sem destinos; `onde` sem endereço e sem horário). Foto vazia → bloco com `iniciais`; logo vazio → `agencia_iniciais`. Selo Cadastur só com `cadastur_verificado`.
  - `rtm_card( array $agente ): string` — card horizontal aprovado (usado na cotação); atributos `data-id`, `data-slug`, `data-destinos` (slugs separados por espaço), `data-tipos` (nomes separados por `|`).
  - Shortcode `[rt_agente secao="topo"]` — usa o agente do post atual; fora de `rt_agente` retorna ''.
  - `rtm_robots( array $robots ): array` (filtro `wp_robots`) — com `ensaio` ligado, `noindex, nofollow` em `rt_agente` e na página cujo conteúdo tem `[rt_cotacao`; filtros `wp_sitemaps_post_types` e `rank_math/sitemap/exclude_post_type` tiram `rt_agente` com `ensaio` ligado.
  - `rtm_instalar_modelo(): int` — cria (uma vez; se já existe, devolve o id) o `elementor_library` "Especialistas — página do agente" do tipo `single`, condição `include/singular/rt_agente`, a partir de `templates/agente.json` (contêineres V3 com widgets Shortcode, um por seção, na ordem da prévia; topo em largura total, depois coluna principal + lateral `cotacao`). Publica o modelo.
  - `rtm_template_reserva( string $template ): string` (filtro `template_include`) — se não houver modelo do Elementor ativo para `rt_agente`, renderiza `get_header()` + todas as seções + `get_footer()`.

- [ ] **Step 1: testes que falham**:
  - `render.test.php`: agente completo → `contem( 'Mariana Kloss', $topo )`, `contem( 'Pedir cotação com Mariana', $topo )`, `contem( 'destino=gramado&amp;agente=mariana-kloss', $topo )`; `nao_contem( '11999990000', <todas as seções juntas> )`; sem `cadastur_conferido_em` → `nao_contem( 'Cadastur verificado' )`; sem foto → `contem( '>MK<' )` (Review Focus 3); agente sem apresentação, especialidades, destinos, endereço e horário → `igual( '', rtm_secao( 'sobre', ... ) )` e idem `especialidades`, `onde`; nome `'<script>x</script>'` → `nao_contem( '<script>x', $topo )` (Review Focus 4); nenhuma seção contém `Google`.
  - `rtm_card()` → `contem( 'data-destinos="gramado canela"' )`, `contem( 'data-tipos="Família|Natal Luz"' )`, sem `data-` com whatsapp/email/company.
  - `privacidade.test.php`: com `ensaio` → `rtm_robots( array() )` em `rt_agente` tem `noindex` e `nofollow`; com `ensaio` desligado, não; sitemaps sem `rt_agente` no ensaio.
  - `instalador.test.php`: `rtm_instalar_modelo()` duas vezes → um único post, condição `include/singular/rt_agente`, `_elementor_data` com os shortcodes `[rt_agente secao="topo"]`… na ordem `topo, sobre, especialidades, onde` + `cotacao` na lateral + `barra`.
- [ ] **Step 2:** `node run.mjs` → FALHA nos 3 arquivos novos.
- [ ] **Step 3:** Implementar. CSS em `rede-marketplace.css` a partir da prévia do agente, trocando as fontes da prévia pelas do kit do site (`var(--e-global-typography-primary-font-family)` com reserva) e as cores pelas globais do kit com reserva (laranja `#f08a12`); enfileirar só em `rt_agente` e na página com `[rt_cotacao`. Instalar o modelo na ativação.
- [ ] **Step 4:** `node run.mjs` → `TUDO OK`.
- [ ] **Step 5: Commit** "página do agente: seções, ensaio sem indexação e modelo do Elementor".

### Task 5: Página de cotação — formulário em 4 etapas (servidor)

**Files:**
- Modify: `includes/cotacao.php`
- Test: `tests/php/cotacao.test.php`

**Interfaces:**
- Consumes: `rtm_agentes_ativos()`, `rtm_card()`, `rtm_tipos_iniciais()` / termos de `rt_tipo_viagem`.
- Produces: shortcode `[rt_cotacao]` → `rtm_cotacao_html(): string`. Estrutura da prévia de cotação: `<form id="rtm-cotacao" novalidate>` com as seções `data-etapa="1..4"`; IDs estáveis: `rtm-destino` (select com os posts de `destinos` que têm ao menos um agente ativo, `value`=slug), `rtm-origem`, `rtm-tipos` (botões `.rtm-pilula` com `data-tipo`), `rtm-lista` (todos os `rtm_card()` ativos), `rtm-vazio` (aviso, `hidden`), `rtm-rede` (checkbox "Prefiro que a Rede Turística escolha os especialistas para mim"), `rtm-datas` (3 pílulas `certas|mes|aberto`), `rtm-ida`, `rtm-volta`, `rtm-mes` (type month, visível só em "Só sei o mês"), `rtm-adultos` (1–4, 5+; padrão 2), `rtm-criancas` (0–2, 3+), `rtm-bebes` (0–1, 2+), `rtm-inclui` (pílulas: Aéreo, Hospedagem, Passeios, Ingressos Natal Luz, Transfer, Carro), `rtm-orcamento` (6 opções exatas das Global Constraints), `rtm-obs`, `rtm-nome`, `rtm-whats`, `rtm-email`, `rtm-horario` (Qualquer horário, Manhã, Tarde, Noite), `rtm-lgpd` (texto: "Autorizo a Rede Turística a enviar meus dados aos especialistas escolhidos para que preparem as cotações, conforme a Política de Privacidade." com link para a política do site), `rtm-aviso-ensaio` (hidden), barra `rtm-contagem` + botão `rtm-enviar` "Pedir cotações". Sem atributo `action`.

- [ ] **Step 1: testes que falham** (`cotacao.test.php`):
  - HTML contém os 6 rótulos de orçamento exatos e os 6 tipos de viagem.
  - `nao_contem( 'action=', $html )`; `nao_contem( '11999990000', $html )`; `nao_contem( 'company-', $html )`.
  - com 2 agentes ativos e 1 inativo → exatamente 2 ocorrências de `class="rtm-card"`.
  - o select `rtm-destino` lista só destinos com agente ativo (gramado sim; paris sem agente, não).
- [ ] **Step 2:** `node run.mjs cotacao` → FALHA.
- [ ] **Step 3:** Implementar `rtm_cotacao_html()`; enfileirar `assets/cotacao.js` (Task 6) quando o shortcode roda.
- [ ] **Step 4:** `node run.mjs` → `TUDO OK`.
- [ ] **Step 5: Commit** "cotação: formulário em 4 etapas".

### Task 6: Página de cotação — comportamento no navegador

**Files:**
- Create: `assets/cotacao.js`
- Test: `tests/js/cotacao.test.mjs`

**Interfaces:**
- Consumes: o HTML e os IDs da Task 5 (os testes montam um HTML mínimo com esses IDs e 3 cards: `mariana-kloss` (gramado; Família|Natal Luz), `rafael-antunes` (gramado; Casal / Lua de mel|Luxo), `camila-duarte` (gramado canela; Grupo|Natal Luz)).
- Produces: `window.RTMCotacao = { estado(): {destino, tipo, agentes: string[], rede: bool}, validar(): string[] /* ids dos campos com erro */ }`.

Comportamento: na carga, `destino` = `sanitize`(`?destino`) (minúsculas, sem acento, não alfanumérico → `-`) se existir no select, senão o primeiro; `tipo` inicial = nenhum (mostra todos do destino); card visível se o slug do destino está em `data-destinos` e (sem tipo ou tipo em `data-tipos`); trocar destino/tipo desmarca agentes que ficaram ocultos; clique/Enter/Espaço no card alterna, máximo 3; marcar `rtm-rede` limpa a seleção; marcar um card desmarca `rtm-rede`; `?agente=<slug>` marca o card se ele estiver visível; contagem "N especialista(s) · N cotação(ões)" ou "A Rede Turística escolhe por você · até 3"; `rtm-vazio` aparece quando nenhum card está visível. Enviar: `validar()` exige destino, (≥1 agente ou rede), adultos, nome, WhatsApp com 10–11 dígitos, e-mail válido, `rtm-lgpd`; com erro, marca `aria-invalid="true"` e rola até o primeiro; sem erro, mostra `rtm-aviso-ensaio` ("Ensaio: nada foi enviado. No próximo passo o pedido segue para os especialistas escolhidos."). Nunca chama `fetch`/`XMLHttpRequest`/`submit()` real.

- [ ] **Step 1: testes que falham** (`cotacao.test.mjs`, jsdom com `fetch` e `XMLHttpRequest` espiões):
  - URL `?destino=Gramado&agente=camila-duarte` → `estado().destino === 'gramado'` e `agentes` = `['camila-duarte']` (Review Focus 1).
  - `?destino=gramadô` → 'gramado'; `?destino=xyz` → primeiro destino do select, sem erro (Review Focus 1).
  - `?agente=nao-existe` → `agentes` vazio, sem exceção (Review Focus 2).
  - tipo "Casal / Lua de mel" → só `rafael-antunes` visível; `mariana-kloss` marcada antes fica desmarcada.
  - clicar nos 3 e num 4º card fictício → continua 3.
  - marcar `rtm-rede` → `agentes` vazio e `rede` true; clicar num card → `rede` false.
  - tipo sem agente → `rtm-vazio` sem `hidden`.
  - enviar vazio → `validar()` inclui `rtm-nome`, `rtm-whats`, `rtm-email`, `rtm-lgpd`; enviar preenchido → `rtm-aviso-ensaio` visível; `fetch` e `XMLHttpRequest` nunca chamados.
- [ ] **Step 2:** `cd tests && node --test "js/*.test.mjs"` → FALHA.
- [ ] **Step 3:** Implementar `cotacao.js` (IIFE, sem dependências).
- [ ] **Step 4:** `npm test` → PHP e JS `TUDO OK`.
- [ ] **Step 5: Commit** "cotação: filtro, seleção de até 3 e validação no navegador".

### Task 7: Agentes de teste

**Files:**
- Modify: `includes/teste.php`, `includes/admin.php` (botões)
- Create: `assets/teste/mariana.svg`, `assets/teste/rafael.svg`, `assets/teste/camila.svg` (retratos ilustrados das prévias)
- Test: `tests/php/teste.test.php`

**Interfaces:**
- Consumes: `rtm_campos()`, `update_post_meta`.
- Produces: `rtm_criar_agentes_teste(): array` (ids) e `rtm_apagar_agentes_teste(): int` (quantos), acionados por dois botões em Configurações › Rede Marketplace (nonce + `manage_options`). Os 3 agentes das prévias (Mariana Kloss / Serra Viagens / Gramado-RS; Rafael Antunes / Rota Sul Turismo / Porto Alegre-RS; Camila Duarte / Bem Viajar / São Paulo-SP), com textos, especialidades, tipos e destinos (`gramado`; Camila também `canela` se existir), `foto_url_teste` apontando para os SVGs, `ativo`='1', `cadastur_conferido_em` preenchido, WhatsApp/e-mail fictícios (`11900000001`… / `teste+mariana@redeturistica.com.br`), meta `rtm_teste`='1'. Título exibido sem "(teste)"; marca só no meta e na coluna do painel.

- [ ] **Step 1: testes que falham** — criar duas vezes → 3 posts no total (não duplica); todos com `rtm_teste`='1'; apagar → 3 e nenhum `rt_agente` com `rtm_teste` sobra; um agente real (sem `rtm_teste`) não é apagado.
- [ ] **Step 2:** `node run.mjs teste` → FALHA.
- [ ] **Step 3:** Implementar.
- [ ] **Step 4:** `npm test` → `TUDO OK`.
- [ ] **Step 5: Commit** "agentes de teste para o ensaio".

### Task 8: Entrega no site (ensaio), conferência e registro

**Files:**
- Create: `Clientes/RedeTuristica/wordpress/scripts/marketplace_conferir.py`
- Modify: `Clientes/RedeTuristica/wordpress/CHANGELOG.md`, `Clientes/RedeTuristica/wordpress/CLAUDE.md` (stack + versão), `Clientes/RedeTuristica/wordpress/docs/modulos/marketplace-piloto.md`, `rede-marketplace/readme.txt`

**Interfaces:**
- Consumes: o zip da Task 1 e o que as Tasks 2–7 entregam.
- Produces: `marketplace_conferir.py` (só leitura, HTTP público): `/especialistas/mariana-kloss/` → 200, `noindex` no HTML, contém "Mariana Kloss" e "Pedir cotação com Mariana", **não** contém `11900000001`, `teste+mariana`, `company-`; `/cotacao-especialistas/?destino=gramado` → 200, `noindex`, 3 `rtm-card`, sem `action=`; `/wp-sitemap.xml` e o sitemap do Rank Math sem `especialistas`; `/cotacao-de-viagens/` (formulário #1025) com o mesmo HTML do formulário de antes (comparar com `backups/` salvo antes da instalação). Imprime `N falhas`.

- [ ] **Step 1:** Antes de instalar: salvar `backups/cotacao-de-viagens-2026-11-0X.html` e a lista de páginas de Destinos (para comparar depois).
- [ ] **Step 2:** Pedir ok do dono para criar o repositório privado `TravelTecOficial/rede-marketplace`; `git push` e `gh release create v0.1.0 dist/rede-marketplace.zip`.
- [ ] **Step 3:** Dono envia o zip em Plugins › Adicionar novo › Enviar e ativa. Claude confere em Configurações › Rede Marketplace que `ensaio` está ligado e `url_cotacao` = `/cotacao-especialistas/`.
- [ ] **Step 4:** Dono clica "Criar agentes de teste" (ou Claude, se o painel for acessível pelo navegador do app com a sessão do dono).
- [ ] **Step 5:** Criar a página "Cotação com especialistas" (`/cotacao-especialistas/`), publicada, sem menu, com o shortcode `[rt_cotacao]` (Elementor MCP `create-page` + widget Shortcode, ou EMCP), template largura total.
- [ ] **Step 6:** `python scripts/marketplace_conferir.py` → `0 falhas`. Conferir no navegador a 1440 px e 375 px: página da Mariana e cotação com `?destino=gramado&agente=mariana-kloss`.
- [ ] **Step 7:** Versão do site → v2.4.0 no `CLAUDE.md`/CHANGELOG ("Marketplace — passo 1 em ensaio: página do agente e cotação com especialistas"); conferir que a versão mudou na tela onde ela aparece. Atualizar o documento do módulo (etapas, onde está o código, próximo passo) e a planilha (RT-05, RT-06, RT-07, RT-12 → Concluída com evidência; Diário). Commit e push no repo `cliente-redeturistica`.
- [ ] **Step 8:** Mandar ao dono os dois links para ele ver (agente e cotação) e pedir aprovação do passo 1.
