import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const codigo = fs.readFileSync(new URL('../../rede-marketplace/assets/agente.js', import.meta.url), 'utf8');
const html = '<div class="rtm-dados"><button type="button" class="rtm-cadastur rtm-cad-abrir">Cadastur</button>'
  + '<dialog id="rtm-cad"><div><button type="button" class="rtm-cad-fechar">×</button></div></dialog></div>';

test('selo abre e o X fecha a janela do Cadastur (sem showModal: atributo open)', () => {
  const { window } = new JSDOM(html, { runScripts: 'outside-only' });
  window.HTMLDialogElement.prototype.showModal = undefined;
  window.HTMLDialogElement.prototype.close = undefined;
  window.eval(codigo);
  const d = window.document.getElementById('rtm-cad');
  assert.equal(d.parentNode, window.document.body, 'janela movida para o body');
  window.document.querySelector('.rtm-cad-abrir').click();
  assert.ok(d.hasAttribute('open'));
  d.querySelector('.rtm-cad-fechar').click();
  assert.ok(!d.hasAttribute('open'));
});

test('selo oficial também abre e o foco volta para ele', () => {
  const { window } = new JSDOM(html.replace('Cadastur</button>', 'Cadastur</button><button type="button" class="rtm-cad-selo rtm-cad-abrir">selo</button>'), { runScripts: 'outside-only' });
  window.HTMLDialogElement.prototype.showModal = undefined;
  window.HTMLDialogElement.prototype.close = undefined;
  window.eval(codigo);
  const d = window.document.getElementById('rtm-cad');
  const selo = window.document.querySelector('.rtm-cad-selo');
  selo.click();
  assert.ok(d.hasAttribute('open'));
  d.querySelector('.rtm-cad-fechar').click();
  assert.equal(window.document.activeElement, selo);
});

test('sem selo na página: não faz nada', () => {
  const { window } = new JSDOM('<p>x</p>', { runScripts: 'outside-only' });
  assert.doesNotThrow(() => window.eval(codigo));
});
