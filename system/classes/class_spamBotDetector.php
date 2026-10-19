<?php
	class spamBotDetector
	{
		private $error = false;
		
		// https://www.stopforumspam.com/usage; replaced by the tests.
		protected $stopforumspam_url = 'https://api.stopforumspam.org/api';
		
		protected function if_data_is_valid( $ip ){
			return is_string($ip) && !empty($ip);
		}
		
		public function checkDomainForum( $ip, $email )
		{
			global $C;
			
			if( ! $this->if_data_is_valid($ip) ){
				return false;
			}
			if( ! $this->if_data_is_valid($email) ){
				return false;
			}
			if( ! function_exists('curl_init') ){
				$this->stopforumspam_failed('the cURL extension is not loaded');
				return false;
			}
			
			// Short time limits keep the sign-up well under max_execution_time; a service that cannot
			// be reached does not stop the sign-up. A POST keeps the e-mail address out of the URL.
			$curl = new curlCall($this->stopforumspam_url);
			$curl->setTimeouts(3, 5);
			$curl->addPostData(array('ip' => $ip, 'email' => $email, 'json' => ''), FALSE);
			$curl_body = $curl->getData();
			$curl_result = is_string($curl_body) ? json_decode($curl_body) : NULL;
				
			if( ! isset($curl_result->success) || $curl_result->success !== 1 ){
				$reason = $curl_body === FALSE ? $curl->getError() : 'HTTP '.$curl->getStatus();
				if( isset($curl_result->error) && is_string($curl_result->error) ){
					$reason .= ', '.$curl_result->error;
				}
				$this->stopforumspam_failed($reason);
				return $this->error;
			}
			
			// A listing alone is weak evidence: addresses are shared and change hands, and reports stay
			// listed for years. Its confidence, a percentage that falls quickly as the last report gets
			// older, has to reach the minimum.
			$min_confidence = isset($C->STOPFORUMSPAM_MIN_CONFIDENCE) && is_numeric($C->STOPFORUMSPAM_MIN_CONFIDENCE) ? (float) $C->STOPFORUMSPAM_MIN_CONFIDENCE : 10;
			foreach( array('email', 'ip') as $field ){
				if( isset($curl_result->$field->appears) && $curl_result->$field->appears ){
					$confidence = isset($curl_result->$field->confidence) ? (float) $curl_result->$field->confidence : 100;
					if( $confidence >= $min_confidence ){
						$this->error	= TRUE;
					}
				}
			}
			
			return $this->error;
		}
		
		private function stopforumspam_failed( $reason )
		{
			error_log('Sharetronix spam check: StopForumSpam check failed ('.trim(preg_replace('/\s+/', ' ', $reason)).'); the sign-up went on without it.');
		}
		
		public function checkProjectHoneyPot( $ip )
		{
			global $C;
			
			if( ! $this->if_data_is_valid($ip) ){
				return false;
			}
			
			if( isset($C->HONEYPOT_ACCESS_KEY) && !empty($C->HONEYPOT_ACCESS_KEY) ){
				$ip_to_check = explode('.', $ip);
				if( count($ip_to_check) != 4 ){
					return false;
				}
				$ip_to_check = implode('.', array_reverse($ip_to_check));
			
				// http:BL is a DNS list (https://www.projecthoneypot.org/httpbl_api.php): a listed address
				// resolves to 127.<days>.<threat>.<type>; type 0 is a search engine, any other type a bot.
				$honeypot_name = $C->HONEYPOT_ACCESS_KEY.'.'.$ip_to_check.'.dnsbl.httpbl.org';
				$answer = $this->dns_lookup($honeypot_name);
				$octets = explode('.', $answer);
				if( $answer !== $honeypot_name && count($octets) == 4 && $octets[0] === '127' && (int) $octets[3] > 0 ){
					$this->error = TRUE;
				}
			}	
			
			return $this->error;
		}
		
		// The A record of a name, or the name itself when it does not resolve; replaced by the tests.
		protected function dns_lookup( $name )
		{
			return gethostbyname($name);
		}
	}