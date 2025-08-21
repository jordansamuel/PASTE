<?php
/*
 * Paste $v3.1 2025/08/16 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */

// reCAPTCHA config
$cap_e              = $cap_e              ?? ($_SESSION['cap_e']             ?? 'off');      // 'on'|'off'
$mode               = $mode               ?? ($_SESSION['mode']              ?? 'normal');   // 'reCAPTCHA'|'normal'
$recaptcha_version  = $recaptcha_version  ?? ($_SESSION['recaptcha_version'] ?? 'v2');       // 'v2'|'v3'
$site_key           = $site_key           ?? ($_SESSION['recaptcha_sitekey'] ?? '');         // site key string
$captcha_enabled    = ($cap_e === 'on' && $mode === 'reCAPTCHA' && !empty($site_key));
?>

<!-- Footer -->
<footer class="container-xl py-3 my-4 border-top">
  <div class="row align-items-center gy-2">
    <div class="col-md-4 mb-0 text-muted">
      Copyright &copy; <?php echo date("Y"); ?>
      <a href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
        <?php echo htmlspecialchars($site_name ?? 'Paste', ENT_QUOTES, 'UTF-8'); ?>
      </a>. All rights reserved.
    </div>

    <div class="col-md-4 text-center">
      <a href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline-flex align-items-center text-decoration-none" aria-label="Paste Home">
        <i class="bi bi-clipboard me-2" style="font-size: 1.5rem;"></i>
      </a>
      <?php
      // Footer inline links:
      $footerLinks = getNavLinks($pdo, 'footer');
      echo renderNavListSimple($footerLinks, ' &middot; ');
      ?>
    </div>

    <div class="col-md-4 text-md-end text-muted">
      <button type="button" class="btn btn-link p-0 me-3" data-bs-toggle="modal" data-bs-target="#cookieSettingsModal" aria-label="Cookie Settings">Cookie Settings</button>
      <a href="https://phpaste.sourceforge.io/" target="_blank" class="text-decoration-none">Powered by Paste</a>
    </div>
  </div>
</footer>

<?php if (!isset($_SESSION['username'])): ?>
  <div class="text-center mb-4">
    <?php echo $ads_2 ?? ''; ?>
  </div>
<?php endif; ?>

<!-- GDPR stuff -->
<div id="cookieBanner" class="position-fixed bottom-0 start-0 end-0 border-top shadow-sm">
  <div class="container-xl py-1 d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
    <div class="me-lg-2">
      <h6 class="mb-1">We use cookies. To comply with GDPR in the EU and the UK we have to show you these.</h6>
      <p class="mb-0 text-muted small">
        We use cookies and similar technologies to keep this website functional (including spam protection via Google reCAPTCHA), and — with your consent — to measure usage and show ads. 
        See <a href="<?php echo htmlspecialchars(($baseurl ?? '/') . 'page/privacy', ENT_QUOTES, 'UTF-8'); ?>">Privacy</a>.
      </p>
    </div>
    <div class="ms-lg-auto d-flex gap-3">
      <button id="cookieReject" type="button" class="btn btn-outline-secondary">Reject non-essential</button>
      <button id="cookieSettings" type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cookieSettingsModal">Settings</button>
      <button id="cookieAcceptAll" type="button" class="btn btn-primary">Accept all</button>
    </div>
  </div>
</div>

<!-- Cookie Settings Modal -->
<div class="modal fade" id="cookieSettingsModal" tabindex="-1" aria-labelledby="cookieSettingsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cookieSettingsLabel">Cookie Settings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">
          We use cookies to make our site work and keep it safe and secure (e.g., reCAPTCHA). You can choose to enable additional categories.
        </p>

        <div class="list-group">

          <label class="list-group-item d-flex align-items-start">
            <div class="form-check form-switch me-3 mt-1">
              <input class="form-check-input" type="checkbox" role="switch" checked disabled>
            </div>
            <div>
              <div class="fw-semibold">Strictly necessary</div>
              <div class="small text-muted">
                Required for security and core features (sessions, preferences, rate-limiting, and Google reCAPTCHA). These are always on.
              </div>
            </div>
          </label>

          <label class="list-group-item d-flex align-items-start">
            <div class="form-check form-switch me-3 mt-1">
              <input id="consentAnalytics" class="form-check-input" type="checkbox" role="switch">
            </div>
            <div>
              <div class="fw-semibold">Analytics</div>
              <div class="small text-muted">
                Helps us understand usage and improve the site (Google Analytics).
              </div>
            </div>
          </label>

          <label class="list-group-item d-flex align-items-start">
            <div class="form-check form-switch me-3 mt-1">
              <input id="consentAds" class="form-check-input" type="checkbox" role="switch">
            </div>
            <div>
              <div class="fw-semibold">Advertising</div>
              <div class="small text-muted">
                Enables ad networks like Google AdSense. Ads may use cookies to personalize/measure performance.
              </div>
            </div>
          </label>

        </div>
      </div>
      <div class="modal-footer">
        <button id="cookieSave" type="button" class="btn btn-primary" data-bs-dismiss="modal">Save preferences</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars($baseurl . 'theme/' . $default_theme . '/js/paste.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php if (!empty($showThemeSwitcher) && !empty($hl_theme_options)): ?>
  <script>
	// Highlight.php theme picker
    // Pass config to the external script
    window.__HL_THEMES  = <?php echo json_encode($hl_theme_options, JSON_UNESCAPED_SLASHES); ?>;
    window.__HL_INITIAL = <?php echo isset($initialTheme) ? json_encode($initialTheme) : 'null'; ?>;
  </script>
  <script src="<?php echo htmlspecialchars($baseurl . 'theme/' . $default_theme . '/js/highlightTheme.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<script>
(function () {
  // --- Simple consent storage in a cookie (JSON payload) ---
  var CONSENT_COOKIE = 'paste_consent';
  var CONSENT_MAX_DAYS = 365;

  function setCookie(name, value, days) {
    var expires = '';
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + (days*24*60*60*1000));
      expires = '; expires=' + d.toUTCString();
    }
    var secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; Path=/' + secure + '; SameSite=Lax';
  }

  function getCookie(name) {
    var cname = name + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i].trim();
      if (c.indexOf(cname) === 0) return decodeURIComponent(c.substring(cname.length, c.length));
    }
    return null;
  }

  function getDefaultConsent() {
    return { decided:false, analytics:false, ads:false };
  }

  function readConsent() {
    try {
      var raw = getCookie(CONSENT_COOKIE);
      return raw ? JSON.parse(raw) : getDefaultConsent();
    } catch(e) {
      return getDefaultConsent();
    }
  }

  function saveConsent(c) {
    c.decided = true;
    setCookie(CONSENT_COOKIE, JSON.stringify(c), CONSENT_MAX_DAYS);
  }

  function qs(id){ return document.getElementById(id); }

  // --- Script loaders gated by consent ---
  var hasLoadedGA = false;
  var hasLoadedAds = false;

  function loadGoogleAnalytics(measurementId) {
    if (hasLoadedGA || !measurementId) return;
    hasLoadedGA = true;

    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
    s.onload = function () {
      window.dataLayer = window.dataLayer || [];
      function gtag(){ dataLayer.push(arguments); }
      window.gtag = gtag;
      gtag('js', new Date());
      gtag('config', measurementId);
    };
    document.head.appendChild(s);
  }

  function loadAdSense(clientId) {
    if (hasLoadedAds || !clientId) return;
    hasLoadedAds = true;

    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(clientId);
    s.setAttribute('crossorigin', 'anonymous');
    document.head.appendChild(s);
  }

  // Apply consent now
  function applyConsent(consent) {
    // Analytics (Google Analytics)
    <?php if (!empty($ga)): ?>
      if (consent.analytics) { loadGoogleAnalytics(<?php echo json_encode($ga); ?>); }
    <?php endif; ?>

    // Advertising (Google AdSense)
    // If you’re using AdSense, put your pub id below; otherwise leave blank.
    var adsClient = '';
    if (adsClient && consent.ads) { loadAdSense(adsClient); }
  }

  // --- UI wiring ---
  var banner = qs('cookieBanner');
  var btnAcceptAll = qs('cookieAcceptAll');
  var btnReject = qs('cookieReject');
  var btnSettings = qs('cookieSettings');
  var btnSave = qs('cookieSave');
  var chkAnalytics = qs('consentAnalytics');
  var chkAds = qs('consentAds');

  var consent = readConsent();

  // Initialize toggles from stored consent
  if (chkAnalytics) chkAnalytics.checked = !!consent.analytics;
  if (chkAds) chkAds.checked = !!consent.ads;

  // Show banner if user hasn’t decided yet
  if (!consent.decided && banner) {
    banner.style.display = 'block';
  }

  // Apply consent for this page load if already decided
  applyConsent(consent);

  // Handlers
  if (btnAcceptAll) {
    btnAcceptAll.addEventListener('click', function () {
      consent.analytics = true;
      consent.ads = true;
      saveConsent(consent);
      if (banner) banner.style.display = 'none';
      applyConsent(consent);
    });
  }

  if (btnReject) {
    btnReject.addEventListener('click', function () {
      consent.analytics = false;
      consent.ads = false;
      saveConsent(consent);
      if (banner) banner.style.display = 'none';
      // No need to unload anything; we simply don’t load.
    });
  }

  if (btnSave) {
    btnSave.addEventListener('click', function () {
      consent.analytics = !!(chkAnalytics && chkAnalytics.checked);
      consent.ads = !!(chkAds && chkAds.checked);
      saveConsent(consent);
      if (banner) banner.style.display = 'none';
      applyConsent(consent);
    });
  }
})();
</script>

<?php if ($captcha_enabled && strtolower($recaptcha_version) === 'v3'): ?>
<!-- reCAPTCHA v3 (strictly necessary: loaded regardless of analytics/ads consent) -->
<script>
  window.pasteConfig = {
    enabled: true,
    mode: 'reCAPTCHA',
    version: 'v3',
    siteKey: <?php echo json_encode($site_key); ?>
  };

  (function () {
    function ok(m){ console.log('%c'+m,'color:#16a34a;font-weight:600'); }
    function err(m){ console.log('%c'+m,'color:#ef4444;font-weight:700'); }
    function warn(m){ console.log('%c'+m,'color:#f59e0b;font-weight:600'); }

    ok('[reCAPTCHA] v3 enabled; loading api.js…');

    var s = document.createElement('script');
    s.async = true; s.defer = true;
    s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(window.pasteConfig.siteKey);

    s.onload = function () {
      if (!window.grecaptcha) { err('[reCAPTCHA] api.js loaded but grecaptcha missing'); return; }

      grecaptcha.ready(function () {
        ok('[reCAPTCHA] grecaptcha ready.');

        var actionMap = {
          'mainForm': 'create_paste',
          'signin-form': 'login',
          'direct-signin-form': 'login',
          'signup-form': 'signup',
          'forgot-form': 'forgot',
          'reset-form': 'reset',
          'resend-form': 'resend'
        };

        function ensureHidden(form) {
          var h = form.querySelector('input[name="g-recaptcha-response"]');
          if (!h) {
            h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'g-recaptcha-response';
            form.appendChild(h);
          }
          return h;
        }

        if (!window.__rcBoundSubmit) {
          document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;

            var id = form.id || '';
            var action = actionMap[id] || 'submit';

            var hidden = form.querySelector('input[name="g-recaptcha-response"]');
            if (hidden && hidden.value) { warn('[reCAPTCHA] token already present for "'+action+'"; allow submit.'); return; }

            e.preventDefault();
            grecaptcha.execute(window.pasteConfig.siteKey, { action: action }).then(function (token) {
              console.log('[reCAPTCHA] action="%s" token: %s…', action, token.slice(0, 28));
              ensureHidden(form).value = token;
              HTMLFormElement.prototype.submit.call(form);
            }).catch(function (e2) {
              err('[reCAPTCHA] execute failed for "'+action+'": ' + (e2 && e2.message || e2));
              HTMLFormElement.prototype.submit.call(form); // let server decide
            });
          }, { capture: true });

          window.__rcBoundSubmit = true;
        }

        grecaptcha.execute(window.pasteConfig.siteKey, { action: 'page_load' })
          .then(function (t) { console.log('[reCAPTCHA] action="page_load" token: %s…', t.slice(0, 28)); })
          .catch(function (e3) { err('[reCAPTCHA] page_load token failed: ' + (e3 && e3.message || e3)); });
      });
    };

    s.onerror = function(){ err('[reCAPTCHA] failed to load api.js'); };
    document.head.appendChild(s);
  })();
</script>
<?php endif; ?>

<!-- Additional Script -->
<?php echo $additional_scripts ?? ''; ?>

</body>
</html>
