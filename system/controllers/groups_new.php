<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/group.php');
	$this->load_langfile('inside/groups_new.php');

	$submit	= FALSE;
	$group_type = 'public';
	$group_alias = '';
	$group_name = '';
	$group_description = '';
	$group = new group();
	$group_sub_categories = $group->getGroupCategories(true);
	$sub_categories = array();
	
	foreach($group_sub_categories as $key => $value){
		$sub_categories[$value->id] = ucfirst(strtolower($value->title));
	}
	
	if( isset($_POST['sbm']) ) { 
		global $plugins_manager;
		
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errtxt = $plugins_manager->getEventCallErrorMessage();
		}
		
		$submit	= TRUE;
		
		// A plugin that refused the submit has the last word: no group, and its message is shown.
		if( ! isset($errtxt) ){
			$group = new group();
			$errmsg = $group->createGroup();
			$error = !empty($errmsg)? TRUE : FALSE;
		}
		
		$group_name			= isset($_POST['group_name']) && is_string($_POST['group_name'])? htmlspecialchars( trim($_POST['group_name']) ) : '';
		$group_alias		= isset($_POST['group_alias']) && is_string($_POST['group_alias'])? htmlspecialchars( trim($_POST['group_alias']) ) : '';
		$group_description	= isset($_POST['group_description']) && is_string($_POST['group_description'])? htmlspecialchars( mb_substr(trim($_POST['group_description']) , 0, $C->POST_MAX_SYMBOLS) ) : '';
		$group_type			= isset($_POST['group_type']) && is_string($_POST['group_type'])? (trim($_POST['group_type'])=='private' ? 'private' : 'public') : '';
		$group_category		= isset($_POST['group_category'])? (int) $_POST['group_category'] : 0;
	}

	$tpl = new template( array('page_title' => $this->lang('newgroup_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	$menu = array( 	array('url' => 'groups/tab:all', 	'title' => $this->lang('userselector_tab_all') ),
					array('url' => 'groups/tab:my', 	'title' => $this->lang('group_tabs_my_groups') ),
	);
	
	$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu) );
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('newgroup_f_err'), isset($errtxt) ? $errtxt : $this->lang($errmsg) ) );
	}
	
	$table = new tableCreator();
	$table->form_enctype = 'enctype="multipart/form-data"';
	$rows = array();
	$rows[] = $table->inputField( $this->lang('group_settings_f_title'), 'group_name', $group_name); //title = name
	$rows[] = $table->inputField( $this->lang('group_settings_f_alias'), 'group_alias', $group_alias ); //name = alias = url
	
	// Pavel 3.6.1 skip None option
	if(count($sub_categories) > 1){
		$rows[] = $table->selectField($this->lang('group_settings_f_category'), 'group_category', $sub_categories);
	}
	
	if( isset($C->USERS_TO_CREATE_SUBCATEGORIES) && ($this->user->info->is_network_admin == 1 || $C->USERS_TO_CREATE_SUBCATEGORIES == 1) ){
		$rows[] = $table->textField( '', '<a href="'.$C->SITE_URL.'group/addcategory/return:1">'.$this->lang('newcat_create_new').'</a>' ); //name = alias = url
	}
	
	$rows[] = $table->textArea( $this->lang('group_settings_f_descr'), 'group_description', $group_description );
	$rows[] = $table->radioButton( $this->lang('group_settings_f_type'), 'group_type', array(	'private'=>$this->lang('group_settings_f_tp_private'), 
									 										'public'=>$this->lang('group_settings_f_tp_public')), $group_type );
	$rows[] = $table->fileField( $this->lang('group_settings_f_avatar'), 'form_avatar', '' );
	$rows[] = $table->submitButton( 'sbm', $this->lang('newgroup_f_btn') );

	
	$table->form_title = $this->lang('newgroup_title2');
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ) );
	
	
	$tpl->display();
?>