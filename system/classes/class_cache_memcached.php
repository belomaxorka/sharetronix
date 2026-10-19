<?php
	
	class cache_memcached
	{
		private $memcached_host;
		private $memcached_port;
		private $keys_prefix;
		private $mc;
		private $ext;
		private $debug_mode;
		private $debug_info;
		private $error_logged;
		
		public function __construct()
		{
			global $C;
			$this->memcached_host	= $C->CACHE_MEMCACHE_HOST;
			$this->memcached_port	= $C->CACHE_MEMCACHE_PORT;
			$this->keys_prefix	= $C->CACHE_KEYS_PREFIX.'|'.$C->VERSION.'|';
			$this->mc	= FALSE;
			$this->error_logged	= FALSE;
			$this->debug_mode	= $C->DEBUG_MODE==TRUE;
			$this->debug_info	= (object) array (
				'queries'	=> array(),
				'time'	=> 0,
			);
			$this->ext	= class_exists('Memcached',FALSE) ? 'memcached' : 'memcache';
		}
		
		private function connect()
		{
			$time	= microtime(TRUE);
			if( FALSE == $this->mc ) {
				$this->mc	= $this->ext=='memcached' ? new Memcached() : new Memcache();
				$this->mc->addServer( $this->memcached_host, intval($this->memcached_port) );
			}
			if($this->debug_mode) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'CONNECT',
					'key'		=> $this->memcached_host.':'.$this->memcached_port,
					'result'	=> $this->mc ? 'TRUE' : 'FALSE',
					'time'	=> number_format($time, 5, '.', ''),
				);
			}
			return $this->mc;
		}
		
		public function get($key)
		{
			if( FALSE == $this->mc ) {
				if( FALSE == $this->connect() ) {
					return FALSE;
				}
			}
			$key	= $this->keys_prefix.$key;
			if( $this->debug_mode ) {
				$time	= microtime(TRUE);
				$res	= $this->mc->get( $key );
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'GET',
					'key'		=> $key,
					'result'	=> FALSE===$res ? 'FALSE' : gettype($res),
					'time'	=> number_format($time, 5, '.', ''),
				);
				$this->check_result($res);
				return $res;
			}
			$res	= $this->mc->get( $key );
			$this->check_result($res);
			return $res;
		}
		
		public function set($key, $data, $expire)
		{
			if( FALSE == $this->mc ) {
				if( FALSE == $this->connect() ) {
					return FALSE;
				}
			}
			$key	= $this->keys_prefix.$key;
			if( $this->debug_mode ) {
				$time	= microtime(TRUE);
				$res	= $this->ext=='memcached' ? $this->mc->set( $key, $data, $expire ) : $this->mc->set( $key, $data, FALSE, $expire );
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'SET',
					'key'		=> $key,
					'result'	=> $res ? 'TRUE' : 'FALSE',
					'time'	=> number_format($time, 5, '.', ''),
				);
				$this->check_result($res);
				return $res;
			}
			$res	= $this->ext=='memcached' ? $this->mc->set( $key, $data, $expire ) : $this->mc->set( $key, $data, FALSE, $expire );
			$this->check_result($res);
			return $res;
		}
		
		public function del($key)
		{
			if( FALSE == $this->mc ) {
				if( FALSE == $this->connect() ) {
					return FALSE;
				}
			}
			$key	= $this->keys_prefix.$key;
			if( $this->debug_mode ) {
				$time	= microtime(TRUE);
				$res	= $this->mc->delete( $key, 0 );
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'DELETE',
					'key'		=> $key,
					'result'	=> $res ? 'TRUE' : 'FALSE',
					'time'	=> number_format($time, 5, '.', ''),
				);
				$this->check_result($res);
				return $res;
			}
			$res	= $this->mc->delete( $key, 0 );
			$this->check_result($res);
			return $res;
		}
		
		private function check_result($res)
		{
			// addServer() does not connect, so a failed call is the first sign of an unreachable server.
			// The legacy memcache extension reports such failures as PHP notices itself.
			if( FALSE !== $res || $this->error_logged || $this->ext != 'memcached' ) {
				return;
			}
			$code	= $this->mc->getResultCode();
			if( $code == Memcached::RES_SUCCESS || $code == Memcached::RES_NOTFOUND ) {
				return;
			}
			$this->error_logged	= TRUE;
			$message	= trim(preg_replace('/\s+/', ' ', $this->mc->getResultMessage()));
			error_log('Sharetronix cache: memcached '.$this->memcached_host.':'.$this->memcached_port.' failed ('.$message.'); this request runs without the cache.');
			if( $this->debug_mode ) {
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'SERVER',
					'key'		=> $this->memcached_host.':'.$this->memcached_port,
					'result'	=> 'FAILED: '.$message,
					'time'	=> number_format(0, 5, '.', ''),
				);
			}
		}
		
		public function get_debug_info()
		{
			$debug_info	= clone($this->debug_info);
			$debug_info->time	= number_format($debug_info->time, 4, '.', '');
			$debug_info->queries	= array_reverse($debug_info->queries);
			return $debug_info;
		}
		
		public function garbage_collector()
		{
		}
	}
	
?>