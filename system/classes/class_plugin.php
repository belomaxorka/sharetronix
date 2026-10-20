<?php
	class plugin
	{
		protected $plgn;
		protected $db2;
		protected $network;
		protected $page;
		protected $filter;
		protected $cache;
		protected $user;
		private $lang_plugin_name;
		
		public function __construct()
		{
			$this->plgn 	= & $GLOBALS['plugins_manager'];
			$this->db2		= & $GLOBALS['db2'];
			$this->network	= & $GLOBALS['network'];
			$this->page		= & $GLOBALS['page'];
			$this->cache	= & $GLOBALS['cache'];
			$this->user		= & $GLOBALS['user'];
			
			$this->filter 	= new postFilter();
		}
		
		final protected function setVar( $name, $value, $action = 'add', $priority = 0 )
		{
			$this->plgn->setVar( $name, $value, $action, $priority );
		}
		
		final protected function setDelimiter( $value )
		{
			$this->plgn->delimiter = $value;
		}
		
		final protected function getCurrentController()
		{
			return implode('/', $this->page->request);
		}
		
		final protected function getCurrentTab()
		{
			return $this->page->param('tab');
		}
		
		final protected function isMobileRegime()
		{
			$request 	= $_SERVER['REQUEST_URI'];
			$request	= '/'.rtrim($request, '/').'/'; 
			return (strpos($request, '/m/') !== FALSE) ? TRUE : FALSE;
		}
		
		// Loads apps/<name>/languages/<code>/<filename> of this plugin (see page::load_plugin_langfile()).
		// Load the files in the event handlers: the page does not exist yet while the plugins are constructed.
		final protected function loadLangfile( $filename )
		{
			if( !isset($GLOBALS['page']) || !is_object($GLOBALS['page']) ){
				return FALSE;
			}
			return $GLOBALS['page']->load_plugin_langfile( $this->getLangPluginName(), $filename );
		}
		
		// A text of this plugin's language files in the language of the page, or of $in_another_language
		// (e.g. that of the member an e-mail goes to); English when that language lacks it, otherwise ''.
		final protected function lang( $key, $replace_strings = array(), $in_another_language = FALSE )
		{
			if( !isset($GLOBALS['page']) || !is_object($GLOBALS['page']) ){
				return '';
			}
			return $GLOBALS['page']->plugin_lang( $this->getLangPluginName(), $key, $replace_strings, $in_another_language );
		}
		
		// The folder of this plugin in apps/: the class is named after it, but PHP class names ignore case.
		private function getLangPluginName()
		{
			if( !isset($this->lang_plugin_name) ){
				$this->lang_plugin_name = get_class($this);
				if( is_object($this->plgn) ){
					foreach( $this->plgn->getInstalledPluginNames() as $p ){
						if( strcasecmp($p, $this->lang_plugin_name) === 0 ){
							$this->lang_plugin_name = $p;
						}
					}
				}
			}
			return $this->lang_plugin_name;
		}

	}
?>