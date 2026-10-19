<?php
	class AdminPlugins
	{

		protected $db;
		protected $user;
		protected $tpl;
		protected $params;

		public function __construct($user, &$tpl, &$params)
		{
			$this->AdminPlugins($user, $tpl, $params);
		}

		public function AdminPlugins($user, &$tpl, &$params)
		{

			global $db1, $D;
			$this->db = &$db1;
			$this->user = $user;
			$this->tpl = &$tpl;
			$this->params = &$params;

			$D->items_type = 'plugins';
		}


		/**
		 * Reads apps/<name>/manifest.json. A plugin is a folder of apps/ with a manifest.json whose
		 * "plugin_name" is the folder name and a plugin.php that defines a class of that name.
		 * Returns array('manifest' => object|FALSE, 'error' => string).
		 */
		public static function readManifest($name)
		{
			global $C;

			if( !is_string($name) || !preg_match('/^[a-z0-9_]+$/i', $name) ){
				return array('manifest' => FALSE, 'error' => 'The folder name may only contain letters, digits and underscores.');
			}
			$dir = $C->PLUGINS_DIR.$name.'/';
			if( !is_dir($dir) ){
				return array('manifest' => FALSE, 'error' => 'The folder apps/'.$name.'/ does not exist.');
			}
			if( !is_file($dir.'manifest.json') ){
				return array('manifest' => FALSE, 'error' => 'manifest.json is missing.');
			}
			$manifest = json_decode(file_get_contents($dir.'manifest.json'));
			if( !is_object($manifest) ){
				return array('manifest' => FALSE, 'error' => 'manifest.json is not valid JSON.');
			}
			if( !isset($manifest->plugin_name) || $manifest->plugin_name !== $name ){
				return array('manifest' => FALSE, 'error' => 'The "plugin_name" in manifest.json must be the folder name, '.$name.'.');
			}
			if( !is_file($dir.'plugin.php') ){
				return array('manifest' => FALSE, 'error' => 'plugin.php is missing.');
			}

			return array('manifest' => $manifest, 'error' => '');
		}


		public function index()
		{
			global $C, $D, $plugins_manager;

			$rows = array();
			foreach( (array) $this->db->fetch_all('SELECT name, is_installed FROM plugins') as $r ){
				$rows[$r->name] = intval($r->is_installed);
			}
			$loaded = $plugins_manager->getInstalledPluginNames();

			$names = array_keys($rows);
			if( $handle = @opendir($C->PLUGINS_DIR) ){
				while( FALSE !== ($entry = readdir($handle)) ){
					if( substr($entry, 0, 1) !== '.' && is_dir($C->PLUGINS_DIR.$entry) ){
						$names[] = $entry;
					}
				}
				closedir($handle);
			}
			$names = array_unique($names);
			sort($names);

			$D->plugins = array();
			foreach( $names as $name ){
				$m = self::readManifest($name);
				$p = (object) array(
					'name'		=> $name,
					'version'	=> $m['manifest'] && isset($m['manifest']->version) ? (string) $m['manifest']->version : '',
					'descr'		=> $m['manifest'] && isset($m['manifest']->descr) ? (string) $m['manifest']->descr : '',
					'author'	=> $m['manifest'] && isset($m['manifest']->author) ? (string) $m['manifest']->author : '',
					'error'		=> $m['error'],
					'installed'	=> isset($rows[$name]),
					'enabled'	=> isset($rows[$name]) && $rows[$name] == 1,
					'loaded'	=> in_array($name, $loaded, TRUE),
				);
				$D->plugins[] = $p;
			}
			$D->token = $this->token()->get_value();

			$this->tpl->layout->useBlock('plugins/local');
			$this->tpl->layout->block->save( 'main_content' );
		}


		public function install()
		{
			$this->confirmPage('install');
		}


		public function uninstall()
		{
			$this->confirmPage('uninstall');
		}


		public function confirm_install()
		{
			global $C, $user;

			$name = $this->postedPlugin();
			$m = self::readManifest($name);
			if( !$m['manifest'] ){
				return $this->showError($m['error']);
			}
			if( $this->db->fetch_field('SELECT 1 FROM plugins WHERE name="'.$this->db->e($name).'" LIMIT 1') ){
				return $this->showError('The plugin '.htmlspecialchars($name).' is already installed.');
			}
			$result = $this->runInstaller($name, $m['manifest'], 'up', TRUE);
			if( count($result['errors']) ){
				return $this->showError(implode('<br />', $result['errors']));
			}
			$this->db->query('INSERT INTO plugins (name, is_installed, date_installed, installed_by_user_id) VALUES ("'.$this->db->e($name).'", 1, '.time().', '.intval($user->id).')');
			$this->changed();
			$this->redirect('installed:ok');
		}


		public function confirm_uninstall()
		{
			$name = $this->postedPlugin();
			if( !$this->db->fetch_field('SELECT 1 FROM plugins WHERE name="'.$this->db->e($name).'" LIMIT 1') ){
				return $this->showError('The plugin '.htmlspecialchars($name).' is not installed.');
			}
			// Without its files (or with a broken manifest) the plugin can still be removed from the list.
			$m = self::readManifest($name);
			if( $m['manifest'] ){
				$result = $this->runInstaller($name, $m['manifest'], 'down', TRUE);
				if( count($result['errors']) ){
					return $this->showError(implode('<br />', $result['errors']));
				}
			}
			$this->db->query('DELETE FROM plugins WHERE name="'.$this->db->e($name).'"');
			$this->db->query('DELETE FROM plugins_installed WHERE name="'.$this->db->e($name).'"');
			$this->changed();
			$this->redirect('uninstalled:ok');
		}


		public function enable()
		{
			$this->setEnabled(1);
		}


		public function disable()
		{
			$this->setEnabled(0);
		}


		protected function setEnabled($state)
		{
			$name = $this->postedPlugin();
			if( !$this->db->fetch_field('SELECT 1 FROM plugins WHERE name="'.$this->db->e($name).'" LIMIT 1') ){
				return $this->showError('The plugin '.htmlspecialchars($name).' is not installed.');
			}
			$this->db->query('UPDATE plugins SET is_installed='.intval($state).' WHERE name="'.$this->db->e($name).'"');
			$this->changed();
			$this->redirect($state ? 'enabled:ok' : 'disabled:ok');
		}


		/**
		 * Install and uninstall go through a confirmation page that lists what installer.php will do
		 * (its validation mode); the change itself happens on the confirmation.
		 */
		protected function confirmPage($action)
		{
			global $D;

			$name = $this->postedPlugin();
			$m = self::readManifest($name);
			$installed = (bool) $this->db->fetch_field('SELECT 1 FROM plugins WHERE name="'.$this->db->e($name).'" LIMIT 1');
			if( $action == 'install' && !$m['manifest'] ){
				return $this->showError($m['error']);
			}
			if( $action == 'install' && $installed ){
				return $this->showError('The plugin '.htmlspecialchars($name).' is already installed.');
			}
			if( $action == 'uninstall' && !$installed ){
				return $this->showError('The plugin '.htmlspecialchars($name).' is not installed.');
			}
			$result = $m['manifest'] ? $this->runInstaller($name, $m['manifest'], $action == 'install' ? 'up' : 'down', FALSE) : array('messages' => array(), 'errors' => array(), 'has_installer' => FALSE);

			$D->plugin = $name;
			$D->action = $action;
			$D->errors = $result['errors'];
			$D->messages = $result['messages'];
			$D->has_installer = $result['has_installer'];
			$D->token = $this->token()->get_value();

			$this->tpl->layout->useBlock('plugins/confirm');
			$this->tpl->layout->block->save( 'main_content' );
		}


		/**
		 * Runs up() or down() of apps/<name>/installer.php (class MyInstaller extends Installer): in
		 * validation mode it only reports what would change, otherwise it changes the database.
		 */
		protected function runInstaller($name, $manifest, $direction, $real)
		{
			global $C;

			$result = array('messages' => array(), 'errors' => array(), 'has_installer' => FALSE);
			$file = $C->PLUGINS_DIR.$name.'/installer.php';
			if( !is_file($file) ){
				return $result;
			}
			$result['has_installer'] = TRUE;

			try {
				require_once $C->INCPATH.'plugin_installer/Installer.php';
				include_once $file;
				if( !class_exists('MyInstaller', FALSE) ){
					$result['errors'][] = 'installer.php does not define the class MyInstaller.';
					return $result;
				}
				MyInstaller::$mode = $real ? Installer::INSTALLER_MODE_INSTALL : Installer::INSTALLER_MODE_VALIDATION;
				MyInstaller::$manifest = $manifest;
				$inst = new MyInstaller();
				if( method_exists($inst, 'up') && method_exists($inst, 'down') ){
					$direction == 'up' ? $inst->up() : $inst->down();
				}
				$messages = $inst->finish();
				foreach( is_array($messages) ? $messages : array() as $msg ){
					if( $msg instanceof ValidationError ){
						$result['errors'][] = $msg->getMessage();
					}else{
						$result['messages'][] = $msg->getMessage();
					}
				}
			} catch( Throwable $e ) {
				$result['errors'][] = htmlspecialchars(get_class($e).': '.$e->getMessage());
			}

			return $result;
		}


		/**
		 * Every action is a POST form with the plugin's folder name and a token; links and pages of
		 * other sites cannot change plugins.
		 */
		protected function postedPlugin()
		{
			global $page;

			if( $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['token'], $_POST['plugin']) || !is_string($_POST['token']) || !is_string($_POST['plugin']) || !$this->token()->check_if_valid($_POST['token']) ){
				$page->redirect('admin/plugins/msg:expired');
			}

			return $_POST['plugin'];
		}


		protected function token()
		{
			return new csrf('admin_plugins');
		}


		protected function changed()
		{
			global $plugins_manager;

			$plugins_manager->invalidateEventCache();	// plugins_cache
			invalidateCachedHTML();				// header/footer with plugin scripts and styles
		}


		protected function redirect($param)
		{
			global $page;

			$page->redirect('admin/plugins/'.$param);
		}


		protected function showError($text)
		{
			$this->tpl->layout->setVar('main_content_placeholder', $this->tpl->designer->errorMessage('Error', $text));
			$this->index();
		}
	}
