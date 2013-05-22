<?php
chdir("/var/www/html/server/newTest");
require_once("src/testConf.php");
exec("php ".TestConf::runTestsFile." > ".TestConf::resultsTextFile);

$file_handle = fopen(TestConf::resultsTextFile, "r");
while (!feof($file_handle)) 
{
   $line = fgets($file_handle);
    echo $line;
}
fclose($file_handle);
?>
