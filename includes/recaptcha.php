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

declare(strict_types=1);

// Ensure session is started
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// --- Configurable defaults ---
$RECAPTCHA_MIN_SCORE = 0.8; // v3 minimum score
$RECAPTCHA_MAX_AGE   = 120; // seconds

/**
 * Called when verification fails
 * Sets $error and returns to caller for soft handling (no redirect)
 */
function _recaptcha_fail(string $reasonKey = 'recaptcha_failed'): void {
    global $lang, $error;
    $error = $lang[$reasonKey] ?? 'reCAPTCHA verification failed.';
}

// Verify with Google API
function recaptcha_siteverify(array $payload): ?array {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 10,
        ]
    ]);
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    if ($resp === false) return null;
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

// v3 check
function verify_recaptcha_v3(string $token, string $expectedAction): array {
    $secret = $_SESSION['recaptcha_secretkey'] ?? '';
    if ($token === '' || $secret === '') return ['ok' => false, 'why' => 'missing'];

    $data = recaptcha_siteverify([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    if (!$data || empty($data['success'])) {
        return ['ok' => false, 'why' => implode(',', (array)($data['error-codes'] ?? ['not_success']))];
    }

    if (!empty($data['challenge_ts'])) {
        $age = time() - strtotime($data['challenge_ts']);
        if ($age > ($GLOBALS['RECAPTCHA_MAX_AGE'] ?? 120)) {
            return ['ok' => false, 'why' => 'timeout'];
        }
    }

    $score = (float)($data['score'] ?? 0.0);
    if ($score < ($GLOBALS['RECAPTCHA_MIN_SCORE'] ?? 0.8)) {
        return ['ok' => false, 'why' => 'low_score', 'score' => $score];
    }

    return ['ok' => true, 'score' => $score, 'action' => ($data['action'] ?? null)];
}

// v2 check
function verify_recaptcha_v2(string $token): array {
    $secret = $_SESSION['recaptcha_secretkey'] ?? '';
    if ($token === '' || $secret === '') return ['ok' => false, 'why' => 'missing'];

    $data = recaptcha_siteverify([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    if (!$data || empty($data['success'])) {
        return ['ok' => false, 'why' => implode(',', (array)($data['error-codes'] ?? ['not_success']))];
    }
    return ['ok' => true];
}

/**
 * Main gate
 * - Call from controllers like: require_human('create_paste');
 * - Sets $error for soft handling on fail
 */
function require_human(string $expectedAction): void {
    global $error;

    // Debug overrides
    if (isset($_GET['forcefail'])) $_SESSION['forcefail'] = 1;
    if (isset($_GET['forcepass'])) $_SESSION['forcepass'] = 1;

    $consume = function(string $k): bool {
        $v = !empty($_SESSION[$k]);
        if ($v) unset($_SESSION[$k]);
        return $v;
    };

    if ($consume('forcepass')) {
        error_log("reCAPTCHA DEBUG: forced PASS for action={$expectedAction}");
        return;
    }
    if ($consume('forcefail')) {
        error_log("reCAPTCHA DEBUG: forced FAIL for action={$expectedAction}");
        _recaptcha_fail('recaptcha_failed');
        return;
    }

    $cap_e = $_SESSION['cap_e'] ?? 'off';
    $mode  = $_SESSION['mode'] ?? 'Normal';
    $ver   = $_SESSION['recaptcha_version'] ?? 'v2';

    if ($cap_e !== 'on' || $mode !== 'reCAPTCHA') {
        return;
    }

    $token = trim((string)(
        $_POST['g-recaptcha-response'] ??
        $_POST['recaptcha_token']      ?? ''
    ));

    if ($token === '') {
        _recaptcha_fail('recaptcha_missing');
        return;
    }

    if ($ver === 'v3') {
        $res = verify_recaptcha_v3($token, $expectedAction);
        if (empty($res['ok'])) {
            $reasonKey = match ($res['why'] ?? '') {
                'missing' => 'recaptcha_missing',
                'timeout', 'stale' => 'recaptcha_timeout',
                default => 'recaptcha_failed'
            };
            _recaptcha_fail($reasonKey);
        }
        return;
    }

    // v2
    $res = verify_recaptcha_v2($token);
    if (empty($res['ok'])) {
        $reasonKey = match ($res['why'] ?? '') {
            'missing' => 'recaptcha_missing',
            default   => 'recaptcha_failed'
        };
        _recaptcha_fail($reasonKey);
    }
}
