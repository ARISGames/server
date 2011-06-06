<?php

class ReturnData {

	public $data;
	public $returnCode;
	public $returnCodeDescription;
	
	
	public function ReturnData($returnCode, $data = NULL, $returnCodeDescription=NULL){
		$this->data = $data;
		$this->returnCode = $returnCode;
		$this->returnCodeDescription = $returnCodeDescription;
		
		return $this;
	}
}
	
?>