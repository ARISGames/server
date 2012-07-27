<?php
class TestConf
{
    const verbosity = 3; // 0-output nothing; 1-output end test results; 2-output individual test results; 3-output test details; 4-output urls; 5-output url return data; 6-output return data with NSLogs; 7-output everything

    const sendMailOnFailure = true;
    const failureAlertee = "pdougherty@wisc.edu";

    const tr_SUCCESS = 0;
    const tr_FAIL = 1;
    const tr_NA = 2;

    const rootTestDirectory = "/var/www/html/server/test";
    const srcDirectory = "/var/www/html/server/test/src";//rootTestDirectory."/src/";
    const runTestsFile = "/var/www/html/server/test/src/run.php";//srcDirectory."/run.php";
    const resultsTextFile = "/var/www/html/server/test/human/results.txt";//rootTestDirectory."/human/results.txt";
}
?>
