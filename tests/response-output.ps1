#requires -Version 7.0
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$fileName = 'regression-output-' + [guid]::NewGuid().ToString('N') + '.php'
$probePath = Join-Path $repoRoot ".local/site/$fileName"
$probeSource = @'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
while (ob_get_level() > 0) { ob_end_clean(); }
require __DIR__.'/index.php';
'@
$client = [Net.Http.HttpClient]::new()
try {
    [IO.File]::WriteAllText($probePath, $probeSource, [Text.UTF8Encoding]::new($false))
    $client.DefaultRequestHeaders.Add('Accept-Encoding', 'gzip')
    $response = $client.GetAsync("http://localhost:8092/$fileName").GetAwaiter().GetResult()
    if ([int]$response.StatusCode -ne 200 -or -not $response.Content.Headers.ContentEncoding.Contains('gzip')) {
        throw 'Expected a successful compressed application response.'
    }
    $bytes = $response.Content.ReadAsByteArrayAsync().GetAwaiter().GetResult()
    $stream = [IO.MemoryStream]::new($bytes)
    $gzip = [IO.Compression.GZipStream]::new($stream, [IO.Compression.CompressionMode]::Decompress)
    $reader = [IO.StreamReader]::new($gzip)
    try { $body = $reader.ReadToEnd() } finally { $reader.Dispose(); $stream.Dispose() }
    if ($body -notmatch '^\s*<!DOCTYPE html>' -or $body -match 'Deprecated|<b>Warning</b>|<b>Fatal error</b>') {
        throw 'The compressed response contains unexpected diagnostic output.'
    }
    Write-Output 'PASS: E_ALL with displayed errors produces valid gzip HTML without diagnostic output.'
} finally {
    $client.Dispose()
    if (Test-Path -LiteralPath $probePath) { Remove-Item -LiteralPath $probePath }
}
