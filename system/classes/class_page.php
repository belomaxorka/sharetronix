<?php
	
	class page
	{
		public $network;
		public $user;
		public $cache;
		public $db1;
		public $db2;
		public $request;
		public $params;
		public $title;
		public $html;
		public $controllers;
		public $lang_data;
		public $lang_data_en;
		public $tpl_name;
		public $plugin_name;
		public $is_mobile;
		public $tmp_loaded_langfiles;
		public $plugin_lang_data;
		public $plugin_lang_data_en;
		public $tmp_loaded_plugin_langfiles;
		private $plugin_lang_other;
		private $plugin_langfile_errors;

		public function __construct()
		{
			$this->network	= & $GLOBALS['network'];
			$this->user		= & $GLOBALS['user'];
			$this->cache	= & $GLOBALS['cache'];
			$this->db1		= & $GLOBALS['db1'];
			$this->db2		= & $GLOBALS['db2'];
			$this->request	= array();
			$this->params	= new stdClass;
			$this->params->user	= FALSE;
			$this->params->group	= FALSE;
			$this->title	= NULL;
			$this->html		= NULL;
			$this->controllers	= $GLOBALS['C']->INCPATH.'controllers/';
			$this->lang_data		= array();
			$this->lang_data_en	= array();
			$this->plugin_lang_data	= array();
			$this->plugin_lang_data_en	= array();
			$this->tmp_loaded_plugin_langfiles	= array();
			$this->plugin_lang_other	= array();
			$this->plugin_langfile_errors	= array();
			$this->tpl_name	 	= 'default';
			$this->plugin_name 	= FALSE;
			$this->is_mobile	= FALSE;
		}
		
		public function LOAD()
		{
			$this->_parse_input();
			
			$this->_set_template();
			$this->_send_headers();
			$this->_load_controller();
		}
		
		private function _parse_input()
		{
			global $C;
			$this->params->user	= FALSE;
			$this->params->group	= FALSE;
			$request	= $_SERVER['REQUEST_URI']; 

			$pos		= strpos($request, '?');
			if( FALSE !== $pos ) {
				$request	= substr($request, 0, $pos);
			}
			if( FALSE !== strpos($request, '//') ) {
				$request	= preg_replace('/\/+/iu', '/', $request);
			}
			$tmp	= str_replace(array('http://','https://'), '', $C->SITE_URL);
			if( FALSE !== strpos($tmp, '//') ) {
				$tmp	= preg_replace('/\/+/iu', '/', $tmp);
			}
			$tmp	= substr($tmp, strpos($tmp, '/'));
			if( substr($request,0,strlen($tmp)) == $tmp ) {
				$request	= substr($request, strlen($tmp));
			}
			$is_mobile	= FALSE;
			$request	= '/'.ltrim($request);
			if( substr($_SERVER['HTTP_HOST'], 0, 2) == 'm.' ) {
				$is_mobile	= TRUE;
				$tmp	= substr($_SERVER['HTTP_HOST'], 2);
				$C->DOMAIN		= 'm.'.$C->DOMAIN;
				$C->SITE_URL	= preg_replace('/^(http(s)?\:\/\/)(.*)$/iu', '$1m.$3', $C->SITE_URL);
				$C->API_ID		= 1;
			}
			elseif( $request=='/m' || substr($request, 0, 3)=='/m/' ) {
				$is_mobile	= TRUE;
				$C->SITE_URL	.= 'm/';
				$C->API_ID		= 1;
			}elseif( isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge|maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|pixi|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/iu', $_SERVER['HTTP_USER_AGENT']) ) {
				$is_mobile	= TRUE;
				$C->SITE_URL	.= 'm/';
				$C->API_ID		= 1;
			}
			$this->is_mobile = $is_mobile;
			
			$is_plugin = FALSE; 
			if( substr($request, 0, 8) == '/plugin/' ){
				$is_plugin	= TRUE;
				if( !$this->user->is_logged ){
					$is_plugin = FALSE;
				}
			}

			if( $is_plugin ){
				$tmp 	= trim( $request, '/' );
				$tmp	= array_fill_keys( explode('/', $tmp), 1 );
				if( isset($tmp['m']) ){
					unset ($tmp['m']);
				}
				$tmp = array_keys($tmp);
				
				if( count($tmp) < 3 ){
					$this->redirect('home');
				}
			
				$plugin_name 	= $tmp[1];
				
				// Only installed (loaded) plugins answer; a copied or uninstalled directory does not.
				if( in_array($plugin_name, $GLOBALS['plugins_manager']->getInstalledPluginNames(), TRUE) && is_dir($C->PLUGINS_DIR.$plugin_name.'/system/controllers/') ){
				
					$this->controllers	= $C->PLUGINS_DIR.$plugin_name.'/system/controllers/';
				
					$request = str_replace('/plugin/'.$plugin_name.'/', '/', $request);
					$this->plugin_name = $plugin_name;
				}
				
			}else if( $is_mobile ) {
				$this->controllers	.= 'mobile/';
			}
			
			if( $_SERVER['HTTP_HOST']!=$C->DOMAIN && FALSE!==strpos($_SERVER['HTTP_HOST'], '.'.$C->DOMAIN) ) {
				$tmp	= str_replace('.'.$C->DOMAIN, '', $_SERVER['HTTP_HOST']);
				$tmp	= preg_replace('/^www\./', '', $tmp);
				$tmp	= trim($tmp);
				if( ! empty($tmp) ) {
					$request	= $tmp.'/'.$request;
				}
			}
			$request	= trim($request, '/');
			if( empty($request) ) {
				$this->request[]	= 'home';
				return;
			}
			if( substr($request, 0, 2) == 'm/' ) {
				$request	= substr($request, 2);
			} 
			$request	= explode('/', $request); 
			foreach($request as $i=>$one) {
				if( FALSE!==strpos($one,':') && preg_match('/^([a-z0-9\-_]+)\:(.*)$/iu',$one,$m) ) {
					$this->params->{$m[1]}	= $m[2];
					unset($request[$i]);
					continue;
				}
				if( ! preg_match('/^([a-z0-9\-\._]+)$/iu', $one) ) {
					unset($request[$i]);
					continue;
				}
			}
			$request	= array_values($request);
			
			if( 0 == count($request) ) {
				$this->request[]	= 'home';
				return;
			}
			if( $request[0] === '1' ) {
				$this->controllers	= $GLOBALS['C']->INCPATH.'controllers/api/';
				unset($request[0]);
				foreach($request as &$v) {
					if( preg_match('/\.(xml|json|rss|atom)$/iu', $v, $m) ) {
						$v	= str_ireplace('.'.$m[1], '', $v);
						$this->params->format	= strtolower($m[1]);
					}
				}
				$request		= array_values($request);
				foreach($request as $i=>$one) {
					$t	= $this->request;
					$t[]	= $one;
					if( file_exists( $this->controllers.implode('_', $t).'.php') ) {
						$this->request[]	= $one;
						unset($request[$i]);
						continue;
					}
					break;
				}
				$request		= array_values($request);
				if( 0 == count($this->request) ) {
					$this->request[]	= 'home';
				}
				$this->params->more	= $request;
				return;
			}
			$first	= $request[0];
			if( file_exists($this->controllers.$first.'.php') ) {
				$this->request[]	= $first;
			}
			elseif( $u = $this->network->get_user_by_username($first, FALSE, TRUE) ) {
				$this->request[]	= 'user';
				$this->params->user	= $u;
			}
			elseif( $g = $this->network->get_group_by_name($first, FALSE, TRUE) ) {
				$this->request[]	= 'group';
				$this->params->group	= $g;
			}
			else {
				$this->request[]	= 'home';
				return;
			}
			unset($request[0]);
			foreach($request as $one) {
				$t	= $this->request;
				$t[]	= $one;
				if( file_exists( $this->controllers.implode('_', $t).'.php') ) {
					$this->request[]	= $one;
					continue;
				}
				break;
			}
			if( ! $this->params->user ) {
				$this->params->user	= $this->user->is_logged ? $this->user->id : FALSE;
			}
			if( 0 == count($this->request) ) {
				$this->request[]	= 'home';
				return;
			}
		}
		
		private function _send_headers()
		{
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', FALSE);
			header('Pragma: no-cache');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s'). ' GMT');
			if( $this->request[0] == 'services' ) {
				header('Content-type: application/json; charset=utf-8');
			}elseif(isset($this->params->format)){
				switch($this->params->format){
					case 'xml':  header('Content-type: application/xml');
							 break;
					case 'json': header('Content-type: application/json');
							 break;
					case 'rss':  header('Content-type: application/rss+xml');
							 break;
					case 'atom': header('Content-type: application/atom+xml');
							 break;
					default: 	 header('Content-type: application/xml');
							 break;
				}	
			}
			else {
				header('Content-type: text/html; charset=utf-8');
			}
		}
		
		public function _set_template()
		{
			$theme_path = $GLOBALS['C']->INCPATH.'../themes/'.$GLOBALS['C']->THEME.'/';
			
			if( isset($GLOBALS['C']->THEME) && file_exists($theme_path . 'manifest.json') ) {
				$this->tpl_name		= $GLOBALS['C']->THEME;
			}

			$current_theme	= FALSE;
			$manifest = json_decode( @file_get_contents( $theme_path . 'manifest.json' ) );
			//@TODO: investigate why sometimes $manifest is not object
			if(is_object($manifest)){$manifest->theme_overwrite_css = strtoupper($manifest->theme_overwrite_css);
			}
			if( isset($manifest->theme_overwrite_css) && $manifest->theme_overwrite_css === "TRUE" ){
				$GLOBALS['C']->THEME_CSS_OVERWRITE = TRUE;
			}
			
			/*$GLOBALS['C']->LOGO_HEIGHT	= 0;
			if( $current_theme && isset($current_theme->logo_height) ) {
				$GLOBALS['C']->LOGO_HEIGHT	= intval($current_theme->logo_height);
			}*/
			
			$GLOBALS['C']->THEME	= $this->tpl_name;
			
			return $current_theme;
		}
		
		private function _load_controller()
		{
			global $C, $D, $plugins_manager;
			$D	= new stdClass;
			$D->page_title	= $C->SITE_TITLE;
			$db1		= & $this->db1;
			$db2		= & $this->db2;
			$db			= & $db2;
			$cache		= & $this->cache;
			$user		= & $this->user;
			$network	= & $this->network;
			
			require_once( $this->controllers.implode('_',$this->request).'.php' );
		}
		
		public function load_single_block($filename, $output_content=TRUE, $is_email = FALSE)
		{
			$filename = explode('.php', $filename); // to be removed after fixing all email reuqired filenames
			$filename = $filename[0];
			global $C, $D;
			if( $is_email == true ) {
				
				$filename = $GLOBALS['C']->INCPATH.'../static/templates/' . $filename. '.php';
			} else {
				$source = ( $this->is_mobile == false )  ? "system" : "mobile";
				$default_block	= $GLOBALS['C']->INCPATH.'../static/templates/'.$source.'/blocks/'.$filename.'.php';
				$theme_block	= $GLOBALS['C']->INCPATH.'../themes/'.$this->tpl_name.'/templates/'.$source.'/blocks/'.$filename.'.php';
				// Like layouts and HTML blocks, a theme without this block uses the default one.
				$filename	= ($this->tpl_name!='default' && file_exists($theme_block)) ? $theme_block : $default_block;
			}
			
			if( $output_content ) {
				require($filename);
				return TRUE;
			}
			else {
				ob_start();
				require($filename);
				$cnt	= ob_get_contents();
				ob_end_clean();
				return $cnt;
			}
		}
		
		public function load_langfile($filename)
		{
			if( ! isset($this->tmp_loaded_langfiles) ) {
				$this->tmp_loaded_langfiles	= array();
			}
			$this->tmp_loaded_langfiles[]	= $filename;
			global $C;
			$file	= $C->INCPATH.'languages/'.$C->LANGUAGE.'/'.$filename;
			// English is the reference pack: lang() gives the English text of a key that the active language
			// pack lacks, and a file that this pack lacks is not an error.
			if( $C->LANGUAGE != 'en' && file_exists($C->INCPATH.'languages/en/'.$filename) ) {
				$lang	= $this->_read_langfile($C->INCPATH.'languages/en/'.$filename);
				if( is_array($lang) ) {
					foreach($lang as $k=>$v) {
						$this->lang_data_en[$k]	= $v;
					}
				}
				if( ! file_exists($file) ) {
					return;
				}
			}
			$lang	= $this->_read_langfile($file);
			if( ! is_array($lang) ) {
				return FALSE;
			}
			foreach($lang as $k=>$v) {
				$this->lang_data[$k]	= $v;
			}
		}
		
		public function lang($key, $replace_strings=array(), $in_another_language=FALSE)
		{
			if( $in_another_language && $in_another_language!=$GLOBALS['C']->LANGUAGE && is_dir($GLOBALS['C']->INCPATH.'languages/'.$in_another_language) ) {
				return $this->lang_in_another_language($key, $replace_strings, $in_another_language);
			}
			if( empty($key) ) {
				return '';
			}
			if( isset($this->lang_data[$key]) ) {
				$txt	= $this->lang_data[$key];
			}
			elseif( isset($this->lang_data_en[$key]) ) {
				$txt	= $this->lang_data_en[$key];
			}
			else {
				return '';
			}
			if( 0 == count($replace_strings) ) {
				return $txt;
			}
			return str_replace(array_keys($replace_strings), array_values($replace_strings), $txt);
		}
		
		public function lang_in_another_language($key, $replace_strings=array(), $in_language=FALSE)
		{
			if( empty($key) ) {
				return '';
			}
			if( ! isset($this->tmp_loaded_langfiles) ) {
				return '';
			}
			$lang_data	= array();
			foreach($this->lang_data as $k=>$v) {
				$lang_data[$k]	= $v;
			}
			if( $in_language && is_dir($GLOBALS['C']->INCPATH.'languages/'.$in_language) ) {
				// English first, for the keys and files that the other language pack lacks
				foreach($this->lang_data_en as $k=>$v) {
					$lang_data[$k]	= $v;
				}
				foreach($this->tmp_loaded_langfiles as $f) {
					$file	= $GLOBALS['C']->INCPATH.'languages/'.$in_language.'/'.$f;
					if( ! file_exists($file) ) {
						continue;
					}
					$lang	= $this->_read_langfile($file);
					if( is_array($lang) ) {
						foreach($lang as $k=>$v) {
							$lang_data[$k]	= $v;
						}
					}
				}
			}
			if( ! isset($lang_data[$key]) ) {
				return '';
			}
			$txt	= $lang_data[$key];
			if( 0 == count($replace_strings) ) {
				return $txt;
			}
			return str_replace(array_keys($replace_strings), array_values($replace_strings), $txt);
		}
		
		// The $lang array of a language file, or FALSE when it sets no array; its output is dropped.
		private function _read_langfile($file)
		{
			global $C;
			$lang	= array();
			ob_start();
			require( $file );
			ob_end_clean();
			return is_array($lang) ? $lang : FALSE;
		}
		
		// The texts of a plugin: apps/<plugin>/languages/<code>/<filename>, which sets $lang like the files of
		// the language packs. English is the reference here as well, but the texts stay with the plugin: only
		// plugin_lang() reads them, so they neither clash with nor change the texts of the core and of other
		// plugins. Each file is read once per request. A missing English file, or a file that fails or sets no
		// $lang array, is written to the PHP error log instead of stopping the page, as for plugin code.
		public function load_plugin_langfile($plugin, $filename)
		{
			global $C;
			if( ! is_string($plugin) || ! preg_match('/^[a-z0-9_]+$/i', $plugin) || ! is_string($filename) || ! preg_match('/^[a-z0-9_\-]+(\/[a-z0-9_\-]+)*\.php$/i', $filename) ) {
				error_log('Sharetronix plugin language file not loaded: invalid name '.preg_replace('/\s+/', ' ', var_export($plugin, TRUE).', '.var_export($filename, TRUE)));
				return FALSE;
			}
			if( isset($this->tmp_loaded_plugin_langfiles[$plugin][$filename]) ) {
				return ! isset($this->plugin_langfile_errors[$plugin][$filename]);
			}
			$en	= $this->_read_plugin_langfile($plugin, 'en/'.$filename, TRUE);
			if( $C->LANGUAGE != 'en' ) {
				if( is_array($en) ) {
					foreach($en as $k=>$v) {
						$this->plugin_lang_data_en[$plugin][$k]	= $v;
					}
				}
				$lang	= $this->_read_plugin_langfile($plugin, $C->LANGUAGE.'/'.$filename, FALSE);
			}
			else {
				$lang	= $en;
			}
			if( is_array($lang) ) {
				foreach($lang as $k=>$v) {
					$this->plugin_lang_data[$plugin][$k]	= $v;
				}
			}
			$this->tmp_loaded_plugin_langfiles[$plugin][$filename]	= array_keys((is_array($en) ? $en : array()) + (is_array($lang) ? $lang : array()));
			unset($this->plugin_lang_other[$plugin]);
			if( ! is_array($en) ) {
				$this->plugin_langfile_errors[$plugin][$filename]	= TRUE;
				return FALSE;
			}
			return TRUE;
		}
		
		// A text of a plugin (see load_plugin_langfile()): the active language, then English, then ''. With
		// $in_another_language, an installed language pack such as that of the member an e-mail goes to, that
		// language comes first, as in lang().
		public function plugin_lang($plugin, $key, $replace_strings=array(), $in_another_language=FALSE)
		{
			global $C;
			if( empty($key) || ! is_scalar($key) || ! is_string($plugin) ) {
				return '';
			}
			if( $in_another_language && $in_another_language!=$C->LANGUAGE && is_string($in_another_language) && preg_match('/^[a-z0-9_\-]+$/i', $in_another_language) && is_dir($C->INCPATH.'languages/'.$in_another_language) ) {
				$texts	= $this->_plugin_lang_in_another_language($plugin, $in_another_language);
				if( ! isset($texts[$key]) ) {
					return '';
				}
				$txt	= $texts[$key];
			}
			elseif( isset($this->plugin_lang_data[$plugin][$key]) ) {
				$txt	= $this->plugin_lang_data[$plugin][$key];
			}
			elseif( isset($this->plugin_lang_data_en[$plugin][$key]) ) {
				$txt	= $this->plugin_lang_data_en[$plugin][$key];
			}
			else {
				return '';
			}
			if( ! is_array($replace_strings) || 0 == count($replace_strings) ) {
				return $txt;
			}
			return str_replace(array_keys($replace_strings), array_values($replace_strings), $txt);
		}
		
		// The texts of a plugin in another language: that language over English over the active one, from
		// the files loaded so far. Kept until the plugin loads another file.
		private function _plugin_lang_in_another_language($plugin, $language)
		{
			if( isset($this->plugin_lang_other[$plugin][$language]) ) {
				return $this->plugin_lang_other[$plugin][$language];
			}
			$texts	= isset($this->plugin_lang_data[$plugin]) ? $this->plugin_lang_data[$plugin] : array();
			if( isset($this->plugin_lang_data_en[$plugin]) ) {
				foreach($this->plugin_lang_data_en[$plugin] as $k=>$v) {
					$texts[$k]	= $v;
				}
			}
			if( $language != 'en' && isset($this->tmp_loaded_plugin_langfiles[$plugin]) ) {
				foreach(array_keys($this->tmp_loaded_plugin_langfiles[$plugin]) as $f) {
					$lang	= $this->_read_plugin_langfile($plugin, $language.'/'.$f, FALSE);
					if( is_array($lang) ) {
						foreach($lang as $k=>$v) {
							$texts[$k]	= $v;
						}
					}
				}
			}
			$this->plugin_lang_other[$plugin][$language]	= $texts;
			return $texts;
		}
		
		// The $lang array of apps/<plugin>/languages/<file>, or FALSE when the file is missing (logged if it
		// is $required), fails or sets no array. Its output is dropped.
		private function _read_plugin_langfile($plugin, $file, $required)
		{
			global $C;
			$path	= $C->PLUGINS_DIR.$plugin.'/languages/'.$file;
			if( ! is_file($path) ) {
				if( $required ) {
					error_log('Sharetronix plugin '.$plugin.': language file languages/'.$file.' not found; English is the reference language of a plugin');
				}
				return FALSE;
			}
			$level	= ob_get_level();
			ob_start();
			try {
				$lang	= $this->_require_plugin_langfile($path);
				$error	= is_array($lang) ? '' : 'sets no $lang array';
			} catch( Throwable $e ) {
				$lang	= FALSE;
				$error	= 'failed: '.get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine();
			}
			while( ob_get_level() > $level ) {
				ob_end_clean();
			}
			if( $error !== '' ) {
				error_log('Sharetronix plugin '.$plugin.': language file languages/'.$file.' '.$error);
				return FALSE;
			}
			return $lang;
		}
		
		// Runs a plugin's language file in a scope of its own, so that it cannot overwrite the reader's variables.
		private function _require_plugin_langfile($__file)
		{
			global $C;
			$lang	= array();
			require( $__file );
			return $lang;
		}
		
		public function param($key)
		{
			if( FALSE == isset($this->params->$key) ) {
				return FALSE;
			}
			$value	= $this->params->$key;
			
			/*if( is_numeric($value) ) {
				return floatval($value);
			}
			if( $value=="true" || $value=="TRUE" ) {
				return TRUE;
			}
			if( $value=="false" || $value=="FALSE" ) {
				return FALSE;
			}*/
			
			return $value;
		}
		
		public function redirect($loc, $abs=FALSE)
		{
			global $C;
			if( ! $abs && preg_match('/^http(s)?\:\/\//', $loc) ) {
				$abs	= TRUE;
			}
			if( ! $abs ) {
				if( $loc[0] != '/' ) {
					$loc	= $C->SITE_URL.$loc;
				}
			}
			if( ! headers_sent() ) {
				header('Location: '.$loc);
			}
			echo '<meta http-equiv="refresh" content="0;url='.$loc.'" />';
			echo '<script type="text/javascript"> self.location = "'.$loc.'"; </script>';
			exit;
		}
	}
	
?>