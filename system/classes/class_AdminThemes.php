<?php
	
	class AdminThemes
	{
		protected $tpl;
		
		public function __construct( $tpl )
		{
			$this->tpl = $tpl;
		}
		
		public static function getInstalledThemes()
		{
			global $C;
			$names = array();
			$themes_path = $C->INCPATH.'../themes/';
			
			if ($handle = opendir($themes_path)) {		
				while (FALSE !== ($entry = readdir($handle))) {
					if( $entry !== '.' && $entry !== '..' && $entry !== '.htaccess' ){
						if( ! file_exists($themes_path.$entry.'/manifest.json') ) { 
							continue; 
						}
						
						$manifest_file = @file_get_contents( $themes_path.$entry.'/manifest.json' );
						$manifest_file = json_decode($manifest_file);
						if( !$manifest_file ){ 
							continue;
						}
						
						$names[$entry] = $manifest_file;
					}
				}
				closedir($handle);
			}
			
			return $names;
		}
		
		public static function getInstalledThemesNames()
		{
			$themes = AdminThemes::getInstalledThemes();
			
			return array_keys($themes);
		}
		
		public static function getLogoHeight( $theme )
		{
			// Header logo height of an installed theme (logo_height in its manifest), or 80, the
			// height .system-logo reserves in static/css/framework.css, when the theme sets none.
			$themes = AdminThemes::getInstalledThemes();
			if( isset($themes[$theme]->logo_height) && intval($themes[$theme]->logo_height) > 0 ) {
				return intval($themes[$theme]->logo_height);
			}
			return 80;
		}
		
	}