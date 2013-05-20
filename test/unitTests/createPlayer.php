<?php
require_once("test.php");

class createPlayer extends Test
{
    private $playerId = 0;

    private $userName = "ARIS_TESTERTRON_5000";
    private $pass = "TESTERTRON_PASSWORD";
    private $first = "TESTER";
    private $last = "TRON";
    private $email = "ILIKEUSINGCAPSLOCK@CAPS4LYFE.COM";

    public function solo()
    {
        $returnVal = $this->compareResults();
        $this->callFunction("players","deletePlayer",array($this->userName, $this->pass));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults();
    }

    private function compareResults()
    {
	$result = $this->callFunction("players", "createPlayer", array($this->userName, $this->pass, $this->first, $this->last, $this->email));
        if($this->parseReturnCode($result) === 0)
        {
            $this->playerId = $this->parseData($result);
            Model::$playerId = $this->parseData($result);
            Model::$playerUsername = $this->userName;
            Model::$playerPassword = $this->pass;
            $this->testLog("Created Player(".$this->playerId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else if($this->parseReturnCode($result) == 1)
        {
            $this->testLog("Duplicate name- deleting and re-calling...");
            $result = $this->callFunction("players","deletePlayer",array($this->userName, $this->pass));
            return $this->compareResults();
        }
        else
        {
            return new testResult(TestConf::tr_FAIL, $result);
        }
    }
}
