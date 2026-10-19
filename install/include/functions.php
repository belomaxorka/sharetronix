<?php
	
	function loadController( $controller_name )
	{
		global $C, $D;
		
		$controller = $C->INCLUDEPATH.'controllers/'.$controller_name.'.php';
		
		file_exists( $controller )?  require_once( $controller ) : require_once( $C->INCLUDEPATH.'controllers/error404.php' );
	}
	
	function loadTemplate( $template_name )
	{
		global $C, $D;
	
		$template = $C->INCLUDEPATH.'templates/'.$template_name.'.php'; 
		
		require_once( $C->INCLUDEPATH.'templates/html_header.php' );
		if( file_exists( $template ) ){
			require_once( $template );
		}
		require_once( $C->INCLUDEPATH.'templates/html_footer.php' );
	}
	
	function config_replace_variable($source, $variable, $value, $keep_quots=TRUE)
	{
		$pattern	= '/('.preg_quote($variable).'\s*\=\s*)\'([^\\\']*)(\'\s*)/su';
		if( $keep_quots ) {
			// The value goes into a single-quoted PHP string, where only \ and ' are special.
			$value	= '\''.addcslashes($value, '\\\'').'\'';
		}
		// A callback, so that $1 or \1 in the value (a password, for example) is not a back-reference.
		return preg_replace_callback($pattern, function($m) use ($value) { return $m[1].$value.$m[2]; }, $source);
	}
	
	function get_old_version()
	{
		global $C, $db;
		
		$tmp_incpath 		= $C->INCPATH;
		$tmp_includepath 	= $C->INCLUDEPATH;
		$tmp_version		= FALSE;
		$tmp_debugmode 		= $C->DEBUG_MODE;
		$tmp_siteurl		= '';
		$error 				= FALSE;
		
		$file	= $tmp_incpath.'conf_main.php';
		if( file_exists($file) ) {
			$C	= new stdClass;
			$C->INCPATH	= realpath($tmp_incpath);
			include($file);			
			
			$tmp_siteurl = $C->SITE_URL;

			if( isset($C->VERSION) ){
				$tmp_version = $C->VERSION;
			}
			
			if( !isset($C->DB_HOST) || !isset($C->DB_USER) || !isset($C->DB_PASS) || !isset($C->DB_NAME) ){
				$error = TRUE;
			}
			if( !$error ){
				$C->DEBUG_MODE = $tmp_debugmode;
				
				$_SESSION['INSTALL_DATA']['MYSQL_HOST'] = $C->DB_HOST;
				$_SESSION['INSTALL_DATA']['MYSQL_USER'] = $C->DB_USER;
				$_SESSION['INSTALL_DATA']['MYSQL_PASS'] =  $C->DB_PASS;
				$_SESSION['INSTALL_DATA']['MYSQL_DBNAME'] = $C->DB_NAME;
				$_SESSION['INSTALL_DATA']['SITE_URL'] = $C->SITE_URL;
				
				$db = new mysqlExt($C->DB_HOST, $C->DB_USER, $C->DB_PASS, $C->DB_NAME);
				
				// Every update records the new version in the settings table; conf_main.php keeps the
				// version of the first installation.
				$db_version = $db->fetch_field('SELECT value FROM settings WHERE word="VERSION" LIMIT 1');
				if( $db_version ){
					$tmp_version = $db_version;
				}
			}
			
			$C = new stdClass;
			$C->DEBUG_MODE = $tmp_debugmode;
			$C->SITE_URL = $tmp_siteurl;
			include($tmp_includepath.'../config/config.php');
			
			return ($tmp_version && !$error)? $tmp_version : FALSE ;
		}
		
		return FALSE;
	}
	
	function shadow_password($pass)
	{
		if( strlen($pass) === 0 ){
			return '';
		}
		return (string) implode('', array_fill(0, strlen($pass)-1, '*') );
	}
	
	function remove_obsolete_files($files)
	{
		// Deletes files (and, for entries ending in /, empty directories) that a newer release no
		// longer ships. Returns the existing entries that could not be deleted.
		global $C;
		
		$root	= rtrim(str_replace('\\', '/', realpath($C->INCPATH.'../')), '/').'/';
		$failed	= array();
		foreach( $files as $file ) {
			if( $file === '' || $file[0] === '/' || strpos($file, '..') !== FALSE || strpos($file, 'storage/') === 0 ) {
				continue;
			}
			$path	= $root.rtrim($file, '/');
			if( substr($file, -1) === '/' ) {
				if( is_dir($path) && count(scandir($path)) == 2 && !@rmdir($path) ) {
					$failed[]	= $file;
				}
			}
			elseif( is_file($path) && !@unlink($path) ) {
				$failed[]	= $file;
			}
		}
		@clearstatcache();
		
		return $failed;
	}
	
	function make_writable_for_owner($path)
	{
		// The web server can change the mode only of what it owns, so it needs read and write
		// permission for the owner; the group and other permissions stay as they are.
		$perms	= @fileperms($path);
		if( $perms === FALSE ) {
			return;
		}
		@chmod($path, ($perms & 07777) | (is_dir($path) ? 0700 : 0600));
	}
	
	function loopback_request($url)
	{
		// Fetches one of the installer's own test files from this server. Returns the response
		// body, or FALSE when the server could not be reached at all. As in WordPress loopback
		// requests, the certificate is not verified: the request only reads a fixed test file from
		// this server, and a self-signed certificate must not block the installation.
		if( function_exists('curl_init') ) {
			$ch	= curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL		=> $url,
				CURLOPT_RETURNTRANSFER	=> TRUE,
				CURLOPT_HEADER	=> FALSE,
				CURLOPT_CONNECTTIMEOUT	=> 5,
				CURLOPT_TIMEOUT	=> 10,
				CURLOPT_FOLLOWLOCATION	=> TRUE,
				CURLOPT_MAXREDIRS	=> 5,
				CURLOPT_SSL_VERIFYPEER	=> FALSE,
				CURLOPT_SSL_VERIFYHOST	=> 0,
				CURLOPT_PROXY	=> '',
			));
			$res	= curl_exec($ch);
			return curl_errno($ch) ? FALSE : $res;
		}
		$ctx	= stream_context_create(array(
			'http'	=> array('timeout' => 10, 'ignore_errors' => TRUE),
			'ssl'	=> array('verify_peer' => FALSE, 'verify_peer_name' => FALSE),
		));
		return @file_get_contents($url, FALSE, $ctx);
	}
	
	/*function directory_tree_is_writable($node)
	{
		$node	= realpath($node);
		if( ! $node ) {
			return TRUE;
		}
		if( !is_readable($node) || !is_writable($node) ) {
			@chmod($node, 0777);
			if( !is_readable($node) || !is_writable($node) ) {
				return FALSE;
			}
		}
		if( is_dir($node) ) {
			$dir	= opendir($node);
			while($file = readdir($dir)) {
				if( $file == '.' || $file == '..' ) {
					continue;
				}
				if( ! directory_tree_is_writable($node.'/'.$file) ) {
					return FALSE;
				}
			}
			closedir($dir);
		}
		return TRUE;
	}
	
	function directory_tree_delete($node)
	{
		$node	= realpath($node);
		if( ! $node ) {
			return;
		}
		if( ! is_dir($node) ) {
			@unlink($node);
			return;
		}
		$dir	= opendir($node);
		while($file = readdir($dir)) {
			if( $file == '.' || $file == '..' ) {
				continue;
			}
			directory_tree_delete($node.'/'.$file);
		}
		closedir($dir);
		@rmdir($node);
		return;
	}
	
	*/
?>