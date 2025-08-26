// JS for the theme picker for highlight.php if enabled
(function () {
  // Provided by footer.php when the switcher is present
  const THEMES  = Array.isArray(window.__HL_THEMES) ? window.__HL_THEMES : [];
  const INITIAL = window.__HL_INITIAL || null; // (?theme=)

  if (!THEMES.length) return;

  // Normalize a theme id or filename to a comparable id (e.g., "Atelier Estuary Dark.min.css?v=1" -> "atelier-estuary-dark")
  function normId(s) {
    return (s || '')
      .toLowerCase()
      .replace(/[ _]+/g, '-')     // spaces/underscores -> hyphen
      .replace(/\.min\.css$/,'')  // drop .min.css
      .replace(/\.css$/,'')       // drop .css
      .replace(/[?#].*$/,'')      // drop query/hash
      .trim();
  }

  function findHeaderLink() {
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    for (const l of links) {
      const href = (l.getAttribute('href') || '').toLowerCase();
      if (href.includes('/includes/Highlight/')) return l;
    }
    return null;
  }

  function ensureLink() {
    let el = document.getElementById('hljs-theme-link') || findHeaderLink();
    if (el) { el.id = 'hljs-theme-link'; return el; }
    el = document.createElement('link');
    el.id = 'hljs-theme-link';
    el.rel = 'stylesheet';
    document.head.appendChild(el);
    return el;
  }

  function applyTheme(obj) {
    if (!obj) return;
    const link = ensureLink();
    if (link.getAttribute('href') !== obj.href) link.setAttribute('href', obj.href);
    try { localStorage.setItem('hljs_theme', obj.id); } catch (_) {}
  }

  function choose(initialId) {
    const byId = (id) => THEMES.find(t => normId(t.id) === normId(id));
    if (initialId) {
      const m = byId(initialId);
      if (m) return m;
    }
    try {
      const ls = localStorage.getItem('hljs_theme');
      if (ls) {
        const m = byId(ls);
        if (m) return m;
      }
    } catch (_) {}
    return THEMES.find(t => t.id === 'hybrid')
        || THEMES.find(t => t.id === 'github-dark')
        || THEMES[0];
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Determine the default theme from the link already present in <head>
    const headerLink = findHeaderLink();
    const DEFAULT_ID = (function () {
      if (!headerLink) return 'hybrid';
      const href = headerLink.getAttribute('href') || '';
      const file = (href.split('?')[0].split('#')[0].split('/').pop()) || '';
      return normId(file);
    })();

    const byId = (id) => THEMES.find(x => normId(x.id) === normId(id));

    // Update URL param (?theme=...) remove it entirely when using the default
    function updateThemeQueryParam(id) {
      const isDefault = normId(id) === normId(DEFAULT_ID);
      try {
        const url = new URL(window.location.href);
        const before = url.toString();
        if (isDefault) {
          url.searchParams.delete('theme');
          url.searchParams.delete('highlight');
        } else {
          url.searchParams.set('theme', id);
          url.searchParams.delete('highlight');
        }
        const after = url.toString();
        if (after !== before) window.history.replaceState(null, '', url);
      } catch {
        // Fallback for older browsers
        let href = window.location.href;
        href = href
          .replace(/([?&])(theme|highlight)=[^&]*/gi, '$1')
          .replace(/[?&]$/, '');
        if (!isDefault) {
          href += (href.indexOf('?') === -1 ? '?' : '&') + 'theme=' + encodeURIComponent(id);
        }
        window.history.replaceState(null, '', href);
      }
    }

    // If URL explicitly set a theme, clear LS so it doesn't override
    if (INITIAL) { try { localStorage.removeItem('hljs_theme'); } catch (_) {} }

    // There may be 1–2 selects (toolbar + fullscreen)
    function getSelects() { return Array.from(document.querySelectorAll('#hljs-theme-select')); }
    let selects = getSelects();
    if (!selects.length) return;

    let chosen = choose(INITIAL) || byId(selects[0].value);
    if (chosen) {
      applyTheme(chosen);
      selects.forEach(s => { s.value = chosen.id; });
      updateThemeQueryParam(chosen.id); // removes param if it's the default
    }

    function onChange(e) {
      const t = byId(e.target.value);
      if (!t) return;
      applyTheme(t);
      updateThemeQueryParam(t.id);       // removes param if default, sets otherwise
      // keep other pickers in sync (e.g., fullscreen modal)
      selects.forEach(s => { if (s !== e.target) s.value = t.id; });
    }
    selects.forEach(s => { s.addEventListener('change', onChange); s.__hlBound = true; });

    // If a modal injects a new select, bind & sync it
    document.addEventListener('shown.bs.modal', function () {
      const newer = getSelects();
      if (newer.length !== selects.length) {
        selects = newer;
        const currentId = (localStorage.getItem('hljs_theme') || (chosen && chosen.id)) || '';
        selects.forEach(s => {
          if (!s.__hlBound) {
            s.addEventListener('change', onChange);
            s.__hlBound = true;
          }
          if (currentId) s.value = currentId;
        });
      }
    });
  });
})();
