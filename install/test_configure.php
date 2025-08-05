<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
  $_POST['data_host'] = 'localhost';
  $_POST['data_name'] = 'database_name';
  $_POST['data_user'] = 'user_name';
  $_POST['data_pass'] = 'password';
  $_POST['data_sec'] = bin2hex(random_bytes(16));
  require_once 'configure.php';
  ?>