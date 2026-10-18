# Repository instructions

These instructions apply throughout this repository. Read [CONTRIBUTING.md](CONTRIBUTING.md), [ROADMAP.md](ROADMAP.md), and [docs/TESTING.md](docs/TESTING.md) before making changes.

## Purpose

Maintain and improve the existing Sharetronix codebase, starting from 3.6.2. Preserve its behavior, data, and established extension points while fixing defects and improving compatibility, reliability, and security. A replacement platform or a wholesale rewrite is outside this project's scope.

## Code conventions

- Inspect the surrounding implementation and its callers before editing. Reuse existing helpers, classes, configuration, and extension mechanisms where appropriate.
- Follow the formatting of the file and nearby code: indentation, alignment, braces, whitespace, quotes, and array syntax. The codebase has mixed historical styles; do not impose a new repository-wide standard.
- Preserve existing naming conventions for variables, functions, methods, classes, files, and configuration keys. Do not rename existing symbols just to modernize their appearance.
- Keep existing line endings and encodings in source files. Do not run bulk formatters or include unrelated whitespace, encoding, or line-ending changes.
- Keep changes focused. Separate compatibility work, behavior changes, dependency updates, and unrelated refactoring into reviewable changes.
- Refactoring and replacement of an implementation are allowed when necessary to solve a concrete problem. Explain the reason, follow local conventions, and verify affected behavior and callers. Avoid introducing parallel abstractions for functionality the project already provides.
- Preserve configuration, database, URL, theme, plugin, and API contracts where practical. Document intentional incompatibilities and provide an upgrade path when existing installations are affected.
- Keep database schema, charset, and collation migrations explicit and separate from unrelated PHP compatibility fixes. Test them against a backed-up, disposable database.

## Attribution and dependencies

- Preserve copyright notices, author credits, license texts, and third-party attribution. Do not remove or replace them as routine cleanup.
- Bundled libraries retain their own conventions and licenses. Update or patch them deliberately; avoid bulk editing vendor code.
- Never commit credentials, installation-specific configuration, real user data, database dumps, uploaded files, or generated caches.

## Verification

- Reproduce a reported defect when possible. Record expected and actual behavior and the environment before changing code.
- Follow [docs/TESTING.md](docs/TESTING.md). Select checks appropriate to the change; syntax checks alone do not establish runtime compatibility.
- For PHP edits, lint the changed files with short tags enabled and exercise the affected application flow. Include negative and permission checks when relevant.
- Add a focused regression test for a defect when a useful automated test is practical. Until a harness exists for that area, document repeatable manual steps and results. Documentation-only changes need link and diff review, not application tests.
- Do not hide migration failures by suppressing errors or lowering error reporting. Capture warnings and deprecations in the local test environment and investigate their causes.
- Report the exact PHP and database versions used, relevant web-server settings, checks run, outcomes, and checks not run. Distinguish a blocked check from a passed check.
- Do not claim support for a runtime or mark a roadmap milestone complete without recorded verification. The PHP versions in the roadmap are targets until validated.

## Commits and documentation

- Use [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/) for new commits: `type(scope): description`. The scope is optional.
- Use lowercase types and short English imperative descriptions. Common types are `fix`, `feat`, `docs`, `refactor`, `test`, `build`, `ci`, `perf`, `chore`, and `style`.
- Use `fix` for a bug fix, `feat` for a feature, and `refactor` for an implementation change that preserves behavior. `style` does not authorize unrelated formatting changes.
- Mark intentional breaking changes with `!` and describe their impact and migration in the commit body or a `BREAKING CHANGE:` footer. A raised minimum runtime version is a breaking change.
- Keep each commit focused. Do not rewrite published history just to convert earlier messages to this convention.
- Update user-facing documentation and the `Unreleased` section of [CHANGELOG.md](CHANGELOG.md) when behavior, requirements, installation, or upgrade steps change.
- Describe the final change and its validation accurately. Do not invent test results, release dates, completed milestones, or supported environments.
