/* Comportamento da página de cotação: filtro, seleção de até 3 especialistas, validação e envio.
 * O envio só acontece quando o formulário tem data-envio (chave do Cliente Ideal guardada; no ensaio, só para quem
 * edita o site): POST para o próprio site, que repassa ao Cliente Ideal. Sem data-envio, nada vai pela rede. */
(function () {
  'use strict';

  var MAX = 3;
  var d = document;
  var $ = function (id) { return d.getElementById(id); };

  var campoDestino = $('rtm-destino');
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

  // Destinos com especialista (sugestões do campo): [{ slug, nome }].
  var opcoesDestino = Array.prototype.slice.call(d.querySelectorAll('#rtm-destinos-lista option')).map(function (o) {
    return { slug: o.getAttribute('data-slug') || slugar(o.value), nome: o.value };
  });
  var textoVazio = vazio ? vazio.textContent : '';

  function destinoTexto() { return campoDestino ? String(campoDestino.value || '').trim() : ''; }
  function acharDestino(texto) {
    var s = slugar(texto);
    if (!s) { return null; }
    for (var i = 0; i < opcoesDestino.length; i++) {
      if (opcoesDestino[i].slug === s || slugar(opcoesDestino[i].nome) === s) { return opcoesDestino[i]; }
    }
    return null;
  }
  // Slug do destino digitado, se ele tem especialista; '' se em branco ou sem especialista.
  function destinoAtual() { var o = acharDestino(destinoTexto()); return o ? o.slug : ''; }
  function marcado(c) { return c.getAttribute('aria-pressed') === 'true'; }
  function marcar(c, v) { c.setAttribute('aria-pressed', v ? 'true' : 'false'); }
  function selecionados() { return cards.filter(function (c) { return marcado(c) && !c.hidden; }); }
  // Vindo do perfil do agente ou de uma sugestão (?agente=): a cotação é só desse agente.
  var unico = null;

  function visivel(c) {
    if (unico) { return c === unico; }
    if (destinoTexto()) {
      var dest = (c.getAttribute('data-destinos') || '').split(/\s+/);
      if (dest.indexOf(destinoAtual()) === -1) { return false; } // em branco: todos os especialistas
    }
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
    if (!tituloDestino || unico) { return; }
    var o = acharDestino(destinoTexto());
    tituloDestino.textContent = o ? ' em ' + o.nome : ' nos melhores destinos';
  }

  function filtrar() {
    var algum = false;
    cards.forEach(function (c) {
      var v = visivel(c);
      c.hidden = !v;
      if (!v) { marcar(c, false); } else { algum = true; }
    });
    if (vazio) {
      var texto = destinoTexto();
      vazio.textContent = texto && !destinoAtual()
        ? 'Ainda não temos especialista para ' + texto + '. Marque "a Rede escolhe" e indicamos o melhor para você.'
        : textoVazio;
      vazio.hidden = algum;
    }
    atualizarContagem();
  }

  function alternar(c) {
    if (c.hidden || c === unico) { return; }
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

  if (campoDestino) {
    var aoMudarDestino = function () { atualizarTitulo(); filtrar(); };
    campoDestino.addEventListener('input', aoMudarDestino);
    campoDestino.addEventListener('change', aoMudarDestino);
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
    if (!destinoTexto()) { erros.push('rtm-destino'); }
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
    var formEl = $('rtm-cotacao');
    var url = formEl ? formEl.getAttribute('data-envio') : '';
    if (!url || !window.fetch) {
      mostrarAviso('Ensaio: nada foi enviado. No próximo passo o pedido segue para os especialistas escolhidos.', false);
      return;
    }
    if (enviando) { return; }
    enviando = true;
    if (enviar) { enviar.disabled = true; enviar.textContent = 'Enviando…'; }
    var cab = { 'Content-Type': 'application/json' };
    var nonce = formEl.getAttribute('data-nonce');
    if (nonce) { cab['X-WP-Nonce'] = nonce; }
    window.fetch(url, { method: 'POST', headers: cab, credentials: 'same-origin', body: JSON.stringify(pedido()) })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, j: j || {} }; }); })
      .then(function (res) {
        if (res.ok && res.j.ok) { obrigado(res.j.agentes || []); return; }
        falhou(res.j.msg || 'Não conseguimos enviar agora. Tente de novo em alguns minutos.');
      })
      .catch(function () { falhou('Sem conexão. Confira a internet e tente de novo.'); });
  }

  var enviando = false;
  function mostrarAviso(txt, erro) {
    if (!aviso) { return; }
    aviso.textContent = txt;
    aviso.classList.toggle('rtm-erro', !!erro);
    aviso.hidden = false;
    if (aviso.scrollIntoView) { aviso.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  }
  function falhou(msg) {
    enviando = false;
    if (enviar) { enviar.textContent = 'Pedir cotações'; }
    atualizarContagem();
    mostrarAviso(msg, true);
  }
  function obrigado(nomes) {
    var raiz = d.querySelector('.rtm-cotacao');
    if (!raiz) { return; }
    var box = d.createElement('div');
    box.className = 'rtm-obrigado';
    box.setAttribute('role', 'status');
    var h2 = d.createElement('h2');
    h2.textContent = 'Pedido enviado!';
    var p = d.createElement('p');
    var quem = nomes.length ? nomes.join(', ').replace(/, ([^,]*)$/, ' e $1') : 'Os especialistas';
    p.textContent = quem + (nomes.length === 1 ? ' vai' : ' vão') + ' falar com você pelo WhatsApp ou e-mail para montar a sua cotação.';
    box.appendChild(h2);
    box.appendChild(p);
    raiz.appendChild(box);
    raiz.classList.add('rtm-enviado');
    if (box.scrollIntoView) { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  }

  // Dados do pedido para o servidor (que repassa ao Cliente Ideal).
  function pressionados(id, attr) {
    return pilulas(id).filter(function (p) { return p.getAttribute('aria-pressed') === 'true'; }).map(function (p) { return p.getAttribute(attr) || p.textContent.trim(); });
  }
  function datasTexto() {
    var v = (pressionados('rtm-datas', 'data-v')[0]) || '';
    if (v === 'certas') {
      var ida = valor('rtm-ida'), volta = valor('rtm-volta');
      return ida || volta ? ('Ida ' + (ida || '?') + ' · volta ' + (volta || '?')) : 'Datas a definir';
    }
    if (v === 'mes') { return valor('rtm-mes') ? 'Mês: ' + valor('rtm-mes') : 'Só sabe o mês'; }
    return 'Ainda não sabe as datas';
  }
  function pessoasTexto() {
    var partes = [valor('rtm-adultos') + ' adulto(s)'];
    if (valor('rtm-criancas') && valor('rtm-criancas') !== '0') { partes.push(valor('rtm-criancas') + ' criança(s)'); }
    if (valor('rtm-bebes') && valor('rtm-bebes') !== '0') { partes.push(valor('rtm-bebes') + ' bebê(s)'); }
    return partes.join(', ');
  }
  function utm() {
    var res = {};
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'].forEach(function (k) {
      var v = params ? params.get(k) : '';
      if (v) { res[k] = v; }
    });
    return res;
  }
  function pedido() {
    var lgpd = $('rtm-lgpd');
    return {
      agentes: selecionados().map(function (c) { return c.getAttribute('data-slug'); }),
      rede: !!(rede && rede.checked),
      destino: destinoTexto(),
      destino_slug: destinoAtual(),
      tipo: tipo,
      saida: valor('rtm-origem'),
      datas: datasTexto(),
      pessoas: pessoasTexto(),
      inclui: pressionados('rtm-inclui', 'data-item').join(', '),
      orcamento: valor('rtm-orcamento'),
      mensagem: valor('rtm-obs'),
      horario: valor('rtm-horario'),
      nome: valor('rtm-nome'),
      telefone: valor('rtm-whats'),
      email: valor('rtm-email'),
      lgpd: !!(lgpd && lgpd.checked),
      sugestao: sugAtual,
      origem_url: String(window.location.href).split('#')[0],
      utm: utm(),
      site: valor('rtm-site')
    };
  }

  if (enviar) { enviar.addEventListener('click', aoEnviar); }
  var form = $('rtm-cotacao');
  if (form) { form.addEventListener('submit', function (e) { e.preventDefault(); aoEnviar(); }); }

  function estado() {
    return {
      destino: destinoAtual(),
      destino_texto: destinoTexto(),
      tipo: tipo,
      agentes: selecionados().map(function (c) { return c.getAttribute('data-slug'); }),
      rede: !!(rede && rede.checked),
      sugestao: sugAtual
    };
  }

  // Início: destino, agente e sugestão vindos da URL.
  var params = null;
  try { params = new URLSearchParams(window.location.search); } catch (e) { params = null; }
  var sugestoes = {};
  try { sugestoes = JSON.parse(($('rtm-sugestoes-dados') || {}).textContent || '{}') || {}; } catch (e) { sugestoes = {}; }
  var pedidaSug = params ? String(params.get('sugestao') || '') : '';
  var sug = Object.prototype.hasOwnProperty.call(sugestoes, pedidaSug) ? sugestoes[pedidaSug] : null;
  var sugAtual = sug ? pedidaSug : '';
  var avisoSug = $('rtm-sugestao-aviso');
  if (sug && avisoSug) {
    $('rtm-sugestao-texto').textContent = 'Sugestão escolhida: ' + sug.titulo + ' (' + sug.agente_nome + ')';
    avisoSug.hidden = false;
  }
  var tirarSug = $('rtm-sugestao-tirar');
  if (tirarSug) {
    tirarSug.addEventListener('click', function () { sugAtual = ''; if (avisoSug) { avisoSug.hidden = true; } });
  }
  if (campoDestino) {
    var raiz = d.querySelector('.rtm-cotacao');
    var achado = acharDestino(params ? params.get('destino') : '') || acharDestino(raiz ? raiz.getAttribute('data-destino') : '') || acharDestino(sug ? sug.destino : '');
    if (achado) { campoDestino.value = achado.nome; }
  }
  atualizarTitulo();
  filtrar();
  var ag = sug ? sug.agente : (params ? params.get('agente') : ''); // com sugestão válida, vale o dono dela
  if (ag) {
    cards.forEach(function (c) {
      if (c.getAttribute('data-slug') === ag) { unico = c; }
    });
    if (unico) {
      var nomeEl = unico.querySelector('.rtm-card-nome') || unico.querySelector('h3');
      var nomeAg = nomeEl ? nomeEl.textContent.trim() : '';
      var h = d.querySelector('.rtm-titulo') || (tituloDestino && tituloDestino.parentNode);
      if (h) { h.textContent = 'Receba sua cotação de ' + nomeAg; }
      var dica = d.querySelector('.rtm-etapa[data-etapa="2"] .rtm-etapa-dica');
      if (dica) { dica.textContent = nomeAg + ' vai preparar a sua cotação.'; }
      var raizC = d.querySelector('.rtm-cotacao');
      if (raizC) { raizC.classList.add('rtm-cotacao-um'); }
      if (rede) {
        rede.checked = false;
        var rotRede = rede.closest('label');
        if (rotRede) { rotRede.hidden = true; }
      }
      filtrar();
      marcar(unico, true);
      atualizarContagem();
    }
  }

  window.RTMCotacao = { estado: estado, validar: validar, pedido: pedido };
})();
