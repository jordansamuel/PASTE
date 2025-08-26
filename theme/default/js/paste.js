(function () {
  'use strict';

  // ========= tiny utils =====================================================

  // Start index of the current line (from a caret index)
  function lineStart(value, i){ while (i > 0 && value.charCodeAt(i - 1) !== 10) i--; return i }
  // End index of the current line (from a caret index)
  function lineEnd(value, i){ while (i < value.length && value.charCodeAt(i) !== 10) i++; return i }
  // Fire an input event after programmatic changes
  function triggerInput(el){ try { el.dispatchEvent(new Event('input', { bubbles: true })) } catch(_) {} }
  // Fast newline counter (no regex; walks the string once)
  function countLinesFast(str){ let n = 1; for (let i=0; i<str.length; i++) if (str.charCodeAt(i) === 10) n++; return n }
  // Digits in an integer
  function digitsOf(n){ return Math.max(1, (n|0).toString().length) }

  // ========= lightweight editor (textarea + virtualized line-number gutter) =

  function initLiteEditor(ta, opts){
    if (!ta || ta.dataset.liteInit === '1') return;
    const readOnly = !!(opts && opts.readOnly);

    // ---- DOM scaffold -------------------------------------------------------
    const wrap = document.createElement('div');
    wrap.className = 'editor-wrap';

    const gutter = document.createElement('div');
    gutter.className = 'editor-gutter';
    gutter.setAttribute('aria-hidden','true');

    const rail = document.createElement('div');       // the vertical list of numbers
    rail.className = 'editor-gutter-inner';
    gutter.appendChild(rail);

    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(gutter);
    wrap.appendChild(ta);

    ta.classList.add('editor-ta', 'form-control');
    ta.setAttribute('wrap','off');        // no soft wrapping (keeps lines 1:1)
    ta.style.overflowX = 'auto';
    ta.style.overflowY = 'auto';
    ta.dataset.liteInit = '1';

    // ---- metrics: match gutter & textarea ----------------------------------
    const csTA = getComputedStyle(ta);
    const fs   = parseFloat(csTA.fontSize) || 14;
    const lhPx = (csTA.lineHeight && csTA.lineHeight !== 'normal')
      ? parseFloat(csTA.lineHeight)
      : Math.round(fs * 1.5);

    ta.style.lineHeight = lhPx + 'px';

    // make gutter use the exact same font + line-height
    gutter.style.fontFamily = csTA.fontFamily;
    gutter.style.fontSize   = csTA.fontSize;
    gutter.style.lineHeight = lhPx + 'px';
    gutter.style.paddingTop    = csTA.paddingTop;
    gutter.style.paddingBottom = csTA.paddingBottom;

    // Compute precise vertical delta so numbers & text align pixel-perfect.
    // This accounts for paddings/borders on both the textarea and gutter/rail.
    const csG = getComputedStyle(gutter);
    const csR = getComputedStyle(rail);
    const padTopTA = parseFloat(csTA.paddingTop)        || 0;
    const bTopTA   = parseFloat(csTA.borderTopWidth)    || 0;
    const padTopGU = parseFloat(csG.paddingTop)         || 0;
    const bTopGU   = parseFloat(csG.borderTopWidth)     || 0;
    const padTopRL = parseFloat(csR.paddingTop)         || 0;
    const TOP_DELTA = (padTopTA + bTopTA) - (padTopGU + bTopGU + padTopRL);

    // lock the initial height so huge pastes don't auto-expand the TA
    // (honors rows="" if present; otherwise uses current rendered height)
    (function lockHeightOnce(){
      const rows   = parseInt(ta.getAttribute('rows') || '0', 10);
      const padTop    = parseFloat(csTA.paddingTop)        || 0;
      const padBottom = parseFloat(csTA.paddingBottom)     || 0;
      const bTop      = parseFloat(csTA.borderTopWidth)    || 0;
      const bBottom   = parseFloat(csTA.borderBottomWidth) || 0;
      const h = rows > 0
        ? Math.round(rows * lhPx + padTop + padBottom + bTop + bBottom)
        : ta.offsetHeight;
      if (h > 0) { ta.style.height = h + 'px'; ta.style.minHeight = h + 'px'; }
    })();

    // kill bootstrap ring artifacts
    ta.style.boxShadow = 'none';
    ta.style.outline   = '0';
    ta.addEventListener('focus', function(){
      ta.style.boxShadow = 'none';
      ta.style.outline   = '0';
    });

    // ---- state for virtual gutter ------------------------------------------
    let totalLines   = countLinesFast(ta.value); // total in the doc
    let renderStart  = 1;                        // first line number currently in the rail
    let renderEnd    = 0;                        // last line number currently in the rail
    let rafId        = 0;                        // rAF scheduler id
    const BUFFER     = 40;                       // extra lines above/below the viewport

    // Ensure gutter width fits the number of digits (in ch, to keep crisp)
    function ensureGutterWidth(){
      gutter.style.minWidth = (digitsOf(totalLines) + 2) + 'ch';
    }
    ensureGutterWidth();

    // First visible line (1-based)
    function firstVisibleLine(){
      return Math.max(1, Math.floor(ta.scrollTop / lhPx) + 1);
    }
    // How many lines can we see right now?
    function visibleCount(){
      return Math.max(1, Math.ceil(ta.clientHeight / lhPx) + 1);
    }

    // Build "start..end" as a single string with trailing newline
    function buildNumbers(start, end){
      const len = end - start + 1;
      const buf = new Array(len);
      for (let i = 0; i < len; i++) buf[i] = (start + i) + '';
      return buf.join('\n') + '\n';
    }

    // Position the rail so that line "renderStart" sits exactly next to the
    // correct text row (uses translate3d for GPU-friendly subpixel placement)
    function positionRail(){
      const offsetPx = ((renderStart - 1) * lhPx) - ta.scrollTop + TOP_DELTA;
      rail.style.transform = 'translate3d(0,' + offsetPx + 'px,0)';
    }

    // Main rAF updater: maybe rebuild numbers, always reposition the rail.
    function update(){
      rafId = 0;

      // determine the range we need rendered
      const firstVis = firstVisibleLine();
      const visCnt   = visibleCount();
      const start    = Math.max(1, firstVis - BUFFER);
      const end      = Math.min(totalLines, firstVis + visCnt + BUFFER);

      // only rebuild the rail text if the required range changed
      if (start !== renderStart || end !== renderEnd) {
        rail.textContent = buildNumbers(start, end);
        renderStart = start;
        renderEnd   = end;
      }

      // adjust width if digits grew/shrank (e.g., 99 -> 100 lines)
      ensureGutterWidth();

      // always reposition so numbers stay glued to the text
      positionRail();

      // keep gutter box the same height as the textarea (for nice borders)
      gutter.style.height = ta.offsetHeight + 'px';
    }
    function schedule(){ if (!rafId) rafId = requestAnimationFrame(update) }

    // ---- events -------------------------------------------------------------

    // Scroll: we only need to reposition (cheap), but schedule() also handles
    // the occasional range rebuild when we cross buffer thresholds.
    ta.addEventListener('scroll', schedule, { passive: true });

    // Content changes: recalc totalLines once, then schedule an update
    ['input','change','cut','paste'].forEach(ev => {
      ta.addEventListener(ev, function(){
        totalLines = countLinesFast(ta.value);
        schedule();
      });
    });

    // Tab / Shift+Tab indentation helpers (editing only)
    if (!readOnly){
      ta.addEventListener('keydown', function(e){
        if (e.key !== 'Tab') return;
        e.preventDefault();

        const start = ta.selectionStart, end = ta.selectionEnd;
        const v = ta.value, before = v.slice(0,start), sel = v.slice(start,end), after = v.slice(end);

        if (e.shiftKey){
          const lines = sel.split('\n');
          const newSel = lines.map(l=>{
            if (l.startsWith('    ')) return l.slice(4);
            if (l.startsWith('\t'))   return l.slice(1);
            return l.replace(/^ {1,3}/,'');
          }).join('\n');

          // adjust selection to account for the first line's removed indent
          const firstLineStart = before.lastIndexOf('\n') + 1;
          let removed = 0;
          const head = v.slice(firstLineStart, firstLineStart+4);
          if (head.startsWith('\t')) removed = 1;
          else if (head.startsWith('    ')) removed = 4;
          else { const m = head.match(/^ {1,3}/); removed = m ? m[0].length : 0 }

          ta.value = before + newSel + after;
          const newStart = start - Math.min(removed, start - firstLineStart);
          const diff = sel.length - newSel.length;
          ta.setSelectionRange(newStart, end - diff);
        } else {
          if (sel.indexOf('\n') !== -1){
            const ind = sel.replace(/^/gm, '    ');
            ta.value = before + ind + after;
            ta.setSelectionRange(start + 4, end + (ind.length - sel.length));
          } else {
            ta.value = before + '    ' + sel + after;
            const caret = start + 4;
            ta.setSelectionRange(caret, caret);
          }
        }

        totalLines = countLinesFast(ta.value);
        schedule();
      });
    } else {
      ta.setAttribute('readonly','readonly');
    }

    // Keep things aligned when the box resizes
    if ('ResizeObserver' in window){
      new ResizeObserver(schedule).observe(ta);
    } else {
      window.addEventListener('resize', schedule);
    }

    // Initial paint
    schedule();
  }

  // ========= notifications ==================================================
  function showNotification(message, isError = false, fadeOut = true) {
    const notification = document.getElementById('notification');
    if (!notification) return;
    notification.textContent = message;
    notification.className = 'notification' + (isError ? ' error' : '');
    notification.style.display = 'block';
    if (fadeOut) {
      setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
          notification.style.display = 'none';
          notification.classList.remove('fade-out');
          notification.textContent = '';
        }, 500);
      }, 3000);
    } else {
      if (!notification.querySelector('.close-btn')) {
        const closeBtn = document.createElement('button');
        closeBtn.textContent = '×';
        closeBtn.className = 'close-btn';
        closeBtn.addEventListener('click', () => {
          notification.style.display = 'none';
          notification.textContent = '';
        });
        notification.appendChild(closeBtn);
      }
    }
  }

  // ========= tools (existing UI hooks) =====================================
  window.togglev = function () {
    // GeSHi wrapper
    const block = document.querySelector('.code-content');
    if (block) {
      block.classList.toggle('no-line-numbers');
      try { localStorage.setItem('paste_ln_hidden', block.classList.contains('no-line-numbers') ? '1' : '0'); } catch (_) {}
      return;
    }
    // Fallback
    const olElement = document.querySelector('pre ol, .geshi ol, ol');
    if (!olElement) { showNotification('Code list element not found.', true); return; }
    const currentStyle = olElement.style.listStyle || getComputedStyle(olElement).listStyle;
    olElement.style.listStyle = (currentStyle.startsWith('none')) ? 'decimal' : 'none';
  };

  window.toggleFullScreen = function(){
    const modalElement = document.getElementById('fullscreenModal');
    if (!modalElement) { showNotification('Fullscreen modal not available.', true); return; }
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    bsModal.show();
    modalElement.addEventListener('hidden.bs.modal', function handler() {
      const backdrop = document.querySelector('.modal-backdrop'); if (backdrop) backdrop.remove();
      document.body.classList.remove('modal-open');
      modalElement.removeEventListener('hidden.bs.modal', handler);
    }, { once: true });
  };

  window.copyToClipboard = function(){
    const ta = document.getElementById('code');
    const text = ta ? ta.value : '';
    if (!text) { showNotification('No code to copy.', true); return; }
    navigator.clipboard.writeText(text).then(
      () => showNotification('Copied to clipboard!'),
      () => showNotification('Failed to copy.', true)
    );
  };

  window.showEmbedCode = function(embedCode){
    if (embedCode) showNotification('Embed code: ' + embedCode, false, false);
    else showNotification('Could not generate embed code.', true);
  };

  // Insert "!highlight!" at selected lines in the main editor
  window.highlightLine = function (e) {
    if (e && e.preventDefault) e.preventDefault();
    var ta = document.getElementById('edit-code'); if (!ta) return;

    var prefix = '!highlight!';
    var value  = ta.value;
    var start  = ta.selectionStart || 0;
    var end    = ta.selectionEnd   || start;
    var keepScroll = ta.scrollTop;

    var ls = lineStart(value, start);
    var le = lineEnd(value, end);

    var before = value.slice(0, ls);
    var middle = value.slice(ls, le);
    var after  = value.slice(le);

    var lines = middle.split('\n');
    var addedTotal = 0;
    var addedPerLine = [];

    for (var i = 0; i < lines.length; i++) {
      if (lines[i].startsWith(prefix)) {
        addedPerLine[i] = 0;
      } else {
        lines[i] = prefix + lines[i];
        addedPerLine[i] = prefix.length;
        addedTotal += prefix.length;
      }
    }

    var newMiddle = lines.join('\n');
    ta.value = before + newMiddle + after;

    if (start === end) {
      var caretOffset = (addedPerLine[0] || 0);
      ta.selectionStart = ta.selectionEnd = start + caretOffset;
    } else {
      ta.selectionStart = ls;
      ta.selectionEnd   = le + addedTotal;
    }

    ta.scrollTop = keepScroll;
    triggerInput(ta);
    ta.focus();
  };

  // ========= boot ===========================================================
  document.addEventListener('DOMContentLoaded', function(){
    const edit = document.getElementById('edit-code'); if (edit) initLiteEditor(edit, { readOnly:false });
    const raw  = document.getElementById('code');      if (raw)  initLiteEditor(raw,  { readOnly:true  });

    document.addEventListener('click', function (ev) {
      const t = ev.target;
      if (t.closest && t.closest('.highlight-line'))   { ev.preventDefault(); window.highlightLine(ev); }
      if (t.closest && t.closest('.toggle-fullscreen')){ ev.preventDefault(); window.toggleFullScreen(); }
      if (t.closest && t.closest('.copy-clipboard'))   { ev.preventDefault(); window.copyToClipboard(); }
    }, { capture: true });
  });

})();
