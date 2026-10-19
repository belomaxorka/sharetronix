<?php

	chdir(dirname(__FILE__));
	require_once('./include/authorize.php');
	require_once('./config/config_system.php');
	require_once('./config/config.php');
	require_once( $C->INCPATH.'classes/class_mysql.php' );
	require_once( $C->INCPATH.'classes/class_pageDesigner.php' );
	require_once( $C->INCPATH.'helpers/func_signup.php' );
	require_once( $C->INCPATH.'helpers/func_main.php' );
	require_once( $C->INCLUDEPATH.'func_database.php' );
	require_once( $C->INCLUDEPATH.'functions.php' );
	require_once( $C->INCLUDEPATH.'directories.php' );
	require_once( $C->INCLUDEPATH.'classes/class_mysqlExt.php' );
	
	chdir(dirname(__FILE__));

	if( ! isset($_SESSION['INSTALL_STEP']) ) {
		$_SESSION['INSTALL_STEP']	= 1;
	}
	if( ! isset($_SESSION['INSTALL_DATA']) ) {
		$_SESSION['INSTALL_DATA']	= array();
	}
	
	$s = & $_SESSION['INSTALL_DATA'];
	if( isset($s['MYSQL_HOST']) && isset($s['MYSQL_USER']) && isset($s['MYSQL_PASS']) && isset($s['MYSQL_DBNAME']) ){
		$db 		= new mysqlExt($s['MYSQL_HOST'], $s['MYSQL_USER'], $s['MYSQL_PASS'], $s['MYSQL_DBNAME']);
	}
	
	$D->designer = new pageDesigner();

	// An update reads the installed version again on every request: a failed update may have
	// applied part of its changes.
	if( !isset($_SESSION['INSTALL_DATA']['IS_UPDATE']) || $_SESSION['INSTALL_DATA']['IS_UPDATE'] ){
		$old_version	= get_old_version();
		if( $old_version ){
			$_SESSION['INSTALL_DATA']['IS_UPDATE'] = $old_version?	TRUE : FALSE;
			$_SESSION['INSTALL_DATA']['OLD_VERSION'] = $old_version;
			
		}else{
			$_SESSION['INSTALL_DATA']['IS_UPDATE'] = FALSE;
   			$_SESSION['INSTALL_STEP'] = 1;
		}
	}

	if( $_SESSION['INSTALL_DATA']['IS_UPDATE'] ){
		$controller_name = ($_SESSION['INSTALL_STEP'] == 1 && version_compare($_SESSION['INSTALL_DATA']['OLD_VERSION'], $C->VERSION, '<'))? 'step_' . $_SESSION['INSTALL_STEP'] : 'update';
	}else{
		$controller_name = 'step_' . $_SESSION['INSTALL_STEP'];
	}

	if( $controller_name=='update' ) {
		unset($_SESSION['INSTALL_UPDATE_ALLOWED']);
		// If the update stops with an error, the next visit starts again at step 1 instead of
		// running the update by itself.
		$_SESSION['INSTALL_STEP'] = 1;
	}
	loadController( $controller_name  );
	if( $controller_name=='update' ) {
		unset($_SESSION['INSTALL_DATA'], $_SESSION['INSTALL_STEP']);
	}
	
?>