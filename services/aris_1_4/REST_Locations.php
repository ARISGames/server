<?php

require_once('../../config.class.php');
require_once('media.php');
require_once('locations.php');

$timeLimitInMinutes = 5;

$conn = mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
mysql_select_db (Config::dbSchema);
$prefix = $_REQUEST['gameId'];

$query = "SELECT * FROM {$prefix}_locations";
$locationsRs = @mysql_query($query);
if (mysql_error()) die ("<html><body>ERROR: Bad gameId</body></html>");

$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
$kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
$kml[] = ' <Document>';

$xml = array('<?xml version="1.0" encoding="UTF-8"?>');
$xml[] = '<Locations>';

while ($location = @mysql_fetch_object($locationsRs)) {
	$kml[] = ' <Placemark id="location' . $location->location_id . '">';
  	$kml[] = ' <name>' . htmlentities($location->name) . '</name>';
	$kmlDescription = array("<![CDATA[");

	switch ($location->type) {
		case 'Item':
			
			$xml[] = '	<Item>';
			$xml[] = '		<Title>' . $location->name . '</Title>';
			$xml[] = '		<Latitude>' . $location->latitude . '</Latitude>';
			$xml[] = '		<Longitude>' . $location->longitude . '</Longitude>';
			
			$query = "SELECT {$prefix}_items.*, media.*, players.*
				FROM {$prefix}_items, media, players
				WHERE {$prefix}_items.media_id = media.media_id 
					AND {$prefix}_items.item_id = {$location->type_id}";
					
			error_log($query, 0);
			$rs = @mysql_query($query);
			$item = @mysql_fetch_object($rs);
			
			$mediaURL = Config::gamedataWWWPath . "/{$_REQUEST['gameId']}/{$item->file_name}";
  
			$mediaObject = new Media;
			$type = $mediaObject->getMediaType($mediaURL);
			
			if ($type == Media::MEDIA_IMAGE) $mediaHtml = "<a target = '_blank' href = '{$mediaURL}'><img src = '$mediaURL' width = '100%'/></a>";
			else if ($type == Media::MEDIA_AUDIO) $mediaHtml = "<a target = '_blank' href = '{$mediaURL}'>Link to Audio</a>"; 
			else if ($type == Media::MEDIA_VIDEO) $mediaHtml = '<div style="margin-left: auto; margin-right:auto;">
					  <object height="175" width="212">
						<param value="' . $mediaURL . '" name="movie">
						<param value="transparent" name="wmode">
						<embed wmode="transparent" type="application/x-shockwave-flash"
						   src="'.$mediaURL.'" height="175"
						   width="212">
					   </object>
					 </div>';
			$kmlDescription[] = "<strong>Type:</strong> Item<br/>";
			$kmlDescription[] = "<strong>Created By:</strong> {$item->user_name}<br/>";
			$kmlDescription[] = "<strong>Date:</strong> {$item->origin_timestamp}<br/>";
			$kmlDescription[] = "<p>{$item->description}</p>";
			$kmlDescription[] = $mediaHtml;

			

			$xml[] = '		<Author>'.$item->user_name.'</Author>';
			$xml[] = '		<OriginLatitude>'.$item->origin_latitude.'</OriginLatitude>';
			$xml[] = '		<OriginLongitude>'.$item->origin_longitude.'</OriginLongitude>';
			$xml[] = '		<OriginTimestamp>'.$item->origin_timestamp.'</OriginTimestamp>';
			$mediaURL = Config::gamedataWWWPath . "/{$_REQUEST['gameId']}/{$item->file_name}";
			$xml[] = '		<MediaURL>'.$mediaURL.'</MediaURL>';
			$xml[] = '	</Item>';
			break;
		case 'Node':
			$kmlDescription[] = "<strong>Type:</strong> Plaque<br/>";
		
			$xml[] = '	<Plaque>';
			$xml[] = '		<Title>' . $location->name . '</Title>';
			$xml[] = '		<Latitude>' . $location->latitude . '</Latitude>';
			$xml[] = '		<Longitude>' . $location->longitude . '</Longitude>';			
			$xml[] = '	</Plaque>';
			break;
		case 'Npc':
			$kmlDescription[] = "<strong>Type:</strong> Npc<br/>";
		
			$xml[] = '	<Npc>';
			$xml[] = '		<Title>' . $location->name . '</Title>';
			$xml[] = '		<Latitude>' . $location->latitude . '</Latitude>';
			$xml[] = '		<Longitude>' . $location->longitude . '</Longitude>';			
			$xml[] = '	</Npc>';
			break;
		default:
			//Shouldn't get here
	}
	
	$kmlDescription[] = "]]>";
	$kmlDescription = join("\n", $kmlDescription);
	
	$kml[] = ' <description>' . $kmlDescription . '</description>';
	$kml[] = ' <Point>';
	$kml[] = ' <coordinates>' . $row['origin_longitude'] . ','  . $row['origin_latitude'] . '</coordinates>';
	$kml[] = ' </Point>';
	$kml[] = ' </Placemark>';
}

		
$query = "SELECT players.player_id, players.user_name, 
				players.latitude, players.longitude, 
				player_log.timestamp 
				FROM players, player_log
				WHERE 
				players.player_id = player_log.player_id AND
				players.last_game_id = '{$_REQUEST['gameId']}' AND
				UNIX_TIMESTAMP( NOW( ) ) - UNIX_TIMESTAMP( player_log.timestamp ) <= ( $timeLimitInMinutes * 60 )
				GROUP BY player_id
				";
				
//echo $query;				

$playersRs = @mysql_query($query);
while ($player = @mysql_fetch_object($playersRs)) {
	$xml[] = '	<Player>';
	$xml[] = '		<Name>' . $player->user_name . '</Name>';
	$xml[] = '		<Latitude>' . $player->latitude . '</Latitude>';
	$xml[] = '		<Longitude>' . $player->longitude . '</Longitude>';			
	$xml[] = '	</Player>';
}
	

switch ($_REQUEST['type']) {
	case 'kml':
		// End KML file
		$kml[] = ' </Document>';
		$kml[] = '</kml>';
		$kmlOutput = join("\n", $kml);
		header('Content-type: application/vnd.google-earth.kml+xml');
		header('Content-Disposition: attachment; filename="ARISLocations.kml"');
		echo $kmlOutput;
		break;
	case 'xml':
		// End XML file
		$xml[] = ' </Locations>';
		$xmlOutput = join("\n", $xml);
		header('Content-type: text/xml');
		header('Content-Disposition: attachment; filename="ARISLocations.xml"');
		echo $xmlOutput;
		break;
	default:
		echo 'Please add a "type" GET variable of "kml" or "xml"';	
}


?>