<?php
class TestConf
{
    const verbosity = 3; // 0-output nothing; 1-output end test results; 2-output individual test results; 3-output test details; 4-output urls; 5-output url return data; 6-output return data with NSLogs; 7-output everything

    const svnStatusFile = "configScripts/svnChanged.txt";
    const resultsTextFile = "human/results.txt";

    const sendMailOnFailure = true;
    const failureAlertee = "arisgames-dev@googlegroups.com";

    const tr_SUCCESS = 0;
    const tr_FAIL = 1;
    const tr_NA = 2;
}
?>
