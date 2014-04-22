<?php
require_once("module.php");
require_once("items.php");
require_once("notes.php");
require_once("media.php");

class Players extends Module
{
	public function startOverGameForPlayer($gameId, $playerId)
	{	
                $debugString = "";
            
                $debugString .= $gameId ." ". $playerId ." DELETE PLAYER_ITEMS: ";
                $sTime = microtime(true);
		Module::query("DELETE FROM player_items WHERE game_id = {$gameId} AND player_id = '{$playerId}'");
                $debugString .=(microtime(true)-$sTime)."\n";

                $debugString .= $gameId ." ". $playerId ." DELETE PLAYER_LOG: ";
                $sTime = microtime(true);
		Module::query("UPDATE player_log SET deleted = 1 WHERE player_id = '{$playerId}' AND game_id = '{$gameId}'");
                $debugString .=(microtime(true)-$sTime)."\n";

                Module::serverErrorLog($debugString);

		return new returnData(0, TRUE);
	}	

	public function updatePlayerLocation($playerId, $gameId, $floatLat, $floatLong)
	{
		Module::processGameEvent($playerId, $gameId, Module::kLOG_MOVE, $floatLat, $floatLong);
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}

        public function getGamesForPlayerAtLocation($playerId, $latitude, $longitude, $maxDistance=99999999, $locational, $includeGamesinDevelopment)
        {
            if ($includeGamesinDevelopment) $query = "
                SELECT games.game_id FROM games JOIN locations ON games.game_id = locations.game_id 
                    WHERE locations.latitude BETWEEN {$latitude}-.5 AND {$latitude}+.5
                    AND locations.longitude BETWEEN {$longitude}-.5 AND {$longitude}+.5
                    AND is_locational = '{$locational}'
                    GROUP BY games.game_id
                    LIMIT 50";
            else $query = "
                SELECT games.game_id FROM games JOIN locations ON games.game_id = locations.game_id 
                    WHERE locations.latitude BETWEEN {$latitude}-.5 AND {$latitude}+.5
                    AND locations.longitude BETWEEN {$longitude}-.5 AND {$longitude}+.5
                    AND is_locational = '{$locational}'
                    AND ready_for_public = TRUE
                    GROUP BY games.game_id
                    LIMIT 50";

            $gamesRs = dbconnection::query($query);

            $games = array();
            while($game = @mysql_fetch_object($gamesRs))
            {
                $gameObj = new stdClass;
                $gameObj = Games::getFullGameObject($game->game_id, $playerId, 1, $maxDistance, $latitude, $longitude);
                if($gameObj != NULL) $games[] = $gameObj;
            }
            return new returnData(0, $games, NULL);
        }		

    public function getOneGame($gameId, $playerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0)
    {
        $games = array();
        $gameObj = Games::getFullGameObject($gameId, $playerId, $boolGetLocationalInfo, $intSkipAtDistance, $latitude, $longitude);

        if($gameObj != NULL)
            $games[] = $gameObj;
        return new returnData(0, $games, NULL);
    }	

    public function getPopularGames($playerId, $time, $includeGamesinDevelopment)
    {
        if ($time == 0) $queryInterval = '1 DAY';
        else if ($time == 1) $queryInterval = '7 DAY';
        else if ($time == 2) $queryInterval = '1 MONTH';

        if ($includeGamesinDevelopment) $query = "SELECT media.file_path as file_path, temp.game_id, temp.name, temp.description, temp.count FROM (SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count FROM games INNER JOIN player_log ON games.game_id = player_log.game_id WHERE player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1) as temp LEFT JOIN media ON temp.icon_media_id = media.media_id GROUP BY game_id HAVING count > 1 ORDER BY count DESC LIMIT 20";

        else $query = "SELECT media.file_path as file_path, temp.game_id, temp.name, temp.description, temp.count FROM (SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count FROM games INNER JOIN player_log ON games.game_id = player_log.game_id WHERE ready_for_public = TRUE AND player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1) as temp LEFT JOIN media ON temp.icon_media_id = media.media_id GROUP BY game_id HAVING count > 1 ORDER BY count DESC LIMIT 20";

        $gamesRs = dbconnection::query($query);

        $games = array();
        while($game = @mysql_fetch_object($gamesRs))
        {
            $gameObj = Games::getFullGameObject($game->game_id, $playerId, 0, 9999999999, 0, 0);
            if($gameObj != NULL)
            {
                $gameObj->count = $game->count;
                $games[] = $gameObj;
            }
        }
        return new returnData(0, $games, NULL);
    }		

    public function getGamesContainingText($playerId, $latitude, $longitude, $textToFind, $boolIncludeDevGames = 1, $page = 0)
    {
        $textToFind = addSlashes($textToFind);
        $textToFind = urldecode($textToFind);
        if($boolIncludeDevGames) $query = "SELECT game_id, name FROM games WHERE (name LIKE '%{$textToFind}%' OR description LIKE '%{$textToFind}%') ORDER BY name ASC LIMIT ".($page*25).", 25";
        else $query = "SELECT game_id, name FROM games WHERE (name LIKE '%{$textToFind}%' OR description LIKE '%{$textToFind}%') AND ready_for_public = 1 ORDER BY name ASC LIMIT ".($page*25).", 25";

        $result = dbconnection::query($query);
        $games = array();
        while($game = mysql_fetch_object($result)){
            $gameObj = new stdClass;
            $gameObj = Games::getFullGameObject($game->game_id, $playerId, 1, 9999999999, $latitude, $longitude);
            if($gameObj != NULL){
                $games[] = $gameObj;
            }
            else{
                $gameObj = Games::getFullGameObject($game->game_id, $playerId, 0, 9999999999, $latitude, $longitude);
                if($gameObj != NULL){
                    $games[] = $gameObj;
                }
            }
        }
        return new returnData(0, $games);
    }

    public function getRecentGamesForPlayer($playerId, $latitude, $longitude, $includeDev = 1)
    {
        $debugString = "";
        $sTime = microtime(true);
        $logs = dbconnection::queryArray("SELECT game_id, MAX(timestamp) as ts FROM player_log WHERE player_id = '{$playerId}' AND game_id != 0 GROUP BY game_id ORDER BY ts DESC LIMIT 20");
        $debugString .= "GetRecentGamesQuery: ".(microtime(true)-$sTime)."\n";
        $games = array();
        for($i = 0; $i < count($logs) && count($games) < 10; $i++)
        {
            $sTime = microtime(true);
            $gameObj = Games::getFullGameObject($logs[$i]->game_id, $playerId, 1, 9999999999, $latitude, $longitude);
            if($gameObj != NULL && ($gameObj->ready_for_public || $includeDev)) $games[] = $gameObj;
            $debugString .= $logs[$i]->game_id.": ".(microtime(true)-$sTime)."\n";
        }
        Module::serverErrorLog($debugString);

        return new returnData(0, $games);
    }


	public function nodeViewed($gameId, $playerId, $intNodeId, $intLocationId = 0)
	{	
		//Module::applyPlayerStateChanges($gameId, $playerId, Module::kLOG_VIEW_NODE, $intNodeId); //Was causing duplicate playerStateChanges (changed 5/23/12 Phil)
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_NODE, $intNodeId, $intLocationId);

		return new returnData(0, TRUE);
	}

	public function setItemCountForPlayerJSON($obj)
	{
		$gameId = $obj['gameId'];
		$itemId = $obj['itemId'];
		$playerId = $obj['playerId'];
		$qty = $obj['qty'];

		Module::setItemCountForPlayer($gameId, $itemId, $playerId, $qty);
	}

	public function setItemCountForPlayer($gameId, $itemId, $playerId, $qty)
	{
		$rData = Module::setItemCountForPlayer($gameId, $itemId, $playerId, $qty);
		if(!$rData->returnCode)
			return new returnData(0, $rData);
		else
			return $rData;
	}

	public function giveItemToPlayer($gameId, $itemId, $playerId, $qtyToGive=1) 
	{
		$rData = Module::giveItemToPlayer($gameId, $itemId, $playerId, $qtyToGive=1);
		if(!$rData->returnCode)
			return new returnData(0, $rData);
		else
			return $rData;
	}

	public function takeItemFromPlayer($gameId, $itemId, $playerId, $qtyToGive=1) 
	{
		$rData = Module::takeItemFromPlayer($gameId, $itemId, $playerId, $qtyToGive=1);
		if(!$rData->returnCode)
			return new returnData(0, $rData);
		else
			return $rData;
	}

	public function locationViewed($gameId, $playerId, $locationId)
	{
		$location = Module::queryObject("SELECT * FROM locations WHERE game_id = $gameId AND location_id = $locationId LIMIT 1");
		if(mysql_error()) return new returnData(3, NULL, "SQL Error");
		if($location) Module::checkSpawnablesForDeletion($gameId, $locationId, $location->type, $location->type_id);

		return new returnData(0, TRUE);
	}

	public function itemViewed($gameId, $playerId, $itemId, $intLocationId = 0)
	{
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_ITEM, $itemId, $intLocationId);

		$query = "UPDATE player_items SET viewed = 1 WHERE game_id = {$gameId} AND player_id = {$playerId} AND item_id = {$itemId}";

		Module::query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);

		return new returnData(0, TRUE);
	}

	public function npcViewed($gameId, $playerId, $intNpcId, $intLocationId = 0)
	{	
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_NPC, $intNpcId, $intLocationId);

		return new returnData(0, TRUE);
	}

	public function webPageViewed($gameId, $playerId, $intWebPageId, $intLocationId = 0)
	{	
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_WEBPAGE, $intWebPageId, $intLocationId);

		return new returnData(0, TRUE);
	}

	public function augBubbleViewed($gameId, $playerId, $intAugBubbleId, $intLocationId = 0)
	{	
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_AUGBUBBLE, $intAugBubbleId, $intLocationId);

		return new returnData(0, TRUE);
	}

	public function pickupItemFromLocation($gameId, $playerId, $itemId, $intLocationId, $qty=1)
	{	
		$query = "SELECT item_qty from locations WHERE game_id = {$gameId} AND location_id = $intLocationId";
		$result = Module::query($query);
		$loc = mysql_fetch_object($result);

		if($loc->item_qty != -1 && $loc->item_qty < $qty)
                {
			if($loc->item_qty == 0) return new returnData(0, FALSE, "Location has qty 0");

			$qtyGiven = Module::giveItemToPlayer($gameId, $itemId, $playerId, $loc->item_qty);
			Module::decrementItemQtyAtLocation($gameId, $intLocationId, $qtyGiven); 

			return new returnData(0, $qtyGiven);
		}

		$qtyGiven = Module::giveItemToPlayer($gameId, $itemId, $playerId, $qty);
		Module::decrementItemQtyAtLocation($gameId, $intLocationId, $qtyGiven); 

		return new returnData(0, $qtyGiven);
	}

	public function dropItem($gameId, $playerId, $itemId, $floatLat, $floatLong, $qty=1)
	{
		Module::takeItemFromPlayer($gameId, $itemId, $playerId, $qty);
		Players::giveItemToWorld($gameId, $itemId, $floatLat, $floatLong, $qty);

		return new returnData(0, FALSE);
	}		

	public function dropNote($gameId, $playerId, $noteId, $floatLat, $floatLong)
	{
		Module::giveNoteToWorld($gameId, $noteId, $floatLat, $floatLong);

		return new returnData(0, FALSE);
	}	

	public function destroyItem($gameId, $playerId, $itemId, $qty=1)
	{
		Module::takeItemFromPlayer($gameId, $itemId, $playerId, $qty);
		Module::processGameEvent($playerId, $gameId, Module::kLOG_DESTROY_ITEM, $itemId, $qty);

		return new returnData(0, FALSE);
	}		

	public function mapViewed($gameId, $playerId)
	{
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_MAP);
		return new returnData(0, FALSE);
	}

	public function questsViewed($gameId, $playerId)
	{
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_QUESTS);
		return new returnData(0, FALSE);
	}

	public function inventoryViewed($gameId, $playerId)
	{
		Module::processGameEvent($playerId, $gameId, Module::kLOG_VIEW_INVENTORY);
		return new returnData(0, FALSE);
	}			

	function setShowPlayerOnMap($playerId, $spom)
	{
		$query = "UPDATE players SET show_on_map = '{$spom}' WHERE player_id = '{$playerId}'";
		Module::query($query);
		return new returnData(0);
	}

        protected function giveItemToWorld($gameId, $itemId, $floatLat, $floatLong, $intQty = 1)
        {
            $clumpingRangeInMeters = 10;

            $query = "SELECT *,((ACOS(SIN($floatLat * PI() / 180) * SIN(latitude * PI() / 180) + 
                COS($floatLat * PI() / 180) * COS(latitude * PI() / 180) * 
                COS(($floatLong - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1609.344
                AS `distance`, location_id 
                FROM locations 
                WHERE type = 'item' AND type_id = '{$itemId}' AND game_id = '{$gameId}'
                HAVING distance<= {$clumpingRangeInMeters}
            ORDER BY distance ASC"; 	
                $result = Module::query($query);

            if($closestLocationWithinClumpingRange = @mysql_fetch_object($result))
                Module::query("UPDATE locations SET item_qty = item_qty + {$intQty} WHERE location_id = {$closestLocationWithinClumpingRange->location_id} AND game_id = '{$gameId}'");
            else
            {
                $item = Module::queryObject("SELECT * FROM items WHERE item_id = '{$itemId}'");
                Module::query("INSERT INTO locations (game_id, name, type, type_id, icon_media_id, latitude, longitude, error, item_qty) VALUES ('{$gameId}', '{$item->name}','Item','{$itemId}', '{$item->icon_media_id}', '{$floatLat}','{$floatLong}', '100','{$intQty}')");
                QRCodes::createQRCode($gameId, "Location", mysql_insert_id(), '');
            }
        }

    /*
    //Expected JSON format
    {
        "playerId":1234,   //<- REQUIRED
        "media":
            {
                "filename":"banana.jpg",  //<- Unimportant (will get changed), but MUST have correct extension (ie '.jpg')
                "data":"as262dsf6a..."    //<- base64 encoded media data
            }
    }
    */
    public function uploadPlayerMediaFromJSON($glob)
    {
        //WHY DOESNT THIS HAPPEN VIA THE FRAMEWORK?!
	$data = file_get_contents("php://input");
        $glob = json_decode($data);

        $playerId     = $glob->playerId;
        $media        = $glob->media;
        $media->path  = "player";

        if(!is_numeric($playerId)) return new returnData(1,NULL,"JSON package has no numeric member \"playerId\"");
 
        $media = Media::createMediaFromJSON($media)->data;
        Module::query("UPDATE players SET media_id = '{$media->media_id}' WHERE player_id = '{$playerId}'");
        return new returnData(0,$media);
    }
}
?>
