/* Comportamento da página de cotação: filtro, seleção de até 3 especialistas e validação.
 * Ensaio: nada é enviado pela rede (sem fetch, XMLHttpRequest, submit ou sendBeacon). */
(function () {
  'use strict';

  var MAX = 3;
  var d = document;
  var $ = function (id) { return d.getElementById(id); };

  var selDestino = $('rtm-destino');
  var lista = $('rtm-lista');
  if (!lista) { return; }

  var rede = $('rtm-rede');
  var vazio = $('rtm-vazio');
  var contagem = $('rtm-contagem');
  var enviar = $('rtm-enviar');
  var aviso = $('rtm-aviso-ensaio');
  var tituloDestino = $('rtm-titulo-destino');
  var cards = Array.prototype.slice.call(lista.querySelectorAll('.rtm-card'));
  var tipo = '';

  function slugar(s) {
    var t = String(s || '').toLowerCase();
    if (t.normalize) { t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
    return t.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  function destinoAtual() { return selDestino ? selDestino.value : ''; }
  function marcado(c) { return c.getAttribute('aria-pressed') === 'true'; }
  function marcar(c, v) { c.setAttribute('aria-pressed', v ? 'true' : 'false'); }
  function selecionados() { return cards.filter(function (c) { return marcado(c) && !c.hidden; }); }

  function visivel(c) {
    var dest = (c.getAttribute('data-destinos') || '').split(/\s+/);
    if (dest.indexOf(destinoAtual()) === -1) { return false; }
    if (!tipo) { return true; }
    return (c.getAttribute('data-tipos') || '').split('|').indexOf(tipo) !== -1;
  }

  function atualizarContagem() {
    var n = selecionados().length;
    if (contagem) {
      if (rede && rede.checked) {
        contagem.textContent = 'A Rede Turística escolhe por você · até 3';
      } else if (n === 0) {
        contagem.textContent = 'Nenhum especialista escolhido ';
        var em = d.createElement('em');
        em.textContent = '· até 3';
        contagem.appendChild(em);
      } else {
        contagem.textContent = n + (n === 1 ? ' especialista · 1 cotação' : ' especialistas · ' + n + ' cotações');
      }
    }
    if (enviar) { enviar.disabled = !(n > 0 || (rede && rede.checked)); }
  }

  function atualizarTitulo() {
    if (!tituloDestino) { return; }
    var o = selDestino && selDestino.selectedIndex >= 0 ? selDestino.options[selDestino.selectedIndex] : null;
    tituloDestino.textContent = o ? ' em ' + o.text : '';
  }

  function filtrar() {
    var algum = false;
    cards.forEach(function (c) {
      var v = visivel(c);
      c.hidden = !v;
      if (!v) { marcar(c, false); } else { algum = true; }
    });
    if (vazio) { vazio.hidden = algum; }
    atualizarContagem();
  }

  function alternar(c) {
    if (c.hidden) { return; }
    if (marcado(c)) {
      marcar(c, false);
    } else {
      if (selecionados().length >= MAX) { return; }
      marcar(c, true);
      if (rede) { rede.checked = false; }
    }
    atualizarContagem();
  }

  lista.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('a')) { return; }
    var c = e.target.closest ? e.target.closest('.rtm-card') : null;
    if (c && lista.contains(c)) { alternar(c); }
  });
  lista.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar') { return; }
    if (!e.target.classList || !e.target.classList.contains('rtm-card')) { return; }
    e.preventDefault();
    alternar(e.target);
  });

  if (rede) {
    rede.addEventListener('change', function () {
      if (rede.checked) { cards.forEach(function (c) { marcar(c, false); }); }
      atualizarContagem();
    });
  }

  if (selDestino) {
    selDestino.addEventListener('change', function () { atualizarTitulo(); filtrar(); });
  }

  // Pílulas: tipo (uma só, clicar de novo limpa), datas (uma só) e itens (várias).
  function pilulas(id) {
    var box = $(id);
    return box ? Array.prototype.slice.call(box.querySelectorAll('button.rtm-pilula')) : [];
  }
  function ligar(id, aoClicar) {
    var todas = pilulas(id);
    todas.forEach(function (p) {
      p.addEventListener('click', function () { aoClicar(p, todas); });
    });
  }
  function pressionar(p, v) { p.setAttribute('aria-pressed', v ? 'true' : 'false'); }

  ligar('rtm-tipos', function (p, todas) {
    var ligado = p.getAttribute('aria-pressed') === 'true';
    todas.forEach(function (o) { pressionar(o, false); });
    tipo = ligado ? '' : p.getAttribute('data-tipo');
    pressionar(p, !ligado);
    filtrar();
  });

  function rotulo(id) {
    var el = $(id);
    return el ? (el.closest('label') || el) : null;
  }
  function mostrarDatas(v) {
    var mapa = { 'rtm-ida': v === 'certas', 'rtm-volta': v === 'certas', 'rtm-mes': v === 'mes' };
    Object.keys(mapa).forEach(function (id) {
      var r = rotulo(id);
      if (r) { r.hidden = !mapa[id]; }
    });
  }
  ligar('rtm-datas', function (p, todas) {
    todas.forEach(function (o) { pressionar(o, o === p); });
    mostrarDatas(p.getAttribute('data-v'));
  });
  var datasAtiva = pilulas('rtm-datas').filter(function (p) { return p.getAttribute('aria-pressed') === 'true'; })[0];
  if (datasAtiva) { mostrarDatas(datasAtiva.getAttribute('data-v')); }

  ligar('rtm-inclui', function (p) { pressionar(p, p.getAttribute('aria-pressed') !== 'true'); });

  // Validação.
  function valor(id) { var el = $(id); return el ? String(el.value || '').trim() : ''; }
  function validar() {
    var erros = [];
    var algumAgente = selecionados().length > 0 || (rede && rede.checked);
    if (!destinoAtual()) { erros.push('rtm-destino'); }
    if (!algumAgente) { erros.push('rtm-lista'); }
    if (!valor('rtm-adultos')) { erros.push('rtm-adultos'); }
    if (!valor('rtm-nome')) { erros.push('rtm-nome'); }
    var tel = valor('rtm-whats').replace(/\D+/g, '');
    if (tel.length < 10 || tel.length > 11) { erros.push('rtm-whats'); }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor('rtm-email'))) { erros.push('rtm-email'); }
    var lgpd = $('rtm-lgpd');
    if (!lgpd || !lgpd.checked) { erros.push('rtm-lgpd'); }

    ['rtm-destino', 'rtm-lista', 'rtm-adultos', 'rtm-nome', 'rtm-whats', 'rtm-email', 'rtm-lgpd'].forEach(function (id) {
      var el = $(id);
      if (!el) { return; }
      if (erros.indexOf(id) !== -1) { el.setAttribute('aria-invalid', 'true'); } else { el.removeAttribute('aria-invalid'); }
    });
    return erros;
  }

  function aoEnviar() {
    var erros = validar();
    if (erros.length) {
      if (aviso) { aviso.hidden = true; }
      var primeiro = $(erros[0]);
      if (primeiro) {
        if (primeiro.scrollIntoView) { primeiro.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        if (primeiro.focus && erros[0] !== 'rtm-lista') { primeiro.focus(); }
      }
      return;
    }
    if (aviso) {
      aviso.textContent = 'Ensaio: nada foi enviado. No próximo passo o pedido segue para os especialistas escolhidos.';
      aviso.hidden = false;
      if (aviso.scrollIntoView) { aviso.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    }
  }

  if (enviar) { enviar.addEventListener('click', aoEnviar); }
  var form = $('rtm-cotacao');
  if (form) { form.addEventListener('submit', function (e) { e.preventDefault(); aoEnviar(); }); }

  function estado() {
    return {
      destino: destinoAtual(),
      tipo: tipo,
      agentes: selecionados().map(function (c) { return c.getAttribute('data-slug'); }),
      rede: !!(rede && rede.checked)
    };
  }

  // Início: destino e agente vindos da URL.
  var params = null;
  try { params = new URLSearchParams(window.location.search); } catch (e) { params = null; }
  if (selDestino && selDestino.options.length) {
    var raiz = document.querySelector('.rtm-cotacao');
    var pedido = slugar(params ? params.get('destino') : '') || slugar(raiz ? raiz.getAttribute('data-destino') : '');
    var achou = false;
    for (var i = 0; i < selDestino.options.length; i++) {
      if (selDestino.options[i].value === pedido) { selDestino.selectedIndex = i; achou = true; break; }
    }
    if (!achou) { selDestino.selectedIndex = 0; }
  }
  atualizarTitulo();
  filtrar();
  var ag = params ? params.get('agente') : '';
  if (ag) {
    cards.forEach(function (c) {
      if (c.getAttribute('data-slug') === ag && !c.hidden) { marcar(c, true); }
    });
    atualizarContagem();
  }

  window.RTMCotacao = { estado: estado, validar: validar };
})();
