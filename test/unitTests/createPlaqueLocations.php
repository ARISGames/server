<?php
require_once("test.php");

class createPlaqueLocations extends Test
{
    public $dependancy = "createPlaque";

    private $locationId = 0;

    private $gameId = 0; 
    private $nodeId = 0; 
    private $name = "Check out this location!"; 
    private $iconMediaId = 0; 
    private $lat = -43; 
    private $lon = 89; 
    private $error = 20; 
    private $qty = 7; 
    private $hidden = 0; 
    private $forceView = 0; 
    private $allowQuickTravel = 1; 
    private $wiggle = 1; 
    private $qrCode = "APlaqueLoc"; 
    private $imageMatchId = 0; 
    private $errorText = "Sorry son!"; 

    public function solo()
    {
        $returnVal = $this->compareResults($this->gameId);
        $this->callFunction("locations","deleteLocation",array($this->gameId, $this->nodeId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$gameId, Model::$plaqueIds[0]);
    }

    private function compareResults($gameId, $nodeId)
    {
	$result = $this->callFunction("locations","createLocationWithQrCode",array($gameId, $this->name, $this->iconMediaId, $this->lat, $this->lon, $this->error, 'Node', $nodeId, $this->qty, $this->hidden, $this->forceView, $this->allowQuickTravel, $this->wiggle, $this->qrCode, $this->imageMatchId, $this->errorText));
        if($this->parseReturnCode($result) == 0)
        {
            $this->locationId = $this->parseData($result);
            Model::$plaqueLocationIds[] = $this->parseData($result);
            $this->testLog("Created Plaque(".$nodeId.") Location(".$this->locationId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
