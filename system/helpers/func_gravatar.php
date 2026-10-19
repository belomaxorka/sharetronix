<?php

	// Downloads the Gravatar of an e-mail address into $dest (https://docs.gravatar.com/sdk/images/:
	// SHA-256 of the trimmed, lower-cased address; d=404 answers 404 when there is no picture).
	// Returns 'ok', 'none' when the address has no Gravatar, or 'unavailable' when gravatar.com could
	// not be used (logged). A failed connection, a timeout, a server error or HTTP 429 is tried again
	// once, so the request waits at most about 11 seconds.
	function download_gravatar($email, $dest, $base_url = 'https://gravatar.com/avatar/')
	{
		global $C;
		if( ! function_exists('curl_init') ) {
			error_log('Sharetronix avatar: downloading a Gravatar needs the cURL extension.');
			return 'unavailable';
		}
		$url	= $base_url.hash('sha256', strtolower(trim($email))).'?s='.intval($C->AVATAR_SIZE).'&d=404';
		$reason	= '';
		$tries	= 0;
		while( $tries < 2 ) {
			$tries++;
			$curl	= new curlCall($url);
			$curl->setTimeouts(3, 5);
			$body	= $curl->getData();
			$status	= $curl->getStatus();
			if( $body !== FALSE && $status == 404 ) {
				return 'none';
			}
			if( $body !== FALSE && $status == 200 ) {
				if( file_put_contents($dest, $body) === strlen($body) ) {
					return 'ok';
				}
				$reason	= 'cannot write '.$dest;
				break;
			}
			$reason	= $body === FALSE ? $curl->getError() : 'HTTP '.$status;
			if( $body !== FALSE && $status < 500 && $status != 429 ) {
				break;
			}
			if( $tries < 2 ) {
				usleep(500000);
			}
		}
		error_log('Sharetronix avatar: Gravatar download failed ('.trim(preg_replace('/\s+/', ' ', $reason)).') after '.$tries.' attempt(s).');
		return 'unavailable';
	}

?>