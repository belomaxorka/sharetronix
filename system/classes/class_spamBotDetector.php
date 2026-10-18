<?php
	class spamBotDetector
	{
		private $error = false;
		
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
			
			$curl = new curlCall('http://www.stopforumspam.com/api?ip='.$ip.'&email='.$email.'&f=json');
			$curl_result = $curl->getData();
			$curl_result = json_decode($curl_result);
				
			if( isset($curl_result->success) && $curl_result->success === 1 ){
				if( isset($curl_result->success->appears) && $curl_result->email->appears ){
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
			
				$honeypot_url = $C->HONEYPOT_ACCESS_KEY.'.'.$ip_to_check.'.dnsbl.httpbl.org';
				$curl = new curlCall($honeypot_url);
				$curl = $curl->getData();

				$this->error = ($curl == false)? false : true;//stupid, but later we will check the answer in $curl
			}	
			
			return $this->error;
		}
	}