# Pre-merge regression verification: 2026-10-18

Application revisions: before fixes `0a34c630541989c8046527d90e03514debc9db57`, after fixes `0963a1ed5fa44dbf8cd4ab516b721ac624e4f4e8`. The expanded tests in this change do not modify application source. Source snapshots are exported from Git, then installed into separate disposable sites/databases. The test suite is shared across both revisions.

Environment: Windows 11 Pro x64 build 10.0.26200; Apache 2.4.66 Win64 with rewrite and FastCGI; PHP 7.2.34 NTS; MySQL 5.7.44, empty SQL mode, `utf8`/`utf8_unicode_ci`; filesystem cache and default theme. `E_ALL`, short tags, and logging are enabled; output buffering is 4096. PHP 8.4.19 is used for syntax and the focused offset comparison. Mail is captured locally and RPC pings are disabled in both installed fixtures before content tests. No production system or real user data is involved.

## Installation and shared behavior

| Scenario | Original revision | Fixed revision |
| --- | --- | --- |
| Successful installer redirect with an empty response body | FAIL: 26 cleared-session notices | PASS |
| Login with the generated `localhost:8093` domain | FAIL: returns to sign-in | PASS: session retained |
| Guest access to the installed updater | FAIL: HTTP 200; synthetic cleanup sentinel deleted | PASS: HTTP 403; sentinel retained |
| Admin update confirmation, invalid/missing token, ordinary-user denial, revoked administration, repeat confirmation, obsolete wizard denial | Original protection absent | PASS |
| Shared application scenarios below | PASS: 28 checks with the documented domain workaround | PASS: the same 28 checks without the workaround |
| Full fixed installation/update/HTTP-cron/application integration run | Not applicable as a healthy baseline | PASS: 57 checks |

The common checks cover independent ordinary-user sessions; guest/user administration denial; public posting and owner persistence; another user's reading, comment, like/unlike; rejected edit/delete by another user; successful owner edit/delete; profile reload with Cyrillic text and isolation from the other account; follow/unfollow persistence; private/public group creation; private post access for the owner and denial for a guest/non-member through direct post/group routes; public group joining/posting; search visibility; and logout invalidation. HTTP service responses and database effects are both checked. These do not execute browser JavaScript.

The original fixture logged 61 autoload deprecations and 26 installer notices during the comparison run. The fixed fixture logged no PHP warnings, notices, deprecations, fatal errors, or parse errors during its corresponding run. Apache logs for the fixed fixture contain normal startup notices only.

## Focused before/after comparisons

`tests/before-after.ps1` matched all 18 expected outcomes across nine cases:

| Case | Before | After |
| --- | --- | --- |
| Port-safe session/cookie scopes | Known failure reproduced | PASS |
| Autoload coexistence and clean diagnostics | Known deprecation reproduced | PASS |
| Private temporary-directory configuration/cleanup | Missing-directory contract reproduced | PASS |
| Retired version request with cURL unavailable | Unconditional cURL call reproduced | PASS, no remote request |
| Token expiration SQL | Unknown `timestamp` column reproduced | PASS, expired token removed and recent token retained |
| Password/audio character behavior on PHP 7.2 | PASS | PASS |
| Corresponding PHP 8.4 parser path | Parse failure reproduced | PASS |
| Daily database maintenance | Removed RSS-table query reproduced | PASS, post count retained |
| Daily cache file paths | Relative `unlink()` failure reproduced | PASS, working-directory sentinels retained |

The token SQL comparison uses `95f7b2d`, immediately before its SQL fix and after removal of the retired network request. The other focused cases use the original source revision. Expected failures must contain their specific diagnostic, so unrelated setup failures cannot count as successful reproduction.

Separately, the complete worker at `0151072` reproduced four stuck running flags and a skipped `1min` plugin job. Restoring `0963a1e` passed all eight intervals, plugin jobs with/without core directories, next schedules, and an immediate repeat without duplicate work. No worker diagnostics remained. The original worker with its retired external request was not executed.

## Limits and retained evidence

This is evidence that the recorded defects are corrected and that no regression was observed in the tested shared scenarios. It does not establish absence of other bugs or complete PHP 8.4 support. Modern-PHP deprecations, older-version database migrations, interrupted upgrades, broader browser coverage, API/mobile behavior, and other external integrations remain follow-up work as recorded in the earlier reports.

Raw responses, source snapshots, synthetic database names, and machine-readable results are retained under ignored `.local/installer-tests/` and `.local/comparisons/`. Credentials and generated configuration are not committed. No recurring task is registered by these tests. See [TESTING.md](../TESTING.md) for commands and [the individual fix report](2026-10-18-fixes.md) for the original per-commit checks.
