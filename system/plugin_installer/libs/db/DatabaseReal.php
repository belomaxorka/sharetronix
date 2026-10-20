<?php

require_once $C->INSTALLER_PATH . 'libs/db/DatabaseInterface.php';
require_once $C->INSTALLER_PATH . 'libs/db/ActiveRecord.php';
require_once $C->INSTALLER_PATH . 'libs/TableOwner.php';

class DatabaseReal implements DatabaseInterface {
	
	private $db;
	protected $plugin_name;
	// The statements that up() or down() carried out so far: named when a later one fails.
	private $executed = array();
	// The undo of what up() created in this run, newest last (DROP TABLE, DROP COLUMN, the plugins_tables
	// rows), the tables it created, and the statements of installer.php that a rollback cannot undo.
	private $undo = array();
	private $created = array();
	private $kept = array();
	
	public function __construct($plugin_name) {
		$this->DatabaseReal($plugin_name);
	}

	public function DatabaseReal($plugin_name){
		$this->plugin_name = $plugin_name;
		$this->db = ActiveRecord::getInstance();
	}
	
	
	public function create_table($table_name, $engine = 'MyISAM', $charset = 'utf8mb4') {

		$sql = sprintf("
			CREATE TABLE %s ( 
				id INT(11) NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (id)
			) ENGINE=%s DEFAULT CHARSET=%s%s;
			", $table_name, $engine, $charset,
			// the collation of the core tables: text of two collations cannot be compared
			$charset == 'utf8mb4' ? ' COLLATE=' . mysql::UTF8MB4_COLLATION : ''
		);
		$this->run($sql);
		$this->created[] = $table_name;
		$this->undo[] = 'DROP TABLE IF EXISTS ' . $table_name;
		$this->remember_owner($table_name);

		TableOwner::assumeOwnership($table_name, $this->plugin_name);
                
	}
	
	
	public function drop_table($table_name) {
		
		$sql = sprintf("DROP TABLE IF EXISTS %s", $table_name);
		$this->execute($sql);
		// The plugin no longer owns the table (create_table() registered it).
		$this->execute(sprintf("DELETE FROM plugins_tables WHERE `table`=%s AND `owner`=%s", $this->db->link->quote($table_name), $this->db->link->quote($this->plugin_name)));
	}
	
	
	public function add_field($table_name, $field_name, $field_type, $options = array()) {
		
		$null = "NULL";
		$limit = false;
		$default = "DEFAULT NULL";
		$type = $field_type;
		$has_default = false;
		
		foreach($options as $opt) {
			switch (get_class($opt)){
				case "FieldOptionLimit":
					$type .= "(" . $opt->getValue() . ")"; 
					break;
				case "FieldOptionDefault":
					$val = $opt->getValue();
					$has_default = true;
					if(in_array($field_type, array(ColumnType::INTEGER, ColumnType::BOOLEAN, ColumnType::FLOAT, ColumnType::DECIMAL))) {
						// MySQL refuses a default such as '' for a number column (error 1067, in every SQL mode)
						// and stores '' as 0 when it is inserted.
						$default = "DEFAULT " . (is_numeric($val) ? $val : 0);
					}  else {
						$default = "DEFAULT " . $this->db->link->quote($val);
					}
					break;
				case "FieldOptionNull":
					if($opt->getValue() == false) {
						$null = " NOT NULL";
					}
					break;
				default:
					throw new Exception("Unsupported field option");
			}
		}
		
		// NOT NULL without a default: "NOT NULL DEFAULT NULL" is invalid, so the column gets no default.
		if($null != "NULL" && !$has_default) {
			$default = "";
		}
		
		if($limit == false) {
			if($type == ColumnType::INTEGER) {
				$type .= "(11) ";
			}
			if($type == ColumnType::STRING) {
				$type .= "(255) ";
			}
		}
		
		$sql = "
			ALTER TABLE  $table_name 
				ADD COLUMN $field_name $type $null $default 
		";
		$this->run($sql);
		if( !in_array($table_name, $this->created, TRUE) ) {
			// a table that this run did not create keeps its other columns
			$this->undo[] = 'ALTER TABLE ' . $table_name . ' DROP COLUMN ' . $field_name;
			$this->remember_owner($table_name);
		}
		
		TableOwner::assumeOwnership($table_name, $this->plugin_name);
		
	}
	
	
	public function remove_field($table_name, $field_name) {
		$sql = sprintf("
			ALTER TABLE %s DROP COLUMN %s", $table_name,  $field_name
		);
		$this->execute($sql);
		
	}
	

	// A statement of installer.php itself: a rollback undoes it only with a table that this run created.
	public function execute($sql) {
		$this->run($sql);
		$this->kept[] = $this->statement($sql);
	}
	
	// Runs a statement of up() or down() and keeps it for the messages of the Plugins page.
	private function run($sql) {
		$this->db = ActiveRecord::getInstance();
		try {
			$this->db->query($sql);
		} catch (Exception $e) {
			// Stops up() or down(): the Plugins page shows the message and leaves the plugin as it was
			// (AdminPlugins::runInstaller()). Printing the error and going on registered plugins whose
			// tables or columns were never created.
			throw new RuntimeException('The database refused a statement of installer.php: ' . $e->getMessage() . ' Statement: ' . $this->statement($sql));
		}
		$this->executed[] = $this->statement($sql);
		//$sql = str_replace(PHP_EOL, "<br />", $sql);
		//$sql = str_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $sql);
		//print $sql;
	}
	
	
	public function getExecuted() {
		return $this->executed;
	}
	
	// After a statement of up() failed: undoes what this run created, newest first - the columns it added
	// to other tables, the tables it created and their plugins_tables rows. Answers what was undone, what
	// could not be undone, and the statements of installer.php that stay (a data statement on a table
	// that this run created is gone with the table).
	public function rollback() {
		$result = array('undone' => array(), 'failed' => array(), 'kept' => array());
		foreach( array_reverse($this->undo) as $sql ) {
			try {
				$this->db->query($sql);
				$result['undone'][] = $sql;
			} catch (Exception $e) {
				$result['failed'][] = $sql . ' (' . $e->getMessage() . ')';
			}
		}
		foreach( $this->kept as $sql ) {
			if( preg_match('/^(?:INSERT(?:\s+IGNORE)?\s+INTO|REPLACE\s+INTO|UPDATE|DELETE\s+FROM)\s+`?(\w+)`?/i', $sql, $m) && in_array($m[1], $this->created, TRUE) ) {
				continue;
			}
			$result['kept'][] = $sql;
		}
		$this->undo = $this->created = $this->kept = array();
		return $result;
	}
	
	// How to put the plugins_tables row of a table back as it was before this run changed it.
	private function remember_owner($table_name) {
		$owner = TableOwner::getOwner($table_name);
		$this->undo[] = $owner === false
			? sprintf("DELETE FROM plugins_tables WHERE `table`=%s AND `owner`=%s", $this->db->link->quote($table_name), $this->db->link->quote($this->plugin_name))
			: sprintf("UPDATE plugins_tables SET `owner`=%s WHERE `table`=%s", $this->db->link->quote($owner), $this->db->link->quote($table_name));
	}
	
	// A statement on one line without its final semicolon, for the messages of the Plugins page.
	private function statement($sql) {
		return rtrim(trim(preg_replace('/\s+/', ' ', $sql)), '; ');
	}
	
	
}
