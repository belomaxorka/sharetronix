<?php

	function loadSingleActivityComment( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$c = $params[0]; //the resource object from the DB
		$ajax = (isset($params[1]) && $params[1]===TRUE)? TRUE : FALSE;
		$newcomment = (isset($params[2]) && $params[2] == TRUE)? TRUE : FALSE;
		
		if( $ajax ){
			
			$tpl->layout->useBlock('activity-comment');

			$tpl->layout->block->setVar('activity_comment_user_avatar', '<a href="'.userlink($c->comment_user->username).'" class="avatar bizcard"  data-userid="'.$c->comment_user->id.'"><img src="'.getAvatarUrl($c->comment_user->avatar, 'thumbs3').'" alt="'.getThisUserCommunityName($c->comment_user).'" /></a>');
			$tpl->layout->block->setVar('activity_comment_user_username', '<a href="'.userlink($c->comment_user->username).'" class="author bizcard"  data-userid="'.$c->comment_user->id.'">'.getThisUserCommunityName($c->comment_user).'</a>');
			$tpl->layout->block->setVar('activity_comment_options', ($c->if_can_delete()? '<a href="" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'", "comments_id":"'.(isset($c->id)? $c->id : $c->comment_id).'"}').'" data-role="services" data-namespace="comments" data-action="deleteComment" class="delete">'.$page->lang('activity_comment_option_delete').'</a>' : '') );
			
			$tpl->layout->block->setVar('activity_comment_text', $c->parse_text() );
			$tpl->layout->block->setVar('activity_comment_date', post::parse_date($c->comment_date) );
			$tpl->layout->block->setVar('activity_comment_footer', '');
				
			if($user->is_logged && !$page->is_mobile){
				$is_liked  		= $c->is_comment_liked();
				$likes_number 	= !empty($c->post->post_likes['comment_'.$c->comment_id]) ? count($c->post->post_likes['comment_'.$c->comment_id]) : 0;
				$like_content	= '<a href="" data-role="services" data-namespace="comments" data-action="'.($is_liked? 'unlike' : 'like').'" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'","activities_comment_id":"'.$c->comment_id.'"}').'">'.($is_liked? 'Unlike' : 'Like').'</a>';
				
				if ($likes_number > 0) {
					$like_users = $is_liked? ' (You' : '';
					$showlikes_btn = '<a class="showpostlikes_btn" href="" data-role="services" data-namespace="comments" data-action="showlikes" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'","activities_comment_id":"'.$c->comment_id.'"}').'">';
						
					if( !$is_liked ){
						foreach ($c->post->post_likes['comment_'.$c->comment_id] as $usr) {
							if( $usr[0] != $user->info->username ){
								$like_users = ' (<a href="'.userlink( $usr[0] ).'">'.$usr[0].'</a>';
								break;
							}
						}
						$like_content .= $like_users . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like'.($likes_number==1? 's' : '').' this )';
					}else{
						$like_content .= $like_users . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like this )';
					}
						
				}
				
				$tpl->layout->block->setVar( 'activity_comment_footer', '<div class="like-list">'.$like_content.'</div>' );
			}
			
			$tpl->layout->block->save( 'main_content', true );
		}else{
	
			$pm->onPostCommentLoad( $c ); //@TODO: event on the ajax will not work
			
			$tpl->layout->useInnerBlock('activity-comment');
			
			$tpl->layout->inner_block->setVar('activity_comment_new', $newcomment? 'new' : '' );
			
			$tpl->layout->inner_block->setVar('activity_comment_user_avatar', '<a href="'.userlink($c->comment_user->username).'" class="avatar bizcard"  data-userid="'.$c->comment_user->id.'"><img src="'.getAvatarUrl($c->comment_user->avatar, 'thumbs3').'" alt="'.getThisUserCommunityName($c->comment_user).'" /></a>');
			$tpl->layout->inner_block->setVar('activity_comment_user_username', '<a href="'.userlink($c->comment_user->username).'" class="author bizcard"  data-userid="'.$c->comment_user->id.'">'.getThisUserCommunityName($c->comment_user).'</a>');
			$tpl->layout->inner_block->setVar('activity_comment_options', ($c->if_can_delete()? '<a href="" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'", "comments_id":"'.$c->comment_id.'"}').'" data-role="services" data-namespace="comments" data-action="deleteComment" class="delete">'.$page->lang('activity_comment_option_delete').'</a>' : '')); //delete comment
			
			$tpl->layout->inner_block->setVar('activity_comment_text', $c->parse_text() );
			$tpl->layout->inner_block->setVar('activity_comment_date', post::parse_date($c->comment_date) );
			$tpl->layout->inner_block->setVar('activity_comment_footer', '');
			
			if($user->is_logged && !$page->is_mobile){
				$is_liked  		= $c->is_comment_liked();
				$likes_number 	= isset($c->post->post_likes['comment_'.$c->comment_id])? count($c->post->post_likes['comment_'.$c->comment_id]) : 0;
				$like_content	= '<a href="" data-role="services" data-namespace="comments" data-action="'.($is_liked? 'unlike' : 'like').'" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'","activities_comment_id":"'.$c->comment_id.'"}').'">'.($is_liked? 'Unlike' : 'Like').'</a>';
			
				if ($likes_number > 0) {
					$like_users = $is_liked? ' (You' : '';
					$showlikes_btn = '<a class="showpostlikes_btn" href="" data-role="services" data-namespace="comments" data-action="showlikes" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'","activities_comment_id":"'.$c->comment_id.'"}').'">';
			
					if( !$is_liked ){
						foreach ($c->post->post_likes['comment_'.$c->comment_id] as $usr) {
							if( $usr[0] != $user->info->username ){
								$like_users = ' (<a href="'.userlink( $usr[0] ).'">'.$usr[0].'</a>';
								break;
							}
						}
						$like_content .= $like_users . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like'.($likes_number==1? 's' : '').' this )';
					}else{
						$like_content .= $like_users . (($likes_number>1)? ' and '.$showlikes_btn. ($likes_number-1).' other'.($likes_number-1>1? 's' : '').'</a>' : '') . ' like this )';
					}
			
				}
			
				$tpl->layout->inner_block->setVar( 'activity_comment_footer', '<div class="like-list">'.$like_content.'</div>' );

			}
				
			$tpl->layout->inner_block->saveInBlockPart( 'activity_comments', true );
			
		}
	}