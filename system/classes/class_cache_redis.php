<?php
	
	class cache_redis
	{
		private $redis_host;
		private $redis_port;
		private $redis_pass;
		private $redis_db;
		private $keys_prefix;
		private $rd;
		private $debug_mode;
		private $debug_info;
		
		public function __construct()
		{
			global $C;
			$this->redis_host	= !empty($C->CACHE_REDIS_HOST) ? $C->CACHE_REDIS_HOST : '127.0.0.1';
			$this->redis_port	= !empty($C->CACHE_REDIS_PORT) ? intval($C->CACHE_REDIS_PORT) : 6379;
			$this->redis_pass	= isset($C->CACHE_REDIS_PASS) ? strval($C->CACHE_REDIS_PASS) : '';
			$this->redis_db	= isset($C->CACHE_REDIS_DB) ? intval($C->CACHE_REDIS_DB) : 0;
			$this->keys_prefix	= $C->CACHE_KEYS_PREFIX.'|'.$C->VERSION.'|';
			$this->rd	= NULL;
			$this->debug_mode	= $C->DEBUG_MODE==TRUE;
			$this->debug_info	= (object) array (
				'queries'	=> array(),
				'time'	=> 0,
			);
		}
		
		private function connect()
		{
			// One attempt per request: an unreachable server must not delay every cache call.
			if( NULL !== $this->rd ) {
				return FALSE !== $this->rd;
			}
			$time	= microtime(TRUE);
			$this->rd	= FALSE;
			try {
				$rd	= new Redis();
				$res	= $rd->connect($this->redis_host, $this->redis_port, 1);
				if( $res && $this->redis_pass != '' ) {
					$res	= $rd->auth($this->redis_pass);
				}
				if( $res && $this->redis_db > 0 ) {
					$res	= $rd->select($this->redis_db);
				}
				if( $res ) {
					$this->rd	= $rd;
				}
			}
			catch( Exception $e ) {
				$this->rd	= FALSE;
			}
			if( $this->debug_mode ) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'CONNECT',
					'key'		=> $this->redis_host.':'.$this->redis_port,
					'result'	=> $this->rd ? 'TRUE' : 'FALSE',
					'time'	=> number_format($time, 5, '.', ''),
				);
			}
			return FALSE !== $this->rd;
		}
		
		private function call($action, $key, $data=NULL, $ttl=0)
		{
			if( ! $this->connect() ) {
				return FALSE;
			}
			$time	= microtime(TRUE);
			try {
				if( $action == 'GET' ) {
					$res	= $this->rd->get($key);
					$res	= FALSE===$res ? FALSE : unserialize($res);
				}
				elseif( $action == 'SET' ) {
					$res	= $ttl > 0 ? $this->rd->setex($key, $ttl, serialize($data)) : $this->rd->set($key, serialize($data));
				}
				else {
					$res	= $this->rd->del($key) > 0;
				}
			}
			catch( Exception $e ) {
				$this->rd	= FALSE;
				$res	= FALSE;
			}
			if( $this->debug_mode ) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> $action,
					'key'		=> $key,
					'result'	=> $action=='GET' ? (!$res ? 'FALSE' : gettype($res)) : ($res ? 'TRUE' : 'FALSE'),
					'time'	=> number_format($time, 5, '.', ''),
				);
			}
			return $res;
		}
		
		public function get($key)
		{
			return $this->call('GET', $this->keys_prefix.$key);
		}
		
		public function set($key, $data, $ttl)
		{
			return $this->call('SET', $this->keys_prefix.$key, $data, intval($ttl));
		}
		
		public function del($key)
		{
			return $this->call('DELETE', $this->keys_prefix.$key);
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