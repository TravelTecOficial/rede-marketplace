// Comportamento da página de cotação (assets/cotacao.js) em jsdom, com fetch/XHR espiões.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const JS = fs.readFileSync(new URL('../../rede-marketplace/assets/cotacao.js', import.meta.url), 'utf8');

const card = (slug, destinos, tipos) =>
  `<div class="rtm-card" role="button" tabindex="0" aria-pressed="false" data-id="1" data-slug="${slug}" data-destinos="${destinos}" data-tipos="${tipos}"><h3>${slug}</h3><a class="rtm-perfil" href="#perfil-${slug}">Ver perfil</a></div>`;
const sel = (id, ops, v) => `<select id="${id}">${ops.map((o) => `<option value="${o}"${o === v ? ' selected' : ''}>${o}</option>`).join('')}</select>`;

function html(destinos) {
  const opts = destinos.map(([s, t]) => `<option value="${t}" data-slug="${s}"></option>`).join('');
  return `<div class="rtm-cotacao"><h1>Receba até 3 cotações de especialistas<span id="rtm-titulo-destino"></span></h1>
<form id="rtm-cotacao" novalidate>
<input type="text" id="rtm-destino" list="rtm-destinos-lista"><datalist id="rtm-destinos-lista">${opts}</datalist>
<div id="rtm-tipos"><button type="button" class="rtm-pilula" data-tipo="Família" aria-pressed="false">Família</button><button type="button" class="rtm-pilula" data-tipo="Casal / Lua de mel" aria-pressed="false">Casal / Lua de mel</button><button type="button" class="rtm-pilula" data-tipo="Aventura" aria-pressed="false">Aventura</button></div>
<section class="rtm-etapa" data-etapa="2"><p class="rtm-etapa-dica">Marque de 1 a 3.</p></section><div id="rtm-lista">${card('mariana-kloss', 'gramado', 'Família|Natal Luz')}${card('rafael-antunes', 'gramado', 'Casal / Lua de mel|Luxo')}${card('camila-duarte', 'gramado canela', 'Grupo|Natal Luz')}${card('bruno-seibel', 'gramado', 'Família')}</div>
<div id="rtm-vazio" hidden>Nenhum</div>
<label for="rtm-rede"><input type="checkbox" id="rtm-rede"></label>
<div id="rtm-datas"><button type="button" class="rtm-pilula" data-v="certas" aria-pressed="true"></button><button type="button" class="rtm-pilula" data-v="mes" aria-pressed="false"></button><button type="button" class="rtm-pilula" data-v="aberto" aria-pressed="false"></button></div>
<label for="rtm-ida"><input type="date" id="rtm-ida"></label><label for="rtm-volta"><input type="date" id="rtm-volta"></label><label for="rtm-mes" hidden><input type="month" id="rtm-mes"></label>
${sel('rtm-adultos', ['1', '2', '3'], '2')}
<div id="rtm-inclui"><button type="button" class="rtm-pilula" data-item="Aéreo" aria-pressed="true"></button><button type="button" class="rtm-pilula" data-item="Carro" aria-pressed="false"></button></div>
<input id="rtm-nome"><input id="rtm-whats"><input id="rtm-email"><input type="checkbox" id="rtm-lgpd">
<div id="rtm-aviso-ensaio" hidden>Modo ensaio</div></form>
<div id="rtm-contagem">Nenhum especialista escolhido <em>· até 3</em></div><button type="button" id="rtm-enviar" disabled>Pedir</button></div>`;
}

function montar(query = '', destinos = [['gramado', 'Gramado'], ['canela', 'Canela']]) {
  const dom = new JSDOM(`<!doctype html><body>${html(destinos)}</body>`, { runScripts: 'outside-only', url: 'https://exemplo.test/cotacao/' + query });
  const w = dom.window;
  w.__rede = 0;
  w.fetch = () => { w.__rede++; return Promise.reject(new Error('rede')); };
  w.XMLHttpRequest = function () { w.__rede++; };
  w.HTMLFormElement.prototype.submit = () => { w.__rede++; };
  w.Element.prototype.scrollIntoView = function () { w.__rolou = this.id || true; };
  w.eval(JS);
  return w;
}
const $ = (w, id) => w.document.getElementById(id);
const cards = (w) => [...w.document.querySelectorAll('.rtm-card')];
const cardDe = (w, slug) => cards(w).find((c) => c.dataset.slug === slug);
const clicar = (el) => el.dispatchEvent(new el.ownerDocument.defaultView.MouseEvent('click', { bubbles: true, cancelable: true }));
const visiveis = (w) => cards(w).filter((c) => !c.hidden).map((c) => c.dataset.slug);
const P = (a) => JSON.parse(JSON.stringify(a));
const pilula = (w, id, attr, v) => w.document.querySelector(`#${id} [${attr}="${v}"]`);

test('?destino=Gramado&agente=camila-duarte', () => {
  const w = montar('?destino=Gramado&agente=camila-duarte');
  const e = w.RTMCotacao.estado();
  assert.equal(e.destino, 'gramado');
  assert.deepEqual(P(e.agentes), ['camila-duarte']);
  assert.equal(cardDe(w, 'camila-duarte').getAttribute('aria-pressed'), 'true');
  assert.equal($(w, 'rtm-enviar').disabled, false);
  assert.equal($(w, 'rtm-contagem').textContent.replace(/\s+/g, ' ').trim(), '1 especialista · 1 cotação');
});

test('destino com acento e destino inexistente na URL', () => {
  assert.equal(montar('?destino=gramadô').RTMCotacao.estado().destino, 'gramado');
  const w = montar('?destino=xyz', [['canela', 'Canela'], ['gramado', 'Gramado']]);
  assert.equal(w.RTMCotacao.estado().destino, '');
  assert.equal($(w, 'rtm-destino').value, '');
  assert.equal(visiveis(w).length, 4, 'destino em branco: todos os especialistas');
});

test('sem destino: título genérico e todos os especialistas', () => {
  const w = montar();
  assert.equal($(w, 'rtm-titulo-destino').textContent, ' nos melhores destinos');
  assert.equal(visiveis(w).length, 4);
});

test('destino digitado sem especialista: aviso com o nome e nenhum card', () => {
  const w = montar();
  $(w, 'rtm-destino').value = 'Paris';
  $(w, 'rtm-destino').dispatchEvent(new w.Event('input', { bubbles: true }));
  assert.equal(visiveis(w).length, 0);
  assert.equal($(w, 'rtm-vazio').hidden, false);
  assert.match($(w, 'rtm-vazio').textContent, /Ainda não temos especialista para Paris/);
  assert.equal($(w, 'rtm-titulo-destino').textContent, ' nos melhores destinos');
  assert.equal(w.RTMCotacao.estado().destino_texto, 'Paris');
  assert.ok(!w.RTMCotacao.validar().includes('rtm-destino'), 'destino digitado vale');
});

test('título recebe o destino e acompanha a troca', () => {
  const w = montar('?destino=gramado');
  assert.equal($(w, 'rtm-titulo-destino').textContent, ' em Gramado');
  $(w, 'rtm-destino').value = 'Canela';
  $(w, 'rtm-destino').dispatchEvent(new w.Event('input', { bubbles: true }));
  assert.equal($(w, 'rtm-titulo-destino').textContent, ' em Canela');
  assert.deepEqual(P(visiveis(w)), ['camila-duarte']);
  assert.equal($(w, 'rtm-vazio').hidden, true);
});

test('?agente inexistente ou oculto não marca nada nem lança erro', () => {
  assert.deepEqual(P(montar('?agente=nao-existe').RTMCotacao.estado().agentes), []);
  assert.deepEqual(P(montar('?destino=canela&agente=mariana-kloss').RTMCotacao.estado().agentes), ['mariana-kloss'], 'vindo do perfil: o agente vale mesmo fora do destino digitado');
});

test('sem tipo mostra todos do destino; tipo filtra e desmarca ocultos', () => {
  const w = montar();
  assert.deepEqual(P(visiveis(w)), ['mariana-kloss', 'rafael-antunes', 'camila-duarte', 'bruno-seibel']);
  clicar(cardDe(w, 'mariana-kloss'));
  assert.deepEqual(P(w.RTMCotacao.estado().agentes), ['mariana-kloss']);
  clicar(pilula(w, 'rtm-tipos', 'data-tipo', 'Casal / Lua de mel'));
  assert.deepEqual(P(visiveis(w)), ['rafael-antunes']);
  assert.deepEqual(P(w.RTMCotacao.estado().agentes), []);
  assert.equal(w.RTMCotacao.estado().tipo, 'Casal / Lua de mel');
  assert.equal(cardDe(w, 'mariana-kloss').getAttribute('aria-pressed'), 'false');
  clicar(pilula(w, 'rtm-tipos', 'data-tipo', 'Casal / Lua de mel'));
  assert.equal(w.RTMCotacao.estado().tipo, '');
  assert.equal(visiveis(w).length, 4);
});

test('tipo sem agente mostra rtm-vazio', () => {
  const w = montar();
  assert.equal($(w, 'rtm-vazio').hidden, true);
  clicar(pilula(w, 'rtm-tipos', 'data-tipo', 'Aventura'));
  assert.equal($(w, 'rtm-vazio').hidden, false);
});

test('máximo de 3 agentes; Enter e Espaço alternam', () => {
  const w = montar();
  ['mariana-kloss', 'rafael-antunes', 'camila-duarte', 'bruno-seibel'].forEach((s) => clicar(cardDe(w, s)));
  assert.deepEqual(P(w.RTMCotacao.estado().agentes), ['mariana-kloss', 'rafael-antunes', 'camila-duarte']);
  assert.equal($(w, 'rtm-contagem').textContent.replace(/\s+/g, ' ').trim(), '3 especialistas · 3 cotações');
  const c = cardDe(w, 'mariana-kloss');
  c.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
  assert.equal(w.RTMCotacao.estado().agentes.length, 2);
  c.dispatchEvent(new w.KeyboardEvent('keydown', { key: ' ', bubbles: true, cancelable: true }));
  assert.equal(w.RTMCotacao.estado().agentes.length, 3);
});

test('clicar em "Ver perfil" não alterna a seleção', () => {
  const w = montar();
  const a = cardDe(w, 'mariana-kloss').querySelector('a.rtm-perfil');
  const ev = new w.MouseEvent('click', { bubbles: true, cancelable: true });
  a.dispatchEvent(ev);
  assert.equal(ev.defaultPrevented, false);
  assert.deepEqual(P(w.RTMCotacao.estado().agentes), []);
});

test('rede limpa a seleção e escolher card desmarca rede', () => {
  const w = montar();
  clicar(cardDe(w, 'rafael-antunes'));
  const r = $(w, 'rtm-rede');
  r.checked = true;
  r.dispatchEvent(new w.Event('change', { bubbles: true }));
  let e = w.RTMCotacao.estado();
  assert.deepEqual(P(e.agentes), []);
  assert.equal(e.rede, true);
  assert.equal($(w, 'rtm-enviar').disabled, false);
  assert.match($(w, 'rtm-contagem').textContent, /A Rede Turística escolhe por você/);
  clicar(cardDe(w, 'rafael-antunes'));
  e = w.RTMCotacao.estado();
  assert.equal(e.rede, false);
  assert.deepEqual(P(e.agentes), ['rafael-antunes']);
});

test('pílulas de datas alternam campos e as de inclui alternam sozinhas', () => {
  const w = montar();
  const lab = (id) => $(w, id).closest('label');
  clicar(pilula(w, 'rtm-datas', 'data-v', 'mes'));
  assert.equal(lab('rtm-mes').hidden, false);
  assert.equal(lab('rtm-ida').hidden, true);
  assert.equal(pilula(w, 'rtm-datas', 'data-v', 'certas').getAttribute('aria-pressed'), 'false');
  clicar(pilula(w, 'rtm-datas', 'data-v', 'aberto'));
  assert.equal(lab('rtm-mes').hidden, true);
  assert.equal(lab('rtm-volta').hidden, true);
  clicar(pilula(w, 'rtm-datas', 'data-v', 'certas'));
  assert.equal(lab('rtm-ida').hidden, false);
  clicar(pilula(w, 'rtm-inclui', 'data-item', 'Carro'));
  assert.equal(pilula(w, 'rtm-inclui', 'data-item', 'Carro').getAttribute('aria-pressed'), 'true');
  assert.equal(pilula(w, 'rtm-inclui', 'data-item', 'Aéreo').getAttribute('aria-pressed'), 'true');
});

test('sem destinos não lança erro; destino em branco é obrigatório', () => {
  const w = montar('', []);
  assert.equal(w.RTMCotacao.estado().destino, '');
  assert.ok(w.RTMCotacao.validar().includes('rtm-destino'));
});

test('enviar vazio aponta erros; preenchido mostra o aviso de ensaio sem rede', () => {
  const w = montar('?destino=gramado');
  clicar($(w, 'rtm-enviar'));
  const erros = w.RTMCotacao.validar();
  for (const id of ['rtm-nome', 'rtm-whats', 'rtm-email', 'rtm-lgpd']) assert.ok(erros.includes(id), id);
  assert.ok(erros.includes('rtm-lista'));
  assert.equal($(w, 'rtm-nome').getAttribute('aria-invalid'), 'true');
  assert.equal($(w, 'rtm-aviso-ensaio').hidden, true);
  assert.ok(w.__rolou);

  clicar(cardDe(w, 'mariana-kloss'));
  $(w, 'rtm-nome').value = 'Ana Souza';
  $(w, 'rtm-whats').value = '(11) 99999-9999';
  $(w, 'rtm-email').value = 'ana@exemplo.com';
  $(w, 'rtm-lgpd').checked = true;
  clicar($(w, 'rtm-enviar'));
  assert.deepEqual(P(w.RTMCotacao.validar()), []);
  assert.equal($(w, 'rtm-aviso-ensaio').hidden, false);
  assert.match($(w, 'rtm-aviso-ensaio').textContent, /Ensaio: nada foi enviado/);
  assert.equal($(w, 'rtm-nome').hasAttribute('aria-invalid'), false);
  assert.equal(w.__rede, 0);
});

test('WhatsApp e e-mail inválidos são recusados', () => {
  const w = montar('?destino=gramado');
  clicar(cardDe(w, 'mariana-kloss'));
  $(w, 'rtm-nome').value = 'Ana';
  $(w, 'rtm-lgpd').checked = true;
  $(w, 'rtm-whats').value = '12345';
  $(w, 'rtm-email').value = 'ana@';
  assert.deepEqual(P(w.RTMCotacao.validar().sort()), ['rtm-email', 'rtm-whats']);
  $(w, 'rtm-whats').value = '(11) 89999-9999';
  assert.ok(w.RTMCotacao.validar().includes('rtm-whats'), 'celular sem o 9');
  $(w, 'rtm-whats').value = '(01) 99999-9999';
  assert.ok(w.RTMCotacao.validar().includes('rtm-whats'), 'DDD inválido');
  $(w, 'rtm-whats').value = '+55 (11) 99999-9999';
  assert.ok(!w.RTMCotacao.validar().includes('rtm-whats'), '+55 aceito');
  $(w, 'rtm-whats').value = '1133334444';
  $(w, 'rtm-email').value = 'a@b.co';
  assert.deepEqual(P(w.RTMCotacao.validar()), []);
});

test('faixa de acentos combinantes escrita com escapes (sem caracteres invisíveis no arquivo)', () => {
  const B = String.fromCharCode(92); // barra invertida
  const escrito = '[' + B + 'u0300-' + B + 'u036f]';
  assert.ok(JS.includes(escrito), 'usa ' + escrito);
  const literal = Array.from(JS).some((c) => c.charCodeAt(0) >= 0x300 && c.charCodeAt(0) <= 0x36f);
  assert.ok(!literal, 'nenhum caractere combinante literal no arquivo');
});

test('página de Destino: data-destino escolhe o destino sem parâmetro na URL', () => {
  const dom = new JSDOM(`<!doctype html><body>${html([['gramado', 'Gramado'], ['canela', 'Canela']]).replace('<div class="rtm-cotacao">', '<div class="rtm-cotacao rtm-cotacao-destino" data-destino="canela">')}</body>`, { runScripts: 'outside-only', url: 'https://exemplo.test/destinos/canela/' });
  dom.window.Element.prototype.scrollIntoView = function () {};
  dom.window.eval(JS);
  const d = dom.window.document;
  assert.equal(d.getElementById('rtm-destino').value, 'Canela');
  assert.equal(d.querySelector('[data-slug="camila-duarte"]').hidden, false);
  assert.equal(d.querySelector('[data-slug="mariana-kloss"]').hidden, true);
});

const SUGS = { 'gramado-em-familia-4-dias': { titulo: 'Gramado em família, 4 dias', agente: 'mariana-kloss', agente_nome: 'Mariana Kloss', destino: 'gramado' } };
function montarSug(query) {
  const extra = `<div id="rtm-sugestao-aviso" hidden><span id="rtm-sugestao-texto"></span><button type="button" id="rtm-sugestao-tirar">Tirar</button></div><script type="application/json" id="rtm-sugestoes-dados">${JSON.stringify(SUGS)}</script>`;
  const dom = new JSDOM(`<!doctype html><body>${html([['gramado', 'Gramado'], ['canela', 'Canela']]).replace('<form id="rtm-cotacao" novalidate>', extra + '<form id="rtm-cotacao" novalidate>')}</body>`, { runScripts: 'outside-only', url: 'https://exemplo.test/cotacao/' + query });
  dom.window.Element.prototype.scrollIntoView = function () {};
  dom.window.eval(JS);
  return dom.window;
}

test('?sugestao= mostra a sugestão, marca o agente dono e preenche o destino', () => {
  const w = montarSug('?sugestao=gramado-em-familia-4-dias');
  const d = w.document;
  assert.equal(d.getElementById('rtm-sugestao-aviso').hidden, false);
  assert.equal(d.getElementById('rtm-sugestao-texto').textContent, 'Sugestão escolhida: Gramado em família, 4 dias (Mariana Kloss)');
  assert.equal(d.getElementById('rtm-destino').value, 'Gramado');
  assert.equal(d.querySelector('[data-slug="mariana-kloss"]').getAttribute('aria-pressed'), 'true');
  assert.equal(w.RTMCotacao.estado().sugestao, 'gramado-em-familia-4-dias');
});

test('?sugestao= inexistente: sem aviso e sem sugestão no estado', () => {
  const w = montarSug('?sugestao=nao-existe');
  assert.equal(w.document.getElementById('rtm-sugestao-aviso').hidden, true);
  assert.equal(w.RTMCotacao.estado().sugestao, '');
});

test('tirar a sugestão esconde o aviso e mantém o agente', () => {
  const w = montarSug('?sugestao=gramado-em-familia-4-dias');
  w.document.getElementById('rtm-sugestao-tirar').click();
  assert.equal(w.document.getElementById('rtm-sugestao-aviso').hidden, true);
  assert.equal(w.RTMCotacao.estado().sugestao, '');
  assert.equal(w.document.querySelector('[data-slug="mariana-kloss"]').getAttribute('aria-pressed'), 'true');
});

test('?sugestao= com outro &agente: vale o dono da sugestão', () => {
  const w = montarSug('?sugestao=gramado-em-familia-4-dias&agente=camila-duarte');
  const d = w.document;
  assert.equal(d.querySelector('[data-slug="mariana-kloss"]').getAttribute('aria-pressed'), 'true');
  assert.equal(d.querySelector('[data-slug="camila-duarte"]').getAttribute('aria-pressed'), 'false');
});

test('vindo do perfil (?agente=): só esse agente, sem "a Rede escolhe", título com o nome', () => {
  const w = montar('?destino=gramado&agente=mariana-kloss');
  const d = w.document;
  assert.deepEqual(P(visiveis(w)), ['mariana-kloss']);
  assert.equal(cardDe(w, 'mariana-kloss').getAttribute('aria-pressed'), 'true');
  assert.equal(d.getElementById('rtm-rede').closest('label').hidden, true);
  assert.match(d.querySelector('h1').textContent, /Receba sua cotação de mariana-kloss|Receba sua cotação de/);
  clicar(cardDe(w, 'mariana-kloss'));
  assert.equal(cardDe(w, 'mariana-kloss').getAttribute('aria-pressed'), 'true', 'não dá para desmarcar o único agente');
  assert.equal(d.querySelector('.rtm-etapa-dica').textContent, 'mariana-kloss vai preparar a sua cotação.');
  d.querySelector('[data-tipo="Casal / Lua de mel"]').click();
  assert.deepEqual(P(visiveis(w)), ['mariana-kloss'], 'tipo de viagem não esconde o agente escolhido');
});

test('?agente= que não existe: cotação normal com todos', () => {
  const w = montar('?agente=nao-existe');
  assert.equal(visiveis(w).length, 4);
  assert.equal(w.document.getElementById('rtm-rede').closest('label').hidden, false);
});


test('com data-envio: manda o pedido ao site e mostra o obrigado', async () => {
  const w = montar('?destino=gramado&utm_source=google&gclid=abc');
  const form = $(w, 'rtm-cotacao');
  form.setAttribute('data-envio', 'https://exemplo.test/wp-json/rede-marketplace/v1/cotacao');
  form.setAttribute('data-nonce', 'n1');
  let chamada = null;
  w.fetch = (url, op) => { chamada = { url, op }; return Promise.resolve({ ok: true, json: () => Promise.resolve({ ok: true, agentes: ['mariana-kloss', 'rafael-antunes'] }) }); };
  clicar(cardDe(w, 'mariana-kloss'));
  clicar(cardDe(w, 'rafael-antunes'));
  $(w, 'rtm-nome').value = 'Ana Souza';
  $(w, 'rtm-whats').value = '(11) 99999-9999';
  $(w, 'rtm-email').value = 'ana@exemplo.com';
  $(w, 'rtm-lgpd').checked = true;
  $(w, 'rtm-destino').value = 'Gramado';
  clicar($(w, 'rtm-enviar'));
  await new Promise((r) => setTimeout(r, 20));
  assert.equal(chamada.url, 'https://exemplo.test/wp-json/rede-marketplace/v1/cotacao');
  assert.equal(chamada.op.method, 'POST');
  assert.equal(chamada.op.headers['X-WP-Nonce'], 'n1');
  const corpo = JSON.parse(chamada.op.body);
  assert.deepEqual(corpo.agentes, ['mariana-kloss', 'rafael-antunes']);
  assert.equal(corpo.nome, 'Ana Souza');
  assert.equal(corpo.destino_slug, 'gramado');
  assert.equal(corpo.lgpd, true);
  assert.deepEqual(corpo.utm, { utm_source: 'google', gclid: 'abc' });
  assert.match(corpo.pessoas, /2 adulto/);
  assert.equal(corpo.inclui, 'Aéreo');
  const ob = w.document.querySelector('.rtm-obrigado');
  assert.ok(ob, 'obrigado na tela');
  assert.match(ob.textContent, /Pedido enviado!/);
  assert.match(ob.textContent, /mariana-kloss e rafael-antunes vão falar com você/);
  assert.ok(w.document.querySelector('.rtm-cotacao').classList.contains('rtm-enviado'));
});

test('com data-envio: erro do servidor mostra a mensagem e libera o botão', async () => {
  const w = montar('?destino=gramado');
  $(w, 'rtm-cotacao').setAttribute('data-envio', 'https://exemplo.test/wp-json/rede-marketplace/v1/cotacao');
  w.fetch = () => Promise.resolve({ ok: false, json: () => Promise.resolve({ ok: false, msg: 'Muitos pedidos seguidos.' }) });
  clicar(cardDe(w, 'mariana-kloss'));
  $(w, 'rtm-nome').value = 'Ana'; $(w, 'rtm-whats').value = '11999999999'; $(w, 'rtm-email').value = 'a@b.co'; $(w, 'rtm-lgpd').checked = true;
  clicar($(w, 'rtm-enviar'));
  await new Promise((r) => setTimeout(r, 20));
  assert.equal($(w, 'rtm-aviso-ensaio').hidden, false);
  assert.match($(w, 'rtm-aviso-ensaio').textContent, /Muitos pedidos seguidos/);
  assert.ok($(w, 'rtm-aviso-ensaio').classList.contains('rtm-erro'));
  assert.equal($(w, 'rtm-enviar').disabled, false);
  assert.equal(w.document.querySelector('.rtm-obrigado'), null);
});

test('máscara do WhatsApp, mensagens de erro e nome inválido', () => {
  const w = montar('?destino=gramado');
  const t = $(w, 'rtm-whats');
  const digitar = (v) => { t.value = v; t.dispatchEvent(new w.Event('input', { bubbles: true })); };
  digitar('11999998888'); assert.equal(t.value, '(11) 99999-8888');
  digitar('1133334444'); assert.equal(t.value, '(11) 3333-4444');
  digitar('+55 11 99999-8888 123'); assert.equal(t.value, '(11) 99999-8888');
  digitar('119'); assert.equal(t.value, '(11) 9');
  $(w, 'rtm-nome').value = 'Ana 123';
  clicar($(w, 'rtm-enviar'));
  assert.ok(w.RTMCotacao.validar().includes('rtm-nome'));
  assert.equal($(w, 'rtm-nome-erro').hidden, false);
  assert.match($(w, 'rtm-nome-erro').textContent, /só letras/);
  $(w, 'rtm-nome').value = 'Ana  Souza';
  $(w, 'rtm-nome').dispatchEvent(new w.Event('input', { bubbles: true }));
  assert.equal($(w, 'rtm-nome-erro').hidden, true, 'aviso some ao corrigir');
  $(w, 'rtm-nome').dispatchEvent(new w.Event('blur'));
  assert.equal($(w, 'rtm-nome').value, 'Ana Souza');
  digitar('11999998888');
  assert.equal(w.RTMCotacao.pedido().telefone, '11999998888');
});

test('volta antes da ida e ida no passado são recusadas; volta mínima segue a ida', () => {
  const w = montar('?destino=gramado');
  const z = (n) => String(n).padStart(2, '0');
  const dia = (k) => { const a = new w.Date(); a.setDate(a.getDate() + k); return a.getFullYear() + '-' + z(a.getMonth() + 1) + '-' + z(a.getDate()); };
  const ida = $(w, 'rtm-ida'), volta = $(w, 'rtm-volta');
  assert.equal(ida.min, dia(0));
  ida.value = dia(10); ida.dispatchEvent(new w.Event('change', { bubbles: true }));
  assert.equal(volta.min, dia(10));
  volta.value = dia(5);
  assert.ok(w.RTMCotacao.validar().includes('rtm-volta'));
  volta.value = dia(10);
  assert.ok(!w.RTMCotacao.validar().includes('rtm-volta'), 'mesmo dia vale');
  ida.value = dia(-1);
  assert.ok(w.RTMCotacao.validar().includes('rtm-ida'));
  ida.value = dia(20); ida.dispatchEvent(new w.Event('change', { bubbles: true }));
  assert.equal(volta.value, '', 'volta antes da nova ida é limpa');
  volta.value = dia(25);
  const p = w.RTMCotacao.pedido();
  assert.equal(p.ida, dia(20));
  assert.equal(p.volta, dia(25));
  assert.match(p.datas, /^Ida \d{2}\/\d{2}\/\d{4} · volta \d{2}\/\d{2}\/\d{4}$/);
  clicar(pilula(w, 'rtm-datas', 'data-v', 'aberto'));
  volta.value = dia(1);
  assert.ok(!w.RTMCotacao.validar().includes('rtm-volta'), 'sem datas certas não valida ida/volta');
  assert.equal(w.RTMCotacao.pedido().ida, '');
});
