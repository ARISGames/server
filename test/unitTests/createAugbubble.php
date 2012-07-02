<?php
require_once("test.php");

class createAugbubble extends Test
{
    public $dependancy = "createGame";

    private $augbubbleId = 0;

    private $gameId = 0; 
    private $name = "The Best Augbubble"; 
    private $description = "This is the best augbubble in the entire game."; 
    private $iconMediaId = 0; 

    public function solo()
    {
        $returnVal = $this->compareResults($this->gameId);
        $this->callFunction("augbubbles","deleteAugBubble",array($this->gameId, $this->augbubbleId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$gameId);
    }

    private function compareResults($gameId)
    {
	$result = $this->callFunction("augbubbles", "createAugBubble", array($gameId, $this->name, $this->description, $this->iconMediaId));
        if($this->parseReturnCode($result) == 0)
        {
            $this->augbubbleId = $this->parseData($result);
            Model::$augbubbleIds[] = $this->parseData($result);
            $this->testLog("Created Augbubble(".$this->augbubbleId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
