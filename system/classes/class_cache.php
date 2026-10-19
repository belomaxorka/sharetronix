<?php
	
	global $C;
	
	switch($C->CACHE_MECHANISM)
	{
		case 'memcached':
			if( class_exists('Memcached', FALSE) || class_exists('Memcache', FALSE) ) {
				class cache extends cache_memcached {}
			}
			else {
				// Without a memcached or memcache extension, use the file cache.
				class cache extends cache_filesystem {}
			}
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