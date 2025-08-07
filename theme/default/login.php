<?php
/*
 * Paste <//github.com/jordansamuel/PASTE>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
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

            <?php if (isset($_GET['signup'])): ?>
                <!-- Signup Form -->
                <form action="login.php?signup" method="post">
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
                    <button type="submit" name="signup" class="btn btn-primary btn-lg fw-bold btn-perky w-100"><?php echo htmlspecialchars($lang['signup'] ?? 'Sign Up', ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            <?php else: ?>
                <!-- Login Form -->
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo htmlspecialchars($lang['password'] ?? 'Password', ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="signin" class="btn btn-primary btn-lg fw-bold btn-perky w-100"><?php echo htmlspecialchars($lang['login'] ?? 'Login', ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
                <div class="mt-3 text-center">
                    <a href="login.php?forgot"><?php echo htmlspecialchars($lang['forgot'] ?? 'Forgot Password', ENT_QUOTES, 'UTF-8'); ?></a> |
                    <a href="login.php?resend"><?php echo htmlspecialchars($lang['resend'] ?? 'Resend Verification', ENT_QUOTES, 'UTF-8'); ?></a> |
                    <a href="login.php?signup"><?php echo htmlspecialchars($lang['signup'] ?? 'Sign Up', ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($enablegoog == 'yes' || $enablefb == 'yes'): ?>
                <div class="mt-3 text-center">
                    <p><?php echo htmlspecialchars($lang['or_login_with'] ?? 'Or login with', ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($enablegoog == 'yes'): ?>
                        <a href="login.php?login=google" class="btn btn-danger btn-lg fw-bold btn-perky w-100 mb-2">
                            <i class="bi bi-google"></i> <?php echo htmlspecialchars($lang['login_with_google'] ?? 'Login with Google', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($enablefb == 'yes'): ?>
                        <a href="login.php?login=facebook" class="btn btn-primary btn-lg fw-bold btn-perky w-100">
                            <i class="bi bi-facebook"></i> <?php echo htmlspecialchars($lang['login_with_facebook'] ?? 'Login with Facebook', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endif; ?>
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
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>