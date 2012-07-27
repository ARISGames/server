<?php
require_once("test.php");

class createItem extends Test
{
    public $dependancy = "createGame";

    private $itemId = 0;

    private $gameId = 0; 
    private $name = "The Best Item"; 
    private $description = "This is the best item in the entire game."; 
    private $mediaId = 0; 
    private $iconMediaId = 0; 
    private $droppable = 1;
    private $destroyable = 1;
    private $attribute = 0;
    private $maxQuantityInPlayerInventory = 10;
    private $weight = 100;
    private $url = "www.phildogames.com";
    private $type = "URL";

    public function solo()
    {
        $returnVal = $this->compareResults($this->gameId);
        $this->callFunction("items","deleteItem",array($this->gameId, $this->itemId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$gameId);
    }

    private function compareResults($gameId)
    {
	$result = $this->callFunction("items", "createItem", array($gameId, $this->name, $this->description, $this->iconMediaId, $this->mediaId, $this->droppable, $this->destroyable, $this->attribute, $this->maxQuantityInPlayerInventory, $this->weight, $this->url, $this->type));
        if($this->parseReturnCode($result) == 0)
        {
            $this->itemId = $this->parseData($result);
            Model::$itemIds[] = $this->parseData($result);
            $this->testLog("Created Item(".$this->itemId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
