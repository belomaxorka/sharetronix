<?php 
	
	class csrf
	{
		private $token_area;
				
		public function __construct($token_area = 'dashboard')
		{
			$this->token_area = $token_area;
			
			$this->user		= & $GLOBALS['user'];
			
			if( !isset($this->user->sess[$this->token_area]) ){
				$this->user->sess[$this->token_area] = $this->generate_token();
			}
			elseif(!$this->check_if_valid($this->user->sess[$this->token_area]->value)){
				$this->user->sess[$this->token_area] = $this->generate_token();
			}
		}
		
		private function generate_token()
		{
			$token = new stdClass();
			
			$token->value = md5(uniqid(rand(), TRUE));
			$token->time = time();
			
			return $token;
		}
		
		public function check_if_valid($token)
		{
			if( !isset($this->user->sess[$this->token_area]) ){
				return false;
			}
			if( isset($this->user->sess[$this->token_area]->time) && ((time() - $this->user->sess[$this->token_area]->time) > 300) ){
				return false;
			}
			if(isset($this->user->sess[$this->token_area]->value) && $this->user->sess[$this->token_area]->value != $token){
				return false;
			}
			
			return true;
		}
		
		public function get_value()
		{
			if( isset($this->user->sess[$this->token_area]->value) ){
				return $this->user->sess[$this->token_area]->value;
			}
		}
	}