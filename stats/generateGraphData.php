<?php
include('config.class.php');

function monthsago($i)
{
	return "DATE_SUB(NOW(), INTERVAL $i MONTH)";
}

function getCountsForTable($t)
{
	$range = 24;
	$query = "SELECT SUM(created BETWEEN ".monthsago($range)." AND ".monthsago($range-1).") AS '{$range}_months_ago'";
	for($i = $range-1; $i > 0; $i--)
	{
		$query = $query . ", SUM(created BETWEEN ".monthsago($i)." AND ".monthsago($i-1).") AS '{$i}_months_ago'";
	}
	$query = $query." FROM '{$t}';";
	$result = mysql_query($query);
	$counts = mysql_fetch_object($result);
}

$sqlLink = mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass) or die('MySQL error: ' . mysql_error());
mysql_select_db('server') or die('MySQL error: ' . mysql_error());

$graphData->players = getCountsForTable("players");
$graphData->games = getCountsForTable("games");
$graphData->editors = getCountsForTable("editors");

$fileName = 'graphData.txt';
$fh = fopen($filename, 'w') or die("Cannot open file.");
fwrite($fh, json_encode($graphData));
fclose($fh);
?>
