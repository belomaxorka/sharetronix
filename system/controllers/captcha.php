<?php

	$namespace	= isset($_GET['namespace']) ? $_GET['namespace'] : 'signup';
	if( ! captcha::is_namespace($namespace) ) {
		header('HTTP/1.1 404 Not Found');
		exit;
	}

	$captcha	= new captcha($namespace);
	if( $captcha->is_widget() ) {
		header('HTTP/1.1 404 Not Found');
		exit;
	}

	$captcha->send_image();
	exit;

?>