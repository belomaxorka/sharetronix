<?php
	global $db2, $C;
	
	$page 		= & $GLOBALS['page'];
	$user 		= & $GLOBALS['user'];
	$network 	= & $GLOBALS['network'];
	$pm 		= & $GLOBALS['plugins_manager'];
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	switch( $ajax_action ){
		
		case 'get':
			
			break;
		
		case 'checknew':
			$activities_type 	= isset($_POST[ 'activities_type' ])? 	$_POST[ 'activities_type' ] 		: 		'';
			$activities_id 		= isset($_POST[ 'last_activity' ])? 	intval($_POST[ 'last_activity' ]) 	: 		0;
			$activities_group 	= isset($_POST[ 'activities_group' ])?	$_POST[ 'activities_group' ] 		: 		'';
			$activities_tab 	= isset($_POST[ 'activities_tab' ])?	$_POST[ 'activities_tab' ] 		: 		'';

			if( empty($activities_type) || empty($activities_id) ){
				echo 'ERROR';
				return;
			}
			
			$activity = activityFactory::select($activities_type);
			if( !$activity ){
				echo 'ERROR';
				return;
			}
			if( !empty($activities_group) ){
				$g = $network->get_group_by_id($activities_group);
				$activity->setGroup( $g );
			}
			if( !empty($activities_tab) ){
				$activity->tab = $activities_tab;
			}
			$activity->setNewetsPost( $activities_id );
				
			$new_activities = $activity->checkNewPosts();
			
			$new_activities_text = ' new activity';
			if($new_activities >1)$new_activities_text = ' new activities';
			$new_activities_tab = ( $activities_type === 'dashboard' )? $network->get_dashboard_tabstate($user->id, array('all', 'commented', '@me', 'private','notifications')) : array('all'=>0, '@me'=>0, 'commented'=>0, 'private'=>0, 'notifications'=>0);
			$answer = array(	'html'=>$new_activities. $new_activities_text, 
								'new_activities_dashboard'=>$new_activities, 
								'new_activities_tab_all'=>$new_activities_tab['all'], 
								'new_activities_tab_at'=>$new_activities_tab['@me'],
								'new_activities_tab_commented'=>$new_activities_tab['commented'],
								'new_messages'=>$new_activities_tab['private'],
								'new_notifications'=>$new_activities_tab['notifications']
			);
				
			echo json_encode($answer);
			
			break;
			
		case 'getnew': 
			$activities_type 	= isset($_POST[ 'activities_type' ])? 	$_POST[ 'activities_type' ] 		: 		'';
			$activities_id 		= isset($_POST[ 'last_activity' ])? 	intval($_POST[ 'last_activity' ]) 	: 		0;
			$activities_group 	= isset($_POST[ 'activities_group' ])?	$_POST[ 'activities_group' ] 		: 		'';
			$activities_tab 	= isset($_POST[ 'activities_tab' ])? 	$_POST[ 'activities_tab' ] 			: 		'';
			
			if( empty($activities_type) || empty($activities_id) ){
				echo 'ERROR';
				return;
			}
			
			$tpl = new template(array(), FALSE);

			$tpl->useStaticHTML();
			$tpl->staticHTML->useActivityContainer();
			
			$activity = activityFactory::select($activities_type);
			if( !$activity ){
				echo 'ERROR';
				return;
			}
			$activity->setTemplate( $tpl );
			if( !empty($activities_group) ){
				$g = $network->get_group_by_id($activities_group);
				$activity->setGroup( $g );
			}
			if( !empty($activities_tab) ){
				$activity->tab = $activities_tab;
			}
			
			$result = $activity->loadPosts($activities_id, TRUE);
			
			$answer = array('html'=>$tpl->display(true), 'first_activities_id'=>$result[0]);
			
			echo json_encode($answer);
			
			break;
			
		case 'getall':
			$activities_type 	= isset($_POST[ 'activities_type' ])? 	$_POST[ 'activities_type' ] 		: 		'';
			$activities_id 		= isset($_POST[ 'activities_id' ])? 	intval($_POST[ 'activities_id' ]) 	: 		0;
			$activities_tab 	= isset($_POST[ 'activities_tab' ])? 	$_POST[ 'activities_tab' ] 			: 		'';
			$activities_group 	= isset($_POST[ 'activities_group' ])?	$_POST[ 'activities_group' ] 		: 		'';
			$activities_user	= isset($_POST[ 'activities_user' ])? 	$_POST[ 'activities_user' ] 		: 		'';
			$activities_search	= isset($_POST[ 'activities_search' ])? $_POST[ 'activities_search' ] 		: 		'';

			if( !empty($activities_type) && $activities_id ){
				$tpl = new template(array(), FALSE);
				
				$tpl->useStaticHTML();
				$tpl->staticHTML->useActivityContainer();
				
				$activity = activityFactory::select($activities_type);
				if( !$activity ){
					echo 'ERROR';
					return;
				}
				$activity->setTemplate( $tpl );
				if( !empty($activities_group) ){
					$g = $network->get_group_by_id($activities_group);
					$activity->setGroup( $g );
				}
				if( !empty($activities_user) ){
					$u = $network->get_user_by_id($activities_user);
					$activity->setUser( $u );
				}
				
				if( !empty($activities_tab) ){
					$activity->tab = $activities_tab;
				}
				
				if( !empty($activities_search) ){
					$activity->search_string = $activities_search;
				}
				
				$result = $activity->loadPosts($activities_id); 
				
				$answer = array('html'=>$tpl->display(true), 'last_activities_id'=>$result[1]);
				
				echo json_encode($answer);
			}
				
			break;
		case 'set':
			
			// A signed-out request (an expired session) has no user to post as.
			if( ! $user->is_logged ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error');
				return;
			}
			
			$activity_text 	= isset($_POST[ 'activities_text' ])? $_POST[ 'activities_text' ] : '';
			$activities_token 	= isset($_POST[ 'token' ])? $_POST[ 'token' ] : '';
			
			if( !empty($activity_text) && !empty($activities_token) ){
				
				//$p = new newpost();
				
				//
				$sess = &$user->sess;
				if( ! isset($sess['TEMP_ACTIVITY_POSTS']) ) {
					$sess['TEMP_ACTIVITY_POSTS']	= array();
				}
				if( ! isset($sess['TEMP_ACTIVITY_POSTS'][$activities_token]) ) {
					$sess['TEMP_ACTIVITY_POSTS'][$activities_token]	= new newpost();
				}
				$p	= & $sess['TEMP_ACTIVITY_POSTS'][$activities_token];
				//
				if( isset($sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$activities_token]) ){
					$att	= & $sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$activities_token];
				}
				
				if( !empty($C->POST_MAX_SYMBOLS) ){ 
					$activity_text = mb_substr($activity_text, 0, $C->POST_MAX_SYMBOLS); 
				}
				
				if( isset($_POST[ 'activities_type' ]) && $_POST[ 'activities_type' ] == 'profile' ){
					if(isset($_POST[ 'activities_username' ]) && strpos($activity_text, '@'.$_POST[ 'activities_username' ]) === false ){
						$activity_text = '@'.$_POST[ 'activities_username' ].' '.$activity_text;
					}
				}
								
				$p->set_message($activity_text);

				if( isset($_POST[ 'activities_group' ]) && !empty($_POST[ 'activities_group' ]) ){
					$p->set_group_id( intval( $_POST[ 'activities_group' ] ) );
				}
				elseif(isset($att['group']) && !empty($att['group'])){
					$p->set_group_id( intval( $att['group'] ) );
				}
				
				if( isset($att['image']) ){ 
					foreach($att['image'] as $img){
						if( $ii = $p->attach_image($C->STORAGE_TMP_DIR.$img->tempfile, $img->filename) ) {
							rm($C->STORAGE_TMP_DIR.$img->tempfile);
						}
					}
					unset($att['image']);
				}
				
				if( isset($att['file']) ){
					foreach($att['file'] as $file){
						if( $ff = $p->attach_file($C->STORAGE_TMP_DIR.$file->tempfile, $file->filename, $file->detected_type) ) {
							rm($C->STORAGE_TMP_DIR.$file->tempfile);
						}
					}
					unset($att['file']);
				}
				
				if( isset($att['link']) ){
					foreach($att['link'] as $link){
						$p->attach_link($link);
					}
					unset($att['link']);
				}
				
				if( isset($att['videoembed']) ){
					foreach($att['videoembed'] as $vid){
						$p->attach_videoembed($vid);
					}
					unset($att['videoembed']);
				}
				
				$res	= $p->save();

				$p->remove_post_cache();
				
				if( $res ){
					// The next post has a new token. The stored object holds the connection objects and the
					// settings, the database password included, and stayed in the session for every post.
					unset($sess['TEMP_ACTIVITY_POSTS'][$activities_token], $sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$activities_token]);
					$activity_id = explode('_', $res);
					$activity_type = $activity_id[1]; //delete private post
					$activity_id = intval($activity_id[0]);
				
					$obj = $db2->query( 'SELECT * FROM '.( $activity_type=='private'? 'posts_pr' : 'posts' ).' WHERE id="'. $activity_id .'" LIMIT 1' );
					$obj = $db2->fetch_object($obj);
					$obj->type = $activity_type=='private'? 'private' : 'public';
					
					$tpl = new template(array(), FALSE);
					
					$tpl->useStaticHTML();
					$tpl->staticHTML->useActivityContainer( 'main_content', FALSE );
					
					$tpl->initRoutine('SingleActivity', array( &$obj, FALSE ));
					$tpl->routine->load();
					
					$answer = array('html'=>$tpl->display(true), 'inserted_activities_id'=>$activity_id);
					
					echo json_encode($answer);
					return;
				}
				
				if( $errmsg = $pm->getEventCallErrorMessage() ){
					echo 'ERROR:' . $errmsg;
					return;
				}
				
				echo 'ERROR:'.$page->lang('global_ajax_post_error');
				return;
			}
			
			break;
			
		case 'delete':	
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) )
			{
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error1');
					return;
				}
				if( $p->delete_this_post() ) {
					echo 'OK';
					return;
				}
				
				if( $errmsg = $pm->getEventCallErrorMessage() ){
					echo 'ERROR:' . $errmsg;
					return;
				}
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error');
			return;
			
			break;
		
		case 'bookmark':
				
				if( isset($_POST['activities_id']) && isset($_POST['activities_type']) )
				{
					$p	= new post($_POST['activities_type'], $_POST['activities_id']);
					if( $p->error ) {
						echo 'ERROR:'.$page->lang('global_ajax_post_error1');
						return;
					}
					
					$type = $p->is_post_faved()? FALSE : TRUE;
					
					if( $p->fave_post($type) ) {
						echo 'OK';
						return;
					}
				}
					
				echo 'ERROR:'.$page->lang('global_ajax_post_error');
				return;
					
				break;
				
		case 'like': 
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) ) {
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}

				if( !$p->could_be_liked() && !$p->is_post_liked() ){
					echo 'ERROR';
					return;
				}
			
				if( $p->like_post(TRUE) ){
					$likes = $p->get_post_likes();
					$likes_number = isset($likes['post'])? count($likes['post']) : 0;
						
					$like_content = '<a href="" data-role="services" data-namespace="activities" data-action="unlike" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'"}').'">Unlike</a>';
					if( $likes_number > 0 ){
						$showlikes_btn = '<a class="showpostlikes_btn" href="" data-role="services" data-namespace="activities" data-action="showlikes" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'"}').'">';
						$like_content .= ' (You' . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like this )';
					}
						
					echo '<div class="like-list">'.$like_content.'</div>';
					return;
				}
			
			}
			
			echo 'ERROR:Invalid post.';
			
			break;
			
		case 'unlike': 
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) ) {
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}
			
				if( !$p->could_be_liked() && !$p->is_post_liked() ){
					echo 'ERROR';
					return;
				}
			
				if( $p->like_post(FALSE) ){
					$likes = $p->get_post_likes();
					$likes_number = isset($likes['post'])? count($likes['post']) : 0;
					$like_users = '';
					$like_content = '<a href="" data-role="services" data-namespace="activities" data-action="like" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'"}').'">Like</a>';
					if( $likes_number>0 ){
						$showlikes_btn = '<a class="showpostlikes_btn" href="" data-role="services" data-namespace="activities" data-action="showlikes" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'"}').'">';
							
						foreach ($likes['post'] as $usr) {
							if( $usr[0] != $this->user->info->username ){
								$like_users = ' (<a href="'.userlink( $usr[0] ).'">'.$usr[0].'</a>';
								break;
							}
						}
						$like_content .= $like_users . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like'.($likes_number==1? 's' : '').' this )';
			
					}
			
					echo '<div class="like-list">'.$like_content.'</div>';
			
					return;
				}
			
			}
			
			echo 'ERROR:Invalid post.';
			break;
			
		case 'showlikes': 
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) ) {
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}
			
				global $C;

				$likes = $p->get_post_likes();
				$html = '';
				if( isset($likes['post']) && count($likes['post'])>0 ){
					foreach( $likes['post'] as $v ){
						$html .= '<a href="'.userlink($v[0]).'"><img src="'.getAvatarUrl($v[1], 'thumbs3').'" /> '.$v[0].'</a><br />';
					}
				}
			
				echo $html;
				return;
			
			}
			
			echo 'ERROR:Invalid post.';
			
			break;
			
		case 'edit': 
			
			if( isset($_POST['activities_id'], $_POST['activities_type'], $_POST['message']) ) {
				if( empty($_POST['message']) ){
					echo 'ERROR:Empty message.';
					return;
				}
			
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}
			
				if( !$p->if_can_edit() ){
					echo 'ERROR: You could not edit this post.';
					return;
				}
			
				$message = strip_tags($_POST['message']);
				
				if( !empty($C->POST_MAX_SYMBOLS) ){ 
					$message = mb_substr($message, 0, $C->POST_MAX_SYMBOLS); 
				}
			
				if( $p->edit($message) ){
					echo 'OK: Post edited';
					return;
				}
			
			}
			
			echo 'ERROR:Invalid post.';
			break;
	}