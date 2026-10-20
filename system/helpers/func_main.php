<?php
	
	function sharetronix_autoload($class_name)
	{
		global $C;
		
		if( file_exists( $C->INCPATH.'classes/class_'.$class_name.'.php' ) ){
			require_once( $C->INCPATH.'classes/class_'.$class_name.'.php' );
		}elseif( file_exists( $C->INCPATH.'libraries/class_'.$class_name.'.php' ) ){
			require_once( $C->INCPATH.'libraries/class_'.$class_name.'.php' );
		}
	}
	
	spl_autoload_register('sharetronix_autoload');

	function my_session_name($domain)
	{
		global $C;
		$name	= preg_replace('/[^a-z0-9]/i', '', $C->RNDKEY.$domain);
		return preg_match('/[a-z]/i', $name) ? $name : 'stx'.$name;
	}
	
	function cookie_domain()
	{
		global $C;
		$tmp	= parse_url('http://'.$GLOBALS['C']->DOMAIN, PHP_URL_HOST);
		if( ! $tmp || substr($tmp,0,1)=='[' ) {
			return '';
		}
		if( substr($tmp,0,2) == 'm.' ) {
			$tmp	= substr($tmp,2);
		}
		$pos	= strpos($tmp, '.');
		if( FALSE === $pos ) {
			return '';
		}
		if( preg_match('/^[0-9\.]+$/', $tmp) ) {
			return $tmp;
		}
		return '.'.$tmp;
	}
	
	function rm()
	{
		$files = func_get_args();
		foreach($files as $filename)
			if( is_file($filename) && is_writable($filename) )
				unlink($filename);
	}
	
	function is_valid_email($email)
	{
		return preg_match('/^[a-zA-Z0-9._%-]+@([a-zA-Z0-9.-]+\.)+[a-zA-Z]{2,12}$/u', $email);
	}
	
	function is_valid_url($link)
	{
		if(filter_var($link, FILTER_VALIDATE_URL) != FALSE){
			return true;
		}
		
		return false;
		
// 		if(!preg_match('/^(http|https):\/\/((([a-z0-9.-]+\.)+[a-z]{2,4})|([0-9\.]{1,4}){4})(\/([a-zA-Z�-�0-9-_\�\:%\.\?\!\=\+\&\/\#\~\;\,\@]+)?)?$/', $link))
// 			return FALSE;
// 		else return TRUE;
	}
	
	function my_copy($source, $dest)
	{
		// A remote file gets the time limit of the cURL attempt below; without it copy() waits up to
		// default_socket_timeout (60 s) and the request can run past max_execution_time.
		if( preg_match('/^(http|https)\:\/\//u', $source) ) {
			$res	= @copy($source, $dest, stream_context_create(array('http' => array('timeout' => 5))));
		}
		else {
			$res	= @copy($source, $dest);
		}
		if( $res ) {
			chmod($dest, 0644);
			return TRUE;
		}
		if( function_exists('curl_init') && preg_match('/^(http|https|ftp)\:\/\//u', $source) ) {
			global $C;
			$dst	= fopen($dest, 'w');
			if( ! $dst ) {
				return FALSE;
			}
			$ch	= curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_FILE	=> $dst,
				CURLOPT_HEADER	=> FALSE,
				CURLOPT_URL		=> $source,
				CURLOPT_CONNECTTIMEOUT	=> 3,
				CURLOPT_TIMEOUT	=> 5,
				CURLOPT_MAXREDIRS	=> 5,
				CURLOPT_REFERER	=> $C->SITE_URL,
				CURLOPT_USERAGENT	=> isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1',
			));
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			$res	= curl_exec($ch);
			fclose($dst);
			$failed	= ! $res || curl_errno($ch);
			// curl_close() has no effect since PHP 8.0 and is deprecated as of 8.5
			if( PHP_VERSION_ID < 80000 ) {
				curl_close($ch);
			}
			if( $failed ) {
				return FALSE;
			}
			chmod($dest, 0644);
			return TRUE;
		}
		return FALSE;
	}
	
	function do_send_mail($email, $subject, $message, $from=FALSE, $reply_to=FALSE, $reply_to_name='')
	{
		global $C;
		if( ! $from ) {
			//$from	= $C->SITE_TITLE.' <'.$C->SYSTEM_EMAIL.'>';
			$from	= $C->SYSTEM_EMAIL;
		}
		
		$err = FALSE;
		
		$mail = new PHPMailer();
		$mail->Subject = $subject;
		// Replies go to $reply_to (the sender of the contact form) instead of the From address, which
		// stays the site's own so that the receiving server accepts the message.
		if( $reply_to ) {
			$mail->AddReplyTo($reply_to, $reply_to_name);
		}
		$mail->SetFrom( $from );                         
		
		if( isset($C->SENDMAIL_PATH) && !empty($C->SENDMAIL_PATH) ){
			$mail->Sendmail = $C->SENDMAIL_PATH;
			$mail->IsSendmail();  
		}else{
			$mail->isMail();
		}
		
		$mail->AddAddress($email);
		$mail->AltBody    = $message; // optional, comment out and test
		$mail->MsgHTML($message);
		$mail->IsHTML(FALSE); 
		
		try{
			if( ! $mail->Send() ) {
				$err = $mail->ErrorInfo;
			}
		}
		catch ( phpmailerException $e ){
			$err = $e->getMessage();
		}
		if( $err !== FALSE ) {
			error_log('Sharetronix mail error: '.$err.' | Recipient domain: '.substr(strrchr($email, '@'), 1).' | Subject: '.$subject);
			return FALSE;
		}
		
		return TRUE;
		
		
		/*$crlf	= "\n";
		preg_match('/^(.*)(\<(.*)\>)?$/iuU', $from, $m);
		$from_mail	= trim($m[3]);
		$from_name	= trim($m[1]);
		$tmp	= empty($from_name) ? $from_mail : ( '=?UTF-8?B?'.base64_encode($from_name).'?= <'.$from_mail.'>' );
		$headers	= '';
		$headers	.= 'From: '.$tmp.$crlf;
		$headers	.= 'Reply-To: '.$tmp.$crlf;
		$headers	.= 'Return-Path: '.$tmp.$crlf;
		$headers	.= 'Message-ID: <'.time().rand(1000,9999).'@'.$C->DOMAIN.'>'.$crlf;
		$headers	.= 'X-Mailer: PHP/'.PHP_VERSION.$crlf;
		$headers	.= 'MIME-Version: 1.0'.$crlf;
		$headers	.= 'Content-Type: text/plain; charset=UTF-8'.$crlf;
		$headers	.= 'Content-Transfer-Encoding: 8bit'.$crlf;
		$subject	= '=?UTF-8?B?'.base64_encode($subject).'?=';
		return mail( $email, $subject, $message, $headers );*/
	}
	
	function do_send_mail_html($email, $subject, $message_txt, $message_html, $from=FALSE)
	{
		global $C;
		if( ! $from ) {
			//$from	= $C->SITE_TITLE.' <'.$C->SYSTEM_EMAIL.'>';
			$from	= $C->SYSTEM_EMAIL;
		}
		/*	(DELETE_THIS_LINE) This is a fix for everybody with mail issues (2 types)
			(DELETE_THIS_LINE) 1. Your script send mails with blank body
			(DELETE_THIS_LINE) 2. Your script send mails with missing text in the mail body
			
			(DELETE_THIS_LINE) To activate the fix delete all the lines which contains (DELETE_THIS_LINE).
		
		do_send_mail($email, $subject, $message_txt, $from);
		return;
		
			(DELETE_THIS_LINE)*/
		
		/*
		$crlf	= "\n";
		$boundary	= '=_Part_'.md5(time().rand(0,9999999999));
		preg_match('/^(.*)(\<(.*)\>)?$/iuU', $from, $m);
		$from_mail	= trim($m[3]);
		$from_name	= trim($m[1]);
		$tmp	= empty($from_name) ? $from_mail : ( '=?UTF-8?B?'.base64_encode($from_name).'?= <'.$from_mail.'>' );
		$headers	= '';
		$headers	.= 'From: '.$tmp.$crlf;
		$headers	.= 'Reply-To: '.$tmp.$crlf;
		$headers	.= 'Return-Path: '.$tmp.$crlf;
		$headers	.= 'Message-ID: <'.time().rand(1000,9999).'@'.$C->DOMAIN.'>'.$crlf;
		$headers	.= 'X-Mailer: PHP/'.PHP_VERSION.$crlf;
		$headers	.= 'MIME-Version: 1.0'.$crlf;
		$headers	.= 'Content-Type: multipart/alternative; boundary="'.$boundary.'"'.$crlf;
		$headers	.= '--'.$boundary.$crlf;
		$headers	.= 'Content-Type: text/plain; charset=UTF-8'.$crlf;
		$headers	.= 'Content-Transfer-Encoding: base64'.$crlf;
		$headers	.= 'Content-Disposition: inline'.$crlf;
		$headers	.= $crlf;
		$headers	.= chunk_split(base64_encode($message_txt));
		$headers	.= '--'.$boundary.$crlf;
		$headers	.= 'Content-Type: text/html; charset=UTF-8'.$crlf;
		$headers	.= 'Content-Transfer-Encoding: base64'.$crlf;
		$headers	.= 'Content-Disposition: inline'.$crlf;
		$headers	.= $crlf;
		$headers	.= chunk_split(base64_encode($message_html), 76, $crlf);
		$subject	= '=?UTF-8?B?'.base64_encode($subject).'?=';
		$result	= @mail( $email, $subject, '', $headers );
		if( ! $result ) {
			// if mail is not accepted for delivery by the MTA, try something else:
			$headers	= '';
			$headers	.= 'From: '.$tmp.$crlf;
			$headers	.= 'Reply-To: '.$tmp.$crlf;
			$headers	.= 'Return-Path: '.$tmp.$crlf;
			$headers	.= 'Message-ID: <'.time().rand(1000,9999).'@'.$C->DOMAIN.'>'.$crlf;
			$headers	.= 'X-Mailer: PHP/'.PHP_VERSION.$crlf;
			$headers	.= 'MIME-Version: 1.0'.$crlf;
			$headers	.= 'Content-Type: multipart/alternative; boundary="'.$boundary.'"'.$crlf;
			$headers	.= '--'.$boundary.$crlf;
			$headers	.= 'Content-Type: text/plain; charset=UTF-8'.$crlf;
			$headers	.= 'Content-Transfer-Encoding: base64'.$crlf;
			$headers	.= 'Content-Disposition: inline'.$crlf;
			$headers	.= chunk_split(base64_encode($message_txt));
			$headers	.= '--'.$boundary.$crlf;
			$headers	.= 'Content-Type: text/html; charset=UTF-8'.$crlf;
			$headers	.= 'Content-Transfer-Encoding: base64'.$crlf;
			$headers	.= 'Content-Disposition: inline'.$crlf;
			$headers	.= chunk_split(base64_encode($message_html), 76, $crlf);
			$result	= @mail( $email, $subject, '', $headers );
		}
		return $result;*/
		
		$err = FALSE; 
		
		$mail = new PHPMailer();
		$mail->Subject = $subject;
		$mail->SetFrom( $from );
		
		if( isset($C->SENDMAIL_PATH) && !empty($C->SENDMAIL_PATH) ){
			$mail->Sendmail = $C->SENDMAIL_PATH;
			$mail->IsSendmail();  
		}else{
			$mail->isMail();
		}
		
		$mail->AddAddress($email);
		$mail->AltBody    = $message_txt; // optional
		$mail->MsgHTML($message_html);
		$mail->IsHTML(TRUE);
		
		try{
			if( ! $mail->Send() ) {
				$err = $mail->ErrorInfo;
			}
		}catch ( phpmailerException $e){
			$err = $e->getMessage();
		}
		if( $err !== FALSE ) {
			error_log('Sharetronix mail error: '.$err.' | Recipient domain: '.substr(strrchr($email, '@'), 1).' | Subject: '.$subject);
			return FALSE;
		}
		
		return TRUE;
	}
	
	function format_date($format, $timestamp)
	{
		if( PHP_VERSION_ID < 80100 ) {
			// strftime() is not deprecated here and keeps the language-pack locale.
			return strftime($format, $timestamp);
		}
		// Preserve the percent directives used by the bundled English UI.
		$formats = array(
			'a'=>'D', 'A'=>'l', 'b'=>'M', 'h'=>'M', 'B'=>'F', 'd'=>'d',
			'D'=>'m/d/y', 'F'=>'Y-m-d', 'H'=>'H', 'I'=>'h', 'm'=>'m',
			'M'=>'i', 'p'=>'A', 'P'=>'a', 'r'=>'h:i:s A', 'R'=>'H:i',
			'S'=>'s', 'T'=>'H:i:s', 'u'=>'N', 'V'=>'W', 'w'=>'w',
			'x'=>'m/d/y', 'X'=>'H:i:s', 'y'=>'y', 'Y'=>'Y', 'G'=>'o',
			'z'=>'O', 'Z'=>'T'
		);
		return preg_replace_callback('/%([a-zA-Z%])/', function($matches) use ($formats, $timestamp) {
			$code = $matches[1];
			if( isset($formats[$code]) ) {
				return date($formats[$code], $timestamp);
			}
			switch($code) {
				case '%': return '%';
				case 'n': return "\n";
				case 't': return "\t";
				case 'e': return str_pad(date('j', $timestamp), 2, ' ', STR_PAD_LEFT);
				case 'k': return str_pad(date('G', $timestamp), 2, ' ', STR_PAD_LEFT);
				case 'l': return str_pad(date('g', $timestamp), 2, ' ', STR_PAD_LEFT);
				case 'j': return str_pad(date('z', $timestamp)+1, 3, '0', STR_PAD_LEFT);
				case 'C': return str_pad(floor(date('Y', $timestamp)/100), 2, '0', STR_PAD_LEFT);
				case 'g': return substr(date('o', $timestamp), -2);
				case 's': return (string) $timestamp;
				case 'U': return sprintf('%02d', floor((date('z', $timestamp)+7-date('w', $timestamp))/7));
				case 'W': return sprintf('%02d', floor((date('z', $timestamp)+8-date('N', $timestamp))/7));
				case 'c': return format_date('%a %b %e %T %Y', $timestamp);
			}
			return $matches[0];
		}, $format);
	}

	function safe_attachment_extension($filename)
	{
		global $C;
		// Allowlist of uploadable attachment extensions; override with $C->ATTACH_ALLOWED_EXTENSIONS.
		$allowed = isset($C->ATTACH_ALLOWED_EXTENSIONS)? $C->ATTACH_ALLOWED_EXTENSIONS : array(
			'jpg','jpeg','png','gif','bmp','webp',
			'pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','rtf','odt','ods','odp',
			'zip','rar','7z','gz','tar',
			'mp3','wav','ogg','oga','m4a','aac','flac',
			'mp4','m4v','mov','avi','webm','mkv','wmv','flv','3gp'
		);
		$filename	= (string) $filename;
		$pos	= mb_strrpos($filename, '.');
		if( FALSE === $pos ) {
			return FALSE;
		}
		$ext	= mb_strtolower( trim( mb_substr($filename, $pos+1) ) );
		if( $ext === '' || ! in_array($ext, $allowed, TRUE) ) {
			return FALSE;
		}
		return $ext;
	}

	function attachment_disposition($filename, $fallback)
	{
		// Content-Disposition for an attachment download under its original name. Quotes,
		// backslashes and control characters are dropped. A name that is not plain ASCII goes
		// in filename* (RFC 6266) with the stored name as the plain fallback.
		$filename	= trim( preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', (string) $filename) );
		if( $filename === '' || ! preg_match('//u', $filename) ) {
			return 'Content-Disposition: attachment; filename="'.$fallback.'"';
		}
		if( preg_match('/[^\x20-\x7E]/', $filename) ) {
			return 'Content-Disposition: attachment; filename="'.$fallback.'"; filename*=UTF-8\'\''.rawurlencode($filename);
		}
		return 'Content-Disposition: attachment; filename="'.$filename.'"';
	}

	function generate_password($len=8, $let='abcdefghkmnpqrstuvwxyzABCDEFGHKLMNPRSTUVWXYZ23456789')
	{
		$word	= '';
		for($i=0; $i<$len; $i++) {
			$word	.= $let[ rand(0,strlen($let)-1) ];
		}
		return $word;
	}
	
	function show_filesize($bytes)
	{
		$kb	= ceil($bytes/1024);
		if( $kb < 1024 ) {
			return $kb.'KB';
		}
		$mb	= round($kb/1024,1);
		return $mb.'MB';
	}
	
	// The text of a number of things: the key with the suffix 1 for exactly one ('1 post'), the key
	// itself with #NUM# for any other number ('#NUM# posts'), as the language files pair them.
	function lang_count($key, $num)
	{
		$num	= intval($num);
		return $GLOBALS['page']->lang($num==1 ? $key.'1' : $key, array('#NUM#'=>$num));
	}
	
	// The tooltip of an administration action on a member in a list (remove_administrator, activate_user,
	// remove_moderator): the string usrlist_tltp_ACTION, or the name of an action that has none.
	function admin_action_title($action)
	{
		$text	= $GLOBALS['page']->lang('usrlist_tltp_'.$action);
		return $text !== '' ? $text : ucfirst(str_replace('_', ' ', $action));
	}
	
	function str_cut($str, $mx)
	{
		return mb_strlen($str)>$mx ? mb_substr($str, 0, $mx-1).'..' : $str;
	}
	
	function str_cut_link($str, $mx)
	{
		return mb_strlen($str)>$mx ? ( mb_substr($str,0,$mx-6).'...'.mb_substr($str,-4) ) : $str;
	}
	
	function nowrap($string)
	{
		return str_replace(' ', '&nbsp;', $string);
	}
	
	function br2nl($string)
	{
		return str_replace(array('<br />', '<br/>', '<br>'), "\r\n", $string);
	}
	
	function strip_url($url)
	{
		$url	= preg_replace('/^(http|https):\/\/(www\.)?/u', '', trim($url));
		$url	= preg_replace('/\/$/u', '', $url);
		return trim($url);
	}
	
	function my_ucwords($str)
	{
		return mb_convert_case($str, MB_CASE_TITLE);
	}
	
	function my_ucfirst($str)
	{
		return mb_strtoupper(mb_substr($str,0,1)).mb_substr($str,1);
	}
	
	function userlink($username)
	{
		global $C;
		if( $C->USERS_ARE_SUBDOMAINS ) {
			return 'http://'.$username.'.'.$C->DOMAIN;
		}
		return $C->SITE_URL.$username;
	}
	
	function getAvatarUrl( $avatar, $size = 'thumbs1' )
	{

		global $C;
			
		if( !in_array($size, array('thumbs1', 'thumbs2', 'thumbs3', 'thumbs4', 'thumbs5', 'origin')) ){
			$size = 'thumbs1';
		}
		
		if ($size == 'origin') $size = '';
		
		$filename = $C->STORAGE_DIR	 . 'avatars/' . $size . '/'. $avatar;
		if(!file_exists($filename)){
			missingThumbsCreate($C->STORAGE_DIR	 . 'avatars/'.$avatar,$avatar, $size);
		}
		return $C->STORAGE_URL . 'avatars/' . $size . '/'. $avatar;
	}
	
	function changeTemplateArray( &$val)
	{
		$val = '{%'. $val .'%}';
	}
	
	
	function get_user_id($username)
	{
		global $db2;
		
		$res = $db2->query('SELECT id FROM users WHERE username="'.$db2->e($username).'" LIMIT 1');
		if(!$db2->num_rows($res)) return false;
		
		$obj = $db2->fetch_object($res);
		return intval($obj->id);
	}
	
	function checkIfUnicode( $string )
	{
		if (mb_strlen($string) != mb_strlen(utf8_decode($string))){
			return TRUE;
		}	
		
		return FALSE;
	}
	
	function getCachedHTML( $name, $user_id = FALSE )
	{
		global $C, $page;
		$user = & $GLOBALS['user'];
		
		$cache_filename = $C->INCPATH.'cache_html/'.$name.($page->is_mobile? '-mobile-' : '').( $user_id? '-current_user_'.$user->id.'-about_user_'.$user_id : '' ).'.cached.php';
		if( file_exists( $cache_filename ) ){
			return file_get_contents( $cache_filename );
		}
			
		return FALSE;
	}
	
	function setCachedHTML( $name, $value, $user_id = FALSE)
	{
			
		global $C, $page;
		$user = & $GLOBALS['user'];

		$name = strval($name);
		$value = strval($value);
			
		if( empty($name) || empty($value) || ($user_id && !is_numeric($user_id)) ){
			return FALSE;
		}
		$cache_filename = $C->INCPATH.'cache_html/'.$name.($page->is_mobile? '-mobile-' : '').( $user_id? '-current_user_'.$user->id.'-about_user_'.$user_id : '' ).'.cached.php';

		if( file_exists( $cache_filename ) ){
			unlink( $cache_filename );
		}
			
		return FALSE !== file_put_contents($cache_filename, $value);
	}
	
	function invalidateCachedHTML()
	{
		global $C;
		
		$cache_folder = $C->INCPATH.'cache_html/';
		
		if ($handle = opendir( $cache_folder )) {
			while (FALSE !== ($entry = readdir($handle))) {
				if( $entry !== '.' && $entry !== '..' ){
					unlink ( $cache_folder . $entry );
				}
			}
			closedir($handle);
		}
			
		return TRUE;
	}
	
	function missingThumbsCreate($source, $fn,$thumbs)
	{
		global $C;
		
		$prefix = array (
					'origin'=>0,
					'thumbs1'=>1,
					'thumbs2'=>2,
					'thumbs3'=>3,
					'thumbs4'=>4,
					'thumbs5'=>5
		);
		
		list($w, $h, $tp) = getimagesize($source);
		if( $w==0 || $h==0 ) {
			return FALSE;
		}
		if( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			return FALSE;
		}
		$fn0	= $C->STORAGE_DIR.'avatars/'.$fn;
		${'fn' . $prefix[$thumbs]}	= $C->STORAGE_DIR.'avatars/thumbs' . $prefix[$thumbs] . '/'.$fn;

		if( $C->IMAGE_MANIPULATION == "imagemagick_cli" ) {
			switch($thumbs){
				case 'origin':	
					exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.$C->AVATAR_SIZE.'x -strip +repage '.$fn0 );
					break;
				case 'thumbs1':
					exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE1.'x'):('x'.$C->AVATAR_SIZE1)).' -crop '.$C->AVATAR_SIZE1.'x'.$C->AVATAR_SIZE1.'+0+0 -strip +repage '.$fn1 );
					break;
				case 'thumbs2':
					exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE2.'x'):('x'.$C->AVATAR_SIZE2)).' -crop '.$C->AVATAR_SIZE2.'x'.$C->AVATAR_SIZE2.'+0+0 -strip +repage '.$fn2 );
					break;
				case 'thumbs3':
					exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE3.'x'):('x'.$C->AVATAR_SIZE3)).' -crop '.$C->AVATAR_SIZE3.'x'.$C->AVATAR_SIZE3.'+0+0 -strip +repage '.$fn3 );
					break;
				case 'thumbs4':
					exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE4.'x'):('x'.$C->AVATAR_SIZE4)).' -crop '.$C->AVATAR_SIZE4.'x'.$C->AVATAR_SIZE4.'+0+0 -strip +repage '.$fn4 );
					break;
				case 'thumbs5':
					exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE_MOBILE_WIDTH.'x'):('x'.$C->AVATAR_SIZE_MOBILE_HEIGHT)).' -crop '.$C->AVATAR_SIZE_MOBILE_WIDTH.'x'.$C->AVATAR_SIZE_MOBILE_HEIGHT.'+0+0 -strip +repage '.$fn5 );
					break;
			}
			if( $tp==IMAGETYPE_GIF && !file_exists($fn0) ) {
				${'tmp' . $prefix[$thumbs]}	= str_replace('.png', '-0.png', ${'fn'.$prefix[$thumbs]});
				if( file_exists($tmp0) ) {
					rename(${'tmp'.$prefix[$thumbs]}, ${'fn'.$prefix[$thumbs]});
					$tmp	= str_replace('.png', '-', $fn);					
					system( 'rm '.$C->STORAGE_DIR.'avatars/thumbs' . $prefix[$thumbs] . '/'.$tmp.'*' );
				}
			}
		}
		else {
			$srcp	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$srcp	= imagecreatefromgif($source);
					break;
				case IMAGETYPE_JPEG:
					$srcp	= imagecreatefromjpeg($source);
					break;
				case IMAGETYPE_PNG:
					$srcp	= imagecreatefrompng($source);
					break;
			}
			if( ! $srcp ) {
				return FALSE;
			}
			
			if($thumbs !='thumbs5'){
				$avatar_size = 'AVATAR_SIZE'.$prefix[$thumbs];
				${'dstp'.$prefix[$thumbs]}	= imagecreatetruecolor($C->$avatar_size, $C->$avatar_size);			
				${'res'.$prefix[$thumbs]} = imagecopyresampled(${'dstp'.$prefix[$thumbs]}, $srcp, 0, 0, $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2),  $C->$avatar_size,  $C->$avatar_size, min($w,$h), min($w,$h));
					
			} else{
				$dstp5	= imagecreatetruecolor($C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_HEIGHT);
				$dstp5_tmp = imagecreatetruecolor($C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_WIDTH);
				$res5_tmp = imagecopyresampled($dstp5_tmp, $srcp, 0, 0,  $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2), $C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_WIDTH, min($w,$h), min($w,$h));
				$res5	= imagecopyresampled($dstp5, $dstp5_tmp, 0, 0, 0, round($C->AVATAR_SIZE_MOBILE_HEIGHT * 2), $C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_HEIGHT, 420, 60);					
			}			
				
			unset($srcp);
			if( ! (${'res'.$prefix[$thumbs]})) {
				unset(${'dstp'.$prefix[$thumbs]});
				if($prefix[$thumbs] == 5){
					unset($dstp5_tmp);
				}
				return FALSE;
			}
			switch($tp) {
				case IMAGETYPE_GIF:
					imagegif(${'dstp'.$prefix[$thumbs]}, ${'fn'.$prefix[$thumbs]});


					break;
				case IMAGETYPE_JPEG:
					imagejpeg(${'dstp'.$prefix[$thumbs]}, ${'fn'.$prefix[$thumbs]}, 100);
					break;
				case IMAGETYPE_PNG:
					imagepng(${'dstp'.$prefix[$thumbs]}, ${'fn'.$prefix[$thumbs]});
					break;
			}
			unset(${'dstp'.$prefix[$thumbs]});
			
		}
		if( !file_exists(${'fn'.$prefix[$thumbs]})) {
			rm(${'fn'.$prefix[$thumbs]});
			return FALSE;
		}
		chmod( ${'fn'.$prefix[$thumbs]}, 0644 );		
		return TRUE;
	}
	
	function serialize_fix_callback($match) {
		return 's:' . strlen($match[2]);
	}
	
	function getThisUserCommunityName( $obj )
	{
		global $C;
		
		return (isset($C->NAME_INDENTIFICATOR) && $C->NAME_INDENTIFICATOR == 2 && !empty($obj->fullname))? htmlspecialchars($obj->fullname) : $obj->username;
	}
?>
