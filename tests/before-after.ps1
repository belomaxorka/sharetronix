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
    & git -C $repoRoot archive --format=zip "--output=$root/source.zip" $commit -- system static themes apps
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
    @{ name = 'daily-cache-path'; before = '0a34c63'; test = 'daily-cache-cleanup.php'; version = '7.2'; noIni = $true; error = 'unlink\(' },
    @{ name = 'php84-signatures'; before = '42ba595'; test = 'php-signatures.php'; version = '8.4'; noIni = $true; error = 'Optional parameter' },
    @{ name = 'php84-object-state'; before = 'bb4c9cb'; test = 'php-runtime-state.php'; version = '8.4'; noIni = $true; error = 'dynamic property network::' },
    @{ name = 'php84-cookie-values'; before = '3e0f072'; test = 'remember-cookie.php'; version = '8.4'; noIni = $true; error = 'Passing null' },
    @{ name = 'activity-saved-type'; before = 'a08dd70'; test = 'activity-result.php'; version = '8.4'; noIni = $true; error = 'array offset on int' },
    @{ name = 'php84-langpack'; before = '90c534f'; test = 'langpack-constructor.php'; version = '8.4'; noIni = $true; error = 'Construct language metadata' },
    @{ name = 'php84-post-links'; before = 'c31463a'; test = 'post-links.php'; version = '8.4'; noIni = $true; error = 'undefined function create_function' },
    @{ name = 'php84-post-dates'; before = 'eb959a1'; test = 'post-dates.php'; version = '8.4'; noIni = $true; error = 'strftime.*deprecated' },
    @{ name = 'legacy-date-output'; before = 'eb959a1'; test = 'post-dates.php'; version = '7.2'; noIni = $true; beforePass = $true },
    @{ name = 'php84-feed-polling'; before = '0f2c131'; test = 'activity-polling.php'; version = '8.4'; noIni = $true; error = 'dynamic property activityDashboard::' }
)
$configs = @{}
foreach ($version in @('7.2','8.4')) {
    $config = [IO.File]::ReadAllText("$stateRoot/php.ini")
    $extensionDir = (Join-Path $RuntimeRoot "PHP-$version/ext").Replace('\', '/')
    $config = [regex]::Replace($config, 'extension_dir=.*', "extension_dir=`"$extensionDir`"")
    $config = [regex]::Replace($config, 'extension=php_gd2?\.dll', $(if ($version -eq '7.2') { 'extension=php_gd2.dll' } else { 'extension=php_gd.dll' }))
    $configs[$version] = "$runRoot/php-$version.ini"
    [IO.File]::WriteAllText($configs[$version], $config, [Text.UTF8Encoding]::new($false))
}
$results = [Collections.Generic.List[object]]::new()
foreach ($case in $cases) {
    foreach ($side in @('before', 'after')) {
        $snapshot = Comparison-Snapshot $(if ($side -eq 'before') { $case.before } else { $AfterRef })
        $php = Join-Path $RuntimeRoot "PHP-$($case.version)/php.exe"
        [string[]]$arguments = if ($case.noIni) { @('-n') } else { @('-c', $configs[$case.version]) }
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
Write-Output "PASS: $($cases.Count) before/after comparisons matched their expected behavior; private evidence at $runRoot"
