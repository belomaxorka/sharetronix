<?php
	
	// The notification rules (users_notif_rules) of a member who has not changed them.
	function get_user_default_notification_rules()
	{
		return array (
			// 0 - off, 1 - on
			'ntf_them_if_i_follow_usr'	=> 1,
			'ntf_them_if_i_comment'		=> 1,
			'ntf_them_if_i_edt_profl'	=> 1,
			'ntf_them_if_i_edt_pictr'	=> 1,
			'ntf_them_if_i_create_grp'	=> 1,
			'ntf_them_if_i_join_grp'	=> 1,
			
			// 0 - off, 2 - post, 3 - email, 1 - both
			'ntf_me_if_u_follows_me'	=> 2,
			'ntf_me_if_u_follows_u2'	=> 0,
			'ntf_me_if_u_commments_me'	=> 0,
			'ntf_me_if_u_commments_m2'	=> 0,
			'ntf_me_if_u_edt_profl'		=> 0,
			'ntf_me_if_u_edt_pictr'		=> 0,
			'ntf_me_if_u_creates_grp'	=> 0,
			'ntf_me_if_u_joins_grp'		=> 2,
			'ntf_me_if_u_invit_me_grp'	=> 2,
			'ntf_me_if_u_posts_qme'		=> 0,
			'ntf_me_if_u_posts_prvmsg'	=> 0,
			'ntf_me_if_u_registers'		=> 0,
			'ntf_me_on_post_like'		=> 0,
		);
	}
	
	function set_user_default_notification_rules($user_id)
	{
		global $db2, $network;
		$rules	= get_user_default_notification_rules();
		$in_sql	= array();
		$in_sql[]	= '`user_id`="'.$user_id.'"';
		foreach($rules as $k=>$v) {
			$in_sql[]	= '`'.$k.'`="'.$v.'"';
		}
		$in_sql	= implode(', ', $in_sql);
		$db2->query('REPLACE INTO users_notif_rules SET '.$in_sql);
	}
	
	function isEmailConfirmed( $regid, $regkey )
	{
		global $page, $db2, $user;
		
		if( isset($user->sess['email_confirmed']) ){
			return $user->sess['email_confirmed'];
		}
		
		$reg_id	= intval($page->param('regid'));
		$reg_key	= $db2->e($page->param('regkey'));
		
		if( !$reg_id || empty($reg_id) || !$reg_key || empty($reg_key) ){
			return FALSE;
		}
		
		$db2->query('SELECT email FROM unconfirmed_registrations WHERE id="'.$reg_id.'" AND confirm_key="'.$reg_key.'" LIMIT 1');
		if( ! $obj = $db2->fetch_object() ) {
			return FALSE;
		}	
		
		$email		= stripslashes($obj->email);
		$user->sess['email_confirmed'] = $email;
		
		return $email;
	}
	
	function controllers_conflicts_lookup($username)
	{
		global $C;
		$error = FALSE;
		
		if( !$error && file_exists($C->INCPATH.'controllers/'.strtolower($username).'.php') ) {
			$error	= TRUE;
		}
		if( !$error && file_exists($C->INCPATH.'controllers/mobile/'.strtolower($username).'.php') ) {
			$error	= TRUE;
		}
		if( !$error && file_exists($C->INCPATH.'../'.strtolower($username)) ) {
			$error	= TRUE;
		}
		
		return $error;
	}
	
?>