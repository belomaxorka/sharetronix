<?php

class FieldOptionLimit extends FieldOption {
	
	
	/**
	 * FieldOptionLimit
	 * 
	 * @access	public
	 * @param 	int		 $value
	 */
	public function __construct($value) {
		$this->FieldOptionLimit($value);
	}

	public function FieldOptionLimit($value){
		parent::FieldOption((int)$value);
	}
	
}