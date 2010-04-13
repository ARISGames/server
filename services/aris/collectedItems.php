<?php

require_once('config.class.php');
$conn = mysql_pconnect(Config::dbHost, Config::dbUser, Config::dbPass);
mysql_select_db (Config::dbSchema);
$prefix = $_REQUEST['gameId'];
$query = "SELECT {$prefix}_items.*, media.*
			FROM {$prefix}_items, media
			WHERE {$prefix}_items.media_id = media.media_id";
$result = mysql_query($query);


// Creates an array of strings to hold the lines of the KML file.
$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
$kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
$kml[] = ' <Document>';


// Iterates through the rows, printing a node for each row.
while ($row = @mysql_fetch_assoc($result)) 
{
  $kml[] = ' <Placemark id="placemark' . $row['item_id'] . '">';
  $kml[] = ' <name>' . htmlentities($row['name']) . '</name>';
  
  $mediaURL = Config::gamedataWWWPath . "/{$_REQUEST['gameId']}/{$row['file_name']}";
  $mediaImg = "<a href = '{$mediaURL}'><img src = '$mediaURL' width = '100%'/></a>";
  
  $kml[] = ' <description>' . htmlentities($row['description']) . '<br/>' . $mediaImg . '</description>';
  $kml[] = ' <styleUrl>#' . 1 .'Style</styleUrl>';
  $kml[] = ' <Point>';
  $kml[] = ' <coordinates>' . $row['origin_longitude'] . ','  . $row['origin_latitude'] . '</coordinates>';
  $kml[] = ' </Point>';
  $kml[] = ' </Placemark>';
 
} 

// End XML file
$kml[] = ' </Document>';
$kml[] = '</kml>';
$kmlOutput = join("\n", $kml);
header('Content-type: application/vnd.google-earth.kml+xml');
echo $kmlOutput;


?>