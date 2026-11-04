# Sugestões de viagem do agente — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** O agente tem sugestões de viagem (cards na lateral do perfil + página própria da sugestão), que levam à cotação com a sugestão marcada.

**Architecture:** Novo tipo `rt_sugestao` (arquivo `includes/sugestao.php`) com dados públicos via `rtm_sugestao()`, URL `/especialistas/<agente>/<sugestao>/` por regra de rewrite própria; painel em `includes/admin-sugestao.php`; HTML em `includes/render-sugestao.php` + `templates/sugestao.php`; galeria em `assets/sugestao.js`; cotação lê `?sugestao=` no navegador a partir de um JSON embutido.

**Tech Stack:** WordPress (PHP 7.4+), JS sem dependências, testes php-wasm (PHP 7.4 e 8.2) + jsdom (`cd tests && npm test`), `empacotar.py`, release GitHub (atualização automática).

**Spec:** `C:\Users\User\Documents\Clientes\RedeTuristica\docs\superpowers\specs\2026-11-04-rede-sugestoes-agente-design.md` · Prévia: https://claude.ai/artifact/H1Fkx54JDUEaxkL12dbsJR

## Global Constraints

- Prefixo `rtm_` em funções, metas (`rtm_<campo>`) e classes CSS; textos em português do Brasil, iguais aos do spec.
- PHP 7.4 compatível; nada de dependência nova; nenhum dado sai do navegador (cotação continua em ensaio).
- CSS sem `!important`; seletores com especificidade ≥ (0,2,1) contra o kit do Elementor e o reset do Hello (`[type=button]`, `button` pintam de `#c36`).
- Modo ensaio: `noindex`, fora dos sitemaps (WP e Rank Math), fora da busca e da REST pública — igual a `rt_agente`.
- Sugestão de agente inativo/inexistente, em rascunho, ou URL com agente errado: 404.
- Copy fixa: "Sugestões de <primeiro nome>", "Viagens que <primeiro nome> costuma montar", "Ver sugestão", "a partir de R$ X por pessoa", "Quero uma cotação dessa sugestão", "A partir de / por pessoa · referência; o valor final vem na cotação", "<primeiro nome> ajusta hotel, dias e passeios ao seu jeito na cotação.", "Outras sugestões de <primeiro nome>", "Ver perfil de <primeiro nome>", "Sugestão escolhida: <título> (<nome do agente>)".
- Versão final 0.10.0 (cabeçalho, `RTM_VERSION`, `tests/php/estrutura.test.php`, `readme.txt`).

## Review Focus

1. Sugestão sem imagem de destaque: o card e a página abrem sem espaço vazio de imagem (bloco de imagem omitido) — teste no Task 3.
2. Plugin atualizado pelo GitHub (sem reativar): a URL `/especialistas/<agente>/<sugestao>/` funciona — regras de rewrite renovadas quando `RTM_VERSION` muda — teste no Task 1.
3. Mesmo slug de sugestão em dois agentes / URL com o agente de outra pessoa: só a combinação certa abre; a outra dá 404 — teste no Task 1.
4. `?sugestao=` de outro agente ou de sugestão inexistente na cotação: aviso só quando a sugestão existe; o agente marcado é o dono da sugestão — teste no Task 5.
5. Preço: inteiro em reais formatado "R$ 2.890"; vazio ou 0 não mostra preço em lugar nenhum — teste no Task 3.

---

### Task 1: Tipo `rt_sugestao`, dados públicos, URL e proteções

**Files:**
- Create: `rede-marketplace/includes/sugestao.php`
- Modify: `rede-marketplace/rede-marketplace.php` (lista de includes: `sugestao` depois de `modelo`)
- Modify: `rede-marketplace/includes/privacidade.php` (noindex/sitemaps também para `rt_sugestao`)
- Modify: `rede-marketplace/includes/modelo.php` (`rtm_fora_das_listas` cobre `rt_sugestao`)
- Test: `tests/php/sugestao.test.php`

**Interfaces:**
- Produces:
  - `rtm_campos_sugestao(): array` — chave => `['rotulo','tipo','max']`; chaves: `agente` (id), `destino` (slug), `destinos_texto`, `tipo` (nome do tipo de viagem), `texto_card` (max 140), `resumo`, `galeria` (lista de ids), `duracao`, `ideal_para`, `melhor_epoca`, `saida`, `inclui` (lista de strings), `dias` (lista de `['titulo','descricao']`), `preco` (inteiro, reais), `ordem` (inteiro), `img_teste` (URL, só teste).
  - `rtm_sugestao( int $post_id ): ?array` — null se não for `rt_sugestao` publicada ou se `rtm_agente(agente)` for null. Retorna campos + `id`, `slug`, `titulo`, `url`, `imagem` (URL da imagem destacada `large`, senão `img_teste`, senão ''), `fotos` (lista de URLs: imagem + galeria), `agente` (array de `rtm_agente()`).
  - `rtm_sugestoes_do_agente( int $agente_id ): array` — publicadas, por `ordem` ASC e depois título.
  - `rtm_url_sugestao( array $agente, string $slug ): string` — `home_url('/especialistas/<agente-slug>/<slug>/')`.
  - `rtm_preco_txt( $reais ): string` — `'R$ 2.890'`; '' para vazio/0.
  - Query var `rtm_sug_agente`; regra `^especialistas/([^/]+)/([^/]+)/?$` → `index.php?rt_sugestao=$matches[2]&rtm_sug_agente=$matches[1]`.

- [ ] **Step 1: Write the failing tests** em `tests/php/sugestao.test.php` (fixtures no estilo de `render.test.php`: agente 5 "Mariana Kloss" ativo; sugestões 20 "Gramado em família" `agente=5`, `preco=2890`, `ordem=2`; 21 "Natal Luz" `ordem=1`; 22 rascunho; 23 de agente inativo):
  - `igual( null, rtm_sugestao( 22 ) )`, `igual( null, rtm_sugestao( 23 ) )`, `igual( null, rtm_sugestao( 5 ) )`.
  - `igual( 'Mariana Kloss', rtm_sugestao( 20 )['agente']['nome'] )`; `contem( '/especialistas/mariana-kloss/gramado-em-familia/', rtm_sugestao( 20 )['url'], … )`.
  - `igual( array( 21, 20 ), wp_list_pluck( rtm_sugestoes_do_agente( 5 ), 'id' ) )` (ordem, sem rascunho).
  - `igual( 'R$ 2.890', rtm_preco_txt( 2890 ) )`; `igual( '', rtm_preco_txt( 0 ) )`; `igual( '', rtm_preco_txt( '' ) )`.
  - Proteções: `rtm_sitemaps_tipos( array( 'rt_sugestao' => 1, 'page' => 1 ) )` sem `rt_sugestao` em ensaio; `rtm_rank_math_excluir_tipo( false, 'rt_sugestao' ) === true`; `rtm_pagina_marketplace()` verdadeiro com post atual `rt_sugestao`.
  - Rewrite renovado: com `rtm_regras_versao` ≠ `RTM_VERSION`, `rtm_renovar_regras()` chama `flush_rewrite_rules` (stub conta chamadas) e grava a versão; segunda chamada não chama de novo.
  - URL de outro agente: `rtm_sugestao_da_url( 'rafael-antunes', 20 ) === false`; `rtm_sugestao_da_url( 'mariana-kloss', 20 ) === true`.
- [ ] **Step 2: Run** `cd tests && node run.mjs sugestao` → FAIL (funções inexistentes).
- [ ] **Step 3: Implement** em `includes/sugestao.php`: `register_post_type( 'rt_sugestao', … )` (public, `exclude_from_search`, `show_in_rest` false, `show_in_menu` `edit.php?post_type=rt_agente`, supports title+thumbnail, `rewrite` false, `query_var` true); `rtm_campos_sugestao()`, `rtm_sugestao()`, `rtm_sugestoes_do_agente()`, `rtm_url_sugestao()`, `rtm_preco_txt()`; `add_rewrite_rule` + `query_vars`; `post_type_link` para `rt_sugestao` usando `rtm_url_sugestao`; `rtm_renovar_regras()` em `init` prioridade 99 (option `rtm_regras_versao`); `rtm_sugestao_da_url( string $agente_slug, int $id ): bool`; `template_redirect`: 404 (mesmo padrão de `rtm_404_inativo`) quando `rtm_sugestao()` é null ou o agente da URL não bate. Em `privacidade.php` e `modelo.php`, tratar `rt_sugestao` como `rt_agente`.
- [ ] **Step 4: Run** `cd tests && npm test` → PASS (todos os arquivos, PHP 7.4 e 8.2).
- [ ] **Step 5: Commit** `git add -A && git commit -m "Sugestoes: tipo rt_sugestao, URL e protecoes"`

### Task 2: Painel da sugestão

**Files:**
- Create: `rede-marketplace/includes/admin-sugestao.php`
- Modify: `rede-marketplace/rede-marketplace.php` (include `admin-sugestao` depois de `admin`)
- Modify: `rede-marketplace/assets/admin.js` (repetidor genérico + galeria múltipla)
- Modify: `rede-marketplace/includes/admin.php` (`rtm_scripts_admin` também na tela de `rt_sugestao`)
- Test: `tests/php/admin-sugestao.test.php`

**Interfaces:**
- Consumes: `rtm_campos_sugestao()` (Task 1); `rtm_destinos_publicados()`, `rtm_tipos_cotacao()`, `rtm_agentes_ativos()` existentes; `rtm_especialidades_normalizar()` como modelo de lista.
- Produces: `rtm_limpar_sugestao( array $entrada ): array`; `rtm_salvar_sugestao( int $post_id )` em `save_post_rt_sugestao` (nonce `rtm_sugestao`); caixa "Dados da sugestão"; colunas Agente/Destino/Ordem na lista.

- [ ] **Step 1: Write the failing tests:** `rtm_limpar_sugestao()` — `agente` só id de `rt_agente` existente (senão 0); `destino` só slug de destino publicado (senão ''); `tipo` só nome em `rtm_tipos_cotacao()`; `texto_card` cortado em 140 (mb); `galeria` só ids numéricos, sem repetição, máx 8; `inclui` sem vazios, máx 12; `dias` `{titulo,descricao}`, sem título descartado, máx 10, descrição máx 200; `preco` inteiro ≥0 a partir de "2.890", "2890", "R$ 2.890" (vira 2890) e "abc" (vira ''); `ordem` inteiro. Salvar sem nonce não grava; com nonce grava `rtm_<campo>`. Caixa renderiza select de agente com os ativos e `name="rtm_s[...]"` escapados.
- [ ] **Step 2: Run** `cd tests && node run.mjs admin-sugestao` → FAIL.
- [ ] **Step 3: Implement** `includes/admin-sugestao.php` (campos em `rtm_s[...]`); `admin.js`: repetidor genérico por `data-rtm-repetidor` (reaproveitar a lógica das especialidades) e galeria (`wp.media` com `multiple: true`, lista de miniaturas com remover, input hidden de ids separados por vírgula).
- [ ] **Step 4: Run** `cd tests && npm test` → PASS.
- [ ] **Step 5: Commit** `git commit -am "Sugestoes: painel"`

### Task 3: Cards na página do agente

**Files:**
- Create: `rede-marketplace/includes/render-sugestao.php`
- Modify: `rede-marketplace/includes/agente.php` (`rtm_secoes()` ganha `sugestoes`), `rede-marketplace/includes/render.php` (`rtm_secao` delega `sugestoes`), `rede-marketplace/templates/agente.php`, `rede-marketplace/assets/rede-marketplace.css`
- Test: `tests/php/render-sugestao.test.php`

**Interfaces:**
- Consumes: `rtm_sugestao()`, `rtm_sugestoes_do_agente()`, `rtm_preco_txt()` (Task 1); `rtm_primeiro_nome()` existente.
- Produces: `rtm_card_sugestao( array $s ): string` (`<a class="rtm-sug" href>` com `.rtm-sug-img` só se `imagem`, chip do `tipo`, título, `texto_card`, preço só se houver, "Ver sugestão"); `rtm_html_sugestoes( array $agente, string $onde ): string` (`$onde` = `lateral` | `celular`; '' sem sugestões); `[rt_agente secao="sugestoes"]` = lateral.

- [ ] **Step 1: Write the failing tests:** com 2 sugestões: lateral contém "Sugestões de Mariana", "Viagens que Mariana costuma montar", 2× `class="rtm-sug"`, href da sugestão; preço "a partir de <strong>R$ 2.890</strong> por pessoa" só na que tem preço; sugestão sem imagem não tem `rtm-sug-img`; agente sem sugestões → `''`. Modelo reserva (`templates/agente.php` renderizado com o agente) tem a ordem: `rtm-sugestoes-lateral` antes de `rtm-lateral` (cotação) dentro da coluna lateral, e `rtm-sugestoes-celular` logo depois de `rtm-especialidades`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** render + template: coluna lateral vira `<div class="rtm-col-lateral">` com sugestões e depois `rtm_secao('cotacao')`; bloco celular escondido acima de 900px, lateral escondida abaixo (mesmo breakpoint de hoje); a lateral deixa de ser `sticky`. CSS do card conforme prévia (imagem 16:9, chip sobre a imagem, cards empilhados com `gap:12px`).
- [ ] **Step 4: Run** `npm test` → PASS.
- [ ] **Step 5: Commit** `git commit -am "Sugestoes: cards no perfil"`

### Task 4: Página da sugestão e galeria

**Files:**
- Create: `rede-marketplace/templates/sugestao.php`, `rede-marketplace/assets/sugestao.js`
- Modify: `rede-marketplace/includes/render-sugestao.php`, `rede-marketplace/includes/agente.php` (`template_include` também para `rt_sugestao`, sempre o modelo PHP), `rede-marketplace/includes/render.php` (CSS/JS enfileirados em `is_singular('rt_sugestao')`), `rede-marketplace/assets/rede-marketplace.css`
- Test: `tests/php/render-sugestao.test.php` (mais casos), `tests/js/sugestao.test.mjs`

**Interfaces:**
- Consumes: Task 1 e 3; `rtm_url_cotacao( array $agente )` existente (acrescentar `&sugestao=<slug>`).
- Produces: `rtm_pagina_sugestao_html( array $s ): string`; `rtm_url_cotacao_sugestao( array $s ): string`.

- [ ] **Step 1: Write the failing tests (PHP):** trilha com link do agente; galeria com `data-fotos` contando as fotos e "+N fotos" quando > 3; título h1 único; fatos só os preenchidos; "O que inclui" e "Roteiro sugerido" somem vazios; nota "Mariana ajusta hotel, dias e passeios ao seu jeito na cotação."; outras sugestões excluem a atual; lateral com preço (ou sem bloco de preço), botão com href `…/cotacao-de-viagens/?destino=gramado&agente=mariana-kloss&sugestao=gramado-em-familia` (usar a URL de cotação das opções); barra celular com o mesmo botão. **(JS)** clicar numa foto abre `dialog.rtm-galeria-dialogo` na foto clicada; "próxima"/"anterior" e setas do teclado trocam; Esc e o X fecham e o foco volta; com 1 foto não há setas.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** conforme prévia (galeria 2fr/1fr; no celular só a principal; `dialog` movido para o `body`, mesmo padrão de `assets/agente.js`).
- [ ] **Step 4: Run** `npm test` → PASS.
- [ ] **Step 5: Commit** `git commit -am "Sugestoes: pagina da sugestao e galeria"`

### Task 5: Sugestão marcada na cotação

**Files:**
- Modify: `rede-marketplace/includes/cotacao.php`, `rede-marketplace/assets/cotacao.js`, `rede-marketplace/assets/rede-marketplace.css`
- Test: `tests/php/cotacao.test.php`, `tests/js/cotacao.test.mjs`

**Interfaces:**
- Consumes: `rtm_sugestao()` (Task 1).
- Produces: `<script type="application/json" id="rtm-sugestoes-dados">` com `{ "<slug>": { "titulo", "agente": "<slug do agente>", "agente_nome", "destino" } }` só de sugestões publicadas de agentes ativos; `<div id="rtm-sugestao-aviso" hidden>` com botão `#rtm-sugestao-tirar`; `estado().sugestao` (slug ou '').

- [ ] **Step 1: Write the failing tests:** PHP — JSON contém a sugestão publicada e não a de agente inativo; aviso existe e começa `hidden`. JS — `?sugestao=gramado-em-familia` mostra "Sugestão escolhida: Gramado em família, 4 dias (Mariana Kloss)", marca o card `mariana-kloss` mesmo sem `&agente`, preenche o destino; `?sugestao=nao-existe` não mostra aviso e `estado().sugestao === ''`; "tirar" esconde o aviso e zera `estado().sugestao` sem desmarcar o agente.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** `npm test` → PASS.
- [ ] **Step 5: Commit** `git commit -am "Sugestoes: sugestao marcada na cotacao"`

### Task 6: Sugestões de teste, versão e pacote

**Files:**
- Modify: `rede-marketplace/includes/teste.php` (3 sugestões por agente de teste, criadas/apagadas junto), `rede-marketplace/assets/teste/` (ilustrações SVG: `sug-*.svg`), `rede-marketplace/rede-marketplace.php`, `rede-marketplace/readme.txt`, `tests/php/teste.test.php`, `tests/php/estrutura.test.php`
- Test: `tests/php/teste.test.php`

**Interfaces:**
- Consumes: Task 1 (`img_teste`, campos).
- Produces: `rtm_dados_sugestoes_teste(): array` (por nome do agente); sugestões marcadas `rtm_teste=1`.

- [ ] **Step 1: Write the failing tests:** criar agentes de teste cria 9 sugestões (3 por agente) com `rtm_teste=1`, ligadas ao agente certo; apagar remove agentes e sugestões; a Mariana tem "Gramado em família, 4 dias" (Família, R$ 2.890, 4 dias, 6 itens em "inclui", 4 dias de roteiro, texto igual à prévia), "Natal Luz com ingressos garantidos" (R$ 3.450) e "Serra Gaúcha sem pressa, 5 dias" (Melhor idade, R$ 3.980); versão 0.10.0.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** (textos do Rafael e da Camila coerentes com as especialidades de cada um; nada de foto de terceiros).
- [ ] **Step 4: Run** `npm test` → PASS; `python empacotar.py` → zip 0.10.0 com `assets/sugestao.js`, `templates/sugestao.php` e os SVG.
- [ ] **Step 5: Commit** `git commit -am "Sugestoes: dados de teste e 0.10.0"`

Depois da revisão final: push, `gh release create v0.10.0 dist/rede-marketplace.zip`, dono atualiza pelo painel, dono clica "Apagar" + "Criar agentes de teste", conferência no site (1440/375, URL errada dá 404, cotação com sugestão), CHANGELOG/CLAUDE.md/módulo no repo do cliente.
