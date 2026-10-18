<?php
	
	global $C;
	
	$page = & $GLOBALS['page'];
	$user = & $GLOBALS['user'];
	$network = & $GLOBALS['network'];

	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	require $C->INCPATH.'helpers/func_additional.php';
	//require $C->INCPATH.'helpers/func_images.php';

	if( ! $user->is_logged ) {
		echo 'ERROR:'.$page->lang('global_ajax_post_error4');
		return;
	}

	$token = $page->param('token');

	if( !$token ){
		if( isset($_POST['token']) && !empty($_POST['token']) ){
			$token = trim($_POST['token']);
		}
	}
	
	if( !$token || ( is_string($token) && empty($token) ) ){
		echo 'ERROR:'.$page->lang('global_ajax_post_error4');
		return;
	}

	$sess = &$user->sess;
	$post_temp_id = trim( $token );
	
	if( ! isset($sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS']) ) {
		$sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS']	= array();
	}
	if( ! isset($sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$post_temp_id]) ) {
		$sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$post_temp_id]	= array();
	}
	$att	= & $sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$post_temp_id];

	switch( $ajax_action ){
		case 'setfile':
			
			if( !isset($_FILES['userfile']) ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error5');
				return;
			}
			
			$file	= (object) $_FILES['userfile'];
			
			if( ! is_uploaded_file($file->tmp_name) ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error6');
				return;
			}	
			
			$ext	= safe_attachment_extension($file->name);
			if( FALSE === $ext ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error5');
				return;
			}
			$tempfile	= time().rand(1000000,9999999).'.'.$ext;
			move_uploaded_file($file->tmp_name, $C->STORAGE_TMP_DIR.$tempfile);
			if( ! file_exists($C->STORAGE_TMP_DIR.$tempfile) ) {
				$data	= FALSE;
				return;
			}
			chmod($C->STORAGE_TMP_DIR.$tempfile, 0644);
			$data	= (object) array (
					'tempfile'	=> $tempfile,
					'filename'	=> $file->name,
					'filetype'	=> $file->type,
					'filesize'	=> filesize($C->STORAGE_TMP_DIR.$tempfile),
			);

			$file_type = detectUploadedFileType( $data->filetype );
			$data->detected_type = $file_type;
			
			$answer = array();
			if( $file_type === 'image' ){
				if( !function_exists('create_thumbnail_image') ){
					require $C->INCPATH.'helpers/func_images.php';
				}
				create_thumbnail_image($tempfile);
			}
			$attachment_id = 0;
			
			switch( $file_type ){
				case 'file':
				case 'acrobat':
				case 'word':
				case 'excell':
							if( !isset($att['file']) ) {
								$att['file'] = array();
							}
							$attachment_id = count($att['file']);
							$attachment_type = 'file';
							$attachment_url = $C->SITE_URL.'getfile/tmpid:'.$post_temp_id.'/attid:'.$attachment_id;
							
							$att['file'][] = $data;
							
							break;
				case 'image':
							if( !isset($att['image']) ) {
								$att['image'] = array();
							}
							$attachment_id = count($att['image']);
							$attachment_type = 'image';
							$attachment_url = $C->STORAGE_TMP_URL.'thumb_'.$tempfile;
							
							$att['image'][] = $data;

							break;
			}
			//
			$answer = array(	'token'=>$token,
					'file_name'=>$file->name,
					'file_type'=>$file_type,
					'att_type'=>$attachment_type,
					'att_id'=>$attachment_id,
					'url'=>$attachment_url,
			);
			//
			
			header('Content-type: text/plain; charset=utf-8');
			
			if( count($answer) ){
				echo json_encode($answer);
			}else{
				echo 'ERROR:'.$page->lang('global_ajax_post_error7');
			}
			
			break;
		
		case 'seturl':
			
			if( !isset($_POST['url']) || empty($_POST['url']) ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error8');
				return;
			}
			
			$link_type = 'link';
			$attach_data = trim($_POST['url']);
			
			$html = new htmlContentReader(); 
			$tmp = $html->setUrl($attach_data);
			if( !$tmp ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error8');
				return;
			}
			
			if( ! preg_match('/^(ftp|http|https):\/\//', $attach_data) ) {
				$attach_data	= 'http://'.$attach_data;
			}
			
			$html->parseUrl();
			$title = $html->getTitle(); 
			$html->parseMetaTags();
			$description = $html->getMetaDescription();
			
			$site_images = $html->parseImages(true);
			
			if( FALSE !== ($video_data = $html->ifVideoLink($title, $description)) ){
				$link_type = 'videoembed';
			}
			
			if( !isset($att[$link_type]) ) {
				$att[$link_type] = array();
			}

			$attachment_id = count($att[$link_type]);

			$answer = array(	'description'=>empty($description)? $attach_data : htmlspecialchars( $description ), 
								'title'=>empty($title)? $attach_data : $title,
								'Images' => $site_images,
								'token'=>$token,
								'type'=> ($link_type == 'link')? 'page' : $link_type,
								'att_type'=>$link_type, 
								'att_id'=>$attachment_id,
								'url'=>$attach_data,
								'video_image' => ($link_type == 'videoembed' && !empty($video_data->file_thumbnail))? $C->STORAGE_TMP_URL.$video_data->file_thumbnail : '',
					);

			$att[$link_type][] = ($link_type == 'link')? $answer : $video_data;
			
			unset($html);
			unset($att);
			
			echo json_encode($answer);	
			return;

			
			break;
		
		case 'setgroup':
				
			if( !isset($_POST['group_id']) || empty($_POST['group_id']) ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error8');
				return;
			}
				
			$link_type = 'group';
			$attach_data = trim($_POST['group_id']);
				
			if( !isset($att[$link_type]) ) {
				$att[$link_type] = array();
			}
		
			$att[$link_type] = $attach_data;
				var_dump($sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS']); exit;
			echo 'OK';
			return;
		
				
			break;
		
		case 'delete':
			if( !isset($_POST['attachment_id']) || !isset($_POST['attachment_type']) ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error9');
				return;
			}/*elseif( empty($_POST['attachment_type']) || empty($_POST['attachment_id']) ){
				echo 'ERROR:Invalid attachment type and ID values';
				return;
			}*/
			
			$att_id = intval($_POST['attachment_id']);
			$att_type = trim($_POST['attachment_type']);

			if( isset($att[$att_type][$att_id]) ){
				unset($att[$att_type][$att_id]);
				echo 'OK';
				return;
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error10');
			return;
			
			break;
			
		case 'setlinkimage':
			
			$sess = &$user->sess;
			
			if(!isset($_POST['link_index'], $_POST['image'])){
				return;
			}

			$index = (int) $_POST['link_index'];
			$image = trim($_POST['image']);

			if( !isset($sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$token]) ){
				return;
			}
			
			$att	= & $sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$token];
			
			if(isset($att['link'][$index])){
				$att['link'][$index]['image'] = $image;
			}
			
			echo 'OK';
			return;
			
			break;
			
	}