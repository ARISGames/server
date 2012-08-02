<?php
require_once('/var/www/html/server/config.class.php');
class TestConf
{
    const verbosity = 3; // 0-output nothing; 1-output end test results; 2-output individual test results; 3-output test details; 4-output urls; 5-output url return data; 6-output return data with NSLogs; 7-output everything

    const sendMailOnSuccess = true;
    const sendMailOnFailure = true;
    const successAlertee = "arisgames-dev@googlegroups.com";
    const failureAlertee = "arisgames-dev@googlegroups.com";

    const tr_SUCCESS = 0;
    const tr_FAIL = 1;
    const tr_NA = 2;
                                                                                     //String concatenation is funky with constants... below is what all the strings SHOULD be...
    const rootTestDirectory = "/var/www/html/server/newTest";
    const wwwTestDirectory = "dev.arisgames.org";                                    //Config::WWWPath."/server/newTest";

    const srcDirectory = "/var/www/html/server/newTest/src";                         //rootTestDirectory."/src/";
    const unitTestsDir = "/var/www/html/server/newTest/src/unitTests";               //srcDirectory."/unitTests";
    const runTestsFile = "/var/www/html/server/newTest/src/run.php";                 //srcDirectory."/run.php";
    const resultsTextFile = "/var/www/html/server/newTest/human/results.txt";        //rootTestDirectory."/human/results.txt";

    const wwwResultsTextFile = "dev.arisgames.org/server/newTest/human/results.txt";  //wwwTestDirectory."/human/results.txt";
    const wwwRunTestsFile = "dev.arisgames.org/server/newTest/src/run.php";           //wwwTestDirectory."/src/run.php";
}
?>
