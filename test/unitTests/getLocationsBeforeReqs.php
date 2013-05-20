<?php
require_once("test.php");

class getLocationsBeforeReqs extends Test
{
    public $dependancy = "createItemLocations";

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
                $this->testLog("Found Location: Id-".$location->location_id." Type-".$location->type." TypeId-".$location->type_id);
            }
        // EXPECTED OUTPUT-
        //{"data":[{"location_id":"1","name":"Check out this location!","description":"","latitude":"-43","longitude":"89","error":"20","type":"Node","type_id":"1","icon_media_id":"0","item_qty":"7","hidden":"0","force_view":"0","allow_quick_travel":"1","wiggle":"1","show_title":"0","spawnstamp":"2012-06-29 11:55:22","spawnable_id":null,"active":null,"delete_when_viewed":0},{"location_id":"2","name":"Check out this location!","description":"","latitude":"-43","longitude":"89","error":"20","type":"AugBubble","type_id":"275","icon_media_id":"0","item_qty":"7","hidden":"0","force_view":"0","allow_quick_travel":"1","wiggle":"1","show_title":"0","spawnstamp":"2012-06-29 11:55:22","spawnable_id":null,"active":null,"delete_when_viewed":0},{"location_id":"3","name":"Check out this location!","description":"","latitude":"-43","longitude":"89","error":"20","type":"Npc","type_id":"1","icon_media_id":"0","item_qty":"7","hidden":"0","force_view":"0","allow_quick_travel":"1","wiggle":"1","show_title":"0","spawnstamp":"2012-06-29 11:55:22","spawnable_id":null,"active":null,"delete_when_viewed":0},{"location_id":"4","name":"Check out this location!","description":"","latitude":"-43","longitude":"89","error":"20","type":"Item","type_id":"1","icon_media_id":"0","item_qty":"7","hidden":"0","force_view":"0","allow_quick_travel":"1","wiggle":"1","show_title":"0","spawnstamp":"2012-06-29 11:55:22","spawnable_id":null,"active":null,"delete_when_viewed":0},{"location_id":"5","name":"Check out this location!","description":"","latitude":"-43","longitude":"89","error":"20","type":"WebPage","type_id":"476","icon_media_id":"0","item_qty":"7","hidden":"0","force_view":"0","allow_quick_travel":"1","wiggle":"1","show_title":"0","spawnstamp":"2012-06-29 11:55:22","spawnable_id":null,"active":null,"delete_when_viewed":0}],"returnCode":0,"returnCodeDescription":null}
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
        {
            return new testResult(TestConf::tr_FAIL, $result);
        }
    }
}
