<?php

require_once('../../config.class.php');
require_once('media.php');
require_once('locations.php');

$timeLimitInMinutes = 5;

$conn = mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
mysql_select_db (Config::dbSchema);

$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
$kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
$kml[] = ' <Document>';

$xml = array('<?xml version="1.0" encoding="UTF-8"?>');
$xml[] = '<games>';

$csv = array('gameId, name, playerCount, logCount, nodeCount,itemCount,npcCount,locationCount,totalGameObjectCount,latitude,longitude');

$q = "SELECT * from games";
$gamesRs = mysql_query($q);

while ($gameObject = mysql_fetch_object($gamesRs)) {
	$gameId = $gameObject->game_id;
	$gameName = $gameObject->name;
	$gameDescription = $gameObject->description;

	//Calc the log count
	$q = "SELECT COUNT(DISTINCT id) AS count FROM player_log WHERE game_id = {$gameId}";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$logCount = $obj->count;
	if ($_REQUEST['minLogCount'] > $logCount) continue;

	
	//Calc the player log count
	$q = "SELECT COUNT(DISTINCT player_id) AS count FROM player_log WHERE game_id = {$gameId}";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$playerCount = $obj->count;
	if ($_REQUEST['minPlayerCount'] > $playerCount) continue;

	//Calc the node count
	$q = "SELECT COUNT(DISTINCT node_id) AS count FROM {$gameId}_nodes";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$nodeCount = $obj->count;	

	//Calc the item count
	$q = "SELECT COUNT(DISTINCT item_id) AS count FROM {$gameId}_items";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$itemCount = $obj->count;		
	
	//Calc the npc count
	$q = "SELECT COUNT(DISTINCT npc_id) AS count FROM {$gameId}_npcs";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$npcCount = $obj->count;		

	//Calc the loc count
	$q = "SELECT COUNT(DISTINCT location_id) AS count FROM {$gameId}_locations";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$locationCount = $obj->count;
	
	$totalObjectCount = $nodeCount + $itemCount + $npcCount + $locationCount;
	if ($_REQUEST['minObjectCount'] > $totalObjectCount) continue;
	
	//Calc the first Location
	$q = "SELECT latitude, longitude FROM {$gameId}_locations LIMIT 1";
	$rs = mysql_query($q);
	$obj = mysql_fetch_object($rs);
	$latitude = $obj->latitude;
	$longitude = $obj->longitude;

	$kml[] = ' <Placemark id="placemark' . $gameId . '">';
  	$kml[] = ' <name><![CDATA[' . $gameName . ']]></name>';
  	$description = array("<![CDATA[");
  	$description[] = '<p><strong>Description:</strong>' . $gameDescription . '</p>';
  	$description[] = '<p><strong>Player Count:</strong>' . $playerCount . '</p>';
	$description[] = '<p><strong>PLog Count:</strong>' . $logCount . '</p>';
	$description[] = '<p><strong>Node Count:</strong>' . $nodeCount . '</p>';
	$description[] = '<p><strong>Item Count:</strong>' . $itemCount . '</p>';
	$description[] = '<p><strong>NPC Count:</strong>' . $npcCount . '</p>';
	$description[] = '<p><strong>Location Count:</strong>' . $locationCount . '</p>';
	$description[] = '<p><strong>Total Object Count:</strong>' . $totalObjectCount . '</p>';

  	$description[] = "]]>";
  	$descriptionHtml = join("\n", $description);
	$kml[] = ' <description>' . $descriptionHtml . '</description>';
  	$kml[] = ' <Point>';
  	$kml[] = ' <coordinates>' . $longitude . ','  . $latitude . '</coordinates>';
  	$kml[] = ' </Point>';
  	$kml[] = ' </Placemark>';
	
	if ($locationCount > 0) {
		$xml[] = '<game>';
		$xml[] = '	<id>' . $gameId . '</id>';
		$xml[] = '	<name><![CDATA[' . $gameName . ']]></name>';
		$xml[] = '	<playerCount>' . $playerCount . '</playerCount>';
		$xml[] = '	<logCount>' . $logCount . '</logCount>';
		$xml[] = '	<nodeCount>' . $nodeCount . '</nodeCount>';
		$xml[] = '	<itemCount>' . $itemCount . '</itemCount>';
		$xml[] = '	<npcCount>' . $npcCount . '</npcCount>';
		$xml[] = '	<locationCount>' . $locationCount . '</locationCount>';
		$xml[] = '	<totalCount>' . $totalObjectCount . '</totalCount>';
		$xml[] = '</game>';
	}
	
	$csv[] = 	'"' . $gameId . '","' . $gameName  . '","' . $playerCount . '","' .  
				$logCount . '","' .  $nodeCount . '","' .  $itemCount . '","' .  
				$npcCount . '","' .  $locationCount . '","' . $totalObjectCount . '","' .  $latitude . '","' .  $longitude . '"';
}

switch ($_REQUEST['type']) {
	case 'kml':
		//Construct KML
		$kml[] = ' </Document>';
		$kml[] = '</kml>';
		$kmlOutput = join("\n", $kml);
		header('Content-type: application/vnd.google-earth.kml+xml');
		header('Content-Disposition: attachment; filename="ARISGames.kml"');
		echo $kmlOutput;
		break;

	case 'xml':
		//Construct XML
		$xml[] = ' </games>';
		$xmlOutput = join("\n", $xml);
		header('Content-type: text/xml');
		header('Content-Disposition: attachment; filename="ARISGames.xml"');
		echo $xmlOutput;
		break;
	
	case 'csv':
		//Construct CSV
		$csvOutput = join("\n", $csv);
		header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="ARISGames.csv"');
		echo $csvOutput;
		break;
		
	default:
		echo 'Please add a "type" GET variable of "kml","xml" or "csv"';
}



?>