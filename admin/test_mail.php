<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once '../includes/mail.php';
$result = send_mail(
    'test@example.com', // Replace with a valid recipient
    'Test Email from Paste',
    '<h1>Test Email</h1><p>This is a test email from Paste.</p>'
);
echo $result;
?>