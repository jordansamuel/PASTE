(function () {
  // ---------- small utils ----------
  function px(v){ return v ? (parseFloat(v) || 0) : 0; }
  function lineStart(value, i){ while (i > 0 && value.charCodeAt(i - 1) !== 10) i--; return i; }
  function lineEnd(value, i){ while (i < value.length && value.charCodeAt(i) !== 10) i++; return i; }
  function triggerInput(el){ try { el.dispatchEvent(new Event('input', { bubbles: true })); } catch(_) {} }

  // ---------- lightweight editor (gutter + textarea) ----------
  function initLiteEditor(ta, opts){
    if (!ta || ta.dataset.liteInit === '1') return;
    const readOnly = !!(opts && opts.readOnly);

    // structure
    const wrap = document.createElement('div');
    wrap.className = 'editor-wrap';

    const gutter = document.createElement('div');
    gutter.className = 'editor-gutter';
    gutter.setAttribute('aria-hidden','true');

    const rail = document.createElement('div');
    rail.className = 'editor-gutter-inner';
    gutter.appendChild(rail);

    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(gutter);
    wrap.appendChild(ta);
    ta.classList.add('editor-ta', 'form-control');      // keep Bootstrap sizing, but we’ll neutralize its ring
    ta.dataset.liteInit = '1';

    // ----- force identical metrics between TA and gutter -----
    const csTA = getComputedStyle(ta);

    // set explicit pixel line-height to avoid browser rounding drift
    const fs = parseFloat(csTA.fontSize) || 14;
    const lhPx = (csTA.lineHeight && csTA.lineHeight !== 'normal')
      ? parseFloat(csTA.lineHeight)
      : Math.round(fs * 1.5);

    // lock textarea line-height so we know exact pixel step
    ta.style.lineHeight = lhPx + 'px';

    // clone font metrics to gutter so numbers align perfectly
    gutter.style.fontFamily = csTA.fontFamily;
    gutter.style.fontSize   = csTA.fontSize;
    gutter.style.lineHeight = lhPx + 'px';
    // match vertical padding so first number aligns with first text line
    gutter.style.paddingTop    = csTA.paddingTop;
    gutter.style.paddingBottom = csTA.paddingBottom;

    // neutralize Bootstrap focus ring for this textarea
    ta.style.boxShadow  = 'none';
    ta.style.outline    = '0';
    ta.addEventListener('focus', function(){
      ta.style.boxShadow = 'none';
      ta.style.outline   = '0';
    });

    // render numbers (capped)
    let lastCount = -1;
    let rafId = 0;

    function lineCount(str){ return (str.match(/\n/g) || []).length + 1; }

    function renderNumbers(count){
      if (count === lastCount) return;
      lastCount = count;
      const cap = Math.min(count, 50000);
      let out = '';
      for (let i = 1; i <= cap; i++) out += i + '\n';
      if (count > cap) out += '…\n';
      rail.textContent = out;
    }

    function syncHeights(){
      // Match the *visual box* height of the textarea (borders included)
      gutter.style.height = ta.offsetHeight + 'px';
    }

    function syncScroll(){
      // Move the inner rail so numbers appear to scroll with the textarea
      rail.style.transform = 'translateY(' + (-ta.scrollTop) + 'px)';
    }

    function update(){
      rafId = 0;
      renderNumbers(lineCount(ta.value));
      syncHeights();
      syncScroll();
    }

    function schedule(){ if (!rafId) rafId = requestAnimationFrame(update); }

    // events
    ta.addEventListener('scroll', syncScroll);
    ['input','change','cut','paste'].forEach(ev => ta.addEventListener(ev, schedule));

    // tab / shift+tab (skip when readOnly)
    if (!readOnly){
      ta.addEventListener('keydown', function(e){
        if (e.key !== 'Tab') return;
        e.preventDefault();

        const start = ta.selectionStart, end = ta.selectionEnd;
        const v = ta.value, before = v.slice(0,start), sel = v.slice(start,end), after = v.slice(end);

        if (e.shiftKey){
          // unindent
          const lines = sel.split('\n');
          const newSel = lines.map(l=>{
            if (l.startsWith('    ')) return l.slice(4);
            if (l.startsWith('\t'))   return l.slice(1);
            return l.replace(/^ {1,3}/,'');
          }).join('\n');

          // compute removed on first affected line (for caret)
          const firstLineStart = before.lastIndexOf('\n') + 1;
          let removed = 0;
          const head = v.slice(firstLineStart, firstLineStart+4);
          if (head.startsWith('\t')) removed = 1;
          else if (head.startsWith('    ')) removed = 4;
          else { const m = head.match(/^ {1,3}/); removed = m ? m[0].length : 0; }

          ta.value = before + newSel + after;
          const newStart = start - Math.min(removed, start - firstLineStart);
          const diff = sel.length - newSel.length;
          ta.setSelectionRange(newStart, end - diff);
        } else {
          // indent
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
        schedule();
      });
    } else {
      ta.setAttribute('readonly','readonly');
    }

    // keep in sync if user resizes textarea (drag handle)
    if ('ResizeObserver' in window){
      new ResizeObserver(()=>{ syncHeights(); syncScroll(); }).observe(ta);
    } else {
      window.addEventListener('resize', ()=>{ syncHeights(); syncScroll(); });
    }

    update();
  }

  // ---------- notifications (used by tools) ----------
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

  // ---------- tools used in view.php ----------
  // Toggle line numbers for GeSHi-rendered block (ordered list)
	window.togglev = function () {
	  // Grab the main code wrapper
	  const block = document.querySelector('.code-content');
	  if (block) {
		block.classList.toggle('no-line-numbers');
		const hidden = block.classList.contains('no-line-numbers');
		try { localStorage.setItem('paste_ln_hidden', hidden ? '1' : '0'); } catch (_) {}
		return;
	  }

	  // Fallback: if no .code-content wrapper exists
	  const olElement = document.querySelector('pre ol, .geshi ol, ol');
	  if (!olElement) { 
		showNotification('Code list element not found.', true); 
		return; 
	  }

	  const currentStyle = olElement.style.listStyle || getComputedStyle(olElement).listStyle;
	  olElement.style.listStyle = (currentStyle.startsWith('none')) ? 'decimal' : 'none';
	};

  // Fullscreen modal (Bootstrap)
  window.toggleFullScreen = function(){
    const modalElement = document.getElementById('fullscreenModal');
    if (!modalElement) { showNotification('Fullscreen modal not available.', true); return; }
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    bsModal.show();
    modalElement.addEventListener('hidden.bs.modal', function handler() {
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) backdrop.remove();
      document.body.classList.remove('modal-open');
      modalElement.removeEventListener('hidden.bs.modal', handler);
    }, { once: true });
  };

  // Copy from raw textarea (#code)
  window.copyToClipboard = function(){
    const ta = document.getElementById('code');
    const text = ta ? ta.value : '';
    if (!text) { showNotification('No code to copy.', true); return; }
    navigator.clipboard.writeText(text).then(
      () => showNotification('Copied to clipboard!'),
      () => showNotification('Failed to copy.', true)
    );
  };

  // Show embed code in a notification (sticky)
  window.showEmbedCode = function(embedCode){
    if (embedCode) showNotification('Embed code: ' + embedCode, false, false);
    else showNotification('Could not generate embed code.', true);
  };

  // ---------- native textarea highlight tool ----------
  window.highlightLine = function (e) {
    if (e && e.preventDefault) e.preventDefault();

    var ta = document.getElementById('edit-code');
    if (!ta) return;

    var prefix = '!highlight!';
    var value  = ta.value;
    var start  = ta.selectionStart || 0;
    var end    = ta.selectionEnd   || start;
    var keepScroll = ta.scrollTop;

    // Expand to full lines covering selection (or current line if caret)
    var ls = lineStart(value, start);
    var le = lineEnd(value, end);

    var before = value.slice(0, ls);
    var middle = value.slice(ls, le);
    var after  = value.slice(le);

    // Prefix each selected line if not already highlighted
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
      // single caret: keep caret on same visual column
      var caretOffset = (addedPerLine[0] || 0);
      ta.selectionStart = ta.selectionEnd = start + caretOffset;
    } else {
      // selection: keep covering the same logical block
      ta.selectionStart = ls;
      ta.selectionEnd   = le + addedTotal;
    }

    ta.scrollTop = keepScroll;
    triggerInput(ta); // let gutter update
    ta.focus();
  };

  // ---------- boot ----------
  document.addEventListener('DOMContentLoaded', function(){
    // init editors (editable and read-only)
    const edit = document.getElementById('edit-code');
    if (edit) initLiteEditor(edit, { readOnly:false });

    const raw = document.getElementById('code');
    if (raw) initLiteEditor(raw, { readOnly:true });

    // action buttons (delegate to keep it simple)
    document.addEventListener('click', function (ev) {
      const t = ev.target;
      if (t.closest && t.closest('.highlight-line'))   { ev.preventDefault(); window.highlightLine(ev); }
      if (t.closest && t.closest('.toggle-fullscreen')){ ev.preventDefault(); window.toggleFullScreen(); }
      if (t.closest && t.closest('.copy-clipboard'))   { ev.preventDefault(); window.copyToClipboard(); }
    }, { capture: true });
  });
})();
