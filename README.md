<img src="static/images/logo.png" alt="Sharetronix">

[![Lint](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml/badge.svg)](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml)

Sharetronix is an open-source PHP platform for microblogging and social networking. It provides personal and group feeds, comments, likes, private messages, file and image attachments, search, an administration panel, themes, plugins, a mobile interface, and an API.

This repository maintains Sharetronix **3.6.2**. It keeps the platform running on current PHP versions and fixes security and reliability defects while preserving existing data, URLs, themes, and plugins. A rewrite or a new feature set is out of scope.

## Requirements

- Apache HTTP Server with `mod_rewrite`, and `.htaccess` overrides allowed for the application directory.
- PHP 7.2 or 8.4 with the `mbstring`, `mysqli`, and `gd` extensions (GD with FreeType support, for the CAPTCHA image), `short_open_tag = On`, and either the cURL extension or `allow_url_fopen = On`. An external CAPTCHA service needs the cURL extension.
- MySQL 5.7 or MariaDB 10.4 or later. MySQL 8.0 and later are not supported yet: `GROUPS` is a reserved word there, so the queries on the `groups` table fail, and sign-up, groups, and the background jobs do not work.

The application turns off the strict SQL modes, `ONLY_FULL_GROUP_BY`, `ANSI_QUOTES`, and `NO_BACKSLASH_ESCAPES` for its own database connections, so the server's `sql_mode` can stay at its default.

Installation, updates, and the core application flows have been tested with:

| Component | Versions |
| --- | --- |
| PHP | 7.2.34 and 8.4.19 (FastCGI) |
| Web server | Apache 2.4.66 |
| Database | MySQL 5.7.44; MariaDB 10.4.34, 10.5.29, 10.6.25, 10.11.15, 11.4.10, and 11.8.6 |

All listed MariaDB releases were tested with PHP 8.4, and MariaDB 10.11 and 11.8 also with PHP 7.2. PHP 8.5 is the next target. CI already checks syntax on it, but it has not been tested at runtime. Other PHP versions may work but are untested.

## Installation

1. Get the source code:

   ```sh
   git clone https://github.com/belomaxorka/sharetronix.git
   ```

2. Point an Apache virtual host at the checkout and enable `mod_rewrite`.
3. Create an empty MySQL or MariaDB database and a user with full privileges on it.
4. Give the web-server user write access to `system/`, `storage/`, `themes/`, `apps/`, and `.htaccess`. The installer lists any path it cannot write to.
5. Open `/install/` in a browser and follow the wizard. It checks the requirements, creates the database tables and the administrator account, and writes the configuration to `system/conf_main.php`.

The wizard saves the address you opened `/install/` with, including `http://` or `https://`, as the site address (`$C->SITE_URL`), so open it over HTTPS if the site should use HTTPS. To check `mod_rewrite`, it requests two test files from that address on the same server without verifying the TLS certificate, so a self-signed certificate works. If the server cannot connect to its own address, for example because of NAT or container port mapping, the check shows a warning and the installation can continue.

Behind a proxy or load balancer that terminates TLS and forwards plain HTTP to Apache, PHP sees an `http://` request, so the wizard saves an `http://` address. After the installation, change `$C->SITE_URL` in `system/conf_main.php` to the `https://` address.

## Background tasks

By default, pages viewed in a browser trigger the periodic jobs with a background request to `/cron`. On a server with cron, run the worker every minute instead:

```sh
* * * * * /path/to/php /path/to/sharetronix/system/cronjobs/worker.php
```

Then set `$C->CRONJOB_IS_INSTALLED = TRUE;` in `system/conf_main.php`. See [system/cronjobs/readme.txt](system/cronjobs/readme.txt).

## Cache

`$C->CACHE_MECHANISM` in `system/conf_main.php` selects the cache: `filesystem` (the default, stored in `system/cache/`), `memcached`, or `redis`. The memcached cache requires the `memcached` or `memcache` PHP extension. Redis requires the [phpredis](https://github.com/phpredis/phpredis) extension and uses `$C->CACHE_REDIS_HOST`, `$C->CACHE_REDIS_PORT`, `$C->CACHE_REDIS_PASS`, and `$C->CACHE_REDIS_DB` (defaults: `127.0.0.1`, `6379`, no password, database 0). Without the required extension the file cache is used. While the memcached or Redis server is unreachable, pages are served without the cache, and every such request logs one line about it.

## CAPTCHA

Sign-up, password recovery, and the contact form ask for a CAPTCHA. By default it is a built-in image, generated on the server with [Gregwar Captcha](https://github.com/Gregwar/Captcha) (`system/libraries/gregwar-captcha/`); it needs no account and sends nothing to other services. The code is valid for 15 minutes and for one check, right or wrong; the form has a link that shows another image.

Instead of the image, `system/conf_main.php` can select an external service. Create a site key and a secret key for your domain in the service's console and set:

```php
$C->CAPTCHA_PROVIDER   = 'turnstile';
$C->CAPTCHA_SITE_KEY   = '...';
$C->CAPTCHA_SECRET_KEY = '...';
```

| `$C->CAPTCHA_PROVIDER` | Service |
| --- | --- |
| `'image'` | Built-in image (the default) |
| `'recaptcha2'` | [Google reCAPTCHA](https://developers.google.com/recaptcha) v2, "I'm not a robot" checkbox |
| `'recaptcha3'` | Google reCAPTCHA v3, no widget; accepts a score of at least `$C->CAPTCHA_MIN_SCORE` (default `0.5`) |
| `'turnstile'` | [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) |
| `'hcaptcha'` | [hCaptcha](https://docs.hcaptcha.com/) |
| `'yandex'` | [Yandex SmartCaptcha](https://yandex.cloud/en/docs/smartcaptcha/) |

Without both keys, or with an unknown name, the built-in image is used, and a line saying why is logged when a form is sent. The server checks every answer with the service over HTTPS (timeouts of 5 seconds to connect and 10 seconds in total). If the service cannot be reached, answers with an error, or rejects the keys, the form is not accepted, the visitor is asked to try again later, and a line starting with `Sharetronix captcha:` is logged. On Windows, PHP has no CA certificates for cURL unless `curl.cainfo` in `php.ini` points to a bundle such as [cacert.pem](https://curl.se/docs/caextract.html); without it every check fails with an SSL certificate error.

Some services let visitors through on their own: according to Google, reCAPTCHA checks above the free monthly quota (10,000) answer `success`, and SmartCaptcha accepts every token while the Yandex Cloud billing account is not active.

Privacy: with an external service, the sign-up, password recovery, and contact pages load the service's JavaScript, so the service receives the visitor's IP address, browser data, and its own cookies, and may process them outside your jurisdiction. Mention it in your privacy policy if that applies to you. Sharetronix itself does not send the visitor's IP address with the check.

Themes that override the blocks `registration`, `user-signup-email`, or `user-forgotten-pass-step1` keep working. The CAPTCHA markup (the image with its link, or the service's widget) is in `{%captcha_image%}`; show the `captcha_word` input only when `$D->captcha_widget` is false (`$D->use_google_recaptcha` is kept as its old name). The image is served by `/captcha?namespace=signup`, `forgotten_pass`, or `contacts`.

## Debugging

Debug mode is enabled for client IP addresses listed in `system/conf_main.php`:

```php
$C->DEBUG_USERS = array('203.0.113.10');
```

Requests from these addresses display PHP errors of all levels and, when a database query fails, the query and the MySQL error. For everyone else the application turns `display_errors` off, whatever `php.ini` says, so errors only go to the PHP error log. The installer (`/install/`) always displays errors. The address is the one PHP receives as `REMOTE_ADDR`: behind a reverse proxy or CDN this is the proxy's address, and listing it enables debug mode for every visitor; a browser on the server itself may connect as `::1` rather than `127.0.0.1`. Do not leave debug mode enabled on a production site.

With `$C->DEBUG_CONSOLE = TRUE;` pages in debug mode show a small **Debug** link at the bottom right. It opens a window with the page generation time, memory use, and every SQL query and cache call of the request.

Failed database queries, failed e-mail deliveries, unreachable cache servers, failed CAPTCHA checks by an external service, and service answers that cannot be encoded are logged as lines starting with `Sharetronix`. Make sure `log_errors` is on and `error_log` points to a file you can read. The command-line cron worker also prints errors to its output.

## Plugins, themes, and languages

A plugin is a folder in `apps/` with a `manifest.json`, whose `plugin_name` is the folder name, and a `plugin.php` that defines a class of that name. To add one, copy its folder to `apps/`, open **Administration → Plugins**, and install it. Installing first shows what the plugin's `installer.php` will change in the database, if it has one, and asks for confirmation. An installed plugin can be disabled and enabled again without losing its data. Uninstalling undoes its database changes and leaves its files in `apps/`; delete them by hand.

A plugin that cannot be loaded, for example because its code does not run on the server's PHP version or its folder is missing, is skipped and reported in the PHP error log; the Plugins page shows it as not loaded. So is an event handler that fails with an error. If a plugin still breaks every page, for example because it runs out of memory, add `$C->PLUGINS_DISABLED = TRUE;` to `system/conf_main.php`: no plugin runs, and the Plugins page can still uninstall it. Remove the line afterwards.

A theme is a folder in `themes/` with a `manifest.json`. Copy it there and select it in **Administration → Themes**; to remove a theme, select another one and delete its folder.

A language pack is a folder in `system/languages/` with a `language.php`. Copy it there; **Administration → Languages** lists the installed packs, and the general settings choose the default language.

### Writing a plugin

This fork keeps the plugin interface of Sharetronix 3.x and does not extend it. A plugin folder `apps/<name>/` contains:

- `manifest.json` with `plugin_name`, which must be the folder name (letters, digits, and `_`); the Plugins page also shows `version`, `author`, and `descr`.
- `plugin.php` with `class <name> extends plugin`. A public method named after an event runs when the core fires the event and receives the event's arguments as one array (`$args[0]`, `$args[1]`). `$this->setVar('<placeholder>', $html)` adds HTML to a `{%placeholder%}` of the templates being rendered; `'replace'` as the third argument replaces its content. The base class also provides `$this->db2`, `$this->user`, `$this->network`, `$this->page`, `$this->cache`, `getCurrentController()`, `getCurrentTab()`, and `isMobileRegime()`.
- Optionally `installer.php` with `class MyInstaller extends Installer`, whose `up()` and `down()` create and remove the plugin's tables and fields through the helpers in `system/plugin_installer/`. The Plugins page shows what they will change before it runs them.
- Optionally `system/controllers/` for pages at `/plugin/<name>/...` (signed-in users only), `system/controllers/ajax/<action>.php` for `/services/<name>/<action>`, `system/cronjobs/php_<interval>/*.php` for jobs that run after the core jobs of that interval, and `static/js/*.js` and `static/css/*.css`, which the pages include.

All of these work only while the plugin is installed and enabled. A handler of an event marked *veto* can stop the action with `$this->setVar('<event>_error', $message)`; the core then cancels it and shows the message where the page or the AJAX answer has room for one. A handler of the query events returns SQL, which the core runs as it is.

| Event | When | Arguments |
| --- | --- | --- |
| `onPageLoad` | Every page built from templates | — |
| `onPageSubmit` (veto) | Before the contacts, group settings, new group, group category, invitation, sign-in, or sign-up form is processed | — |
| `onUserSettingsSubmit` (veto) | Before a user settings form is saved | — |
| `onAdminSettingsSubmit` (veto) | Before an administration form is saved | — |
| `onPostSave`, `onPostDelete` (veto) | Before a post is saved or deleted | The `newpost` or `post` object |
| `onPostCommentSave`, `onPostCommentDelete` (veto) | Before a comment is saved or deleted | The `newpostcomment` or `postcomment` object |
| `onUserFollow`, `onUserUnfollow` (veto) | Before the signed-in user follows or unfollows someone | The signed-in `user` object, then the other user |
| `onGroupJoin`, `onGroupLeave` (veto) | Before the signed-in user joins or leaves a group | The group |
| `onPostLoad`, `onPostCommentLoad` | A post or comment is rendered | The post or comment |
| `onSingleUserLoad`, `onSingleGroupLoad` | A user or group is rendered in a list | The user or group |
| `onPageSetQuery`, `onPageSetCountQuery`, `onPageSetQueryOrder` | A feed, the group list, or the member list is opened with a `tab:` the core does not know | — (return the query, the count query, or the `ORDER BY` clause) |

## Upgrading

The updater updates Sharetronix 3.0 and later. Releases 1.x and 2.x used a different database layout and upload folders, which it cannot convert.

1. Back up the database, `system/conf_main.php`, and `storage/`, and make sure the backup can be restored. Restore a database backup into an emptied database: an update that failed half-way may have created tables that the backup does not contain, and restoring over them leaves them in place.
2. Make sure PHP loads the `mbstring` extension. Sharetronix 3.6.3 no longer ships a replacement for it: without the extension, pages, the updater, and the cron worker stop with a message that it is missing.
3. Replace the application files with the new version: delete the old files instead of copying the new ones over them, but keep `system/conf_main.php`, `storage/`, and any themes, plugins, or languages you installed. Files that a release removes must not stay behind; for example, a left-over `system/cronjobs/php_6h/7-clean_request_tokens.php` from 3.6.2 stops the cron worker once the 3.6.3 updater has dropped the OAuth tables. The updater deletes the files that newer releases no longer ship and lists any file it cannot delete; delete those by hand.
4. Sign in as an administrator, open `/install/`, and confirm the update. The updater applies the database changes for the new version.
5. Clear `system/cache/` and `system/cache_html/`, or wait until cached data expires (one hour by default) and the daily cron job clears the cached page HTML.

Outside debug mode, PHP errors are no longer displayed to visitors, even when `display_errors` is on in `php.ini`. To see them in the browser, add your address to `$C->DEBUG_USERS` (see [Debugging](#debugging)).

The APC and MySQL heap (`mysqlheap`) cache drivers have been removed. If `system/conf_main.php` sets `$C->CACHE_MECHANISM` to `'apc'` or `'mysqlheap'`, the application now uses the file cache in `system/cache/`, which must stay writable; `'memcached'` and `'redis'` remain available.

The bundled Securimage CAPTCHA, the reCAPTCHA v1 integration, and the image CAPTCHA of the contact form have been replaced by the built-in image described in [CAPTCHA](#captcha). The updater deletes `system/libraries/securimage/`, `system/helpers/func_recaptchalib.php`, and `system/helpers/func_captcha.php`; old images `storage/tmp/captcha_*.gif` of the contact form can be deleted by hand. Existing configurations need no change: without the new settings the image is used. `$C->GOOGLE_CAPTCHA_PUBLIC_KEY` and `$C->GOOGLE_CAPTCHA_PRIVATE_KEY` are ignored and can be removed; Google shut reCAPTCHA v1 down in 2018, so for reCAPTCHA create new v2 or v3 keys and set `$C->CAPTCHA_PROVIDER`. Plugins that called `generate_captcha()` or Securimage must use the `captcha` class instead.

New accounts no longer take their picture from Gravatar during sign-up; they start with the default picture. Members can choose **Use My Gravatar** on the picture settings page (`/settings/avatar`), which downloads their Gravatar over HTTPS (cURL with a CA bundle, see [CAPTCHA](#captcha)) and sends a SHA-256 hash of their e-mail address to gravatar.com. Existing pictures are not changed.

## Known limitations

- Sign-in through Twitter and Facebook and other integrations with retired third-party services are untested and may not work.
- On PHP 8.1 and later, dates use English month and day names regardless of the language pack ([#9](https://github.com/belomaxorka/sharetronix/issues/9)).

Other known defects are tracked in [GitHub issues](https://github.com/belomaxorka/sharetronix/issues).

## Source layout

- `index.php`, `.htaccess`, `web.config` — application entry point and server configuration.
- `system/` — application code, libraries, languages, and background jobs.
- `themes/`, `static/` — templates, styles, scripts, and images.
- `install/` — installation and update wizard.
- `apps/`, `storage/` — extensions and uploaded files.

## Contributing

Bug reports and pull requests are welcome. When reporting a defect, include the PHP, database, and web-server versions, the steps to reproduce, and the expected and actual behavior. Remove passwords, tokens, and personal data from logs.

Keep each pull request focused on one problem, follow the conventions of the surrounding code, and preserve author credits and third-party notices. Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/), for example `fix(auth): preserve the session after signing in`. Describe how you verified the change.

Every pull request and every push to `main` runs a syntax check of all PHP files on PHP 7.2, 8.4, and 8.5. To check a file locally:

```sh
php -d short_open_tag=On -l path/to/file.php
```

## License

Sharetronix is licensed under the [GNU Affero General Public License, version 3](LICENSE.txt) (`AGPL-3.0-only`). Original author credits remain in the source files. Bundled third-party components keep their own licenses and copyright notices.
