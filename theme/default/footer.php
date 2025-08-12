<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Default theme
 * License: GNU General Public License v3 or later
 */
?>
<!-- Footer -->
<footer class="container-xl py-3 my-4 border-top">
    <div class="row">
        <div class="col-md-4 mb-0 text-muted">
            Copyright &copy; <?php echo date("Y"); ?> <a href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none"><?php echo htmlspecialchars($site_name ?? 'Paste', ENT_QUOTES, 'UTF-8'); ?></a>. All rights reserved.
        </div>
        <div class="col-md-4 text-center">
            <a href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline-flex align-items-center text-decoration-none" aria-label="Paste Home">
                <i class="bi bi-clipboard me-2" style="font-size: 1.5rem;"></i>
            </a>
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
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/php/php.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/markdown/markdown.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/javascript/javascript.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/python/python.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/clike/clike.min.js"></script>
<?php if (isset($_SESSION['captcha_mode']) && ($_SESSION['captcha_mode'] == "recaptcha" || $_SESSION['captcha_mode'] == "recaptcha_v3")): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo ($_SESSION['captcha_mode'] == 'recaptcha_v3') ? htmlspecialchars($_SESSION['captcha'] ?? '', ENT_QUOTES, 'UTF-8') : 'explicit'; ?>&onload=onRecaptchaLoad" async defer></script>
<?php endif; ?>
<script src="<?php echo htmlspecialchars($baseurl . 'theme/' . $default_theme . '/js/paste.js', ENT_QUOTES, 'UTF-8'); ?>"></script>

<!-- Google Analytics -->
<?php if (!empty($ga)): ?>
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', '<?php echo htmlspecialchars($ga, ENT_QUOTES, 'UTF-8'); ?>', 'auto');
ga('send', 'pageview');
</script>
<?php endif; ?>

<script>

<?php if (isset($_SESSION['captcha_mode']) && $_SESSION['captcha_mode'] == "recaptcha"): ?>
function onRecaptchaSuccess(token) {
    console.log('reCAPTCHA v2 completed: Token received');
    document.getElementById('g-recaptcha-response').value = token;
}
function validateRecaptcha() {
    const token = document.getElementById('g-recaptcha-response').value;
    if (!token) {
        console.error('reCAPTCHA v2 token missing');
        alert('<?php echo htmlspecialchars($lang['recaptcha_missing'] ?? 'Please complete the reCAPTCHA.', ENT_QUOTES, 'UTF-8'); ?>');
        return false;
    }
    return true;
}
<?php elseif (isset($_SESSION['captcha_mode']) && $_SESSION['captcha_mode'] == "recaptcha_v3"): ?>
function onRecaptchaLoad() {
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo htmlspecialchars($_SESSION['captcha'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', {action: 'create_paste'}).then(function(token) {
            console.log('reCAPTCHA v3 executed: Token received');
            document.getElementById('g-recaptcha-response').value = token;
        }, function(error) {
            console.error('reCAPTCHA v3 error:', error);
        });
    });
}
function validateRecaptcha() {
    return true; // Validation handled server-side for v3
}
<?php else: ?>
function validateRecaptcha() {
    return true; // No reCAPTCHA validation for internal or none
}
<?php endif; ?>
</script>

<!-- Additional Scripts -->
<?php echo $additional_scripts ?? ''; ?>
</body>
</html>