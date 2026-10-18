# Testing Sharetronix

This document describes the verification plan. Use [LOCAL_DEVELOPMENT.md](LOCAL_DEVELOPMENT.md) for the Windows diagnostic stack and the 15-check HTTP smoke script. The [2026-10-18 baseline report](test-runs/2026-10-18-baseline.md) records completed checks and known defects; the scenarios below are not all covered by that run. Broader automated tests and CI remain part of [ROADMAP.md](../ROADMAP.md).

## Environment and evidence

Use a disposable local installation with synthetic users and content. The installer expects Apache with rewrite support; PHP's built-in development server alone does not verify that deployment path. Capture mail locally and identify external services that have been stubbed, disabled, or could not be exercised.

For each run, record:

| Field | Required information |
| --- | --- |
| Source | Commit SHA and any local patches |
| Runtime | Exact PHP version, SAPI, extensions, and relevant `php.ini` settings |
| Web server | Exact version, virtual host, rewrite configuration, and operating system |
| Database | Product, exact version, SQL mode, charset, and collation |
| Setup | Fresh install or upgrade path, theme/plugins, cache backend, synthetic fixture description |
| Integrations | Mail capture, background tasks, and external services exercised or unavailable |
| Results | Scenario, steps/command, expected result, actual result, and sanitized evidence |

Mark each result **PASS**, **FAIL**, **BLOCKED**, or **NOT RUN**. Link failures to issues. Do not count a disabled integration or a blocked setup as a passing check. Record browser and viewport for UI checks.

## Syntax checks

With the PHP executable being tested on `PATH`, run this from the repository root for each changed PHP file:

```sh
php -v
php -d short_open_tag=On -l index.php
```

Replace `index.php` with the changed path, including templates containing PHP. Enabling short tags prevents legacy `<? ... ?>` sections from being treated as plain text during linting. This is a syntax check only: it does not execute application code, establish extension availability, or validate database access and permissions. CLI and web-server PHP configurations must be recorded separately.

During the compatibility inventory, lint all PHP sources and templates on the target runtime and retain the complete failure list. Once a test harness and CI are added, document their exact commands here.

## Focused regressions

With the Windows local stack running, use its PHP configuration for the focused checks:

```powershell
& C:\OSPanel\modules\PHP-7.2\php.exe -c .local\php.ini tests\session.php
& C:\OSPanel\modules\PHP-7.2\php.exe -c .local\php.ini tests\autoload.php
& C:\OSPanel\modules\PHP-7.2\php.exe -c .local\php.ini tests\temp-cleanup.php
& C:\OSPanel\modules\PHP-7.2\php.exe -n tests\token-cleanup.php
& C:\OSPanel\modules\PHP-7.2\php.exe -c .local\php.ini tests\token-expiry.php
& C:\OSPanel\modules\PHP-7.2\php.exe -n tests\php-offsets.php
& C:\OSPanel\modules\PHP-7.2\php.exe -c .local\php.ini tests\daily-database.php
& C:\OSPanel\modules\PHP-7.2\php.exe -n tests\daily-cache-cleanup.php
.\tests\php-syntax.ps1
.\tests\php-syntax.ps1 -PhpExecutable C:\OSPanel\modules\PHP-7.2\php.exe
.\tests\response-output.ps1
.\tests\installer.ps1
.\tests\cron-worker.ps1
.\scripts\smoke-local.ps1
```

The installer test uses an additional loopback listener on port 8093 and a newly created database on the dedicated local MySQL instance. It follows the actual wizard, checks the final redirect, logs in, and exercises update authorization and confirmation without a web-server installer block. It also checks the installed application's HTTP cron fallback. Disposable databases and evidence are retained locally; it does not reset the main test installation. The [fix verification report](test-runs/2026-10-18-fixes.md) records failures before each patch and checks afterward.

The daily-database and complete cron-worker regressions require a completed installer-test fixture. They use that disposable database. The worker check makes all eight intervals due, installs a synthetic plugin in the fixture, checks job completion and running flags, and immediately repeats the worker to verify that jobs are not duplicated before their next run.

## Application scenarios

Start with an administrator, ordinary users A and B, and a guest. Include a public group, a private group that B cannot access, public posts, and private content. Repeat applicable scenarios through the web UI and direct URLs/API endpoints so visibility is checked on the server.

| Area | Minimum scenarios and expected checks |
| --- | --- |
| Installation | Complete `/install/` on an empty database; verify generated configuration, schema, rewrite routes, writable paths, administrator login, and protection against unintended reinstall. |
| Authentication | Register, sign in/out, recover a password through captured mail, reject invalid credentials/tokens, and verify session persistence and logout invalidation. |
| Profiles | View/edit a profile, change settings, upload/replace an avatar, and confirm one user cannot change another user's data. |
| Posting | Create, view, edit/delete where supported, comment, like, reshare, paginate, and refresh feeds. Check ownership, counters, persistence, and empty/invalid input. |
| Visibility | Check public/private posts and groups as guest, owner/member, and non-member; verify feeds, search, direct links, attachments, and API responses respect access rules. |
| Groups and follows | Create/edit groups, join/leave, invite users, follow/unfollow, and check membership/administration permissions and resulting feeds. |
| Private messages | Send/read/delete as supported; verify participant access and absence from public feeds/search. |
| Search | Search users, groups, posts, mentions, and tags where supported; check pagination, non-ASCII input, and private-content filtering. |
| Files and images | Upload and retrieve supported attachments; check size/type rejection, thumbnail generation, invalid names, and unauthorized file access. |
| Administration | Exercise user/group/content management and settings; reject equivalent requests from ordinary users and guests. |
| Themes and plugins | Load existing themes, translations, and installed plugins; verify activation/settings where available and affected extension hooks. |
| Mobile and API | Exercise the existing mobile routes and API authentication, supported actions, response format, and permission failures. Record unavailable clients/integrations. |
| Background work | Exercise cron, notifications, captured email, cache invalidation, and temporary-file cleanup; check repeat execution for duplicate or lost work. |
| Error handling | Check missing records/routes, invalid input, expired sessions, and database/permission failures; inspect logs and ensure sensitive details are not exposed. |

For changed forms and endpoints, include relevant CSRF, input-validation, and output-escaping checks. Perform access checks with independent sessions for A and B, not just an administrator session. Verify data and filesystem effects as well as the visible response.

## Upgrades and recovery

1. Prepare a populated, disposable 3.6.2 installation with synthetic users, groups, relationships, posts, comments, and attachments.
2. Back up configuration, database, and uploaded files; demonstrate that the backup can be restored before relying on it.
3. Follow the documented upgrade path to the candidate commit and capture logs. If that path is not yet implemented or documented, record the check as blocked.
4. Compare record counts, representative content, file references, account access, and privacy settings before and after. Re-run the core scenarios and check restart/persistence.
5. Test recovery after a failed or interrupted upgrade using the documented restore procedure. Do not assume an older application version can use a migrated database.

## Regression and release checks

For a bug fix, demonstrate the reported failure before the patch and the expected behavior afterward where possible. Add focused automated checks as the harness grows; use repeatable manual evidence for unautomated areas. Run the same scenario against every PHP/database combination for which support will be claimed.

Before a release, test a clean checkout of the final commit, a fresh installation, an upgrade, core user and permission flows, and backup/restore. Record remaining defects and untested integrations. A successful lint run or a single successful login is not a completed release check.
