<?php
	
	class cache_filesystem
	{
		private $path;
		private $keys_prefix;
		private $debug_mode;
		private $debug_info;
		private $error_logged;
		
		public function __construct()
		{
			global $C;
			$this->path	= $C->CACHE_FILESYSTEM_PATH;
			$this->keys_prefix	= $C->CACHE_KEYS_PREFIX.'|'.$C->VERSION.'|';
			$this->debug_mode	= $C->DEBUG_MODE==TRUE;
			$this->debug_info	= (object) array (
				'queries'	=> array(),
				'time'	=> 0,
			);
			$this->error_logged	= FALSE;
		}
		
		private function find_filename($key)
		{
			return $this->path.'/'.md5($this->keys_prefix).'-'.md5($key);
		}
		
		private function remove_file($file)
		{
			// Deleting needs write access to the folder, not to the file, which may belong to another
			// user: the command-line cron worker and the web server can run as different users.
			if( file_exists($file) ) {
				@unlink($file);
				clearstatcache(TRUE, $file);
			}
			return !file_exists($file);
		}
		
		public function get($key)
		{
			$file	= $this->find_filename($key);
			$time	= microtime(TRUE);
			$res	= FALSE;
			// Another request can replace or delete the entry at any moment; a vanished file is a miss.
			$data	= is_readable($file) ? @file_get_contents($file) : FALSE;
			if( FALSE !== $data ) {
				// The expiry time ends at the first line break; the serialized value may contain more.
				$pos	= strpos($data, "\n");
				if( FALSE !== $pos ) {
					if( intval(substr($data, 0, $pos)) >= time() ) {
						$res	= unserialize(substr($data, $pos+1));
					}
				}
				if( FALSE === $res ) {
					$this->remove_file($file);
				}
			}
			if( $this->debug_mode ) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'GET',
					'key'		=> $this->keys_prefix.$key,
					'result'	=> FALSE===$res ? 'FALSE' : gettype($res),
					'time'	=> number_format($time, 5, '.', ''),
				);
			}
			return $res;
		}
		
		public function set($key, $data, $ttl)
		{
			$file	= $this->find_filename($key);
			$time	= microtime(TRUE);
			// A temporary file replaces the entry in one step (rename), so that a concurrent request
			// reads the old or the new value, never a partly written or mixed one.
			$tmp	= $file.'.'.getmypid().'-'.mt_rand().'.tmp';
			$res	= FALSE !== @file_put_contents($tmp, (time()+$ttl)."\n".serialize($data));
			if( $res ) {
				// read back with unserialize(), so only the owner may write or read it
				@chmod($tmp, 0600);
				$res	= @rename($tmp, $file);
				if( ! $res && $this->remove_file($file) ) {
					// Windows does not replace a file that another process holds open.
					$res	= @rename($tmp, $file);
				}
			}
			if( ! $res ) {
				// before the unlink() of the temporary file: error_get_last() still holds the failed write
				$this->log_error();
				@unlink($tmp);
			}
			if( $this->debug_mode ) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'SET',
					'key'		=> $this->keys_prefix.$key,
					'result'	=> $res ? 'TRUE' : 'FALSE',
					'time'	=> number_format($time, 5, '.', ''),
				);
			}
			return $res;
		}
		
		public function del($key)
		{
			$file	= $this->find_filename($key);
			$time	= microtime(TRUE);
			$res	= $this->remove_file($file);
			if( $this->debug_mode ) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'DELETE',
					'key'		=> $this->keys_prefix.$key,
					'result'	=> $res ? 'TRUE' : 'FALSE',
					'time'	=> number_format($time, 5, '.', ''),
				);
				return $res;
			}
			return $res;
		}
		
		private function log_error()
		{
			// One line per request, like the memcached and Redis drivers, instead of warnings for every entry.
			if( $this->error_logged ) {
				return;
			}
			$this->error_logged	= TRUE;
			$e	= error_get_last();
			error_log('Sharetronix cache: cannot write files in '.$this->path.' ('.($e ? $e['message'] : 'unknown error').'); this request does not store cache entries.');
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
			$prefix	= md5($this->keys_prefix).'-';
			$prefixlen	= strlen($prefix);
			$time	= microtime(TRUE);
			$dir	= opendir($this->path);
			if( ! $dir ) {
				return 0;
			}
			$i	= 0;
			while($filename = readdir($dir)) {
				if($filename=="." || $filename=="..") {
					continue;
				}
				if( substr($filename, 0, $prefixlen) != $prefix ) {
					continue;
				}
				$file	= $this->path.'/'.$filename;
				if( preg_match('/^[0-9a-f]{32}-[0-9a-f]{32}\..+\.tmp$/', $filename) ) {
					// left behind by an interrupted write
					if( @filemtime($file) < time()-3600 ) {
						@unlink($file);
					}
					continue;
				}
				// A request may delete an expired entry while this runs; a missing file is not an error.
				$fp	= @fopen($file, 'r');
				if( ! $fp ) {
					continue;
				}
				$tm	= fread($fp, 10);
				fclose($fp);
				if( intval($tm) <= time() && is_writable($file) ) {
					unlink($file);
					$i	++;
				}
			}
			closedir($dir);
			if( $this->debug_mode ) {
				$time	= microtime(TRUE)-$time;
				$this->debug_info->time	+= $time;
				$this->debug_info->queries[]	= (object) array (
					'action'	=> 'GARBAGE',
					'key'		=> 'delete',
					'result'	=> $i,
					'time'	=> number_format($time, 5, '.', ''),
				);
			}
			return $i;
		}
	}
	
?>