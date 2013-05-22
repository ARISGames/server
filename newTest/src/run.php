<?php
require_once('testConf.php');
require_once('testHelper.php');

echo "Updating svn...\n";
exec("svn update /var/www/html/server",$output);
echo "Complete: ";
echo $output[0];
echo("\n\nRunning testHelper...\n\n");

$testHelper = new TestHelper();
$testHelper->runAllTests();
$testHelper->outputResults();
?>
