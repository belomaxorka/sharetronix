<?php

	$path	= $C->INCPATH.'../';
	$D->error = FALSE;
	$D->errmsg = '';
	$D->errtype = FALSE;
	$perms	= array();
	
	// Nothing to do for a database of this version or a newer one.
	if( version_compare($_SESSION['INSTALL_DATA']['OLD_VERSION'], $C->VERSION, '>=') ){
		$D->uptodate = TRUE;
		loadTemplate('update');
		return;
	}
	
	// Releases before 3.0 used another database layout and stored uploads elsewhere; this
	// updater cannot convert them.
	if( version_compare($_SESSION['INSTALL_DATA']['OLD_VERSION'], '3.0.0', '<') ){
		$D->error = TRUE;
		$D->errmsg = 'Sharetronix '.htmlspecialchars($_SESSION['INSTALL_DATA']['OLD_VERSION']).' cannot be updated with this release: the updater supports Sharetronix 3.0 and later.';
	}
	
	//check directories
	$D->files = get_directories_for_update( $_SESSION['INSTALL_DATA']['OLD_VERSION'] );
	if( !$D->error && $D->files === FALSE ){
		$D->error = TRUE;
		$D->errmsg = 'Could not find table describing the files needed to be updated.';
	}
	
	if( !$D->error ){
		@clearstatcache();
		foreach($D->files as $i=>$fl) {
			$curr_error	= FALSE;
			if( !is_readable($path.$fl) || !is_writable($path.$fl) ) {
				make_writable_for_owner($path.$fl);
				@clearstatcache();
				if( !is_readable($path.$fl) || !is_writable($path.$fl) ) {
					$D->error	= TRUE;
					$curr_error	= TRUE;
				}
			}
			if( ! $curr_error ) {
				unset($perms[$fl]);
				unset($D->files[$i]);
			}
		}
		if( $D->error ){
			$D->errtype = 1;
		}
	}
	
	//install database changes
	if( !$D->error ){
		$res	= update_database( $_SESSION['INSTALL_DATA']['OLD_VERSION'] );
		if( ! $res ) {
			$D->error	= TRUE;
			$D->errmsg	= '{Error occured while we tried to update your community database}';
			$D->errtype = 2;
		}
	}
	
	// Code that newer releases removed must not stay behind: a left-over cron job, for example,
	// would keep running against tables that the update dropped.
	$D->not_removed = array();
	if( !$D->error ){
		$D->not_removed = remove_obsolete_files( get_removed_files_for_update($_SESSION['INSTALL_DATA']['OLD_VERSION']) );
	}
	
	loadTemplate('update');