<?php
require_once("test.php");

class createPlaque extends Test
{
    public $dependancy = "createGame";

    private $nodeId = 0;

    private $gameId = 0; 
    private $title = "Check out this plaque yo!"; 
    private $text = "ITS SOOOOO COOOL"; 
    private $mediaId = 0; 
    private $iconMediaId = 0; 
    private $opt1Text = "Option One?";
    private $opt2Text = "Option Two?";
    private $opt3Text = "Option Three?";
    private $opt1NodeId = 2;
    private $opt2NodeId = 3;
    private $opt3NodeId = 4;
    private $correctAnswer = "Yes.";
    private $incorrectNodeId = 5;
    private $correctNodeId = 6;

    public function solo()
    {
        $returnVal = $this->compareResults($this->gameId);
        $this->callFunction("nodes","deleteNode",array($this->gameId, $this->nodeId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$gameId);
    }

    private function compareResults($gameId)
    {
	$result = $this->callFunction("nodes", "createNode", array($gameId, $this->title, $this->text, $this->mediaId, $this->iconMediaId, $this->opt1Text, $this->opt1NodeId, $this->opt2Text, $this->opt2NodeId, $this->opt3Text, $this->opt3NodeId, $this->correctAnswer, $this->incorrectNodeId, $this->correctNodeId));
        if($this->parseReturnCode($result) == 0)
        {
            $this->nodeId = $this->parseData($result);
            Model::$plaqueIds[] = $this->parseData($result);
            $this->testLog("Created Plaque(".$this->nodeId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
