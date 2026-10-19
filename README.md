<img src="static/images/logo.png" alt="Sharetronix">

[![Lint](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml/badge.svg)](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml)

Sharetronix is an open-source PHP platform for microblogging and social networking. It provides personal and group feeds, comments, likes, private messages, file and image attachments, search, an administration panel, themes, plugins, a mobile interface, and an API.

This repository maintains Sharetronix **3.6.2**. It keeps the platform running on current PHP versions and fixes security and reliability defects while preserving existing data, URLs, themes, and plugins. A rewrite or a new feature set is out of scope.

## Requirements

- Apache HTTP Server with `mod_rewrite`, and `.htaccess` overrides allowed for the application directory.
- PHP 7.2 or 8.4 with the `mbstring`, `mysqli`, and `gd` extensions, `short_open_tag = On`, and either the cURL extension or `allow_url_fopen = On`.
- MySQL 5.7.

Installation, updates, and the core application flows have been tested with:

| Component | Versions |
| --- | --- |
| PHP | 7.2.34 and 8.4.19 (FastCGI) |
| Web server | Apache 2.4.66 |
| Database | MySQL 5.7.44 |

PHP 8.5 is the next target. CI already checks syntax on it, but it has not been tested at runtime. Other PHP versions, MariaDB, and newer MySQL releases may work but are untested.

## Installation

1. Get the source code:

   ```sh
   git clone https://github.com/belomaxorka/sharetronix.git
   ```

2. Point an Apache virtual host at the checkout and enable `mod_rewrite`.
3. Create an empty MySQL database and a user with full privileges on it.
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

## Debugging

Debug mode is enabled for client IP addresses listed in `system/conf_main.php`:

```php
$C->DEBUG_USERS = array('203.0.113.10');
```

Requests from these addresses display PHP errors of all levels and, when a database query fails, the query and the MySQL error. For everyone else the application turns `display_errors` off, whatever `php.ini` says, so errors only go to the PHP error log. The installer (`/install/`) always displays errors. The address is the one PHP receives as `REMOTE_ADDR`: behind a reverse proxy or CDN this is the proxy's address, and listing it enables debug mode for every visitor; a browser on the server itself may connect as `::1` rather than `127.0.0.1`. Do not leave debug mode enabled on a production site.

With `$C->DEBUG_CONSOLE = TRUE;` pages in debug mode show a small **Debug** link at the bottom right. It opens a window with the page generation time, memory use, and every SQL query and cache call of the request.

Failed database queries, failed e-mail deliveries, unreachable cache servers, and service answers that cannot be encoded are logged as lines starting with `Sharetronix`. Make sure `log_errors` is on and `error_log` points to a file you can read. The command-line cron worker also prints errors to its output.

## Upgrading

1. Back up the database, `system/conf_main.php`, and `storage/`, and make sure the backup can be restored.
2. Make sure PHP loads the `mbstring` extension. Sharetronix 3.6.3 no longer ships a replacement for it: without the extension, pages, the updater, and the cron worker stop with a message that it is missing.
3. Replace the application files with the new version: delete the old files instead of copying the new ones over them, but keep `system/conf_main.php`, `storage/`, and any themes, plugins, or languages you installed. Files that a release removes must not stay behind; for example, a left-over `system/cronjobs/php_6h/7-clean_request_tokens.php` from 3.6.2 stops the cron worker once the 3.6.3 updater has dropped the OAuth tables.
4. Sign in as an administrator, open `/install/`, and confirm the update. The updater applies the database changes for the new version.
5. Clear `system/cache/` and `system/cache_html/`, or wait until cached data expires (one hour by default) and the daily cron job clears the cached page HTML.

Outside debug mode, PHP errors are no longer displayed to visitors, even when `display_errors` is on in `php.ini`. To see them in the browser, add your address to `$C->DEBUG_USERS` (see [Debugging](#debugging)).

The APC and MySQL heap (`mysqlheap`) cache drivers have been removed. If `system/conf_main.php` sets `$C->CACHE_MECHANISM` to `'apc'` or `'mysqlheap'`, the application now uses the file cache in `system/cache/`, which must stay writable; `'memcached'` and `'redis'` remain available.

## Known limitations

- Sign-in through Twitter and Facebook and other integrations with retired third-party services are untested and may not work.
- Installing languages and plugins from the administration panel relies on the retired Sharetronix marketplace ([#8](https://github.com/belomaxorka/sharetronix/issues/8)).
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
