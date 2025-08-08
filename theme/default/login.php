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
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4"><?php echo htmlspecialchars($lang['login/register'] ?? 'Login / Register', ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['action']) && $_GET['action'] === 'reset' && isset($_GET['username']) && isset($_GET['code'])): ?>
                <!-- Password Reset Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($lang['reset_password'] ?? 'Reset Password', ENT_QUOTES, 'UTF-8'); ?></h5>
                        <form method="POST" action="<?php echo htmlspecialchars($baseurl . 'login.php?action=reset&username=' . urlencode($_GET['username']) . '&code=' . urlencode($_GET['code']), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo htmlspecialchars($lang['new_password'] ?? 'New Password', ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg fw-bold w-100"><?php echo htmlspecialchars($lang['reset_password'] ?? 'Reset Password', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>
                </div>
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'forgot'): ?>
                <!-- Forgot Password Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($lang['forgot_password'] ?? 'Forgot Password', ENT_QUOTES, 'UTF-8'); ?></h5>
                        <form method="POST" action="<?php echo htmlspecialchars($baseurl . 'login.php?action=forgot', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg fw-bold w-100"><?php echo htmlspecialchars($lang['send_reset_link'] ?? 'Send Reset Link', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>
                </div>
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'resend'): ?>
                <!-- Resend Verification Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($lang['resend_verification'] ?? 'Resend Verification Email', ENT_QUOTES, 'UTF-8'); ?></h5>
                        <form method="POST" action="<?php echo htmlspecialchars($baseurl . 'login.php?action=resend', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg fw-bold w-100"><?php echo htmlspecialchars($lang['resend_verification'] ?? 'Resend Verification Email', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>
                </div>
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'signup'): ?>
                <!-- Signup Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></h5>
                        <form action="<?php echo htmlspecialchars($baseurl . 'login.php?action=signup', ENT_QUOTES, 'UTF-8'); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="signup" value="1">
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
                            <button type="submit" name="signup" class="btn btn-primary btn-lg fw-bold w-100"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Login Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></h5>
                        <form action="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="signin" value="1">
                            <div class="mb-3">
                                <label for="username" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="signin" class="btn btn-primary btn-lg fw-bold w-100"><?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="<?php echo htmlspecialchars($baseurl . 'login.php?action=forgot', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['forgot_password'] ?? 'Forgot Password', ENT_QUOTES, 'UTF-8'); ?></a> |
                            <a href="<?php echo htmlspecialchars($baseurl . 'login.php?action=resend', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['resend_verification'] ?? 'Resend Verification', ENT_QUOTES, 'UTF-8'); ?></a> |
                            <a href="<?php echo htmlspecialchars($baseurl . 'login.php?action=signup', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lang['signup'] ?? 'Register', ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($enablegoog == 'yes' || $enablefb == 'yes'): ?>
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <p><?php echo htmlspecialchars($lang['or_login_with'] ?? 'Or login with', ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($enablegoog == 'yes'): ?>
                            <a href="<?php echo htmlspecialchars($baseurl . 'login.php?login=google', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger btn-lg fw-bold w-100 mb-2">
                                <i class="bi bi-google"></i> <?php echo htmlspecialchars($lang['login_with_google'] ?? 'Login with Google', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($enablefb == 'yes'): ?>
                            <a href="<?php echo htmlspecialchars($baseurl . 'login.php?login=facebook', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg fw-bold w-100">
                                <i class="bi bi-facebook"></i> <?php echo htmlspecialchars($lang['login_with_facebook'] ?? 'Login with Facebook', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
    body {
        background-color: #f8f9fa;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .container {
        max-width: 500px;
    }
    .btn-perky {
        transition: transform 0.2s ease-in-out, background-color 0.2s ease-in-out;
    }
    .btn-perky:hover {
        transform: scale(1.05);
    }
    .form-label {
        font-weight: 500;
    }
    .alert {
        border-radius: 0.25rem;
    }
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>