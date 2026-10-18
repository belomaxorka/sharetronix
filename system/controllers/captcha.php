<?php
	require_once( $C->INCPATH.'libraries/securimage/securimage.php');
	
	$img = new Securimage();
	$img->setNamespace(isset($_GET['namespace'])? $_GET['namespace'] : 'signup');
	$img->show();