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
?>

<?php
// reCAPTCHA config
$cap_e              = $cap_e              ?? ($_SESSION['cap_e']             ?? 'off');      // 'on'|'off'
$mode               = $mode               ?? ($_SESSION['mode']              ?? 'normal');   // 'reCAPTCHA'|'normal'
$recaptcha_version  = $recaptcha_version  ?? ($_SESSION['recaptcha_version'] ?? 'v2');       // 'v2'|'v3'
$site_key           = $site_key           ?? ($_SESSION['recaptcha_sitekey'] ?? '');         // site key string
$captcha_enabled    = ($cap_e === 'on' && $mode === 'reCAPTCHA' && !empty($site_key));
?>

<!-- Footer -->
<footer class="container-xl py-3 my-4 border-top">
  <div class="row">
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
      Powered by <a href="https://phpaste.sourceforge.io/" target="_blank" class="text-decoration-none">Paste</a>
    </div>
  </div>
</footer>

<?php if (!isset($_SESSION['username'])): ?>
    <div class="text-center mb-4">
        <?php echo $ads_2 ?? ''; ?>
    </div>
<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars($baseurl . 'theme/' . $default_theme . '/js/paste.js', ENT_QUOTES, 'UTF-8'); ?>"></script>

<!-- Google Analytics -->
<?php if (!empty($ga)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($ga, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo htmlspecialchars($ga, ENT_QUOTES, 'UTF-8'); ?>');
</script>
<?php endif; ?>

<?php if ($captcha_enabled && strtolower($recaptcha_version) === 'v3'): ?>
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

      // Wait for reCAPTCHA to initialize fully
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

        // Avoid double-binding if footer is ever included twice
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

        // Sanity token on page load
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


<!-- Additional Scripts -->
<?php echo $additional_scripts ?? ''; ?>
</body>
</html>
