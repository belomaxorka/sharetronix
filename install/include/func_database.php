<?php
	
	function delete_all_tables()
	{
		global $db, $C;
		$s = & $_SESSION['INSTALL_DATA'];
		
		if( !isset($s['MYSQL_DBNAME']) || empty($s['MYSQL_DBNAME']) ){
			return FALSE;
		}
		
		$res = $db->query('SHOW TABLES FROM '.$db->quote_name($s['MYSQL_DBNAME']));
		while( $tbl = $db->fetch_array($res) ){ 
			$db->query("DROP TABLE IF EXISTS `".$tbl[0] . "`;");
		}
		
		return TRUE;
	}
	
	function create_all_tables()
	{
		global $db, $C;
		
		require_once $C->INCLUDEPATH.'descriptions/database-tables-create.php';
		require_once $C->INCLUDEPATH.'descriptions/database-tables-insert.php';
		
		if( !isset($db_tables) || !isset($db_insert) ){
			return FALSE;
		}
		
		if( isset($db_tables) && isset($db_insert) ){
			foreach( $db_tables as $tableName=>$tableDescription ){
				$db->query($tableDescription);
				
				if( isset($db_insert[$tableName]) ){
					foreach($db_insert[$tableName] as $insertQuery){
						$db->query($insertQuery);
					}
				}
			}
		}
		
		return TRUE;
	}
	
	function insert_all_system_tables()
	{
		global $db, $C;
		$s = & $_SESSION['INSTALL_DATA'];
		
		if( !isset($s['MYSQL_DBNAME']) || empty($s['MYSQL_DBNAME']) ){
			return FALSE;
		}
		
		$res = $db->query("TRUNCATE TABLE `plugins_tables`");
		$res = $db->query('SHOW TABLES FROM '.$db->quote_name($s['MYSQL_DBNAME']));
		while($tbl = $db->fetch_array($res)){
			$db->query("INSERT INTO `plugins_tables` (`table`, `owner`) VALUES ('".$tbl[0] ."', 'system')");
		}
		
		
		return TRUE;
	}
	
	function insert_system_table( $tableName ){
		global $db;
		
		$res = $db->fetch_field('SELECT 1 FROM plugins_tables WHERE `table`="'.$tableName.'" LIMIT 1');
		if( $res ){
			return TRUE;
		}
		$db->query('INSERT INTO plugins_tables(`table`, owner) VALUES("'.$tableName.'", "system")');
		
		return TRUE;
	}
	
	function create_database()
	{
		global $db;
	
		$res = delete_all_tables();
	
		if( $res ){
			// Tables that plugins create without a character set get the one of the core tables.
			// Optional: the user may lack the privilege, and the core tables name their own.
			$db->soft_query('ALTER DATABASE '.$db->quote_name($_SESSION['INSTALL_DATA']['MYSQL_DBNAME']).' CHARACTER SET utf8mb4 COLLATE '.mysql::UTF8MB4_COLLATION);
			$res = create_all_tables();
			insert_all_system_tables();
		}
	
		if( !$res ){
			delete_all_tables();
		}
	
		return $res;
	}
	
	function update_database( $current_version )
	{
		global $db, $C;
		require_once $C->INCLUDEPATH.'descriptions/database-tables-update.php';
		
		if( !isset($db_updates) ){
			return FALSE;
		}
		
		// Releases before 3.0 used another database layout and upload folders.
		if( version_compare($current_version, '3.0.0', '<') ){
			return FALSE;
		}
		
		foreach($db_updates as $version=>$data){
			if( version_compare($version, $current_version, '<=') ){
				continue;
			}
			
			foreach( $data['tables'] as $tblName => $tblDescription ){
				$db->update_query($tblDescription);
				insert_system_table($tblName);
			}
			
			foreach( $data['queries'] as $tblInsert ){
				// An array names a function of this file that carries out a step; FALSE stops the update.
				if( is_array($tblInsert) ){
					if( ! call_user_func($tblInsert['function']) ){
						return FALSE;
					}
					continue;
				}
				$db->update_query($tblInsert);
			}
		}
		
		return TRUE;
	}
	
	// The first step of the 4.0.0 update: a server without mysql::UTF8MB4_COLLATION (MySQL 5.5, MariaDB
	// 5.5) stops the update here, before the steps that drop tables and change rows, which an update stopped
	// at the conversion would leave done while the installed version stays the old one.
	function require_utf8mb4_collation()
	{
		global $db, $D;
		
		$coll	= mysql::UTF8MB4_COLLATION;
		if( ! $db->fetch_field("SHOW COLLATION LIKE '".$coll."'") ) {
			$D->update_error	= 'The database server does not support the utf8mb4 character set with the '.$coll.' collation. Sharetronix needs MySQL 5.7 or later or MariaDB 10.4 or later.';
			return FALSE;
		}
		return TRUE;
	}
	
	// The 4.0.0 update stores text as utf8mb4, so that emoji are kept: converts the tables of the core
	// and of the installed plugins to utf8mb4 with mysql::UTF8MB4_COLLATION, then sets $C->DB_CHARSET
	// in conf_main.php, which switches the connections to utf8mb4. Converted tables are skipped, so
	// an interrupted update continues where it stopped; tables of other applications in the same
	// database are not touched. A core table that cannot be converted stops the update with the
	// MySQL error; a plugin table that cannot be converted keeps utf8 and is listed afterwards.
	function convert_database_to_utf8mb4()
	{
		global $db, $C, $D;
		
		if( ! require_utf8mb4_collation() ) {
			return FALSE;
		}
		$coll	= mysql::UTF8MB4_COLLATION;
		// Each table is copied once; large tables take a while.
		@set_time_limit(0);
		
		require $C->INCLUDEPATH.'descriptions/database-tables-create.php';
		$core	= array_keys($db_tables);
		$plugin	= array();
		$res	= $db->query("SELECT `table` FROM `plugins_tables` WHERE `owner`<>'system'");
		while( $obj = $db->fetch_object($res) ) {
			$plugin[]	= $obj->table;
		}
		$tables	= array();
		$res	= $db->query('SELECT TABLE_NAME AS t, ENGINE AS e, TABLE_COLLATION AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()');
		while( $obj = $db->fetch_object($res) ) {
			$tables[$obj->t]	= $obj;
		}
		
		$D->not_converted	= array();
		foreach( array_merge($core, $plugin) as $name ) {
			if( !isset($tables[$name]) || $tables[$name]->c == $coll ) {
				continue;
			}
			$query	= 'ALTER TABLE '.$db->quote_name($name).' '.utf8mb4_key_changes($name, $tables[$name]->e).'CONVERT TO CHARACTER SET utf8mb4 COLLATE '.$coll;
			if( in_array($name, $core) ) {
				$db->update_query($query);
			}
			elseif( ! $db->soft_query($query) ) {
				$D->not_converted[]	= $name.': '.$db->soft_error;
			}
		}
		// Tables that plugins create without a character set get the one of the core tables (optional).
		$db->soft_query('ALTER DATABASE '.$db->quote_name($db->fetch_field('SELECT DATABASE()')).' CHARACTER SET utf8mb4 COLLATE '.$coll);
		
		if( ! config_set_variable($C->INCPATH.'conf_main.php', '$C->DB_CHARSET', 'utf8mb4') ) {
			$D->update_error	= 'The tables now use utf8mb4, but system/conf_main.php could not be changed. Make it writable and run the update again, or add the line $C->DB_CHARSET = \'utf8mb4\'; to it.';
			return FALSE;
		}
		
		return TRUE;
	}
	
	// The index changes that let a MyISAM table use utf8mb4, for ALTER TABLE: a MyISAM key holds
	// at most 1000 bytes, 250 characters of utf8mb4. A key on one longer column (or prefix) is
	// rebuilt on the first 250 characters with the same name and uniqueness, like the installer's.
	// A longer key on several columns is rebuilt only when it is not UNIQUE, with shorter prefixes
	// on its text columns (utf8mb4_composite_key()); a UNIQUE one stays, as shorter prefixes could
	// make different rows equal: MySQL then refuses to convert the table, MariaDB makes it a hash key.
	function utf8mb4_key_changes($table, $engine)
	{
		global $db;
		
		if( strtoupper($engine) != 'MYISAM' ) {
			return '';
		}
		$keys	= array();
		$res	= $db->query('SELECT s.INDEX_NAME AS i, s.NON_UNIQUE AS u, s.COLUMN_NAME AS col, s.SUB_PART AS sub, c.CHARACTER_MAXIMUM_LENGTH AS len, c.CHARACTER_OCTET_LENGTH AS octets, c.CHARACTER_SET_NAME AS cs, c.DATA_TYPE AS type, c.NUMERIC_PRECISION AS prec FROM information_schema.STATISTICS s LEFT JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA=s.TABLE_SCHEMA AND c.TABLE_NAME=s.TABLE_NAME AND c.COLUMN_NAME=s.COLUMN_NAME WHERE s.TABLE_SCHEMA=DATABASE() AND s.TABLE_NAME="'.$db->e($table).'" AND s.INDEX_TYPE<>"FULLTEXT" ORDER BY s.INDEX_NAME, s.SEQ_IN_INDEX');
		while( $obj = $db->fetch_object($res) ) {
			$keys[$obj->i][]	= $obj;
		}
		$sql	= '';
		foreach( $keys as $name => $parts ) {
			$p	= $parts[0];
			if( $name != 'PRIMARY' && count($parts) > 1 && $p->u && ($cols = utf8mb4_composite_key($parts)) ) {
				$sql	.= 'DROP INDEX '.$db->quote_name($name).', ADD KEY '.$db->quote_name($name).' ('.$cols.'), ';
				continue;
			}
			if( $name == 'PRIMARY' || count($parts) != 1 || $p->len === NULL || ($p->sub ? $p->sub : $p->len) <= 250 ) {
				continue;
			}
			$sql	.= 'DROP INDEX '.$db->quote_name($name).', ADD '.($p->u ? '' : 'UNIQUE ').'KEY '.$db->quote_name($name).' ('.$db->quote_name($p->col).'(250)), ';
		}
		
		return $sql;
	}
	
	// The columns of a key on several columns for ADD KEY, with the prefixes of its text columns shortened
	// in proportion so that the key holds at most 1000 bytes in utf8mb4 (4 bytes a character, a column of
	// another type its size), or FALSE when the key fits as it is or cannot be made to fit.
	function utf8mb4_composite_key($parts)
	{
		global $db;
		
		// the bytes of a key part of a type that has no characters (8 for any other type)
		$sizes	= array('tinyint'=>1, 'smallint'=>2, 'mediumint'=>3, 'int'=>4, 'integer'=>4, 'bigint'=>8, 'float'=>4, 'double'=>8, 'real'=>8, 'date'=>3, 'time'=>6, 'year'=>1, 'datetime'=>8, 'timestamp'=>7, 'enum'=>2, 'set'=>8);
		$fixed	= 0;
		$chars	= 0;
		$text	= array();
		foreach( $parts as $k => $q ) {
			if( $q->col === NULL ) {
				// an expression (a functional key part of MySQL 8) is not rebuilt here
				return FALSE;
			}
			$type	= strtolower((string) $q->type);
			if( $type == 'enum' || $type == 'set' ) {
				$fixed	+= $sizes[$type];
			}
			elseif( $q->cs !== NULL ) {
				$text[$k]	= intval($q->sub ? $q->sub : $q->len);
				$chars	+= $text[$k];
			}
			elseif( $q->octets !== NULL ) {
				// a binary string keeps its bytes
				$fixed	+= intval($q->sub ? $q->sub : $q->octets);
			}
			elseif( $type == 'decimal' || $type == 'bit' ) {
				$fixed	+= intval(ceil($q->prec / ($type == 'bit' ? 8 : 2))) + 1;
			}
			else {
				$fixed	+= isset($sizes[$type]) ? $sizes[$type] : 8;
			}
		}
		$room	= intval(floor((1000 - $fixed) / 4));
		if( ! $text || $fixed + 4 * $chars <= 1000 || $room < count($text) ) {
			return FALSE;
		}
		// each text column keeps its share of the characters that fit, at least one
		$sum	= 0;
		foreach( $text as $k => $n ) {
			$text[$k]	= max(1, intval(floor($n * $room / $chars)));
			$sum	+= $text[$k];
		}
		while( $sum > $room ) {
			$text[array_search(max($text), $text)]--;
			$sum--;
		}
		$cols	= array();
		foreach( $parts as $k => $q ) {
			$cols[]	= $db->quote_name($q->col).(isset($text[$k]) ? '('.$text[$k].')' : ($q->sub ? '('.$q->sub.')' : ''));
		}
		
		return implode(', ', $cols);
	}

?>