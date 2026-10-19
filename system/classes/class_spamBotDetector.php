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
			if( ! $this->if_data_is_valid($ip) ){
				return false;
			}
			if( ! $this->if_data_is_valid($email) ){
				return false;
			}
			
			// Short time limits keep the sign-up well under max_execution_time; a service that cannot
			// be reached does not stop the sign-up.
			$curl = new curlCall($this->stopforumspam_url.'?ip='.rawurlencode($ip).'&email='.rawurlencode($email).'&json');
			$curl->setTimeouts(3, 5);
			$curl_result = $curl->getData();
			$curl_result = is_string($curl_result) ? json_decode($curl_result) : NULL;
				
			if( isset($curl_result->success) && $curl_result->success === 1 ){
				if( isset($curl_result->email->appears) && $curl_result->email->appears ){
					$this->error	= TRUE;
				}elseif( isset($curl_result->ip->appears) && $curl_result->ip->appears ){
					$this->error	= TRUE;
				}
			}	
			
			return $this->error;
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