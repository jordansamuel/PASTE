# Paste 2.1
[![Download PASTE](https://img.shields.io/sourceforge/dw/phpaste.svg)](https://sourceforge.net/projects/phpaste/files/latest/download)
[![Download PASTE](https://img.shields.io/sourceforge/dt/phpaste.svg)](https://sourceforge.net/projects/phpaste/files/latest/download)

Paste is forked from the original source pastebin.com used before it was bought.
The original source is available from the previous owner's **[GitHub repository](https://github.com/lordelph/pastebin)**

If you would like to contribute to developing Paste please feel free to find me on IRC.

A demo of Paste is available on our homepage at **[SourceForge](https://phpaste.sourceforge.io/demo)**

A public version can be found at **[PasteThis](http://pastethis.in)**

[![Frontend](http://i.imgur.com/UxZVxqo.png)](http://pastethis.in/)
[![Frontend](http://i.imgur.com/peFanYH.png)](http://pastethis.in/)


Requirements
===
* Apache 2.X
* PHP 5.3.7 (or later) with php-mcrypt & GD enabled [PHP5.5+ recommended]
* MySQL 5.x+

---

Install
===
* Create a database for PASTE.
* Upload all files to a webfolder
* Point your browser to http://yourpas.te/installation/install
* Input some settings, DELETE the install folder and you're ready to go.

---

Upgrading
===

* 2.0 to 2.1

Insert the schema changes to your database using the CLI:

mysql -uuser -ppassword databasename < upgrade/2.0-to-2.1.sql

or upload & import /upgrade/2.0-to-2.1.sql using phpMyAdmin

* 1.9 to 2.0

Run /upgrade/1.9-to.2.0.php

---

Any bugs can be reported at:
https://github.com/jordansamuel/PASTE/issues/new

You can find support on IRC by connecting to irc.collectiveirc.net in channel #PASTE

---
Clean URLs (mod_rewrite)
===
Set mod_rewrite in config.php to 1

---
Changelog
===
See **[CHANGELOG.md](https://github.com/jordansamuel/PASTE/blob/master/CHANGELOG.md)**

---
Paste now supports pastes of upto 4GB in size, and this is configurable in config.php

However, this relies on the value of post_max_size in your PHP configuration file.

```php
// Max paste size in MB. This value should always be below the value of
// post_max_size in your PHP configuration settings (php.ini) or empty errors will occur.
// The value we got on installation of Paste was: post_max_size = 1G
// Otherwise, the maximum value that can be set is 4000 (4GB)
$pastelimit = "1"; // 0.5 = 512 kilobytes, 1 = 1MB
```

To enable registration with OAUTH see this block in config.php

```php
// OAUTH (to enable, change to yes and edit)
$enablefb = "no";
$enablegoog = "no";

// "CHANGE THIS" = Replace with your details
// Facebook
define('FB_APP_ID', 'CHANGE THIS'); // Your application ID, see https://developers.facebook.com/docs/apps/register
define('FB_APP_SECRET', 'CHANGE THIS');    // What's your Secret key

// Google 
define('G_Client_ID', 'CHANGE THIS'); // Get a Client ID from https://console.developers.google.com/projectselector/apis/library
define('G_Client_Secret', 'CHANGE THIS'); // What's your Secret ID
define('G_Redirect_Uri', 'http://urltoyour/installation/oauth/google.php'); // Leave this as is
define('G_Application_Name', 'Paste'); // Make sure this matches the name of your application
```

Everything else can be configured using the admin panel.

---


Credits
===
Paul Dixon for developing **[the original pastebin.com](https://github.com/lordelph/pastebin)**

**[Pat O'Brien](https://github.com/poblabs)** for numerous contributions to the project.

Roberto Rodriguez (roberto.rodriguez.pino[AT]gmail.com) for PostgreSQL support on v1.9.

The Paste theme was built using Bootstrap, jQuery and various jQuery plugins for
present and future features, but we do try to keep it bloat free.
Icons are provided by FontAwesome.
