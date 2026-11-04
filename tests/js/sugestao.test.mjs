import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const codigo = fs.readFileSync(new URL('../../rede-marketplace/assets/sugestao.js', import.meta.url), 'utf8');
const pagina = (fotos) => `<div class="rtm-galeria" data-fotos="${fotos.length}" data-lista='${JSON.stringify(fotos)}'>`
  + fotos.slice(0, 3).map((f, i) => `<button type="button" class="rtm-galeria-foto" data-i="${i}"><img src="${f}" alt=""></button>`).join('') + '</div>'
  + '<dialog class="rtm-galeria-dialogo"><img class="rtm-galeria-grande" alt=""><button type="button" class="rtm-galeria-ant">‹</button><button type="button" class="rtm-galeria-prox">›</button><button type="button" class="rtm-galeria-fechar">×</button><span class="rtm-galeria-conta"></span></dialog>';

function montar(fotos) {
  const { window } = new JSDOM(`<body>${pagina(fotos)}</body>`, { runScripts: 'outside-only' });
  window.HTMLDialogElement.prototype.showModal = undefined;
  window.HTMLDialogElement.prototype.close = undefined;
  window.eval(codigo);
  return window;
}

test('clicar numa foto abre a galeria nela; próxima/anterior e setas trocam', () => {
  const w = montar(['a.jpg', 'b.jpg', 'c.jpg', 'd.jpg']);
  const d = w.document.querySelector('.rtm-galeria-dialogo');
  w.document.querySelectorAll('.rtm-galeria-foto')[1].click();
  assert.ok(d.hasAttribute('open'));
  assert.match(d.querySelector('.rtm-galeria-grande').src, /b\.jpg$/);
  assert.equal(d.querySelector('.rtm-galeria-conta').textContent, '2 de 4');
  d.querySelector('.rtm-galeria-prox').click();
  assert.match(d.querySelector('.rtm-galeria-grande').src, /c\.jpg$/);
  d.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
  assert.match(d.querySelector('.rtm-galeria-grande').src, /b\.jpg$/);
  d.querySelector('.rtm-galeria-ant').click(); d.querySelector('.rtm-galeria-ant').click();
  assert.match(d.querySelector('.rtm-galeria-grande').src, /d\.jpg$/, 'anterior da primeira volta para a última');
});

test('X fecha e o foco volta para a foto clicada', () => {
  const w = montar(['a.jpg', 'b.jpg']);
  const d = w.document.querySelector('.rtm-galeria-dialogo');
  const f = w.document.querySelectorAll('.rtm-galeria-foto')[0];
  f.click();
  d.querySelector('.rtm-galeria-fechar').click();
  assert.ok(!d.hasAttribute('open'));
  assert.equal(w.document.activeElement, f);
});

test('uma foto só: sem setas', () => {
  const w = montar(['a.jpg']);
  const d = w.document.querySelector('.rtm-galeria-dialogo');
  w.document.querySelector('.rtm-galeria-foto').click();
  assert.ok(d.querySelector('.rtm-galeria-prox').hidden);
  assert.ok(d.querySelector('.rtm-galeria-ant').hidden);
});
