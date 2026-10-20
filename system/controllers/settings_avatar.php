<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	//require_once( $C->INCPATH.'helpers/func_images.php' );
	
	global $plugins_manager;
	
	$u	= & $this->user;
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$send_notif	= FALSE;
	$use_gravatar	= isset($_POST['use_gravatar']);
	$gravatar_tmp	= FALSE;
	if( $use_gravatar || (isset($_FILES['profile_avatar']) && is_uploaded_file($_FILES['profile_avatar']['tmp_name'])) ) {
		$submit	= TRUE;
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error && $use_gravatar ){
			require_once( $C->INCPATH.'helpers/func_gravatar.php' );
			$gravatar_tmp	= $C->TMP_DIR.'grvtr'.time().rand(0,9999).'.img';
			$res	= download_gravatar($this->user->info->email, $gravatar_tmp);
			if( $res == 'none' ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_avatar_err_nogravatar', array('#EMAIL#'=>htmlspecialchars($this->user->info->email)));
			}
			elseif( $res != 'ok' ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_avatar_err_gravatar');
			}
		}
		
		if( !$error ){
			$f	= $use_gravatar ? (object) array('tmp_name' => $gravatar_tmp) : (object) $_FILES['profile_avatar'];
			list($w, $h, $tp) = getimagesize($f->tmp_name);
			if( $w==0 || $h==0 ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_avatar_err_invalidfile');
			}
			elseif( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_avatar_err_invalidformat');
			}
			elseif( $w<$C->AVATAR_SIZE || $h<$C->AVATAR_SIZE ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_avatar_err_toosmall');
			}
			
			if( ! $error ) {
				$avtr	= $this->user->info->avatar;
				if( $avtr != $C->DEF_AVATAR_USER ) {
					rm( $C->STORAGE_DIR.'avatars/'.$avtr );
					rm( $C->STORAGE_DIR.'avatars/thumbs1/'.$avtr );
					rm( $C->STORAGE_DIR.'avatars/thumbs2/'.$avtr );
					rm( $C->STORAGE_DIR.'avatars/thumbs3/'.$avtr );
					rm( $C->STORAGE_DIR.'avatars/thumbs4/'.$avtr );
					rm( $C->STORAGE_DIR.'avatars/thumbs5/'.$avtr );
				}else{
					$avtr	= time().rand(100000,999999).'.png';	
				}
				
				if( $avtr != $C->DEF_AVATAR_USER ) {
					$res	= copy_avatar($f->tmp_name, $avtr);
					if( ! $res) {
						$error	= TRUE;
						$errmsg	= $this->lang('st_avatar_err_cantcopy');
					}
				}else{
					$avtr = '';
				}
				
				$db2->query('UPDATE users SET avatar="'.$db2->e($avtr).'" WHERE id="'.$this->user->id.'" LIMIT 1');
				$this->network->get_user_by_id($this->user->id, TRUE);
				$this->network->get_online_users(TRUE);
				$send_notif	= TRUE;
				$this->user->info->avatar	= $avtr;
			}
			
		}
		if( $gravatar_tmp ) {
			rm($gravatar_tmp);
		}
	}
	elseif( $this->param('del') == 'current' ) {
		$old	= $this->user->info->avatar;
		if( $old != $C->DEF_AVATAR_USER ) {
			rm( $C->STORAGE_DIR.'avatars/'.$old );
			rm( $C->STORAGE_DIR.'avatars/thumbs1/'.$old );
			rm( $C->STORAGE_DIR.'avatars/thumbs2/'.$old );
			rm( $C->STORAGE_DIR.'avatars/thumbs3/'.$old );
			rm( $C->STORAGE_DIR.'avatars/thumbs4/'.$old );
			rm( $C->STORAGE_DIR.'avatars/thumbs5/'.$old );
			$db2->query('UPDATE users SET avatar="" WHERE id="'.$this->user->id.'" LIMIT 1');
			$this->user->info->avatar	= $C->DEF_AVATAR_USER;
			$this->network->get_user_by_id($this->user->id, TRUE);
			$msg	= 'deleted';
			$send_notif	= TRUE;
		}
	}else if( isset($_POST['sbm']) ){
		$submit = TRUE;
	}
	
	
	if( $send_notif ) {
		$notif = new notifier();
		$notif->set_notification_obj('user', $this->user->id);
		$notif->onChangeAvatar();	
	}
	
	list($currw, $currh) = getimagesize($C->STORAGE_DIR.'avatars/'.$u->info->avatar);
	
	
	$tpl = new template( array('page_title' => $this->lang('settings_avatar_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_avatat_err'), $errmsg) );
	}else if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_avatat_ok'), $this->lang('st_avatat_okmsg') ) );
	}else if( isset($msg) && $msg == 'deleted' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_avatat_ok'), $this->lang('st_avatat_okdelmsg') ) );
	}
	
	$table = new tableCreator();
	
	$rows = array(
			$table->textField( $this->lang('st_avatar_current_picture'), '<img src="'. $C->STORAGE_URL .'avatars/thumbs1/'. $u->info->avatar .'" alt="" border="0" />' ),
			$table->fileField( $this->lang('st_avatar_change_picture'), 'profile_avatar', '' ),
			$table->textField( '', $this->lang('st_avatar_change_info') ),
			$table->textField( $this->lang('st_avatar_remove_ttl'), '<a href="'.$C->SITE_URL.'settings/avatar/del:current">'.$this->lang('st_avatar_upload_or_delete').'</a>' ),
			$table->submitButton( 'sbm', $this->lang('st_avatar_uploadbtn') ),
			$table->textField( $this->lang('st_avatar_gravatar'), $this->lang('st_avatar_gravatar_info', array('#EMAIL#'=>htmlspecialchars($u->info->email))) ),
			$table->submitButton( 'use_gravatar', $this->lang('st_avatar_gravatar_btn') )
	);

	$table->form_title = $this->lang('settings_avatar_ttl2');
	$table->form_enctype = 'enctype="multipart/form-data"';
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
?>