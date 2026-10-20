<?php



if(defined("PROJPATH") == false){
	chdir(__DIR__);
	chdir("../..");
	$projpath = getcwd();
	
	define('PROJPATH', $projpath);
}
define("PLUGINDIR", PROJPATH . "apps" . DIRECTORY_SEPARATOR);

// The plugins page (class_AdminPlugins) includes this file after the application has read its
// settings; reading conf_main.php again would reset those that the database overrides, such as the
// language, for the rest of the page.
if( !isset($C->INCPATH) ){
	include PROJPATH . 'system/conf_main.php';
}
$C->INSTALLER_PATH = $C->INCPATH . "plugin_installer" . DIRECTORY_SEPARATOR;


require_once $C->INSTALLER_PATH . 'libs/db/DatabaseInterface.php';
require_once $C->INSTALLER_PATH . 'libs/validation/DatabaseValidate.php';
require_once $C->INSTALLER_PATH . 'libs/db/DatabaseReal.php';
require_once $C->INSTALLER_PATH . 'libs/db/ActiveRecord.php';

require_once $C->INSTALLER_PATH . 'libs/TableOwner.php';

require_once $C->INSTALLER_PATH . 'libs/db/ColumnType.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOption.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOptionLimit.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOptionDefault.php';
require_once $C->INSTALLER_PATH . 'libs/db/FieldOptionNull.php';



abstract class Installer {

	const INSTALLER_MODE_VALIDATION=1;
	const INSTALLER_MODE_INSTALL=0;
	
	protected $db;
	public static $mode;
	public static $manifest;
	
	public function __construct() {
		$this->Installer();
	}

	public function Installer(){
		
		if(self::$mode === null) 
			throw new Exception("Something is terribly wrong; no install mode set!");
		
		
			
		if(self::$mode == Installer::INSTALLER_MODE_INSTALL) {
			$this->db = new DatabaseReal(self::$manifest->plugin_name);
		} else {
			
			$this->db = new DatabaseValidate(self::$manifest->plugin_name);
		}
	}
	
	abstract public function up();
	abstract public function down();
	
	
	final public function finish(){
		
		if(self::$mode == Installer::INSTALLER_MODE_VALIDATION) {
			return $this->db->getResult();
		} else {
			//@TODO: check this
			//print "Installation finished from parrent";
		}
		
	}
	
	// The statements that up() or down() carried out so far (install mode): the Plugins page names
	// them when a later statement fails, since the database keeps their result.
	final public function executed(){
		return method_exists($this->db, 'getExecuted') ? $this->db->getExecuted() : array();
	}
	
	// After a failed statement of up() (install mode): undoes the tables and columns that this run created,
	// see DatabaseReal::rollback(). FALSE in validation mode.
	final public function rollback(){
		return method_exists($this->db, 'rollback') ? $this->db->rollback() : FALSE;
	}
	
}

if( function_exists("rcopy") == false || function_exists("rrmdir") == false ) {
	require_once $C->INCPATH . 'helpers/func_filesystem.php';
}
