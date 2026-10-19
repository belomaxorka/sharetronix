<?php
	$s = &$_SESSION['INSTALL_DATA'];
	
	$D->error	= FALSE;
	$D->errmsg	= '';
	$D->MYSQL_HOST		= '';
	$D->MYSQL_USER		= '';
	$D->MYSQL_PASS		= '';
	$D->MYSQL_DBNAME	= '';
	
	if( isset($_POST['MYSQL_HOST'], $_POST['MYSQL_USER'], $_POST['MYSQL_DBNAME']) ) {

		$D->MYSQL_HOST	= trim($_POST['MYSQL_HOST']);
		$D->MYSQL_USER	= trim($_POST['MYSQL_USER']);
		$D->MYSQL_PASS	= trim($_POST['MYSQL_PASS']);
		$D->MYSQL_DBNAME= trim($_POST['MYSQL_DBNAME']);	
		
		if( empty($D->MYSQL_HOST) ) {
			$D->error	= TRUE;
			$D->errmsg	= 'Please fill your MySQL host name, usually "localhost".';
		}elseif( empty($D->MYSQL_USER) ){
			$D->error	= TRUE;
			$D->errmsg	= 'Please fill your MySQL username.';
		}elseif(empty($D->MYSQL_DBNAME)){
			$D->error	= TRUE;
			$D->errmsg	= 'Please fill the empty MySQL database name.';
		}
		
		if( ! $D->error ) {
			$db 		= new mysqlExt($D->MYSQL_HOST, $D->MYSQL_USER, $D->MYSQL_PASS, $D->MYSQL_DBNAME);
			$conn 		= @$db->connect();

			if( is_numeric($conn) && $conn === 1  ){
				$D->error	= TRUE;
				$D->errmsg	= 'Cannot connect - please check host, username and password.';
				list($errno, $error)	= $db->get_connect_error();
				if( $error ) {
					$D->errmsg	.= '<br />The server answered: '.htmlspecialchars($errno.' '.$error, ENT_QUOTES, 'UTF-8');
				}
				if( $errno == 2054 ) {
					// PHP 7.2 cannot log in to the caching_sha2_password accounts of MySQL 8.
					$D->errmsg	.= '<br />This PHP version does not support the authentication method of this database user. Use PHP 8, or create the user with mysql_native_password (see the requirements in README.md).';
				}
			}elseif( is_numeric($conn) && $conn === 2  ){
				$D->error	= TRUE;
				$D->errmsg	= 'Database does not exist.';
			}
		}
		
		if( !$D->error ) {
			$tbl	= $db->query('SHOW TABLES FROM '.$db->quote_name($D->MYSQL_DBNAME));
			if( $tbl && $db->num_rows($tbl)>0 ) {
				$D->error	= TRUE;
				$D->errmsg	= 'Database must be empty - this one contains one or more tables.';
			}
		}
		
		if( !$D->error ){
			$tmp	= $db->get_server_version();
			
			// Every MariaDB release is newer than MySQL 5.0 by this measure (MariaDB 5.1 and later).
			if( $tmp < 50000 ) {
				$D->error	= TRUE;
				$D->errmsg	= 'Your MySQL server doesn\'t meet sharetronix requirenments, version 5.0 or higher required.';
			}
			// The tables use utf8mb4 with this collation.
			if( !$D->error && ! $db->fetch_field("SHOW COLLATION LIKE '".mysql::UTF8MB4_COLLATION."'") ) {
				$D->error	= TRUE;
				$D->errmsg	= 'Your database server does not support the utf8mb4 character set with the '.mysql::UTF8MB4_COLLATION.' collation. Sharetronix needs MySQL 5.7 or later or MariaDB 10.4 or later.';
			}
		}
		
		if( ! $D->error ) {
			$s['MYSQL_HOST'] = $D->MYSQL_HOST;
			$s['MYSQL_USER'] = $D->MYSQL_USER;
			$s['MYSQL_PASS'] = $D->MYSQL_PASS;
			$s['MYSQL_DBNAME'] = $D->MYSQL_DBNAME;
			
			$_SESSION['INSTALL_STEP']	= 3;
			header('Location: ?r='.rand(0,99999));
		}
	}
	
	loadTemplate('step_2');
	
?>