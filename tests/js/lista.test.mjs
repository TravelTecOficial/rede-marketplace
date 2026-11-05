import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const codigo = fs.readFileSync(new URL('../../rede-marketplace/assets/lista.js', import.meta.url), 'utf8');
const card = (s, d, t) => `<article class="rtm-lista-card" data-slug="${s}" data-destinos="${d}" data-tipos="${t}"></article>`;
const html = `<select id="rtm-lista-destino"><option value="">Todos os destinos</option><option value="gramado">Gramado</option><option value="canela">Canela</option></select>
<div id="rtm-lista-tipos"><button type="button" class="rtm-pilula" data-tipo="Família" aria-pressed="false">Família</button><button type="button" class="rtm-pilula" data-tipo="Grupo" aria-pressed="false">Grupo</button></div>
<p id="rtm-lista-conta"></p><div class="rtm-lista-grade">${card('mariana', 'gramado', 'Família')}${card('camila', 'gramado canela', 'Grupo|Natal Luz')}${card('rafael', 'gramado', 'Luxo')}</div><p id="rtm-lista-vazio" hidden>Nenhum</p>`;
function montar(q = '') {
  const { window } = new JSDOM(`<body>${html}</body>`, { runScripts: 'outside-only', url: 'https://exemplo.test/especialistas/' + q });
  window.eval(codigo);
  return window;
}
const vis = (w) => [...w.document.querySelectorAll('.rtm-lista-card')].filter((c) => !c.hidden).map((c) => c.dataset.slug);

test('sem filtro: todos e contagem', () => {
  const w = montar();
  assert.deepEqual([...vis(w)], ['mariana', 'camila', 'rafael']);
  assert.equal(w.document.getElementById('rtm-lista-conta').textContent, '3 especialistas');
});

test('destino e tipo filtram; clicar de novo no tipo limpa', () => {
  const w = montar();
  const sel = w.document.getElementById('rtm-lista-destino');
  sel.value = 'canela'; sel.dispatchEvent(new w.Event('change'));
  assert.deepEqual([...vis(w)], ['camila']);
  assert.equal(w.document.getElementById('rtm-lista-conta').textContent, '1 especialista');
  sel.value = ''; sel.dispatchEvent(new w.Event('change'));
  const fam = w.document.querySelector('[data-tipo="Família"]');
  fam.click();
  assert.deepEqual([...vis(w)], ['mariana']);
  assert.equal(fam.getAttribute('aria-pressed'), 'true');
  fam.click();
  assert.equal(vis(w).length, 3);
});

test('nenhum resultado mostra o aviso; ?destino= da URL já filtra', () => {
  const w = montar('?destino=canela');
  assert.equal(w.document.getElementById('rtm-lista-destino').value, 'canela');
  w.document.querySelector('[data-tipo="Família"]').click();
  assert.equal(vis(w).length, 0);
  assert.equal(w.document.getElementById('rtm-lista-vazio').hidden, false);
});
