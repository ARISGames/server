<?php
require_once('utils.php');
require_once('returnData.php');
require_once('/var/www/html/server/config.class.php');

abstract class Module extends Utils
{
    protected function giveItemToPlayer($gameId, $itemId, $playerId, $qtyToGive=1)
    {
        $currentQty = Module::itemQtyInPlayerInventory($gameId, $playerId, $itemId);
        $item = Items::getItem($gameId, $itemId)->data;
        $maxQty = $item->max_qty_in_inventory; 

        if($currentQty + $qtyToGive > $maxQty  && $maxQty != -1)
            $qtyToGive =  $maxQty - $currentQty;

        if($qtyToGive < 1) return 0;
        else
        {
            Module::adjustQtyForPlayerItem($gameId, $itemId, $playerId, $qtyToGive);

            //check log if item has already been viewed. If yes, set item to viewed in database
            $viewed = Module::queryObject("SELECT * FROM player_log WHERE player_id = {$playerId} AND game_id = {$gameId} AND event_type = 'VIEW_ITEM' AND event_detail_1 = {$itemId} AND deleted = 0;");
            if($viewed)
                Module::query("UPDATE player_items SET viewed = 1 WHERE game_id = {$gameId} AND player_id = {$playerId} AND item_id = {$itemId}");

            return $qtyToGive;
        }
    }

    protected function getPlayerCountForGame($gameId)
    {
        $countObj = Module::queryObject("SELECT COUNT(DISTINCT player_id) AS count FROM player_log WHERE game_id = $gameId AND timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 20 MINUTE) AND NOW()");
        return new returnData(0, $countObj);
    }

    protected function setItemCountForPlayer($gameId, $intItemId, $playerId, $qty)
    {
        $currentQty = Module::itemQtyInPlayerInventory($gameId, $playerId, $intItemId);
        $item = Items::getItem($gameId, $intItemId)->data;
        $maxQty = $item->max_qty_in_inventory; 

        if($qty > $maxQty  && $maxQty != -1)
            $qty = $maxQty;

        if($qty < 0) return 0;
        else
        {
            $amountToAdjust = $qty - $currentQty;
            Module::adjustQtyForPlayerItem($gameId, $intItemId, $playerId, $amountToAdjust);
            return $qty;
        }
    }

    protected function takeItemFromPlayer($gameId, $intItemId, $playerId, $qtyToTake=1)
    {
        Module::adjustQtyForPlayerItem($gameId, $intItemId, $playerId, -$qtyToTake);
    }

    protected function removeItemFromAllPlayerInventories($gameId, $intItemId)
    {
        Module::query("DELETE FROM player_items WHERE item_id = {$intItemId} AND game_id = '{$gameId}'");
    }

    protected function adjustQtyForPlayerItem($gameId, $intItemId, $playerId, $amountOfAdjustment)
    {
        //Get any existing record
        $result = Module::query("SELECT * FROM player_items WHERE player_id = $playerId AND item_id = $intItemId AND game_id = '{$gameId}' LIMIT 1");

        if ($existingPlayerItem = @mysql_fetch_object($result))
        {
            //Check if this change will make the qty go to < 1, if so delete the record
            $newQty = $existingPlayerItem->qty + $amountOfAdjustment;
            if($newQty < 1)
                Module::query("DELETE FROM player_items WHERE player_id = $playerId AND item_id = $intItemId AND game_id = '{$gameId}'");
            else {
                //Update the qty
                $query = "UPDATE player_items 
                    SET qty = $newQty
                    WHERE player_id = $playerId AND item_id = $intItemId AND game_id = '{$gameId}'";
                Module::query($query);
            }
        }
        else if ($amountOfAdjustment > 0)
        {
            $query = "INSERT INTO player_items 
                (game_id,player_id, item_id, qty) VALUES ({$gameId},$playerId, $intItemId, $amountOfAdjustment)
                ON duplicate KEY UPDATE item_id = $intItemId";
            Module::query($query);
        }

        if($amountOfAdjustment > 0)
            Module::processGameEvent($playerId, $gameId, Module::kLOG_PICKUP_ITEM, $intItemId, $amountOfAdjustment);
        else
            Module::processGameEvent($playerId, $gameId, Module::kLOG_DROP_ITEM, $intItemId, -1*$amountOfAdjustment);
    }

    protected function decrementItemQtyAtLocation($gameId, $intLocationId, $intQty = 1)
    {
        //If this location has a null item_qty, decrementing it will still be a null
        $query = "UPDATE locations 
            SET item_qty = item_qty-{$intQty}
        WHERE location_id = '{$intLocationId}' AND item_qty > 0 AND game_id = '{$gameId}'";
        Module::query($query);    	
    }

    protected function giveNoteToWorld($gameId, $noteId, $floatLat, $floatLong)
    {
        $query = "SELECT * FROM locations WHERE type = 'PlayerNote' AND type_id = '{$noteId}' AND game_id = '{$gameId}'";	
        $result = Module::query($query);

        if ($existingNote = @mysql_fetch_object($result))
        {
            //We have a match
            $query = "UPDATE locations
                SET latitude = '{$floatLat}', longitude = '{$floatLong}'
                WHERE location_id = {$existingNote->location_id} AND game_id = '{$gameId}'";
            Module::query($query);
            $obj = Module::queryObject("SELECT title, owner_id FROM notes WHERE note_id = '{$noteId}'");
        }
        else
        {
            $error = 100; //Use 100 meters
            $query = "SELECT title, owner_id FROM notes WHERE note_id = '{$noteId}'";
            $result = Module::query($query);
            $obj = @mysql_fetch_object($result);
            $title = $obj->title;

            $query = "INSERT INTO locations (game_id, name, type, type_id, icon_media_id, latitude, longitude, error, item_qty, hidden, force_view, allow_quick_travel)
                VALUES ('{$gameId}', '{$title}','PlayerNote','{$noteId}', ".Module::kPLAYER_NOTE_DEFAULT_ICON.", '{$floatLat}','{$floatLong}', '{$error}','1',0,0,0)";
            Module::query($query);

            $newId = mysql_insert_id();
        }
        Module::processGameEvent($obj->owner_id, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM, $noteId, $floatLat, $floatLong);
        Module::processGameEvent($obj->owner_id, $gameId, Module::kLOG_DROP_NOTE, $noteId, $floatLat, $floatLong);
    }

    protected static function saveContentNoAuthentication($gameId, $intObjectContentId, $intFolderId, $strContentType, $intContentId, $intSortOrder)
    {
        if($intObjectContentId)
        {
            $query = "UPDATE folder_contents
                SET 
                folder_id = '{$intFolderId}',
                          content_type = '{$strContentType}',
                          content_id = '{$intContentId}',
                          previous_id = '{$intSortOrder}'
                              WHERE 
                              object_content_id = {$intObjectContentId} AND
                              game_id = {$gameId}
            ";

            Module::query($query);
            return new returnData(0);
        }	
        else
        {
            $query = "INSERT INTO folder_contents 
                (game_id, folder_id, content_type, content_id, previous_id)
                VALUES 
                ('{$gameId}','{$intFolderId}', '{$strContentType}', '{$intContentId}', '{$intSortOrder}')";

            Module::query($query);
            $newContentId = mysql_insert_id();

            return new returnData(0, $newContentId, NULL);
        }
    }

    protected function metersBetweenLatLngs($lat1, $lon1, $lat2, $lon2)
    { 
        $theta = $lon1 - $lon2; 
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
        $dist = acos($dist); 
        $dist = rad2deg($dist); 
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
        return ($miles * 1609.344); //convert to meters
    }

    protected function randomLatLnWithinRadius($originLat, $originLon, $minDistTrueScale, $maxDistTrueScale)
    {
        $radius = ((rand(0,1000)/1000)*($maxDistTrueScale-$minDistTrueScale)) + $minDistTrueScale;
        $xDelt = rand(-1000,1000)/1000;
        $yDelt = rand(-1000,1000)/1000;

        $distLargeScale = Module::metersBetweenLatLngs($originLat, $originLon, $originLat+$yDelt, $originLon+$xDelt);
        $maxDistLargeScale = ($distLargeScale/$radius) * $maxDistTrueScale;
        $xDelt = $xDelt * ($maxDistTrueScale/$maxDistLargeScale);
        $yDelt = $yDelt * ($maxDistTrueScale/$maxDistLargeScale);
        $locObj->lat = $originLat + $yDelt;
        $locObj->lon = $originLon + $xDelt;

        return $locObj;
    }

    protected function playerHasLog($gameId, $playerId, $strEventType, $strEventDetail)
    {
        $query = "SELECT 1 FROM player_log 
            WHERE player_id = '{$playerId}' AND
            game_id = '{$gameId}' AND
            event_type = '{$strEventType}' AND
            event_detail_1 = '{$strEventDetail}' AND
            deleted = 0
            LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_num_rows($rsResult) > 0) return true;
        else return false;	
    }

    protected function playerHasItem($gameId, $playerId, $itemId, $minItemQuantity)
    {
        if (!$minItemQuantity) $minItemQuantity = 1;
        $qty = Module::itemQtyInPlayerInventory($gameId, $playerId, $itemId);
        if ($qty >= $minItemQuantity) return true;
        else return false;
    }		

    protected function playerHasTaggedItem($gameId, $playerId, $tagId, $minItemQuantity)
    {
        if (!$minItemQuantity) $minItemQuantity = 1;
        $qty = Module::itemTagQtyInPlayerInventory($gameId, $playerId, $tagId);
        if ($qty >= $minItemQuantity) return true;
        else return false;
    }		

    protected function itemQtyInPlayerInventory($gameId, $playerId, $itemId)
    {
        $query = "SELECT qty FROM player_items 
            WHERE player_id = '{$playerId}' 
            AND item_id = '{$itemId}' AND game_id = '{$gameId}' LIMIT 1";

        $rsResult = Module::query($query);
        $playerItem = mysql_fetch_object($rsResult);

        if ($playerItem) return $playerItem->qty;
        else             return 0;
    }	    

    protected function itemTagQtyInPlayerInventory($gameId, $playerId, $tagId)
    {
        $query = "SELECT object_id FROM object_tags WHERE tag_id = '{$tagId}' AND object_type = 'ITEM'";
        $result = Module::query($query);
        $qty = 0;
        while($obj = mysql_fetch_object($result))
            $qty+=Module::itemQtyInPlayerInventory($gameId, $playerId, $obj->object_id);
        return $qty;
    }	    

    protected function playerHasUploadedMediaItemWithinDistance($gameId, $playerId, $dblLatitude, $dblLongitude, $dblDistanceInMeters, $qty, $mediaType) 
    {
        if($dblLatitude == "" || $dblLongitude == "" || $dblDistanceInMeters == "") return false; //MySQL Math segment freaks out if there is nothing in them ('0' is ok)
        $query = "SELECT game_items.*
            FROM player_log, (SELECT * FROM items WHERE game_id = '{$gameId}') AS game_items
            WHERE 
            player_log.player_id = '{$playerId}' AND
            player_log.game_id = '{$gameId}' AND
            player_log.event_type = '". $mediaType ."' AND
            player_log.event_detail_1 = game_items.item_id AND
            player_log.deleted = 0 AND

            (((acos(sin(({$dblLatitude}*pi()/180)) * sin((origin_latitude*pi()/180))+cos(({$dblLatitude}*pi()/180)) * 
                    cos((origin_latitude*pi()/180)) * 
                    cos((({$dblLongitude} - origin_longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344*1000) < {$dblDistanceInMeters}";
        $rsResult = Module::query($query);
        if (mysql_error()) return false;
        if (@mysql_num_rows($rsResult) >= $qty) return true;


        if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM)
            $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN (SELECT * FROM locations WHERE game_id = '{$gameId}') AS game_locations ON notes.note_id = game_locations.type_id WHERE owner_id = '{$playerId}'";
        else if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE)
            $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN (SELECT * FROM locations WHERE game_id = '{$gameId}') AS game_locations ON notes.note_id = game_locations.type_id WHERE owner_id = '{$playerId}' AND note_content.type='PHOTO'";
        else if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO)
            $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN (SELECT * FROM locations WHERE game_id = '{$gameId}') AS game_locations ON notes.note_id = game_locations.type_id WHERE owner_id = '{$playerId}' AND note_content.type='AUDIO'";
        else if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO)
            $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN (SELECT * FROM locations WHERE game_id = '{$gameId}') AS game_locations ON notes.note_id = game_locations.type_id WHERE owner_id = '{$playerId}' AND note_content.type='VIDEO'";
        $queryappendation = "AND (((acos(sin(({$dblLatitude}*pi()/180)) * sin((game_locations.latitude*pi()/180))+cos(({$dblLatitude}*pi()/180)) * 
            cos((game_locations.latitude*pi()/180)) * 
            cos((({$dblLongitude} - game_locations.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344*1000) < {$dblDistanceInMeters}";
        $result = Module::query($query.$queryappendation);
        if (mysql_num_rows($result) >= $qty) return true;
        else return false;
    }	    

    protected function playerHasNote($gameId, $playerId, $qty)
    {
        $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND owner_id = '{$playerId}' AND parent_note_id = 0 AND incomplete = '0'";
        $result = Module::query($query);
        if (mysql_num_rows($result) >= $qty) return true;
        return false;
    }

    protected function playerHasNoteWithTag($gameId, $playerId, $tag, $qty)
    {
        $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND owner_id = '{$playerId}' AND parent_note_id = 0 AND incomplete = '0'";
        $result = Module::query($query);
        $num = 0;
        while($noteobj = mysql_fetch_object($result))
        {
            $query = "SELECT * FROM note_tags WHERE note_id='{$noteobj->note_id}' AND tag_id='{$tag}'";
            $result2 = Module::query($query);
            if(mysql_num_rows($result2)>0) $num++;
        }
        if(($qty == "" && $num > 0) || $num > $qty)
            return true;
        else
            return false;
    }

    protected function playerHasNoteWithComments($gameId, $playerId, $qty)
    {
        $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND owner_id = '{$playerId}' AND incomplete = '0'";
        $result = Module::query($query);
        while($note_id = mysql_fetch_object($result))
        {
            $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = '{$note_id->note_id}'";
            $res = Module::query($query);
            if (@mysql_num_rows($res) >= $qty) return true;
        }
        return false;
    }

    protected function playerHasNoteWithLikes($gameId, $playerId, $qty)
    {
        $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND owner_id = '{$playerId}' AND incomplete = '0'";
        $result = Module::query($query);
        while($note_id = mysql_fetch_object($result))
        {
            $query = "SELECT player_id FROM note_likes WHERE note_id = '{$note_id->note_id}'";
            $res = Module::query($query);
            if (@mysql_num_rows($res) >= $qty) return true;
        }
        return false;
    }

    protected function playerHasGivenNoteComments($gameId, $playerId, $qty)
    {
        $query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND parent_note_id != 0";
        $result = Module::query($query);
        if (@mysql_num_rows($result) >= $qty) return true;
        return false;
    }

    protected function objectMeetsRequirements ($gameId, $playerId, $strObjectType, $intObjectId)
    {		
        //Fetch the requirements
        $query = "SELECT requirement,
            requirement_detail_1,requirement_detail_2,requirement_detail_3,requirement_detail_4,
            boolean_operator, not_operator
                FROM requirements 
                WHERE content_type = '{$strObjectType}' AND content_id = '{$intObjectId}' AND game_id = '{$gameId}'";
        $rsRequirments = Module::query($query);

        $andsMet = false;
        $requirementsExist = false;
        while ($requirement = mysql_fetch_array($rsRequirments)) {
            $requirementsExist = true;
            //Check the requirement

            $requirementMet = false;
            switch ($requirement['requirement']) {
                //Log related
                case Module::kREQ_PLAYER_VIEWED_ITEM:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_ITEM, 
                            $requirement['requirement_detail_1']);
                    break;
                case Module::kREQ_PLAYER_VIEWED_NODE:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_NODE, 
                            $requirement['requirement_detail_1']);
                    break;
                case Module::kREQ_PLAYER_VIEWED_NPC:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_NPC, 
                            $requirement['requirement_detail_1']);
                    break;
                case Module::kREQ_PLAYER_VIEWED_WEBPAGE:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_WEBPAGE, 
                            $requirement['requirement_detail_1']);
                    break;
                case Module::kREQ_PLAYER_VIEWED_AUGBUBBLE:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_AUGBUBBLE, 
                            $requirement['requirement_detail_1']);
                    break;
                case Module::kREQ_PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_RECEIVE_WEBHOOK, 
                            $requirement['requirement_detail_1']);
                    break;
                    //Inventory related	
                case Module::kREQ_PLAYER_HAS_ITEM:
                    $requirementMet = Module::playerHasItem($gameId, $playerId, 
                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
                    break;
                case Module::kREQ_PLAYER_HAS_TAGGED_ITEM:
                    $requirementMet = Module::playerHasTaggedItem($gameId, $playerId,
                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
                    break;
                    //Data Collection
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM:
                    $requirementMet = Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, 
                            $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM);
                    break;
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO:
                    $requirementMet = Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, 
                            $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO);
                    break;
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO:
                    $requirementMet = Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, 
                            $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO);
                    break;
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE:
                    $requirementMet = Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, 
                            $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE);
                    break;
                case Module::kREQ_PLAYER_HAS_COMPLETED_QUEST:
                    $requirementMet = Module::playerHasLog($gameId, $playerId, Module::kLOG_COMPLETE_QUEST, 
                            $requirement['requirement_detail_1']);
                    break;
                case Module::kREQ_PLAYER_HAS_NOTE:
                    $requirementMet = Module::playerHasNote($gameId, $playerId, $requirement['requirement_detail_2']);
                    break;
                case Module::kREQ_PLAYER_HAS_NOTE_WITH_TAG:
                    $requirementMet = Module::playerHasNoteWithTag($gameId, $playerId, $requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
                    break;
                case Module::kREQ_PLAYER_HAS_NOTE_WITH_LIKES:
                    $requirementMet = Module::playerHasNoteWithLikes($gameId, $playerId, $requirement['requirement_detail_2']);
                    break;
                case Module::kREQ_PLAYER_HAS_NOTE_WITH_COMMENTS:
                    $requirementMet = Module::playerHasNoteWithComments($gameId, $playerId, $requirement['requirement_detail_2']);
                    break;
                case Module::kREQ_PLAYER_HAS_GIVEN_NOTE_COMMENTS:
                    $requirementMet = Module::playerHasGivenNoteComments($gameId, $playerId, $requirement['requirement_detail_2']);
                    break;
            }//switch

            //Account for the 'NOT's
            if($requirement['not_operator'] == "NOT") $requirementMet = !$requirementMet;

            if ($requirement['boolean_operator'] == "AND" && $requirementMet == false) return false;
            if ($requirement['boolean_operator'] == "AND" && $requirementMet == true)  $andsMet = true;
            if ($requirement['boolean_operator'] == "OR"  && $requirementMet == true)  return true;
            if ($requirement['boolean_operator'] == "OR"  && $requirementMet == false) $requirementsMet = false;
        }

        if (!$requirementsExist) return true;
        if ($andsMet)            return true;
        else                     return false;
    }	

    protected function applyPlayerStateChanges($gameId, $playerId, $strEventType, $strEventDetail)
    {	
        $changeMade = false;

        //Fetch the state changes
        $query = "SELECT * FROM player_state_changes 
            WHERE event_type = '{$strEventType}'
            AND event_detail = '{$strEventDetail}' AND game_id = '{$gameId}'";

        $rsStateChanges = Module::query($query);

        while ($stateChange = mysql_fetch_array($rsStateChanges)) {

            //Check the requirement
            switch ($stateChange['action']) {
                case Module::kPSC_GIVE_ITEM:
                    //echo 'Running a GIVE_ITEM';
                    Module::giveItemToPlayer($gameId, $stateChange['action_detail'], $playerId,$stateChange['action_amount']);
                    $changeMade = true;
                    break;
                case Module::kPSC_TAKE_ITEM:
                    //echo 'Running a TAKE_ITEM';
                    Module::takeItemFromPlayer($gameId, $stateChange['action_detail'], $playerId,$stateChange['action_amount']);
                    $changeMade = true;
                    break;
            }
        }//stateChanges loop

        return $changeMade;
    }

    /*
     * All Events are to come through this gateway-
     * Takes events and appends them to the log, completes quests, and fires off webhooks accordingly
     */
    protected function processGameEvent($playerId, $gameId, $eventType, $eventDetail1='N/A', $eventDetail2='N/A', $eventDetail3='N/A', $eventDetail4='N/A')
    {
        //Module::serverErrorLog("Module::processGameEvent: playerId:$playerId, gameId:$gameId, eventType:$eventType, eventDetail1:$eventDetail1, eventDetail2:$eventDetail2, eventDetail3:$eventDetail3, eventDetail4:$eventDetail4");
        Module::appendLog($playerId, $gameId, $eventType, $eventDetail1, $eventDetail2, $eventDetail3);
        Module::applyPlayerStateChanges($gameId, $playerId, $eventType, $eventDetail1);

        $dirtybit = true;
        while($dirtybit)
        {
            $unfinishedQuests = Module::getUnfinishedQuests($playerId, $gameId);
            $unfiredWebhooks = Module::getUnfiredWebhooks($playerId, $gameId);

            $dirtybit = false;
            foreach($unfinishedQuests as $unfinishedQuest)
            {
                if(Module::questIsCompleted($playerId, $gameId, $unfinishedQuest->quest_id))
                {
                    Module::appendLog($playerId, $gameId, Module::kLOG_COMPLETE_QUEST, $unfinishedQuest->quest_id);
                    $dirtybit = true;
                }
            }

            foreach($unfiredWebhooks as $unfiredWebhook)
            {
                if(Module::hookShouldBeFired($playerId, $gameId, $unfiredWebhook->web_hook_id))
                    Module::fireOffWebhook($playerId, $gameId, $unfiredWebhook->web_hook_id);//NOTE- Does NOT set dirtybit
            }
        }

        $shouldCheckSpawnablesForDeletion = true;
        switch($eventType)
        {
            case Module::kLOG_VIEW_ITEM:
                $type = "Item";
                break;
            case Module::kLOG_VIEW_NODE:
                $type = "Node";
                break;
            case Module::kLOG_VIEW_NPC:
                $type = "Npc";
                break;
            case Module::kLOG_VIEW_WEBPAGE:
                $type = "WebPage";
                break;
            case Module::kLOG_VIEW_AUGBUBBLE:
                $type = "AugBubble";
                break;
            default:
                $shouldCheckSpawnablesForDeletion = false;
        }

        if($shouldCheckSpawnablesForDeletion)
            Module::checkSpawnablesForDeletion($gameId, $eventDetail2, $type, $eventDetail1);
    }

    protected function checkSpawnablesForDeletion($gameId, $locationId, $type, $typeId)
    {
        //Clean up spawnables that ought to be removed after viewing
        $query = "SELECT * FROM spawnables WHERE game_id = $gameId AND active = 1 AND type = '$type' AND type_id = $typeId LIMIT 1";

        $result = Module::query($query);
        if(($obj = mysql_fetch_object($result)) && $obj->delete_when_viewed == 1 && $obj->active == 1) 
        {
            $query = "DELETE locations, qrcodes FROM locations LEFT JOIN qrcodes ON locations.location_id = qrcodes.link_id WHERE location_id = $locationId AND locations.game_id = '{$gameId}'";
            Module::query($query);
        }
    }

    protected function appendLog($playerId, $gameId, $eventType, $eventDetail1='N/A', $eventDetail2='N/A', $eventDetail3='N/A')
    {
        $query = "INSERT INTO player_log (player_id, game_id, event_type, event_detail_1, event_detail_2, event_detail_3) VALUES ({$playerId},{$gameId},'{$eventType}','{$eventDetail1}','{$eventDetail2}','{$eventDetail3}')";
        Module::query($query);
    }

    protected function getUnfinishedQuests($playerId, $gameId)
    {
        //Get all quests for game
        $query = "SELECT * FROM quests WHERE game_id = '{$gameId}'";
        $result = Module::query($query);
        $gameQuests = array();
        while($gameQuest = mysql_fetch_object($result))
            $gameQuests[] = $gameQuest;

        //Get all completed quests by player
        $query = "SELECT * FROM player_log WHERE player_id = $playerId AND game_id = $gameId AND event_type = 'COMPLETE_QUEST' AND deleted = 0;";
        $result = Module::query($query);
        $playerCompletedQuests = array();
        while($playerCompletedQuest = mysql_fetch_object($result))
        {
            $playerCompletedQuests[] = $playerCompletedQuest;
        }

        //Cross reference lists to remove already-completed quests
        $unfinishedQuests = array();
        foreach($gameQuests as $gameQuest)
        {
            $questAlreadyCompleted = false;
            foreach($playerCompletedQuests as $playerCompletedQuest)
            {
                if($gameQuest->quest_id == $playerCompletedQuest->event_detail_1) $questAlreadyCompleted = true;
            }
            if(!$questAlreadyCompleted) $unfinishedQuests[] = $gameQuest;
        }

        return $unfinishedQuests;	
    }

    protected function getUnfiredWebhooks($playerId, $gameId)
    {
        //Get all webhooks for game
        $query = "SELECT * FROM web_hooks WHERE game_id = '{$gameId}' AND incoming = 0";
        $result = Module::query($query);
        $gameWebhooks = array();
        while($gameWebhook = mysql_fetch_object($result))
            $gameWebhooks[] = $gameWebhook;

        //Get all webhooks fired by player
        $query = "SELECT * FROM player_log WHERE player_id = $playerId AND game_id = $gameId AND event_type = 'SEND_WEBHOOK' AND deleted = 0;";
        $result = Module::query($query);
        $playerFiredWebhooks = array();
        while($playerFiredWebhook = mysql_fetch_object($result))
            $playerFiredWebhooks[] = $playerFiredWebhook;

        //Cross reference lists to remove already-fired webhooks
        $unfiredWebhooks = array();
        foreach($gameWebhooks as $gameWebhook)
        {
            $webhookAlreadyFired = false;
            foreach($playerFiredWebhooks as $playerFiredWebhook)
            {
                if($gameWebhook->web_hook_id == $playerFiredWebhook->event_detail_1) $webhookAlreadyFired = true;
            }
            if(!$webhookAlreadyFired) $unfiredWebhooks[] = $gameWebhook;
        }

        return $unfiredWebhooks;	
    }

    protected function questIsCompleted($playerId, $gameId, $questId)
    {
        return Module::objectMeetsRequirements($gameId, $playerId, 'QuestComplete', $questId);
    }

    protected function hookShouldBeFired($playerId, $gameId, $webhookId)
    {
        return Module::objectMeetsRequirements($gameId, $playerId, 'OutgoingWebhook', $webhookId);
    }

    protected function fireOffWebHook($playerId, $gameId, $webHookId)
    {
        Module::appendLog($playerId, $gameId, "SEND_WEBHOOK", $webHookId);

        $webHook = Module::queryObject("SELECT * FROM web_hooks WHERE web_hook_id = '{$webHookId}' LIMIT 1");
        $name = str_replace(" ", "", $webHook->name);
        $name = str_replace("{playerId}", $playerId, $name);
        $url = $webHook->url . "?hook=" . $name . "&wid=" . $webHook->web_hook_id . "&gameid=" . $gameId . "&playerid=" . $playerId; 

	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_exec($ch);
	curl_close($ch);
    }
}
?>
