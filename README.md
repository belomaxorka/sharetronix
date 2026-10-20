<img src="static/images/logo.png" alt="Sharetronix">

[![Lint](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml/badge.svg)](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml)

Sharetronix is an open-source PHP platform for microblogging and social networking. It provides personal and group feeds, comments, likes, private messages, file and image attachments, search, an administration panel, themes, plugins, a mobile interface, and an API.

This repository maintains Sharetronix **3.6.2**. It keeps the platform running on current PHP versions and fixes security and reliability defects while preserving existing data, URLs, themes, and plugins. A rewrite or a new feature set is out of scope.

## Requirements

- Apache HTTP Server with `mod_rewrite`, and `.htaccess` overrides allowed for the application directory.
- PHP 7.2, 8.4, or 8.5 with the `mbstring`, `mysqli`, and `gd` extensions (GD with FreeType support, for the CAPTCHA image), `short_open_tag = On`, and either the cURL extension or `allow_url_fopen = On`. An external CAPTCHA service needs the cURL extension, and installing a plugin that has database tables needs the `pdo_mysql` extension.
- MySQL 5.7, 8.0, or 8.4, or MariaDB 10.4 or later.

The application turns off the strict SQL modes, `ONLY_FULL_GROUP_BY`, `ANSI_QUOTES`, and `NO_BACKSLASH_ESCAPES` for its own database connections, together with `NO_ZERO_DATE`, `NO_ZERO_IN_DATE`, and `ERROR_FOR_DIVISION_BY_ZERO`, which only work with strict mode, so the server's `sql_mode` can stay at its default.

Text is stored as `utf8mb4`, so posts, comments, messages, and names can contain emoji. All tables and connections use the collation `utf8mb4_unicode_520_ci`, which every supported server has, whatever character set the database was created with; the installer stops with a message on a server without it.

MySQL 8.0 and 8.4 create database accounts with the `caching_sha2_password` plugin by default. PHP 8.4 and 8.5 connect to such accounts, also without TLS. The database driver of PHP 7.2 does not support that plugin, and the installer then reports "Cannot connect - please check host, username and password." With PHP 7.2, create the database user with `mysql_native_password`, for example `CREATE USER 'sharetronix'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';` (or change an existing user with `ALTER USER`). MySQL 8.4 does not load that plugin by default: add `mysql_native_password=ON` to the `[mysqld]` section of the server configuration and restart the server first.

Installation, updates, and the core application flows have been tested with:

| Component | Versions |
| --- | --- |
| PHP | 7.2.34, 8.4.19, and 8.5.4 (FastCGI) |
| Web server | Apache 2.4.66 |
| Database | MySQL 5.7.44, 8.0.45, and 8.4.8; MariaDB 10.4.34, 10.5.29, 10.6.25, 10.11.15, 11.4.10, and 11.8.6 |

All listed database releases were tested with PHP 8.4; MySQL 5.7 and 8.4 and MariaDB 10.11 and 11.8 also with PHP 7.2, and MySQL 8.4 and MariaDB 11.8 also with PHP 8.5, including the update and the command-line cron worker. MySQL 8.0 and 8.4 were tested with `mysql_native_password` accounts, and MySQL 8.4 with PHP 8.4 also with a `caching_sha2_password` account. Other PHP versions may work but are untested.

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

Run the worker as the web-server user (for example in `crontab -u www-data -e`) or as root, not as another account: it writes cache entries into `system/cache/` that the web server must read, and deletes old files in `system/cache_html/` and `system/tmp/`. Then set `$C->CRONJOB_IS_INSTALLED = TRUE;` in `system/conf_main.php`. See [system/cronjobs/readme.txt](system/cronjobs/readme.txt).

## Cache

`$C->CACHE_MECHANISM` in `system/conf_main.php` selects the cache: `filesystem` (the default, stored in `system/cache/`), `memcached`, or `redis`. The memcached cache requires the `memcached` or `memcache` PHP extension. Redis requires the [phpredis](https://github.com/phpredis/phpredis) extension and uses `$C->CACHE_REDIS_HOST`, `$C->CACHE_REDIS_PORT`, `$C->CACHE_REDIS_PASS`, and `$C->CACHE_REDIS_DB` (defaults: `127.0.0.1`, `6379`, no password, database 0). Without the required extension the file cache is used. The memcached and Redis keys contain `$C->CACHE_KEYS_PREFIX`, the version, and a hash of the database host and name, so installations that share a server keep their entries apart. While the memcached or Redis server is unreachable or does not answer within one second, pages are served without the cache, and every such request logs one line about it.

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

## Spam checks

Before it creates an account, the sign-up checks the visitor's e-mail address and IP address with [StopForumSpam](https://www.stopforumspam.com/), a shared list of addresses reported for forum and blog spam, and refuses the sign-up if either has been reported with a confidence of at least `$C->STOPFORUMSPAM_MIN_CONFIDENCE` percent (default `10`):

```php
$C->STOPFORUMSPAM_MIN_CONFIDENCE = 10;
```

StopForumSpam calculates the confidence from the number of reports and the time since the last one. In October 2026 an address reported once in the past week scored 18, addresses last reported more than ten days ago scored less than 10 even with a dozen reports, and addresses reported dozens of times in the past days, or blacklisted, more than 90. A higher minimum refuses fewer visitors. `0` refuses every address that has ever been reported (Sharetronix 3.6.2 did so for IP addresses), which also turns away visitors who share or were later given an address that was once used for spam.

The check has timeouts of 3 seconds to connect and 5 seconds in total. If the service cannot be reached or answers with an error, or PHP has no cURL extension, the sign-up goes on without the check and a line starting with `Sharetronix spam check:` is logged. StopForumSpam's [terms](https://www.stopforumspam.com/legal) are written for non-commercial use and limit the number of queries per day; read them before you use the check on a commercial site. `$C->STOPFORUMSPAM_ENABLED = FALSE;` turns the check off.

With `$C->HONEYPOT_ACCESS_KEY` set to an access key of [Project Honey Pot](https://www.projecthoneypot.org/), the sign-up also looks the visitor's IPv4 address up in its http:BL list through DNS and refuses every listed address except search engines.

Privacy: StopForumSpam receives the e-mail address and IP address of everyone who signs up, and Project Honey Pot the IP address; mention them in your privacy policy if that applies to you. The address checked is the one PHP receives as `REMOTE_ADDR`: behind a reverse proxy or CDN, configure the web server to pass on the visitor's address (for example with Apache `mod_remoteip` or the nginx `realip` module), or every visitor is checked with the proxy's address.

## Mobile version

Browsers whose User-Agent names a mobile device (most phones and Android tablets), the `m.` subdomain, and addresses under `/m/` get the mobile version. Without an account, visitors can sign in, sign up, recover a password, and use the contact form and the terms page there. These pages run the same code as the desktop ones, so the CAPTCHA and the spam checks apply. They look like the mobile sign-in page: the layouts `header-outside` and `footer-outside` and the blocks `login`, `registration`, `user-signup-email`, `user-forgotten-pass-step1`, and `user-forgotten-pass-step2` in `static/templates/mobile/`, which a theme can override in `templates/mobile/`. The links in the sign-up and password recovery e-mails use the normal address, which a phone opens in the mobile version.

## Debugging

Debug mode is enabled for client IP addresses listed in `system/conf_main.php`:

```php
$C->DEBUG_USERS = array('203.0.113.10');
```

Requests from these addresses display PHP errors of all levels and, when a database query fails, the query and the MySQL error. For everyone else the application turns `display_errors` off, whatever `php.ini` says, so errors only go to the PHP error log. The installer (`/install/`) always displays errors. The address is the one PHP receives as `REMOTE_ADDR`: behind a reverse proxy or CDN this is the proxy's address, and listing it enables debug mode for every visitor; a browser on the server itself may connect as `::1` rather than `127.0.0.1`. Do not leave debug mode enabled on a production site.

With `$C->DEBUG_CONSOLE = TRUE;` pages in debug mode show a small **Debug** link at the bottom right. It opens a window with the page generation time, memory use, and every SQL query and cache call of the request.

Failed database queries, failed e-mail deliveries, unreachable cache servers, failed CAPTCHA checks by an external service, and service answers that cannot be encoded are logged as lines starting with `Sharetronix`. Make sure `log_errors` is on and `error_log` points to a file you can read. The command-line cron worker also prints errors to its output.

## Plugins, themes, and languages

A plugin is a folder in `apps/` with a `manifest.json`, whose `plugin_name` is the folder name, and a `plugin.php` that defines a class of that name. To add one, copy its folder to `apps/`, open **Administration → Plugins**, and install it. Installing first shows what the plugin's `installer.php` will change in the database, if it has one, and asks for confirmation. If the database refuses one of its statements, the installation stops with the error and the plugin stays uninstalled: the installer drops the tables and the columns that `installer.php` created in that run, puts their entries in the `plugins_tables` registry back, and lists any other statement of `installer.php` that had already been carried out, which it cannot undo. An installed plugin can be disabled and enabled again without losing its data. Uninstalling undoes its database changes and leaves its files in `apps/`; delete them by hand.

A plugin that cannot be loaded, for example because its code does not run on the server's PHP version or its folder is missing, is skipped and reported in the PHP error log; the Plugins page shows it as not loaded. So is an event handler that fails with an error. If a plugin still breaks every page, for example because it runs out of memory, add `$C->PLUGINS_DISABLED = TRUE;` to `system/conf_main.php`: no plugin runs, and the Plugins page can still uninstall it. Remove the line afterwards.

A theme is a folder in `themes/` with a `manifest.json`. Copy it there and select it in **Administration → Themes**; to remove a theme, select another one and delete its folder.

A language pack is a folder in `system/languages/` with a `language.php`. Copy it there; **Administration → Languages** lists the installed packs, and the general settings choose the default language. The English pack, `system/languages/en/`, is the reference: a text or a whole language file that another pack lacks is shown in English, also in the e-mails sent in a member's language, so a pack made for an older release keeps working. A plugin brings its own texts in `apps/<name>/languages/`, with a folder of the same name for each language it translates (see [Plugin texts and translations](#plugin-texts-and-translations)).

### Writing a plugin

This fork keeps the plugin interface of Sharetronix 3.x and adds only the plugin's own language files. A plugin folder `apps/<name>/` contains:

- `manifest.json` with `plugin_name`, which must be the folder name (letters, digits, and `_`); the Plugins page also shows `version`, `author`, and `descr`.
- `plugin.php` with `class <name> extends plugin`. A public method named after an event runs when the core fires the event and receives the event's arguments as one array (`$args[0]`, `$args[1]`). `$this->setVar('<placeholder>', $html)` adds HTML to a `{%placeholder%}` of the templates being rendered; `'replace'` as the third argument replaces its content. The base class also provides `$this->db2`, `$this->user`, `$this->network`, `$this->page`, `$this->cache`, `getCurrentController()`, `getCurrentTab()`, `isMobileRegime()`, and `loadLangfile()` and `lang()` for the plugin's texts.
- Optionally `installer.php` with `class MyInstaller extends Installer`, whose `up()` and `down()` create and remove the plugin's tables and fields through the helpers in `system/plugin_installer/`. The Plugins page shows what they will change before it runs them. `create_table()` makes a MyISAM table in `utf8mb4` with the collation of the core tables; a key on more than 250 characters of a column exceeds the MyISAM key limit of 1000 bytes on MySQL, so index a prefix (`KEY (name(250))`).
- Optionally `system/controllers/` for pages at `/plugin/<name>/...` (signed-in users only), `system/controllers/ajax/<action>.php` for `/services/<name>/<action>`, `system/cronjobs/php_<interval>/*.php` for jobs that run after the core jobs of that interval, and `static/js/*.js` and `static/css/*.css`, which the pages include.
- Optionally `languages/<code>/*.php` with the plugin's texts, see [Plugin texts and translations](#plugin-texts-and-translations).

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

### Plugin texts and translations

A plugin keeps its texts in its own language files instead of using those of the core, whose keys may change or disappear in a release. `apps/<name>/languages/en/<file>.php` sets `$lang` like the files of `system/languages/en/`, with placeholders such as `#NAME#`; a translation is a folder named after a language pack, for example `languages/ru/<file>.php`. English is the reference: each file must exist in `languages/en/`, and a text or a whole file that a translation lacks is shown in English.

```php
<?php
	// apps/hello/languages/en/hello.php
	$lang	= array
	(
		'hello_greeting'	=> 'Hello, #NAME#!',
		'hello_mail_subject'	=> '#NAME# follows you',
	);
?>
```

```php
<?php
	// apps/hello/plugin.php
	class hello extends plugin
	{
		public function onPageLoad()
		{
			if( !$this->user->is_logged ){
				return;
			}
			$this->loadLangfile('hello.php');
			$this->setVar('main_content_top_placeholder', htmlspecialchars($this->lang('hello_greeting', array('#NAME#' => $this->user->info->username))));
		}

		public function onUserFollow( $args )
		{
			$this->loadLangfile('hello.php');
			// in the language of the member who gets the e-mail
			$subject = $this->lang('hello_mail_subject', array('#NAME#' => $args[0]->info->username), $args[1]->language);
		}
	}
?>
```

- `$this->loadLangfile('<file>.php')` loads a file in the language of the page. Each file is read once per request, so a handler may call it every time. Call it in the event handlers, not in the constructor: the plugins are created before the page.
- `$this->lang('key', $replace)` returns the text, with the keys of `$replace` replaced, or `''` for an unknown key. A member's `language` as the third argument gives the text in that language, for e-mails.
- Controllers, AJAX controllers, cron jobs, templates, and `installer.php` of a plugin call the page object: `load_plugin_langfile('<name>', '<file>.php')` and `plugin_lang('<name>', 'key', $replace, $language)`. It is `$this` in a page controller, `$this->page` in a template, `$page` in a cron job, and `$GLOBALS['page']` everywhere.
- The keys of `languages/<code>/js.php` are given to the scripts on the pages of signed-in members: `stxText('<name>:<key>', 'English text')` returns the text in the language of the page, and the English text given to it where a page has none.

The texts of a plugin are separate from those of the core and of other plugins: the same key does not clash, a plugin cannot change a text of the core, and `$this->lang()` does not return texts of the core; use `$this->page->lang()` for those. A missing English file, or a language file that fails or sets no `$lang` array, is reported in the PHP error log instead of stopping the page.

A plugin written for an older version that declares its own method `lang()` or `loadLangfile()` must rename it: these methods of the base class are final, PHP refuses such a class, and every page fails while the plugin is installed. To recover, set `$C->PLUGINS_DISABLED = TRUE;` (see above) and uninstall or fix the plugin.

## Upgrading

The updater updates Sharetronix 3.0 and later. Releases 1.x and 2.x used a different database layout and upload folders, which it cannot convert.

1. Back up the database, `system/conf_main.php`, and `storage/`, and make sure the backup can be restored. Restore a database backup into an emptied database: an update that failed half-way may have created tables that the backup does not contain, and restoring over them leaves them in place.
2. Make sure PHP loads the `mbstring` extension. Sharetronix 4.0.0 no longer ships a replacement for it: without the extension, pages, the updater, and the cron worker stop with a message that it is missing.
3. Replace the application files with the new version: delete the old files instead of copying the new ones over them, but keep `system/conf_main.php`, `storage/`, and any themes, plugins, or languages you installed. Files that a release removes must not stay behind; for example, a left-over `system/cronjobs/php_6h/7-clean_request_tokens.php` from 3.6.2 stops the cron worker once the 4.0.0 updater has dropped the OAuth tables. The updater deletes the files that newer releases no longer ship and lists any file it cannot delete; delete those by hand.
4. Sign in as an administrator, open `/install/`, and confirm the update. The updater applies the database changes for the new version. `system/conf_main.php` must be writable during the update to 4.0.0; the updater lists it otherwise.
5. Clear `system/cache/` and `system/cache_html/`, or wait until cached data expires (one hour by default) and the daily cron job clears the cached page HTML.

Outside debug mode, PHP errors are no longer displayed to visitors, even when `display_errors` is on in `php.ini`. To see them in the browser, add your address to `$C->DEBUG_USERS` (see [Debugging](#debugging)).

The update to 4.0.0 converts the database from `utf8` to `utf8mb4` (see [Requirements](#requirements)): the tables of Sharetronix and of the installed plugins and the database default; other tables in the same database are not touched, and the data does not change. Each table is copied once, so a large site needs some time; if the update is interrupted, for example by a web-server timeout, run it again: tables that are already converted are skipped. When it is done, the updater adds `$C->DB_CHARSET = 'utf8mb4';` to `system/conf_main.php`, which switches the connections to `utf8mb4`. Text that older versions could not store is not restored: MariaDB saved an emoji as `?`, and MySQL cut the text off at it. Keys that would exceed the MyISAM limit of 1000 bytes get shorter prefixes: 250 characters for a key on one column, and shorter prefixes on the text columns, in proportion, for a key on several columns that is not unique. A plugin table that cannot be converted, such as one with a unique key on several long columns on MySQL, keeps `utf8` and is named on the result page.

The update to 4.0.0 also adds indexes to the `post_likes`, `notifications`, and `users` tables, which copies each of these tables once more; on a test site with a million notifications this took up to 8 seconds per index. Indexes that an earlier run already added are skipped.

The APC and MySQL heap (`mysqlheap`) cache drivers have been removed, and the update drops the MEMORY table `cache` that `mysqlheap` used. If `system/conf_main.php` sets `$C->CACHE_MECHANISM` to `'apc'` or `'mysqlheap'`, the application now uses the file cache in `system/cache/`, which must stay writable; `'memcached'` and `'redis'` remain available.

The bundled Securimage CAPTCHA, the reCAPTCHA v1 integration, and the image CAPTCHA of the contact form have been replaced by the built-in image described in [CAPTCHA](#captcha). The updater deletes `system/libraries/securimage/`, `system/helpers/func_recaptchalib.php`, and `system/helpers/func_captcha.php`; old images `storage/tmp/captcha_*.gif` of the contact form can be deleted by hand. Existing configurations need no change: without the new settings the image is used. `$C->GOOGLE_CAPTCHA_PUBLIC_KEY` and `$C->GOOGLE_CAPTCHA_PRIVATE_KEY` are ignored and can be removed; Google shut reCAPTCHA v1 down in 2018, so for reCAPTCHA create new v2 or v3 keys and set `$C->CAPTCHA_PROVIDER`. Plugins that called `generate_captcha()` or Securimage must use the `captcha` class instead.

New accounts no longer take their picture from Gravatar during sign-up; they start with the default picture. Members can choose **Use My Gravatar** on the picture settings page (`/settings/avatar`), which downloads their Gravatar over HTTPS (cURL with a CA bundle, see [CAPTCHA](#captcha)) and sends a SHA-256 hash of their e-mail address to gravatar.com. Existing pictures are not changed.

The StopForumSpam check at sign-up no longer refuses addresses whose reports are old (see [Spam checks](#spam-checks)). To refuse every address that has ever been reported, as 3.6.2 did for IP addresses, set `$C->STOPFORUMSPAM_MIN_CONFIDENCE = 0;` in `system/conf_main.php`.

Sign-up and sign-in through Facebook and Twitter have been removed: Facebook shut down the registration plugin they relied on, and Twitter sign-in needs the paid X API. The updater deletes `system/libraries/twitter/`, `system/classes/class_twitter_module.php`, and the button images. Members who signed up that way set a password at sign-up and sign in with it like everyone else; password recovery works for them too. `$C->FACEBOOK_API_KEY`, `$C->FACEBOOK_API_ID`, `$C->FACEBOOK_API_SECRET`, `$C->TWITTER_CONSUMER_KEY`, `$C->TWITTER_CONSUMER_SECRET`, `$C->YAHOO_CONSUMER_KEY`, and `$C->YAHOO_CONSUMER_SECRET` in `system/conf_main.php` are ignored and can be removed. The `facebook_uid` and `twitter_uid` columns of the `users` table are kept.

Sharetronix 4.0.0 no longer ships the language strings and the 21 language files that the core does not use. A plugin that showed such a core text with `$page->lang()` now shows an empty text, and one that loads a removed file with `load_langfile()` stops the page with a fatal error; move these texts into the plugin's own language files (see [Plugin texts and translations](#plugin-texts-and-translations)). A plugin that declares its own method `lang()` or `loadLangfile()` makes every page fail after the upgrade: rename the method before you upgrade.

## Known limitations

- Video embedding (`system/conf_embed.php`) still uses Flash players, which current browsers no longer run, so embedded videos do not play; some of its services (Vine, MySpace video, Revver) have closed.
- The mobile version has no settings, administration, search, or invitation pages; on a phone their addresses show the mobile home page. The browser's desktop-site mode reaches them.
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
