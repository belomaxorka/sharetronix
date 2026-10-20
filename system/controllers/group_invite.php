<?php

	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/group.php');
	$this->load_langfile('inside/group_invite.php');
	
	
	$g	= $this->network->get_group_by_id(intval($this->params->group));
	if( ! $g ) {
		$this->redirect('groups');
	}
	if( $g->is_private ) {
		$u	= $this->network->get_group_members($g->id);
		if( !$u || !isset($u[$this->user->id]) ) {
			$this->redirect('dashboard');
		}
	}
	
	$D->g	= & $g;
	$D->i_am_member	= $this->user->if_follow_group($g->id);
	$D->i_am_admin	= FALSE;
	if( $D->i_am_member ) {
		$D->i_am_admin	= $db->fetch('SELECT id FROM groups_admins WHERE group_id="'.$g->id.'" AND user_id="'.$this->user->id.'" LIMIT 1') ? TRUE : FALSE;
	}
	if( !$D->i_am_admin && $this->user->info->is_network_admin==1 ) {
		$D->i_am_admin	= TRUE;
	}
	$D->i_can_invite	= $D->i_am_admin || ($D->i_am_member && $g->is_public);
	
	if( ! $D->i_can_invite ) {
		$this->redirect($C->SITE_URL.$g->groupname);
	}
	
	//$D->page_favicon	= $C->IMG_URL.'avatars/thumbs2/'.$g->avatar;
	
	$data	= array();
	$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
	$from	=  ($pg - 1) * $C->PAGING_NUM_USERS;
	
	if( !isset($_POST['usersearch']) ){
		$users_not_for_invitations = array($this->user->id);
		$tmp = array_keys($this->network->get_group_members($g->id));
		$users_not_for_invitations = array_merge($users_not_for_invitations, $tmp);
		
		$tmp = $this->network->get_group_invited_members($g->id);
		$users_not_for_invitations = array_merge($users_not_for_invitations, $tmp);
		$users_not_for_invitations = array_unique($users_not_for_invitations);		
		
		// A COUNT(*) with the same condition instead of SQL_CALC_FOUND_ROWS and FOUND_ROWS(), which MySQL 8.0.17 deprecated (warning 1287).
		$where = 'id NOT IN('.implode(',', $users_not_for_invitations).')';
		$num_results = $this->db2->fetch_field('SELECT COUNT(*) FROM users WHERE '.$where);
		$r = $this->db2->query('SELECT * FROM users WHERE '.$where.' ORDER BY username ASC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);
		while($row = $this->db2->fetch_object($r)){
			$tmp = new stdClass;
			$tmp->username 	= $row->username;
			$tmp->id 		= $row->id;
			$tmp->fullname 	= $row->fullname;
			$tmp->avatar	= empty($row->avatar)? $GLOBALS['C']->DEF_AVATAR_USER : $row->avatar;
		
			$data[] = $tmp;
		}
		
	}else{
		if( !empty($_POST['usersearch']) && is_string($_POST['usersearch']) ){ 
			$srch = $this->db2->e($_POST['usersearch']);
			$where = 'username LIKE "%'.$srch.'%" OR fullname LIKE "%'.$srch.'%"';
			$num_results = $this->db2->fetch_field('SELECT COUNT(*) FROM users WHERE '.$where);
			$r = $this->db2->query('SELECT * FROM users WHERE '.$where.' ORDER BY username ASC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);
			while($row = $this->db2->fetch_object($r)){
				$tmp = new stdClass;
				$tmp->username 	= $row->username;
				$tmp->id 		= $row->id;
				$tmp->fullname 	= $row->fullname;
				$tmp->avatar	= empty($row->avatar)? $GLOBALS['C']->DEF_AVATAR_USER : $row->avatar;
				
				$data[] = $tmp;
			}
		}
	}
	
	if( !isset($num_results) ) {
		$num_results = 0;
	}
	$num_pages	= ceil($num_results / $C->PAGING_NUM_USERS);
	$paging_url	= $C->SITE_URL.$g->groupname.'/invite/pg:';
	
	if( isset($_POST['submit_invite_members']) && isset($_SESSION['group_'.$g->id]))
	{
		$_POST['invite_users'] = array_keys($_SESSION['group_'.$g->id]);
		$group = new group( $g );
		$group->invite();
		unset($_SESSION['group_'.$g->id]);
	}
	
	$tpl = new template( array(
			'page_title' => $this->lang('os_grpinv_pagetitle', array('#GROUP#'=>$g->title, '#SITE_TITLE#'=>$C->SITE_TITLE)),
			'header_page_layout'=>'c',
	));
	
	if( count($data) ){
		$tpl->layout->useBlock('user-invite-form');
		
		$float = 'left-container';
		foreach($data as $o){
			$tpl->layout->useInnerBlock('single-user-invite');
			
			$tpl->layout->inner_block->setVar( 'single_user_avatar', '<a href="'.userlink($o->username).'"><img src="'.$C->STORAGE_URL.'avatars/thumbs1/'.$o->avatar.'" alt="'.$o->fullname.'" /></a>');
			$tpl->layout->inner_block->setVar( 'single_user_username', '<a href="'.userlink($o->username).'">'.ucfirst($o->fullname).'</a>' );
			$tpl->layout->inner_block->setVar( 'single_invite_user_id', $o->id );
			$tpl->layout->inner_block->setVar('invite_data_value', htmlentities('{"group_id": '.$g->id.', "user_id":"'.$o->id.'"}'));
			
			$tpl->layout->inner_block->setVar( 'invite_checkbox_checked', (isset($_SESSION['group_'.$g->id][$o->id])? 'checked' : '') );
			
			$float = ($float == 'left-container')? 'right-container' : 'left-container';
			$tpl->layout->inner_block->setVar( 'single_user_float', $float );
			
			$tpl->layout->inner_block->saveInBlockPart('users_invite_data', true);
		}
		
		$tpl->layout->block->save('main_content');
		
		$tpl->layout->setVar( 'main_content', $tpl->designer->pager( $num_results, $num_pages, $pg, $paging_url ) );
	}else{
		$tpl->layout->setVar('main_content', $tpl->designer->errorMessage($this->lang('grpinv_nobody_ttl'), $this->lang('group_invite_no_memebers_found')));
	}
	
	$tpl->display();
	
?>