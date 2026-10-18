# Local development on Windows

This diagnostic baseline runs the existing application with Apache, PHP 7.2, MySQL, and captured email. These historical PHP/MySQL versions are for an isolated local compatibility baseline; they are not a production support policy. See [the recorded test run](test-runs/2026-10-18-baseline.md) for results and known defects.

## Prerequisites

Use Windows, Git, and PowerShell 7 with the following existing Open Server modules. The scripts do not download runtimes, change Open Server configuration, register services, or edit the hosts file.

| Module directory under `C:\OSPanel\modules` | Tested version |
| --- | --- |
| `Apache` | Apache 2.4.66, Win64, Apache Lounge VS18 build 2026-02-23 |
| `PHP-7.2` | PHP 7.2.34 NTS, MSVC15 x64, build 2025-09-21 |
| `MySQL-5.7` | MySQL Community Server 5.7.44, Win64 |
| `Mailpit` | Mailpit 1.29.3, windows/amd64 |
| `PHP-8.4` (optional compatibility probe) | PHP 8.4.19 NTS x64 |

Startup checks these version pins. Use `-RuntimeRoot 'D:\path\to\modules'` if the same modules are installed elsewhere. Changing a runtime pin requires another recorded verification run.

## Start and install

Run from the repository root:

```powershell
.\scripts\dev.ps1 Start
```

The script copies the tracked application files from the working tree into `.local/site`, initializes a dedicated database instance, creates random local credentials, and launches hidden processes. It uses its own PHP/Apache/MySQL configuration and process records in `.local`.

| Service | Address |
| --- | --- |
| Application | `http://localhost:8092/` |
| Initial installer | `http://localhost:8092/install/` |
| Captured mail | `http://127.0.0.1:18025/` |
| SMTP capture | `127.0.0.1:11025` |
| Dedicated MySQL | `127.0.0.1:33062` |
| PHP FastCGI | `127.0.0.1:9072` |

All services bind to loopback. Startup refuses occupied ports. Apache uses `mod_rewrite`, `.htaccess`, and [FastCGI via `mod_proxy_fcgi`](https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html); the normal Open Server instances remain separate.

1. Open the initial installer and accept the project license.
2. Enter database host `127.0.0.1:33062`. Use `user`, `password`, and `database` from `.local/credentials.json` for the other database fields.
3. Use `adminUser` and `adminPassword` from that file for the administrator. Use a synthetic address such as `admin@localhost.test`. The username `admin` is reserved by the application.
4. Finish installation. The script's site copy receives `system/conf_main.php`; the repository's source configuration is not changed.
5. Disable outbound RPC pings in the local configuration and restart:

```powershell
.\scripts\dev.ps1 Configure
.\scripts\dev.ps1 Stop
.\scripts\dev.ps1 Start
```

`Configure` disables outbound RPC pings without rewriting the installer-generated `DOMAIN` or `SITE_URL`. Session names retain a safe port suffix, while cookie domain attributes contain only a hostname; no manual domain workaround is required. After installation the application itself denies updater access to guests and ordinary users with HTTP 403. An active administrator must sign in and submit the update confirmation with its session-bound token before update processing can run. Back up first; the obsolete `/install/old/` wizard is disabled, and updates use `/install/`. No blanket Apache installer block is required.

PHP loads GD, cURL, Fileinfo, Mbstring, MySQLi, and OpenSSL, with the standard XML/DOM, JSON, session, and other built-in modules available in this distribution. Short tags are enabled; output buffering is 4096 bytes. `E_ALL` and logging remain enabled, with browser display off by default. The loader regression also verifies clean compressed HTML with displayed diagnostics enabled. The installer and cron worker can override the display setting; inspect logs even if a page or command appears successful.

MySQL uses `utf8` / `utf8_unicode_ci` and an empty SQL mode to characterize legacy behavior. Strict SQL modes, `utf8mb4`, and newer database releases need separate compatibility work. The application uses filesystem caching. Installer writable paths include `system`, its cache/tmp/language directories, `storage`, `themes`, `apps`, and the copied `.htaccess`.

## Fixtures and repeatable checks

Create two ordinary synthetic users after installation:

```powershell
& C:\OSPanel\modules\PHP-7.2\php.exe -c .local\php.ini scripts\seed-local.php
.\scripts\smoke-local.ps1
```

The fixture script creates `qausera` and `qauserb` with random passwords in `.local/credentials.json`; it refuses to overwrite an unrelated existing account. It inserts fixture accounts directly into the disposable database. This does not exercise public registration or CAPTCHA completion.

The HTTP smoke script checks 15 authentication, session, profile-access, and administration boundaries with independent sessions. Results are written to `.local/http-smoke-results.json`. Use [TESTING.md](TESTING.md) for browser scenarios such as posting, groups, privacy, and uploads.

Email from the Windows PHP `mail()` function is delivered to the dedicated Mailpit instance. No SMTP relay is configured. Use synthetic addresses only. RPC pings are disabled; other retired external integrations have not been comprehensively stubbed. The cron version check has been removed. The full worker, immediate repeat, plugin scheduling, and private temporary-file cleanup have regression coverage on a disposable installer database; the HTTP fallback is also checked after installation. No recurring operating-system task is registered. Use the commands in [TESTING.md](TESTING.md) for verification. The original worker instructions are in `system/cronjobs/readme.txt`; findings and fixes are in the test reports.

## Stop, synchronize, and reset

```powershell
.\scripts\dev.ps1 Status
.\scripts\dev.ps1 Stop
.\scripts\dev.ps1 Start
.\scripts\dev.ps1 Sync
```

`Stop` verifies recorded process IDs, executable paths, and start times before stopping owned processes, and requests a database shutdown. Data, uploads, sessions, and captured messages survive restart. `Sync` copies tracked application files from the working tree into the running site; it does not remove files deleted from Git. Untracked new application files need to be staged before synchronization. Generated configuration and user data remain local.

To start again with an empty installation:

```powershell
.\scripts\dev.ps1 Reset
.\scripts\dev.ps1 Start
```

`Reset` stops the stack and moves `.local` to a timestamped `.local-archive-*` directory inside the checkout. It retains the previous installation instead of deleting it. Both current and archived state are ignored by Git. This reset command was reviewed but was not exercised in the initial run; ordinary start/stop/restart and backup were exercised.

## Backup and restore

```powershell
.\scripts\dev.ps1 Backup
```

Snapshots go into `.local/backups/<timestamp>/` and contain a database dump, `conf_main.php`, and `storage`. MySQL tables are locked while the dump is written because the legacy schema includes MyISAM tables. Stop browser activity and other writers while verifying a snapshot against the live database. Mailpit and process state are separate from the application backup.

Verify a restore into a new database on this dedicated MySQL instance before touching the original installation:

1. Select the snapshot and create a new database, for example `sharetronix_restore_check`, using `.local/client.ini` with the module's `mysql.exe`.
2. Import `database.sql` into that database. Do not import into the original database as a verification shortcut.
3. Copy the saved configuration and storage into a separate local restore directory. Compare SHA-256 file hashes and `CHECKSUM TABLE ... EXTENDED` results for all restored tables while the reference data is idle.
4. To exercise the restored database through the local application, grant the local application account access to that database, retain the current configuration, and temporarily set its `DB_NAME` to the restored database name. Keep the local URL, domain, and RPC settings.
5. Restart, verify authentication, representative content, permissions, and avatar/file delivery, then restore the original configuration and restart again.

The initial run verified all 48 table checksums, 23 storage files, and the configuration hash, then exercised the restored database through the application. Installation upgrades and recovery from an interrupted upgrade remain untested.

## Diagnostics

Logs are in `.local/logs/`; configuration, sessions, dumps, and captured email also remain under `.local`. Do not add these files to Git or paste unsanitized diagnostics into reports. Public test reports should contain synthetic examples, relative source paths, and summarized evidence.

To probe modern PHP separately, stop the stack, run `scripts/dev.ps1 Start -PhpVersion 8.4`, and request `/`. After the focused syntax fixes, the homepage and 15 HTTP smoke checks passed, but PHP 8.4 deprecations and unverified flows remain; see the follow-up fix report. Stop it and start with the default PHP 7.2 again for the diagnostic baseline. This probe does not establish complete PHP 8.4 support.
