<?php

require_once $C->INSTALLER_PATH . 'libs/db/DatabaseInterface.php';
require_once $C->INSTALLER_PATH . 'libs/db/ActiveRecord.php';
require_once $C->INSTALLER_PATH . 'libs/TableOwner.php';

class DatabaseReal implements DatabaseInterface {
	
	private $db;
	protected $plugin_name;
	// The statements that up() or down() carried out so far: named when a later one fails.
	private $executed = array();
	
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
		$this->execute($sql);

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
		$this->execute($sql);
		
		TableOwner::assumeOwnership($table_name, $this->plugin_name);
		
	}
	
	
	public function remove_field($table_name, $field_name) {
		$sql = sprintf("
			ALTER TABLE %s DROP COLUMN %s", $table_name,  $field_name
		);
		$this->execute($sql);
		
	}
	

	public function execute($sql) {
		$this->db = ActiveRecord::getInstance();
		try {
			$this->db->query($sql);
		} catch (Exception $e) {
			// Stops up() or down(): the Plugins page shows the message and leaves the plugin as it was
			// (AdminPlugins::runInstaller()). Printing the error and going on registered plugins whose
			// tables or columns were never created.
			throw new RuntimeException('The database refused a statement of installer.php: ' . $e->getMessage() . ' Statement: ' . trim(preg_replace('/\s+/', ' ', $sql)));
		}
		$this->executed[] = trim(preg_replace('/\s+/', ' ', $sql));
		//$sql = str_replace(PHP_EOL, "<br />", $sql);
		//$sql = str_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $sql);
		//print $sql;
	}
	
	
	public function getExecuted() {
		return $this->executed;
	}
	
	
}
