<?php
require_once("test.php");

class setRequirementsForQuests extends Test
{
    public $dependancy = "getQuestsBeforeReqs";

    public function group()
    {
        return $this->compareResults();
    }

    private function compareResults()
    {
        $requirements = array('PLAYER_HAS_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_HAS_UPLOADED_MEDIA_ITEM','PLAYER_HAS_NOTE','PLAYER_HAS_COMPLETED_QUEST');
        //^ Random assortment of requirements

        $i = 0;
        foreach(Model::$questIds as $questId)
        {
            $this->testLog("Adding Requirement to Quest: Id-".$questId);
	    $result = $this->callFunction("requirements", "createRequirement", array(Model::$gameId, 'QuestComplete', $questId, $requirements[$i%count($requirements)], 1, 1, 1, 1, "AND", "DO"));
            if($this->parseReturnCode($result) === 0)
            {
                $this->testLog("Success(".$this->parseData($result).")");
            }
            else
            {
                return new testResult(TestConf::tr_FAIL, $result);
            }
            $i++;
        }

        return new testResult(TestConf::tr_SUCCESS);
    }
}
