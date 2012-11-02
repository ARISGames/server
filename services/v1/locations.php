<?php
require_once("module.php");
require_once("players.php");
require_once("qrcodes.php");


class Locations extends Module
{

    /**
     * Fetch all location in a game
     *
     * @param integer $intGameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of locations
     * @see returnData
     */
    public function getLocations($intGameID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT {$prefix}_locations.*, f.active AS is_fountain FROM {$prefix}_locations LEFT JOIN (SELECT active, location_id FROM fountains WHERE game_id = $prefix) AS f ON {$prefix}_locations.location_id = f.location_id";
        $rsResult = @mysql_query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());
        return new returnData(0, $rsResult);	
    }


    /**
     * Get all 'QR Code' entries for a location. The only purpose for this will be for the multiple entries for image matching.
     * @param integer The Game ID
     * @param integer The Location ID
     * @returns An array of media ID's for a specific location
     */
    public function getAllImageMatchEntriesForLocation($intGameID, $intLocationID){

        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT match_media_id FROM {$prefix}_qrcodes WHERE link_id = {$intLocationID}";
        $result = mysql_query($query);

        $medias = array();
        while($mid = mysql_fetch_object($result)){
            NetDebug::trace($mid->match_media_id);
            $medias[] = Media::getMediaObject($intGameID, $mid->match_media_id);
        }

        return new returnData(0, $medias);
    }


    /**
     * Adds a record in the QR database. Used for image matching
     * @param integer The Game ID
     * @param integer The Location ID
     * @param integer The Image Match Media ID
     * @returns 0 on success
     */
    public function addImageMatchEntryForLocation($intGameId, $intLocationId, $intMatchMediaID){
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //Check if location exists, and store code
        $query = "SELECT * FROM {$prefix}_qrcodes WHERE link_id={$intLocationId}";
        $result = mysql_query($query);
        $code = 0;
        if(mysql_num_rows($result) != 0){
            $row = mysql_fetch_object($result);
            $code = $row->code;
        }
        else return new returnData(1, NULL, "Location Doesn't Exist");

        //Check if this media/location pair already exists. If so, exit (our job is already done)
        $query = "SELECT * FROM {$prefix}_qrcodes WHERE link_id ={$intLocationId} AND match_media_id ={$intMatchMediaID}";
        $result = mysql_query($query);
        if(mysql_num_rows($result) != 0) return new returnData(0); 

        //Check if this is the only entry...
        $query = "SELECT * FROM {$prefix}_qrcodes WHERE link_id ={$intLocationId} AND match_media_id ='0'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) == 1){
            $query = "UPDATE {$prefix}_qrcodes SET match_media_id = {$intMatchMediaID} WHERE link_id={$intLocationId}";
            mysql_query($query);
            Locations::generateDescriptors($intMatchMediaID, $intGameId);
            return new returnData(0);
        }


        $query = "INSERT INTO {$prefix}_qrcodes (link_id, match_media_id, code) VALUES ({$intLocationId}, {$intMatchMediaID}, {$code})";
        mysql_query($query);
        Locations::generateDescriptors($intMatchMediaID, $intGameId);

        return new returnData(0);
    }

    private function generateDescriptors($mediaId, $gameId) {
        //Get the filename for the media
        if ($mediaId) {
            $query = "SELECT file_path FROM media WHERE media_id = '{$mediaId}' LIMIT 1";
            $result = @mysql_query($query);
            $fileName = mysql_fetch_object($result)->file_path;	
            if (mysql_error()) NetDebug::trace("SQL Error: ". mysql_error());

            $gameMediaAndDescriptorsPath = Media::getMediaDirectory($gameId)->data;
            $execCommand = '../../ImageMatcher/ImageMatcher generate ' . $gameMediaAndDescriptorsPath . $fileName;
            NetDebug::trace($execCommand);
            $console = exec($execCommand);
            NetDebug::trace($console);
        }
    }


    /**
     * Removes a record in the QR database. Used for image Matching.
     * @param integer The Game ID
     * @param integer The Location ID
     * @param integer The Image Match Media to remove
     * @returns 0 on success
     */
    public function removeImageMatchEntryForLocation($intGameId, $intLocationId, $intMatchMediaID){
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //Check if this is the only remaining QR code entry. If so, ONLY clear the image match media ID, DO NOT delete the whole row.
        $query = "SELECT * FROM {$prefix}_qrcodes WHERE link_id ={$intLocationId}";
        $result = @mysql_query($query);
        if(mysql_num_rows($result) == 1){
            $query = "UPDATE {$prefix}_qrcodes SET match_media_id = '0' WHERE link_id={$intLocationId} AND match_media_id = {$intMatchMediaID}";
            mysql_query($query);
            deleteImageMatchXML($intMatchMediaID, $intGameId);
            return new returnData(0);
        }
        elseif(mysql_num_rows($result) > 1){
            $query = "DELETE FROM {$prefix}_qrcodes WHERE link_id={$intLocationId} AND match_media_id={$intMatchMediaID}";
            mysql_query($query);
            deleteImageMatchXML($intMatchMediaID, $intGameId);
            return new returnData(0);
        }
        else{
            return new returnData(1);
        }
    }

    public function deleteImageMatchXML($mediaId, $gameId){
        $query = "SELECT file_path FROM media WHERE media_id = '{$mediaId}' AND (game_id = '{$gameId}' OR game_id = '0')";
        $result = mysql_query($query);

        if($med = mysql_fetch_object($result)){
            NetDebug::trace("../../gamedata/".$gameId."/".substr($med->file_path, 0, -4).".xml");
            unlink("../../gamedata/".$gameId."/".substr($med->file_path, 0, -4).".xml");
        }
    }

    /**
     * Fetch all locations in a game with matching QR Code information
     *
     * @param integer $intGameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of locations with the QR code record id and code
     * @see returnData
     */
    public function getLocationsWithQrCode($intGameID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT game_locations.*,game_qrcodes.qrcode_id,game_qrcodes.code,game_qrcodes.match_media_id, game_qrcodes.fail_text, f.active AS is_fountain
            FROM (SELECT * FROM locations WHERE game_id = {$prefix}) AS game_locations
            LEFT JOIN (SELECT * FROM qrcodes WHERE game_id = {$prefix}) AS game_qrcodes
            ON game_locations.location_id = game_qrcodes.link_id LEFT JOIN
            (SELECT location_id, active FROM fountains WHERE game_id = $prefix) AS f
            ON {$prefix}_locations.location_id = f.location_id";
        NetDebug::trace($query);	

        $rsResult = @mysql_query($query);
        NetDebug::trace(mysql_error());	

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);	
    }	





















    /**
     * Fetch locations with fulfilled requirements and other player positions
     *
     * @param integer $intGameID The game identifier
     * @param integer $intPlayerID The player identifier
     * @return returnData
     * @returns a returnData object containing an array of locations
     * @see returnData
     */
    public function getLocationsForPlayer($intGameID, $intPlayerID, $lat = 0, $lon = 0)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $arrayLocations = array();

        //Gets all non-spawned locations
        $query = "SELECT {$prefix}_locations.*, gamefountains.fountain_id, gamefountains.spawn_probability, gamefountains.spawn_rate, gamefountains.max_amount, gamefountains.last_spawned, gamefountains.active FROM {$prefix}_locations LEFT JOIN (SELECT * FROM spawnables WHERE game_id = $prefix) AS gamespawns ON {$prefix}_locations.type = gamespawns.type AND {$prefix}_locations.type_id = gamespawns.type_id LEFT JOIN (SELECT * FROM fountains WHERE game_id = $prefix) AS gamefountains ON {$prefix}_locations.location_id = gamefountains.location_id WHERE {$prefix}_locations.latitude != '' AND {$prefix}_locations.longitude != '' AND (spawnable_id IS NULL OR gamespawns.active = 0)";

        $rsLocations = @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error" . mysql_error());

        while ($location = mysql_fetch_object($rsLocations)) {
            //If location and object it links to meet requirments, add it to the array
            NetDebug::trace('Location ' . $location->location_id . ' Found. Checking Reqs');	

            //Does it Exist?
            switch ($location->type) {
                case 'Item':
                    $query = "SELECT icon_media_id FROM {$prefix}_items WHERE item_id = {$location->type_id} LIMIT 1";
                    break;
                case 'Node':
                    $query = "SELECT icon_media_id FROM {$prefix}_nodes WHERE node_id = {$location->type_id} LIMIT 1";
                    break;
                case 'Npc':
                    $query = "SELECT icon_media_id FROM {$prefix}_npcs WHERE npc_id = {$location->type_id} LIMIT 1";
                    break;
                case 'WebPage':
                    $query = "SELECT icon_media_id FROM web_pages WHERE web_page_id = {$location->type_id} LIMIT 1";
                    break;
                case 'AugBubble':
                    $query = "SELECT icon_media_id FROM aug_bubbles WHERE aug_bubble_id = {$location->type_id} LIMIT 1";
                    break;
                case 'PlayerNote':
                    $query = "SELECT public_to_map FROM notes WHERE note_id = {$location->type_id} LIMIT 1";
                    break;
            }

            if ($location->type == 'PlayerNote') {
                $rsObject = @mysql_query($query);
                $object = @mysql_fetch_object($rsObject);
                if (!$object || $object->public_to_map == 0) {
                    NetDebug::trace("Skipping Location:'{$location->location_id}' becasue it points to something bogus, or it isn't shared to map");	
                    continue;
                }
            }
            else {
                $rsObject = @mysql_query($query);
                $object = @mysql_fetch_object($rsObject);
                if (!$object) {
                    NetDebug::trace("Skipping Location:'{$location->location_id}' becasue it points to something bogus");	
                    continue;
                }
            }

            //Deal with qty (whether empty, or fountain)
            if($location->fountain_id && $location->active)
            {
                $secondsOfSpawning = strtotime("now")-strtotime($location->last_spawned);
                while($secondsOfSpawning > $location->spawn_rate && $location->item_qty < $location->max_amount)
                {
                    if(rand(0,100) < $location->spawn_probability)
                    {
                        $location->item_qty++;
                    }
                    $secondsOfSpawning-=$location->spawn_rate;
                    $query = "UPDATE fountains SET last_spawned = now() WHERE fountain_id = ".$location->fountain_id;
                    mysql_query($query);
                }
                if($location->item_qty >= $location->max_amount)
                {
                    $query = "UPDATE fountains SET last_spawned = now() WHERE fountain_id = ".$location->fountain_id;
                    mysql_query($query);
                }
                $query = "UPDATE locations SET item_qty = ".$location->item_qty." WHERE location_id = ".$location->location_id;
                mysql_query($query);
            }

            if($location->type == 'Item' && $location->item_qty < 1 && $location->item_qty != -1)
            {
                NetDebug::trace("Skipping Location:'{$location->location_id}' becasue it has < 1 item_qty");
                continue;
            }

            //Does it meet it's requirements?
            if (!$this->objectMeetsRequirements($prefix, $intPlayerID, 'Location', $location->location_id)) {
                // NetDebug::trace($prefix . " " . $intPlayerID . " 'Location' " . $location->location_id);
                NetDebug::trace("Skipping Location:'{$location->location_id}' becasue it doesn't meet it's requirements");
                continue;
            }
            else{
                NetDebug::trace("Requirement met. Awwe yeeeaaaah.");
            }

            //Special Case for Notes
            if($location->type == 'PlayerNote')
            {
                $query = "SELECT public_to_map, public_to_notebook, owner_id FROM notes WHERE note_id='{$location->type_id}' LIMIT 1";
                $result = mysql_query($query);
                $note = mysql_fetch_object($result);
                //If note doesn't exist, or if it is neither public nor owned by the owner, skip it.
                if(!$note || !($note->public_to_map || $note->owner_id == $intPlayerID))
                {
                    NetDebug::trace("Skipping Location:{$location->location_id} because Note doesn't exist, or current user does not have permission to view it");
                    continue;
                }
                if($note->public_to_notebook || $note->owner_id == $intPlayerId)
                    $location->allow_quick_travel = 1;
            }

            NetDebug::trace('Location:{$location->location_id} is ok');	

            //If location's icon is not defined, use the object's icon
            if (!$location->icon_media_id) {
                $objectsIconMediaId = $object->icon_media_id;
                $location->icon_media_id = $objectsIconMediaId;
            }

            $location->delete_when_viewed = 0;

            //Add it
            $arrayLocations[] = $location;
        }










        //Get all spawned locations (needs separate calculations, as requirements are not associated with each location)
        $query = "SELECT * FROM spawnables WHERE game_id = ".$prefix." AND active = 1";
        $results = mysql_query($query);
        while($spawnable = mysql_fetch_object($results)){

            //If spawnable and object it links to meet requirments, add it to the array
            NetDebug::trace('Spawnable ' . $spawnable->spawnable_id . ' Found. Checking Reqs');	

            //Does it Exist?
            switch ($spawnable->type) {
                case 'Item':
                    $query = "SELECT name as title, icon_media_id FROM {$prefix}_items WHERE item_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'Node':
                    $query = "SELECT title, icon_media_id FROM {$prefix}_nodes WHERE node_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'Npc':
                    $query = "SELECT name as title, icon_media_id FROM {$prefix}_npcs WHERE npc_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'WebPage':
                    $query = "SELECT name as title, icon_media_id FROM web_pages WHERE web_page_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'AugBubble':
                    $query = "SELECT name as title, icon_media_id FROM aug_bubbles WHERE aug_bubble_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'PlayerNote':
                    $query = "SELECT public_to_map FROM notes WHERE note_id = {$spawnable->type_id} LIMIT 1";
                    break;
            }

            $rsObject = @mysql_query($query);
            $object = @mysql_fetch_object($rsObject);
            if (!$object) {
                NetDebug::trace("Skipping Spawnable:'{$spawnable->spawnable_id}' becasue it points to something bogus");	
                continue;
            }
            $spawnable->icon_media_id = $object->icon_media_id;
            $spawnable->title = $object->title;

            //Does it meet it's requirements?
            if (!$this->objectMeetsRequirements ($prefix, $intPlayerID, 'Spawnable', $spawnable->spawnable_id)) {
                NetDebug::trace("Skipping Spawnable:'{$spawnable->spawnable_id}' becasue it doesn't meet it's requirements");
                continue;
            }
            else{
                NetDebug::trace("Requirement met. Awwe yeeeaaaah.");
            }


            //Create spawnables
            if($spawnable->location_bound_type == 'PLAYER')
            {
                if($lat == 0 && $lon == 0)
                {
                    //Find player location from log and set lat and lon accordingly
                    $query = "SELECT event_detail_1, event_detail_2 FROM player_log WHERE player_id = $intPlayerID AND (game_id = $intGameID OR game_id = 0) AND event_type = 'MOVE' AND deleted = 0 ORDER BY timestamp DESC LIMIT 1";
                    $result = mysql_query($query);
                    if($obj = mysql_fetch_object($result))
                    {
                        $lat = $obj->event_detail_1;
                        $lon = $obj->event_detail_2;
                    }
                }
            }
            else if($spawnable->location_bound_type == 'LOCATION')
            {
                $lat = $spawnable->latitude;
                $lon = $spawnable->longitude;
            }

            if($spawnable->amount_restriction == 'PER_PLAYER')
            {
                //Special case for calculating max on a per_player basis with a set spawn location
                if($spawnable->location_bound_type == 'LOCATION')
                {
                    $query = "SELECT DISTINCT player_id FROM player_log WHERE game_id = $intGameID AND deleted = 0 AND timestamp >= NOW() - INTERVAL 20 MINUTE";
                    $result = mysql_query($query);
                    $spawnable->amount *= mysql_num_rows($result);
                }
                $radius = Module::mToDeg($spawnable->max_area);
                $query = "SELECT * FROM ".$prefix."_locations WHERE type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id." AND latitude < ". ($lat+$radius) ." AND latitude > ". ($lat-$radius) ." AND longitude < ". ($lon+$radius) ." AND longitude > ". ($lon-$radius);
                $result = mysql_query($query);
                $numLocs = mysql_num_rows($result);
            }
            else if($spawnable->amount_restriction == 'TOTAL')
            {
                $query = "SELECT * FROM ".$prefix."_locations WHERE type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id;
                $result = mysql_query($query);
                $numLocs = mysql_num_rows($result);
            }

            $secondsOfSpawning = strtotime("now")-strtotime($spawnable->last_spawned);
            while($secondsOfSpawning > $spawnable->spawn_rate && $numLocs < $spawnable->amount)
            {
                if(rand(0,100) < $spawnable->spawn_probability)
                {
                    $numLocs++;
                    $spawnLoc = Module::randomLatLnWithinRadius($lat, $lon, $spawnable->min_area, $spawnable->max_area);
                    $newLat = $spawnLoc->lat;//$lat+Module::mToDeg(((rand(0,100)/50)*$spawnable->max_area)-$spawnable->max_area);
                    $newLon = $spawnLoc->lon;//$lon+Module::mToDeg(((rand(0,100)/50)*$spawnable->max_area)-$spawnable->max_area);
                    Locations::createLocationWithQrCode($intGameID, $spawnable->location_name, $spawnable->icon_media_id, $newLat, $newLon, $spawnable->error_range, $spawnable->type, $spawnable->type_id, 1, $spawnable->hidden, $spawnable->force_view, $spawnable->allow_quick_travel, $spawnable->wiggle, $spawnable->show_title, '', 0, "You've incorrectly encountered a spawnable! Weird...");
                }
                $query = "UPDATE spawnables SET last_spawned = now() WHERE spawnable_id = ".$spawnable->spawnable_id;
                mysql_query($query);
                $secondsOfSpawning-=$spawnable->spawn_rate;
                if(location_bound_type != 'LOCATION') $secondsOfSpawning = 0; //Only simulate once unless location bound is a location
            }
            if($numLocs >= $spawnable->amount)
            {
                $query = "UPDATE spawnables SET last_spawned = now() WHERE spawnable_id = ".$spawnable->spawnable_id;
                mysql_query($query);
            } 

            //Destroy spawnables
            if($spawnable->time_to_live != -1)
            {
                $query = "DELETE game_locations, game_qrcodes 
			FROM (SELECT * FROM locations WHERE game_id = {$prefix}) AS game_locations 
			LEFT_JOIN (SELECT * FROM qrcodes WHERE game_id = {$prefix}) AS game_qrcodes ON game_locations.location_id = game_qrcodes.link_id 
			WHERE type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id." AND ((spawnstamp < NOW() - INTERVAL ".$spawnable->time_to_live." SECOND) OR (type = 'Item' AND item_qty = 0))";
                mysql_query($query);
            }

            $query = "SELECT * FROM ".$prefix."_locations WHERE type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id;
            $locresults = mysql_query($query);
            while($locobj = mysql_fetch_object($locresults))
            {
                //If location's icon is not defined, use the object's icon
                if (!$locobj->icon_media_id) 
                    $locobj->icon_media_id = $object->icon_media_id;
                $locobj->delete_when_viewed = $spawnable->delete_when_viewed && $spawnable->active;
                //Module::serverErrorLog($locobj->delete_when_viewed."<- final  ".$spawnable->delete_when_viewed." ".$spawnable->active);

                //Add it
                if($locobj->type != 'Item' || ($locobj->item_qty == -1 || $locobj->item_qty > 0))
                    $arrayLocations[] = $locobj;
            }
        }










        //Add the others players from this game, making them look like reqular locations
        $playersJSON = Players::getOtherPlayersForGame($intGameID, $intPlayerID);
        $playersArray = $playersJSON->data;

        foreach ($playersArray as $player) {
            NetDebug::trace("adding player: " . $player->user_name );	

            $tmpPlayerObject = new stdClass();

            $tmpPlayerObject->name = $player->user_name;
            $tmpPlayerObject->latitude = $player->latitude;
            $tmpPlayerObject->longitude = $player->longitude;
            $tmpPlayerObject->type_id = $player->player_id;

            $tmpPlayerObject->error = "5";
            $tmpPlayerObject->type = "Player";

            $tmpPlayerObject->description = '';
            $tmpPlayerObject->force_view = "0";
            $tmpPlayerObject->hidden = "0";
            $tmpPlayerObject->icon_media_id = "0";
            $tmpPlayerObject->item_qty = "0";
            $tmpPlayerObject->location_id = "0";

            $arrayLocations[] = $tmpPlayerObject;
            NetDebug::trace("just adding player: " . $tmpPlayerObject->name );	

        }

        return new returnData(0, $arrayLocations);

    }



























    /**
     * Fetch a specific location
     *
     * @param integer $intGameID The game identifier
     * @param integer $intLocationID The location to fetch
     * @return returnData
     * @returns a returnData object containing a location
     * @see returnData
     */
    public function getLocation($intGameID, $intLocationID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM {$prefix}_locations WHERE location_id = {$intLocationID} LIMIT 1";

        $rsResult = @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        $location = mysql_fetch_object($rsResult);
        if (!$location) return new returnData(2, NULL, "No matching location");
        return new returnData(0, $location);
    }


    /**
     * Creates a location that points to a given object
     *
     * @param integer $intGameID The game identifier
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @return returnData
     * @returns a returnData object containing the new locationID
     * @see returnData
     */
    public function createLocation($intGameID, $strLocationName, $intIconMediaID, 
            $dblLatitude, $dblLongitude, $dblError,
            $strObjectType, $intObjectID,
            $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation) {	

        Locations::createLocationWithQrCode($intGameID, $strLocationName, $intIconMediaID, 
                $dblLatitude, $dblLongitude, $dblError,
                $strObjectType, $intObjectID,
                $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation, $qrCode = '', 0);
    }

    /**
     * Creates a location that points to a given object
     *
     * @param integer $intGameID The game identifier
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @param string $qrCode Code to use with the decoder
     * @return returnData
     * @returns a returnData object containing the new locationID
     * @see returnData
     */
    public function createLocationWithQrCode($intGameID, $strLocationName, $intIconMediaID, 
            $dblLatitude, $dblLongitude, $dblError,
            $strObjectType, $intObjectID,
            $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation, $qrCode = '', $imageMatchId, $errorText) {

        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");
        if (!$intQuantity) $intQuantity = 1;
        if (!$boolAllowQuickTravel) $boolAllowQuickTravel = 0;

        $strLocationName = addslashes($strLocationName);
        //if ($dblError < 5) $dblError = 25; // <-- NO!

        //Check the object Type is good or null
        if ( !Locations::isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        $query = "INSERT INTO {$prefix}_locations 
            (name, icon_media_id, latitude, longitude, error, 
             type, type_id, item_qty, hidden, force_view, allow_quick_travel, wiggle, show_title)
            VALUES ('{$strLocationName}', '{$intIconMediaID}',
                    '{$dblLatitude}','{$dblLongitude}','{$dblError}',
                    '{$strObjectType}','{$intObjectID}','{$intQuantity}',
                    '{$boolHidden}','{$boolForceView}', '{$boolAllowQuickTravel}', '{$boolAllowWiggle}', '{$boolDisplayAnnotation}')";

        NetDebug::trace("createLocation: Running a query = $query");	

        @mysql_query($query);

        if (mysql_error()) {
            NetDebug::trace("createLocation: SQL Error = " . mysql_error());
            return new returnData(3, NULL, "SQL Error");
        }

        $newId = mysql_insert_id();
        //Create a coresponding QR Code
        QRCodes::createQRCode($intGameID, "Location", $newId, $qrCode, $imageMatchId, $errorText);

        return new returnData(0, $newId);

    }





    /**
     * Updates the attributes of a Location
     *
     * @param integer $intGameID The game identifier
     * @param string $intLocationID The location identifier     
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @return returnData
     * @returns a returnData object containing true if a record was modified
     * @see returnData
     */     
    public function updateLocation($intGameID, $intLocationID, $strLocationName, $intIconMediaID, 
            $dblLatitude, $dblLongitude, $dblError,
            $strObjectType, $intObjectID,
            $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotations)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $strLocationName = addslashes($strLocationName);
        //if ($dblError < 5) $dblError = 25; // <-- NO!

        //Check the object Type is good or null
        if ( !$this->isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        $query = "UPDATE {$prefix}_locations
            SET 
            name = '{$strLocationName}',
                 icon_media_id = '{$intIconMediaID}', 
                 latitude = '{$dblLatitude}', 
                 longitude = '{$dblLongitude}', 
                 error = '{$dblError}',
                 type = '{$strObjectType}',
                 type_id = '{$intObjectID}',
                 item_qty = '{$intQuantity}',
                 hidden = '{$boolHidden}',
                 force_view = '{$boolForceView}',
                 allow_quick_travel = '{$boolAllowQuickTravel}',
                 wiggle = '{$boolAllowWiggle}',
                 show_title = '{$boolDisplayAnnotations}',
                 WHERE location_id = '{$intLocationID}'";

        NetDebug::trace("updateLocation: Query: $query");		

        @mysql_query($query);
        if (mysql_error()) {
            NetDebug::trace("MySQL Error:" . mysql_error());
            return new returnData(3, NULL, "SQL Error");		
        }

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }

    }	

    /**
     * Updates the attributes of a Location
     *
     * @param integer $intGameID The game identifier
     * @param string $intLocationID The location identifier     
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @param string $qrCode a code to set for the QR image and the decoder    
     * @return returnData
     * @returns a returnData object containing true if a record was modified
     * @see returnData
     */     
    public function updateLocationWithQrCode($intGameID, $intLocationID, $strLocationName, $intIconMediaID, $dblLatitude, $dblLongitude, $dblError, $strObjectType, $intObjectID, $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation, $qrCode, $imageMatchId, $errorText)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $errorText = addslashes($errorText);
        $strLocationName = addslashes($strLocationName);
        //if ($dblError < 5) $dblError = 25; // <-- NO!

        //Check the object Type is good or null
        if ( !$this->isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        $query = "UPDATE {$prefix}_locations SET 
            name = '{$strLocationName}',
                 icon_media_id = '{$intIconMediaID}', 
                 latitude = '{$dblLatitude}', 
                 longitude = '{$dblLongitude}', 
                 error = '{$dblError}',
                 type = '{$strObjectType}',
                 type_id = '{$intObjectID}',
                 item_qty = '{$intQuantity}',
                 hidden = '{$boolHidden}',
                 force_view = '{$boolForceView}',
                 allow_quick_travel = '{$boolAllowQuickTravel}',
                 wiggle = '{$boolAllowWiggle}',
                 show_title = '{$boolDisplayAnnotation}' 
                     WHERE location_id = '{$intLocationID}'";
        NetDebug::trace("updateLocation: Query: $query");		
        @mysql_query($query);
        if (mysql_error()) {
            NetDebug::trace("MySQL Error:" . mysql_error());
            return new returnData(3, NULL, "SQL Error" . mysql_error());		
        }


        $query = "UPDATE {$prefix}_qrcodes
            SET 
            code = '{$qrCode}', fail_text = '{$errorText}'
            WHERE link_type = 'Location' and link_id = '{$intLocationID}'";
        NetDebug::trace("updateLocation: Query: $query");		
        @mysql_query($query);


        if (mysql_error()) {
            NetDebug::trace("MySQL Error:" . mysql_error());
            return new returnData(3, NULL, "SQL Error on query: {$query} Error:" . mysql_error());		
        }		

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }

    }	


    /**
     * Deletes a Location
     *
     * @param integer $intGameID The game identifier
     * @param string $intLocationID The location identifier     
     * @return returnData
     * @returns a returnData object containing true if a record was deleted
     * @see returnData
     */ 
    public function deleteLocation($intGameID, $intLocationId)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //Lookup the name of the item
        $query = "DELETE FROM {$prefix}_locations 
            WHERE location_id = '{$intLocationId}'";
        NetDebug::trace("deleteLocation: Query: $query");		

        @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        //Delete any QR Codes that point here
        QRCodes::deleteQRCodeCodesForLink($intGameID, "Location", $intLocationId);


        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }	
    }

    /**
     * Deletes all locations that refer to the given object
     *
     * @param integer $intGameID The game identifier
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @return returnData
     * @returns a returnData object containing true if a record was deleted
     * @see returnData
     */ 
    public function deleteLocationsForObject($intGameID, $strObjectType, $intObjectId)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //Check the object Type is good or null
        if ( !Locations::isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        //Delete the Locations and related QR Codes
        $query = "DELETE locations, qrcodes 
        	FROM locations
		LEFT JOIN qrcodes
		ON locations.game_id = qrcodes.game_id AND locations.location_id = qrcodes.link_id
		WHERE locations.type = '{$strObjectType}' AND locations.type_id = '{$intObjectId}' AND qrcodes.link_type = 'Location'";

        NetDebug::trace("Query: $query");		

        @mysql_query($query);
        NetDebug::trace(mysql_error());		

        if (mysql_error()) return new returnData(3, NULL, "SQL Error" . mysql_error());

        if (mysql_affected_rows())
            return new returnData(0, TRUE);
        else
            return new returnData(0, FALSE);
    }	


    /**
     * Fetch the valid content types for use in other location operations
     *
     * @param integer $intGameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of valid objectType strings
     * @see returnData
     */      
    public function objectTypeOptions($intGameID){	
        $options = Locations::lookupObjectTypeOptionsFromSQL($intGameID);
        if (!$options) return new returnData(1, NULL, "invalid game id");
        return new returnData(0, $options);
    }


    /**
     * Check if a content type is valid
     *
     * @param integer $intGameID The game identifier
     * @return bool
     * @returns TRUE if valid, FALSE otherwise
     */  
    private function isValidObjectType($intGameID, $strObjectType) {
        $validTypes = Locations::lookupObjectTypeOptionsFromSQL($intGameID);
        return in_array($strObjectType, $validTypes);
    }


    /**
     * Fetch the valid content types for use in other location operations
     *
     * @param integer $intGameID The game identifier
     * @return array
     * @returns an array of strings
     */  
    private function lookupObjectTypeOptionsFromSQL($intGameID){
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return FALSE;

        $query = "SHOW COLUMNS FROM {$prefix}_locations LIKE 'type'";
        $result = mysql_query( $query );
        $row = mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }	
}
