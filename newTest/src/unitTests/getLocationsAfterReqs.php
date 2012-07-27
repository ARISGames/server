<?php
require_once("src/test.php");

class getLocationsAfterReqs extends Test
{
    public $dependancy = "setRequirementsForLocs";

    private $lat = -43;
    private $lon = 89;

    public function group()
    {
        return $this->compareResults(Model::$gameId, Model::$playerId);
    }

    private function compareResults($gameId, $playerId)
    {
	$result = $this->callFunction("locations", "getLocationsForPlayer", array($gameId, $playerId, $this->lat, $this->lon));
        if($this->parseReturnCode($result) === 0)
        {
            $locations = $this->parseData($result);
            foreach($locations as $location)
            {
                $this->testLog("Error: Found Location: Id-".$location->location_id." Type-".$location->type." TypeId-".$location->type_id.", and shouldn't have.");
                return new testResult(TestConf::tr_FAIL, "Found Location (".$location->location_id.") with requirements and shouldn't have.");
            }
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
        {
            return new testResult(TestConf::tr_FAIL, $result);
        }
    }
}
