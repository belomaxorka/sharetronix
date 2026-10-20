<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	if( !isset($C->USERS_TO_CREATE_SUBCATEGORIES) ){
		$this->redirect('home');
	}
	if( isset($C->USERS_TO_CREATE_SUBCATEGORIES) && $C->USERS_TO_CREATE_SUBCATEGORIES != 1 && $this->user->info->is_network_admin == 0 ){
		$this->redirect('home');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/group.php');
	$this->load_langfile('inside/groups_new.php');

	$submit	= FALSE;
	$group_category = '';
	$group = new group();
	$group_sub_categories = $group->getGroupCategories();
	$sub_categories = array();
	
	foreach($group_sub_categories as $key => $value){
		$sub_categories[$value->url] = strtolower($value->title);
	}
	
	if( isset($_POST['sbm']) ) { 
		global $plugins_manager;
		
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errtxt = $plugins_manager->getEventCallErrorMessage();
		}
		
		$category_url = isset($_POST['group_category']) && is_string($_POST['group_category']) ? str_replace(' ', '', strtolower($_POST['group_category'])) : '';
		
		if(!isset($_POST['group_category']) || !is_string($_POST['group_category']) || empty($_POST['group_category'])){
			$errmsg = 'newcat_invalid_name';
		}else if(in_array(strtolower($_POST['group_category']), $sub_categories)){
			$errmsg = 'newcat_name_already_exists';
		}elseif( ! preg_match('/^[a-z0-9\-\_ ]{3,30}$/iu', $_POST['group_category']) ) {
			$errmsg	= 'newcat_invalid_name';
		}elseif(isset($sub_categories[$category_url])){
			$errmsg = 'newcat_name_already_exists';
		}
		
		$submit	= TRUE;
		
		$error = ( !empty($errmsg) || isset($errtxt) )? TRUE : FALSE;
		
		if( !$error ){
			$group->createGroupCategory($_POST['group_category']);
		}
		
		$group_category	= isset($_POST['group_category']) && is_string($_POST['group_category'])? htmlspecialchars( trim($_POST['group_category']) ) : '';
	}

	$tpl = new template( array('page_title' => $this->lang('newcat_page_title'), 'header_page_layout'=>'c') );
	
	$menu = array( 	array('url' => 'groups/tab:all', 	'title' => $this->lang('userselector_tab_all') ),
					array('url' => 'groups/tab:my', 	'title' => $this->lang('group_tabs_my_groups') ),
	);
	
	$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu) );
		
	$return = trim(urldecode($this->param('return')));
	if($return == '1'){
		$return = 'groups/new';
	}else{
		$return = $return.'/tab:settings/subtab:main';
	}
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('newgroup_f_err'), isset($errtxt) ? $errtxt : $this->lang($errmsg) ) );
	}elseif( $submit && !$error ){
		//$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('newcat_success_title'), $this->lang('newcat_success_text') ) );
		$this->redirect($C->SITE_URL.$return);
	}
	
	$tpl->layout->setVar( 'main_content', '<a href="'.$C->SITE_URL.$return.'" class="back-button-new-cat">'.$this->lang('newcat_back_btn').'</a>' );
	
	$table = new tableCreator();
	$rows = array();
	$rows[] = $table->inputField( $this->lang('group_settings_f_category'), 'group_category', $group_category);
	$rows[] = $table->textField('', $this->lang('newcat_info_field'));
	$rows[] = $table->submitButton( 'sbm', $this->lang('newcat_submit_btn') );

	$table->form_title = $this->lang('newcat_form_title');
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ) );
	
	
	$tpl->display();
?>