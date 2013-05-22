<?php
require_once("src/test.php");

class createNpc extends Test
{
    public $dependancy = "createGame";

    private $npcId = 0;

    private $gameId = 0; 
    private $name = "Phil Dougherty"; 
    private $description = "A relatively awesome dude."; 
    private $greeting = "YO DAWWWWWWWWGG."; 
    private $closing = "ADIOS!"; 
    private $mediaId = 0; 
    private $iconMediaId = 0; 

    public function solo()
    {
        $returnVal = $this->compareResults($this->gameId);
        $this->callFunction("npcs","deleteNpc",array($this->gameId, $this->npcId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$gameId);
    }

    private function compareResults($gameId)
    {
	$result = $this->callFunction("npcs", "createNpc", array($gameId, $this->name, $this->description, $this->greeting, $this->closing, $this->mediaId, $this->iconMediaId));
        if($this->parseReturnCode($result) == 0)
        {
            $this->npcId = $this->parseData($result);
            Model::$npcIds[] = $this->parseData($result);
            $this->testLog("Created Npc(".$this->npcId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
