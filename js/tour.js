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
 *   //step Overview | | A step with no nodes shows the whole model.
 *
 * - Node names must match the node labels exactly (as written in the TSV).
 * - Separate node names with semicolons. Captions may contain commas.
 * - Links are highlighted automatically when both of their nodes are listed in the step.
 *
 * Keys: Right/Left arrow = next/previous, P = play/pause, H = hide/show the controls,
 *       Esc = close the tour.
 *
 * Requires: the page's Mermaid SVG inside #modelDiv, the #triplesTxt textarea and the
 * svg-pan-zoom instance in window.panZoom (both created by local.js).
 */
(function () {
  'use strict';

  var SELECTORS = {
    source: '#triplesTxt',
    svg: '#modelDiv svg',
    node: 'g.node',
    link: 'path.flowchart-link',
    linkLabel: 'g.edgeLabel',
    cluster: 'g.cluster'
  };
  var DIM = '0.12', CLUSTER_DIM = '0.35', ZOOM_MS = 900, MAX_ZOOM_STEP = 6;

  // ------------------------------------------------------------------ parsing
  function parseTour(text) {
    var tour = { title: '', steps: [] };
    (text || '').split(/\r?\n/).forEach(function (line) {
      var m;
      // When the page is refreshed, the server turns ", " into a tab; put the commas back.
      line = line.replace(/\t+$/, '').replace(/\t/g, ', ');
      if ((m = line.match(/^\/\/tour\s+(.*)$/i))) { tour.title = m[1].trim(); return; }
      if ((m = line.match(/^\/\/step\s+(.*)$/i))) {
        var parts = m[1].split('|');
        tour.steps.push({
          title: (parts[0] || '').trim(),
          nodes: (parts[1] || '').split(';').map(norm).filter(Boolean),
          caption: parts.slice(2).join('|').trim()
        });
      }
    });
    return tour;
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

    // ---- styles and panel
    var css = document.createElement('style');
    css.textContent = [
      '#dmt-panel{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);width:min(900px,92vw);background:rgba(20,24,32,.92);color:#fff;border-radius:12px;padding:16px 20px 12px;font:18px/1.45 system-ui,sans-serif;z-index:99999;box-shadow:0 8px 30px rgba(0,0,0,.35)}',
      '#dmt-title{font-weight:700;font-size:14px;letter-spacing:.05em;text-transform:uppercase;color:#ffb37a;margin-bottom:4px}',
      '#dmt-text{min-height:3em}',
      '#dmt-ctl{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;font-size:14px;color:#ccd}',
      '#dmt-ctl button,#dmt-show,#dmt-launch{background:#2f3747;color:#fff;border:0;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:15px}',
      '#dmt-ctl button:hover,#dmt-launch:hover{background:#455069}',
      '#dmt-ctl .dmt-sp{flex:1}',
      '#dmt-ctl select{background:#2f3747;color:#fff;border:0;border-radius:6px;padding:3px}',
      '#dmt-ctl label.dmt-off{opacity:.45;cursor:not-allowed}',
      '#dmt-show{position:absolute;right:8px;top:8px;font-size:12px;padding:2px 8px;opacity:.15;transition:opacity .3s}',
      '#dmt-show:hover{opacity:1}',
      '#dmt-launch{position:fixed;right:24px;bottom:24px;z-index:99999;padding:8px 14px;box-shadow:0 4px 16px rgba(0,0,0,.3)}',
      '.dmt-node-hi rect,.dmt-node-hi polygon,.dmt-node-hi circle,.dmt-node-hi path{stroke:#e8590c!important;stroke-width:4px!important}',
      'path.dmt-link-hi{stroke:#e8590c!important;stroke-width:3px!important}'
    ].join('\n');
    document.head.appendChild(css);

    var panel = document.createElement('div');
    panel.id = 'dmt-panel';
    panel.innerHTML =
      '<button id="dmt-show" title="Show controls (H)" style="display:none">Show controls</button>' +
      '<div id="dmt-title"></div><div id="dmt-text"></div>' +
      '<div id="dmt-ctl">' +
      '<button id="dmt-prev" title="Previous (Left arrow)">&#9664;</button>' +
      '<button id="dmt-play" title="Play or pause (P)">&#9654; Play</button>' +
      '<button id="dmt-next" title="Next (Right arrow)">&#9654;&#124;</button>' +
      '<span id="dmt-count"></span><span class="dmt-sp"></span>' +
      '<label>Speed <select id="dmt-speed"><option value="0.75">0.75x</option><option value="1" selected>1x</option><option value="1.25">1.25x</option><option value="1.5">1.5x</option></select></label>' +
      '<label id="dmt-voice-label"><input type="checkbox" id="dmt-voice"> Voice</label>' +
      '<button id="dmt-hide" title="Hide controls (H)">Hide controls</button>' +
      '<button id="dmt-close" title="Close the tour (Esc)">&#10005;</button>' +
      '</div>';
    document.body.appendChild(panel);

    var launch = document.createElement('button');
    launch.id = 'dmt-launch';
    launch.innerHTML = '&#9654; ' + (tour.title ? 'Tour: ' + escapeHtml(tour.title) : 'Start tour');
    document.body.appendChild(launch);

    function $(id) { return document.getElementById(id); }

    // ---- state
    var cur = -1, playing = false, timer = null;

    // ---- highlighting
    function stepElements(step) {
      var set = new Set();
      var wanted = new Set();
      step.nodes.forEach(function (name) {
        var n = byLabel[name];
        if (n) { set.add(n); wanted.add(n); }
        else if (window.console) { console.warn('tour.js: no node labelled "' + name + '"'); }
      });
      linkInfo.forEach(function (l) {
        if (l.from && l.to && wanted.has(l.from) && wanted.has(l.to)) {
          set.add(l.path); if (l.label) { set.add(l.label); }
        }
      });
      return set;
    }
    function highlight(set) {
      var any = set.size > 0;
      everything.forEach(function (e) {
        e.style.transition = 'opacity .6s';
        e.style.opacity = !any ? '' : (set.has(e) ? '1' : DIM);
        e.classList.toggle('dmt-node-hi', any && set.has(e) && e.classList.contains('node'));
        e.classList.toggle('dmt-link-hi', any && set.has(e) && e.tagName.toLowerCase() === 'path');
      });
      if (any) { clusters.forEach(function (c) { c.style.opacity = CLUSTER_DIM; }); }
    }
    function clearHighlight() {
      everything.forEach(function (e) { e.style.opacity = ''; e.classList.remove('dmt-node-hi', 'dmt-link-hi'); });
    }

    // ---- zooming (animated, using svg-pan-zoom)
    function animateTo(applyTarget) {
      var pz = window.panZoom; if (!pz) { return; }
      var z0 = pz.getZoom(), p0 = pz.getPan();
      applyTarget();
      var z1 = pz.getZoom(), p1 = pz.getPan();
      pz.zoom(z0); pz.pan(p0);
      var t0 = performance.now();
      (function frame(t) {
        var k = Math.min(1, (t - t0) / ZOOM_MS);
        k = k < 0.5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2;
        pz.zoom(z0 * Math.pow(z1 / z0, k));
        pz.pan({ x: p0.x + (p1.x - p0.x) * k, y: p0.y + (p1.y - p0.y) * k });
        if (k < 1) { requestAnimationFrame(frame); }
      })(t0);
    }
    function focusOn(set) {
      var pz = window.panZoom; if (!pz) { return; }
      var sr = svg.getBoundingClientRect(); if (!sr.width || !sr.height) { return; }
      if (!set.size) { animateTo(function () { pz.fit(); pz.center(); }); return; }
      var x0 = 1e9, y0 = 1e9, x1 = -1e9, y1 = -1e9;
      set.forEach(function (e) {
        var r = e.getBoundingClientRect(); if (!r.width && !r.height) { return; }
        x0 = Math.min(x0, r.left); y0 = Math.min(y0, r.top); x1 = Math.max(x1, r.right); y1 = Math.max(y1, r.bottom);
      });
      if (x0 > x1) { return; }
      // Visible area of the model, above the caption panel.
      var top = Math.max(0, sr.top), bottom = Math.min(window.innerHeight, sr.bottom);
      if (panel.style.display !== 'none') { bottom = Math.min(bottom, panel.getBoundingClientRect().top - 10); }
      var W = sr.width, H = Math.max(100, bottom - top);
      var cx = (x0 + x1) / 2 - sr.left, cy = (y0 + y1) / 2 - sr.top;
      var tx = W / 2, ty = (top - sr.top) + H / 2;
      var f = Math.min(W * 0.8 / Math.max(1, x1 - x0), H * 0.75 / Math.max(1, y1 - y0), MAX_ZOOM_STEP);
      animateTo(function () { pz.zoomAtPoint(pz.getZoom() * f, { x: cx, y: cy }); pz.panBy({ x: tx - cx, y: ty - cy }); });
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
    function speak(text, done) {
      if (!$('dmt-voice').checked || !voices().length) { done(false); return; }
      var s = window.speechSynthesis, u = new SpeechSynthesisUtterance(text), vs = voices();
      s.cancel();
      u.voice = vs.filter(function (v) { return /en-GB/i.test(v.lang); })[0] ||
                vs.filter(function (v) { return /^en/i.test(v.lang); })[0] || null;
      u.rate = speed();
      u.onend = function () { done(true); };
      u.onerror = function () { done(false); };
      s.speak(u);
    }
    function speed() { return parseFloat($('dmt-speed').value) || 1; }

    // ---- navigation
    function go(i) {
      if (i < 0 || i >= tour.steps.length) { return; }
      cur = i; clearTimeout(timer);
      var step = tour.steps[i];
      $('dmt-title').textContent = step.title;
      $('dmt-text').textContent = step.caption;
      $('dmt-count').textContent = 'Step ' + (i + 1) + ' of ' + tour.steps.length;
      var set = stepElements(step);
      highlight(set);
      setTimeout(function () { focusOn(set); }, 50);
      var words = step.caption.split(/\s+/).length;
      speak(step.caption, function (spoke) {
        if (!playing) { return; }
        // With voice: move on shortly after speaking. Without: allow reading time.
        var wait = spoke ? 1200 / speed() : Math.max(3500, words * 380) / speed() + 900;
        timer = setTimeout(advance, wait);
      });
    }
    function advance() { if (cur < tour.steps.length - 1) { go(cur + 1); } else { pause(); } }
    function play() {
      playing = true; $('dmt-play').innerHTML = '&#10074;&#10074; Pause';
      if (cur < 0 || cur >= tour.steps.length - 1) { go(0); } else { go(cur + 1); }
    }
    function pause() {
      playing = false; clearTimeout(timer);
      if ('speechSynthesis' in window) { window.speechSynthesis.cancel(); }
      $('dmt-play').innerHTML = '&#9654; Play';
    }
    function setControls(show) {
      $('dmt-ctl').style.display = show ? 'flex' : 'none';
      $('dmt-show').style.display = show ? 'none' : 'block';
    }
    function open() {
      panel.style.display = 'block'; launch.style.display = 'none';
      cur = -1; setControls(true); updateVoiceOption();
      $('dmt-title').textContent = tour.title || 'Guided tour';
      $('dmt-text').textContent = 'Press Play, or use the arrow keys. P plays or pauses, H hides or shows the controls, Esc closes the tour.';
      $('dmt-count').textContent = tour.steps.length + ' steps';
    }
    function close() {
      pause(); clearHighlight(); panel.style.display = 'none'; launch.style.display = 'block';
      if (window.panZoom) { animateTo(function () { window.panZoom.fit(); window.panZoom.center(); }); }
    }

    // ---- events
    $('dmt-prev').onclick = function () { pause(); go(cur - 1); };
    $('dmt-next').onclick = function () { pause(); go(cur + 1); };
    $('dmt-play').onclick = function () { if (playing) { pause(); } else { play(); } };
    $('dmt-hide').onclick = function () { setControls(false); };
    $('dmt-show').onclick = function () { setControls(true); };
    $('dmt-close').onclick = close;
    launch.onclick = open;
    document.addEventListener('keydown', onKey);
    function onKey(e) {
      if (panel.style.display === 'none') { return; }
      var tag = (e.target.tagName || '').toUpperCase();
      if (tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'SELECT') { return; }
      if (e.key === 'ArrowRight') { pause(); go(cur + 1); }
      else if (e.key === 'ArrowLeft') { pause(); go(cur - 1); }
      else if (e.key === 'p' || e.key === 'P') { if (playing) { pause(); } else { play(); } }
      else if (e.key === 'h' || e.key === 'H') { setControls($('dmt-ctl').style.display === 'none'); }
      else if (e.key === 'Escape') { close(); }
    }

    function remove() {
      pause(); clearHighlight();
      document.removeEventListener('keydown', onKey);
      panel.remove(); launch.remove(); css.remove();
    }

    panel.style.display = 'none';
    window.DMTour = { open: open, close: close, go: go, play: play, pause: pause, tour: tour };
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
