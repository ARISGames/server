<?php
require_once("src/test.php");

class setRequirementsForLocs extends Test
{
    public $dependancy = "getLocationsBeforeReqs";

    public function group()
    {
        return $this->compareResults();
    }

    private function compareResults()
    {
        $locations = array_merge(Model::$itemLocationIds,Model::$npcLocationIds, Model::$plaqueLocationIds, Model::$webpageLocationIds, Model::$augbubbleLocationIds, Model::$noteLocationIds);
        $requirements = array('PLAYER_HAS_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_HAS_UPLOADED_MEDIA_ITEM','PLAYER_HAS_NOTE','PLAYER_HAS_COMPLETED_QUEST');
        //^ Random assortment of requirements

        $i = 0;
        foreach($locations as $location)
        {
            $this->testLog("Adding Requirement to Location: Id-".$location);
	    $result = $this->callFunction("requirements", "createRequirement", array(Model::$gameId, 'Location', $location, $requirements[$i%count($requirements)], 1, 1, 1, 1, "AND", "DO"));
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
