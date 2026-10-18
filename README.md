<img src="static/images/logo.png" alt="Sharetronix">

[![Lint](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml/badge.svg)](https://github.com/belomaxorka/sharetronix/actions/workflows/lint.yml)

Sharetronix is an open-source PHP platform for microblogging and social networking. It provides personal and group feeds, comments, likes, private messages, file and image attachments, search, an administration panel, themes, plugins, a mobile interface, and an API.

This repository maintains Sharetronix **3.6.2**. It keeps the platform running on current PHP versions and fixes security and reliability defects while preserving existing data, URLs, themes, and plugins. A rewrite or a new feature set is out of scope.

## Requirements

- Apache HTTP Server with `mod_rewrite`, and `.htaccess` overrides allowed for the application directory.
- PHP 7.2 or 8.4 with the `mysqli` and `gd` extensions, `short_open_tag = On`, and either the cURL extension or `allow_url_fopen = On`.
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

## Background tasks

By default, pages viewed in a browser trigger the periodic jobs with a background request to `/cron`. On a server with cron, run the worker every minute instead:

```sh
* * * * * /path/to/php /path/to/sharetronix/system/cronjobs/worker.php
```

Then set `$C->CRONJOB_IS_INSTALLED = TRUE;` in `system/conf_main.php`. See [system/cronjobs/readme.txt](system/cronjobs/readme.txt).

## Upgrading

1. Back up the database, `system/conf_main.php`, and `storage/`, and make sure the backup can be restored.
2. Replace the application files with the new version. Keep `system/conf_main.php`, `storage/`, and any themes, plugins, or languages you installed.
3. Sign in as an administrator, open `/install/`, and confirm the update. The updater applies the database changes for the new version.
4. Clear `system/cache/`, or wait for cached data to expire (one hour by default).

The APC cache driver has been removed. If `system/conf_main.php` sets `$C->CACHE_MECHANISM = 'apc'`, the application now uses the file cache in `system/cache/`, which must stay writable; `'memcached'` and `'mysqlheap'` remain available.

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
