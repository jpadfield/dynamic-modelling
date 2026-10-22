/*
 * export.js - save the diagram on screen as a PNG or SVG file
 * -----------------------------------------------------------
 * The picture is made from the page itself, so it is exactly what Mermaid has drawn here.
 *
 * - The whole model is always saved, wherever it has been panned or zoomed to, and any
 *   tour highlighting is left out.
 * - PNG files are sized by text size: node text is drawn TEXT_PX pixels high (or
 *   LARGE_TEXT_PX for the large text option), so text is equally clear in small and
 *   large models. Bigger models simply give bigger pictures.
 * - SVG files keep the drawing as shapes and text, so they stay sharp at any size.
 *   They use HTML labels (as the page does), which some drawing programs do not show.
 * - Font Awesome icons are built into the saved files, so they appear in the picture.
 * - Backgrounds are transparent, as the earlier Download Image option gave. A white
 *   background can be put back by uncommenting the code marked WHITE BACKGROUND, in
 *   two places (buildSvg and downloadPNG).
 * - Browsers limit how big a picture they can make. Very large models are made smaller
 *   to fit, and a message says so. If the browser cannot make a PNG at all, the SVG is
 *   saved instead and a message explains why.
 *
 * Menu items (in the Links menu): #downloadPNG, #downloadPNGLarge, #downloadSVG.
 */
(function () {
  'use strict';

  var TEXT_PX = 16;          // node text height in a normal PNG, in pixels
  var LARGE_TEXT_PX = 32;    // node text height in a large text PNG
  var PAD = 10;              // margin around the model, in the model's own units
  var MAX_SIDE = 16384;      // largest picture side that current browsers can all make
  var MAX_AREA = 100000000;  // largest picture area (width x height) allowed
  // Font Awesome 5 files, matching the version loaded by index.php
  var FA_URL = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/webfonts/';
  var FA_FILES = {
    'Font Awesome 5 Free|900': 'fa-solid-900.woff2',
    'Font Awesome 5 Free|400': 'fa-regular-400.woff2',
    'Font Awesome 5 Brands|400': 'fa-brands-400.woff2'
  };
  var fontCache = {};

  function stamp() {
    var d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; };
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + '_' +
      p(d.getHours()) + '-' + p(d.getMinutes()) + '-' + p(d.getSeconds());
  }

  // A short message at the bottom of the page.
  function tell(text) {
    var m = document.getElementById('dm-export-note');
    if (!m) {
      m = document.createElement('div');
      m.id = 'dm-export-note';
      m.setAttribute('role', 'status');
      m.style.cssText = 'position:fixed;left:50%;bottom:90px;transform:translateX(-50%);max-width:min(640px,90vw);' +
        'background:rgba(20,24,32,.92);color:#fff;padding:10px 16px;border-radius:8px;font:15px/1.4 system-ui,sans-serif;' +
        'z-index:100000;box-shadow:0 6px 20px rgba(0,0,0,.3)';
      document.body.appendChild(m);
    }
    m.textContent = text;
    m.style.display = 'block';
    clearTimeout(tell.timer);
    tell.timer = setTimeout(function () { m.style.display = 'none'; }, 9000);
  }

  function save(blob, name) {
    var a = document.createElement('a'), url = URL.createObjectURL(blob);
    a.href = url; a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
  }

  function toBase64(buffer) {
    var bytes = new Uint8Array(buffer), s = '', chunk = 0x8000;
    for (var i = 0; i < bytes.length; i += chunk) { s += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk)); }
    return btoa(s);
  }
  function fontData(file) {
    if (!fontCache[file]) {
      fontCache[file] = fetch(FA_URL + file)
        .then(function (r) { if (!r.ok) { throw new Error('font ' + r.status); } return r.arrayBuffer(); })
        .then(toBase64);
    }
    return fontCache[file];
  }

  // Build a stand-alone copy of the diagram. Returns a promise of {text, width, height, units}
  // where width and height are in the model's own units and units is the node text size in them.
  function buildSvg() {
    var svg = document.querySelector('#modelDiv svg');
    if (!svg || !svg.querySelector('g.node')) { return Promise.reject(new Error('There is no diagram to save yet.')); }
    var vp = svg.querySelector('.svg-pan-zoom_viewport');
    var box = (vp || svg).getBBox();

    // node text size in the model's own units
    var units = 16, lab = svg.querySelector('g.node .nodeLabel') || svg.querySelector('g.node text');
    if (lab) {
      var px = parseFloat(getComputedStyle(lab).fontSize) || 16;
      var holder = lab.closest ? lab.closest('foreignObject, text') : null;
      var hm = holder && holder.getScreenCTM ? holder.getScreenCTM() : null;
      var rm = (vp || svg).getScreenCTM ? (vp || svg).getScreenCTM() : null;
      units = (hm && rm && rm.a) ? px * hm.a / rm.a : px;
    }

    var clone = svg.cloneNode(true);
    var cvp = clone.querySelector('.svg-pan-zoom_viewport');
    if (cvp) { cvp.removeAttribute('transform'); cvp.removeAttribute('style'); }
    ['#svg-pan-zoom-controls', '#svg-pan-zoom-controls-styles'].forEach(function (sel) {
      var e = clone.querySelector(sel); if (e) { e.remove(); }
    });

    // leave out tour highlighting
    [].forEach.call(clone.querySelectorAll('g.node, g.cluster, path.flowchart-link, g.edgeLabel'), function (e) {
      e.style.opacity = ''; e.style.transition = '';
      e.classList.remove('dmt-node-hi', 'dmt-link-hi');
    });

    // icons: replace each icon with its character in the icon font, and build the font in
    var originals = svg.querySelectorAll('i[class*="fa-"]');
    var copies = clone.querySelectorAll('i[class*="fa-"]');
    var needed = {};
    [].forEach.call(originals, function (icon, n) {
      var before = getComputedStyle(icon, '::before'), cs = getComputedStyle(icon);
      var glyph = (before.content || '').replace(/^["']|["']$/g, '');
      var family = (cs.fontFamily || '').split(',')[0].replace(/["']/g, '').trim();
      var weight = String(cs.fontWeight || '400');
      var key = family + '|' + weight;
      if (!glyph || glyph === 'none' || !FA_FILES[key] || !copies[n]) { return; }
      needed[key] = FA_FILES[key];
      var span = document.createElementNS('http://www.w3.org/1999/xhtml', 'span');
      span.textContent = glyph;
      span.setAttribute('style', "font-family:'" + family + "';font-weight:" + weight + ';font-style:normal;display:inline-block');
      copies[n].parentNode.replaceChild(span, copies[n]);
    });

    var w = box.width + 2 * PAD, h = box.height + 2 * PAD;
    clone.setAttribute('viewBox', (box.x - PAD) + ' ' + (box.y - PAD) + ' ' + w + ' ' + h);
    clone.setAttribute('width', Math.ceil(w));
    clone.setAttribute('height', Math.ceil(h));
    clone.removeAttribute('style');
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    clone.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

    var keys = Object.keys(needed);
    return Promise.all(keys.map(function (k) { return fontData(needed[k]); })).then(function (data) {
      if (keys.length) {
        var css = keys.map(function (k, i) {
          var parts = k.split('|');
          return "@font-face{font-family:'" + parts[0] + "';font-style:normal;font-weight:" + parts[1] +
            ";src:url(data:font/woff2;base64," + data[i] + ") format('woff2');}";
        }).join('\n');
        var style = document.createElementNS('http://www.w3.org/2000/svg', 'style');
        style.textContent = css;
        clone.insertBefore(style, clone.firstChild);
      }
      // WHITE BACKGROUND (SVG and PNG): switched off on 19 October 2026 so that pictures have a
      // transparent background, as the earlier Download Image option gave. Transparent pictures
      // blend into coloured slides and pages, but the black lines and link text are hard to see
      // on dark backgrounds. To put a white background back, uncomment the five lines below
      // (and the WHITE BACKGROUND line in downloadPNG).
      // var bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      // bg.setAttribute('x', box.x - PAD); bg.setAttribute('y', box.y - PAD);
      // bg.setAttribute('width', w); bg.setAttribute('height', h); bg.setAttribute('fill', 'white');
      // var first = clone.querySelector('style') ? clone.querySelector('style').nextSibling : clone.firstChild;
      // (cvp || clone).insertBefore(bg, cvp ? cvp.firstChild : first);
      return { text: new XMLSerializer().serializeToString(clone), width: w, height: h, units: units };
    }, function () {
      // the icon font could not be fetched: save without it rather than not at all
      return { text: new XMLSerializer().serializeToString(clone), width: w, height: h, units: units, noIcons: keys.length > 0 };
    });
  }

  function downloadSVG(reason) {
    return buildSvg().then(function (s) {
      save(new Blob([s.text], { type: 'image/svg+xml' }), 'model_' + stamp() + '.svg');
      if (reason) { tell(reason); }
      else if (s.noIcons) { tell('The SVG has been saved, but the icon font could not be fetched so icons may not show.'); }
    });
  }

  function downloadPNG(textPx) {
    return buildSvg().then(function (s) {
      var scale = textPx / (s.units || 16);
      var width = Math.ceil(s.width * scale), height = Math.ceil(s.height * scale);
      var note = '';
      var fit = Math.min(1, MAX_SIDE / width, MAX_SIDE / height, Math.sqrt(MAX_AREA / (width * height)));
      if (fit < 1) {
        scale *= fit; width = Math.floor(s.width * scale); height = Math.floor(s.height * scale);
        note = 'This model is too large for a full size PNG, so it has been made smaller (text about ' +
          Math.max(1, Math.round(textPx * fit)) + ' pixels high). Download SVG gives the full size.';
      }
      return new Promise(function (resolve, reject) {
        var img = new Image();
        img.onload = function () {
          try {
            var c = document.createElement('canvas');
            c.width = width; c.height = height;
            var ctx = c.getContext('2d');
            // WHITE BACKGROUND (PNG): switched off so that the PNG is transparent; see the note in buildSvg.
            // ctx.fillStyle = 'white'; ctx.fillRect(0, 0, width, height);
            ctx.drawImage(img, 0, 0, width, height);
            c.toBlob(function (blob) {
              if (!blob) { reject(new Error('no picture')); return; }
              save(blob, 'model_' + stamp() + '.png');
              if (note) { tell(note); }
              else if (s.noIcons) { tell('The PNG has been saved, but the icon font could not be fetched so icons may not show.'); }
              resolve();
            }, 'image/png');
          } catch (e) { reject(e); }
        };
        img.onerror = function () { reject(new Error('The drawing could not be read as a picture.')); };
        img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(s.text);
      });
    }).catch(function (e) {
      if (window.console) { console.warn('export.js: PNG failed', e); }
      // some browsers (older Safari, for example) will not turn these drawings into PNGs
      return downloadSVG('This browser could not make a PNG of this diagram, so an SVG has been saved instead. ' +
        'Most browsers and image programs can open it, and it can be converted to PNG elsewhere.');
    });
  }

  function bind(id, fn) {
    var el = document.getElementById(id);
    if (el) { el.addEventListener('click', function (e) { e.preventDefault(); fn(); }); }
  }
  function init() {
    bind('downloadPNG', function () { downloadPNG(TEXT_PX); });
    bind('downloadPNGLarge', function () { downloadPNG(LARGE_TEXT_PX); });
    bind('downloadSVG', function () { downloadSVG().catch(function (e) { tell(e.message || 'The SVG could not be saved.'); }); });
  }

  window.DMExport = { png: downloadPNG, svg: downloadSVG };
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
