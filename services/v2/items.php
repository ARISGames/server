<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class Items extends Module
{
    public static function getItems($gameId)
    {
        $query = "SELECT * FROM items WHERE game_id = '{$gameId}'";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public static function getItemsForPlayer($gameId, $playerId)
    {
        $query = "SELECT game_items.*, game_player_items.qty, game_player_items.viewed FROM (SELECT * FROM items WHERE game_id = {$gameId}) AS game_items JOIN (SELECT * FROM player_items WHERE game_id = {$gameId} AND player_id = $playerId) AS game_player_items ON game_items.item_id = game_player_items.item_id";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        if (!$rsResult) return new returnData(3, NULL, "Something bad happened");
        return new returnData(0, $rsResult);
    }	

    public static function getItemCountForPlayer($obj)
    {
        $gameId = $obj['gameId'];
        $playerId = $obj['playerId'];
        $itemId = $obj['itemId'];

        $query = "SELECT qty FROM player_items WHERE player_id = $playerId AND item_id = $itemId AND game_id = '{$gameId}'";

        $rsResult = Module::query($query);
        if (!$rsResult) return new returnData(0, NULL);
        $row = @mysql_fetch_row($rsResult);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $row[0]);
    }

    public static function getAttributesForPlayer($gameId, $playerId)
    {
        $query = "SELECT game_items.*, game_player_items.qty FROM (SELECT * FROM items WHERE game_id = {$gameId} AND is_attribute = '1') AS game_items JOIN (SELECT * FROM player_items WHERE game_id = {$gameId} AND player_id = $playerId) AS game_player_items ON game_items.item_id = game_player_items.item_id";

        $rsResult = Module::query($query);
        if (!$rsResult) return new returnData(0, NULL);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public static function getItem($gameId, $itemId)
    {
        $query = "SELECT * FROM items WHERE item_id = {$itemId} AND game_id = '{$gameId}' LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $item = @mysql_fetch_object($rsResult);
        if (!$item) return new returnData(2, NULL, "invalid item id");

        return new returnData(0, $item);
    }

    public static function createItem($gameId, $name, $description, $iconMediaId, $mediaId, $droppable, $destroyable, $tradeable, $attribute, $maxQuantityInPlayerInventory, $weight, $url, $type)
    {
        $name = addslashes($name);	
        $description = addslashes($description);	

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


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		

        return new returnData(0, mysql_insert_id());
    }

    public static function updateItem($gameId, $itemId, $name, $description, 
            $iconMediaId, $mediaId, $droppable, $destroyable, $tradeable, $attribute, $maxQuantityInPlayerInventory, $weight, $url, $type)
    {
        $name = addslashes($name);	
        $description = addslashes($description);	

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
                    WHERE item_id = '{$itemId}' AND game_id = '{$gameId}'";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);

        if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
        else return new returnData(0, FALSE, "Success Running:" . $query);


    }

    public static function deleteItem($gameId, $itemId)
    {
        Locations::deleteLocationsForObject($gameId, 'Item', $itemId);
        Requirements::deleteRequirementsForRequirementObject($gameId, 'Item', $itemId);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'Item', $itemId);
        Module::removeItemFromAllPlayerInventories($gameId, $itemId );

        $query = "DELETE FROM items WHERE item_id = {$itemId} AND game_id = '{$gameId}'";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if(mysql_affected_rows())
            return new returnData(0, TRUE);
        else
            return new returnData(0, FALSE);
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

    public static function getTag($gameId, $tagId)
    {
        $query = "SELECT tag as name, tag_id FROM game_object_tags WHERE game_id = '{$gameId}' AND tag_id = '{$tagId}'";
        $result = Module::query($query);
        $t = mysql_fetch_object($result);
        return new returnData(0, $t);
    }

    public static function getTags($gameId)
    {
        $query = "SELECT tag as name, tag_id FROM game_object_tags WHERE game_id = '{$gameId}'";
        $result = Module::query($query);
        $ts = array();
        while($t = mysql_fetch_object($result))
            $ts[] = $t;
        return new returnData(0, $ts);
    }

    public static function getItemTags($itemId)
    {
        $query = "SELECT game_object_tags.tag as name, game_object_tags.tag_id FROM game_object_tags RIGHT JOIN object_tags ON game_object_tags.tag_id = object_tags.tag_id WHERE object_tags.object_type = 'ITEM' AND object_tags.object_id = '{$itemId}'";
        $result = Module::query($query);
        $ts = array();
        while($t = mysql_fetch_object($result))
            $ts[] = $t;
        return new returnData(0, $ts);
    }

    public static function addItemTag($gameId, $tag)
    {
        $query = "INSERT INTO game_object_tags (game_id, tag) VALUES ('{$gameId}', '{$tag}');";
        Module::query($query);
        return new returnData(0, mysql_insert_id());
    }

    public static function deleteTag($gameId, $tagId)
    {
        $query = "DELETE FROM object_tags WHERE tag_id = '{$tagId}'";
        Module::query($query);
        $query = "DELETE FROM game_object_tags WHERE tag_id = '{$tagId}'";
        Module::query($query);
        return new returnData(0);
    }

    public static function tagItem($gameId, $itemId, $tagId)
    {
        $query = "INSERT INTO object_tags (object_type, object_id, tag_id) VALUES ('ITEM', '{$itemId}', '{$tagId}');";
        Module::query($query);
        return new returnData(0);
    }

    public static function untagItem($gameId, $itemId, $tagId)
    {
        $query = "DELETE FROM object_tags WHERE object_type = 'ITEM' AND object_id = '{$itemId}' AND tag_id = '{$tagId}';";
        Module::query($query);
        return new returnData(0);
    }

    // \/ \/ \/ BACKPACK FUNCTIONS \/ \/ \/

    public static function getDetailedPlayerAttributes($playerId, $gameId)
    {
        /* ATTRIBUTES */
        $query = "SELECT DISTINCT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.file_path as media_url, m.game_id as media_game_id, im.file_path as icon_url, im.game_id as icon_game_id FROM (SELECT * FROM player_items WHERE game_id = {$gameId} AND player_id = {$playerId}) as pi LEFT JOIN (SELECT * FROM items WHERE game_id = {$gameId}) as i ON pi.item_id = i.item_id LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE i.type = 'ATTRIB' GROUP BY i.item_id";

        $result = Module::query($query);
        $contents = array();
        while($content = mysql_fetch_object($result)) {
            if($content->media_url) $content->media_url = Config::gamedataWWWPath . '/' . $content->media_url;
            if($content->icon_url) $content->icon_url = Config::gamedataWWWPath . '/' . $content->icon_url;
            $content->tags = Items::getItemTags($content->item_id)->data;
            $contents[] = $content;
        }
        return $contents;
    }

    public static function getDetailedPlayerItems($playerId, $gameId)
    {
        /* OTHER ITEMS */
        $query = "SELECT DISTINCT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.file_path as media_url, m.game_id as media_game_id, im.file_path as icon_url, im.game_id as icon_game_id FROM (SELECT * FROM player_items WHERE game_id={$gameId} AND player_id = {$playerId}) as pi LEFT JOIN (SELECT * FROM items WHERE game_id = {$gameId}) as i ON pi.item_id = i.item_id LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE i.type != 'ATTRIB' GROUP BY i.item_id";

        $result = Module::query($query);
        $contents = array();
        while($content = mysql_fetch_object($result)){
            if($content->media_url) $content->media_url = Config::gamedataWWWPath . '/' . $content->media_url;
            if($content->icon_url) $content->icon_url = Config::gamedataWWWPath . '/' . $content->icon_url;
            $content->tags = Items::getItemTags($content->item_id)->data;
            $contents[] = $content;
        }

        return $contents;
    }
}
