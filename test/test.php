<?php
require_once("testConf.php");
require_once("testResult.php");
require_once("model.php");

abstract class Test
{
    public $dependancy = "";

    protected function callFunction($class, $function, $params)
    {
        $paramString = "/";
        foreach($params as $param)
        {
            $paramString.=urlencode($param)."/";
        }
        $url = Config::WWWPath."/server/json.php/v1.".$class.".".$function.$paramString;
        if(TestConf::verbosity > 3)
            echo "\tCalling URL: ".$url."\n";
        if (!function_exists('curl_init')) die('You must have cUrl installed to run this test.');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, Config::WWWPath.'/server/test');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);
        if(TestConf::verbosity == 5)
            echo "\t\tResult: ".$this->stripComments($output)."\n";
        if(TestConf::verbosity > 5)
            echo "\t\tResult: ".$output."\n";
        return $output;
    }

    protected function testLog($string, $tab = true, $newline = true)
    {
        if(TestConf::verbosity < 3) return;
        if($tab) echo "\t";
        echo $string;
        if($newline) echo "\n";
    }

    protected function stripComments($JSONreturnString)
    {
        if(strpos($JSONreturnString, "/*") !== false)
            $JSONreturnString = substr($JSONreturnString, strpos($JSONreturnString, "*/")+2);
        return substr($JSONreturnString, strpos($JSONreturnString, "{"));
    }

    protected function parseFault($JSONreturnString)
    {
        return @json_decode($this->stripComments($JSONreturnString))->faultCode;
    }

    protected function parseData($JSONreturnString)
    {
        return @json_decode($this->stripComments($JSONreturnString))->data;
    }

    protected function parseReturnCode($JSONreturnString)
    {
        return @json_decode($this->stripComments($JSONreturnString))->returnCode;
    }

    protected function parseReturnCodeDescription($JSONreturnString)
    {
        return @json_decode($this->stripComments($JSONreturnString))->returnCodeDescription;
    }
    
    public function solo()
    {
        return new testResult(TestResult::tr_NA, "Solo test not implemented");
    }

    public function group()
    {
        return new testResult(TestResult::tr_NA, "Group test not implemented");
    }
}
?>
