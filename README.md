Paste 3
=======

[![Download PASTE](https://a.fsdn.com/con/app/sf-download-button)](https://sourceforge.net/projects/phpaste/files/latest/download)

[![Download PASTE](https://img.shields.io/sourceforge/dw/phpaste.svg)](https://sourceforge.net/projects/phpaste/files/latest/download)
[![Download PASTE](https://img.shields.io/sourceforge/dt/phpaste.svg)](https://sourceforge.net/projects/phpaste/files/latest/download)

Paste is forked from the original source pastebin.com used before it was bought.
The original source is available from the previous owner's **[GitHub repository](https://github.com/lordelph/pastebin)**

A public version can be found **[here](https://paste.boxlabs.uk/)**

IRC: If you would like support or want to contribute to Paste connect to irc.collectiveirc.net in channel #PASTE

Any bugs can be reported at:
https://github.com/boxlabss/PASTE/issues/new

Requirements
===
 - PHP 7.4 or higher with `pdo_mysql`, `openssl`, and `curl` extensions
  - MySQL or MariaDB
  - Composer for dependency management
  - Web server (e.g., Apache) with HTTPS enabled

See docs/CHANGELOG
---

Install
===
* Create a database for PASTE.
* Upload all files to a webfolder
* Point your browser to http(s)://example.com/install
* Input some settings, DELETE the install folder and you're ready to go.
* To configure OAuth, first you need to use composer to install phpmailer and google api/oauth2 client
  - Install Composer dependencies:
    ```bash
    cd /oauth
    composer require google/apiclient:^2.12 league/oauth2-client:^2.7
    cd /mail
    composer require phpmailer/phpmailer:^6.9
    ```
   - Enter database details (host, name, user, password) and OAuth settings (enable or disable Google/Facebook).
   - This generates `config.php` with dynamic `G_REDIRECT_URI` based on your domain.
   
 **Set Up Google OAuth for User Logins**:
   - Go to [Google Cloud Console](https://console.developers.google.com).
   - Create a project and enable the Google+ API.
   - Create OAuth 2.0 credentials (Web application).
   - Set the Authorized Redirect URI to: `<baseurl>oauth/google.php` (e.g., `https://yourdomain.com/oauth/google.php`), where `<baseurl>` is from `site_info.baseurl`.
   - Update `config.php` with:
     ```php
     define('G_CLIENT_ID', 'your_client_id');
     define('G_CLIENT_SECRET', 'your_client_secret');
     ```
   - Ensure `enablegoog` is set to `yes` in `config.php`.
 **Set Up Gmail SMTP with OAuth2**:
   - In [Google Cloud Console](https://console.developers.google.com), enable the Gmail API.
   - Create or reuse OAuth 2.0 credentials.
   - Set the Authorized Redirect URI to: `<baseurl>oauth/google_smtp.php` (e.g., `https://yourdomain.com/oauth/google_smtp.php`), where `<baseurl>` is from `site_info.baseurl`.
   - Log in to `/admin/configuration.php` as an admin.
   - Enter the Client ID and Client Secret under "Google OAuth 2.0 Setup for Gmail SMTP".
   - Click "Authorize Gmail SMTP" to authenticate and save the refresh token in the `mail` table.
   - Configure SMTP settings (host: `smtp.gmail.com`, port: `587`, socket: `tls`, auth: `true`, protocol: `2`).

Development setup
===
* Set up git
* Fork this repository
* Create a database for PASTE.
* Check out the current master branch of your fork
* Point your browser to http(s)://example.com/install and follow the instructions on screen or import docs/paste.mysqlschema.sql into your database and copy docs/config.example.php to config.php and edit

Now you can start coding and send in pull requests.

---

Upgrading
===
3.0 schema changes
run the installer to update database
(backup first)


* 2.1 to 2.2
no changes to database

* 2.0 to 2.1

Insert the schema changes to your database using the CLI:
```
mysql -uuser -ppassword databasename < upgrade/2.0-to-2.1.sql
```
or upload & import upgrade/2.0-to-2.1.sql using phpMyAdmin

* 1.9 to 2.0

Run upgrade/1.9-to.2.0.php

---
Clean URLs
===
Set mod_rewrite in config.php to 1

For Apache, just use .htaccess

For Nginx, use the example config in **[docs/nginx.example.conf](https://github.com/jordansamuel/PASTE/blob/HEAD/docs/nginx.example.conf)**

---
Changelog
===
See **[docs/CHANGELOG.md](https://github.com/jordansamuel/PASTE/blob/HEAD/docs/CHANGELOG.md)**

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

* Paul Dixon for developing **[the original pastebin.com](https://github.com/lordelph/pastebin)**
* **[Pat O'Brien](https://github.com/poblabs)** for numerous contributions to the project.
* **[Viktoria Rei Bauer](https://github.com/ToeiRei)** for her contributions to the project.
* Roberto Rodriguez (roberto.rodriguez.pino[AT]gmail.com) for PostgreSQL support on v1.9.

The Paste theme was built using Bootstrap 5

