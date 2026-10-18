# Maintenance roadmap

The goal is to keep Sharetronix usable and maintainable as an evolution of the existing platform. Work starts with reproducible installation and observable behavior, then proceeds through focused fixes and runtime modernization.

Checked items have recorded implementation or verification; remaining items are planned. Documentation alone does not establish that the application runs or that a PHP version is supported. Release-blocking defects can be fixed as soon as they are reproduced, without waiting for every earlier checklist item.

## Starting point

- The installer declares Apache with `mod_rewrite`, PHP 5.2+, MySQL 5.0+, GD, short open tags, and cURL or URL fopen support. These are historical requirements, not a current support policy.
- `system/classes/class_mysql.php` contains both `mysql_*` and `mysqli_*` paths. Its existing interface is a useful boundary for database compatibility work.
- A [Windows diagnostic stack](docs/LOCAL_DEVELOPMENT.md) and a 15-check HTTP smoke script now establish an initial baseline. CI and broader automated coverage remain to be added; bundled library examples are not application tests.
- The [initial run](docs/test-runs/2026-10-18-baseline.md) covers fresh installation, core browser flows, and backup/restore on PHP 7.2/MySQL 5.7. Upgrades and compatibility with modern releases remain unverified or blocked.

## 1. Reproducible local installation

- [x] Inventory initial PHP extensions, settings, writable directories, background tasks, and external dependencies from the code and installer; findings are in the baseline documentation.
- [x] Add a versioned local Apache/PHP/database stack with pinned versions and documented startup, shutdown, logs, and reset commands. The initial implementation uses native Windows modules already available on the development host.
- [x] Establish a local diagnostic baseline for existing behavior without declaring its historical runtime a production support target.
- [x] Capture email locally, use synthetic accounts/uploads, and disable RPC pings.
- [ ] Stub or remove the remaining retired external dependencies before enabling recurring background runs. The cron version check has been removed; other integrations still need review.
- [x] Complete `/install/`, database setup, rewrite checks, writable-path checks, and first administrator login, with local workarounds explicitly recorded.
- [x] Record the environment, test results, and reproducible blocker reports in [the baseline report](docs/test-runs/2026-10-18-baseline.md).
- [x] Document and verify backup and restore of configuration, database, and storage for the baseline installation.

**Completion:** another contributor can start from a clean checkout, follow the documented commands, install the application, sign in, create a post, and reset the test environment. Exact versions and remaining limitations are recorded.

## 2. Establish behavior and a regression baseline

- [ ] Run the [manual scenario checklist](docs/TESTING.md#application-scenarios) with an administrator, two ordinary users, and a guest.
- [ ] Cover clean installation, persistence after restart, and restoration of a populated 3.6.2 test installation.
- [ ] Record failures as focused issues with reproduction steps, environment, expected behavior, actual behavior, and sanitized logs.
- [ ] Prioritize security, data loss, installation blockers, authentication, and posting failures ahead of cosmetic defects and new features.
- [ ] Add a small automated test harness with synthetic fixtures. Start with valuable cases around changed helpers, database behavior, and authentication/visibility boundaries.
- [ ] Add CI incrementally: PHP syntax checks first, then focused tests and database/HTTP integration checks once the environment is reproducible. Keep existing failures visible rather than silently excluding them.

**Completion:** core scenarios have recorded results, known defects have reproducible reports, and the first regression checks can be repeated locally and in CI. A checklist run does not prove the absence of bugs.

## 3. Stabilize existing functionality

- [ ] Fix confirmed installation, session, authentication, permissions, posting, upload, and database defects in small changes.
- [ ] Add regression coverage for fixes where practical; otherwise retain explicit manual reproduction and verification steps.
- [ ] Review authentication and recovery, query construction, output escaping, CSRF protection, file handling, and access to private content. Track concrete findings and fixes.
- [ ] Inventory bundled dependencies, versions, licenses, and known upstream fixes; update or patch one dependency at a time with affected flows checked.
- [ ] Exercise existing theme, plugin, mobile, and API behavior where present; document broken or obsolete integrations individually.

**Completion:** no known release-blocking security, data-loss, or core-flow defects remain; deferred defects and unsupported integrations are documented.

## 4. Move to supported PHP and database versions

The initial migration target is **PHP 8.4**, followed by **PHP 8.5** in the verification matrix. Neither is currently claimed as supported by this project. As checked on 2026-10-18, PHP lists security support through 2028-12-31 for 8.4 and 2029-12-31 for 8.5. Recheck the [official PHP support schedule](https://www.php.net/supported-versions.php) before finalizing a release matrix.

- [ ] Run a complete PHP syntax inventory with short tags enabled on the target runtime; classify parse failures, runtime failures, warnings, and deprecations separately.
- [ ] Audit removed functions and syntax, constructor/callback behavior, resource-to-object assumptions, dynamic properties, and stricter argument handling. Fix confirmed incompatibilities rather than applying blanket rewrites.
- [ ] Make the existing database wrapper work reliably with `mysqli`, remove the obsolete `mysql_*` dependency, and verify its return values and error behavior against existing callers.
- [ ] Update installer checks and setup requirements to match the tested runtime and required extensions. Remove reliance on short tags through a focused, separately verified change if needed.
- [ ] Check bundled dependencies and session, mail, image, upload, cache, and network operations on each target PHP version.
- [ ] Select and pin a maintained database release after compatibility testing. Verify installation SQL, queries, SQL modes, indexes, and upgrade scripts. Test MySQL and MariaDB separately before claiming support for both.
- [ ] Treat any charset/collation or schema modernization as an explicit migration with backup, data-integrity checks, and a tested restore procedure.
- [ ] Compare core behavior with the diagnostic baseline and run the same regression scenarios on every supported PHP/database combination. Do not retain obsolete PHP compatibility at the expense of the supported targets.

Use the official [PHP migration guides](https://www.php.net/manual/en/appendices.php) to investigate each relevant language change; intermediate PHP releases may help diagnosis without becoming supported deployment targets.

**Completion:** clean installation, upgrade from a populated 3.6.2 test installation, core scenarios, and automated checks pass on the documented matrix. Any intentional behavior or requirement changes have migration notes; unresolved warnings and deprecations have explicit tracked dispositions.

## 5. Ship and sustain a maintenance release

- [ ] Publish exact supported PHP/database versions, required extensions, web-server configuration, and known limitations.
- [ ] Verify an upgrade from 3.6.2 using a disposable copy with users, groups, posts, comments, and attachments; compare data and access rules before and after.
- [ ] Verify the documented backup/restore procedure and recovery from an interrupted or failed upgrade.
- [ ] Run the release checklist on the final commit and ensure CI results correspond to that commit.
- [ ] Update the changelog and installation/upgrade documentation, then tag and publish a release with clear compatibility notes.
- [ ] Triage new reports regularly, keep dependency/runtime support information current, and add regression coverage as maintenance continues.

**Completion:** a tagged release has reproducible installation and upgrade instructions, passing checks, a clear support matrix, and documented remaining limitations.

## Scope boundaries

Preserve the project's identity, established workflows, data model, and extension contracts where practical. A framework migration, replacement frontend, bulk renaming, repository-wide reformatting, and unrelated feature expansion are not initial maintenance milestones. Necessary refactoring remains possible when it solves a demonstrated problem and has appropriate verification.

Use [CONTRIBUTING.md](CONTRIBUTING.md) for the change process and [AGENTS.md](AGENTS.md) for repository instructions for coding agents.
