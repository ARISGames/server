<?php
require_once("test.php");

class createWebpage extends Test
{
    public $dependancy = "createGame";

    private $webpageId = 0;

    private $gameId = 0; 
    private $name = "The Best Webpage"; 
    private $iconMediaId = 0; 
    private $url = "www.phildogames.com";

    public function solo()
    {
        $returnVal = $this->compareResults($this->gameId);
        $this->callFunction("webpages","deleteWebpage",array($this->gameId, $this->webpageId));
        return $returnVal;
    }
    public function group()
    {
        return $this->compareResults(Model::$gameId);
    }

    private function compareResults($gameId)
    {

	$result = $this->callFunction("webpages", "createWebpage", array($gameId, $this->name, $this->url, $this->iconMediaId));
        if($this->parseReturnCode($result) == 0)
        {
            $this->webpageId = $this->parseData($result);
            Model::$webpageIds[] = $this->parseData($result);
            $this->testLog("Created Webpage(".$this->webpageId.")");
            return new testResult(TestConf::tr_SUCCESS);
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
