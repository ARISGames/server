<?php
class TestResult
{
	public $result;
	public $description;
	public $data;

	public function TestResult($result = 0, $description = "No Description", $data = NULL){
		$this->result = $result;
		$this->description = $description;
		$this->data = $data;

		return $this;
	}
}
?>
