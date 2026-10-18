# Contributing to Sharetronix

Contributions should improve the existing Sharetronix platform through focused fixes, compatibility updates, and coherent improvements. See [ROADMAP.md](ROADMAP.md) for priorities and [AGENTS.md](AGENTS.md) for instructions that also apply to coding agents.

## Report a defect

Include the commit or release tested, exact PHP and database versions, web server and PHP SAPI, relevant extensions/settings, and whether this is a fresh or upgraded installation. Describe the steps to reproduce, expected behavior, actual behavior, and sanitized logs. Use synthetic data and remove passwords, tokens, cookies, and personal information from reports.

For privacy and permission bugs, identify the roles involved and the visibility setting of the affected content. Check [docs/TESTING.md](docs/TESTING.md) for the environment record and scenario checklist.

## Make a change

1. Inspect the affected code, callers, and nearby conventions. Choose one concrete problem and reproduce it when possible.
2. Create a branch for the change. Keep fixes, runtime compatibility work, dependency updates, and unrelated refactoring separate.
3. Follow the existing formatting and naming in each touched file. Reuse existing helpers and architecture where appropriate; avoid cosmetic renames, bulk formatting, or line-ending changes.
4. Preserve copyright notices, author credits, and third-party licenses. Keep installation configuration, secrets, user data, uploads, and caches out of commits.
5. Verify the affected flow using [docs/TESTING.md](docs/TESTING.md). Add a useful regression test where practical, or document repeatable manual checks when that area has no test harness yet.
6. Update documentation and `CHANGELOG.md` under `Unreleased` for changes to behavior, requirements, installation, or upgrades.
7. Open a pull request describing the problem, resulting behavior, verification, and material compatibility effects. State what remains untested.

Necessary refactoring is welcome when its purpose and impact are clear. Keep it consistent with the codebase, preserve existing contracts where practical, and include migration instructions for intentional incompatibilities. Schema or data migrations need backup, upgrade, and restore verification on disposable data.

## Commit messages

Use [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/) for new commits:

```text
type(scope): short imperative description
```

The scope is optional. Use lowercase types and English descriptions. For example:

```text
fix(auth): preserve the session after signing in
fix(db): handle a failed database connection
test(feed): cover private group visibility
docs: document the local installation process
refactor(cache): reuse the existing cache helper
```

These are message examples, not changes already implemented. Use `fix` for defects, `feat` for features, `refactor` for behavior-preserving implementation changes, `test` for tests, and `docs` for documentation. Use `build`, `ci`, `perf`, `chore`, or `style` when appropriate; formatting changes still need to remain focused.

Mark intentional breaking changes with `!` and explain the migration. Raising the minimum PHP version, for example:

```text
build(php)!: require PHP 8.4

BREAKING CHANGE: PHP versions below 8.4 are no longer supported.
Upgrade the PHP runtime before installing this release.
```

The example does not establish the current runtime requirement. Do not rewrite published history merely to adopt the convention.

## Ready for review

A change is ready when its purpose and scope are clear, relevant checks have passed or their blockers are stated, and affected documentation is current. Include the exact environment, commands/scenarios run, and outcomes. Syntax checks alone are not evidence that installation or the application works. Documentation-only changes need a review of links and the diff; they do not require application tests.

New runtime support must be demonstrated by the installation, upgrade, and application checks in the documented matrix before it is advertised in the README or a release.
