<?php
	global $D;
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	$this->db2->query('SELECT 1 FROM users WHERE id="'.$this->user->id.'" AND is_network_admin=1 LIMIT 1');
	if( 0 == $this->db2->num_rows() ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/admin.php');
	
	$tpl = new template( array(
		'page_title' => $C->SITE_TITLE.' - Languages',
		'header_page_layout'=>'sc',
	));
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	$action = ( isset($this->params->tab) == true ) ? filter_var($this->params->tab, FILTER_SANITIZE_SPECIAL_CHARS) : "index";
	$controller = new AdminLanguages($this->user, $tpl, $this->params);

	if(method_exists($controller, $action)) {
		call_user_func(array($controller, $action));
	} else {
		die();
	}
	
	
	class AdminLanguages {
		
		protected $db;
		protected $user;
		protected $tpl;
		protected $params;
		protected $page;
		
		public function __construct($user, &$tpl, &$params) {
			$this->AdminLanguages($user, $tpl, $params);
		}

		public function AdminLanguages($user, &$tpl, &$params){
			global $db1;
			$this->db = &$db1;
			$this->user = $user;
			$this->tpl = &$tpl;
			$this->page = & $GLOBALS['page'];
			$this->params = &$params;
		}
		
		
		public function index(){
			global $C, $D;
			
			// A language pack is a folder in system/languages/; the default language is chosen in the
			// general settings. The marketplace that installed packs from here no longer exists.
			require_once( $C->INCPATH.'helpers/func_languages.php' );
			$D->languages = array();
			foreach( get_available_languages(TRUE) as $key => $lang ){
				$D->languages[] = array('langkey' => $key, 'lang_name' => trim($lang->name), 'default' => $key == $C->LANGUAGE);
			}
			
			$this->tpl->layout->useBlock('languages/viewall');
			$this->tpl->layout->block->save( 'main_content' );
		}
		
	}
	
	
	$tpl->display();
