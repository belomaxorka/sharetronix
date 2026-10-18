<?php

	if( file_exists(dirname(__FILE__).'/../../system/conf_main.php') ) {
		$installer_directory = getcwd();
		require_once(dirname(__FILE__).'/../../system/helpers/func_main.php');
		require_once(dirname(__FILE__).'/../../system/helpers/func_images.php');
		require_once(dirname(__FILE__).'/../../system/conf_system.php');
		session_start();

		$cache = new cache();
		$db1 = new mysql($C->DB_HOST, $C->DB_USER, $C->DB_PASS, $C->DB_NAME);
		$db2 = & $db1;
		$network = new network();
		$network->LOAD();
		$user = new user();
		$user->LOAD();

		if( !$user->is_logged || !$db2->fetch_field('SELECT id FROM users WHERE id="'.intval($user->id).'" AND active=1 AND is_network_admin=1 LIMIT 1') ) {
			header('HTTP/1.1 403 Forbidden');
			header('Content-Type: text/plain; charset=utf-8');
			exit('Sign in as an active administrator before accessing the Sharetronix updater.');
		}

		$csrf = new csrf('installer');
		$allowed = isset($_SESSION['INSTALL_UPDATE_ALLOWED']['user_id'], $_SESSION['INSTALL_UPDATE_ALLOWED']['expires'])
			&& $_SESSION['INSTALL_UPDATE_ALLOWED']['user_id']==$user->id
			&& $_SESSION['INSTALL_UPDATE_ALLOWED']['expires']>=time();
		if( !$allowed ) {
			if( isset($_POST['confirm_update']) ) {
				if( !isset($_POST['installer_token']) || !is_string($_POST['installer_token']) || !$csrf->check_if_valid($_POST['installer_token']) ) {
					header('HTTP/1.1 403 Forbidden');
					exit('Invalid or expired updater confirmation.');
				}
				$_SESSION['INSTALL_UPDATE_ALLOWED'] = array('user_id'=>$user->id, 'expires'=>time()+30*60);
			}
			elseif( $_SERVER['REQUEST_METHOD']=='POST' ) {
				header('HTTP/1.1 403 Forbidden');
				exit('Confirm the update before submitting installer data.');
			}
			else {
				header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
	<head><meta charset="utf-8"><title>Sharetronix update</title></head>
	<body>
		<h1>Sharetronix update</h1>
		<p>Back up the database, configuration, and uploaded files before continuing.</p>
		<form method="post">
			<input type="hidden" name="installer_token" value="<?= htmlspecialchars($csrf->get_value(), ENT_QUOTES, 'UTF-8') ?>">
			<button type="submit" name="confirm_update" value="1">Start update</button>
		</form>
	</body>
</html>
<?php
				exit;
			}
		}
		session_write_close();
		chdir($installer_directory);
	}
