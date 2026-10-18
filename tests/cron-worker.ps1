#requires -Version 7.0
param([string]$SourceRef = '', [ValidateSet('7.2', '8.4')][string]$PhpVersion = '7.2')
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$stateRoot = Join-Path $repoRoot '.local'
$phpExecutable = "C:/OSPanel/modules/PHP-$PhpVersion/php.exe"
$fixture = Get-Content -LiteralPath "$stateRoot/installer-tests/latest.json" -Raw | ConvertFrom-Json
$testRoot = [IO.Path]::GetFullPath($fixture.root)
$allowedRoot = [IO.Path]::GetFullPath((Join-Path $stateRoot 'installer-tests')) + [IO.Path]::DirectorySeparatorChar
if (-not $testRoot.StartsWith($allowedRoot, [StringComparison]::OrdinalIgnoreCase) -or $fixture.database -notmatch '^sharetronix_test_[0-9a-f_]+$') {
    throw 'Expected a disposable installer-test fixture.'
}
if ($SourceRef) {
    $sourceCommit = & git -C $repoRoot rev-parse --verify "$SourceRef^{commit}"
    if ($LASTEXITCODE -ne 0) { throw 'The worker source revision must resolve to a commit.' }
    & git -C $repoRoot archive --format=zip "--output=$testRoot/cron-source.zip" $sourceCommit -- system
    if ($LASTEXITCODE -ne 0) { throw 'Could not export the worker source.' }
    Expand-Archive -LiteralPath "$testRoot/cron-source.zip" -DestinationPath "$testRoot/site" -Force
} else {
    $paths = & git -C $repoRoot -c core.quotePath=false ls-files -- system
    if ($LASTEXITCODE -ne 0) { throw 'Could not enumerate worker source files.' }
    foreach ($path in $paths) { Copy-Item -LiteralPath (Join-Path $repoRoot $path) -Destination (Join-Path "$testRoot/site" $path) -Force }
}
$client = 'C:\OSPanel\modules\MySQL-5.7\bin\mysql.exe'
$pluginRoot = "$testRoot/site/apps/RegressionCronPlugin"
[IO.Directory]::CreateDirectory($pluginRoot) | Out-Null
[IO.File]::WriteAllText("$pluginRoot/plugin.php", '<?php class RegressionCronPlugin {}', [Text.UTF8Encoding]::new($false))
foreach ($interval in @('1min', '1h')) {
    [IO.Directory]::CreateDirectory("$pluginRoot/system/cronjobs/php_$interval") | Out-Null
    [IO.File]::WriteAllText("$pluginRoot/system/cronjobs/php_$interval/probe.php", "<?php echo 'REGRESSION_PLUGIN_$interval';", [Text.UTF8Encoding]::new($false))
}
& $client "--defaults-file=$stateRoot/client.ini" -e "DELETE FROM $($fixture.database).plugins WHERE name='RegressionCronPlugin'; INSERT INTO $($fixture.database).plugins (name,is_installed,date_installed,installed_by_user_id) VALUES ('RegressionCronPlugin',1,UNIX_TIMESTAMP(),1); UPDATE $($fixture.database).crons SET is_running=0, next_run=0;"
if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the disposable cron schedule.' }
$runner = @'
<?php
require __DIR__.'/site/system/cronjobs/worker.php';
echo "\nREGRESSION_WORKER_COMPLETE\n";
'@
[IO.File]::WriteAllText("$testRoot/run-worker.php", $runner, [Text.UTF8Encoding]::new($false))
$phpConfig = "$testRoot/cron-php$PhpVersion.ini"
$phpLog = "$testRoot/logs/cron-php$PhpVersion-$([guid]::NewGuid().ToString('N')).log"
$phpIni = [IO.File]::ReadAllText("$stateRoot/php.ini")
$phpIni = [regex]::Replace($phpIni, 'extension_dir=.*', "extension_dir=`"C:/OSPanel/modules/PHP-$PhpVersion/ext`"")
$phpIni = $phpIni.Replace('php_gd2.dll', $(if ($PhpVersion -eq '8.4') { 'php_gd.dll' } else { 'php_gd2.dll' }))
$phpIni = [regex]::Replace($phpIni, 'error_log=.*', ('error_log="' + $phpLog.Replace('\', '/') + '"'))
[IO.File]::WriteAllText($phpConfig, $phpIni, [Text.UTF8Encoding]::new($false))
$output = & $phpExecutable -c $phpConfig "$testRoot/run-worker.php" 2>&1
$workerExit = $LASTEXITCODE
$output | Set-Content -LiteralPath "$testRoot/cron-output.txt"
$text = $output -join "`n"
$logText = if (Test-Path -LiteralPath $phpLog) { Get-Content -LiteralPath $phpLog -Raw } else { '' }
$running = & $client "--defaults-file=$stateRoot/client.ini" -N -e "SELECT COUNT(*) FROM $($fixture.database).crons WHERE is_running=1;"
$pluginsCompleted = $text.Contains('REGRESSION_PLUGIN_1min') -and $text.Contains('REGRESSION_PLUGIN_1h')
$result = [pscustomobject]@{ phpVersion = $PhpVersion; exitCode = $workerExit; completed = $text.Contains('REGRESSION_WORKER_COMPLETE'); diagnostics = (($text + $logText) -match 'Warning:|Notice:|Deprecated:|Fatal error:'); runningJobs = [int]$running; pluginJobsCompleted = $pluginsCompleted; evidence = "$testRoot/cron-output.txt"; phpLog = $phpLog }
$result | ConvertTo-Json | Set-Content -LiteralPath "$testRoot/cron-result.json"
$result | Format-List
if ($workerExit -ne 0 -or -not $result.completed -or $result.diagnostics -or $result.runningJobs -ne 0 -or -not $pluginsCompleted) {
    throw 'Cron worker did not finish cleanly. Inspect the isolated fixture output.'
}
$scheduled = & $client "--defaults-file=$stateRoot/client.ini" -N -e "SELECT COUNT(*) FROM $($fixture.database).crons WHERE is_running=0 AND next_run>last_run AND last_run>0;"
if ($LASTEXITCODE -ne 0 -or [int]$scheduled -ne 8) { throw 'Every interval must be scheduled for its next run.' }
$repeat = & $phpExecutable -c $phpConfig "$testRoot/run-worker.php" 2>&1
$repeatExit = $LASTEXITCODE
$repeat | Set-Content -LiteralPath "$testRoot/cron-repeat-output.txt"
$repeatText = $repeat -join "`n"
$logText = if (Test-Path -LiteralPath $phpLog) { Get-Content -LiteralPath $phpLog -Raw } else { '' }
if ($repeatExit -ne 0 -or -not $repeatText.Contains('REGRESSION_WORKER_COMPLETE') -or ($repeatText + $logText) -match 'RUNNING CRON:|REGRESSION_PLUGIN_|Warning:|Notice:|Deprecated:|Fatal error:') {
    throw 'An immediate repeat must complete without rerunning jobs that are not due.'
}
Write-Output 'PASS: all eight intervals and plugin jobs completed cleanly; an immediate repeat ran no jobs.'
