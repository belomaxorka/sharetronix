#requires -Version 7.0
param([string]$PhpExecutable = 'C:\OSPanel\modules\PHP-8.4\php.exe')
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$paths = & git -C $repoRoot -c core.quotePath=false ls-files '*.php'
if ($LASTEXITCODE -ne 0) { throw 'Could not enumerate tracked PHP files.' }
$failures = [Collections.Generic.List[object]]::new()
foreach ($path in $paths) {
    $output = & $PhpExecutable -n -d short_open_tag=On -d error_reporting=-1 -d display_errors=1 -d log_errors=0 -l (Join-Path $repoRoot $path) 2>&1
    if ($LASTEXITCODE -ne 0 -or ($output -join "`n") -match '(?im)^(PHP )?(Deprecated|Warning|Notice|Fatal error|Parse error):') { $failures.Add([pscustomobject]@{ path = $path; error = ($output -join "`n") }) }
}
if ($failures.Count) {
    $failures | Format-List
    throw "$($failures.Count) of $($paths.Count) PHP files failed syntax/definition checks."
}
Write-Output "PASS: $($paths.Count) tracked PHP files passed syntax/definition checks with short tags and E_ALL enabled."
