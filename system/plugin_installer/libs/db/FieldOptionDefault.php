<?php
class FieldOptionDefault extends FieldOption {
	
	/**
	 * FieldOptionDefault	
	 * 
	 * @access	public
	 * @param 	mixed	 $value
	 */
	public function __construct($value) {
		$this->FieldOptionDefault($value);
	}

	public function FieldOptionDefault($value) {
		parent::FieldOption($value);
	}
	
}