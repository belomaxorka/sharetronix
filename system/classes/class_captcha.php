<?php

	// CAPTCHA of the sign-up, password recovery and contact forms. Every form uses its own namespace;
	// the image code lives in the session, expires after LIFETIME seconds and is removed by every check.
	class captcha
	{
		const LIFETIME	= 900;
		const LENGTH	= 5;
		const CHARSET	= 'abcdefhjkmnprstuvwxy345678';
		const WIDTH		= 180;
		const HEIGHT	= 50;

		public $error	= '';

		private static $namespaces	= array('signup', 'forgotten_pass', 'contacts');

		private $namespace;
		private $user;

		// Also sets $D->captcha_widget (and its old name $D->use_google_recaptcha) for the form blocks,
		// which read it when the controller loads them.
		public function __construct($namespace)
		{
			global $D;
			$this->namespace	= (string) $namespace;
			$this->user		= & $GLOBALS['user'];
			if( is_object($D) ) {
				$D->captcha_widget		= $this->is_widget();
				$D->use_google_recaptcha	= $D->captcha_widget;
			}
		}

		public static function is_namespace($namespace)
		{
			return is_string($namespace) && in_array($namespace, self::$namespaces, TRUE);
		}

		// TRUE when an external widget replaces the image, so the form has no captcha_word field.
		public function is_widget()
		{
			return FALSE;
		}

		public function get_html()
		{
			global $C;
			$url	= $C->SITE_URL.'captcha?namespace='.$this->namespace;
			return '<img id="captcha" src="'.htmlspecialchars($url).'" alt="Captcha" /> '
				.'<a href="#" class="captcha-refresh" onclick="var i=document.getElementById(\'captcha\'); i.src=i.src.replace(/&amp;r=[0-9]+$/, \'\')+\'&amp;r=\'+new Date().getTime(); return false;">'
				.$this->text('captcha_refresh', 'Show another image').'</a>';
		}

		// Sets the variables of a form block: {%captcha_image%} gets the image or the widget,
		// {%captcha_key%} stays empty for older theme blocks.
		public function set_template_vars($block)
		{
			$block->setVar('captcha_image', $this->get_html());
			$block->setVar('captcha_key', '');
		}

		public function verify()
		{
			$this->error	= '';
			if( $this->verify_image() ) {
				return TRUE;
			}
			if( $this->error === '' ) {
				$this->error	= 'wrong';
			}
			return FALSE;
		}

		public function get_error_message($wrong_key)
		{
			return $this->text($wrong_key, 'Invalid verification code, please try again.');
		}

		// Outputs a new image for the namespace and stores its code.
		public function send_image()
		{
			global $C;
			if( ! function_exists('imagettftext') ) {
				error_log('Sharetronix captcha: the image CAPTCHA needs the GD extension with FreeType support.');
				header('HTTP/1.1 500 Internal Server Error');
				return;
			}
			$code	= '';
			for($i=0; $i<self::LENGTH; $i++) {
				$code	.= substr(self::CHARSET, random_int(0, strlen(self::CHARSET)-1), 1);
			}
			$this->user->sess['captcha'][$this->namespace]	= array('code' => $code, 'time' => time());

			$dir	= $C->INCPATH.'libraries/gregwar-captcha/src/Gregwar/Captcha/';
			require_once( $dir.'PhraseBuilderInterface.php' );
			require_once( $dir.'PhraseBuilder.php' );
			require_once( $dir.'CaptchaBuilderInterface.php' );
			require_once( $dir.'CaptchaBuilder.php' );
			$builder	= new Gregwar\Captcha\CaptchaBuilder($code);
			$builder->setImageType('png');
			$builder->build(self::WIDTH, self::HEIGHT);

			header('Content-Type: image/png');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');
			header('Expires: 0');
			$builder->output();
		}

		private function verify_image()
		{
			$stored	= isset($this->user->sess['captcha'][$this->namespace]) ? $this->user->sess['captcha'][$this->namespace] : NULL;
			unset($this->user->sess['captcha'][$this->namespace]);
			$word	= strtolower($this->posted('captcha_word'));
			if( ! is_array($stored) || ! isset($stored['code'], $stored['time']) || $word === '' ) {
				return FALSE;
			}
			if( time() - $stored['time'] > self::LIFETIME ) {
				return FALSE;
			}
			return hash_equals($stored['code'], $word);
		}

		private function posted($name)
		{
			return isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
		}

		// A language string, or the English text when the language pack has no such string.
		private function text($key, $default)
		{
			$txt	= isset($GLOBALS['page']) ? $GLOBALS['page']->lang($key) : '';
			return $txt === '' ? $default : $txt;
		}
	}
