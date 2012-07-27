<?php
require_once("test.php");

class createEditor extends Test
{
    private $editorId = 0;

    private $userName = "ARIS_TESTEREDITORTRON5000";
    private $pass = "TESTEREDITORTRON_PASSWORD";
    private $email = "ILIKEUSINGCAPSLOCK@CAPS4LYFE.COM";
    private $comments = "This is a good test editor robot.";

    public function solo()
    {
        $returnVal = $this->compareResults();
        $this->callFunction("editors","deleteEditor",array($this->userName, $this->pass));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults();
    }

    private function compareResults()
    {
	$result = $this->callFunction("editors", "createEditor", array($this->userName, $this->pass, $this->email, $this->comments));
        if($this->parseReturnCode($result) === 0)
        {
            $this->editorId = $this->parseData($result);
            Model::$editorId = $this->parseData($result);
            Model::$editorUsername = $this->userName;
            Model::$editorPassword = $this->pass;
            $this->testLog("Created Editor(".$this->editorId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else if(($this->parseReturnCode($result) == 4) || ($this->parseReturnCode($result) == 5))
        {
            $this->testLog("Duplicate name- deleting and re-calling...");
            $result = $this->callFunction("editors","deleteEditor",array($this->userName, $this->pass));
            return $this->compareResults();
        }
        else
        {
            return new testResult(TestConf::tr_FAIL, $result);
        }
    }
}
