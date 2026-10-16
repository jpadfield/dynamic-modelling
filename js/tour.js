/*
 * tour.js - guided tours for the Dynamic Modeller
 * ------------------------------------------------
 * Adds a step-by-step walkthrough to a rendered model. Each step highlights a set of
 * nodes (and every link between them), dims the rest, zooms to them and shows a caption.
 *
 * Tours are written as comment lines in the model's TSV, so they travel with the model
 * (including in bookmark links). The renderer ignores them.
 *
 *   //tour Title of the tour                      (optional, one line)
 *   //step Step title | Node A ; Node B ; Node C | Caption text for this step.
 *   //sub  Part title | Node A ; Node B | Caption for a closer look at part of the step.
 *   //step Overview | | A step with no nodes shows the whole model.
 *
 * - Node names must match the node labels exactly (as written in the TSV).
 * - Separate node names with semicolons. Captions may contain commas, but when the page is
 *   refreshed the server keeps only the text up to the third comma in a line.
 * - Links are highlighted automatically when both of their nodes are listed.
 *
 * Parts of a step (sub-steps)
 * - //sub lines belong to the //step above them and are shown, in order, after the view of
 *   the whole step. They are for steps that cover a spread-out area: each part zooms in on
 *   some of the step's nodes so they can be read. The highlighting stays the same as for
 *   the whole step, so the wider connections can still be seen.
 * - The part title and caption are optional. If they are left empty, the step's title and
 *   caption stay on screen and the caption is not read out again.
 * - To help when writing a tour: if a step without //sub lines would have text too small to
 *   read in the current window, a note is written to the browser console.
 *
 * Playing
 * - Back and Forward move through steps and their parts in order. Hold Shift while
 *   clicking them (or with the arrow keys) to jump a whole step.
 * - Back and Forward keep the tour playing if it is playing, and paused if it is paused.
 * - Play after a pause starts the current part again from the beginning.
 * - Moving or zooming the model while the tour plays holds it: the caption is finished
 *   but the tour waits. Resume then carries on with the next part.
 * - Re-centre goes back to the view of the current part without moving on.
 *
 * Keys: Right/Left = forward/back, Shift+Right/Left = next/previous step,
 *       Space or P = play, pause or resume, Home = restart, C = re-centre,
 *       H = hide/show the controls, Esc = close the tour.
 *
 * Requires: the page's Mermaid SVG inside #modelDiv, the #triplesTxt textarea (hidden on
 * the simple page), the svg-pan-zoom instance in window.panZoom (both from local.js) and
 * Font Awesome 5 for the icons (loaded by both versions of the page).
 */
(function () {
  'use strict';

  var SELECTORS = {
    source: '#triplesTxt',
    svg: '#modelDiv svg',
    viewport: '.svg-pan-zoom_viewport',
    node: 'g.node',
    link: 'path.flowchart-link',
    linkLabel: 'g.edgeLabel',
    cluster: 'g.cluster'
  };
  var DIM = '0.12', CLUSTER_DIM = '0.35', ZOOM_MS = 900;
  var MIN_TEXT_PX = 11;     // node text smaller than this on screen is noted in the console as hard to read
  var MAX_TEXT_PX = 30;     // never zoom in further than this text size
  var FIT_W = 0.85, FIT_H = 0.8; // share of the visible area a highlighted section may fill

  // ------------------------------------------------------------------ parsing
  function parseTour(text) {
    var tour = { title: '', steps: [] };
    (text || '').split(/\r?\n/).forEach(function (line) {
      var m;
      // When the page is refreshed, the server turns ", " into a tab; put the commas back.
      line = line.replace(/\t+$/, '').replace(/\t/g, ', ');
      if ((m = line.match(/^\/\/tour\s+(.*)$/i))) { tour.title = m[1].trim(); return; }
      if ((m = line.match(/^\/\/step\s+(.*)$/i))) {
        var step = parsePart(m[1]); step.subs = [];
        tour.steps.push(step);
        return;
      }
      // "//sub" must be followed by a space or "|", so "//subgraph" lines are never read as parts.
      if ((m = line.match(/^\/\/sub(?:\s+|(?=\|))(.*)$/i))) {
        var last = tour.steps[tour.steps.length - 1];
        if (last) { last.subs.push(parsePart(m[1])); }
        else if (window.console) { console.warn('tour.js: a //sub line comes before any //step and was ignored'); }
      }
    });
    return tour;
  }
  function parsePart(s) {
    var parts = s.split('|');
    return {
      title: (parts[0] || '').trim(),
      nodes: (parts[1] || '').split(';').map(norm).filter(Boolean),
      caption: parts.slice(2).join('|').trim()
    };
  }
  function norm(s) { return (s || '').replace(/\s+/g, ' ').trim(); }
  function sourceText() {
    var el = document.querySelector(SELECTORS.source);
    return el ? (el.value || el.textContent || '') : '';
  }

  // ------------------------------------------------------------------ start-up
  // Wait for the model to be drawn and for svg-pan-zoom to be ready, then read the
  // tour from the TSV. If the model is redrawn later, the tour is rebuilt for it.
  var current = null, pending = null;

  function whenReady(cb, tries) {
    tries = tries || 0;
    var svg = document.querySelector(SELECTORS.svg);
    if (svg && svg.querySelector(SELECTORS.node) && window.panZoom) { cb(svg); return; }
    if (tries > 100) { return; } // give up after about 20 seconds
    pending = setTimeout(function () { whenReady(cb, tries + 1); }, 200);
  }

  function start() {
    clearTimeout(pending);
    whenReady(function (svg) {
      if (current && current.svg === svg) { return; }
      if (current) { current.remove(); current = null; }
      var tour = parseTour(sourceText());
      if (tour.steps.length) { current = build(svg, tour); }
    });
  }

  function init() {
    start();
    var div = document.querySelector('#modelDiv');
    if (div && window.MutationObserver) {
      new MutationObserver(function () { setTimeout(start, 300); }).observe(div, { childList: true });
    }
  }

  // ------------------------------------------------------------------ the tour
  function build(svg, tour) {
    var nodeEls = [].slice.call(svg.querySelectorAll(SELECTORS.node));
    var links = [].slice.call(svg.querySelectorAll(SELECTORS.link));
    var linkLabels = [].slice.call(svg.querySelectorAll(SELECTORS.linkLabel));
    var clusters = [].slice.call(svg.querySelectorAll(SELECTORS.cluster));
    var everything = nodeEls.concat(links, linkLabels, clusters);

    var byLabel = {}, byKey = {};
    nodeEls.forEach(function (n) {
      byLabel[norm(n.textContent)] = n;
      var k = (n.id.match(/flowchart-(.+)-\d+$/) || [])[1];
      if (k) { byKey[k] = n; }
    });
    // Each link id ends L_<from>_<to>_<n>; link labels are in the same order as links.
    var linkInfo = links.map(function (p, i) {
      var m = p.id.match(/L_(.+?)_(.+?)_\d+$/);
      return { path: p, label: linkLabels[i], from: m && byKey[m[1]], to: m && byKey[m[2]] };
    });

    // Match the node names in the tour to the drawn nodes once, warning about any that are missing.
    function resolve(names) {
      var out = [];
      names.forEach(function (name) {
        var n = byLabel[name];
        if (n) { if (out.indexOf(n) < 0) { out.push(n); } }
        else if (window.console) { console.warn('tour.js: no node labelled "' + name + '"'); }
      });
      return out;
    }
    // Each step's parts: the whole step first, then its //sub parts.
    tour.steps.forEach(function (s) {
      s.els = resolve(s.nodes);
      s.frames = [{ kind: 'main', step: s, els: s.els }];
      s.subs.forEach(function (u) {
        u.els = resolve(u.nodes);
        s.frames.push({ kind: 'sub', step: s, sub: u, els: u.els.length ? u.els : s.els });
      });
    });

    // ---- styles and panel
    var css = document.createElement('style');
    css.textContent = [
      // The panel leaves the bottom-right corner free for the svg-pan-zoom buttons.
      '#dmt-panel{position:fixed;left:16px;right:88px;bottom:24px;max-width:900px;margin:0 auto;box-sizing:border-box;background:rgba(20,24,32,.92);color:#fff;border-radius:12px;padding:16px 20px 12px;font:18px/1.45 system-ui,sans-serif;z-index:99999;box-shadow:0 8px 30px rgba(0,0,0,.35)}',
      '#dmt-title{font-weight:700;font-size:14px;letter-spacing:.05em;text-transform:uppercase;color:#ffb37a;margin-bottom:4px;padding-right:150px;min-height:1.45em}',
      '#dmt-text{min-height:3em}',
      '#dmt-note{font-size:14px;color:#ffb37a;margin-top:6px}',
      '#dmt-note:empty{display:none}',
      '#dmt-icons{position:absolute;top:10px;right:12px;display:flex;gap:4px}',
      '#dmt-icons button{background:transparent;color:#ccd;border:0;border-radius:6px;width:30px;height:28px;padding:0;cursor:pointer;font-size:15px}',
      '#dmt-icons button:hover{background:#455069;color:#fff}',
      '#dmt-icons .dmt-gap{width:14px}',
      '#dmt-ctl{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;font-size:14px;color:#ccd}',
      '#dmt-ctl button,#dmt-launch{background:#2f3747;color:#fff;border:0;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:15px}',
      '#dmt-ctl button:hover,#dmt-launch:hover{background:#455069}',
      '#dmt-ctl #dmt-play{min-width:6.5em}',
      '#dmt-ctl .dmt-sp{flex:1}',
      '#dmt-ctl select{background:#2f3747;color:#fff;border:0;border-radius:6px;padding:3px}',
      '#dmt-ctl label.dmt-off{opacity:.45;cursor:not-allowed}',
      // The closed tour sits bottom left, clear of the svg-pan-zoom buttons.
      '#dmt-launch{position:fixed;left:24px;bottom:24px;z-index:99999;padding:8px 14px;box-shadow:0 4px 16px rgba(0,0,0,.3)}',
      '.dmt-node-hi rect,.dmt-node-hi polygon,.dmt-node-hi circle,.dmt-node-hi path{stroke:#e8590c!important;stroke-width:4px!important}',
      'path.dmt-link-hi{stroke:#e8590c!important;stroke-width:3px!important}'
    ].join('\n');
    document.head.appendChild(css);

    function iconButton(id, icon, label) {
      return '<button id="' + id + '" title="' + label + '" aria-label="' + label + '"><i class="fas ' + icon + '" aria-hidden="true"></i></button>';
    }
    var panel = document.createElement('div');
    panel.id = 'dmt-panel';
    panel.innerHTML =
      '<div id="dmt-icons">' +
      iconButton('dmt-recentre', 'fa-crosshairs', 'Re-centre on this step (C)') +
      iconButton('dmt-restart', 'fa-undo-alt', 'Restart the tour (Home)') +
      '<span class="dmt-gap"></span>' +
      iconButton('dmt-eye', 'fa-eye', 'Hide controls (H)') +
      iconButton('dmt-close', 'fa-times', 'Close the tour (Esc)') +
      '</div>' +
      '<div id="dmt-title"></div><div id="dmt-text"></div><div id="dmt-note"></div>' +
      '<div id="dmt-ctl">' +
      '<button id="dmt-prev" title="Back (Left arrow). Shift+click or Shift+Left: previous step">&#9664;</button>' +
      '<button id="dmt-play" title="Play, pause or resume (Space or P)">&#9654; Play</button>' +
      '<button id="dmt-next" title="Forward (Right arrow). Shift+click or Shift+Right: next step">&#9654;</button>' +
      '<span id="dmt-count"></span><span class="dmt-sp"></span>' +
      '<label>Speed <select id="dmt-speed"><option value="0.75">0.75x</option><option value="1" selected>1x</option><option value="1.25">1.25x</option><option value="1.5">1.5x</option></select></label>' +
      '<label id="dmt-voice-label"><input type="checkbox" id="dmt-voice"> Voice</label>' +
      '</div>';
    document.body.appendChild(panel);

    var launch = document.createElement('button');
    launch.id = 'dmt-launch';
    launch.innerHTML = '&#9654; ' + (tour.title ? 'Tour: ' + escapeHtml(tour.title) : 'Start tour');
    document.body.appendChild(launch);

    function $(id) { return document.getElementById(id); }

    // ---- state
    // pos is the step (i) and the part of that step (j, where 0 is the whole step) on screen.
    // token changes whenever something new is shown or the tour is paused, so that timers and
    // speech from earlier parts can tell they are out of date and do nothing.
    var pos = null, playing = false, held = false, ended = false, timer = null, token = 0, anim = 0;

    // ---- geometry
    function viewportEl() { return svg.querySelector(SELECTORS.viewport); }

    // Bounding box of some elements in the model's own units (unchanged by zooming).
    function modelBox(els) {
      var vp = viewportEl(), m = vp && vp.getScreenCTM && vp.getScreenCTM();
      if (!m || !m.a) { return null; }
      var b = null;
      els.forEach(function (e) {
        var r = e.getBoundingClientRect(); if (!r.width && !r.height) { return; }
        var x0 = (r.left - m.e) / m.a, y0 = (r.top - m.f) / m.d, x1 = (r.right - m.e) / m.a, y1 = (r.bottom - m.f) / m.d;
        b = b ? { x0: Math.min(b.x0, x0), y0: Math.min(b.y0, y0), x1: Math.max(b.x1, x1), y1: Math.max(b.y1, y1) }
              : { x0: x0, y0: y0, x1: x1, y1: y1 };
      });
      return b;
    }

    // The part of the window where the model can be seen, above the caption panel.
    function visibleArea() {
      var sr = svg.getBoundingClientRect();
      var left = Math.max(0, sr.left), right = Math.min(window.innerWidth, sr.right);
      var top = Math.max(0, sr.top), bottom = Math.min(window.innerHeight, sr.bottom);
      if (panel.style.display !== 'none') { bottom = Math.min(bottom, panel.getBoundingClientRect().top - 10); }
      return { left: left, top: top, W: Math.max(100, right - left), H: Math.max(100, bottom - top) };
    }

    // Size of the node text in the model's own units, so that text size on screen = this x zoom.
    var textUnits = null;
    function textSize() {
      if (textUnits) { return textUnits; }
      var vp = viewportEl(), vm = vp && vp.getScreenCTM && vp.getScreenCTM();
      for (var i = 0; i < nodeEls.length && i < 20; i++) {
        var lab = nodeEls[i].querySelector('.nodeLabel') || nodeEls[i].querySelector('foreignObject div') || nodeEls[i].querySelector('text');
        if (!lab) { continue; }
        var px = parseFloat(window.getComputedStyle(lab).fontSize);
        if (!px) { continue; }
        var holder = lab.closest ? lab.closest('foreignObject, text') : null;
        var hm = holder && holder.getScreenCTM ? holder.getScreenCTM() : null;
        textUnits = (hm && vm && vm.a) ? px * hm.a / vm.a : px;
        return textUnits;
      }
      return 16;
    }

    // The zoom (screen pixels per model unit) and centre that would show these elements.
    function viewFor(els) {
      var area = visibleArea(), b = modelBox(els);
      if (!b) { return null; }
      var bw = Math.max(1, b.x1 - b.x0), bh = Math.max(1, b.y1 - b.y0);
      var Z = Math.min(area.W * FIT_W / bw, area.H * FIT_H / bh, MAX_TEXT_PX / textSize());
      return { Z: Z, cx: (b.x0 + b.x1) / 2, cy: (b.y0 + b.y1) / 2, tx: area.left + area.W / 2, ty: area.top + area.H / 2 };
    }

    // A target for the camera: zoom Z (in svg-pan-zoom's terms), the model point (cx, cy) to show,
    // and where it should appear (sx, sy, in the SVG's own coordinates). svg-pan-zoom's coordinates
    // normally match screen pixels, but not if the SVG has kept a viewBox (for example when
    // svg-pan-zoom was started before Mermaid had finished drawing), so the SVG's own scale is
    // allowed for. The zoom is applied briefly to let svg-pan-zoom apply its limits, then put back.
    function targetFor(v) {
      var pz = window.panZoom; if (!pz || !v) { return null; }
      var sm = svg.getScreenCTM(); if (!sm || !sm.a) { return null; }
      var z0 = pz.getZoom(), p0 = pz.getPan();
      var base = pz.getSizes().realZoom / z0;
      pz.zoom(v.Z / sm.a / base);
      var Z = pz.getSizes().realZoom;
      pz.zoom(z0); pz.pan(p0);
      return { Z: Z, cx: v.cx, cy: v.cy, sx: (v.tx - sm.e) / sm.a, sy: (v.ty - sm.f) / sm.d };
    }
    // The whole model, as svg-pan-zoom's own "fit" shows it (used when the tour is closed).
    function fitTarget() {
      var pz = window.panZoom; if (!pz) { return null; }
      var sm = svg.getScreenCTM(), sr = svg.getBoundingClientRect(); if (!sm || !sm.a) { return null; }
      var z0 = pz.getZoom(), p0 = pz.getPan();
      pz.fit(); pz.center();
      var Z = pz.getSizes().realZoom, p = pz.getPan();
      pz.zoom(z0); pz.pan(p0);
      var sx = (sr.left + sr.width / 2 - sm.e) / sm.a, sy = (sr.top + sr.height / 2 - sm.f) / sm.d;
      return { Z: Z, cx: (sx - p.x) / Z, cy: (sy - p.y) / Z, sx: sx, sy: sy };
    }

    // ---- the camera (animated, using svg-pan-zoom)
    // The zoom changes smoothly by a constant factor each moment. The point being looked at moves
    // so that its distance from the target, as seen on screen, shrinks steadily: the camera heads
    // straight for the target instead of swinging away during large zooms.
    function animateTo(t) {
      var pz = window.panZoom; if (!pz || !t) { return; }
      var my = ++anim, t0 = performance.now();
      var Z0 = pz.getSizes().realZoom, base = Z0 / pz.getZoom(), p0 = pz.getPan();
      var c0x = (t.sx - p0.x) / Z0, c0y = (t.sy - p0.y) / Z0; // model point now at the target position
      (function frame(now) {
        if (my !== anim) { return; } // a newer move, or the user, has taken over
        var k = Math.min(1, (now - t0) / ZOOM_MS);
        k = k < 0.5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2;
        var Z = Z0 * Math.pow(t.Z / Z0, k);
        var u = 1 - (1 - k) * Math.min(1, Z0 / Z);
        pz.zoom(Z / base);
        var Za = pz.getSizes().realZoom;
        var cx = c0x + (t.cx - c0x) * u, cy = c0y + (t.cy - c0y) * u;
        pz.pan({ x: t.sx - Za * cx, y: t.sy - Za * cy });
        if (k < 1) { requestAnimationFrame(frame); }
      })(t0);
    }
    function wholeModel() { return nodeEls.concat(clusters); }
    function focusEls(els) { animateTo(targetFor(viewFor(els.length ? els : wholeModel()))); }

    // A note for tour authors: a step without parts whose text would be too small to read here.
    function noteSmallText(i) {
      var s = tour.steps[i];
      if (s.noted || s.subs.length || s.els.length < 2 || !window.console) { return; }
      var v = viewFor(s.els); if (!v) { return; }
      var px = v.Z * textSize();
      if (px < MIN_TEXT_PX) {
        s.noted = true;
        console.info('tour.js: step ' + (i + 1) + ' ("' + s.title + '") shows its text at about ' + px.toFixed(1) +
          'px in this window. Consider adding //sub lines to zoom in on parts of it.');
      }
    }

    // ---- highlighting (the same for a step and all of its parts)
    function elementsFor(nodes) {
      var set = new Set(nodes);
      linkInfo.forEach(function (l) {
        if (l.from && l.to && set.has(l.from) && set.has(l.to)) { set.add(l.path); if (l.label) { set.add(l.label); } }
      });
      return set;
    }
    function render(f) {
      // A part normally names nodes from its step; any others it names are lit as well.
      var nodes = f.step.els.slice();
      f.els.forEach(function (n) { if (nodes.indexOf(n) < 0) { nodes.push(n); } });
      var lit = elementsFor(nodes), any = lit.size > 0;
      everything.forEach(function (e) {
        var on = lit.has(e);
        e.style.transition = 'opacity .6s';
        e.style.opacity = !any ? '' : (on ? '1' : DIM);
        e.classList.toggle('dmt-node-hi', any && on && e.classList.contains('node'));
        e.classList.toggle('dmt-link-hi', any && on && e.tagName.toLowerCase() === 'path');
      });
      if (any) { clusters.forEach(function (c) { c.style.opacity = CLUSTER_DIM; }); }
    }
    function clearHighlight() {
      everything.forEach(function (e) { e.style.opacity = ''; e.classList.remove('dmt-node-hi', 'dmt-link-hi'); });
    }

    // ---- voice (uses the browser's own speech voices, where any are installed)
    function voices() { return ('speechSynthesis' in window) ? window.speechSynthesis.getVoices() : []; }
    function updateVoiceOption() {
      var ok = voices().length > 0;
      $('dmt-voice').disabled = !ok;
      $('dmt-voice-label').className = ok ? '' : 'dmt-off';
      $('dmt-voice-label').title = ok ? 'Read captions aloud' : 'No speech voices are installed in this browser';
      if (!ok) { $('dmt-voice').checked = false; }
    }
    if ('speechSynthesis' in window) { window.speechSynthesis.onvoiceschanged = updateVoiceOption; }
    function stopSpeech() { if ('speechSynthesis' in window) { window.speechSynthesis.cancel(); } }
    function speak(text, done) {
      var called = false;
      function finish(spoke) { if (!called) { called = true; done(spoke); } }
      if (!text || !$('dmt-voice').checked || !voices().length) { finish(false); return; }
      var s = window.speechSynthesis, u = new SpeechSynthesisUtterance(text), vs = voices();
      s.cancel();
      u.voice = vs.filter(function (v) { return /en-GB/i.test(v.lang); })[0] ||
                vs.filter(function (v) { return /^en/i.test(v.lang); })[0] || null;
      u.rate = speed();
      u.onend = function () { finish(true); };
      u.onerror = function () { finish(false); };
      s.speak(u);
    }
    function speed() { return parseFloat($('dmt-speed').value) || 1; }
    function words(t) { return (t || '').split(/\s+/).filter(Boolean).length; }
    // Time to read a part when there is no voice (or nothing new to say).
    function readingTime(f, spoken) {
      if (spoken) { return Math.max(3500, words(spoken) * 380) + 900; }
      var w = 0; f.els.forEach(function (n) { w += words(n.textContent); });
      return Math.max(2500, w * 250) + ZOOM_MS;
    }

    // ---- showing a part
    function show(i, j) {
      var s = tour.steps[i]; if (!s) { return; }
      var my = ++token, fr = s.frames;
      clearTimeout(timer); stopSpeech(); setNote('');
      if (j < 0 || j >= fr.length) { j = fr.length - 1; }
      pos = { i: i, j: j }; ended = false;
      var f = fr[j], own = f.kind === 'main' ? s.caption : (f.sub.caption || '');
      $('dmt-title').textContent = (f.sub && f.sub.title) || s.title;
      $('dmt-text').textContent = own || s.caption;
      $('dmt-count').textContent = countText(i, j, fr);
      render(f);
      setTimeout(function () {
        if (my !== token || held) { return; }
        if (j === 0) { noteSmallText(i); }
        focusEls(f.els);
      }, 50);
      speak(own, function (spoke) {
        if (my !== token || !playing) { return; }
        var wait = spoke ? 1200 / speed() : readingTime(f, own) / speed();
        timer = setTimeout(function () { if (my === token && playing) { next(); } }, wait);
      });
      updateButtons();
    }
    function countText(i, j, fr) {
      var t = 'Step ' + (i + 1) + ' of ' + tour.steps.length;
      if (fr.length < 2) { return t; }
      if (j === 0) { return t + ' · ' + (fr.length - 1) + ' parts'; }
      return t + ' · part ' + j + ' of ' + (fr.length - 1);
    }
    function setNote(t) { $('dmt-note').textContent = t; }

    // ---- moving through the tour
    function next() {
      if (!pos) { show(0, 0); return; }
      if (pos.j < tour.steps[pos.i].frames.length - 1) { show(pos.i, pos.j + 1); }
      else if (pos.i < tour.steps.length - 1) { show(pos.i + 1, 0); }
      else { atEnd(); }
    }
    function prev() {
      if (!pos) { return; }
      if (pos.j > 0) { show(pos.i, pos.j - 1); }
      else if (pos.i > 0) { show(pos.i - 1, -1); }
      else { show(0, 0); }
    }
    function nextStep() {
      if (!pos) { show(0, 0); }
      else if (pos.i < tour.steps.length - 1) { show(pos.i + 1, 0); }
      else { atEnd(); }
    }
    function prevStep() {
      if (!pos) { return; }
      if (pos.j > 0 || pos.i === 0) { show(pos.i, 0); }
      else { show(pos.i - 1, 0); }
    }
    function atEnd() {
      if (playing) { playing = false; ended = true; updateButtons(); }
      setNote('End of the tour. Press Play to start again.');
    }
    // Back and Forward keep the tour playing or paused as it was; after a hold it counts as paused.
    function nav(fn) { held = false; fn(); }

    // ---- play, pause, hold
    function togglePlay() {
      if (playing) { pause(); return; }
      if (held) { held = false; playing = true; next(); return; }     // carry on with the next part
      playing = true;
      if (!pos || ended) { show(0, 0); } else { show(pos.i, pos.j); }  // start the current part again
    }
    function pause() {
      playing = false; held = false; token++;
      clearTimeout(timer); stopSpeech(); updateButtons();
    }
    // The user has moved the model while the tour plays: let the caption finish, then wait.
    function hold() {
      if (!playing || panel.style.display === 'none') { return; }
      playing = false; held = true; anim++;
      clearTimeout(timer); updateButtons();
      setNote('Paused because you moved the model. Press Resume to carry on, or Re-centre to go back to this view.');
    }
    function restart() { held = false; playing = true; show(0, 0); }
    function recentre() {
      if (!pos) { focusEls([]); return; }
      focusEls(tour.steps[pos.i].frames[pos.j].els);
    }
    function updateButtons() {
      $('dmt-play').innerHTML = playing ? '&#10074;&#10074; Pause' : (held ? '&#9654; Resume' : '&#9654; Play');
    }
    function controlsShown() { return $('dmt-ctl').style.display !== 'none'; }
    function setControls(show) {
      $('dmt-ctl').style.display = show ? 'flex' : 'none';
      var eye = $('dmt-eye'), label = show ? 'Hide controls (H)' : 'Show controls (H)';
      eye.innerHTML = '<i class="fas ' + (show ? 'fa-eye' : 'fa-eye-slash') + '" aria-hidden="true"></i>';
      eye.title = label; eye.setAttribute('aria-label', label);
    }
    function open() {
      panel.style.display = 'block'; launch.style.display = 'none';
      pos = null; playing = false; held = false; ended = false;
      setControls(true); updateVoiceOption(); updateButtons(); setNote('');
      $('dmt-title').textContent = tour.title || 'Guided tour';
      $('dmt-text').textContent = 'Press Play, or use the arrow keys. Space plays or pauses, Shift with an arrow key or button jumps a whole step, C re-centres, H hides or shows the controls, Esc closes the tour.';
      $('dmt-count').textContent = tour.steps.length + ' steps';
    }
    function close() {
      pause(); clearHighlight(); panel.style.display = 'none'; launch.style.display = 'block';
      animateTo(fitTarget());
    }

    // ---- events
    $('dmt-prev').onclick = function (e) { nav(e.shiftKey ? prevStep : prev); };
    $('dmt-next').onclick = function (e) { nav(e.shiftKey ? nextStep : next); };
    $('dmt-play').onclick = togglePlay;
    $('dmt-recentre').onclick = recentre;
    $('dmt-restart').onclick = restart;
    $('dmt-eye').onclick = function () { setControls(!controlsShown()); };
    $('dmt-close').onclick = close;
    launch.onclick = open;
    // After a mouse click, take focus off the button so Space then plays or pauses rather than
    // pressing the same button again. Buttons reached with Tab keep their focus.
    panel.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('button');
      if (b && e.detail > 0) { b.blur(); }
    });
    // Any direct use of the model (wheel, drag, touch, its zoom buttons) holds a playing tour.
    var userEvents = ['wheel', 'pointerdown', 'touchstart'];
    userEvents.forEach(function (ev) { svg.addEventListener(ev, hold, { passive: true, capture: true }); });
    document.addEventListener('keydown', onKey);
    function onKey(e) {
      if (panel.style.display === 'none' || e.altKey || e.ctrlKey || e.metaKey) { return; }
      var tag = (e.target.tagName || '').toUpperCase(), k = e.key;
      if (tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'SELECT') { return; }
      if (tag === 'BUTTON' && (k === ' ' || k === 'Enter')) { return; } // let a focused button do its job
      var handled = true;
      if (k === 'ArrowRight') { nav(e.shiftKey ? nextStep : next); }
      else if (k === 'ArrowLeft') { nav(e.shiftKey ? prevStep : prev); }
      else if (k === ' ' || k === 'p' || k === 'P') { togglePlay(); }
      else if (k === 'Home') { restart(); }
      else if (k === 'c' || k === 'C') { recentre(); }
      else if (k === 'h' || k === 'H') { setControls(!controlsShown()); }
      else if (k === 'Escape') { close(); }
      else { handled = false; }
      if (handled) { e.preventDefault(); }
    }

    function remove() {
      pause(); clearHighlight(); anim++;
      document.removeEventListener('keydown', onKey);
      userEvents.forEach(function (ev) { svg.removeEventListener(ev, hold, { capture: true }); });
      panel.remove(); launch.remove(); css.remove();
    }

    panel.style.display = 'none';
    window.DMTour = {
      open: open, close: close, show: show, next: next, prev: prev, play: togglePlay, pause: pause,
      restart: restart, recentre: recentre, tour: tour,
      state: function () { return { pos: pos, playing: playing, held: held, ended: ended, frames: pos ? tour.steps[pos.i].frames.map(function (f) { return f.kind; }) : null }; }
    };
    return { svg: svg, remove: remove };
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
  else { init(); }
})();
