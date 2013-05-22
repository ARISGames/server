<?php
require_once('testConf.php');
require_once('testHelper.php');

$file = TestConf::svnStatusFile;
$fh = fopen($file, "r");
$contents = fread($fh, filesize($file));
fclose($fh);

if(strpos($contents, "YES") !== false)
{
    $testHelper = new TestHelper();
    $testHelper->runAllTests();
    $testHelper->outputResults();
}

?>
