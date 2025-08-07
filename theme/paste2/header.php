<!DOCTYPE html>
<html lang="<?php echo basename($default_lang, '.php'); ?>" data-bs-theme="dark">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if (isset($p_title)) { echo htmlspecialchars($p_title, ENT_QUOTES, 'UTF-8') . ' - '; } echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($des, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="keywords" content="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" />
    <link rel="shortcut icon" href="https://<?php echo $baseurl; ?>/theme/<?php echo $default_theme; ?>/img/favicon.ico">
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/monokai.min.css">
    <?php if (isset($ges_style)) { echo $ges_style; } ?>
    <link href="https://<?php echo $baseurl; ?>/theme/<?php echo $default_theme; ?>/css/paste.css" rel="stylesheet" type="text/css" />
    <style>
        .btn-perky {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .btn-perky:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>/archive"><?php echo htmlspecialchars($lang['archive'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>/my"><?php echo htmlspecialchars($lang['my-pastes'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>/login.php?logout"><?php echo htmlspecialchars($lang['logout'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal"><?php echo htmlspecialchars($lang['login'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#signupModal"><?php echo htmlspecialchars($lang['signup'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel"><?php echo htmlspecialchars($lang['login'], ENT_QUOTES, 'UTF-8'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label"><?php echo htmlspecialchars($lang['username'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo htmlspecialchars($lang['password'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" name="signin" class="btn btn-primary btn-lg fw-bold btn-perky w-100"><?php echo htmlspecialchars($lang['login'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="login.php?forgot"><?php echo htmlspecialchars($lang['forgot'], ENT_QUOTES, 'UTF-8'); ?></a> |
                        <a href="login.php?resend"><?php echo htmlspecialchars($lang['resend'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Signup Modal -->
    <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signupModalLabel"><?php echo htmlspecialchars($lang['signup'], ENT_QUOTES, 'UTF-8'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form action="login.php?signup" method="post">
                        <div class="mb-3">
                            <label for="signupUsername" class="form-label"><?php echo htmlspecialchars($lang['username'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" class="form-control" id="signupUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupEmail" class="form-label"><?php echo htmlspecialchars($lang['email'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="email" class="form-control" id="signupEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupFullname" class="form-label"><?php echo htmlspecialchars($lang['full_name'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" class="form-control" id="signupFullname" name="full" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupPassword" class="form-label"><?php echo htmlspecialchars($lang['password'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="password" class="form-control" id="signupPassword" name="password" required>
                        </div>
                        <button type="submit" name="signup" class="btn btn-primary btn-lg fw-bold btn-perky w-100"><?php echo htmlspecialchars($lang['signup'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>