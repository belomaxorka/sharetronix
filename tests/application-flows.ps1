# Loaded by installer.ps1 while its disposable site is running.
function Test-ApplicationFlows {
    function Flow-Sql($Statement) {
        $result = & $clientExe "--defaults-file=$stateRoot/client.ini" --default-character-set=utf8mb4 -N $database -e $Statement
        if ($LASTEXITCODE -ne 0) { throw 'Application regression database query failed.' }
        return $result
    }
    function Flow-Client {
        $handler = [Net.Http.HttpClientHandler]::new()
        $handler.AllowAutoRedirect = $false
        $handler.CookieContainer = [Net.CookieContainer]::new()
        return [Net.Http.HttpClient]::new($handler)
    }
    function Flow-Service($Module, $Action, $Data, $Client) {
        $response = Request-Installer "/services/$Module/$Action" $Data $Client
        if ($response.status -ne 200) { throw "Application service failed: $Module/$Action HTTP $($response.status)" }
        try { return ($response.body | ConvertFrom-Json).data } catch {
            [IO.File]::WriteAllText("$testRoot/failed-service-response.txt", $response.body, $encoding)
            throw "Application service returned invalid JSON: $Module/$Action"
        }
    }
    function Flow-Post($Client, $Message, $Group = 0) {
        $page = Follow-Installer (Request-Installer '/dashboard' $null $Client) $Client
        if (-not $page.body.Contains('data-action="set"')) { throw 'The application post composer is unavailable.' }
        # The browser's attachments.js generates this draft identifier client-side.
        $draft = [guid]::NewGuid().ToString('N')
        $post = Flow-Service 'activities' 'set' @{ activities_text = $Message; activities_type = 'dashboard'; activities_group = $Group; token = $draft } $Client
        if ($post.status -ne 'OK' -or [int]$post.inserted_activities_id -lt 1) { throw 'Application could not publish a fixture post.' }
        return [int]$post.inserted_activities_id
    }
    $a = Flow-Client
    $b = Flow-Client
    $c = Flow-Client
    $guest = Flow-Client
    try {
        foreach ($name in @('qaflowa', 'qaflowb', 'qaflowc')) {
            $null = Flow-Sql "INSERT INTO users (username,email,password,fullname,active,language,timezone,is_network_admin) SELECT '$name','$name@localhost.test',password,'Regression $name',1,'en','UTC',0 FROM users WHERE username='qainstall';"
        }
        $aId = [int](Flow-Sql "SELECT id FROM users WHERE username='qaflowa';")
        $bId = [int](Flow-Sql "SELECT id FROM users WHERE username='qaflowb';")
        $response = Follow-Installer (Request-Installer '/signin' @{ email = 'qaflowa'; password = $credentials.adminPassword } $a) $a
        Check-Installer 'FLOW: user A signs in through an independent session' ($response.url -eq "$baseUrl/dashboard")
        $response = Follow-Installer (Request-Installer '/signin' @{ email = 'qaflowb'; password = $credentials.adminPassword } $b) $b
        Check-Installer 'FLOW: user B signs in through an independent session' ($response.url -eq "$baseUrl/dashboard")
        $response = Follow-Installer (Request-Installer '/signin' @{ email = 'qaflowc'; password = $credentials.adminPassword } $c) $c
        Check-Installer 'FLOW: a third ordinary user signs in independently' ($response.url -eq "$baseUrl/dashboard")
        $response = Follow-Installer (Request-Installer '/admin/general' $null $a) $a
        Check-Installer 'FLOW: an ordinary user cannot open administration' ($response.url -eq "$baseUrl/dashboard")
        $response = Follow-Installer (Request-Installer '/admin/general' $null $guest) $guest
        Check-Installer 'FLOW: a guest cannot open administration' ($response.url -eq "$baseUrl/signin")

        $postId = Flow-Post $a 'Regression public message #maintest'
        Check-Installer 'FLOW: public post persists with its owner' ((Flow-Sql "SELECT COUNT(*) FROM posts WHERE id=$postId AND user_id=$aId AND message='Regression public message #maintest';") -eq '1')
        $response = Follow-Installer (Request-Installer "/view/post:$postId" $null $b) $b
        Check-Installer 'FLOW: another user can read the public post' ($response.status -eq 200 -and $response.body.Contains('Regression public message'))
        $poll = Flow-Service 'activities' 'checknew' @{ activities_type = 'dashboard'; activities_tab = 'all'; last_activity = $postId } $b
        Check-Installer 'FLOW: background activity polling returns a valid count without exposing content' ($poll.status -eq 'OK' -and [string]$poll.new_activities_dashboard -match '^\d+$' -and -not $poll.html.Contains('Regression private sentinel'))
        $oldDate = [DateTimeOffset]::UtcNow.AddDays(-2).ToUnixTimeSeconds()
        $null = Flow-Sql "UPDATE posts SET date=$oldDate WHERE id=$postId;"
        $response = Follow-Installer (Request-Installer "/view/post:$postId" $null $b) $b
        $expectedDate = [DateTimeOffset]::FromUnixTimeSeconds($oldDate).UtcDateTime.ToString('MMM dd yyyy, HH:mm', [Globalization.CultureInfo]::InvariantCulture)
        Check-Installer 'FLOW: an older post retains its absolute English date and timezone' ($response.status -eq 200 -and $response.body.Contains($expectedDate))
        $linkId = Flow-Post $a 'Regression link http://example.test/path.'
        $response = Follow-Installer (Request-Installer "/view/post:$linkId" $null $b) $b
        Check-Installer 'FLOW: a post containing a URL renders its link and punctuation' ($response.status -eq 200 -and $response.body.Contains('href="http://example.test/path"') -and $response.body.Contains('http://example.test/path</a>.'))
        $comment = Flow-Service 'comments' 'set' @{ activities_type = 'public'; activities_id = $postId; comments_text = 'Regression comment from B' } $b
        Check-Installer 'FLOW: another user can comment and the comment persists' ($comment.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM posts_comments WHERE post_id=$postId AND user_id=$bId AND message='Regression comment from B';") -eq '1')
        $like = Flow-Service 'activities' 'like' @{ activities_type = 'public'; activities_id = $postId } $b
        Check-Installer 'FLOW: liking a post persists once' ($like.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM post_likes WHERE post_id=$postId AND user_id=$bId AND comment_id=0;") -eq '1')
        $unlike = Flow-Service 'activities' 'unlike' @{ activities_type = 'public'; activities_id = $postId } $b
        Check-Installer 'FLOW: removing the like removes its database row' ($unlike.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM post_likes WHERE post_id=$postId AND user_id=$bId;") -eq '0')
        $edit = Flow-Service 'activities' 'edit' @{ activities_type = 'public'; activities_id = $postId; message = 'Unauthorized edit' } $b
        Check-Installer 'FLOW: another user cannot edit the post' ($edit.status -eq 'ERROR' -and (Flow-Sql "SELECT COUNT(*) FROM posts WHERE id=$postId AND message='Regression public message #maintest';") -eq '1')
        $edit = Flow-Service 'activities' 'edit' @{ activities_type = 'public'; activities_id = $postId; message = 'Regression edited public #maintest' } $a
        Check-Installer 'FLOW: the owner can edit the post' ($edit.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM posts WHERE id=$postId AND message='Regression edited public #maintest';") -eq '1')
        $delete = Flow-Service 'activities' 'delete' @{ activities_type = 'public'; activities_id = $postId } $b
        Check-Installer 'FLOW: another user cannot delete the post' ($delete.status -eq 'ERROR' -and (Flow-Sql "SELECT COUNT(*) FROM posts WHERE id=$postId;") -eq '1')

        $response = Follow-Installer (Request-Installer '/settings/profile' @{ sbm = 'Save'; profile_name = 'Regression User A'; profile_location = 'Local test'; profile_aboutme = 'Проверка профиля'; profile_tags = 'maintest' } $a) $a
        $response = Follow-Installer (Request-Installer '/settings/profile' $null $a) $a
        Check-Installer 'FLOW: profile changes and non-ASCII text survive reload' ($response.status -eq 200 -and $response.body.Contains('Regression User A') -and $response.body.Contains('Проверка профиля'))
        $months = @('January','February','March','April','May','June','July','August','September','October','November','December')
        $monthLabelsMatch = $true
        for ($month = 1; $month -le 12; $month++) {
            if ($response.body -notmatch ('<option[^>]*value="' + $month + '"[^>]*>' + $months[$month-1] + '</option>')) { $monthLabelsMatch = $false }
        }
        Check-Installer 'FLOW: the English profile selector retains all twelve month labels' $monthLabelsMatch
        Check-Installer 'FLOW: profile changes affect only their owner' ((Flow-Sql "SELECT COUNT(*) FROM users WHERE id=$bId AND fullname='Regression qaflowb';") -eq '1')
        $response = Follow-Installer (Request-Installer '/qaflowa/tab:info' $null $b) $b
        Check-Installer 'FLOW: public profile information renders dates and saved text' ($response.status -eq 200 -and $response.body.Contains('Regression User A') -and $response.body.Contains('Проверка профиля'))
        $follow = Flow-Service 'users' 'follow' @{ users_id = $bId } $a
        Check-Installer 'FLOW: following another user persists' ($follow.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM users_followed WHERE who=$aId AND whom=$bId;") -eq '1')
        $unfollow = Flow-Service 'users' 'unfollow' @{ users_id = $bId } $a
        Check-Installer 'FLOW: unfollow removes the relationship' ($unfollow.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM users_followed WHERE who=$aId AND whom=$bId;") -eq '0')

        $message = Flow-Service 'users' 'sendmessage' @{ users_id = $bId; text = 'Regression direct message sentinel' } $a
        $messageId = [int](Flow-Sql "SELECT id FROM posts_pr WHERE user_id=$aId AND to_user=$bId AND message='Regression direct message sentinel';")
        Check-Installer 'FLOW: a direct message persists with its sender and recipient' ($message.status -eq 'OK' -and $messageId -gt 0)
        foreach ($participant in @(@($a,'sender'), @($b,'recipient'))) {
            $response = Follow-Installer (Request-Installer "/view/priv:$messageId" $null $participant[0]) $participant[0]
            Check-Installer "FLOW: the direct-message $($participant[1]) can read it" ($response.status -eq 200 -and $response.body.Contains('Regression direct message sentinel'))
        }
        foreach ($outsider in @(@($c,'non-participant'), @($guest,'guest'))) {
            $response = Follow-Installer (Request-Installer "/view/priv:$messageId" $null $outsider[0]) $outsider[0]
            Check-Installer "FLOW: a direct-message $($outsider[1]) cannot read it" (-not $response.body.Contains('Regression direct message sentinel') -and $response.url -ne "$baseUrl/view/priv:$messageId")
        }

        $avatarPath = "$testRoot/avatar-fixture.png"
        & $phpExecutable -c $phpConfig -r '$image=imagecreatetruecolor(200,200); imagepng($image,$argv[1]); imagedestroy($image);' $avatarPath
        if ($LASTEXITCODE -ne 0) { throw 'Could not generate the synthetic upload fixture.' }
        $form = [Net.Http.MultipartFormDataContent]::new()
        $file = [Net.Http.ByteArrayContent]::new([IO.File]::ReadAllBytes($avatarPath))
        $file.Headers.ContentType = [Net.Http.Headers.MediaTypeHeaderValue]::new('image/png')
        $form.Add($file, 'profile_avatar', 'fixture.png')
        $form.Add([Net.Http.StringContent]::new('Upload'), 'sbm')
        try {
            $uploaded = $a.PostAsync("$baseUrl/settings/avatar", $form).GetAwaiter().GetResult()
            $null = $uploaded.Content.ReadAsStringAsync().GetAwaiter().GetResult()
            $avatar = Flow-Sql "SELECT avatar FROM users WHERE id=$aId;"
            Check-Installer 'FLOW: a PNG avatar upload persists its generated filename' ([int]$uploaded.StatusCode -eq 200 -and $avatar -match '^[0-9]+\.png$')
        } finally { $form.Dispose() }
        $thumbsMatch = $true
        foreach ($thumbnail in @(@('thumbs1',50), @('thumbs3',30))) {
            $bytes = $a.GetByteArrayAsync("$baseUrl/storage/avatars/$($thumbnail[0])/$avatar").GetAwaiter().GetResult()
            if ($bytes.Length -lt 24) { $thumbsMatch = $false; continue }
            $width = [int]$bytes[16]*16777216 + [int]$bytes[17]*65536 + [int]$bytes[18]*256 + [int]$bytes[19]
            $height = [int]$bytes[20]*16777216 + [int]$bytes[21]*65536 + [int]$bytes[22]*256 + [int]$bytes[23]
            if ($bytes[0] -ne 137 -or $width -ne $thumbnail[1] -or $height -ne $thumbnail[1]) { $thumbsMatch = $false }
        }
        Check-Installer 'FLOW: avatar thumbnails are served at the expected dimensions' $thumbsMatch

        foreach ($type in @('private', 'public')) {
            $alias = "qaflow$type"
            $response = Follow-Installer (Request-Installer '/groups/new' @{ sbm = 'Create'; group_name = "Regression $type"; group_alias = $alias; group_description = 'Synthetic regression group'; group_type = $type } $a) $a
            $groupId = [int](Flow-Sql "SELECT id FROM groups WHERE groupname='$alias';")
            Check-Installer "FLOW: a user can create a $type group" ($groupId -gt 0 -and $response.url.Contains("/$alias"))
            if ($type -eq 'private') {
                $privateId = Flow-Post $a 'Regression private sentinel #maintest' $groupId
                $response = Follow-Installer (Request-Installer "/view/post:$privateId" $null $a) $a
                Check-Installer 'FLOW: the private-group owner can read its post' ($response.body.Contains('Regression private sentinel'))
                foreach ($client in @($b, $guest)) {
                    $role = if ($client -eq $b) { 'non-member' } else { 'guest' }
                    $response = Follow-Installer (Request-Installer "/view/post:$privateId" $null $client) $client
                    Check-Installer "FLOW: a $role cannot read a private post directly" (-not $response.body.Contains('Regression private sentinel') -and $response.url -ne "$baseUrl/view/post:$privateId")
                    $response = Follow-Installer (Request-Installer "/$alias" $null $client) $client
                    Check-Installer "FLOW: a $role cannot open the private group" (-not $response.body.Contains('Regression private sentinel') -and -not $response.url.Contains("/$alias"))
                }
            } else {
                $join = Flow-Service 'groups' 'join' @{ groups_id = $groupId } $b
                Check-Installer 'FLOW: another user can join the public group' ($join.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM groups_followed WHERE group_id=$groupId AND user_id=$bId;") -eq '1')
                $groupPost = Flow-Post $b 'Regression public group message' $groupId
                Check-Installer 'FLOW: a joined user can publish to the public group' ((Flow-Sql "SELECT COUNT(*) FROM posts WHERE id=$groupPost AND group_id=$groupId AND user_id=$bId;") -eq '1')
            }
        }
        $response = Follow-Installer (Request-Installer '/search/tab:posts/s:maintest' $null $b) $b
        Check-Installer 'FLOW: search returns public content without private content' ($response.body.Contains('Regression edited public') -and -not $response.body.Contains('Regression private sentinel') -and -not $response.body.Contains('Regression direct message sentinel'))
        $delete = Flow-Service 'activities' 'delete' @{ activities_type = 'public'; activities_id = $postId } $a
        Check-Installer 'FLOW: the owner can delete the public post' ($delete.status -eq 'OK' -and (Flow-Sql "SELECT COUNT(*) FROM posts WHERE id=$postId;") -eq '0')
        $null = Request-Installer '/signout' $null $a
        $response = Follow-Installer (Request-Installer '/dashboard' $null $a) $a
        Check-Installer 'FLOW: logout invalidates the session' ($response.url -eq "$baseUrl/signin")
    } finally {
        $a.Dispose()
        $b.Dispose()
        $c.Dispose()
        $guest.Dispose()
    }
}

Test-ApplicationFlows
