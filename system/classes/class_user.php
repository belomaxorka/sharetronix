<?php
	
	class user
	{
		public $cache;
		public $db1;
		public $db2;

		public $id;
		public $network;
		public $is_logged;
		public $info;
		public $sess;
		private static $_user_ident;
		
		public function __construct()
		{
			$this->id	= FALSE;
			$this->network	= & $GLOBALS['network'];
			$this->cache	= & $GLOBALS['cache'];
			$this->db1		= & $GLOBALS['db1'];
			$this->db2		= & $GLOBALS['db2'];
			$this->info		= new stdClass;
			$this->is_logged	= FALSE;
			$this->sess			= array();
			self::$_user_ident = FALSE;
		}
		
		public function LOAD()
		{
			if( ! $this->network->id ) {
				return FALSE;
			}
			global $C;
			$this->_session_start();
			if( isset($this->sess['IS_LOGGED'], $this->sess['LOGGED_USER']) && $this->sess['IS_LOGGED'] && $this->sess['LOGGED_USER'] ) {
				$u	= & $this->sess['LOGGED_USER'];
				$u	= $this->network->get_user_by_id($u->id);
				if( ! $u ) {
					return FALSE;
				}
				if( $this->network->id && $this->network->id == $u->network_id ) {
					$this->is_logged	= TRUE;
					$this->info	= & $u;
					
					/*$this->info->avatar_url = array(
							'thumbs1' => $C->STORAGE_URL . 'avatars/thumbs1/'.$this->info->avatar ,
							'thumbs2' => $C->STORAGE_URL . 'avatars/thumbs2/'.$this->info->avatar ,
							'thumbs3' => $C->STORAGE_URL . 'avatars/thumbs3/'.$this->info->avatar ,
					);
					$this->info->user_link = $this->userlink();*/
					
					$this->id	= $this->info->id;
					$this->db2->query('UPDATE users SET lastclick_date="'.time().'" WHERE id="'.$this->id.'" LIMIT 1');
					$deflang	= $C->LANGUAGE;
					if( ! empty($this->info->language) ) {
						$C->LANGUAGE	= $this->info->language;
					}
					if( $C->LANGUAGE != $deflang ) {
						$current_language	= new stdClass;
						include($C->INCPATH.'languages/'.$C->LANGUAGE.'/language.php');
						date_default_timezone_set($current_language->php_timezone);
						setlocale(LC_ALL, $current_language->php_locale);
						$C->PHP_LOCALE = $current_language->php_locale;
					}
					if( ! empty($this->info->timezone) ) {
						date_default_timezone_set($this->info->timezone);
					}
					if( $this->info->active == 0 ) {
						$this->logout();
						return FALSE;
					}
					return $this->id;
				}
			}
			if( $this->try_autologin() ) {
				$this->LOAD();
			}
			return FALSE;
		}
		
		private function _session_start()
		{
			if( ! $this->network->id ) {
				return FALSE;
			}
			if( ! isset($_SESSION['NETWORKS_USR_DATA']) ) {
				$_SESSION['NETWORKS_USR_DATA']	= array();
			}
			if( ! isset($_SESSION['NETWORKS_USR_DATA'][$this->network->id]) ) {
				$_SESSION['NETWORKS_USR_DATA'][$this->network->id]	= array();
			}
			$this->sess	= & $_SESSION['NETWORKS_USR_DATA'][$this->network->id];
		}
		
		public function login($login, $pass, $rememberme=FALSE)
		{
			global $C;
			if( ! $this->network->id ) {
				return FALSE;
			}
			if( $this->is_logged ) {
				return FALSE;
			}
			if( empty($login) ) {
				return FALSE;
			}
			$login	= $this->db2->escape($login);
			$pass		= $this->db2->escape($pass);
			$this->db2->query('SELECT id FROM users WHERE (email="'.$login.'" OR username="'.$login.'") AND password="'.$pass.'" AND active=1 LIMIT 1');
			if( ! $obj = $this->db2->fetch_object() ) {
				return FALSE;
			}
			$this->info	= $this->network->get_user_by_id($obj->id, TRUE);
			if( ! $this->info ) {
				return FALSE;
			}
			
			$this->is_logged		= TRUE;
			$this->sess['IS_LOGGED']	= TRUE;
			$this->sess['LOGGED_USER']	= & $this->info;
			$this->id	= $this->info->id;
			
			$ip	= $this->db2->escape( ip2long($_SERVER['REMOTE_ADDR']) );
			$this->db2->query('UPDATE users SET lastlogin_date="'.time().'", lastlogin_ip="'.$ip.'", lastclick_date="'.time().'" WHERE id="'.$this->id.'" LIMIT 1');
			if( TRUE == $rememberme ) {
				$tmp	= $this->id.'_'.md5($this->info->username.'~~'.$this->info->password.'~~'.$_SERVER['HTTP_USER_AGENT']);
				// HttpOnly, like the session cookie: it signs the member in, and no script needs to read it.
				setcookie('rememberme', $tmp, time()+60*24*60*60, '/', cookie_domain(), FALSE, TRUE);
			}
			
			//$this->sess['total_pageviews']	= 0;
			$this->sess['cdetails']	= $this->db2->fetch('SELECT * FROM users_details WHERE user_id="'.$this->id.'" LIMIT 1');
			return TRUE;
		}
		
		public function try_autologin()
		{
			if( ! $this->network->id ) {
				return FALSE;
			}
			if( $this->is_logged ) {
				return FALSE;
			}
			if( ! isset($_COOKIE['rememberme']) || ! is_string($_COOKIE['rememberme']) ) {
				return FALSE;
			}
			$tmp	= explode('_', $_COOKIE['rememberme']);
			$this->db2->query('SELECT username, password, email FROM users WHERE id="'.intval($tmp[0]).'" AND active=1 LIMIT 1');
			if( ! $obj = $this->db2->fetch_object() ) {
				return FALSE;
			}
			$obj->username	= stripslashes($obj->username);
			$obj->password	= stripslashes($obj->password);
			if( isset($tmp[1]) && $tmp[1] == md5($obj->username.'~~'.$obj->password.'~~'.$_SERVER['HTTP_USER_AGENT']) ) {
				return $this->login($obj->username, $obj->password, TRUE);
			}
			setcookie('rememberme', '', time()+30*24*60*60, '/', cookie_domain());
			$_COOKIE['rememberme']	= NULL;
			return FALSE;
		}
		
		public function logout()
		{
			if( ! $this->is_logged ) {
				return FALSE;
			}
			setcookie('rememberme', '', time()+60*24*60*60, '/', cookie_domain());
			$_COOKIE['rememberme']	= NULL;
			$this->sess['IS_LOGGED']	= FALSE;
			$this->sess['LOGGED_USER']	= NULL;
			unset($this->sess['IS_LOGGED']);
			unset($this->sess['LOGGED_USER']);
			$this->id	= FALSE;
			$this->info	= new stdClass;
			$this->is_logged	= FALSE;
		}
		
		public function follow($whom_id, $how=TRUE)
		{
			global $plugins_manager;
			
			if( ! $this->is_logged ) {
				return FALSE;
			}
			$whom	= $this->network->get_user_by_id($whom_id);
			if( ! $whom ) {
				return FALSE;
			}
			if( $whom->active === 0 && $how == TRUE ){
				return FALSE;
			}
			$f	= $this->network->get_user_follows($this->id, TRUE, 'hefollows')->follow_users;
			if( isset($f[$whom_id]) && $how==TRUE ) {
				return TRUE;
			}
			if( !isset($f[$whom_id]) && $how==FALSE ) {
				return TRUE;
			}
			
			if( $how == TRUE ){
				// $args[0] stays the current user (as in 3.x); $args[1] is the user to follow
				$plugins_manager->onUserFollow( $this, $whom );
				if( !$plugins_manager->isValidEventCall() ){
					return FALSE;
				}
			}else{
				$plugins_manager->onUserUnfollow( $this, $whom );
				if( !$plugins_manager->isValidEventCall() ){
					return FALSE;
				}
			}
			
			if( $how == TRUE ) {
				$this->db2->query('INSERT INTO users_followed SET who="'.$this->id.'", whom="'.$whom_id.'", date="'.time().'", whom_from_postid="'.$this->network->get_last_post_id().'" ');
				$this->db2->query('UPDATE users SET num_followers=num_followers+1 WHERE id="'.$whom_id.'" LIMIT 1');
				
				$notif = new notifier();
				$notif->set_notification_obj('user', $whom_id);
				$notif->onFollowUser($whom_id);
			}
			else {
				$this->db2->query('DELETE FROM users_followed WHERE who="'.$this->id.'" AND whom="'.$whom_id.'" ');
				$this->db2->query('UPDATE users SET num_followers=num_followers-1 WHERE id="'.$whom_id.'" LIMIT 1');
				$this->db2->query('DELETE FROM post_userbox WHERE user_id="'.$this->id.'" AND post_id IN(SELECT id FROM posts WHERE user_id="'.$whom_id.'")');
			}
			$this->network->get_user_by_id($whom_id, TRUE);
			$this->network->get_user_follows($whom_id, TRUE);
			$this->network->get_user_follows($whom_id, TRUE, 'hisfollowers');
			
			$this->network->get_user_follows($this->id, TRUE);
			$this->network->get_user_follows($this->id, TRUE, 'hefollows');
			
			return TRUE;
		}
		
		public function follow_group($group_id, $how=TRUE)
		{
			global $plugins_manager;
			
			if( ! $this->is_logged ) {
				return FALSE;
			}
			$group	= $this->network->get_group_by_id($group_id);
			if( ! $group ) {
				return FALSE;
			}
			$priv_members	= array();
			if( $group->is_private && !$this->info->is_network_admin ) {
				$priv_members	= $this->network->get_group_invited_members($group_id);
				if( ! $priv_members ) {
					return FALSE;
				}
				if( ! in_array(intval($this->id), $priv_members) ) {
					return FALSE;
				}
			}
			$f	= $this->network->get_user_follows($this->id, TRUE, 'hisgroups')->follow_groups;
			if( isset($f[$group_id]) && $how==TRUE ) {
				return TRUE;
			}
			if( !isset($f[$group_id]) && $how==FALSE ) {
				return TRUE;
			}
			
			if( $how == TRUE ){
				$plugins_manager->onGroupJoin( $group );
				if( !$plugins_manager->isValidEventCall() ){
					return FALSE;
				}
			}else{
				$plugins_manager->onGroupLeave( $group );
				if( !$plugins_manager->isValidEventCall() ){
					return FALSE;
				}
			}
			
			if( $how == TRUE ) {
				$this->db2->query('INSERT INTO groups_followed SET user_id="'.$this->id.'", group_id="'.$group_id.'", date="'.time().'", group_from_postid="'.$this->network->get_last_post_id().'" ');
				$this->db2->query('UPDATE `groups` SET num_followers=num_followers+1 WHERE id="'.$group_id.'" LIMIT 1');
				
				$notif = new notifier();
				$notif->set_group_id($group_id);
				$notif->set_notification_obj('user', $this->id);
				$notif->onJoinGroup($group_id, $group->is_private);

			}
			else {
				if( ! $this->if_can_leave_group($group_id) ) {
					return FALSE;
				}
				$this->db2->query('DELETE FROM groups_admins WHERE user_id="'.$this->id.'" AND group_id="'.$group_id.'" ');
				$this->db2->query('DELETE FROM groups_followed WHERE user_id="'.$this->id.'" AND group_id="'.$group_id.'" ');
				$this->db2->query('UPDATE `groups` SET num_followers=num_followers-1 WHERE id="'.$group_id.'" LIMIT 1');
				$not_in_users	= array_keys($this->network->get_user_follows($this->id, FALSE, 'hefollows')->follow_users);
				$not_in_users	= count($not_in_users)==0 ? '' : 'AND user_id NOT IN('.implode(', ', $not_in_users).')';
				$this->db2->query('DELETE FROM post_userbox WHERE user_id="'.$this->id.'" AND post_id IN(SELECT id FROM posts WHERE group_id="'.$group_id.'" AND user_id<>"'.$this->id.'" '.$not_in_users.' )');
				$this->db2->query('DELETE FROM groups_private_members WHERE user_id="'.$this->id.'" AND group_id="'.$group_id.'" ');
				//$this->db2->query('DELETE FROM group_notifications WHERE from_user_id="'.$this->id.'" AND to_group_id="'.$group_id.'" ');
			}
			$this->network->get_group_by_id($group_id, TRUE);
			$this->network->get_group_members($group_id, TRUE);
			$this->network->get_user_follows($this->id, TRUE);
			$this->network->get_user_follows($this->id, TRUE, 'hisgroups');
			// leaving deletes the member's row in groups_admins
			$this->network->user_admin_group_ids($this->id, TRUE);
			
			return TRUE;
		}
		
		public function if_follow_user($user_id)
		{
			if( ! $this->is_logged ) {
				return FALSE;
			}
			$res = $this->db2->fetch_field('SELECT id FROM users_followed WHERE who = '.$this->id.' AND whom = '.intval($user_id).' LIMIT 1');
			return $res? TRUE : FALSE;
		}
		
		public function if_user_follows_me($user_id)
		{
			if( ! $this->is_logged ) {
				return FALSE;
			}
			$res = $this->db2->fetch_field('SELECT id FROM users_followed WHERE whom = '.$this->id.' AND who = '.intval($user_id).' LIMIT 1');
			return $res? TRUE : FALSE;
		}
		
		public function if_follow_group($group_id)
		{
			if( ! $this->is_logged ) {
				return FALSE;
			}
			$res = $this->db2->fetch_field('SELECT id FROM groups_followed WHERE user_id = "'.$this->id.'" AND group_id = "'.intval($group_id).'"'.' LIMIT 1');
			return $res? TRUE : FALSE;
		}
		
		public function if_can_leave_group($group_id)
		{
			if( ! $this->is_logged ) {
				return FALSE;
			}
			$r = $this->db2->fetch_field('SELECT COUNT(*) AS c FROM groups_admins WHERE group_id="'.intval($group_id).'" AND user_id<>"'.$this->id.'" LIMIT 1');
			
			// демек ако има други админи освен мен, мога да куитна
			return ($r > 0)? TRUE : FALSE;
		}
		
		public function get_top_groups($num)
		{
			if( ! $this->is_logged ) {
				return array();
			}
			$num	= intval($num);
			if( 0 == $num ) {
				return array();
			}
			
			$data 	= array();
			$tmp	= array_slice($this->network->get_user_follows($this->id, FALSE, 'hisgroups')->follow_groups, 0 , $num, TRUE);
			foreach($tmp as $gid=>$sdf) { 
				$g	= $this->network->get_group_by_id($gid);
				if( ! $g ) {
					continue;
				}
				$data[]	= $g;
			}
			
			return $data;
		}
		
		public function get_my_private_groups_ids($force_refresh = FALSE)
		{
			$private_groups_ids = array();
			
			if( ! $this->id || ! $this->is_logged ) {
				return $private_groups_ids;
			}
				
			$r	= $this->db2->query('SELECT group_id FROM `groups_followed`,`groups` WHERE `groups`.id=groups_followed.group_id AND `groups`.is_public=0 AND groups_followed.user_id="'.$this->id.'"
			UNION SELECT group_id FROM `groups_private_members` WHERE user_id="'.$this->id.'"', FALSE);
			while($obj = $this->db2->fetch_object($r)) {
				$private_groups_ids[]	= $obj->group_id;
			}
	
			return $private_groups_ids;
		}
		
		public function get_my_post_protected_follower_ids($force_refresh = FALSE)
		{
			if( ! $this->id ) {
				return array();
			}
			global $C;
			
			// The cached list of the network (refreshed when a member changes the setting); reading the users
			// table here scanned all of it for every filtered feed, search, and RSS request. Only the protected
			// members among the followers matter: they are asked for directly instead of loading the follower
			// list, which the cache holds by the hundred thousand for a popular member.
			$post_protected_user_ids = $this->network->get_post_protected_user_ids($force_refresh);
			if( count($post_protected_user_ids) == 0 ) {
				return array();
			}
			
			$my_followers = array();
			$r	= $this->db2->query('SELECT who FROM users_followed WHERE whom="'.intval($this->id).'" AND who IN('.implode(', ', array_map('intval', $post_protected_user_ids)).')', FALSE);
			while($obj = $this->db2->fetch_object($r)) {
				$my_followers[]	= $obj->who;
			}
			$my_followers[] = $this->id;
	
			$post_protected_user_ids = array_intersect($my_followers, $post_protected_user_ids);
			
			return $post_protected_user_ids;
		}
		
		public function checkPassByUserId( $pass, $is_admin = FALSE )
		{
			$pass = trim($pass);
			if( empty($pass) ){
				return FALSE;
			}
				
			$user_id = $this->db2->fetch_field('SELECT is_network_admin FROM users WHERE id="'.$this->id.'" AND password="'.$pass.'" '. ($is_admin? ' AND is_network_admin=1 ' : '') .' LIMIT 1');
			if( $user_id === FALSE ){
				return FALSE;
			}
				
			return TRUE;
		}
		
		public function getCommunityName()
		{
			global $C;
			
			if( self::$_user_ident == FALSE ){
				self::$_user_ident = (isset($C->NAME_INDENTIFICATOR) && $C->NAME_INDENTIFICATOR == 2 && !empty($this->info->fullname))? $this->info->fullname : $this->info->username;
			}
			
			return self::$_user_ident;
		}
		
		public function get_admin_users()
		{
			$admins = array();
			
			$r	= $this->db2->query('SELECT * FROM users WHERE is_network_admin="1"', FALSE);
		
			while($obj = $this->db2->fetch_object($r)) {
				$admins[$obj->id]	= $obj;
			}
				
			return $admins;
		}
		
		public function save_search($search_string, $search_tab, $puser = '', $pgroup = '')
		{
			if(empty($search_string) || empty($search_tab)){
				return false;
			}
			
			$search_url = '/tab:'.$search_tab.'/s:'.$search_string;
			if(is_string($puser) && !empty($puser)){
				$search_url .= '/puser:'.$puser;
			}
			
			if(is_string($pgroup) && !empty($pgroup)){
				$search_url .= '/pgroup:'.$pgroup;
			}
			
			$search_key = md5($search_url);
			
			$r = $this->db2->query('SELECT 1 FROM searches WHERE user_id="'. (int) $this->id .'" AND search_key="'. $search_key .'" LIMIT 1');
			if( $o = $this->db2->fetch_object($r) ) {
				return false;
			}

			// The search text and the address built from it come from the request.
			$this->db2->query('INSERT INTO searches(user_id, search_key, search_string, search_url, added_date) VALUES(
				'. (int) $this->id .', "'.$search_key.'", "'.$this->db2->e($search_string).'", "'.$this->db2->e($search_url).'", '. time() .'
			);');
			
			$this->get_saved_searches(TRUE);
			
			return true;
		}
		
		public function get_saved_searches($force_refresh = FALSE)
		{	
			if( ! $this->is_logged ) {
				return array();
			}
			global $C;
			
			$cachekey	= 'n:'.$this->network->id.',usersavedsearches:'.$this->id;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			
			$data = array();
			$this->db2->query('SELECT id, search_url, search_string FROM searches WHERE user_id="'.$this->id.'" ORDER BY id DESC');
			while($tmp = $this->db2->fetch_object()) {
				$tmp->search_string		= stripslashes($tmp->search_string);
				$tmp->search_url	= 'search'.stripslashes($tmp->search_url);
				$data[$tmp->id]		= $tmp;
			}
			
			$this->cache->set($cachekey, $data, $C->CACHE_EXPIRE);
			return $data;
		}
	}
	
?>