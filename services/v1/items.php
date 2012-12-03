<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class Items extends Module
{


    /**
     * Gets the items within a game
     * @param integer $gameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of items
     * @see returnData
     */
    public static function getItems($gameId)
    {

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");


        $query = "SELECT * FROM items WHERE game_id = '{$prefix}'";
        NetDebug::trace($query);

        $rsResult = @mysql_query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    /**
     * Gets the items within a player's inventory
     *
     * @param integer $gameID The game identifier
     * @param integer $playerId The player identifier
     * @return returnData
     * @returns a returnData object containing an array of items
     * @see returnData
     */
    public static function getItemsForPlayer($gameId, $playerId)
    {

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT game_items.*, game_player_items.qty, game_player_items.viewed FROM (SELECT * FROM items WHERE game_id = {$gameId}) AS game_items JOIN (SELECT * FROM player_items WHERE game_id = {$gameId} AND player_id = $playerId) AS game_player_items ON game_items.item_id = game_player_items.item_id";
        NetDebug::trace($query);

        $rsResult = @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        if (!$rsResult) return new returnData(3, NULL, "Something bad happened");
        return new returnData(0, $rsResult);
    }	

    /**
     * Gets the qty of an item in th eplayer's inventory
     *
     * @param Object $obj is an object with the gameId, playerId and itemId
     * @return returnData
     * @returns the qty of the item
     */
    public static function getItemCountForPlayer($obj)
    {
        $gameId = $obj['gameId'];
        $playerId = $obj['playerId'];
        $itemId = $obj['itemId'];

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");


        $query = "SELECT qty FROM player_items WHERE player_id = $playerId AND item_id = $itemId AND game_id = '{$prefix}'";

        $rsResult = @mysql_query($query);
        if (!$rsResult) return new returnData(0, NULL);
        $row = @mysql_fetch_row($rsResult);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        Module::serverErrorLog("hey there-".$gameId." ".$playerId." ".$itemId." ".$query." ".$row[0]);
        return new returnData(0, $row[0]);
    }

    /**
     * Gets the Attributes for a player
     *
     * @param integer $gameID The game identifier
     * @param integer $playerId The player identifier
     * @return returnData
     * @returns a returnData object containing an array of items
     * @see returnData
     */
    public static function getAttributesForPlayer($gameId, $playerId)
    {

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT game_items.*, game_player_items.qty FROM (SELECT * FROM items WHERE game_id = {$gameId} AND is_attribute = '1') AS game_items JOIN (SELECT * FROM player_items WHERE game_id = {$gameId} AND player_id = $playerId) AS game_player_items ON game_items.item_id = game_player_items.item_id";
        NetDebug::trace($query);

        $rsResult = @mysql_query($query);
        if (!$rsResult) return new returnData(0, NULL);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }


    /**
     * Gets a single item from a game
     *
     * @param integer $gameID The game identifier
     * @param integer $itemId The item identifier
     * @return returnData
     * @returns a returnData object containing an items
     * @see returnData
     */
    public static function getItem($gameId, $itemId)
    {

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM items WHERE item_id = {$itemId} AND game_id = '{$prefix}' LIMIT 1";

        $rsResult = @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $item = @mysql_fetch_object($rsResult);
        if (!$item) return new returnData(2, NULL, "invalid item id");

        return new returnData(0, $item);

    }

    /**
     * Gets a single item from a game
     * 
     * @param integer $gameId The game identifier
     * @param string $name The name
     * @param string $description The html formatted description
     * @param integer $iconMediaId The item's media identifier
     * @param integer $mediaId The item's icon media identifier
     * @param bool $droppable 1 if this item can be dropped, 0 if not 
     * @param bool $destroyable 1 if this item can be detroyed, 0 if not
     * @param integer $maxQuantityInPlayerInventory The maximum amount of this item a player can have in their inventory
     * @return returnData
     * @returns a returnData object containing the new item identifier
     * @see returnData
     */
    public static function createItem($gameId, $name, $description, $iconMediaId, $mediaId, $droppable, $destroyable, $tradeable, $attribute, $maxQuantityInPlayerInventory, $weight, $url, $type)
    {
        $name = addslashes($name);	
        $description = addslashes($description);	

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "INSERT INTO items 
            (game_id, name, description, icon_media_id, media_id, dropable, destroyable, tradeable, is_attribute, max_qty_in_inventory, weight, url, type)
            VALUES ('{$gameId}',
                    '{$name}', 
                    '{$description}',
                    '{$iconMediaId}', 
                    '{$mediaId}', 
                    '{$droppable}',
                    '{$destroyable}',
                    '{$tradeable}',
                    '{$attribute}',
                    '{$maxQuantityInPlayerInventory}',
                    '{$weight}',
                    '{$url}',
                    '{$type}')";

        NetDebug::trace("createItem: Running a query = $query");	

        @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		

        return new returnData(0, mysql_insert_id());
    }

    /**
     * Updates an item's properties
     *
     * @param integer $gameId The game identifier
     * @param integer $itemId The item identifier
     * @param string $name The new name
     * @param string $description The new html formatted description
     * @param integer $iconMediaId The new icon media identifier
     * @param integer $mediaId The new media identifier
     * @param bool $droppable 1 if this item can be dropped, 0 if not 
     * @param bool $destroyable 1 if this item can be detroyed, 0 if not
     * @param integer $maxQuantityInPlayerInventory The new maximum quantity of this itema player may hold
     * @return returnData
     * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
     * @see returnData
     */
    public static function updateItem($gameId, $itemId, $name, $description, 
            $iconMediaId, $mediaId, $droppable, $destroyable, $tradeable, $attribute, $maxQuantityInPlayerInventory, $weight, $url, $type)
    {
        $prefix = Module::getPrefix($gameId);

        $name = addslashes($name);	
        $description = addslashes($description);	

        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "UPDATE items 
                SET name = '{$name}', 
                description = '{$description}', 
                icon_media_id = '{$iconMediaId}',
                media_id = '{$mediaId}', 
                dropable = '{$droppable}',
                destroyable = '{$destroyable}',
                tradeable = '{$tradeable}',
                is_attribute = '{$attribute}',
                max_qty_in_inventory = '{$maxQuantityInPlayerInventory}',
                weight = '{$weight}',
                url = '{$url}',
                type = '{$type}'
                WHERE item_id = '{$itemId}' AND game_id = '{$prefix}'";

        NetDebug::trace("updateNpc: Running a query = $query");	

        @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);

        if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
        else return new returnData(0, FALSE, "Success Running:" . $query);


    }


    /**
     * Deletes an Item from a game, removing any refrence made to it in the rest of the game
     *
     * When this service runs, locations, requirements, playerStatechanges and player inventories
     * are updated to remove any refrence to the deleted item.
     *
     * @param integer $gameId The game identifier
     * @param integer $itemId The item identifier
     * @return returnData
     * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
     * @see returnData
     */
    public static function deleteItem($gameId, $itemId)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        Locations::deleteLocationsForObject($gameId, 'Item', $itemId);
        Requirements::deleteRequirementsForRequirementObject($gameId, 'Item', $itemId);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'Item', $itemId);
        Module::removeItemFromAllPlayerInventories($prefix, $itemId );

        $query = "DELETE FROM items WHERE item_id = {$itemId} AND game_id = '{$prefix}'";

        $rsResult = @mysql_query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }

    }	

    public static function commitTradeTransaction($gameId, $pOneId, $pTwoId, $giftsFromPOneJSON, $giftsFromPTwoJSON)
    {
        /* $giftsFromPNJSON format- 
           {"items":[{"item_id":1,"qtyDelta":3},{"item_id":2,"qtyDelta":4}]}
         */
        $pOneGifts = $giftsFromPOneJSON["items"];
        $pTwoGifts = $giftsFromPTwoJSON["items"];
        
        foreach($pOneGifts as $pog)
        {
            Module::adjustQtyForPlayerItem($gameId, $pog["item_id"], $pOneId, -1*$pog["qtyDelta"]);
            Module::adjustQtyForPlayerItem($gameId, $pog["item_id"], $pTwoId, $pog["qtyDelta"]);
        }
        foreach($pTwoGifts as $ptg)
        {
            Module::adjustQtyForPlayerItem($gameId, $ptg["item_id"], $pTwoId, -1*$ptg["qtyDelta"]);
            Module::adjustQtyForPlayerItem($gameId, $ptg["item_id"], $pOneId, $ptg["qtyDelta"]);
        }
        return new returnData(0);
    }










    // \/ \/ \/ BACKPACK FUNCTIONS \/ \/ \/

    public static function getDetailedPlayerAttributes($playerId, $gameId)
    {
        /* ATTRIBUTES */
        $query = "SELECT DISTINCT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.file_path as media_url, m.game_id as media_game_id, im.file_path as icon_url, im.game_id as icon_game_id FROM (SELECT * FROM player_items WHERE game_id = {$gameId}) as pi LEFT JOIN (SELECT * FROM items WHERE game_id = {$gameId}) as i ON pi.item_id = i.item_id LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type = 'ATTRIB' GROUP BY i.item_id";

        $result = mysql_query($query);
        $contents = array();
        while($content = mysql_fetch_object($result)) {
            if($content->media_url) $content->media_url = Config::gamedataWWWPath . '/' . $content->media_url;
            if($content->icon_url) $content->icon_url = Config::gamedataWWWPath . '/' . $content->icon_url;
            $contents[] = $content;
        }
        return $contents;
    }

    public static function getDetailedPlayerItems($playerId, $gameId)
    {
        /* OTHER ITEMS */
        $query = "SELECT DISTINCT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.file_path as media_url, m.game_id as media_game_id, im.file_path as icon_url, im.game_id as icon_game_id FROM (SELECT * FROM player_items WHERE game_id={$gameId}) as pi LEFT JOIN (SELECT * FROM items WHERE game_id = {$gameId}) as i ON pi.item_id = i.item_id LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type != 'ATTRIB' GROUP BY i.item_id";

        $result = mysql_query($query);
        $contents = array();
        while($content = mysql_fetch_object($result)){
            if($content->media_url) $content->media_url = Config::gamedataWWWPath . '/' . $content->media_url;
            if($content->icon_url) $content->icon_url = Config::gamedataWWWPath . '/' . $content->icon_url;
            $contents[] = $content;
        }

        return $contents;
    }
}
