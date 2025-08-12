<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE> new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/ - https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(basename($default_lang ?? 'en.php', '.php'), ENT_QUOTES, 'UTF-8'); ?>" data-bs-theme="dark">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if (isset($p_title)) { echo htmlspecialchars($p_title ?? '', ENT_QUOTES, 'UTF-8') . ' - '; } echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($des ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="keywords" content="<?php echo htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($baseurl . 'theme/' . ($default_theme ?? 'default') . '/img/favicon.ico', ENT_QUOTES, 'UTF-8'); ?>" />
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/monokai.min.css">
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
                        <input class="form-control me-2" type="search" name="q" placeholder="<?php echo htmlspecialchars($lang['search'] ?? 'Search pastes...', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Search" value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="btn btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </div>
            <?php } ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="<?php //echo htmlspecialchars($baseurl ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Home"><?php //echo htmlspecialchars($lang['home'] ?? 'Home', ENT_QUOTES, 'UTF-8'); ?></a>
                    </li> -->
                    <?php
                    if (!isset($privatesite) || $privatesite != "on") {
                        if ($mod_rewrite == '1') {
                            echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($baseurl . 'archive', ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($lang['archive'] ?? 'Archive', ENT_QUOTES, 'UTF-8') . '</a></li>';
                        } else {
                            echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($baseurl . 'archive.php', ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($lang['archive'] ?? 'Archive', ENT_QUOTES, 'UTF-8') . '</a></li>';
                        }
                    }
                    ?>
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
                        <form method="GET" action="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="signin" value="1">
                            <div class="mb-3">
                                <label for="username" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" id="username" placeholder="<?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" class="form-control" id="password" placeholder="<?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="rememberme" name="rememberme" checked>
                                <label class="form-check-label" for="rememberme"><?php echo htmlspecialchars($lang['rememberme'] ?? 'Keep me signed in.', ENT_QUOTES, 'UTF-8'); ?></label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-3"><?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></button>
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
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <form method="GET" action="<?php echo htmlspecialchars($baseurl . 'login.php?action=signup', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="signup" value="1">
                            <div class="mb-3">
                                <label for="signupUsername" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="signupUsername" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signupEmail" class="form-label"><?php echo htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="signupEmail" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signupFullname" class="form-label"><?php echo htmlspecialchars($lang['full_name'] ?? 'Full Name', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="signupFullname" name="full" value="<?php echo htmlspecialchars($_POST['full'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signupPassword" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="signupPassword" name="password" required>
                                </div>
                            </div>
                            <button type="submit" name="signup" class="btn btn-primary btn-lg fw-bold w-100"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></button>
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