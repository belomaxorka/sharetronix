<?php
	$page = & $GLOBALS['page'];	
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	switch( $ajax_action ){
		
		case 'get': 
			
			
			break;
			
		case 'getall':
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) && !is_array($_POST['activities_id']) && !is_array($_POST['activities_type']) )
			{
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error');
					return;
				}
				$comments = $p->get_all_comments();
				
				$tpl = new template(array(), FALSE);
				
				foreach( $comments as $comment ){
					$tpl->initRoutine('SingleActivityComment', array( &$comment, TRUE ));
					$tpl->routine->load();
				}
				
				$tpl->display();

				return;
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error');
			return;
			
			break;
		case 'set':
			
			// A signed-out request (an expired session) has no user to comment as.
			if( ! $this->user->is_logged ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error');
				return;
			}
			
			if( isset($_POST['activities_id'], $_POST['activities_type'], $_POST['comments_text']) && !is_array($_POST['activities_id']) && !is_array($_POST['activities_type']) )
			{
				$msg	= isset($_POST['comments_text']) && is_string($_POST['comments_text']) ? trim($_POST['comments_text']) : '';
				if( empty($msg) ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error12');
					return;
				}
				$c	= new newpostcomment( new post($_POST['activities_type'], $_POST['activities_id']) ); 
				if( $c->error || !isset($c->post->post_id) || (int)$c->post->post_id < 1 ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error');
					return;
				}

				$c->set_message($msg);
				$post_id = $c->save();
				if( FALSE === $post_id && ($errmsg = $GLOBALS['plugins_manager']->getEventCallErrorMessage()) ) {
					echo 'ERROR:'.$errmsg; // a plugin refused
					return;
				}
				if( FALSE !== $post_id ) {
					
					$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $c->id );
					if( $c->error ) {
						echo 'ERROR:'.$page->lang('global_ajax_post_error11');
						return;
					}
					
					$tpl = new template(array(), FALSE);

					$tpl->initRoutine('SingleActivityComment', array( &$c, TRUE ));
					$tpl->routine->load();
					
					$tpl->display();
					
					return;
				}
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error');
			return;
			
			break;
	
		case 'delete':
			
			if( isset($_POST['activities_id'], $_POST['comments_id'], $_POST['activities_type']) && !is_array($_POST['activities_id']) && !is_array($_POST['comments_id']) && !is_array($_POST['activities_type']) )
			{
				$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $_POST['comments_id'] );
				if( $c->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error11');
					return;
				}
				if( ! $c->if_can_delete() ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error13');
					return;
				}
				$c->delete_this_comment();
				echo 'OK';
				return;
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error11');
			return;
			
			break;
			
		case 'like': 
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) && isset($_POST['activities_comment_id']) && !is_array($_POST['activities_id']) && !is_array($_POST['activities_type']) && !is_array($_POST['activities_comment_id']) ) {
				$comment_id = intval($_POST['activities_comment_id']);
			
				$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $_POST['activities_comment_id'] );
				if( $c->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}
			
				if( !$c->comment_could_be_liked() && !$c->is_comment_liked() ){
					echo 'ERROR';
					return;
				}
			
				if( $c->like_comment(TRUE) ){
					$likes = $c->post->get_post_likes();
					$likes_number = isset($likes['comment_'.$comment_id])? count($likes['comment_'.$comment_id]) : 0;
						
					$like_content = '<a href="" data-role="services" data-namespace="comments" data-action="unlike" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'","activities_comment_id":"'.$_POST['activities_comment_id'].'"}').'">Unlike</a>';
					if( $likes_number > 0 ){
						$showlikes_btn = '<a class="showpostlikes_btn" href="" data-role="services" data-namespace="comments" data-action="showlikes" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'","activities_comment_id":"'.$_POST['activities_comment_id'].'"}').'">';
						$like_content .= ' (You' . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like this )';
					}
						
					echo '<div class="like-list">'.$like_content.'</div>';
					return;
				}
			
			}
			
			echo 'ERROR:Invalid post.';
			break;
		
		case 'unlike': 
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) && isset($_POST['activities_comment_id']) && !is_array($_POST['activities_id']) && !is_array($_POST['activities_type']) && !is_array($_POST['activities_comment_id']) ) {
				$comment_id = intval($_POST['activities_comment_id']);
			
				$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $_POST['activities_comment_id'] );
				if( $c->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}
			
				if( !$c->comment_could_be_liked() && !$c->is_comment_liked() ){
					echo 'ERROR';
					return;
				}
			
				if( $c->like_comment(FALSE) ){
					$likes = $c->post->get_post_likes();
					$likes_number = isset($likes['comment_'.$comment_id])? count($likes['comment_'.$comment_id]) : 0;
						
					$like_content = '<a href="" data-role="services" data-namespace="comments" data-action="like" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'","activities_comment_id":"'.$_POST['activities_comment_id'].'"}').'">Like</a>';
					if( $likes_number>0 ){
						$showlikes_btn = '<a class="showpostlikes_btn" href="" data-role="services" data-namespace="comments" data-action="showlikes" data-value="'.htmlentities('{"activities_type":"'.$_POST['activities_type'].'","activities_id":"'.$_POST['activities_id'].'","activities_comment_id":"'.$_POST['activities_comment_id'].'"}').'">';
						$like_users = '';
			
						foreach ($likes['comment_'.$comment_id] as $usr) {
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
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) && isset($_POST['activities_comment_id']) && !is_array($_POST['activities_id']) && !is_array($_POST['activities_type']) && !is_array($_POST['activities_comment_id']) ) {
				$comment_id = intval($_POST['activities_comment_id']);
				
				if( !is_numeric($comment_id) || $comment_id < 1 ){
					echo 'ERROR:Invalid comment.';
					return;
				}
			
				$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $_POST['activities_comment_id'] );
				if( $c->error ) {
					echo 'ERROR:Invalid post data provided.';
					return;
				}
			
				global $C;

				$likes = $c->post->get_post_likes();
				$html = '';
				if( isset($likes['comment_'.$comment_id]) && count($likes['comment_'.$comment_id])>0 ){
					foreach( $likes['comment_'.$comment_id] as $v ){
						$html .= '<a href="'.userlink($v[0]).'"><img src="'.getAvatarUrl($v[1], 'thumbs3').'" /> '.$v[0].'</a><br />';
					}
				}
			
				echo $html;
				return;
			
			}
			
			echo 'ERROR:Invalid post.';
			return;
			break;
	}