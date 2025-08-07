<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(basename($default_lang, ".php"), ENT_QUOTES, 'UTF-8');?>" data-bs-theme="dark">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if(isset($p_title)) { echo htmlspecialchars($p_title, ENT_QUOTES, 'UTF-8') . ' - ';} echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($des, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="keywords" content="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" />
	<link rel="shortcut icon" href="<?php echo htmlspecialchars($baseurl . 'theme/' . $default_theme . '/img/favicon.ico', ENT_QUOTES, 'UTF-8');?>" />
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/monokai.min.css">
	<?php if (isset($ges_style)) { echo $ges_style; };?>
	<link href="<?php echo htmlspecialchars($baseurl . 'theme/' . $default_theme . '/css/paste.css', ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" type="text/css" />
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg bg-dark">
        <div class="container-xl">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8');?>"><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8');?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8');?>" aria-label="Home"><i class="bi bi-clipboard"></i></a>
                    </li>
				  <?php
				  if (!isset($privatesite) || $privatesite != "on") {
					if ($mod_rewrite == '1') {
					  echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($baseurl . 'archive', ENT_QUOTES, 'UTF-8') . '">Archive</a></li>';
					} else {
					  echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($baseurl . 'archive.php', ENT_QUOTES, 'UTF-8') . '">Archive</a></li>';
					}
				  }
				  ?>
                </ul>
                <ul class="navbar-nav navbar-nav-guest">
                    <li class="nav-item dropdown">
				<?php if(isset($_SESSION['token'])) {
				  echo '<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><b>' . htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') . '</b></a>';
				} else {
				  echo '<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><b>Guest</b></a>';
				}
				?>
					
                        <ul class="dropdown-menu dropdown-menu-end">
						  <?php if(isset($_SESSION['token'])) {
							echo '<li><h6 class="dropdown-header">My Account</h6></li>';
							if ($mod_rewrite == '1') {
							  echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'user/' . urlencode($_SESSION['username']), ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-clipboard"></i> Pastes</a></li>';
							  echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'profile', ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-user"></i> Settings</a></li>';
							} else {
							  echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'user.php?user=' . urlencode($_SESSION['username']), ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-clipboard"></i> Pastes</a></li>';
							  echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . 'profile.php', ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-user"></i> Settings</a></li>';
							}
							echo '<li><hr class="dropdown-divider"></li>';
							echo '<li><a class="dropdown-item" href="' . htmlspecialchars($baseurl . '?logout', ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-sign-out"></i> Logout</a></li>';
						  } else {
							echo '<li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#signin">Login</a></li>';
							echo '<li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#signup">Register</a></li>';
						  }
						  ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sign in Modal -->
    <div class="modal fade" id="signin" tabindex="-1" aria-labelledby="signinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="signinModalLabel">Login</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="<?php echo htmlspecialchars($baseurl . 'login.php?login', ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="rememberme" name="rememberme" checked>
                            <label class="form-check-label" for="rememberme"><?php echo htmlspecialchars($lang['rememberme'] ?? 'Remember Me', ENT_QUOTES, 'UTF-8'); ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3">Login</button>
                        <a class="btn btn-outline-light w-100 mt-2" href="<?php echo htmlspecialchars($baseurl . 'login.php?forgot', ENT_QUOTES, 'UTF-8'); ?>">Forgot Password?</a>
						<input type="hidden" name="signin" value="<?php echo htmlspecialchars(md5($date.$ip), ENT_QUOTES, 'UTF-8'); ?>" />
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo htmlspecialchars($baseurl . 'login.php?signup', ENT_QUOTES, 'UTF-8'); ?>">Register</a>
                    <a href="<?php echo htmlspecialchars($baseurl . 'login.php?resend', ENT_QUOTES, 'UTF-8'); ?>">Resend verification email</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sign up Modal -->
    <div class="modal fade" id="signup" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="signupModalLabel">Register</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($baseurl . 'login.php?signup', ENT_QUOTES, 'UTF-8'); ?>" method="post">
                        <div class="mb-3">
                            <label for="signupUsername" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" class="form-control" id="signupUsername" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupEmail" class="form-label"><?php echo htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="email" class="form-control" id="signupEmail" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupFullname" class="form-label"><?php echo htmlspecialchars($lang['full_name'] ?? 'Full Name', ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" class="form-control" id="signupFullname" name="full" value="<?php echo htmlspecialchars($_POST['full'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="signupPassword" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="password" class="form-control" id="signupPassword" name="password" required>
                        </div>
                        <button type="submit" name="signup" class="btn btn-primary btn-lg fw-bold btn-perky w-100"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo htmlspecialchars($baseurl . 'login.php?signin', ENT_QUOTES, 'UTF-8'); ?>">Already have an account?</a>
                    <a href="<?php echo htmlspecialchars($baseurl . 'login.php?resend', ENT_QUOTES, 'UTF-8'); ?>">Resend verification email</a>
                </div>
            </div>
        </div>
    </div>
	<!-- // Header -->