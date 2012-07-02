<?php
require_once('../config.class.php');
require_once('testConf.php');
require_once('testResult.php');

class TestHelper
{

    public $testNames = array();
    public $tests = array();
    public $organizerqueue = array();
    public $testsFound = 0;
    public $testsRun = 0;
    public $testsPassed = 0;
    public $testsFailed = 0;

    /**
     * Gets all run-able tests from unitTests folder, includes them, and stores them in an array
     * @constructor
     */
    public function __construct()
    {
        if (!function_exists('curl_init')) die('You must have cUrl installed to run these tests.');

        if($unitTests = opendir("./unitTests"))
        {
            while($unitTest = readdir($unitTests))
            {
                if($this->isValidTestFile($unitTest))
                {
                    $this->testsFound++;
                    require_once("./unitTests/".$unitTest);
                    $testName = substr($unitTest, 0, -4); //Gets name of file minus '.php'
                    $this->testNames[] = $testName;
                    $test = new $testName;
                    $this->tests[] = $test;
                }
            }
        }
        $this->organizeTestsByDependancyHeirarchy();
    }

    /*
     * Essentially an 'ignore' list 
     */
    private function isValidTestFile($name)
    {
        if($name == "" || $name == "." || $name == ".." || $name == ".svn" || $name == ".git" || $name == ".DS_Store" || $name == "exampleTest.php") return false;
        else return true;
    }

    private function organizeTestsByDependancyHeirarchy()
    {
        $root = new stdClass;
        $root->test = null;
        $root->name = "";
        $root->dependants = array();
        $this->getDependants($root); //Will recursively get all tests

        $this->tests = array();
        $this->organizerqueue = array();
        $this->organizerqueue[] = $root;
        while(count($this->organizerqueue) > 0)
            $this->bfDequeueToTestList();
    }

    private function getDependants($node)
    {
        foreach($this->tests as $test)
        {
            if($test->dependancy == $node->name)
            {
                $child = new stdClass;
                $child->test = $test;
                $child->name = get_class($test);
                $child->dependants = array();
                $this->getDependants($child);
                $node->dependants[] = $child;
            }
        }
    }

    private function bfDequeueToTestList()
    {
        $node = array_shift($this->organizerqueue);
        if($node->test != null) $this->tests[] = $node->test;
        foreach($node->dependants as $dependant)
            $this->organizerqueue[] = $dependant;
    }

    private function recreateTestArrayInOrderOfDependancies($node)
    {
        foreach($node->dependants as $dependant)
            $this->tests[] = $dependant->test;
        foreach($node->dependants as $dependant)
            $this->recreateTestArrayInOrderOfDependancies($dependant);
    }

    /**
     * Gets test from tests array if it exists. Otherwise, attempts to instantiate it.
     * @returns test
     */
    private function getTestByName($testName)
    {
        foreach($tests as $test)
        {
            if(get_class($test) == $testName)
                return $test;
        }
        return new $testName;
    }

    /**
     * Runs a test by test name (assumes test returns testResult object)
     * Calls function as prepared for running by itself, or as a group (using a global model)
     * @returns true/false for pass/fail
     */
    public function runTest($testName, $solo)
    {
        if(get_class($testName) == "string")
            $test = $this->getTestByName($testName);
        else
        {
            $test = $testName;
            $testName = get_class($test);
        }

        if($solo) $function = "solo";
        else $function = "group";

        $this->testsRun++;
        if(method_exists($test, $function))
        {
            if(TestConf::verbosity > 1)
                echo "  -Running '".$testName."'...\n";
            $testResult = call_user_func_array(array($test, $function), array());
            if($testResult->result == TestConf::tr_SUCCESS)
            {
                if(TestConf::verbosity > 1)
                    echo "  Test '".$testName."' Succeeded (".$testResult->description.")\n";
                $this->testsPassed++;
                return true;
            }
            else if($testResult->result == TestConf::tr_FAIL)
            {
                if(TestConf::verbosity > 1)
                    echo "x Test '".$testName."' Failed (".$testResult->description.")\n";
                $this->testsFailed++;
                return false;
            }
            else if($testResult->result == TestConf::tr_NA)
            {
                if(TestConf::verbosity > 1)
                    echo "  Test '".$testName."' Not Applicable (".$testResult->description.")\n";
                $this->testsRun--;
                return false;
            }
        }
        else
        {
            if(TestConf::verbosity > 1)
                echo "x Test '".$testName."' Failed- Invalid Test\n";
            $this->testsFailed++;
            return false;
        }
    }

    /**
     * Iterates through all found tests, and runs them
     */
    public function runAllTests()
    {
        foreach($this->tests as $test)
        {
            $this->runTest($test, false);
        }
    }

    /**
     * Prints test stats
     * @returns true
     */
    public function outputResults()
    {
        if(TestConf::verbosity > 0)
        {
            if(TestConf::verbosity > 1) echo "\n";
            echo "  Results:(".date("Y-m-d H:i:s").")\n";
            echo "  Tests Run- ".$this->testsRun."/".$this->testsFound."\n";
            echo "  Tests Passed- ".$this->testsPassed."/".$this->testsRun."\n";
            echo "  Tests Failed- ".$this->testsFailed."/".$this->testsRun."\n";
        }

        if(TestConf::sendMailOnFailure && $this->testsFailed > 0)
        {
            $body = "Click to see result details- ".Config::WWWPath."/server/test/".TestConf::resultsTextFile." <br />\n";
            $body .= "Click (copy to browser) to re-run test- view-source:".substr(Config::WWWPath, 7)."/server/test/index.php <br />\n";
            $this->sendEmail(TestConf::failureAlertee, "ARIS Unit Tests-".$this->testsFailed."/".$this->testsRun." failed", $body);
        }

        $file = TestConf::svnStatusFile;
        $fh = fopen($file, "w");
        fwrite($fh, "NO");
        fclose($fh);

        return true;
    }

    /**
     * Add a row to the server error log
     * @returns true
     */
    public function serverErrorLog($message)
    {
        $errorLogFile = fopen(Config::serverErrorLog, "a");
        $errorData = date('c') . ' "' . $message . '"' ."\n";
        fwrite($errorLogFile, $errorData);
        fclose($errorLogFile);
        return true;
    }

    /**
     * Sends an Email
     * @returns 0 on success
     */
    public function sendEmail($to, $subject, $body) 
    {
        include_once('../libraries/phpmailer/class.phpmailer.php');

        if (empty($to))
            return false;

        $mail = new phpmailer;
        $mail->PluginDir = '../libraries/phpmailer'; // plugin directory (eg smtp plugin)

        $mail->CharSet = 'UTF-8';
        $mail->Subject = substr(stripslashes($subject), 0, 900);
        $mail->From = 'noreply@arisgames.org';
        $mail->FromName = 'ARIS Mailer';

        $mail->AddAddress($to, 'ARIS Author');
        $mail->MsgHTML($body);

        $mail->WordWrap = 79; // set word wrap

        if ($mail->Send()) return true;
        else return false;
    }
}
?>
