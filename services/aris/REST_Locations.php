<?php

require_once('config.class.php');
require_once('media.php');
require_once('locations.php');

$timeLimitInMinutes = 5;

$conn = mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
mysql_select_db (Config::dbSchema);
$prefix = $_REQUEST['gameId'];

$query = "SELECT * FROM {$prefix}_locations";
$locationsRs = @mysql_query($query);
if (mysql_error()) die ("<html><body>ERROR: Bad gameId</body></html>");


$xml = array('<?xml version="1.0" encoding="UTF-8"?>');
$xml[] = '<Locations>';

while ($location = @mysql_fetch_object($locationsRs)) {
	
	switch ($location->type) {
		case 'Item':
			$xml[] = '	<Item>';
			$xml[] = '		<Title>' . $location->name . '</Title>';
			$xml[] = '		<Latitude>' . $location->latitude . '</Latitude>';
			$xml[] = '		<Longitude>' . $location->longitude . '</Longitude>';
			$query = "SELECT {$prefix}_items.*, media.*, players.*
				FROM {$prefix}_items, media, players
				WHERE {$prefix}_items.media_id = media.media_id 
					AND {$prefix}_items.creator_player_id = players.player_id
					AND {$prefix}_items.item_id = {$location->type_id}";
			$rs = @mysql_query($query);
			$item = @mysql_fetch_object($rs);

			$xml[] = '		<Author>'.$item->user_name.'</Author>';
			$xml[] = '		<OriginLatitude>'.$item->origin_latitude.'</OriginLatitude>';
			$xml[] = '		<OriginLongitude>'.$item->origin_longitude.'</OriginLongitude>';
			$xml[] = '		<OriginTimestamp>'.$item->origin_timestamp.'</OriginTimestamp>';
			$mediaURL = Config::gamedataWWWPath . "/{$_REQUEST['gameId']}/{$item->file_name}";
			$xml[] = '		<MediaURL>'.$mediaURL.'</MediaURL>';
			$xml[] = '	</Item>';
			break;
		case 'Node':
			$xml[] = '	<Plaque>';
			$xml[] = '		<Title>' . $location->name . '</Title>';
			$xml[] = '		<Latitude>' . $location->latitude . '</Latitude>';
			$xml[] = '		<Longitude>' . $location->longitude . '</Longitude>';			
			$xml[] = '	</Plaque>';
			break;
		case 'Npc':
			$xml[] = '	<Npc>';
			$xml[] = '		<Title>' . $location->name . '</Title>';
			$xml[] = '		<Latitude>' . $location->latitude . '</Latitude>';
			$xml[] = '		<Longitude>' . $location->longitude . '</Longitude>';			
			$xml[] = '	</Npc>';
			break;
		default:
			//Shouldn't get here
	}
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
	




// End XML file
$xml[] = ' </Locations>';
$xmlOutput = join("\n", $xml);
echo $xmlOutput;



?>