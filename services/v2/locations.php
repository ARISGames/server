<?php
require_once("module.php");
require_once("players.php");
require_once("qrcodes.php");

class Locations extends Module
{
    public function getLocations($intGameId)
    {
        $query = "SELECT game_locations.*, f.active AS is_fountain FROM (SELECT * FROM locations WHERE game_id = {$intGameId}) AS game_locations LEFT JOIN (SELECT active, location_id FROM fountains WHERE game_id = $intGameId) AS f ON game_locations.location_id = f.location_id";
        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());
        return new returnData(0, $rsResult);	
    }

    public function getAllImageMatchEntriesForLocation($intGameId, $intLocationID)
    {
        $query = "SELECT match_media_id FROM qrcodes WHERE game_id = {$intGameId} AND link_id = {$intLocationID}";
        $result = Module::query($query);

        $medias = array();
        while($mid = mysql_fetch_object($result)){
            $medias[] = Media::getMediaObject($intGameId, $mid->match_media_id);
        }

        return new returnData(0, $medias);
    }

    public function addImageMatchEntryForLocation($intGameId, $intLocationId, $intMatchMediaID)
    {
        //Check if location exists, and store code
        $query = "SELECT * FROM qrcodes WHERE game_id = {$intGameId} AND link_id={$intLocationId}";
        $result = Module::query($query);
        $code = 0;
        if(mysql_num_rows($result) != 0){
            $row = mysql_fetch_object($result);
            $code = $row->code;
        }
        else return new returnData(1, NULL, "Location Doesn't Exist");

        //Check if this media/location pair already exists. If so, exit (our job is already done)
        $query = "SELECT * FROM qrcodes WHERE game_id = {$intGameId} AND link_id ={$intLocationId} AND match_media_id ={$intMatchMediaID}";
        $result = Module::query($query);
        if(mysql_num_rows($result) != 0) return new returnData(0); 

        //Check if this is the only entry...
        $query = "SELECT * FROM qrcodes WHERE game_id = {$intGameId} AND link_id ={$intLocationId} AND match_media_id ='0'";
        $result = Module::query($query);
        if(mysql_num_rows($result) == 1){
            $query = "UPDATE qrcodes SET match_media_id = {$intMatchMediaID} WHERE game_id = {$intGameId} AND link_id={$intLocationId}";
            Module::query($query);
            Locations::generateDescriptors($intMatchMediaID, $intGameId);
            return new returnData(0);
        }


        $query = "INSERT INTO qrcodes (game_id, link_id, match_media_id, code) VALUES ({$intGameId}, {$intLocationId}, {$intMatchMediaID}, {$code})";
        Module::query($query);
        Locations::generateDescriptors($intMatchMediaID, $intGameId);

        return new returnData(0);
    }

    private function generateDescriptors($mediaId, $gameId) {
        //Get the filename for the media
        if ($mediaId) {
            $query = "SELECT file_path FROM media WHERE media_id = '{$mediaId}' LIMIT 1";
            $result = Module::query($query);
            $fileName = mysql_fetch_object($result)->file_path;	

            $gameMediaAndDescriptorsPath = Media::getMediaDirectory($gameId)->data;
            $execCommand = '../../ImageMatcher/ImageMatcher generate ' . $gameMediaAndDescriptorsPath . $fileName;
            $console = exec($execCommand);
        }
    }

    public function removeImageMatchEntryForLocation($intGameId, $intLocationId, $intMatchMediaID)
    {
        //Check if this is the only remaining QR code entry. If so, ONLY clear the image match media ID, DO NOT delete the whole row.
        $query = "SELECT * FROM qrcodes WHERE game_id = {$intGameId} AND link_id ={$intLocationId}";
        $result = Module::query($query);
        if(mysql_num_rows($result) == 1){
            $query = "UPDATE qrcodes SET match_media_id = '0' WHERE game_id = {$intGameId} AND link_id={$intLocationId} AND match_media_id = {$intMatchMediaID}";
            Module::query($query);
            deleteImageMatchXML($intMatchMediaID, $intGameId);
            return new returnData(0);
        }
        elseif(mysql_num_rows($result) > 1){
            $query = "DELETE FROM qrcodes WHERE game_id = {$intGameId} AND link_id={$intLocationId} AND match_media_id={$intMatchMediaID}";
            Module::query($query);
            deleteImageMatchXML($intMatchMediaID, $intGameId);
            return new returnData(0);
        }
        else{
            return new returnData(1);
        }
    }

    public function deleteImageMatchXML($mediaId, $gameId)
    {
        $query = "SELECT file_path FROM media WHERE media_id = '{$mediaId}' AND (game_id = '{$gameId}' OR game_id = '0')";
        $result = Module::query($query);

        if($med = mysql_fetch_object($result)){
            unlink("../../gamedata/".$gameId."/".substr($med->file_path, 0, -4).".xml");
        }
    }

    public function getLocationsWithQrCode($intGameId)
    {
        $query = "SELECT game_locations.*,game_qrcodes.qrcode_id,game_qrcodes.code,game_qrcodes.match_media_id, game_qrcodes.fail_text, f.active AS is_fountain
            FROM (SELECT * FROM locations WHERE game_id = {$intGameId}) AS game_locations
            LEFT JOIN (SELECT * FROM qrcodes WHERE game_id = {$intGameId}) AS game_qrcodes
            ON game_locations.location_id = game_qrcodes.link_id LEFT JOIN
            (SELECT location_id, active FROM fountains WHERE game_id = $intGameId) AS f
            ON game_locations.location_id = f.location_id";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);	
    }	

    public function getLocationsForPlayer($intGameId, $intPlayerID, $lat = 0, $lon = 0)
    {
        $arrayLocations = array();

        //Gets all non-spawned locations
        $query = "SELECT game_locations.*, gamefountains.fountain_id, gamefountains.spawn_probability, gamefountains.spawn_rate, gamefountains.max_amount, gamefountains.last_spawned, gamefountains.active FROM (SELECT * FROM locations WHERE game_id = {$intGameId}) AS game_locations LEFT JOIN (SELECT * FROM spawnables WHERE game_id = $intGameId) AS gamespawns ON game_locations.type = gamespawns.type AND game_locations.type_id = gamespawns.type_id LEFT JOIN (SELECT * FROM fountains WHERE game_id = $intGameId) AS gamefountains ON game_locations.location_id = gamefountains.location_id WHERE game_locations.latitude != '' AND game_locations.longitude != '' AND (spawnable_id IS NULL OR gamespawns.active = 0)";

        $rsLocations = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error" . mysql_error());

        $query = "SELECT full_quick_travel FROM games WHERE game_id = '{$intGameId}'";
        $fqtresult = Module::query($query);
        $fullQuickTravel = (mysql_fetch_object($fqtresult)->full_quick_travel == 1) ? true : false;

        while ($location = mysql_fetch_object($rsLocations)) {
            //If location and object it links to meet requirments, add it to the array

            //Does it Exist?
            switch ($location->type) {
                case 'Item':
                    $query = "SELECT icon_media_id FROM items WHERE game_id = {$intGameId} AND item_id = {$location->type_id} LIMIT 1";
                    break;
                case 'Node':
                    $query = "SELECT icon_media_id FROM nodes WHERE game_id = {$intGameId} AND node_id = {$location->type_id} LIMIT 1";
                    break;
                case 'Npc':
                    $query = "SELECT icon_media_id FROM npcs WHERE game_id = {$intGameId} AND npc_id = {$location->type_id} LIMIT 1";
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
                $rsObject = Module::query($query);
                $object = @mysql_fetch_object($rsObject);
                if (!$object || $object->public_to_map == 0) {
                    continue;
                }
            }
            else {
                $rsObject = Module::query($query);
                $object = @mysql_fetch_object($rsObject);
                if (!$object) {
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
                    Module::query($query);
                }
                if($location->item_qty >= $location->max_amount)
                {
                    $query = "UPDATE fountains SET last_spawned = now() WHERE fountain_id = ".$location->fountain_id;
                    Module::query($query);
                }
                $query = "UPDATE locations SET item_qty = ".$location->item_qty." WHERE game_id = {$intGameId} AND location_id = ".$location->location_id;
                Module::query($query);
            }

            if($location->type == 'Item' && $location->item_qty < 1 && $location->item_qty != -1)
            {
                continue;
            }

            //Does it meet it's requirements?
            if (!$this->objectMeetsRequirements($intGameId, $intPlayerID, 'Location', $location->location_id)) {
                continue;
            }

            //Special Case for Notes
            if($location->type == 'PlayerNote')
            {
                $query = "SELECT public_to_map, public_to_notebook, owner_id FROM notes WHERE note_id='{$location->type_id}' LIMIT 1";
                $result = Module::query($query);
                $note = mysql_fetch_object($result);
                //If note doesn't exist, or if it is neither public nor owned by the owner, skip it.
                if(!$note || !($note->public_to_map || $note->owner_id == $intPlayerID))
                {
                    continue;
                }
                if($note->public_to_notebook || $note->owner_id == $intPlayerId)
                    $location->allow_quick_travel = 1;
            }


            //If location's icon is not defined, use the object's icon
            if (!$location->icon_media_id) {
                $objectsIconMediaId = $object->icon_media_id;
                $location->icon_media_id = $objectsIconMediaId;
            }

            $location->delete_when_viewed = 0;

            if($fullQuickTravel) $location->allow_quick_travel = true;

            //Add it
            $arrayLocations[] = $location;
        }

        //Get all spawned locations (needs separate calculations, as requirements are not associated with each location)
        $query = "SELECT * FROM spawnables WHERE game_id = ".$intGameId." AND active = 1";
        $results = Module::query($query);
        while($spawnable = mysql_fetch_object($results)){

            //If spawnable and object it links to meet requirments, add it to the array

            //Does it Exist?
            switch ($spawnable->type) {
                case 'Item':
                    $query = "SELECT name as title, icon_media_id FROM items WHERE game_id = {$intGameId} AND item_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'Node':
                    $query = "SELECT title, icon_media_id FROM nodes WHERE game_id = {$intGameId} AND node_id = {$spawnable->type_id} LIMIT 1";
                    break;
                case 'Npc':
                    $query = "SELECT name as title, icon_media_id FROM npcs WHERE game_id = {$intGameId} AND npc_id = {$spawnable->type_id} LIMIT 1";
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

            $rsObject = Module::query($query);
            $object = @mysql_fetch_object($rsObject);
            if (!$object) {
                continue;
            }
            $spawnable->icon_media_id = $object->icon_media_id;
            $spawnable->title = $object->title;

            //Does it meet it's requirements?
            if (!$this->objectMeetsRequirements ($intGameId, $intPlayerID, 'Spawnable', $spawnable->spawnable_id)) {
                continue;
            }
            else{
            }


            //Create spawnables
            if($spawnable->location_bound_type == 'PLAYER')
            {
                if($lat == 0 && $lon == 0)
                {
                    //Find player location from log and set lat and lon accordingly
                    $query = "SELECT event_detail_1, event_detail_2 FROM player_log WHERE player_id = $intPlayerID AND (game_id = $intGameId OR game_id = 0) AND event_type = 'MOVE' AND deleted = 0 ORDER BY timestamp DESC LIMIT 1";
                    $result = Module::query($query);
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
                    $query = "SELECT DISTINCT player_id FROM player_log WHERE game_id = {$intGameId}  AND deleted = 0 AND timestamp >= NOW() - INTERVAL 20 MINUTE";
                    $result = Module::query($query);
                    $spawnable->amount *= mysql_num_rows($result);
                }
                $radius = Module::mToDeg($spawnable->max_area);
                $query = "SELECT * FROM locations WHERE game_id = {$intGameId} AND type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id." AND latitude < ". ($lat+$radius) ." AND latitude > ". ($lat-$radius) ." AND longitude < ". ($lon+$radius) ." AND longitude > ". ($lon-$radius);
                $result = Module::query($query);
                $numLocs = mysql_num_rows($result);
            }
            else if($spawnable->amount_restriction == 'TOTAL')
            {
                $query = "SELECT * FROM locations WHERE game_id = {$intGameId} AND type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id;
                $result = Module::query($query);
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
                    Locations::createLocationWithQrCode($intGameId, $spawnable->location_name, $spawnable->icon_media_id, $newLat, $newLon, $spawnable->error_range, $spawnable->type, $spawnable->type_id, 1, $spawnable->hidden, $spawnable->force_view, $spawnable->allow_quick_travel, $spawnable->wiggle, $spawnable->show_title, '', 0, "You've incorrectly encountered a spawnable! Weird...");
                }
                $query = "UPDATE spawnables SET last_spawned = now() WHERE spawnable_id = ".$spawnable->spawnable_id;
                Module::query($query);
                $secondsOfSpawning-=$spawnable->spawn_rate;
                if(location_bound_type != 'LOCATION') $secondsOfSpawning = 0; //Only simulate once unless location bound is a location
            }
            if($numLocs >= $spawnable->amount)
            {
                $query = "UPDATE spawnables SET last_spawned = now() WHERE spawnable_id = ".$spawnable->spawnable_id;
                Module::query($query);
            } 

            //Destroy spawnables
            if($spawnable->time_to_live != -1)
            {
                /*$query = "DELETE game_locations, game_qrcodes 
                  FROM (SELECT * FROM locations WHERE game_id = {$intGameId}) AS game_locations 
                  LEFT_JOIN (SELECT * FROM qrcodes WHERE game_id = {$intGameId}) AS game_qrcodes ON game_locations.location_id = game_qrcodes.link_id 
                  WHERE type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id." AND ((spawnstamp < NOW() - INTERVAL ".$spawnable->time_to_live." SECOND) OR (type = 'Item' AND item_qty = 0))";
                 */
                $query = "DELETE locations, qrcodes FROM locations, qrcodes WHERE locations.game_id = {$intGameId} AND qrcodes.game_id = {$intGameId} AND locations.location_id = qrcodes.link_id AND locations.type = '".$spawnable->type."' AND locations.type_id = ".$spawnable->type_id." AND ((locations.spawnstamp < NOW() - INTERVAL ".$spawnable->time_to_live." SECOND) OR (locations.type = 'Item' AND locations.item_qty = 0))";
                Module::query($query);
            }

            $query = "SELECT * FROM locations WHERE game_id = {$intGameId} AND type = '".$spawnable->type."' AND type_id = ".$spawnable->type_id;
            $locresults = Module::query($query);
            while($locobj = mysql_fetch_object($locresults))
            {
                //If location's icon is not defined, use the object's icon
                if (!$locobj->icon_media_id) 
                    $locobj->icon_media_id = $object->icon_media_id;
                $locobj->delete_when_viewed = $spawnable->delete_when_viewed && $spawnable->active;

                //Add it
                if($locobj->type != 'Item' || ($locobj->item_qty == -1 || $locobj->item_qty > 0))
                    $arrayLocations[] = $locobj;
            }
        }

        //Add the others players from this game, making them look like reqular locations
        $playersJSON = Players::getOtherPlayersForGame($intGameId, $intPlayerID);
        $playersArray = $playersJSON->data;

        foreach ($playersArray as $player) {

            $tmpPlayerObject = new stdClass();

            $tmpPlayerObject->name = $player->user_name;
            if($player->display_name) $tmpPlayerObject->name = $player->display_name;
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

        }

        return new returnData(0, $arrayLocations);

    }

    public function getLocation($intGameId, $intLocationID)
    {
        $query = "SELECT * FROM locations WHERE game_id = {$intGameId} AND location_id = {$intLocationID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        $location = mysql_fetch_object($rsResult);
        if (!$location) return new returnData(2, NULL, "No matching location");
        return new returnData(0, $location);
    }

    public function createLocation($intGameId, $strLocationName, $intIconMediaID, 
            $dblLatitude, $dblLongitude, $dblError,
            $strObjectType, $intObjectID,
            $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation)
    {	
        Locations::createLocationWithQrCode($intGameId, $strLocationName, $intIconMediaID, 
                $dblLatitude, $dblLongitude, $dblError,
                $strObjectType, $intObjectID,
                $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation, $qrCode = '', 0);
    }

    public function createLocationWithQrCode($intGameId, $strLocationName, $intIconMediaID, 
            $dblLatitude, $dblLongitude, $dblError,
            $strObjectType, $intObjectID,
            $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation, $qrCode = '', $imageMatchId, $errorText)
    {
        if (!$intQuantity) $intQuantity = 1;
        if (!$boolAllowQuickTravel) $boolAllowQuickTravel = 0;

        $strLocationName = addslashes($strLocationName);
        //if ($dblError < 5) $dblError = 25; // <-- NO!

        //Check the object Type is good or null
        if ( !Locations::isValidObjectType($strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        $query = "INSERT INTO locations 
            (game_id, name, icon_media_id, latitude, longitude, error, 
             type, type_id, item_qty, hidden, force_view, allow_quick_travel, wiggle, show_title)
            VALUES ('{$intGameId}','{$strLocationName}', '{$intIconMediaID}',
                    '{$dblLatitude}','{$dblLongitude}','{$dblError}',
                    '{$strObjectType}','{$intObjectID}','{$intQuantity}',
                    '{$boolHidden}','{$boolForceView}', '{$boolAllowQuickTravel}', '{$boolAllowWiggle}', '{$boolDisplayAnnotation}')";


        Module::query($query);

        if (mysql_error()) {
            return new returnData(3, NULL, "SQL Error");
        }

        $newId = mysql_insert_id();
        //Create a coresponding QR Code
        QRCodes::createQRCode($intGameId, "Location", $newId, $qrCode, $imageMatchId, $errorText);

        return new returnData(0, $newId);

    }

    public function updateLocation($intGameId, $intLocationID, $strLocationName, $intIconMediaID, 
            $dblLatitude, $dblLongitude, $dblError,
            $strObjectType, $intObjectID,
            $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotations)
    {
        $strLocationName = addslashes($strLocationName);
        //if ($dblError < 5) $dblError = 25; // <-- NO!

        //Check the object Type is good or null
        if ( !$this->isValidObjectType($strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        $query = "UPDATE locations
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
                 WHERE game_id = {$intGameId} AND location_id = '{$intLocationID}'";


        Module::query($query);
        if (mysql_error()) {
            return new returnData(3, NULL, "SQL Error");		
        }

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }

    }	

    public function updateLocationWithQrCode($intGameId, $intLocationID, $strLocationName, $intIconMediaID, $dblLatitude, $dblLongitude, $dblError, $strObjectType, $intObjectID, $intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $boolAllowWiggle, $boolDisplayAnnotation, $qrCode, $imageMatchId, $errorText)
    {
        $errorText = addslashes($errorText);
        $strLocationName = addslashes($strLocationName);
        //if ($dblError < 5) $dblError = 25; // <-- NO!

        //Check the object Type is good or null
        if ( !$this->isValidObjectType($strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        $query = "UPDATE locations SET 
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
                     WHERE game_id = {$intGameId} AND location_id = '{$intLocationID}'";
        Module::query($query);
        if (mysql_error()) {
            return new returnData(3, NULL, "SQL Error" . mysql_error());		
        }


        $query = "UPDATE qrcodes
            SET 
            code = '{$qrCode}', fail_text = '{$errorText}'
            WHERE game_id = {$intGameId} AND link_type = 'Location' AND link_id = '{$intLocationID}'";
        Module::query($query);


        if (mysql_error()) {
            return new returnData(3, NULL, "SQL Error on query: {$query} Error:" . mysql_error());		
        }		

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }

    }	

    public function deleteLocation($intGameId, $intLocationId)
    {
        //Lookup the name of the item
        $query = "DELETE FROM locations 
            WHERE game_id = {$intGameId} AND location_id = '{$intLocationId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        //Delete any QR Codes that point here
        QRCodes::deleteQRCodeCodesForLink($intGameId, "Location", $intLocationId);


        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }	
    }

    public function deleteLocationsForObject($intGameId, $strObjectType, $intObjectId)
    {
        //Check the object Type is good or null
        if ( !Locations::isValidObjectType($strObjectType) or !strlen($strObjectType) > 0 )
            return new returnData(4, NULL, "invalid object type");

        //Delete the Locations and related QR Codes
        $query = "DELETE locations, qrcodes 
            FROM locations
            LEFT JOIN qrcodes
            ON locations.game_id = qrcodes.game_id AND locations.location_id = qrcodes.link_id
            WHERE locations.type = '{$strObjectType}' AND locations.type_id = '{$intObjectId}' AND qrcodes.link_type = 'Location'";


        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error" . mysql_error());

        if (mysql_affected_rows())
            return new returnData(0, TRUE);
        else
            return new returnData(0, FALSE);
    }	

    public function objectTypeOptions()
    {	
        $options = Locations::lookupObjectTypeOptionsFromSQL();
        return new returnData(0, $options);
    }

    private function isValidObjectType($strObjectType)
    {
        $validTypes = Locations::lookupObjectTypeOptionsFromSQL();
        return in_array($strObjectType, $validTypes);
    }

    private function lookupObjectTypeOptionsFromSQL()
    {
        $query = "SHOW COLUMNS FROM locations LIKE 'type'";
        $result = Module::query( $query );
        $row = mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }	
}
