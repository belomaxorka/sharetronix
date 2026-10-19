<?php
	
	class curlCall
	{
		private $_ch;
		
		public function __construct( $url )
		{
			$this->_ch = curl_init();
			curl_setopt($this->_ch, CURLOPT_URL, $url);
			curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($this->_ch, CURLOPT_MAXREDIRS, 5);
			curl_setopt($this->_ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($this->_ch, CURLOPT_AUTOREFERER, TRUE);
		}
		
		public function excludeHeader()
		{
			curl_setopt($this->_ch, CURLOPT_HEADER, FALSE);
		}
		
		public function excludeBody()
		{
			curl_setopt($this->_ch, CURLOPT_NOBODY, FALSE);
		}
		
		public function getData()
		{
			return curl_exec($this->_ch);
		}
		
		public function setTimeouts( $connect, $total )
		{
			curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $connect);
			curl_setopt($this->_ch, CURLOPT_TIMEOUT, $total);
		}
		
		// HTTP status of the last response, 0 when there was none.
		public function getStatus()
		{
			return (int) curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
		}
		
		// cURL error message of the last request, '' when it succeeded.
		public function getError()
		{
			return curl_error($this->_ch);
		}
		
		public function sslSupport( $ssl_support = TRUE )
		{
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $ssl_support);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, $ssl_support);
		}
		
		public function addPostData( $fields = array(), $as_array = TRUE )
		{
			$fields_count = count($fields);
			
			if( !$as_array ){
				$fields_data = http_build_query($fields, '', '&');
			}else{
				$fields_data = $fields;
			}
			
			
			curl_setopt($this->_ch,CURLOPT_POST, $fields_count);
			curl_setopt($this->_ch,CURLOPT_POSTFIELDS, $fields_data);
		}
		
		public function __destruct()
		{
			// Since PHP 8.0 the handle is an object, freed with its last reference; curl_close() has
			// no effect there and is deprecated as of 8.5.
			if( PHP_VERSION_ID < 80000 ) {
				curl_close($this->_ch);
			}
		}
	}