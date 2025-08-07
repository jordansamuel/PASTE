<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once 'mail/vendor/autoload.php';
use Google\Client;
$client = new Client();
$client->setClientId('YOUR_CLIENT_ID');
$client->setClientSecret('YOUR_CLIENT_SECRET');
$client->setRedirectUri('https://yourdomain/mail/PHPMailer/get_oauth_token.php');
$client->addScope('https://mail.google.com/');
$client->setAccessType('offline');
$refresh_token = 'YOUR_REFRESH_TOKEN';
try {
    $accessToken = $client->fetchAccessTokenWithRefreshToken($refresh_token);
    var_dump($accessToken);
    if (isset($accessToken['access_token'])) {
        echo "Access token: " . substr($accessToken['access_token'], 0, 10) . "...\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>