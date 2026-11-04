import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const codigo = fs.readFileSync(new URL('../../rede-marketplace/assets/carrossel.js', import.meta.url), 'utf8');
const html = `<section class="rtm-carrossel" aria-roledescription="carrossel"><button type="button" class="rtm-car-ant">‹</button><div class="rtm-car-trilho">${'<article class="rtm-car-card"></article>'.repeat(4)}</div><button type="button" class="rtm-car-prox">›</button></section>`;

function montar(reduzido = false) {
  const { window } = new JSDOM(`<body>${html}</body>`, { runScripts: 'outside-only', pretendToBeVisual: true });
  window.__rolagens = [];
  window.matchMedia = () => ({ matches: reduzido, addEventListener() {} });
  const t = window.document.querySelector('.rtm-car-trilho');
  Object.defineProperty(t, 'clientWidth', { value: 600 });
  Object.defineProperty(t, 'scrollWidth', { value: 1200 });
  t.scrollBy = (o) => { window.__rolagens.push(o.left); t.scrollLeft += o.left; };
  t.scrollTo = (o) => { window.__rolagens.push('inicio'); t.scrollLeft = o.left; };
  window.document.querySelectorAll('.rtm-car-card').forEach((c) => Object.defineProperty(c, 'offsetWidth', { value: 280 }));
  window.__intervalos = [];
  window.setInterval = (fn, ms) => { window.__intervalos.push({ fn, ms }); return window.__intervalos.length; };
  window.clearInterval = () => {};
  window.eval(codigo);
  return window;
}

test('setas passam um cartão para cada lado', () => {
  const w = montar();
  w.document.querySelector('.rtm-car-prox').click();
  w.document.querySelector('.rtm-car-ant').click();
  assert.deepEqual([...w.__rolagens], [296, -296]);
});

test('passa sozinho a cada 4 s e volta ao começo no fim', () => {
  const w = montar();
  assert.equal(w.__intervalos[0].ms, 4000);
  const t = w.document.querySelector('.rtm-car-trilho');
  w.__intervalos[0].fn();
  assert.equal(w.__rolagens[0], 296);
  t.scrollLeft = 600; // no fim (600 + 600 >= 1200)
  w.__intervalos[0].fn();
  assert.equal(w.__rolagens[1], 'inicio');
});

test('pausa com o mouse em cima', () => {
  const w = montar();
  const c = w.document.querySelector('.rtm-carrossel');
  c.dispatchEvent(new w.Event('mouseenter'));
  w.__intervalos[0].fn();
  assert.equal(w.__rolagens.length, 0);
  c.dispatchEvent(new w.Event('mouseleave'));
  w.__intervalos[0].fn();
  assert.equal(w.__rolagens.length, 1);
});

test('movimento reduzido: não passa sozinho', () => {
  const w = montar(true);
  assert.equal(w.__intervalos.length, 0);
});
