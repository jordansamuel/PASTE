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
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(basename($default_lang ?? 'en.php', '.php'), ENT_QUOTES, 'UTF-8'); ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if (isset($p_title)) { echo htmlspecialchars($p_title ?? '', ENT_QUOTES, 'UTF-8') . ' - '; } echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($des ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="keywords" content="<?php echo htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($baseurl . 'theme/' . ($default_theme ?? 'default') . '/img/favicon.ico', ENT_QUOTES, 'UTF-8'); ?>" />
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
	<?php if (($highlighter ?? 'geshi') === 'highlight'): ?>
	  <?php
		// Highlight.php theme CSS (only when using highlight.php)
		$stylesRel = 'includes/Highlight/styles';
		$styleFile = isset($hl_style) && $hl_style !== '' ? $hl_style : 'hybrid.css';
		$href = rtrim($baseurl ?? '/', '/') . '/' . $stylesRel . '/' . $styleFile;
	  ?>
	  <link rel="stylesheet" id="hljs-theme-link" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
	<?php endif; ?>

    <?php if (isset($ges_style)) { echo $ges_style; } ?>
    <link href="<?php echo htmlspecialchars($baseurl . 'theme/' . ($default_theme ?? 'default') . '/css/paste.css', ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" type="text/css" />
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg bg-dark">
        <div class="container-xl d-flex align-items-center">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($baseurl ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!isset($privatesite) || $privatesite != "on") { ?>
                <div class="navbar-center">
                    <form class="search-form" action="<?php echo htmlspecialchars($baseurl . ($mod_rewrite == '1' ? 'archive' : 'archive.php'), ENT_QUOTES, 'UTF-8'); ?>" method="get">
                        <input class="form-control me-2" type="search" name="q" id="searchInput" placeholder="<?php echo htmlspecialchars($lang['search'] ?? 'Search pastes...', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Search" value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                        <button class="btn btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </div>
            <?php } ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto">
    <?php
    // Archive link
    if (!isset($privatesite) || $privatesite != "on") {
        if ($mod_rewrite == '1') {
            echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($baseurl . 'archive', ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($lang['archive'] ?? 'Archive', ENT_QUOTES, 'UTF-8') . '</a></li>';
        } else {
            echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($baseurl . 'archive.php', ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($lang['archive'] ?? 'Archive', ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
    }

    // Dynamic pages (header/both) from `pages` table
    $headerLinks = getNavLinks($pdo, 'header');
    echo renderBootstrapNav($headerLinks);
    ?>

    <!-- Account / Guest dropdown -->
    <li class="nav-item dropdown navbar-nav-guest">
        <?php if (isset($_SESSION['token'])) {
            echo '<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person"></i> ' . htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') . '</a>';
        ?>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header"><?php echo htmlspecialchars($lang['my_account'] ?? 'My Account', ENT_QUOTES, 'UTF-8'); ?></h6></li>
                <?php
                if ($mod_rewrite == '1') {
                    echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'user/' . urlencode($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-clipboard"></i> ' . htmlspecialchars($lang['pastes'] ?? 'Pastes', ENT_QUOTES, 'UTF-8') . '</a></li>';
                    echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'profile', ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-person"></i> ' . htmlspecialchars($lang['settings'] ?? 'Settings', ENT_QUOTES, 'UTF-8') . '</a></li>';
                    echo '<li><hr class="dropdown-divider"></li>';
                    echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'login.php?action=logout', ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-box-arrow-right"></i> ' . htmlspecialchars($lang['logout'] ?? 'Logout', ENT_QUOTES, 'UTF-8') . '</a></li>';
                } else {
                    echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'user.php?user=' . urlencode($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-clipboard"></i> ' . htmlspecialchars($lang['pastes'] ?? 'Pastes', ENT_QUOTES, 'UTF-8') . '</a></li>';
                    echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'profile.php', ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-person"></i> ' . htmlspecialchars($lang['settings'] ?? 'Settings', ENT_QUOTES, 'UTF-8') . '</a></li>';
                    echo '<li><hr class="dropdown-divider"></li>';
                    echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'login.php?action=logout', ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-box-arrow-right"></i> ' . htmlspecialchars($lang['logout'] ?? 'Logout', ENT_QUOTES, 'UTF-8') . '</a></li>';
                }
                ?>
            </ul>
        <?php } else {
            echo '<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person"></i> ' . htmlspecialchars($lang['guest'] ?? 'Guest', ENT_QUOTES, 'UTF-8') . '</a>';
        ?>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#signin"><i class="bi bi-box-arrow-in-right"></i> <?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#signup"><i class="bi bi-person-plus"></i> <?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></a></li>
                <?php
                if ($enablegoog == 'yes') {
                    echo '<li><a class="dropdown-item btn-oauth" href="' . htmlspecialchars($baseurl . 'login.php?login=google', ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-google oauth-icon"></i> ' . htmlspecialchars($lang['login_with_google'] ?? 'Google', ENT_QUOTES, 'UTF-8') . '</a></li>';
                }
                if ($enablefb == 'yes') {
                    echo '<li><a class="dropdown-item btn-oauth" href="' . htmlspecialchars($baseurl . 'login.php?login=facebook', ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-facebook oauth-icon"></i> ' . htmlspecialchars($lang['login_with_facebook'] ?? 'Facebook', ENT_QUOTES, 'UTF-8') . '</a></li>';
                }
                ?>
            </ul>
        <?php } ?>
    </li>
</ul>

            </div>
        </div>
    </nav>

    <?php if (!isset($privatesite) || $privatesite != "on") { ?>
        <!-- Sign in Modal -->
        <div class="modal fade" id="signin" tabindex="-1" aria-labelledby="signinModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="signinModalLabel"><?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="signin-feedback" class="mb-3"></div>
                        <form method="POST" action="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>" id="signin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="signin" value="1">
                            <input type="hidden" name="ajax" value="1">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? $baseurl, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (isset($_SESSION['cap_e']) && $_SESSION['cap_e'] === 'on' && $_SESSION['mode'] === 'reCAPTCHA'): ?>
                                <textarea id="g-recaptcha-response-signin" name="g-recaptcha-response" style="display: none;"></textarea>
                                <?php if ($_SESSION['recaptcha_version'] === 'v2'): ?>
                                    <div class="g-recaptcha mb-3" data-sitekey="<?php echo htmlspecialchars($_SESSION['recaptcha_sitekey'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-callback="onRecaptchaSuccessSignin"></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="signinUsername" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" id="signinUsername" placeholder="<?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signinPassword" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" class="form-control" id="signinPassword" placeholder="<?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="current-password" required>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="signinRememberme" name="rememberme" checked>
                                <label class="form-check-label" for="signinRememberme"><?php echo htmlspecialchars($lang['rememberme'] ?? 'Keep me signed in.', ENT_QUOTES, 'UTF-8'); ?></label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-perky w-100 mt-3" id="signinSubmit"><?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></button>
                            <a class="btn btn-outline-light w-100 mt-2" href="<?php echo htmlspecialchars($baseurl . 'login.php?action=forgot', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['forgot_password'] ?? 'Forgot Password', ENT_QUOTES, 'UTF-8'); ?></a>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <a href="<?php echo htmlspecialchars($baseurl . 'login.php?action=signup', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></a>
                        <a href="<?php echo htmlspecialchars($baseurl . 'login.php?action=resend', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['resend_verification'] ?? 'Resend Verification Email', ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sign up Modal -->
        <div class="modal fade" id="signup" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="signupModalLabel"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="signup-feedback" class="mb-3"></div>
                        <form action="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>" method="post" id="signup-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="signup" value="1">
                            <input type="hidden" name="ajax" value="1">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? $baseurl, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (isset($_SESSION['cap_e']) && $_SESSION['cap_e'] === 'on' && $_SESSION['mode'] === 'reCAPTCHA'): ?>
                                <textarea id="g-recaptcha-response-signup" name="g-recaptcha-response" style="display: none;"></textarea>
                                <?php if ($_SESSION['recaptcha_version'] === 'v2'): ?>
                                    <div class="g-recaptcha mb-3" data-sitekey="<?php echo htmlspecialchars($_SESSION['recaptcha_sitekey'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-callback="onRecaptchaSuccessSignup"></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="signupUsername" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="signupUsername" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signupEmail" class="form-label"><?php echo htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="signupEmail" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signupFullname" class="form-label"><?php echo htmlspecialchars($lang['full_name'] ?? 'Full Name', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="signupFullname" name="full" value="<?php echo htmlspecialchars($_POST['full'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signupPassword" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="signupPassword" name="password" autocomplete="new-password" required>
                                </div>
                            </div>
                            <button type="submit" name="signup" id="signupSubmit" class="btn btn-primary btn-perky fw-bold w-100"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <a href="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['already_have_account'] ?? 'Already have an account?', ENT_QUOTES, 'UTF-8'); ?></a>
                        <a href="<?php echo htmlspecialchars($baseurl . 'login.php?action=resend', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['resend_verification'] ?? 'Resend Verification Email', ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <!-- // Header -->