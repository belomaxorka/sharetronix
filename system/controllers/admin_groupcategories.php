<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	$db2->query('SELECT 1 FROM users WHERE id="'.$this->user->id.'" AND is_network_admin=1 LIMIT 1');
	if( 0 == $db2->num_rows() ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/admin.php');
	
	global $plugins_manager;
	$group = new group();
	$users_can_create_sub_categories = (isset($C->USERS_TO_CREATE_SUBCATEGORIES) && $C->USERS_TO_CREATE_SUBCATEGORIES == 1)? 1 : 0;
	$group_sub_categories = $group->getGroupCategories(false, false);
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$deluser	= '';
	if( isset($_POST['sbm_categories']) ) {
		$submit	= TRUE;
		$users_can_create_sub_categories	= (isset($_POST['able_to_create_categories']) && (int) $_POST['able_to_create_categories'] == 1)? 1 : 0;
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		$db2->query('REPLACE INTO settings SET word="USERS_TO_CREATE_SUBCATEGORIES", value="'.(int) $users_can_create_sub_categories.'" ');
		$this->network->load_network_settings();
		
		$locked_sub_categories = array();
		$admin_sub_categories = array();
		foreach($group_sub_categories as $key => $value){
			if(isset($_POST['is_locked_'.$value->id])){
				$locked_sub_categories[] = (int) $value->id;
			}
			if(isset($_POST['is_admin_'.$value->id])){
				$admin_sub_categories[] = (int) $value->id;
			}
		}
		$db2->query('UPDATE groups_categories SET is_admin_created = 0, is_locked = 0');
		
		if( count($locked_sub_categories) > 0 ){
			$db2->query('UPDATE groups_categories SET is_locked = 1 WHERE id IN('.implode(',', $locked_sub_categories).')');
		}
		
		if( count($admin_sub_categories) > 0 ){
			$db2->query('UPDATE groups_categories SET is_admin_created = 1 WHERE id IN('.implode(',', $admin_sub_categories).')');
		}
		
		//refresh
		$group_sub_categories = $group->getGroupCategories(false, false);
	}
	
	if( $this->param('delete') ){
		
		$delete_category_id = (int) $this->param('delete');
		
		$db2->query('DELETE FROM groups_categories WHERE id='.$delete_category_id.' LIMIT 1');
		$db2->query('UPDATE `groups` SET category_id = 0 WHERE category_id='.$delete_category_id);
		
		$this->redirect($C->SITE_URL.'/admin/groupcategories/msg:saved');
	}
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_deleteuser', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admdelu_ok'), $this->lang('admin_grcat_saved_changes') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admbrnd_frm_err'), $errmsg) );
	}
	
	if( $this->param('msg') == 'saved' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admdelu_ok'), $this->lang('admin_grcat_deleted') ) );
	}
	
	//second table
	$table = new tableCreator();
	$table->form_title = $this->lang('admmenu_group_categories');
	//$table->form_title = $this->lang('admin_grcat_sub_cat_ttl')."<span class='catsctrl'> ".$table->checkBoxSpecial( $this->lang('admin_grcat_sub_cat_txt'), array( array('able_to_create_categories', 1, '', $users_can_create_sub_categories ) ) )."</span>";
	$rows = array("<div style=\"margin:10px 0 20px;font-weight:bold;display:flex;float:right;\"><span class='catsctrl'> ".$table->checkBoxSpecial( $this->lang('admin_grcat_sub_cat_txt'), array( array('able_to_create_categories', 1, '', $users_can_create_sub_categories ) ) )."</span></div>");
	
	if( count($group_sub_categories) > 0 ){
		$rows[] = "<tr class='catstitles'><td>".$this->lang('admin_grcat_table_title')."</td>
		<td>".$this->lang('admin_grcat_table_locked')."</td>
		<td>".$this->lang('admin_grcat_table_admin')."</td>
		<td>Control</td>
		</tr>";
		foreach($group_sub_categories as $key => $value){
			$rows[] = $table->textFieldSpecial(
				$value->title,
				$table->checkBoxSpecial( "", array( array('is_locked_'.$value->id, $value->id, '', ($value->is_locked == 1? $value->id : 0) ) ) ),
				$table->checkBoxSpecial( "", array( array('is_admin_'.$value->id, $value->id, '', ($value->is_admin_created == 1? $value->id : 0) ) ) ),
				'<a class="deletebutton" href="'.$C->SITE_URL.'admin/groupcategories/delete:'.$value->id.'">Delete</a>' 
				,'admincats'
				);
		}
	}
	//$rows[] = '<tr><td colspan="4" >'.$table->submitButton( 'sbm_categories', $this->lang('admin_appstore_submit') ) . '</td></tr>';
	$rows[] = '<tr><td style="padding:10px 0; text-align:right;" colspan="4" ><button type="submit" style="margin:0;" name="sbm_categories" class="btn blue"><span>'.$this->lang('admin_appstore_submit').'</span></button></td></tr>';
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	if( count($group_sub_categories) == 0 ){
		$tpl->layout->setVar('main_content', $tpl->designer->informationMessage($this->lang('admin_grcat_no_cats_ttl'), $this->lang('admin_grcat_no_cats_txt')) );
	}
	
	$tpl->display();
	
?>