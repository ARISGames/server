<?php
require_once("src/test.php");

class getQuestsBeforeReqs extends Test
{
    public $dependancy = "createQuests";

    public function group()
    {
        return $this->compareResults(Model::$gameId, Model::$playerId);
    }

    private function compareResults($gameId, $playerId)
    {
	$result = $this->callFunction("quests", "getQuestsForPlayer", array($gameId, $playerId));
	
        if($this->parseReturnCode($result) === 0)
        {
            $quests = $this->parseData($result);
            $active = $quests->active;
            $completed = $quests->completed;
            foreach($active as $quest)
                $this->testLog("Found Active Quest(".$quest->quest_id.")");
            foreach($completed as $quest)
                $this->testLog("Found Complete Quest(".$quest->quest_id.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
        {
            return new testResult(TestConf::tr_FAIL, $result);
        }
    }
}
