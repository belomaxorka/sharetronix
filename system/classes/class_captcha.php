<?php

	// CAPTCHA of the sign-up, password recovery and contact forms. Every form uses its own namespace.
	// $C->CAPTCHA_PROVIDER selects the built-in image ('image', the default) or an external widget;
	// an external provider needs $C->CAPTCHA_SITE_KEY and $C->CAPTCHA_SECRET_KEY. The image code lives
	// in the session, expires after LIFETIME seconds and is removed by every check.
	class captcha
	{
		const LIFETIME	= 900;
		const LENGTH	= 5;
		const CHARSET	= 'abcdefhjkmnprstuvwxy345678';
		const WIDTH		= 180;
		const HEIGHT	= 50;

		// '' after a successful check, 'wrong' for a wrong or missing answer, 'unavailable' when the
		// external service could not check it (logged).
		public $error	= '';

		private static $namespaces	= array('signup', 'forgotten_pass', 'contacts');

		// External widgets: script, container class, POST field of the token, verification URL and the
		// name of the token in the verification request.
		private static $providers	= array(
			'recaptcha2'	=> array(
				'script'	=> 'https://www.google.com/recaptcha/api.js',
				'class'		=> 'g-recaptcha',
				'field'		=> 'g-recaptcha-response',
				'verify'	=> 'https://www.google.com/recaptcha/api/siteverify',
				'token'		=> 'response',
			),
			'recaptcha3'	=> array(
				'script'	=> 'https://www.google.com/recaptcha/api.js?render=',
				'class'		=> '',
				'field'		=> 'g-recaptcha-response',
				'verify'	=> 'https://www.google.com/recaptcha/api/siteverify',
				'token'		=> 'response',
			),
			'turnstile'	=> array(
				'script'	=> 'https://challenges.cloudflare.com/turnstile/v0/api.js',
				'class'		=> 'cf-turnstile',
				'field'		=> 'cf-turnstile-response',
				'verify'	=> 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				'token'		=> 'response',
			),
			'hcaptcha'	=> array(
				'script'	=> 'https://js.hcaptcha.com/1/api.js',
				'class'		=> 'h-captcha',
				'field'		=> 'h-captcha-response',
				'verify'	=> 'https://api.hcaptcha.com/siteverify',
				'token'		=> 'response',
			),
			'yandex'	=> array(
				'script'	=> 'https://smartcaptcha.cloud.yandex.ru/captcha.js',
				'class'		=> 'smart-captcha',
				'field'		=> 'smart-token',
				'verify'	=> 'https://smartcaptcha.cloud.yandex.ru/validate',
				'token'		=> 'token',
			),
		);

		// error-codes of the siteverify services that mean a configuration or service problem rather
		// than a wrong answer.
		private static $service_errors	= array('missing-input-secret', 'invalid-input-secret', 'sitekey-secret-mismatch', 'not-using-dummy-passcode', 'not-using-dummy-secret', 'bad-request', 'internal-error');

		private $namespace;
		private $provider;
		private $problem;
		private $user;

		// Also sets $D->captcha_widget (and its old name $D->use_google_recaptcha) for the form blocks,
		// which read it when the controller loads them.
		public function __construct($namespace)
		{
			global $C, $D;
			$this->namespace	= (string) $namespace;
			$this->user		= & $GLOBALS['user'];

			$this->provider	= 'image';
			$this->problem	= '';
			$name	= isset($C->CAPTCHA_PROVIDER) && is_string($C->CAPTCHA_PROVIDER) ? strtolower(trim($C->CAPTCHA_PROVIDER)) : '';
			if( $name !== '' && $name !== 'image' ) {
				if( ! isset(self::$providers[$name]) ) {
					$this->problem	= 'unknown provider \''.substr(preg_replace('/[^a-z0-9_.-]/', '?', $name), 0, 32).'\'';
				}
				elseif( $this->setting('CAPTCHA_SITE_KEY') === '' || $this->setting('CAPTCHA_SECRET_KEY') === '' ) {
					$this->problem	= 'provider \''.$name.'\' needs $C->CAPTCHA_SITE_KEY and $C->CAPTCHA_SECRET_KEY';
				}
				else {
					$this->provider	= $name;
				}
			}

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
			return $this->provider !== 'image';
		}

		public function get_html()
		{
			global $C;
			if( $this->provider == 'recaptcha3' ) {
				return $this->get_recaptcha3_html();
			}
			if( $this->is_widget() ) {
				$p	= self::$providers[$this->provider];
				$style	= $this->provider == 'yandex' ? ' style="height: 100px"' : '';
				return '<div class="'.$p['class'].'" data-sitekey="'.htmlspecialchars($this->setting('CAPTCHA_SITE_KEY')).'"'.$style.'></div>'
					.'<script src="'.$p['script'].'" async defer></script>';
			}
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
			if( $this->problem !== '' ) {
				error_log('Sharetronix captcha: '.$this->problem.'; the built-in image CAPTCHA is used.');
			}
			$ok	= $this->is_widget() ? $this->verify_widget() : $this->verify_image();
			if( ! $ok && $this->error === '' ) {
				$this->error	= 'wrong';
			}
			return $ok;
		}

		public function get_error_message($wrong_key)
		{
			if( $this->error == 'unavailable' ) {
				return $this->text('captcha_err_unavailable', 'The verification service is not available. Please try again later.');
			}
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

		// One form POST to a verification service: array('status' => HTTP status, 'body' => answer,
		// 'error' => '' or the cURL error).
		protected function post($url, $fields)
		{
			if( ! function_exists('curl_init') ) {
				return array('status' => 0, 'body' => '', 'error' => 'the cURL extension is not loaded');
			}
			$curl	= new curlCall($url);
			$curl->setTimeouts(5, 10);
			$curl->addPostData($fields, FALSE);
			$body	= $curl->getData();
			return array('status' => $curl->getStatus(), 'body' => is_string($body) ? $body : '', 'error' => $body === FALSE ? $curl->getError() : '');
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

		private function verify_widget()
		{
			$p	= self::$providers[$this->provider];
			$token	= $this->posted($p['field']);
			if( $token === '' || strlen($token) > 4096 ) {
				return FALSE;
			}
			$fields	= array('secret' => $this->setting('CAPTCHA_SECRET_KEY'), $p['token'] => $token);
			if( $this->provider == 'hcaptcha' ) {
				$fields['sitekey']	= $this->setting('CAPTCHA_SITE_KEY');
			}
			$answer	= $this->post($p['verify'], $fields);
			if( $answer['error'] !== '' || $answer['status'] != 200 ) {
				return $this->unavailable($answer['error'] !== '' ? $answer['error'] : 'HTTP '.$answer['status']);
			}
			$res	= json_decode($answer['body']);
			if( ! is_object($res) ) {
				return $this->unavailable('the answer is not a JSON object');
			}

			if( $this->provider == 'yandex' ) {
				if( isset($res->status) && $res->status === 'ok' ) {
					return TRUE;
				}
				// The message is empty for a robot and names the token when it is wrong or used.
				$message	= isset($res->message) && is_string($res->message) ? $res->message : '';
				if( $message !== '' && stripos($message, 'token') === FALSE ) {
					return $this->unavailable($message);
				}
				return FALSE;
			}

			if( isset($res->success) && $res->success === TRUE ) {
				if( $this->provider == 'recaptcha3' ) {
					$score	= isset($res->score) ? (float) $res->score : 0.0;
					return ( ! isset($res->action) || $res->action === $this->namespace ) && $score >= $this->min_score();
				}
				return TRUE;
			}
			$codes	= isset($res->{'error-codes'}) && is_array($res->{'error-codes'}) ? $res->{'error-codes'} : array();
			$service	= array_values(array_intersect($codes, self::$service_errors));
			if( count($service) ) {
				return $this->unavailable('error-codes: '.implode(', ', $service));
			}
			return FALSE;
		}

		private function unavailable($reason)
		{
			$this->error	= 'unavailable';
			error_log('Sharetronix captcha: '.$this->provider.' verification failed ('.trim(preg_replace('/\s+/', ' ', $reason)).'); the form was not accepted.');
			return FALSE;
		}

		// reCAPTCHA v3 has no visible widget: the token is requested when the form is sent.
		private function get_recaptcha3_html()
		{
			$key	= $this->setting('CAPTCHA_SITE_KEY');
			$id	= 'captcha_token_'.$this->namespace;
			$js_key	= json_encode($key, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
			return '<input type="hidden" name="g-recaptcha-response" id="'.$id.'" value="" />'
				.'<script src="'.self::$providers['recaptcha3']['script'].rawurlencode($key).'"></script>'
				.'<script>(function() {'
				.'var t = document.getElementById(\''.$id.'\'), f = t ? t.form : null;'
				.'if( ! f ) { return; }'
				.'f.addEventListener(\'submit\', function(e) {'
				.'if( t.value !== \'\' ) { return; }'
				.'e.preventDefault();'
				.'var b = e.submitter || f.querySelector(\'[type=submit][name]\');'
				.'var send = function() {'
				.'if( b && b.name ) { var h = document.createElement(\'input\'); h.type = \'hidden\'; h.name = b.name; h.value = b.value; f.appendChild(h); }'
				.'HTMLFormElement.prototype.submit.call(f);'
				.'};'
				.'if( typeof grecaptcha === \'undefined\' ) { send(); return; }'
				.'grecaptcha.ready(function() {'
				.'try { grecaptcha.execute('.$js_key.', {action: \''.$this->namespace.'\'}).then(function(v) { t.value = v; send(); }, send); }'
				.'catch(x) { send(); }'
				.'});'
				.'});'
				.'})();</script>';
		}

		private function min_score()
		{
			global $C;
			$score	= isset($C->CAPTCHA_MIN_SCORE) && is_numeric($C->CAPTCHA_MIN_SCORE) ? (float) $C->CAPTCHA_MIN_SCORE : 0.5;
			return max(0.0, min(1.0, $score));
		}

		private function setting($name)
		{
			global $C;
			return isset($C->$name) && is_string($C->$name) ? trim($C->$name) : '';
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
