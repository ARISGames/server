<?php
/*
   return codes:
   0-success
   1-bad gameId
   2-
   3-SQL error
 */
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
