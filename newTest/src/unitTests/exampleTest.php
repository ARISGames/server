<?php
require_once("src/test.php"); //Ensure that this file is included

class exampleTest extends Test //and that it is extended here
{
    public $dependancy = ""; //No dependancy- will run at the root level of the test tree

    private $exampleId = 0; //Can set local variables if need be, or if it is convenient for similar implementations between solo and group

    //Implement this to be called on an individual basis (mustn't rely on other tests being run, nor use any data in the model)
    public function solo()
    {
        return $this->compareResults($this->exampleId);
    }

    //Implement this to be ran as a batch with the rest of the files. Make sure $dependancy is set if it relies on other test being run first!
    public function group()
    {
        return $this->compareResults(Model::$exampleId);
    }

    //Convenient to run same process for solo/group, just using different variables
    private function compareResults($exampleId)
    {
	$result = $this->callFunction("exampleClassFile", "exampleFunction", array($exampleId, "pass", "parameters", "here", "thisFunctionDoesn'tExist")); //Constructs URL and pings
        if($this->parseReturnCode($result) == 0) //Should imply that implementation of the function went smoothly- but this is the responsibility of the specific function called
        {
            $this->exampleId = $this->parseData($result); //Make sure you know what data you are expecting if you are doing this.
            Model::$exampleId = $this->parseData($result); //Put it in the model if you need it for other tests
            $this->testLog("Ran exampleTest Smoothly!"); //'testLog' will take care of formatting output based on verbosity level. Ouput test details with this function.
            return new testResult(TestConf::tr_SUCCESS, "Nothin' to say.", null); //Return testResultCode, description, and data if needed
        }
        else
            return new testResult(TestConf::tr_FAIL, $result);
    }
}
