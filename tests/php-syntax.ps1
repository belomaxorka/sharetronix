#requires -Version 7.0
param([string]$PhpExecutable = 'C:\OSPanel\modules\PHP-8.4\php.exe')
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$paths = & git -C $repoRoot -c core.quotePath=false ls-files '*.php'
if ($LASTEXITCODE -ne 0) { throw 'Could not enumerate tracked PHP files.' }
$failures = [Collections.Generic.List[object]]::new()
foreach ($path in $paths) {
    $output = & $PhpExecutable -n -d short_open_tag=On -l (Join-Path $repoRoot $path) 2>&1
    if ($LASTEXITCODE -ne 0) { $failures.Add([pscustomobject]@{ path = $path; error = ($output -join "`n") }) }
}
if ($failures.Count) {
    $failures | Format-List
    throw "$($failures.Count) of $($paths.Count) PHP files failed syntax checks."
}
Write-Output "PASS: $($paths.Count) tracked PHP files passed syntax checks with short tags enabled."
