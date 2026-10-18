#requires -Version 7.0
param(
    [string]$RuntimeRoot = 'C:\OSPanel\modules',
    [string]$SourceRef = '',
    [switch]$ObserveBaseline
)
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$stateRoot = Join-Path $repoRoot '.local'
$runId = (Get-Date -Format 'yyyyMMdd_HHmmss') + '_' + [guid]::NewGuid().ToString('N').Substring(0, 6)
$testRoot = Join-Path $stateRoot "installer-tests/$runId"
$siteRoot = Join-Path $testRoot 'site'
$database = 'sharetronix_test_' + $runId
$baseUrl = 'http://localhost:8093'
$credentials = Get-Content -LiteralPath "$stateRoot/credentials.json" -Raw | ConvertFrom-Json
$encoding = [Text.UTF8Encoding]::new($false)
$server = $null
$checks = [Collections.Generic.List[string]]::new()
$sourceCommit = & git -C $repoRoot rev-parse --verify $(if ($SourceRef) { "$SourceRef^{commit}" } else { 'HEAD^{commit}' })
if ($LASTEXITCODE -ne 0) { throw 'The source revision must resolve to a commit.' }
if ($ObserveBaseline -and -not $SourceRef) { throw 'Baseline observations require an explicit source revision.' }
function Check-Installer($Name, $Condition) {
    if (-not $Condition) { throw "Installer regression failed: $Name" }
    $checks.Add($Name)
}
if (Get-NetTCPConnection -State Listen -LocalPort 8093 -ErrorAction SilentlyContinue) { throw 'Test port 8093 is occupied.' }
if ($credentials.database -ne 'sharetronix_local') { throw 'Expected the isolated local stack.' }
[IO.Directory]::CreateDirectory("$testRoot/logs") | Out-Null
if ($SourceRef) {
    & git -C $repoRoot archive --format=zip "--output=$testRoot/source.zip" $sourceCommit -- index.php .htaccess web.config apps install static storage system themes
    if ($LASTEXITCODE -ne 0) { throw 'Could not export the requested application revision.' }
    Expand-Archive -LiteralPath "$testRoot/source.zip" -DestinationPath $siteRoot
} else {
    $paths = & git -C $repoRoot -c core.quotePath=false ls-files -- index.php .htaccess web.config apps install static storage system themes
    if ($LASTEXITCODE -ne 0) { throw 'Could not enumerate application files.' }
    foreach ($path in $paths) {
        $destination = Join-Path $siteRoot $path
        [IO.Directory]::CreateDirectory((Split-Path -Parent $destination)) | Out-Null
        Copy-Item -LiteralPath (Join-Path $repoRoot $path) -Destination $destination
    }
}
$clientExe = Join-Path $RuntimeRoot 'MySQL-5.7/bin/mysql.exe'
& $clientExe "--defaults-file=$stateRoot/client.ini" -e "CREATE DATABASE $database CHARACTER SET utf8 COLLATE utf8_unicode_ci; GRANT ALL PRIVILEGES ON $database.* TO '$($credentials.user)'@'localhost';"
if ($LASTEXITCODE -ne 0) { throw 'Could not create the disposable installation database.' }
$config = [IO.File]::ReadAllText("$stateRoot/httpd.conf")
$config = $config.Replace('127.0.0.1:8092', '127.0.0.1:8093').Replace('localhost:8092', 'localhost:8093')
$config = $config.Replace(($stateRoot.Replace('\', '/') + '/site'), $siteRoot.Replace('\', '/'))
$config = $config.Replace(($stateRoot.Replace('\', '/') + '/logs'), ($testRoot.Replace('\', '/') + '/logs'))
$config = $config.Replace(($stateRoot.Replace('\', '/') + '/httpd.pid'), ($testRoot.Replace('\', '/') + '/httpd.pid'))
# Keep the fixture free of any server-level installer block.
$config = [regex]::Replace($config, '(?s)<LocationMatch "\^/install.*?</LocationMatch>\r?\n?', '')
[IO.File]::WriteAllText("$testRoot/httpd.conf", $config, $encoding)
$apacheExe = [IO.Path]::GetFullPath((Join-Path $RuntimeRoot 'Apache/bin/httpd.exe'))
& $apacheExe -t -f "$testRoot/httpd.conf"
if ($LASTEXITCODE -ne 0) { throw 'Invalid test Apache configuration.' }
$handler = [Net.Http.HttpClientHandler]::new()
$handler.AllowAutoRedirect = $false
$handler.CookieContainer = [Net.CookieContainer]::new()
$http = [Net.Http.HttpClient]::new($handler)
$http.Timeout = [TimeSpan]::FromSeconds(15)
function Request-Installer($Url, $Data = $null, $Client = $http) {
    if (-not $Url.StartsWith('http')) { $Url = $baseUrl + $Url }
    $uri = [Uri]$Url
    if ($uri.Host -ne 'localhost' -or $uri.Port -ne 8093) { throw 'Refusing an off-site installer request.' }
    $request = [Net.Http.HttpRequestMessage]::new($(if ($null -eq $Data) { [Net.Http.HttpMethod]::Get } else { [Net.Http.HttpMethod]::Post }), $Url)
    if ($null -ne $Data) {
        $form = [Collections.Generic.Dictionary[string, string]]::new()
        foreach ($key in $Data.Keys) { $form.Add($key, [string]$Data[$key]) }
        $request.Content = [Net.Http.FormUrlEncodedContent]::new($form)
    }
    try {
        $response = $Client.SendAsync($request).GetAwaiter().GetResult()
        $body = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
        [pscustomobject]@{ status = [int]$response.StatusCode; body = $body; location = $response.Headers.Location; url = $Url }
    } finally { $request.Dispose() }
}
function Follow-Installer($Response, $Client = $http) {
    for ($redirect = 0; $redirect -lt 10 -and $Response.location; $redirect++) {
        $target = [Uri]::new([Uri]$Response.url, $Response.location)
        $Response = Request-Installer $target.AbsoluteUri $null $Client
    }
    $Response
}
try {
    $server = Start-Process -FilePath $apacheExe -ArgumentList @('-f', "`"$testRoot/httpd.conf`"") -WindowStyle Hidden -PassThru -RedirectStandardOutput "$testRoot/logs/process-out.log" -RedirectStandardError "$testRoot/logs/process-err.log"
    $serverStart = $server.StartTime.ToUniversalTime()
    $ready = $false
    for ($attempt = 0; $attempt -lt 40; $attempt++) {
        $connection = [Net.Sockets.TcpClient]::new()
        try { $connection.Connect('127.0.0.1', 8093); $ready = $true; break } catch { Start-Sleep -Milliseconds 250 } finally { $connection.Dispose() }
    }
    if (-not $ready) { throw 'Test Apache did not start.' }
    $response = Request-Installer '/install/'
    Check-Installer 'Fresh installation is available' ($response.status -eq 200 -and $response.body.Contains('Start Installation'))
    $response = Follow-Installer (Request-Installer '/install/' @{ accept1 = '1'; submit = 'Install' })
    Check-Installer 'License step reaches database setup' ($response.body.Contains('name="MYSQL_HOST"'))
    $response = Follow-Installer (Request-Installer '/install/' @{ MYSQL_HOST = '127.0.0.1:33062'; MYSQL_USER = $credentials.user; MYSQL_PASS = $credentials.password; MYSQL_DBNAME = $database; submit = 'Next' })
    Check-Installer 'Database and writable paths reach account setup' ($response.body.Contains('name="ADMIN_USER"'))
    $response = Follow-Installer (Request-Installer '/install/' @{ ADMIN_USER = 'qainstall'; ADMIN_PASS = $credentials.adminPassword; ADMIN_PASS2 = $credentials.adminPassword; ADMIN_EMAIL = 'installer@localhost.test'; submit = 'Next' })
    Check-Installer 'Administrative account reaches review' ($response.body.Contains('Finish Installation'))
    $response = Request-Installer '/install/' @{ submit = 'Install' }
    Check-Installer 'Successful finish redirects to the application' ($response.status -eq 302 -and $response.location.AbsoluteUri -eq "$baseUrl/?installed=ok")
    [IO.File]::WriteAllText("$testRoot/finish-body.html", $response.body, $encoding)
    if ($ObserveBaseline) {
        Check-Installer 'KNOWN BASELINE DEFECT: finish renders cleared-session notices' ($response.body -match 'Undefined index: INSTALL_(STEP|DATA)')
    } else {
        Check-Installer 'Successful finish stops before rendering cleared session data' ([string]::IsNullOrWhiteSpace($response.body))
    }
    $response = Follow-Installer $response
    Check-Installer 'New application opens after installation' ($response.status -eq 200 -and $response.body.Contains('name="password"'))
    $response = Follow-Installer (Request-Installer '/signin' @{ email = 'qainstall'; password = $credentials.adminPassword })
    if ($ObserveBaseline) {
        Check-Installer 'KNOWN BASELINE DEFECT: generated port-bearing domain loses the login session' ($response.url -eq "$baseUrl/signin")
        $configPath = "$siteRoot/system/conf_main.php"
        $config = [IO.File]::ReadAllText($configPath)
        $config = [regex]::Replace($config, '(\$C->DOMAIN\s*=\s*)''[^'']*'';', '$1''localhost'';')
        $config = [regex]::Replace($config, '(\$C->RPC_PINGS_ON\s*=\s*)TRUE;', '$1FALSE;')
        [IO.File]::WriteAllText($configPath, $config, $encoding)
        $response = Follow-Installer (Request-Installer '/signin' @{ email = 'qainstall'; password = $credentials.adminPassword })
        Check-Installer 'Baseline comparison uses the documented local domain workaround' ($response.url -eq "$baseUrl/dashboard")
        & $clientExe "--defaults-file=$stateRoot/client.ini" -e "INSERT INTO $database.posts SET user_id=0, api_id=0, message='Installer guard sentinel', date=UNIX_TIMESTAMP();"
        if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the baseline updater sentinel.' }
        $guestHttp = [Net.Http.HttpClient]::new()
        try { $response = Request-Installer '/install/' $null $guestHttp } finally { $guestHttp.Dispose() }
        $sentinel = & $clientExe "--defaults-file=$stateRoot/client.ini" -N -e "SELECT COUNT(*) FROM $database.posts WHERE message='Installer guard sentinel';"
        Check-Installer 'KNOWN BASELINE DEFECT: guest updater executes cleanup' ($response.status -eq 200 -and $response.body.Contains('Your community has been updated') -and $sentinel -eq '0')
        . "$PSScriptRoot/application-flows.ps1"
        $result = @{ root = $testRoot; database = $database; sourceCommit = $sourceCommit; mode = 'baseline-with-documented-domain-workaround'; checks = $checks.ToArray() }
        [IO.File]::WriteAllText("$testRoot/results.json", ($result | ConvertTo-Json -Depth 3), $encoding)
        Write-Output "PASS: $($checks.Count) baseline observations/application checks; isolated evidence at $testRoot"
        return
    }
    Check-Installer 'Fresh administrator retains a session with the generated port-bearing domain' ($response.url -eq "$baseUrl/dashboard" -and $response.body.Contains('qainstall'))
    $configPath = "$siteRoot/system/conf_main.php"
    $config = [regex]::Replace([IO.File]::ReadAllText($configPath), '(\$C->RPC_PINGS_ON\s*=\s*)TRUE;', '$1FALSE;')
    [IO.File]::WriteAllText($configPath, $config, $encoding)
    & $clientExe "--defaults-file=$stateRoot/client.ini" -e "INSERT INTO $database.posts SET user_id=0, api_id=0, message='Installer guard sentinel', date=UNIX_TIMESTAMP();"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare update protection fixture.' }
    $guestHttp = [Net.Http.HttpClient]::new()
    try {
        foreach ($entry in @('/install/', '/install/index.php', '/install/old/index.php')) {
            $response = Request-Installer $entry $null $guestHttp
            Check-Installer "Guest cannot execute $entry" ($response.status -eq 403)
            $response = Request-Installer $entry @{ confirm_update = '1'; installer_token = 'invalid' } $guestHttp
            Check-Installer "Guest POST cannot execute $entry" ($response.status -eq 403)
        }
    } finally { $guestHttp.Dispose() }
    function Sentinel-Count {
        & $clientExe "--defaults-file=$stateRoot/client.ini" -N -e "SELECT COUNT(*) FROM $database.posts WHERE message='Installer guard sentinel';"
    }
    Check-Installer 'Guest requests do not execute update cleanup' ((Sentinel-Count) -eq '1')
    & $clientExe "--defaults-file=$stateRoot/client.ini" -e "INSERT INTO $database.users (username,email,password,fullname,active,language,timezone,is_network_admin) SELECT 'qainstallmember','member@localhost.test',password,'Installer member',1,'en','UTC',0 FROM $database.users WHERE username='qainstall';"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare ordinary updater user.' }
    $memberHandler = [Net.Http.HttpClientHandler]::new()
    $memberHandler.AllowAutoRedirect = $false
    $memberHandler.CookieContainer = [Net.CookieContainer]::new()
    $memberHttp = [Net.Http.HttpClient]::new($memberHandler)
    try {
        $response = Follow-Installer (Request-Installer '/signin' @{ email = 'qainstallmember'; password = $credentials.adminPassword } $memberHttp) $memberHttp
        Check-Installer 'Ordinary updater fixture can sign in' ($response.url -eq "$baseUrl/dashboard")
        $response = Request-Installer '/install/' $null $memberHttp
        Check-Installer 'Ordinary user cannot open the updater' ($response.status -eq 403)
        $response = Request-Installer '/install/' @{ confirm_update = '1'; installer_token = 'invalid' } $memberHttp
        Check-Installer 'Ordinary user cannot confirm an update' ($response.status -eq 403)
    } finally { $memberHttp.Dispose() }
    $response = Request-Installer '/install/'
    Check-Installer 'Administrator GET offers confirmation instead of running an update' ($response.status -eq 200 -and $response.body.Contains('name="installer_token"'))
    $tokenMatch = [regex]::Match($response.body, 'name="installer_token" value="([^"]+)"')
    if (-not $tokenMatch.Success) { throw 'Updater confirmation token is missing.' }
    Check-Installer 'Administrator GET preserves update cleanup data' ((Sentinel-Count) -eq '1')
    $response = Request-Installer '/install/' @{ confirm_update = '1'; installer_token = 'invalid' }
    Check-Installer 'Administrator POST requires a valid confirmation token' ($response.status -eq 403)
    $response = Request-Installer '/install/' @{ submit = 'unconfirmed' }
    Check-Installer 'Installer POST cannot bypass update confirmation' ($response.status -eq 403)
    Check-Installer 'Rejected administrator POST preserves update cleanup data' ((Sentinel-Count) -eq '1')
    & $clientExe "--defaults-file=$stateRoot/client.ini" -e "UPDATE $database.users SET is_network_admin=0 WHERE username='qainstall';"
    $response = Request-Installer '/install/'
    Check-Installer 'Revoked administration is checked against the database' ($response.status -eq 403)
    & $clientExe "--defaults-file=$stateRoot/client.ini" -e "UPDATE $database.users SET is_network_admin=1 WHERE username='qainstall';"
    $response = Request-Installer '/install/' @{ confirm_update = '1'; installer_token = $tokenMatch.Groups[1].Value }
    Check-Installer 'Administrator can explicitly run an authorized update' ($response.status -eq 200 -and $response.body.Contains('Your community has been updated'))
    Check-Installer 'Confirmed update actually executes its cleanup' ((Sentinel-Count) -eq '0')
    $response = Request-Installer '/install/'
    Check-Installer 'A completed update requires a new confirmation' ($response.status -eq 200 -and $response.body.Contains('name="installer_token"'))
    $response = Request-Installer '/install/old/index.php'
    Check-Installer 'Obsolete installer remains disabled for administrators' ($response.status -eq 403)
    $response = Request-Installer '/cron'
    [IO.File]::WriteAllText("$testRoot/http-cron-body.txt", $response.body, $encoding)
    Check-Installer 'HTTP cron fallback completes on the installed application' ($response.status -eq 200 -and $response.body.Trim() -eq 'OK')
    . "$PSScriptRoot/application-flows.ps1"
    $result = @{ root = $testRoot; database = $database; sourceCommit = $sourceCommit; mode = 'fixed'; checks = $checks.ToArray() }
    [IO.File]::WriteAllText("$testRoot/results.json", ($result | ConvertTo-Json -Depth 3), $encoding)
    [IO.File]::WriteAllText("$stateRoot/installer-tests/latest.json", ($result | ConvertTo-Json -Depth 3), $encoding)
    Write-Output "PASS: $($checks.Count) installation checks; isolated evidence at $testRoot"
} finally {
    $http.Dispose()
    if ($server) {
        $running = Get-Process -Id $server.Id -ErrorAction SilentlyContinue
        if ($running -and $running.Path -eq $apacheExe -and $running.StartTime.ToUniversalTime() -eq $serverStart) {
            $children = Get-CimInstance Win32_Process | Where-Object { $_.ParentProcessId -eq $running.Id -and $_.ExecutablePath -eq $apacheExe }
            Stop-Process -Id $running.Id
            foreach ($child in $children) { Stop-Process -Id $child.ProcessId -ErrorAction SilentlyContinue }
        }
    }
}
