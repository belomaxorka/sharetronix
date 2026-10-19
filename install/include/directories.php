<?php
	
	function get_directories_for_install()
	{
		global $C;
		
		require_once $C->INCLUDEPATH.'descriptions/directories-install.php';
		
		if( !isset($folders) ){
			return FALSE;
		}
		
		return $folders;
	}
	
	function get_directories_for_update( $current_version )
	{
		global $C;
	
		require_once $C->INCLUDEPATH.'descriptions/directories-update.php';
	
		if( !isset($folders) ){
			return FALSE;
		}
		
		$tmp = array();
		
		foreach( $folders as $version => $dirs ){ 
			if( version_compare($version, $current_version, '>') ){
				foreach( $dirs as $dir ){
					$tmp[] = $dir;
				}
			}	
		}

		return $tmp;
	}
	
	function get_removed_files_for_update( $current_version )
	{
		global $C;
		
		require_once $C->INCLUDEPATH.'descriptions/files-removed.php';
		
		if( !isset($files_removed) ){
			return array();
		}
		
		$tmp = array();
		foreach( $files_removed as $version => $files ){
			if( version_compare($version, $current_version, '>') ){
				$tmp = array_merge($tmp, $files);
			}
		}
		
		return $tmp;
	}
	