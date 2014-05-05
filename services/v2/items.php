<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class items extends dbconnection
{
    //Takes in item JSON, all fields optional except game_id + user_id + key
    public static function createItemJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return items::createItem($glob);
    }

    public static function createItem($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        $itemId = dbconnection::queryInsert(
            "INSERT INTO items (".
            "game_id,".
            ($pack->name                 ? "name,"                 : "").
            ($pack->description          ? "description,"          : "").
            ($pack->icon_media_id        ? "icon_media_id,"        : "").
            ($pack->media_id             ? "media_id,"             : "").
            ($pack->droppable            ? "droppable,"            : "").
            ($pack->destroyable          ? "destroyable,"          : "").
            ($pack->max_qty_in_inventory ? "max_qty_in_inventory," : "").
            ($pack->weight               ? "weight,"               : "").
            ($pack->url                  ? "url,"                  : "").
            ($pack->type                 ? "type,"                 : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->name                 ? "'".addslashes($pack->name)."',"                 : "").
            ($pack->description          ? "'".addslashes($pack->description)."',"          : "").
            ($pack->icon_media_id        ? "'".addslashes($pack->icon_media_id)."',"        : "").
            ($pack->media_id             ? "'".addslashes($pack->media_id)."',"             : "").
            ($pack->droppable            ? "'".addslashes($pack->droppable)."',"            : "").
            ($pack->destroyable          ? "'".addslashes($pack->destroyable)."',"          : "").
            ($pack->max_qty_in_inventory ? "'".addslashes($pack->max_qty_in_inventory)."'," : "").
            ($pack->weight               ? "'".addslashes($pack->weight)."',"               : "").
            ($pack->url                  ? "'".addslashes($pack->url)."',"                  : "").
            ($pack->type                 ? "'".addslashes($pack->type)."',"                 : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return items::getItem($itemId);
    }

    //Takes in game JSON, all fields optional except item_id + user_id + key
    public static function updateItemJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return items::updateItem($glob);
    }

    public static function updateItem($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$pack->item_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE items SET ".
            ($pack->name                 ? "name                 = '".addslashes($pack->name)."', "                 : "").
            ($pack->description          ? "description          = '".addslashes($pack->description)."', "          : "").
            ($pack->icon_media_id        ? "icon_media_id        = '".addslashes($pack->icon_media_id)."', "        : "").
            ($pack->media_id             ? "media_id             = '".addslashes($pack->media_id)."', "             : "").
            ($pack->droppable            ? "droppable            = '".addslashes($pack->droppable)."', "            : "").
            ($pack->destroyable          ? "destroyable          = '".addslashes($pack->destroyable)."', "          : "").
            ($pack->max_qty_in_inventory ? "max_qty_in_inventory = '".addslashes($pack->max_qty_in_inventory)."', " : "").
            ($pack->weight               ? "weight               = '".addslashes($pack->weight)."', "               : "").
            ($pack->url                  ? "url                  = '".addslashes($pack->url)."', "                  : "").
            ($pack->type                 ? "type                 = '".addslashes($pack->type)."', "                 : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE item_id = '{$pack->item_id}'"
        );

        return items::getItem($pack->item_id);
    }

    private static function itemObjectFromSQL($sql_item)
    {
        $item = new stdClass();
        $item->item_id              = $sql_item->item_id;
        $item->game_id              = $sql_item->game_id;
        $item->name                 = $sql_item->name;
        $item->description          = $sql_item->description;
        $item->icon_media_id        = $sql_item->icon_media_id;
        $item->media_id             = $sql_item->media_id;
        $item->droppable            = $sql_item->droppable;
        $item->destroyable          = $sql_item->destroyable;
        $item->max_qty_in_inventory = $sql_item->max_qty_in_inventory;
        $item->weight               = $sql_item->weight;
        $item->url                  = $sql_item->url;
        $item->type                 = $sql_item->type;

        return $item;
    }

    public static function getItem($itemId)
    {
        $sql_item = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$itemId}' LIMIT 1");
        return new return_package(0,items::itemObjectFromSQL($sql_item));
    }

    public static function getItemsForGame($gameId)
    {
        $sql_items = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$gameId}'");
        $items = array();
        for($i = 0; $i < count($sql_items); $i++)
            $items[] = items::itemObjctFromSQL($sql_items[$i]);

        return new return_package(0,$items);
    }

    public static function deleteItem($itemId, $userId, $key)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$itemId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM items WHERE item_id = '{$itemId}' LIMIT 1");
        return new return_package(0);
    }
}
?>
