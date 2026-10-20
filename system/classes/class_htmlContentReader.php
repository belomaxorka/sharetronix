<?php

	class htmlContentReader
	{
		private $html;
		private $dom;
		private $url;
		private $meta_tags;
		// the character set that the HTTP answer names for the page, '' when it names none
		private $charset = '';
		
		public function __construct()
		{
			$this->html = '';
			$this->url = '';
			$this->dom = FALSE;
			$this->meta_tags = array('description'=>'', 'keywords'=>'', 'author'=>'');
		}
		
		public function setUrl($url)
		{
			if( ! preg_match('/^(ftp|http|https):\/\//', $url) ) {
				$url	= 'http://'.$url;
			}
			
			if( !is_valid_url($url) ){
				return FALSE;
			}
			
			$this->url = $url;
			
			return TRUE;
		}
		
		private function _initDom()
		{
			if($this->dom){
				return TRUE;
			}
			if( empty($this->html) ){
				return FALSE;
			}
			
			$this->dom = new DOMDocument();
			// libxml reads a page that names no character set in a <meta> tag as ISO-8859-1: it is told the
			// character set of the HTTP answer, or UTF-8 for text that is valid UTF-8.
			$html = $this->html;
			if( !preg_match('/<meta[^>]+charset/i', $html) ){
				$charset = $this->charset ? $this->charset : (mb_check_encoding($html, 'UTF-8') ? 'UTF-8' : '');
				if( $charset ){
					$meta = '<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">';
					$html = preg_replace('/<head(\s[^>]*)?>/i', '$0'.$meta, $html, 1, $found);
					if( ! $found ){
						$html = $meta.$html;
					}
				}
			}
			@$this->dom->loadHTML($html);
			
			return TRUE;
		}
		
		public function parseUrl()
		{
			if( empty($this->url) ){
				return FALSE;
			}
			if( function_exists('curl_init')  ){ 
		        $ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $this->url); 
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				// The answer of the link preview waits for this request: without limits cURL waited up to
				// 300 seconds for a host that did not answer, far beyond the time limit of the request.
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
				curl_setopt($ch, CURLOPT_TIMEOUT, 6);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
				$this->html = curl_exec($ch);
				if( preg_match('/charset=["\']?([\w\-]+)/i', (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE), $m) ) {
					$this->charset = $m[1];
				}
				if( PHP_VERSION_ID < 80000 ) {
					curl_close($ch);
				}
			}else{
				$this->html = file_get_contents($this->url, FALSE, stream_context_create(array('http' => array('timeout' => 6))));
			}
		}
		
		public function getTitle()
		{
			if( empty($this->html) ){
				return FALSE;
			}
			
			$this->_initDom();
			
			$title = $this->dom->getElementsByTagName("title");
			$title = $this->_strEncoder( $title->item(0)->nodeValue );
			if( is_null($title) || empty($title) ){
				$title = $this->url;
			}
			
			return $title;
		}
		
		public function parseMetaTags()
		{
			if( empty($this->url) ){
				return FALSE;
			}

			// The meta tags of the page that parseUrl() read; get_meta_tags($this->url) read the page a second
			// time, without a time limit. get_meta_tags() reads a file, so the page goes through a temporary one.
			global $C;
			$meta = array();
			if( !empty($this->html) ){
				$tmp = $C->STORAGE_TMP_DIR.uniqid('_meta_', true);
				if( @file_put_contents($tmp, $this->html) !== FALSE ){
					$meta = @get_meta_tags($tmp);
				}
				@unlink($tmp);
			}
			
			if(isset($meta['description']) && !empty($meta['description'])){
				$this->meta_tags['description'] = $this->_strEncoder(trim($meta['description']));
			}
			
			if(isset($meta['keywords']) && !empty($meta['keywords'])){
				$this->meta_tags['keywords'] = $this->_strEncoder(trim($meta['keywords']));
			}
			
			if(isset($meta['author']) && !empty($meta['author'])){
				$this->meta_tags['author'] = $this->_strEncoder(trim($meta['author']));
			}
		}
		
		private function _strEncoder( $str )
		{
			if( empty($str) ){
				return '';
			}
			
			$limit_char_limit = 100;
			
			$current_encoding = mb_detect_encoding($str, 'auto', true);
			$text = @iconv($current_encoding, 'UTF-8//TRANSLIT', $str);
			$text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
			$text = (strlen($text)>$limit_char_limit)? (mb_substr($text, 0, $limit_char_limit).'...') : $text;
			
			$text = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
					'|[\x00-\x7F][\x80-\xBF]+'.
					'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
					'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
					'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
					'?', $text );
			
			return htmlspecialchars( $text );
		}
		
		public function getMetaDescription()
		{
			if( !empty($this->meta_tags['description']) ){
				return $this->meta_tags['description'];
			}
			
			if( !empty($this->meta_tags['keywords']) ){
				return $this->meta_tags['keywords'];
			}
			
			if( !empty($this->meta_tags['author']) ){
				return $this->meta_tags['author'];
			}
			
			return '';
		}
		
		public function ifVideoLink($title, $description)
		{
			global $C;
				
			if( empty($this->url) ){
				return FALSE;
			}
			if( !isset($C->NEWPOST_EMBEDVIDEO_SOURCES) || !is_array($C->NEWPOST_EMBEDVIDEO_SOURCES) ){
				require_once($C->INCPATH.'conf_embed.php');
			}
			
			$data	= (object) array (
					'src_site'		=> '',
					'src_id'		=> '',
					'title'			=> !empty($title)? $title : $this->url,
					'description'	=> !empty($description)? $description : $this->url,
			);
			
			$S	= $C->NEWPOST_EMBEDVIDEO_SOURCES;
			foreach($S as $k=>$obj) {
				if( preg_match($obj->src_url_pattern, $this->url, $matches) ) {
					$data->src_id	= $matches[$obj->src_url_matchnum];
					$data->src_site	= $k;
					break;
				}
				elseif( preg_match($obj->src_emb_pattern, $this->url, $matches) ) {
					$data->src_id	= $matches[$obj->src_emb_matchnum];
					$data->src_site	= $k;
					break;
				}
			}
			if( empty($data->src_site) || empty($data->src_id) ) {
				return FALSE;
			}
			
			//if($this->if_youtube_widescreen($video)) $data->src_site .= '_widescreen';
			
			$data = $this->getVideoImage( $S, $data );
			
			return $data;
		}
		
		public function getVideoImage( & $S, & $video_data )
		{
			global $C;
			
			if( !function_exists('copy_attachment_videoimg') ){
				require_once($C->INCPATH.'helpers/func_images.php');
			}

			$data	= (object) array (
					'in_tmpdir'	=> TRUE,
					'src_site'		=> $video_data->src_site,
					'src_id'		=> $video_data->src_id,
					'title'			=> $video_data->title,
					'description'	=> $video_data->description,
					'file_thumbnail'	=> time().rand(100000,999999).'_thumb.gif',
					'embed_code'	=> '',
					'embed_w'		=> '',
					'embed_h'		=> '',
					'orig_url'		=> '',
					'hits'	=> 0,
			);
			
			$S	= $S[$data->src_site];
			$data->embed_w	= $S->embed_w;
			$data->embed_h	= $S->embed_h;
			$data->embed_code	= str_replace('###ID###', $data->src_id, $S->embed_code);
			$data->orig_url	= str_replace('###ID###', $data->src_id, $S->insite_url);
			
			if(isset($S->embed_thumb_orig)) {
				$file = str_replace("_thumb", "_origin", $data->file_thumbnail);
				$file = str_replace(".gif", ".jpg", $file);
				file_put_contents( $C->STORAGE_DIR . 'attachments/1/' . $file, 
						file_get_contents(
								str_replace('###ID###', $data->src_id, $S->embed_thumb_orig), FALSE, stream_context_create(array('http' => array('timeout' => 5)))
						)
				);
			}
			
			if( ! empty($S->embed_thumb) ) {
				$tmp	= str_replace('###ID###', $data->src_id, $S->embed_thumb);
				
				if( my_copy($tmp, $C->STORAGE_TMP_DIR.$data->file_thumbnail) ) {
					
					//@TODO: tova trqbva da e pri attach
					$res	= copy_attachment_videoimg($C->STORAGE_TMP_DIR.$data->file_thumbnail, $C->STORAGE_TMP_DIR.$data->file_thumbnail, $C->ATTACH_VIDEO_THUMBSIZE);
					if( ! $res ) {
						rm($C->STORAGE_TMP_DIR.$data->file_thumbnail);
					}
				}
			}else{
				$video_image = FALSE;
				
				switch( $data->src_site ){
					case 'vimeo':
						$tmp = explode('/', $data->orig_url);
						$video_id = end( $tmp );
							
						$tmp = @file_get_contents('http://vimeo.com/api/v2/video/'.$video_id.'.json', FALSE, stream_context_create(array('http' => array('timeout' => 5))));
						$json = json_decode( $tmp );
						if( isset($json[0]->thumbnail_medium) ){
							$video_image = $json[0]->thumbnail_medium;	
						}
				
						break;
					default:
						break;
				}
				
				if( $video_image ){
					if( my_copy($video_image, $C->STORAGE_TMP_DIR.$data->file_thumbnail) ) {
							
						//@TODO: tova trqbva da e pri attach
						$res	= copy_attachment_videoimg($C->STORAGE_TMP_DIR.$data->file_thumbnail, $C->STORAGE_TMP_DIR.$data->file_thumbnail, $C->ATTACH_VIDEO_THUMBSIZE);
						if( ! $res ) {
							rm($C->STORAGE_TMP_DIR.$data->file_thumbnail);
						}
					}
				} 
			}
			
			if( ! file_exists($C->STORAGE_TMP_DIR.$data->file_thumbnail) ) {
				$data->file_thumbnail	= '';
			}
			
			
			return $data;
		}
		
		/*
		public function if_youtube_widescreen($video_file)
		{
			if( function_exists('curl_init') && preg_match("/youtube/i", $video_file) ) 
			{
				$data = curl_init();
				curl_setopt($data, CURLOPT_URL, $video_file);
				curl_setopt($data, CURLOPT_RETURNTRANSFER, TRUE);
				$data_result = curl_exec($data);
				curl_close($data);
				
				if(isset($data_result) && !empty($data_result)){
					if(preg_match("/'IS_WIDESCREEN': true/i", $data_result)) return true;
				}
			}
			
			return false;
		}
		*/
	
		public function parseImages( $store = FALSE )
		{			
			if( !$this->_initDom() ){
				return FALSE;
			}
			
			global $C;
			
			if( !function_exists('url_to_absolute') ){
				require_once( $C->INCPATH.'helpers/func_url_to_absolute.php' );
			}
			
			//$this->dom->preserveWhiteSpace = false;
			
			$images = array();
			$tags = $this->dom->getElementsByTagName('img');
			
			foreach ($tags as $tag) {
				$images[] = url_to_absolute($this->url, $tag->getAttribute('src'));
			}
			
			if( $store ){
				
				return $this->storeTempImagesFromUrl($images);
			}else{
				
				return $images;
			}
		}
		
		public function storeTempImagesFromUrl($images)
		{
			global $C;
			
			$max_images = 4;
			$images_stored = array();
			// The answer of the link preview waits for these requests: each remote step gets 3 seconds
			// (default_socket_timeout is 60), and after 6 seconds no further image is tried.
			$socket_timeout = ini_set('default_socket_timeout', 3);
			$until = microtime(TRUE) + 6;
			
			foreach($images as $key => $image_source){
				if( microtime(TRUE) > $until ){
					break;
				}
				if(!is_valid_url($image_source)){
					continue;
				}
				if( !$this->ifResourceIsImage($image_source) ){
					continue;
				}
				
				$unique_name = uniqid('_image_', true);
				$tmp_image = $C->STORAGE_TMP_DIR.$unique_name;
				
				$fin = @fopen($image_source, "rb");
				if( ! $fin ){
					continue;
				}
				$fout = fopen($tmp_image, 'w');

				while (!feof($fin) && microtime(TRUE) <= $until) {
					$buffer= fread($fin, 32*1024);
					fwrite($fout,$buffer);
				}
				$complete = feof($fin);
				fclose($fin);
				fclose($fout);
				if( ! $complete ){
					@unlink($tmp_image);
					break;
				}
				
				$valid_image = validate_image($unique_name); 
				
				if( empty($valid_image) ){
					@unlink($tmp_image);
					continue;
				}
				
				$images_stored[] = $C->STORAGE_TMP_URL.$valid_image;
				
				if(count($images_stored) >= $max_images){
					break;
				}
			}
			if( $socket_timeout !== FALSE ){
				ini_set('default_socket_timeout', $socket_timeout);
			}
			
			return $images_stored;
			
		}
		
		public function ifResourceIsImage($url)
		{
			$res = @getimagesize($url);
			if(is_array($res)){
				return true;
			}
			
			return false;
			
// 			$params = array('http' => array(
// 					'method' => 'HEAD'
// 			));
// 			$ctx = stream_context_create($params);
// 			$fp = @fopen($url, 'rb', false, $ctx);
// 			if (!$fp)
// 				return false;  // Problem with url
		
// 			$meta = stream_get_meta_data($fp);
// 			if ($meta === false)
// 			{
// 				fclose($fp);
// 				return false;  // Problem reading data from url
// 			}
		
// 			$wrapper_data = $meta["wrapper_data"];
// 			if(is_array($wrapper_data)){
// 				foreach(array_keys($wrapper_data) as $hh){
// 					if (strpos($wrapper_data[$hh], 'Content-Type: image') !== false ) // strlen("Content-Type: image") == 19
// 					{
// 						fclose($fp);
// 						return true;
// 					}
// 				}
// 			}
		
// 			fclose($fp);
// 			return false;
		}
	}