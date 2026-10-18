<?php

	invalidateCachedHTML();
	
	if( is_dir($C->TMP_DIR) ){
		if ($handle = opendir( $C->TMP_DIR )) {
			while (FALSE !== ($entry = readdir($handle))) {
				if( $entry !== '.gitkeep' && is_file($C->TMP_DIR.$entry) ){
					unlink ( $C->TMP_DIR.$entry );
				}
			}
			closedir($handle);
		}
	}