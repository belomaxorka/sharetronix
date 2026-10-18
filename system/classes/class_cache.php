<?php
	
	global $C;
	
	switch($C->CACHE_MECHANISM)
	{
		case 'memcached':
			class cache extends cache_memcached {}
			break;
			
		case 'redis':
			if( class_exists('Redis', FALSE) ) {
				class cache extends cache_redis {}
				break;
			}
			// Without the phpredis extension, use the file cache.
		
		default:
			class cache extends cache_filesystem {}
	}
	
?>