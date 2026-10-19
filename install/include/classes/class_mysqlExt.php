<?php
	
	class mysqlExt extends mysql
	{
		public function connect()
		{
			$time	= microtime(TRUE);
			$this->connection	= $this->ext=='mysqli' ? mysqli_connect(/*'p:'.*/$this->dbhost, $this->dbuser, $this->dbpass) : mysql_connect($this->dbhost, $this->dbuser, $this->dbpass);
			
			if( !isset($_SESSION['INSTALL_DATA']['MYSQL_MYEXT']) || empty($_SESSION['INSTALL_DATA']['MYSQL_MYEXT']) ){
				$_SESSION['INSTALL_DATA']['MYSQL_MYEXT'] = $this->ext;
			}
			
			if(FALSE == $this->connection) {
				return 1;
			}
			$db	= $this->ext=='mysqli' ? mysqli_select_db($this->connection, $this->dbname) : mysql_select_db($this->dbname, $this->connection);
			if(FALSE == $db) {
				return 2;
			}
			// Like mysql::connect(): without it the connection uses the server's default character set,
			// latin1 on a stock MySQL 5.7, and non-ASCII data such as the administrator name is stored
			// double-encoded.
			$this->ext=='mysqli' ? mysqli_query($this->connection, 'SET NAMES utf8') : mysql_query('SET NAMES utf8', $this->connection);
			
			return $this->connection;
		}
		
		public function get_server_info() 
		{
			return $this->ext=='mysqli' ? @mysqli_get_server_info($this->connection) : @mysql_get_server_info($this->connection);
		}
		
		// Runs one statement of the updater. An update that stopped inside an entry, before the entry
		// wrote its VERSION row, runs the entry again on the next attempt; a change it already made (a
		// table, column or index that exists, or one that is already gone) is then skipped instead of
		// stopping the update. Every other error stops it as before.
		public function update_query($query)
		{
			if( FALSE == $this->connection ) {
				$this->connect();
			}
			$result	= $this->ext=='mysqli' ? mysqli_query($this->connection, $query) : mysql_query($query, $this->connection);
			if( FALSE !== $result ) {
				return $result;
			}
			$errno	= $this->ext=='mysqli' ? mysqli_errno($this->connection) : mysql_errno($this->connection);
			// 1050 table exists, 1060 duplicate column, 1061 duplicate key name, 1091 nothing to drop
			if( in_array($errno, array(1050, 1060, 1061, 1091)) ) {
				error_log('Sharetronix update: skipped a change that is already applied: '.substr(preg_replace('/\s+/', ' ', $query), 0, 100));
				return TRUE;
			}
			return $this->fatal_error($query);
		}
		
		public function fetch_array($res) 
		{	
			return $this->ext=='mysqli' ? mysqli_fetch_array($res) : mysql_fetch_array($res);
		}
	}