<?php
require_once("test.php");

class createQuests extends Test
{
    public $dependancy = "createItemLocations";

    private $questId;
    private $questName = "TheQuest";
    private $activeDescription = "You've Activated It!";
    private $completeDescription = "You've Done It!";
    private $iconMediaId = 0;
    private $index = 0;

    public function group()
    {
        return $this->compareResults(Model::$gameId);
    }

    private function compareResults($gameId)
    {
	$result = $this->callFunction("quests", "createQuest", array($gameId, $this->questName, $this->activeDescription, $this->completeDescription, $this->iconMediaId, $this->index));
	
        if($this->parseReturnCode($result) === 0)
        {
            $this->questId = $this->parseData($result);
            Model::$questIds[] = $this->parseData($result);
            $this->testLog("Created Quest(".$this->questId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
        {
            return new testResult(TestConf::tr_FAIL, $result);
        }
    }
}
