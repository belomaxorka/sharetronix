<?php
class FieldOptionNull extends FieldOption {
	
	/**
	 * FieldOptionNull
	 * 
	 * @access	public
	 * @param 	bool	 $value
	 */
	public function __construct($value) {
		$this->FieldOptionNull($value);
	}

	public function FieldOptionNull($value){
		parent::FieldOption((bool)$value);
	}
}