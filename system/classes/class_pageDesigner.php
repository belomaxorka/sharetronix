<?php
	class pageDesigner
	{
		public function createMenu( $menu_class, & $menu_items, $placeholder='' )
		{
			/*
			* @param $menu_class - feed-navigation( left menu ), tabs-navigation (tab menu)
			* @param $menu_items - Array( array('url'=>'', 'css_class'=>'', 'title'=>'', 'tab_state'=>'') );
			* @param $placeholder - custom placeholder name for your menu	
			*/
		
			if( !is_array($menu_items) ){
				return '';
			}
		
			global $C;
			$page = & $GLOBALS['page'];
		
			$cur_address = implode('/', $page->request).'/'; //(;
		
			$html = '<ul class="'.$menu_class.'">';
		
			foreach( $menu_items as $item ){
				$html .= $this->createMenuLink($item);
			}
		
			$html .= !empty($placeholder)? '{%'.$placeholder.'%}' : '';
			$html .= '</ul>';
		
			return $html;
		}
		
		public function createMenuLink( $item )
		{
			global $C, $page;
			
			$cur_address 	= implode('/', $page->request).'/'; //(;
			//$tab_state 	= ( isset($item['tab_state']) && is_numeric($item['tab_state']) && ($address !== $cur_address) )? intval($item['tab_state']) : 0;
			$tab_state 		= ( isset($item['tab_state']) && is_numeric($item['tab_state']) && ($item['url'] !== $cur_address) )? intval($item['tab_state']) : 0;
			
			return '<li><a href="'. $C->SITE_URL . $item['url'] .'" class="'. (isset($item['css_class'])? $item['css_class'] : '' ) . (($item['url'] == $cur_address)? ' selected':'') .'"><span>'. $item['title'] .'</span>'. ($tab_state? '<span class="new-items-count"><span>'. $tab_state .'</span></span>' : '') .'</a></li>';
		}
		
		public function createNoPostBox( $title, $text )
		{
			return '<div class="noposts">
				<h3>'. $title .'</h3>
				<p>'. $text .'</p>
		</div>';
		}
		
		public function okMessage($title, $text, $closebtn=TRUE, $incss='')
		{
		
			$html	= '
				<div class="system-message success">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
			return $html;
		}
		
		public function errorMessage($title, $text, $closebtn=TRUE, $incss='')
		{
		
			$html	= '
				<div class="system-message error">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
			return $html;
		}
		
		public function informationMessage($title, $text, $closebtn=TRUE, $incss='')
		{
		
			$html	= '
				<div class="system-message">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
			return $html;
		}
		
		public function getMetaData()
		{
			$html = '';
		
			$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
			$html .= '<meta name="keywords" content="microblogging, sharetronix, blogtronix, enterprise microblogging">'."\n";
		
			return $html;
		}
		
		// The texts that the scripts (static/js) show, in the language of the page: the global stx_lang that
		// stxText() of common.js reads. Kept out of the cached list of scripts, which is the same for every
		// language.
		public function getJSLang()
		{
			global $C;
			$page = & $GLOBALS['page'];
			if( ! in_array('inside/footer.php', (array) $page->tmp_loaded_langfiles) ) {
				$page->load_langfile('inside/footer.php');
			}
			$texts = array();
			foreach( array('js_dialog_ok', 'js_dialog_yes', 'js_dialog_no', 'js_dialog_cancel', 'js_delete_post_confirm', 'js_delete_comment_confirm', 'js_delete_admin_confirm', 'js_post_edited', 'js_link_attached', 'js_group_invalid', 'js_group_btn', 'js_request_failed', 'js_message_empty', 'js_send_message') as $k ) {
				$texts[$k] = $page->lang($k);
			}
			// The texts of the plugins' scripts: the keys of languages/<code>/js.php of each loaded plugin, as
			// "<plugin>:<key>", which no core key and no other plugin can have. A text that is not UTF-8 is left
			// out, so that it cannot break the JSON of the other texts.
			if( isset($GLOBALS['plugins_manager']) && is_object($GLOBALS['plugins_manager']) ) {
				foreach( $GLOBALS['plugins_manager']->getInstalledPluginNames() as $p ) {
					if( is_file($C->PLUGINS_DIR.$p.'/languages/en/js.php') && $page->load_plugin_langfile($p, 'js.php') ) {
						foreach( $page->tmp_loaded_plugin_langfiles[$p]['js.php'] as $k ) {
							$t = $page->plugin_lang($p, $k);
							if( is_string($t) && mb_check_encoding($t, 'UTF-8') ) {
								$texts[$p.':'.$k] = $t;
							}
						}
					}
				}
			}
			return '<script type="text/javascript"> var stx_lang = '.json_encode($texts, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'; </script>'."\n";
		}
		
		public function loadJSData()
		{
			global $C, $page;

			$html = '<script type="text/javascript"> var siteurl = "'. $C->SITE_URL .'"; </script>'."\n";
			$system_files_location 	= $C->STATIC_URL.'js/';
		
			foreach( $this->system_files as $f ){
				$html .= '<script type="text/javascript" src="'.$system_files_location.$f.'.js?v='.$C->VERSION.'"></script>'."\n";
			}
		
			if( $C->THEME != 'default' && $page->is_mobile !== TRUE ){ // theme styles are desktop-only too (loadCSSData)
				if ($handle = @opendir($C->INCPATH.'../themes/'.$C->THEME.'/js/')) {
		
					while (FALSE !== ($entry = readdir($handle))) {
						if( substr($entry, 0, 1) !== '.' && substr($entry, -3) === '.js' && is_file($C->INCPATH.'../themes/'.$C->THEME.'/js/'.$entry) ){
							$html .= '<script type="text/javascript" src="'.$C->SITE_URL.'themes/'.$C->THEME.'/js/'. $entry .'?v='.$C->VERSION.'"></script>'."\n";
						}
					}
		
					closedir($handle);
				}
			}
			
			$html .= $this->_browsePluginsTemplateDir('js', array(
																	'<script type="text/javascript" src="'.$C->OUTSIDE_SITE_URL.'apps/', 
																	'"></script>'."\n"));
		
			return $html;
		}
		
		public function loadCSSData()
		{
			global $C, $page;
			
			$html = '';
			$system_files_location 	= $C->STATIC_URL.'css/';
					  
			
			if( !isset($C->THEME_CSS_OVERWRITE) ){
			    foreach( $this->system_files as $f ){
			     $html .= '<link href="'. $system_files_location . $f .'.css?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
				 
			    }
			   }
  			 else
			   {
			    foreach( $this->system_files as $f ){
			     if ($f !== 'framework'){
			     $html .= '<link href="'. $system_files_location . $f .'.css?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
				 
			     }
			    }
			   }
			
			
			
			if( $C->THEME !== 'default' && $page->is_mobile !==true ){
				if ($handle = @opendir($C->INCPATH.'../themes/'.$C->THEME.'/css/')) {
		
					while (FALSE !== ($entry = readdir($handle))) {
						if( substr($entry, 0, 1) !== '.' && substr($entry, -4) === '.css' && is_file($C->INCPATH.'../themes/'.$C->THEME.'/css/'.$entry) ){
							$html .= '<link href="'.  str_replace("/m/", "/",$C->SITE_URL) .'themes/'. $C->THEME .'/css/'. $entry .'?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
						}
					}
		
					closedir($handle);
				}
			}
				
			$html .= $this->_browsePluginsTemplateDir('css', array(
					'<link href="'. $C->OUTSIDE_SITE_URL .'apps/',
					'" type="text/css" rel="stylesheet" />'."\n"
			));
		
			return $html;
		}
		
		private function _browsePluginsTemplateDir( $file_type, $file_credentials)
		{
			global $C, $plugins_manager;
			
			$html = '';
			
			$installed_plugins = $plugins_manager->getInstalledPluginNames();
			foreach( $installed_plugins as $p ){
				if( is_dir( $C->PLUGINS_DIR.$p.'/static/'.$file_type.'/' ) ){
					if ($handle = @opendir($C->PLUGINS_DIR.$p.'/static/'.$file_type.'/')) {
			
						while (FALSE !== ($entry = readdir($handle))) {
							if( substr($entry, 0, 1) !== '.' && substr($entry, -strlen($file_type)-1) === '.'.$file_type && is_file($C->PLUGINS_DIR.$p.'/static/'.$file_type.'/'.$entry) ){
								$html .= $file_credentials[0]. $p . '/static/'.$file_type.'/' . $entry .'?v='.$C->VERSION. $file_credentials[1];
							}
						}
			
						closedir($handle);
					}
				}
			}
			
			return $html;
		}
		
		public function getFaviconData( $get_link = FALSE )
		{
			global $C;
		
			$html = '';
			$network = & $GLOBALS['network'];
		
			if( $C->HDR_SHOW_FAVICON == 1 ) {
				$html .= (!$get_link)? '<link href="'. $C->STATIC_URL .'images/favicon.ico" type="image/x-icon" rel="shortcut icon" />'."\n" : $C->STATIC_URL .'images/favicon.ico';
			} elseif( $C->HDR_SHOW_FAVICON == 2 ) {
				$html .= (!$get_link)? '<link href="'. $C->STORAGE_URL .'attachments/'. $network->id .'/'. $C->HDR_CUSTOM_FAVICON .'" type="image/x-icon" rel="shortcut icon" />'."\n" : $C->STORAGE_URL .'attachments/'. $network->id .'/'. $C->HDR_CUSTOM_FAVICON;
			}
		
			return $html;
		}
		
		public function loadNetworkLogo()
		{
			global $C;
			$network = & $GLOBALS['network'];
		
			$html = '<a href="'. $C->SITE_URL .'home" title="'. htmlspecialchars($C->SITE_TITLE) .'" class="system-logo">';
		
			if( $C->HDR_SHOW_LOGO==2 && !empty($C->HDR_CUSTOM_LOGO) ) {
				$html .= '<img src="'. $C->STORAGE_URL .'attachments/'. $network->id .'/'. $C->HDR_CUSTOM_LOGO .'" alt="'. htmlspecialchars($C->SITE_TITLE) .'" />';
			} else {
				$html .= '<img src="'. $C->STATIC_URL .'images/logo.png" alt="'. htmlspecialchars($C->SITE_TITLE) .'" />';
			}
		
			$html .= '</a>';
		
			return $html;
		}
		
		public function createInfoBlock( $title, $content, $margintop = false)
		{
			if( !is_string($content) || empty($content) ) return '';
			return '<div class="section-container '.($margintop? 'margin-top15' : '').'">'.((isset($title) && !empty($title)) ? '<h3 class="section-title">'. $title .'</h3>' : ''). $content .'</div>';
		}
		
		public function createUserLinks($user_array, $size = 'thumbs2')
		{
			global $C;
			$html = '';
		
			foreach( $user_array as $u ){
				$html .= '<a href="'. userlink($u['username']) .'" class="slimuser" title="'. htmlspecialchars($u['username']) .'"><img src="'. $C->STORAGE_URL .'avatars/'.$size.'/'. $u['avatar'] .'" alt=""/></a>';
			}
		
			return $html;
		}
		
		public function createTableDetailsBlock( $title, $data, $cssClass = '', $show_semicolon = TRUE )
		{
			$html = '';
		
			if( ! count( $data ) ){
				return $html;
			}
		
			$html .= '<div'. (($cssClass != '') ? ' class="'.$cssClass.'"' : "") .'><h3>'.$title.'</h3><ul>';
		
			foreach( $data as $k=>$v ){
				// the labels of the language packs mostly end with their own colon ("Join date:")
				$html .= '<li><em>'. htmlspecialchars($k) .($show_semicolon && substr(rtrim($k), -1) != ':' ? ':' : '').'</em> <strong>'. htmlspecialchars($v) .'</strong></li>';
			}
		
			$html .= '</ul></div>';
		
			return $html;
		}
		
	}