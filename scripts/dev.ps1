param(
    [ValidateSet('Start', 'Stop', 'Status', 'Sync', 'Configure', 'Backup', 'Reset')]
    [string]$Action = 'Start',
    [string]$RuntimeRoot = 'C:\OSPanel\modules',
    [ValidateSet('7.2', '8.4')]
    [string]$PhpVersion = '7.2'
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$stateRoot = Join-Path $repoRoot '.local'
$siteRoot = Join-Path $stateRoot 'site'
$stateFile = Join-Path $stateRoot 'processes.json'
$utf8 = New-Object System.Text.UTF8Encoding($false)
function Write-LocalFile($Path, $Content) {
    [System.IO.File]::WriteAllText($Path, $Content, $utf8)
}
function UnixPath($Path) { $Path.Replace('\', '/') }
function Wait-LocalPort($Port) {
    for ($attempt = 0; $attempt -lt 60; $attempt++) {
        $client = New-Object System.Net.Sockets.TcpClient
        try { $client.Connect('127.0.0.1', $Port); return } catch { Start-Sleep -Milliseconds 250 } finally { $client.Dispose() }
    }
    throw "Service did not start on port $Port. Inspect .local/logs."
}
function Sync-Site {
    $paths = & git -C $repoRoot -c core.quotePath=false ls-files -- index.php .htaccess web.config apps install static storage system themes
    if ($LASTEXITCODE -ne 0) { throw 'Cannot enumerate application files.' }
    foreach ($path in $paths) {
        $destination = Join-Path $siteRoot $path
        [System.IO.Directory]::CreateDirectory((Split-Path -Parent $destination)) | Out-Null
        Copy-Item -LiteralPath (Join-Path $repoRoot $path) -Destination $destination -Force
    }
}
function Configure-Site {
    $configPath = Join-Path $siteRoot 'system/conf_main.php'
    if (-not (Test-Path -LiteralPath $configPath)) { throw 'Complete installation before running Configure.' }
    $config = [System.IO.File]::ReadAllText($configPath)
    # Local-only workarounds: DOMAIN is a hostname, SITE_URL retains the port.
    $config = [regex]::Replace($config, '(\$C->DOMAIN\s*=\s*)''[^'']*'';', '$1''localhost'';')
    $config = [regex]::Replace($config, '(\$C->RPC_PINGS_ON\s*=\s*)TRUE;', '$1FALSE;')
    Write-LocalFile $configPath $config
}
if ($Action -eq 'Backup') {
    $backupRoot = Join-Path $stateRoot ('backups/' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))
    [System.IO.Directory]::CreateDirectory($backupRoot) | Out-Null
    & "$RuntimeRoot/MySQL-5.7/bin/mysqldump.exe" "--defaults-file=$stateRoot/client.ini" --lock-all-tables --default-character-set=utf8 "--result-file=$backupRoot/database.sql" sharetronix_local
    if ($LASTEXITCODE -ne 0) { throw 'Database backup failed; do not use this incomplete snapshot.' }
    Copy-Item -LiteralPath "$siteRoot/system/conf_main.php" -Destination "$backupRoot/conf_main.php"
    Copy-Item -LiteralPath "$siteRoot/storage" -Destination "$backupRoot/storage" -Recurse
    Write-Output "Local backup: $backupRoot"
    exit
}
if ($Action -eq 'Reset') {
    if (Test-Path -LiteralPath $stateRoot) {
        & $PSCommandPath Stop -RuntimeRoot $RuntimeRoot
        if ($LASTEXITCODE -ne 0) { throw 'Could not stop the local stack.' }
        $resolvedState = [System.IO.Path]::GetFullPath($stateRoot)
        $archivePath = [System.IO.Path]::GetFullPath((Join-Path $repoRoot ('.local-archive-' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))))
        if ($resolvedState -ne (Join-Path $repoRoot '.local') -or (Split-Path -Parent $archivePath) -ne $repoRoot) {
            throw 'Refusing to move state outside this repository.'
        }
        Move-Item -LiteralPath $resolvedState -Destination $archivePath
        Write-Output "Previous installation retained at $archivePath. Run Start for an empty installation."
    }
    exit
}
if ($Action -eq 'Stop') {
    if (Test-Path -LiteralPath $stateFile) {
        $state = Get-Content -LiteralPath $stateFile -Raw | ConvertFrom-Json
        # Request a clean database shutdown before stopping the owned processes.
        $admin = Join-Path $RuntimeRoot 'MySQL-5.7/bin/mysqladmin.exe'
        $ownedDatabase = $state.processes | Where-Object { $_.path -like '*mysqld.exe' }
        $databaseProcess = if ($ownedDatabase) { Get-Process -Id $ownedDatabase.id -ErrorAction SilentlyContinue }
        if ($databaseProcess -and $databaseProcess.Path -eq $ownedDatabase.path -and $databaseProcess.StartTime.ToUniversalTime() -eq [datetime]$ownedDatabase.started) {
            & $admin "--defaults-file=$(Join-Path $stateRoot 'client.ini')" shutdown
            if ($LASTEXITCODE -ne 0) { throw 'Clean database shutdown failed; refusing to terminate mysqld forcibly.' }
        }
        foreach ($owned in $state.processes) {
            $process = Get-Process -Id $owned.id -ErrorAction SilentlyContinue
            if ($process -and $process.Path -eq $owned.path -and $process.StartTime.ToUniversalTime() -eq [datetime]$owned.started) {
                $children = Get-CimInstance Win32_Process | Where-Object { $_.ParentProcessId -eq $process.Id -and $_.ExecutablePath -eq $process.Path }
                Stop-Process -Id $process.Id -ErrorAction SilentlyContinue
                foreach ($child in $children) { Stop-Process -Id $child.ProcessId -ErrorAction SilentlyContinue }
            }
        }
        Write-Output 'Local services stopped; data is retained in .local.'
    }
    exit
}
if ($Action -eq 'Status') {
    if (Test-Path -LiteralPath $stateFile) { Get-Content -LiteralPath $stateFile }
    Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -in @(8092, 9072, 33062, 18025, 11025) } | Select-Object LocalAddress, LocalPort, OwningProcess
    exit
}
if ($Action -eq 'Sync') { Sync-Site; Write-Output 'Tracked application files copied to .local/site; installation data retained.'; exit }
if ($Action -eq 'Configure') { Configure-Site; Write-Output 'Local domain configured and outbound RPC pings disabled.'; exit }

$apacheRoot = Join-Path $RuntimeRoot 'Apache'
$phpRoot = Join-Path $RuntimeRoot "PHP-$PhpVersion"
$mysqlRoot = Join-Path $RuntimeRoot 'MySQL-5.7'
$mailpitExe = Join-Path $RuntimeRoot 'Mailpit/mailpit.exe'
foreach ($binary in @("$apacheRoot/bin/httpd.exe", "$phpRoot/php-cgi.exe", "$mysqlRoot/bin/mysqld.exe", $mailpitExe)) {
    if (-not (Test-Path -LiteralPath $binary)) { throw "Missing runtime: $binary" }
}
$actualPhp = & "$phpRoot/php.exe" -n -r 'echo PHP_VERSION;'
$expectedPhp = if ($PhpVersion -eq '7.2') { '7.2.34' } else { '8.4.19' }
if ($actualPhp -ne $expectedPhp) { throw "Expected PHP $expectedPhp, found $actualPhp. Update the tested runtime matrix before changing this pin." }
$actualApache = (& "$apacheRoot/bin/httpd.exe" -v) -join ' '
$actualMysql = (& "$mysqlRoot/bin/mysqld.exe" --version) -join ' '
$actualMailpit = (& $mailpitExe version --no-release-check) -join ' '
if ($actualApache -notmatch 'Apache/2\.4\.66\b' -or $actualMysql -notmatch '5\.7\.44\b') {
    throw 'This baseline requires Apache 2.4.66 and MySQL 5.7.44. See docs/LOCAL_DEVELOPMENT.md.'
}
if ($actualMailpit -notmatch '\bv1\.29\.3\b') { throw 'This baseline requires Mailpit 1.29.3.' }
foreach ($port in @(8092, 9072, 33062, 18025, 11025)) {
    if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue | Where-Object { $_.LocalAddress -in @('127.0.0.1', '0.0.0.0', '::') }) {
        throw "Port $port is occupied. Stop the existing local stack before starting another."
    }
}
foreach ($directory in @($stateRoot, $siteRoot, "$stateRoot/logs", "$stateRoot/sessions", "$stateRoot/tmp", "$stateRoot/mysql")) {
    [System.IO.Directory]::CreateDirectory($directory) | Out-Null
}
Sync-Site
if (Test-Path -LiteralPath "$siteRoot/system/conf_main.php") { Configure-Site }
$phpExtensions = @('curl', 'fileinfo', 'mbstring', 'mysqli', 'openssl')
$phpExtensions += $(if ($PhpVersion -eq '7.2') { 'gd2' } else { 'gd' })
$extensionLines = ($phpExtensions | ForEach-Object { "extension=php_$_.dll" }) -join "`n"
$installerAccess = if (Test-Path -LiteralPath "$siteRoot/system/conf_main.php") {
    '<LocationMatch "^/install(?:/|$)">
    Require all denied
</LocationMatch>'
} else { '' }
Write-LocalFile "$stateRoot/php.ini" @"
extension_dir="$(UnixPath $phpRoot)/ext"
$extensionLines
short_open_tag=On
date.timezone=UTC
error_reporting=E_ALL
display_errors=Off
log_errors=On
error_log="$(UnixPath $stateRoot)/logs/php.log"
session.save_path="$(UnixPath $stateRoot)/sessions"
upload_tmp_dir="$(UnixPath $stateRoot)/tmp"
sys_temp_dir="$(UnixPath $stateRoot)/tmp"
allow_url_fopen=On
memory_limit=256M
output_buffering=4096
upload_max_filesize=10M
post_max_size=12M
SMTP=127.0.0.1
smtp_port=11025
sendmail_from=sharetronix@localhost.test
"@
Write-LocalFile "$stateRoot/httpd.conf" @"
ServerRoot "$(UnixPath $apacheRoot)"
Listen 127.0.0.1:8092
ServerName localhost:8092
PidFile "$(UnixPath $stateRoot)/httpd.pid"
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule access_compat_module modules/mod_access_compat.so
LoadModule dir_module modules/mod_dir.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule proxy_module modules/mod_proxy.so
LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so
LoadModule log_config_module modules/mod_log_config.so
LoadModule env_module modules/mod_env.so
TypesConfig "$(UnixPath $apacheRoot)/conf/mime.types"
DocumentRoot "$(UnixPath $siteRoot)"
DirectoryIndex index.php index.html
ErrorLog "$(UnixPath $stateRoot)/logs/apache-error.log"
LogFormat "%h %t %m %U %>s" local
CustomLog "$(UnixPath $stateRoot)/logs/apache-access.log" local
ServerTokens Full
ServerSignature Off
AcceptFilter http none
ProxyRequests Off
ProxyFCGIBackendType GENERIC
<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$(UnixPath $siteRoot)">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require local
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9072/"
        ProxyFCGISetEnvIf "true" SCRIPT_FILENAME "$(UnixPath $siteRoot)%{reqenv:SCRIPT_NAME}"
        ProxyFCGISetEnvIf "true" REDIRECT_STATUS "200"
    </FilesMatch>
</Directory>
<DirectoryMatch "(^|/)\.git(/|$)">
    Require all denied
</DirectoryMatch>
<FilesMatch "^\.ht">
    Require all denied
</FilesMatch>
$installerAccess
"@
$credentialsFile = Join-Path $stateRoot 'credentials.json'
if (-not (Test-Path -LiteralPath $credentialsFile)) {
    $credentials = @{ database = 'sharetronix_local'; user = 'sharetronix'; password = [guid]::NewGuid().ToString('N'); rootPassword = [guid]::NewGuid().ToString('N'); adminUser = 'qaadmin'; adminPassword = [guid]::NewGuid().ToString('N') }
    Write-LocalFile $credentialsFile ($credentials | ConvertTo-Json)
} else { $credentials = Get-Content -LiteralPath $credentialsFile -Raw | ConvertFrom-Json }
Write-LocalFile "$stateRoot/client.ini" @"
[client]
host=127.0.0.1
port=33062
user=root
password=$($credentials.rootPassword)
"@
Write-LocalFile "$stateRoot/my.ini" @"
[mysqld]
basedir="$(UnixPath $mysqlRoot)"
datadir="$(UnixPath $stateRoot)/mysql"
bind-address=127.0.0.1
port=33062
character-set-server=utf8
collation-server=utf8_unicode_ci
sql-mode=""
explicit-defaults-for-timestamp=1
log-error="$(UnixPath $stateRoot)/logs/mysql.log"
pid-file="$(UnixPath $stateRoot)/mysql.pid"
"@
$newDatabase = -not (Test-Path -LiteralPath "$stateRoot/mysql/mysql")
if ($newDatabase) {
    & "$mysqlRoot/bin/mysqld.exe" "--defaults-file=$stateRoot/my.ini" --initialize-insecure
    if ($LASTEXITCODE -ne 0) { throw 'Database initialization failed. Inspect .local/logs/mysql.log.' }
    Write-LocalFile "$stateRoot/db-init.sql" @"
ALTER USER 'root'@'localhost' IDENTIFIED BY '$($credentials.rootPassword)';
CREATE DATABASE sharetronix_local CHARACTER SET utf8 COLLATE utf8_unicode_ci;
CREATE USER 'sharetronix'@'localhost' IDENTIFIED BY '$($credentials.password)';
GRANT ALL PRIVILEGES ON sharetronix_local.* TO 'sharetronix'@'localhost';
"@
}
& "$apacheRoot/bin/httpd.exe" -t -f "$stateRoot/httpd.conf"
if ($LASTEXITCODE -ne 0) { throw 'Apache configuration failed validation.' }
$ownedProcesses = @()
function Start-Owned($Binary, $Arguments, $LogName) {
    $process = Start-Process -FilePath $Binary -ArgumentList $Arguments -WindowStyle Hidden -PassThru -RedirectStandardOutput "$stateRoot/logs/$LogName-out.log" -RedirectStandardError "$stateRoot/logs/$LogName-err.log"
    $script:ownedProcesses += @{ id = $process.Id; path = [System.IO.Path]::GetFullPath($Binary); started = $process.StartTime.ToUniversalTime().ToString('o') }
    Write-LocalFile $stateFile (@{ php = $PhpVersion; processes = $script:ownedProcesses } | ConvertTo-Json -Depth 4)
}
try {
    $mysqlArguments = @("--defaults-file=`"$stateRoot/my.ini`"")
    if ($newDatabase) { $mysqlArguments += "--init-file=`"$stateRoot/db-init.sql`"" }
    Start-Owned "$mysqlRoot/bin/mysqld.exe" $mysqlArguments 'mysql'
    Wait-LocalPort 33062
    Start-Owned $mailpitExe @('--listen', '127.0.0.1:18025', '--smtp', '127.0.0.1:11025', '--database', "`"$stateRoot/mailpit.db`"", '--disable-version-check', '--block-remote-css-and-fonts') 'mailpit'
    Wait-LocalPort 11025
    $previousMaxRequests = $env:PHP_FCGI_MAX_REQUESTS
    try {
        $env:PHP_FCGI_MAX_REQUESTS = '0'
        Start-Owned "$phpRoot/php-cgi.exe" @('-c', "`"$stateRoot/php.ini`"", '-b', '127.0.0.1:9072') 'php'
    } finally { $env:PHP_FCGI_MAX_REQUESTS = $previousMaxRequests }
    Wait-LocalPort 9072
    Start-Owned "$apacheRoot/bin/httpd.exe" @('-f', "`"$stateRoot/httpd.conf`"") 'apache'
    Wait-LocalPort 8092
} catch {
    Write-Warning 'Startup did not finish. Run scripts/dev.ps1 Stop before retrying; inspect .local/logs.'
    throw
}
Write-Output 'Sharetronix: http://localhost:8092/ (first installation: /install/)'
Write-Output 'Captured mail: http://127.0.0.1:18025/'
Write-Output 'Installer database host: 127.0.0.1:33062; local credentials: .local/credentials.json'
