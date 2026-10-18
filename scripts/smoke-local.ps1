#requires -Version 7.0
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$stateRoot = Join-Path $repoRoot '.local'
$credentials = Get-Content -LiteralPath "$stateRoot/credentials.json" -Raw | ConvertFrom-Json
if (-not $credentials.users.qausera -or -not $credentials.users.qauserb) {
    throw 'Run scripts/seed-local.php before these checks.'
}
$checks = [System.Collections.Generic.List[object]]::new()
function Check-Local($Name, $Condition) {
    $checks.Add([pscustomobject]@{ name = $Name; passed = [bool]$Condition })
    if (-not $Condition) { throw "Smoke check failed: $Name" }
}
function Request-Local($Path, $Session, $Body = $null) {
    $parameters = @{ Uri = "http://localhost:8092$Path"; WebSession = $Session; TimeoutSec = 15; SkipHttpErrorCheck = $true }
    if ($null -ne $Body) { $parameters.Method = 'POST'; $parameters.Body = $Body }
    Invoke-WebRequest @parameters
}
function Response-Path($Response) { $Response.BaseResponse.RequestMessage.RequestUri.AbsolutePath }
try {
    $guest = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    $response = Request-Local '/' $guest
    Check-Local 'Guest can open the sign-in page' ($response.StatusCode -eq 200 -and $response.Content.Contains('name="password"'))
    $response = Request-Local '/dashboard' $guest
    Check-Local 'Guest dashboard access redirects to sign-in' ((Response-Path $response) -eq '/signin')
    $response = Request-Local '/admin/general' $guest
    Check-Local 'Guest cannot open administration directly' ((Response-Path $response) -eq '/signin')
    $response = Request-Local '/install/' $guest
    Check-Local 'Completed installer is blocked by the local web server' ($response.StatusCode -eq 403)
    $response = Request-Local '/signin' $guest @{ email = 'qausera'; password = 'invalid-local-password' }
    Check-Local 'Invalid credentials are rejected' ((Response-Path $response) -eq '/signin' -and $response.Content.Contains('Invalid e-mail or password'))

    foreach ($username in @('qausera', 'qauserb')) {
        $session = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
        $response = Request-Local '/signin' $session @{ email = $username; password = $credentials.users.$username }
        Check-Local "$username can sign in and retain the session" ((Response-Path $response) -eq '/dashboard' -and $response.Content.Contains($username))
        $response = Request-Local '/admin/general' $session
        Check-Local "$username cannot open administration directly" ((Response-Path $response) -eq '/dashboard')
        $response = Request-Local '/settings/profile' $session
        Check-Local "$username can open profile settings" ($response.StatusCode -eq 200 -and $response.Content.Contains('name="profile_name"'))
        $null = Request-Local '/signout' $session
        $response = Request-Local '/dashboard' $session
        Check-Local "$username cannot open the dashboard after signing out" ((Response-Path $response) -eq '/signin')
    }
    $adminSession = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    $response = Request-Local '/signin' $adminSession @{ email = $credentials.adminUser; password = $credentials.adminPassword }
    Check-Local 'Administrator can sign in' ((Response-Path $response) -eq '/dashboard')
    $response = Request-Local '/admin/general' $adminSession
    Check-Local 'Administrator can open general settings' ($response.StatusCode -eq 200 -and (Response-Path $response) -eq '/admin/general' -and $response.Content.Contains('General settings'))
    $null = Request-Local '/signout' $adminSession
} finally {
    [pscustomobject]@{ timestamp = (Get-Date).ToUniversalTime().ToString('o'); checks = @($checks.ToArray()) } | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath "$stateRoot/http-smoke-results.json" -Encoding utf8
    $checks | Format-Table -AutoSize
}
