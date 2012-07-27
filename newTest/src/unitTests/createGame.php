<?php
require_once("src/test.php");

class createGame extends Test
{
    public $dependancy = "createEditor";

    private $gameId = 0; 
    private $intEditorID = 0; 
    private $strFullName = "ARIS_UNIT_TESTING_GAME"; 
    private $strDescription = "This game was created as a result of unit testing for ARIS. If anyone can see this, something has gone horribly, horribly wrong."; 
    private $intPCMediaID = 0; 
    private $intIconMediaID = 0; 
    private $intMediaID = 0; 
    private $boolIsLocational = 1; 
    private $boolReadyForPublic = 0; 
    private $boolShareToMap = 1; 
    private $boolShareToBook = 1; 
    private $playerCreateTag = 1; 
    private $playerCreateComments = 1; 
    private $playerLikeNotes = 1; 
    private $intIntroNodeId = 0; 
    private $intCompleteNodeId = 0; 
    private $intInventoryCap = 0;

    public function solo()
    {
        $returnVal = $this->compareResults($this->intEditorID);
        $this->callFunction("games","deleteGame",array($this->gameId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$editorId);
    }

    private function compareResults($editorId)
    {
        $result = $this->callFunction("games", "createGame", array($editorId, $this->strFullName, $this->strDescription, $this->intPCMediaID, $this->intIconMediaID, $this->intMediaID, $this->boolIsLocational, $this->boolReadyForPublic, $this->boolShareToMap, $this->boolShareToBook, $this->playerCreateTag, $this->playerCreateComments, $this->playerLikeNotes, $this->intIntroNodeId, $this->intCompleteNodeId, $this->intInventoryCap));
        if($this->parseReturnCode($result) === 0) //Strict === to disallow confusion with ""
        {
            $this->gameId = $this->parseData($result);
            Model::$gameId = $this->parseData($result);
            $this->testLog("Created Game(".$this->gameId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else if($this->parseReturnCode($result) == 4)
        {
            $this->testLog("Duplicate name- deleting and re-calling...");
            $result = $this->callFunction("games", "deleteGame", array($this->parseData($result)));
            return $this->compareResults($editorId);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
