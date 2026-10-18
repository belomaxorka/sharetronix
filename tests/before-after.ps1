#requires -Version 7.0
param([string]$AfterRef = 'HEAD', [string]$RuntimeRoot = 'C:\OSPanel\modules')
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$stateRoot = Join-Path $repoRoot '.local'
$runRoot = Join-Path $stateRoot ('comparisons/' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))
[IO.Directory]::CreateDirectory($runRoot) | Out-Null
$fixture = Get-Content "$stateRoot/installer-tests/latest.json" -Raw | ConvertFrom-Json
if ($fixture.database -notmatch '^sharetronix_test_[0-9a-f_]+$') { throw 'Run the fixed installer test first.' }
$snapshots = @{}
function Comparison-Snapshot($Ref) {
    $commit = & git -C $repoRoot rev-parse --verify "$Ref^{commit}"
    if ($LASTEXITCODE -ne 0) { throw 'Comparison source must resolve to a commit.' }
    if ($snapshots.ContainsKey($commit)) { return $snapshots[$commit] }
    $root = Join-Path $runRoot $commit.Substring(0, 12)
    [IO.Directory]::CreateDirectory("$root/.local/installer-tests") | Out-Null
    & git -C $repoRoot archive --format=zip "--output=$root/source.zip" $commit -- system
    if ($LASTEXITCODE -ne 0) { throw 'Could not export comparison source.' }
    Expand-Archive -LiteralPath "$root/source.zip" -DestinationPath $root
    Copy-Item -LiteralPath "$repoRoot/tests" -Destination "$root/tests" -Recurse
    Copy-Item -LiteralPath "$stateRoot/credentials.json" -Destination "$root/.local/credentials.json"
    Copy-Item -LiteralPath "$stateRoot/installer-tests/latest.json" -Destination "$root/.local/installer-tests/latest.json"
    [IO.Directory]::CreateDirectory("$root/.local/site") | Out-Null
    Copy-Item -LiteralPath "$root/system" -Destination "$root/.local/site/system" -Recurse
    Copy-Item -LiteralPath "$($fixture.root)/site/system/conf_main.php" -Destination "$root/.local/site/system/conf_main.php"
    $snapshots[$commit] = [pscustomobject]@{ root = $root; commit = $commit }
    return $snapshots[$commit]
}
$cases = @(
    @{ name = 'session'; before = '0a34c63'; test = 'session.php'; version = '7.2'; error = 'Incorrect session or cookie scope' },
    @{ name = 'autoload'; before = '0a34c63'; test = 'autoload.php'; version = '7.2'; error = '__autoload.*deprecated' },
    @{ name = 'private-temp'; before = '0a34c63'; test = 'temp-cleanup.php'; version = '7.2'; error = 'must define its private temporary directory' },
    @{ name = 'retired-version-check'; before = '0a34c63'; test = 'token-cleanup.php'; version = '7.2'; noIni = $true; error = 'undefined function curl_init' },
    # The parent of the SQL fix has already removed the retired network request.
    @{ name = 'token-expiry'; before = '95f7b2d'; test = 'token-expiry.php'; version = '7.2'; error = "Unknown column 'timestamp'" },
    @{ name = 'offsets-legacy-behavior'; before = '0a34c63'; test = 'php-offsets.php'; version = '7.2'; noIni = $true; beforePass = $true },
    @{ name = 'offsets-modern-parser'; before = '0a34c63'; test = 'php-offsets.php'; version = '8.4'; noIni = $true; error = 'unexpected token.*\{|curly braces.*no longer supported' },
    @{ name = 'daily-database'; before = '0a34c63'; test = 'daily-database.php'; version = '7.2'; error = 'users_rssfeeds_posts' },
    @{ name = 'daily-cache-path'; before = '0a34c63'; test = 'daily-cache-cleanup.php'; version = '7.2'; noIni = $true; error = 'unlink\(' }
)
$results = [Collections.Generic.List[object]]::new()
foreach ($case in $cases) {
    foreach ($side in @('before', 'after')) {
        $snapshot = Comparison-Snapshot $(if ($side -eq 'before') { $case.before } else { $AfterRef })
        $php = Join-Path $RuntimeRoot "PHP-$($case.version)/php.exe"
        [string[]]$arguments = if ($case.noIni) { @('-n') } else { @('-c', "$stateRoot/php.ini") }
        $arguments += @('-d', 'display_errors=1', '-d', 'log_errors=0')
        $output = & $php @arguments "$($snapshot.root)/tests/$($case.test)" 2>&1
        $exitCode = $LASTEXITCODE
        $text = $output -join "`n"
        [IO.File]::WriteAllText("$runRoot/$($case.name)-$side.txt", $text, [Text.UTF8Encoding]::new($false))
        $expectedPass = $side -eq 'after' -or $case.beforePass
        $matched = if ($expectedPass) { $exitCode -eq 0 -and $text.Contains('PASS:') } else { $exitCode -ne 0 -and $text -match $case.error }
        $results.Add([pscustomobject]@{ name = $case.name; side = $side; source = $snapshot.commit; php = $case.version; exitCode = $exitCode; expectedPass = [bool]$expectedPass; matched = [bool]$matched })
    }
}
$results | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath "$runRoot/results.json"
$results | Format-Table name, side, php, exitCode, expectedPass, matched -AutoSize
if ($results | Where-Object { -not $_.matched }) { throw "Unexpected before/after results; inspect $runRoot" }
Write-Output "PASS: nine before/after comparisons matched their expected behavior; private evidence at $runRoot"
