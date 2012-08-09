<?php
require_once('../../config.class.php');
require_once('returnData.class.php');
require_once('qrcodes.php');

abstract class Module
{
  //constants for player_log table enums
  const kLOG_LOGIN = 'LOGIN';
  const kLOG_MOVE = 'MOVE';
  const kLOG_PICKUP_ITEM = 'PICKUP_ITEM';
  const kLOG_DROP_ITEM = 'DROP_ITEM';
  const kLOG_DROP_NOTE = 'DROP_NOTE';
  const kLOG_DESTROY_ITEM = 'DESTROY_ITEM';
  const kLOG_VIEW_ITEM = 'VIEW_ITEM';
  const kLOG_VIEW_NODE = 'VIEW_NODE';
  const kLOG_VIEW_NPC = 'VIEW_NPC';
  const kLOG_VIEW_WEBPAGE = 'VIEW_WEBPAGE';
  const kLOG_VIEW_AUGBUBBLE = 'VIEW_AUGBUBBLE';
  const kLOG_VIEW_MAP = 'VIEW_MAP';
  const kLOG_VIEW_QUESTS = 'VIEW_QUESTS';
  const kLOG_VIEW_INVENTORY = 'VIEW_INVENTORY';
  const kLOG_ENTER_QRCODE = 'ENTER_QRCODE';
  const kLOG_UPLOAD_MEDIA_ITEM = 'UPLOAD_MEDIA_ITEM';
  const kLOG_UPLOAD_MEDIA_ITEM_IMAGE = 'UPLOAD_MEDIA_ITEM_IMAGE';
  const kLOG_UPLOAD_MEDIA_ITEM_AUDIO = 'UPLOAD_MEDIA_ITEM_AUDIO';
  const kLOG_UPLOAD_MEDIA_ITEM_VIDEO = 'UPLOAD_MEDIA_ITEM_VIDEO';

  const kLOG_RECEIVE_WEBHOOK = 'RECEIVE_WEBHOOK';
  const kLOG_COMPLETE_QUEST = 'COMPLETE_QUEST';
  const kLOG_GET_NOTE = 'GET_NOTE';
  const kLOG_TAG_NOTE = 'TAG_NOTE';
  const kLOG_GIVE_NOTE_LIKE = 'GIVE_NOTE_LIKE';
  const kLOG_GET_NOTE_LIKE = 'GET_NOTE_LIKE';
  const kLOG_GIVE_NOTE_COMMENT = 'GIVE_NOTE_COMMENT';
  const kLOG_GET_NOTE_COMMENT = 'GET_NOTE_COMMENT';

  //constants for gameID_requirements table enums
  const kREQ_PLAYER_HAS_ITEM = 'PLAYER_HAS_ITEM';
  const kREQ_PLAYER_VIEWED_ITEM = 'PLAYER_VIEWED_ITEM';
  const kREQ_PLAYER_VIEWED_NODE = 'PLAYER_VIEWED_NODE';
  const kREQ_PLAYER_VIEWED_NPC = 'PLAYER_VIEWED_NPC';
  const kREQ_PLAYER_VIEWED_WEBPAGE = 'PLAYER_VIEWED_WEBPAGE';
  const kREQ_PLAYER_VIEWED_AUGBUBBLE = 'PLAYER_VIEWED_AUGBUBBLE';
  const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM';
  const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE';
  const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO';
  const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO';
  const kREQ_PLAYER_HAS_COMPLETED_QUEST = 'PLAYER_HAS_COMPLETED_QUEST';
  const kREQ_PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK = 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK';
  const kREQ_PLAYER_HAS_NOTE = 'PLAYER_HAS_NOTE';
  const kREQ_PLAYER_HAS_NOTE_WITH_TAG = 'PLAYER_HAS_NOTE_WITH_TAG';
  const kREQ_PLAYER_HAS_NOTE_WITH_LIKES = 'PLAYER_HAS_NOTE_WITH_LIKES';
  const kREQ_PLAYER_HAS_NOTE_WITH_COMMENTS = 'PLAYER_HAS_NOTE_WITH_COMMENTS';
  const kREQ_PLAYER_HAS_GIVEN_NOTE_COMMENTS = 'PLAYER_HAS_GIVEN_NOTE_COMMENTS';

  const kRESULT_DISPLAY_NODE = 'Node';
  const kRESULT_DISPLAY_QUEST = 'QuestDisplay';
  const kRESULT_COMPLETE_QUEST = 'QuestComplete';
  const kRESULT_DISPLAY_LOCATION = 'Location';
  const kRESULT_EXECUTE_WEBHOOK = 'OutgoingWebhook';

  //constants for player_state_changes table enums
  const kPSC_GIVE_ITEM = 'GIVE_ITEM';
  const kPSC_TAKE_ITEM = 'TAKE_ITEM';	

  //constants for player created items (pictures, etc...)
  const kPLAYER_CREATED_ITEM_CONTENT_TYPE = 'Item';
  const kPLAYER_CREATED_ITEM_DEFAULT_ICON_NUM = '2';
  const kPLAYER_CREATED_ITEM_PHOTO_ICON_NUM = 36;
  const kPLAYER_CREATED_ITEM_AUDIO_ICON_NUM = 34;
  const kPLAYER_CREATED_ITEM_VIDEO_ICON_NUM = 35;
  const kPLAYER_CREATED_ITEM_DEFAULT_PARENT_FOLDER_ID = '-1';
    
  //constants for note icon id
  const kPLAYER_NOTE_DEFAULT_ICON = '94';

    public function findLowestIdFromTable($tableName, $idColumnName)
    {
        $query = "
        SELECT  $idColumnName
        FROM    (
            SELECT  1 AS $idColumnName
        ) q1
        WHERE   NOT EXISTS
        (
            SELECT  1
            FROM    $tableName
            WHERE   $idColumnName = 1
        )
        UNION ALL
        SELECT  *
        FROM    (
            SELECT  $idColumnName + 1
            FROM    $tableName t
            WHERE   NOT EXISTS
            (
                SELECT  1
                FROM    $tableName ti
                WHERE   ti.$idColumnName = t.$idColumnName + 1
            )
            ORDER BY
            $idColumnName
            LIMIT 1
        ) q2
        ORDER BY
        $idColumnName
        LIMIT 1
        ";
        if($result = mysql_query($query))
        {
            if($lowestNonUsedId = mysql_fetch_object($result)->media_id)
                return $lowestNonUsedId;
        }
        else
        {
            //Just going to use the next auto_increment id...
            $query = "SELECT MAX($idColumnName) as 'nextAIID' FROM $tableName";
            $result = mysql_query($query);
            if($nextAutoIncrementId = mysql_fetch_object($result)->nextAIID)
                return $nextAutoIncrementId;
        }
        return null;
    }

  public function Module()
  {
    $this->conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
    if (!$this->conn) {
      Module::serverErrorLog("Problem Connecting to MySQL: " . mysql_error());
      if(Config::adminEmail) Module::sendEmail(Config::adminEmail,"ARIS Server Error", mysql_error());
    }
    mysql_select_db (Config::dbSchema);
    mysql_query("set names utf8");
    mysql_query("set charset set utf8");
  }	

  /**
   * Fetch the prefix of a game
   * @returns a prefix string without the trailing _
   */
  protected function getPrefix($intGameID) {	
    //Lookup game information
    $query = "SELECT prefix FROM games WHERE game_id = '{$intGameID}' LIMIT 1";
    //NetDebug::trace($query);
    $rsResult = @mysql_query($query);
    if (mysql_num_rows($rsResult) < 1) return FALSE;
    $gameRecord = mysql_fetch_array($rsResult);
    return substr($gameRecord['prefix'],0,strlen($gameRecord['prefix'])-1);

  }

  /**
   * Fetch the GameID from a prefix
   * @returns a gameID int
   */
  protected function getGameIdFromPrefix($strPrefix) {	
    //Lookup game information
    $query = "SELECT game_id FROM games WHERE prefix= '{$strPrefix}_'";
    $rsResult = @mysql_query($query);
    if (mysql_num_rows($rsResult) < 1) return FALSE;
    $gameRecord = mysql_fetch_array($rsResult);
    return $gameRecord['game_id'];

  }	

  /*
   * Converts meters to degrees (lat/lon)
   */
  protected function mToDeg($meters)
  {
    //Ridiculous approximation, but fine for most cases
    return $meters*6/500000;
  }

  /*
   * Converts degrees (lat/lon) to meters
   */
  protected function degToM($degrees)
  {
    //Ridiculous approximation, but fine for most cases
    return 500000*$degrees;
  }

  /**
   * Adds the specified item to the specified player. Returns the actual number added after concidering item max
   */
  protected function giveItemToPlayer($strGamePrefix, $intItemID, $intPlayerID, $qtyToGive=1) {
    $currentQty = Module::itemQtyInPlayerInventory($strGamePrefix, $intPlayerID, $intItemID);
    $item = Items::getItem($strGamePrefix, $intItemID)->data;
    $maxQty = $item->max_qty_in_inventory; 

    NetDebug::trace("Module: giveItemToPlayer: Player currently has $currentQty - Item max is $maxQty");


    if ($currentQty + $qtyToGive > $maxQty  && $maxQty != -1) {
      //we are going over the limit
      $qtyToGive =  $maxQty - $currentQty;
      NetDebug::trace("Module: giveItemToPlayer: Attempted to go over item max qty. Request change to $qtyToGive");
    }

    if ($qtyToGive < 1) return 0;
    else {
      Module::adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, $qtyToGive);
      return $qtyToGive;
    }
  }

  /**
   * Sets the item count for specified item and player. Returns the actual number added after considering item max
   */
  protected function setItemCountForPlayer($strGamePrefix, $intItemID, $intPlayerID, $qty) {
    $currentQty = Module::itemQtyInPlayerInventory($strGamePrefix, $intPlayerID, $intItemID);
    $item = Items::getItem($strGamePrefix, $intItemID)->data;
    $maxQty = $item->max_qty_in_inventory; 

    //Module::serverErrorLog("Module: setItemCountForPlayer: Player currently has $currentQty. Setting to $qty.");

    if ($qty > $maxQty  && $maxQty != -1) {
      //we are going over the limit
      $qty =  $maxQty;
      //Module::serverErrorLog("Module: setItemCountForPlayer: Attempted to go over item max qty. Request change to $qty");
    }

    if ($qty < 0) return 0;
    else {
      $amountToAdjust = $qty - $currentQty;
      Module::adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, $amountToAdjust);
      //Module::serverErrorLog("Module: setItemCountForPlayer: Player amount of item is being adjusted by $amountToAdjust.");
      return $qty;
    }
  }

  /**
   * Removes the specified item from the user.
   * Removes the specified item from the user.
   */ 
  protected function takeItemFromPlayer($strGamePrefix, $intItemID, $intPlayerID, $qtyToTake=1) {
    Module::adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, -$qtyToTake);
  }

  /**
   * Removes the specified item from the user.
   */ 
  protected function removeItemFromAllPlayerInventories($strGamePrefix, $intItemID ) {
    $query = "DELETE FROM {$strGamePrefix}_player_items 
      WHERE item_id = $intItemID";
    $result = @mysql_query($query);
    NetDebug::trace($query . mysql_error());    
  }

  /**
   * Updates the qty a player has of an item 
   */ 
  protected function adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, $amountOfAdjustment) {
    //Get any existing record
    $query = "SELECT * FROM {$strGamePrefix}_player_items 
      WHERE player_id = $intPlayerID AND item_id = $intItemID LIMIT 1";
    $result = @mysql_query($query);
    NetDebug::trace($query . mysql_error());

    if ($existingPlayerItem = @mysql_fetch_object($result)) {
      //Check if this change will make the qty go to < 1, if so delete the record
      $newQty = $existingPlayerItem->qty + $amountOfAdjustment;
      if ($newQty < 1) {
        NetDebug::trace("Adjustment would result in a qty of $newQty so delete the record");
        $query = "DELETE FROM {$strGamePrefix}_player_items 
          WHERE player_id = $intPlayerID AND item_id = $intItemID";
        NetDebug::trace($query);
        @mysql_query($query);
      }
      else {
        //Update the qty
        NetDebug::trace("Updating Qty to $newQty");
        $query = "UPDATE {$strGamePrefix}_player_items 
          SET qty = $newQty
          WHERE player_id = $intPlayerID AND item_id = $intItemID";
        NetDebug::trace($query);
        @mysql_query($query);
      }
    }
    else if ($amountOfAdjustment > 0) {
      //Create a record
      NetDebug::trace("Creating a new player_item record");

      $query = "INSERT INTO {$strGamePrefix}_player_items 
        (player_id, item_id, qty) VALUES ($intPlayerID, $intItemID, $amountOfAdjustment)
        ON duplicate KEY UPDATE item_id = $intItemID";
      NetDebug::trace($query);
      @mysql_query($query);
    }
    else 
    {
      NetDebug::trace("Decrementing the qty of an item the player does not have. Ignored.");
      return;
    }

    if($amountOfAdjustment > 0)
      Module::processGameEvent($intPlayerID, $strGamePrefix, Module::kLOG_PICKUP_ITEM, $intItemID, $amountOfAdjustment);
    else
      Module::processGameEvent($intPlayerID, $strGamePrefix, Module::kLOG_DROP_ITEM, $intItemID, -1*$amountOfAdjustment);
  }


  /**
   * Decrement the item_qty at the specified location by the specified amount, default of 1
   */ 
  protected function decrementItemQtyAtLocation($strGamePrefix, $intLocationID, $intQty = 1) {
    //If this location has a null item_qty, decrementing it will still be a null
    $query = "UPDATE {$strGamePrefix}_locations 
      SET item_qty = item_qty-{$intQty}
    WHERE location_id = '{$intLocationID}' AND item_qty > 0";
    NetDebug::trace($query);	
    @mysql_query($query);    	
  }


  /**
   * Adds an item to Locations at the specified latitude, longitude
   */ 
  protected function giveItemToWorld($strGamePrefix, $intItemID, $floatLat, $floatLong, $intQty = 1) {
    //Find any items on the map nearby
    $clumpingRangeInMeters = 10;

    $query = "SELECT *,((ACOS(SIN($floatLat * PI() / 180) * SIN(latitude * PI() / 180) + 
      COS($floatLat * PI() / 180) * COS(latitude * PI() / 180) * 
      COS(($floatLong - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1609.344
      AS `distance`, location_id 
      FROM {$strGamePrefix}_locations 
      WHERE type = 'item' AND type_id = '{$intItemID}'
      HAVING distance<= {$clumpingRangeInMeters}
    ORDER BY distance ASC"; 	
      $result = @mysql_query($query);
    NetDebug::trace($query . ' ' . mysql_error());  

    if ($closestLocationWithinClumpingRange = @mysql_fetch_object($result)) {
      //We have a match
      NetDebug::trace("An item exists nearby, adding to that location");   	

      $query = "UPDATE {$strGamePrefix}_locations
        SET item_qty = item_qty + {$intQty}
      WHERE location_id = {$closestLocationWithinClumpingRange->location_id}";
      NetDebug::trace($query . ' ' . mysql_error());  
      @mysql_query($query);
    }
    else {
      NetDebug::trace("No item exists nearby, creating a new location");   	

      $itemName = $this->getItemName($strGamePrefix, $intItemID);
      $error = 100; //Use 100 meters
      $icon_media_id = $this->getItemIconMediaId($strGamePrefix, $intItemID); //Set the map icon = the item's icon

      $query = "INSERT INTO {$strGamePrefix}_locations (name, type, type_id, icon_media_id, latitude, longitude, error, item_qty)
        VALUES ('{$itemName}','Item','{$intItemID}', '{$icon_media_id}', '{$floatLat}','{$floatLong}', '{$error}','{$intQty}')";
      NetDebug::trace($query . ' ' . mysql_error());  
      @mysql_query($query);

      $newId = mysql_insert_id();
      //Create a coresponding QR Code
      QRCodes::createQRCode($strGamePrefix, "Location", $newId, '');
    }
  }


  /**
   * Adds a note to Locations at the specified latitude, longitude
   */ 
  protected function giveNoteToWorld($strGamePrefix, $noteId, $floatLat, $floatLong) {

    $query = "SELECT * FROM {$strGamePrefix}_locations WHERE type = 'PlayerNote' AND type_id = '{$noteId}'";	
    $result = @mysql_query($query);
    NetDebug::trace($query . ' ' . mysql_error());  

    if ($existingNote = @mysql_fetch_object($result)) {
      //We have a match
      NetDebug::trace("This note has already been placed");   	

      $query = "UPDATE {$strGamePrefix}_locations
        SET latitude = '{$floatLat}', longitude = '{$floatLong}'
        WHERE location_id = {$existingNote->location_id}";
      NetDebug::trace($query . ' ' . mysql_error());  
      @mysql_query($query);
    }
    else {
      NetDebug::trace("Note has not yet been placed");   	

      $error = 100; //Use 100 meters
      $query = "SELECT title, owner_id FROM notes WHERE note_id = '{$noteId}'";
      $result = @mysql_query($query);
      $obj = @mysql_fetch_object($result);
      $title = $obj->title;

      $query = "INSERT INTO {$strGamePrefix}_locations (name, type, type_id, icon_media_id, latitude, longitude, error, item_qty, hidden, force_view, allow_quick_travel)
        VALUES ('{$title}','PlayerNote','{$noteId}', ".Module::kPLAYER_NOTE_DEFAULT_ICON.", '{$floatLat}','{$floatLong}', '{$error}','1',0,0,0)";
      NetDebug::trace($query . ' ' . mysql_error());  
      @mysql_query($query);

      $newId = mysql_insert_id();
      //Create a coresponding QR Code
      QRCodes::createQRCode($strGamePrefix, "Location", $newId, '');
    }
    Module::processGameEvent($obj->owner_id, $strGamePrefix, Module::kLOG_UPLOAD_MEDIA_ITEM, $noteId, $floatLat, $floatLong);
    Module::processGameEvent($obj->owner_id, $strGamePrefix, Module::kLOG_DROP_NOTE, $noteId, $floatLat, $floatLong);
  }

  protected function metersBetweenLatLngs($lat1, $lon1, $lat2, $lon2) { 
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

  /**
   * Checks if a record Exists
   **/
  protected function recordExists($strPrefix, $strTable, $intRecordID){
    $key = substr($strTable, 0, strlen($strTable)-1);
    $query = "SELECT * FROM {$strPrefix}_{$strTable} WHERE {$key} = $intRecordID";
    $rsResult = @mysql_query($query);
    if (mysql_error()) return FALSE;
    if (mysql_num_rows($rsResult) < 1) return FALSE;
    return true;
  }

  /**
   * Looks up an item name
   **/
  protected function getItemName($strPrefix, $intItemID){
    $query = "SELECT name FROM {$strPrefix}_items WHERE item_id = $intItemID";
    $rsResult = @mysql_query($query);		
    $row = @mysql_fetch_array($rsResult);	
    return $row['name'];
  }

  /**
   * Looks up an item icon media id
   **/
  protected function getItemIconMediaId($strPrefix, $intItemID){
    $query = "SELECT name FROM {$strPrefix}_items WHERE item_id = $intItemID";
    $rsResult = @mysql_query($query);		
    $row = @mysql_fetch_array($rsResult);	
    return $row['icon_media_id'];
  }   

  /** 
   * playerHasLog
   *
   * Checks if the specified user has the specified log event in the game
   *
   * @return boolean
   */
  protected function playerHasLog($strPrefix, $intPlayerID, $strEventType, $strEventDetail) {
    $intGameID = Module::getGameIdFromPrefix($strPrefix);

    $query = "SELECT 1 FROM player_log 
      WHERE player_id = '{$intPlayerID}' AND
      game_id = '{$intGameID}' AND
      event_type = '{$strEventType}' AND
      event_detail_1 = '{$strEventDetail}' AND
      deleted = 0
      LIMIT 1";

    //NetDebug::trace($query);
    $rsResult = @mysql_query($query);
    if (mysql_num_rows($rsResult) > 0) return true;
    else return false;	
  }


  /**
   * Checks if a player has an item with a minimum quantity
   *
   * @param integer $gameId The game identifier
   * @param integer $playerID The player identifier
   * @param integer $itemId The item identifier
   * @param integer $minItemQuantity The minimum quantity to qualify, 1 if unspecified
   * @return bool
   * @returns TRUE if the player has >= the minimum quantity, FALSE otherwise
   */     
  protected function playerHasItem($gameID, $playerID, $itemID, $minItemQuantity) {
    if (!$minItemQuantity) $minItemQuantity = 1;
    //NetDebug::trace("checking if player $playerID has atleast $minItemQuantity of item $itemID in inventory");		
    $qty = Module::itemQtyInPlayerInventory($gameID, $playerID, $itemID);
    if ($qty >= $minItemQuantity) return TRUE;
    else return false;
  }		


  /**
   * Checks the quantity a player has of an item in their inventory
   *
   * @param integer $gameId The game identifier
   * @param integer $playerId The player identifier
   * @param integer $itemId The item identifier
   * @return integer
   * @returns the quantity of the item in the player's inventory
   */       
  protected function itemQtyInPlayerInventory($gameId, $playerId, $itemId) {
    $prefix = Module::getPrefix($gameId);
    if (!$prefix) return FALSE;

    $query = "SELECT qty FROM {$prefix}_player_items 
      WHERE player_id = '{$playerId}' 
      AND item_id = '{$itemId}' LIMIT 1";

    $rsResult = @mysql_query($query);
    $playerItem = mysql_fetch_object($rsResult);
    if ($playerItem) {
      return $playerItem->qty;
    }
    else {
      return 0;
    }
  }	    

  /** 
   * playerHasUploadedMedia
   *
   * Checks if the specified user has uploaded media near the specified location.
   * NOTE- $mediaType should be Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO, or just
   * Module::kLOG_UPLOAD_MEDIA_ITEM for any
   * @return boolean
   */

  //Spelled 'distAnce' wrong in function name and variable name... afraid to change it... the repercussions could be ASTRONOMICAL.
  protected function playerHasUploadedMediaItemWithinDistence($intGameID, $intPlayerID, $dblLatitude, $dblLongitude, $dblDistenceInMeters, $qty, $mediaType) 
  {
    NetDebug::trace("playerHasUploadedMediaItemWithinDistence(gid:$intGameID, pid:$intPlayerID, lat:$dblLatitude, lon:$dblLongitude, dist:$dblDistenceInMeters, qty:$qty, type:$mediaType)");
    $prefix = Module::getPrefix($intGameID);
    if (!$prefix) return false;

    if($dblLatitude == "" || $dblLongitude == "" || $dblDistenceInMeters == "") return false; //MySQL Math segment freaks out if there is nothing in them ('0' is ok)
    $query = "SELECT {$prefix}_items.*
      FROM player_log, {$prefix}_items
      WHERE 
      player_log.player_id = '{$intPlayerID}' AND
      player_log.game_id = '{$intGameID}' AND
      player_log.event_type = '". $mediaType ."' AND
      player_log.event_detail_1 = {$prefix}_items.item_id AND
      player_log.deleted = 0 AND

      (((acos(sin(({$dblLatitude}*pi()/180)) * sin((origin_latitude*pi()/180))+cos(({$dblLatitude}*pi()/180)) * 
              cos((origin_latitude*pi()/180)) * 
              cos((({$dblLongitude} - origin_longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344*1000) < {$dblDistenceInMeters}";
    //NetDebug::trace($query);
    $rsResult = @mysql_query($query);
    if (mysql_error()) return false;
    if (@mysql_num_rows($rsResult) >= $qty) return true;


    if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM)
      $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN ".$intGameID."_locations ON notes.note_id = ".$intGameID."_locations.type_id WHERE owner_id = '{$intPlayerID}'";
    else if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE)
      $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN ".$intGameID."_locations ON notes.note_id = ".$intGameID."_locations.type_id WHERE owner_id = '{$intPlayerID}' AND note_content.type='PHOTO'";
    else if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO)
      $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN ".$intGameID."_locations ON notes.note_id = ".$intGameID."_locations.type_id WHERE owner_id = '{$intPlayerID}' AND note_content.type='AUDIO'";
    else if($mediaType == Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO)
      $query = "SELECT * FROM note_content LEFT JOIN notes ON note_content.note_id = notes.note_id LEFT JOIN ".$intGameID."_locations ON notes.note_id = ".$intGameID."_locations.type_id WHERE owner_id = '{$intPlayerID}' AND note_content.type='VIDEO'";
    else
      NetDebug::trace("error...");
    $queryappendation = "AND (((acos(sin(({$dblLatitude}*pi()/180)) * sin((".$intGameID."_locations.latitude*pi()/180))+cos(({$dblLatitude}*pi()/180)) * 
      cos((".$intGameID."_locations.latitude*pi()/180)) * 
      cos((({$dblLongitude} - ".$intGameID."_locations.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344*1000) < {$dblDistenceInMeters}";
    $result = mysql_query($query.$queryappendation);
    NetDebug::trace(mysql_num_rows($result)." - ".$qty);
    if (mysql_num_rows($result) >= $qty) return true;
    else return false;
  }	    

  protected function playerHasNote($intGameID, $intPlayerID, $qty)
  {
    $prefix = Module::getPrefix($intGameID);
    if (!$prefix) return FALSE;

    $query = "SELECT note_id FROM notes WHERE owner_id = '{$intPlayerID}' AND parent_note_id = 0";
    NetDebug::trace($query);
    $result = @mysql_query($query);
    NetDebug::trace(mysql_num_rows($result));
    if (mysql_num_rows($result) >= $qty) return true;
    return false;
  }

  protected function playerHasNoteWithTag($intGameID, $intPlayerID, $tag, $qty)
  {
    $prefix = Module::getPrefix($intGameID);
    if (!$prefix) return FALSE;

    $query = "SELECT note_id FROM notes WHERE owner_id = '{$intPlayerID}' AND parent_note_id = 0";
    NetDebug::trace($query);
    $result = @mysql_query($query);
    NetDebug::trace(mysql_num_rows($result));
    $num = 0;
    while($noteobj = mysql_fetch_object($result))
    {
      $query = "SELECT * FROM note_tags WHERE note_id='{$noteobj->note_id}' AND tag_id='{$tag}'";
      $result2 = mysql_query($query);
      if(mysql_num_rows($result2)>0) $num++;
    }
    if(($qty == "" && $num > 0) || $num > $qty)
      return true;
    else
      return false;
  }
  protected function playerHasNoteWithComments($intGameID, $intPlayerID, $qty)
  {
    $prefix = Module::getPrefix($intGameID);
    if (!$prefix) return FALSE;

    $query = "SELECT note_id FROM notes WHERE game_id = '{$intGameID}' AND owner_id = '{$intPlayerID}'";
    NetDebug::trace($query);
    $result = @mysql_query($query);
    while($note_id = mysql_fetch_object($result))
    {
      $query = "SELECT note_id FROM notes WHERE game_id = '{$intGameID}' AND parent_note_id = '{$note_id->note_id}'";
      NetDebug::trace($query);
      $res = @mysql_query($query);
      if (@mysql_num_rows($res) >= $qty) return true;
    }
    return false;
  }

  protected function playerHasNoteWithLikes($intGameID, $intPlayerID, $qty)
  {
    $prefix = Module::getPrefix($intGameID);
    if (!$prefix) return FALSE;

    $query = "SELECT note_id FROM notes WHERE game_id = '{$intGameID}' AND owner_id = '{$intPlayerID}'";
    NetDebug::trace($query);
    $result = @mysql_query($query);
    while($note_id = mysql_fetch_object($result))
    {
      $query = "SELECT player_id FROM note_likes WHERE note_id = '{$note_id->note_id}'";
      NetDebug::trace($query);
      $res = @mysql_query($query);
      if (@mysql_num_rows($res) >= $qty) return true;
    }
    return false;
  }

  protected function PlayerHasGivenNoteComments($intGameID, $intPlayerID, $qty)
  {
    $prefix = Module::getPrefix($intGameID);
    if (!$prefix) return FALSE;

    $query = "SELECT note_id FROM notes WHERE owner_id = '{$intPlayerID}' AND parent_note_id != 0";
    NetDebug::trace($query);
    $result = @mysql_query($query);
    if (@mysql_num_rows($result) >= $qty) return true;
    return false;
  }

  /** 
   * objectMeetsRequirements
   *
   * Checks all requirements for the specified object for the specified user
   * @return boolean
   */	
  protected function objectMeetsRequirements ($strPrefix, $intPlayerID, $strObjectType, $intObjectID) {		
    //NetDebug::trace("Checking Requirements for {$strObjectType}:{$intObjectID} for playerID:$intPlayerID in gameID:$strPrefix");

    //Fetch the requirements
    $query = "SELECT requirement,
      requirement_detail_1,requirement_detail_2,requirement_detail_3,requirement_detail_4,
      boolean_operator, not_operator
        FROM {$strPrefix}_requirements 
        WHERE content_type = '{$strObjectType}' AND content_id = '{$intObjectID}'";
    $rsRequirments = @mysql_query($query);

    $andsMet = FALSE;
    $requirementsExist = FALSE;
    while ($requirement = mysql_fetch_array($rsRequirments)) {
      $requirementsExist = TRUE;
      //NetDebug::trace("Requirement for {$strObjectType}:{$intObjectID} is {$requirement['requirement']}:{$requirement['requirement_detail_1']}");
      //Check the requirement

      $requirementMet = FALSE;
      //NetDebug::trace("Checking ".$requirement['requirement']);
      switch ($requirement['requirement']) {
        //Log related
        case Module::kREQ_PLAYER_VIEWED_ITEM:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_ITEM, 
              $requirement['requirement_detail_1']);
          break;
        case Module::kREQ_PLAYER_VIEWED_NODE:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_NODE, 
              $requirement['requirement_detail_1']);
          break;
        case Module::kREQ_PLAYER_VIEWED_NPC:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_NPC, 
              $requirement['requirement_detail_1']);
          break;
        case Module::kREQ_PLAYER_VIEWED_WEBPAGE:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_WEBPAGE, 
              $requirement['requirement_detail_1']);
          break;
        case Module::kREQ_PLAYER_VIEWED_AUGBUBBLE:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_AUGBUBBLE, 
              $requirement['requirement_detail_1']);
          break;
        case Module::kREQ_PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_RECEIVE_WEBHOOK, 
              $requirement['requirement_detail_1']);
          break;
          //Inventory related	
        case Module::kREQ_PLAYER_HAS_ITEM:
          $requirementMet = Module::playerHasItem($strPrefix, $intPlayerID, 
              $requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
          break;
          //Data Collection
        case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM:
          $requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
              $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
              $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM);
          break;
        case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO:
          $requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
              $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
              $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO);
          break;
        case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO:
          $requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
              $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
              $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO);
          break;
        case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE:
          $requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
              $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
              $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE);
          break;
        case Module::kREQ_PLAYER_HAS_COMPLETED_QUEST:
          $requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_COMPLETE_QUEST, 
              $requirement['requirement_detail_1']);
          break;
        case Module::kREQ_PLAYER_HAS_NOTE:
          $requirementMet = Module::playerHasNote($strPrefix, $intPlayerID, $requirement['requirement_detail_2']);
          break;
        case Module::kREQ_PLAYER_HAS_NOTE_WITH_TAG:
          $requirementMet = Module::playerHasNoteWithTag($strPrefix, $intPlayerID, $requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
          break;
        case Module::kREQ_PLAYER_HAS_NOTE_WITH_LIKES:
          $requirementMet = Module::playerHasNoteWithLikes($strPrefix, $intPlayerID, $requirement['requirement_detail_2']);
          break;
        case Module::kREQ_PLAYER_HAS_NOTE_WITH_COMMENTS:
          $requirementMet = Module::playerHasNoteWithComments($strPrefix, $intPlayerID, $requirement['requirement_detail_2']);
          break;
        case Module::kREQ_PLAYER_HAS_GIVEN_NOTE_COMMENTS:
          $requirementMet = Module::playerHasGivenNoteComments($strPrefix, $intPlayerID, $requirement['requirement_detail_2']);
          break;

          NetDebug::trace("Was Requirement Met?: ".$requirementMet);
      }//switch

      //Account for the 'NOT's
      if($requirement['not_operator'] == "NOT") $requirementMet = !$requirementMet;

      if ($requirement['boolean_operator'] == "AND" && $requirementMet == FALSE) {
        //NetDebug::trace("An AND requirement was not met. Requirements Failed.");
        return FALSE;
      }

      if ($requirement['boolean_operator'] == "AND" && $requirementMet == TRUE) {
        //NetDebug::trace("An AND requirement was met. Remembering");
        $andsMet = TRUE;
      }

      if ($requirement['boolean_operator'] == "OR" && $requirementMet == TRUE){
        //NetDebug::trace("An OR requirement was met. Requirements Passed.");
        return TRUE;
      }

      if ($requirement['boolean_operator'] == "OR" && $requirementMet == FALSE){
        $requirementsMet = FALSE;
      }
    }

    if (!$requirementsExist) {
      //NetDebug::trace("No requirements exist. Requirements Passed.");
      return TRUE;
    }
    if ($andsMet) {
      //NetDebug::trace("All AND requirements exist. Requirements Passed.");
      return TRUE;
    }
    else {
      //NetDebug::trace("At end. Requirements Not Passed.");			
      return FALSE;
    }
  }	


  /** 
   * applyPlayerStateChanges
   *
   * Applies any state changes for the given object
   * @return boolean. True if a change was made, false otherwise
   */	
  protected function applyPlayerStateChanges($strPrefix, $intPlayerID, $strEventType, $strEventDetail) {	
    $changeMade = FALSE;

    //Fetch the state changes
    $query = "SELECT * FROM {$strPrefix}_player_state_changes 
      WHERE event_type = '{$strEventType}'
      AND event_detail = '{$strEventDetail}'";
    NetDebug::trace($query);

    if(!$rsStateChanges = @mysql_query($query)) return $changeMade;

    while ($stateChange = mysql_fetch_array($rsStateChanges)) {
      NetDebug::trace("State Change Found");

      //Check the requirement
      switch ($stateChange['action']) {
        case Module::kPSC_GIVE_ITEM:
          //echo 'Running a GIVE_ITEM';
          Module::giveItemToPlayer($strPrefix, $stateChange['action_detail'], $intPlayerID,$stateChange['action_amount']);
          $changeMade = TRUE;
          break;
        case Module::kPSC_TAKE_ITEM:
          //echo 'Running a TAKE_ITEM';
          Module::takeItemFromPlayer($strPrefix, $stateChange['action_detail'], $intPlayerID,$stateChange['action_amount']);
          $changeMade = TRUE;
          break;
      }
    }//stateChanges loop

    return $changeMade;
  }

  /*
   * All Events are to come through this gateway-
   * Takes events and appends them to the log, completes quests, and fires off webhooks accordingly
   * EVENT_PIPELINE_START
   */
  public function processGameEvent($playerId, $gameId, $eventType, $eventDetail1='N/A', $eventDetail2='N/A', $eventDetail3='N/A', $eventDetail4='N/A')
  {
    //NetDebug::trace("Module::processGameEvent: playerId:$playerId, gameId:$gameId, eventType:$eventType, eventDetail1:$eventDetail1, eventDetail2:$eventDetail2, eventDetail3:$eventDetail3, eventDetail4:$eventDetail4");
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

    //Clean up spawnables that ought to be removed after viewing
    $shouldCheckSpawnablesForDeletion = false;
    switch($eventType)
    {
      case Module::kLOG_VIEW_ITEM:
        $type = "Item";
        $shouldCheckSpawnablesForDeletion = true;
        break;
      case Module::kLOG_VIEW_NODE:
        $type = "Node";
        $shouldCheckSpawnablesForDeletion = true;
        break;
      case Module::kLOG_VIEW_NPC:
        $type = "Npc";
        $shouldCheckSpawnablesForDeletion = true;
        break;
      case Module::kLOG_VIEW_WEBPAGE:
        $type = "WebPage";
        $shouldCheckSpawnablesForDeletion = true;
        break;
      case Module::kLOG_VIEW_AUGBUBBLE:
        $type = "AugBubble";
        $shouldCheckSpawnablesForDeletion = true;
        break;
    }
    if($shouldCheckSpawnablesForDeletion)
    {
        Module::serverErrorLog("Checking ".$type." ".$eventDetail2);
      $query = "SELECT * FROM spawnables WHERE game_id = $gameId AND type = '$type' AND type_id = $eventDetail1 LIMIT 1";
      $result = mysql_query($query);
      if(($obj = mysql_fetch_object($result)) && $obj->delete_when_viewed == 1) 
      {
        $query = "DELETE FROM ".$gameId."_locations WHERE location_id = $eventDetail2";
        mysql_query($query);
      }
    }
  }

  /*
   * Simply a cleaner standin for a query inserting into the player log
   * EVENT_PIPELINE
   */
  protected function appendLog($playerId, $gameId, $eventType, $eventDetail1='N/A', $eventDetail2='N/A', $eventDetail3='N/A')
  {
    $query = "INSERT INTO player_log (player_id, game_id, event_type, event_detail_1, event_detail_2, event_detail_3) VALUES ({$playerId},{$gameId},'{$eventType}','{$eventDetail1}','{$eventDetail2}','{$eventDetail3}')";
    NetDebug::trace("Module::appendLog: $query");

    @mysql_query($query);
  }

  /*
   * Returns an array of unfinished quests
   * EVENT_PIPELINE
   */
  protected function getUnfinishedQuests($playerId, $gameId)
  {
    //Get all quests for game
    $query = "SELECT * FROM {$gameId}_quests";
    $result = mysql_query($query);
    $gameQuests = array();
    while($gameQuest = mysql_fetch_object($result))
      $gameQuests[] = $gameQuest;

    //Get all completed quests by player
    $query = "SELECT * FROM player_log WHERE game_id = $gameId AND player_id = $playerId AND event_type = 'COMPLETE_QUEST' AND deleted = 0;";
    $result = mysql_query($query);
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

  /*
   * Returns an array of unfired webhooks
   * EVENT_PIPELINE
   */
  protected function getUnfiredWebhooks($playerId, $gameId)
  {
    //Get all webhooks for game
    $query = "SELECT * FROM web_hooks WHERE game_id = '{$gameId}' AND incoming = 0";
    $result = mysql_query($query);
    $gameWebhooks = array();
    while($gameWebhook = mysql_fetch_object($result))
      $gameWebhooks[] = $gameWebhook;

    //Get all webhooks fired by player
    $query = "SELECT * FROM player_log WHERE game_id = $gameId AND player_id = $playerId AND event_type = 'SEND_WEBHOOK' AND deleted = 0;";
    $result = mysql_query($query);
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

  /*
   * Returns whether a player has completed a quest
   * EVENT_PIPELINE
   */
  protected function questIsCompleted($playerId, $gameId, $questId)
  {
    return Module::objectMeetsRequirements($gameId, $playerId, 'QuestComplete', $questId);
  }

  /*
   * Returns whether a webhook ought to be fired
   * EVENT_PIPELINE
   */
  protected function hookShouldBeFired($playerId, $gameId, $webhookId)
  {
    return Module::objectMeetsRequirements($gameId, $playerId, 'OutgoingWebhook', $webhookId);
  }

  /*
   * Sends off webhook
   * EVENT_PIPELINE
   */
  protected function fireOffWebHook($playerId, $gameId, $webHookId){
    Module::appendLog($playerId, $gameId, "SEND_WEBHOOK", $webHookId);

    $query = "SELECT * FROM web_hooks WHERE web_hook_id = '{$webHookId}' LIMIT 1";
    $result = mysql_query($query);
    $webHook = mysql_fetch_object($result);
    $name = str_replace(" ", "", $webHook->name);
    $name = str_replace("{playerId}", $playerId, $webHook->name);
    $url = $webHook->url . "?hook=" . $name . "&wid=" . $webHook->web_hook_id . "&gameid=" . $gameId . "&playerid=" . $playerId; 
    NetDebug::trace("Module::fireOffWebHook: Final URL: $url");
    @file_get_contents($url);
  }

  /**
   * Add a row to the server error log
   * @returns void
   */
  protected function serverErrorLog($message)
  {
    NetDebug::trace("Logging an Error: $message");
    $errorLogFile = fopen(Config::serverErrorLog, "a");
    $errorData = date('c') . ' "' . $message . '"' ."\n";
    fwrite($errorLogFile, $errorData);
    fclose($errorLogFile);
  }

  /**
   * Sends an Email
   * @returns 0 on success
   */
  protected function sendEmail($to, $subject, $body) {
    include_once('../../libraries/phpmailer/class.phpmailer.php');

    if (empty($to)) {
      return false;
    }

    NetDebug::trace("TO: $to");
    NetDebug::trace("SUBJECT: $subject");
    NetDebug::trace("BODY: $body");

    $mail = new phpmailer;
    $mail->PluginDir = '../../libraries/phpmailer';      // plugin directory (eg smtp plugin)

    $mail->CharSet = 'UTF-8';
    $mail->Subject = substr(stripslashes($subject), 0, 900);
    $mail->From = 'noreply@arisgames.org';
    $mail->FromName = 'ARIS Mailer';

    $mail->AddAddress($to, 'ARIS Author');
    $mail->MsgHTML($body);


    $mail->WordWrap = 79;                               // set word wrap

    if ($mail->Send()) return true;
    else return false;

  }
}
