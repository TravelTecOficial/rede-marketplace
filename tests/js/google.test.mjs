import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const codigo = fs.readFileSync(new URL('../../rede-marketplace/assets/google.js', import.meta.url), 'utf8');
const html = `<div class="rtm-nota" data-rtm-google-nota hidden></div><a data-rtm-google-ver hidden href="#avaliacoes">Ver avaliações</a>
<section id="avaliacoes" data-rtm-google="ChIJ1" data-chave="K" hidden><div class="rtm-av-resumo"></div><div class="rtm-avals"></div><a class="rtm-av-link" hidden>Ver todas no Google</a></section>`;

function montar(lugar, falha = false) {
  const { window } = new JSDOM(`<body>${html}</body>`, { runScripts: 'outside-only' });
  window.__pedidos = [];
  window.google = { maps: { importLibrary: async () => ({
    Place: class { constructor(o) { this.id = o.id; } async fetchFields(o) { window.__pedidos.push(o.fields); if (falha) throw new Error('quota'); Object.assign(this, lugar); } },
  }) } };
  window.eval(codigo);
  return window;
}
const espera = () => new Promise((r) => setTimeout(r, 20));

test('preenche nota, resumo e até 5 depoimentos com autor e link do Google', async () => {
  const reviews = Array.from({ length: 7 }, (_, i) => ({ rating: 5, relativePublishTimeDescription: 'há 1 semana', text: 'Ótimo atendimento ' + i, authorAttribution: { displayName: 'Ana ' + i, uri: 'https://maps.google.com/u' + i } }));
  const w = montar({ rating: 4.8, userRatingCount: 132, reviews, googleMapsURI: 'https://maps.google.com/?cid=1' });
  await espera();
  const d = w.document;
  assert.equal(JSON.stringify(w.__pedidos[0]), JSON.stringify(['rating', 'userRatingCount', 'reviews', 'googleMapsURI']));
  assert.equal(d.getElementById('avaliacoes').hidden, false);
  assert.match(d.querySelector('[data-rtm-google-nota]').textContent, /4,8 · 132 avaliações no Google/);
  assert.equal(d.querySelector('[data-rtm-google-nota]').hidden, false);
  assert.equal(d.querySelector('[data-rtm-google-ver]').hidden, false);
  assert.equal(d.querySelectorAll('.rtm-av').length, 5);
  assert.equal(d.querySelector('.rtm-av a').getAttribute('href'), 'https://maps.google.com/u0');
  assert.equal(d.querySelector('.rtm-av-link').getAttribute('href'), 'https://maps.google.com/?cid=1');
  assert.equal(d.querySelector('.rtm-av-link').hidden, false);
});

test('texto do depoimento entra como texto (sem HTML)', async () => {
  const w = montar({ rating: 5, userRatingCount: 1, reviews: [{ rating: 5, text: '<img src=x onerror=alert(1)>', authorAttribution: { displayName: '<b>X</b>' } }] });
  await espera();
  assert.equal(w.document.querySelector('.rtm-av img'), null);
  assert.match(w.document.querySelector('.rtm-av').textContent, /<img src=x/);
});

test('sem avaliações ou com erro do Google: tudo continua escondido', async () => {
  const w1 = montar({ rating: undefined, userRatingCount: 0, reviews: [] });
  const w2 = montar({}, true);
  await espera();
  for (const w of [w1, w2]) {
    assert.equal(w.document.getElementById('avaliacoes').hidden, true);
    assert.equal(w.document.querySelector('[data-rtm-google-nota]').hidden, true);
  }
});
