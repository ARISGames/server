<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("dialogs.php");
require_once("tabs.php");
require_once("tags.php");
require_once("instances.php");
require_once("factories.php");
require_once("events.php");
require_once("requirements.php");

class items extends dbconnection
{
    //Takes in item JSON, all fields optional except game_id + user_id + key
    public static function createItem($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->item_id = dbconnection::queryInsert(
            "INSERT INTO items (".
            "game_id,".
            (isset($pack->name)                 ? "name,"                 : "").
            (isset($pack->description)          ? "description,"          : "").
            (isset($pack->icon_media_id)        ? "icon_media_id,"        : "").
            (isset($pack->media_id)             ? "media_id,"             : "").
            (isset($pack->droppable)            ? "droppable,"            : "").
            (isset($pack->destroyable)          ? "destroyable,"          : "").
            (isset($pack->max_qty_in_inventory) ? "max_qty_in_inventory," : "").
            (isset($pack->weight)               ? "weight,"               : "").
            (isset($pack->url)                  ? "url,"                  : "").
            (isset($pack->type)                 ? "type,"                 : "").
            (isset($pack->delta_notification)   ? "delta_notification,"   : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                 ? "'".addslashes($pack->name)."',"                 : "").
            (isset($pack->description)          ? "'".addslashes($pack->description)."',"          : "").
            (isset($pack->icon_media_id)        ? "'".addslashes($pack->icon_media_id)."',"        : "").
            (isset($pack->media_id)             ? "'".addslashes($pack->media_id)."',"             : "").
            (isset($pack->droppable)            ? "'".addslashes($pack->droppable)."',"            : "").
            (isset($pack->destroyable)          ? "'".addslashes($pack->destroyable)."',"          : "").
            (isset($pack->max_qty_in_inventory) ? "'".addslashes($pack->max_qty_in_inventory)."'," : "").
            (isset($pack->weight)               ? "'".addslashes($pack->weight)."',"               : "").
            (isset($pack->url)                  ? "'".addslashes($pack->url)."',"                  : "").
            (isset($pack->type)                 ? "'".addslashes($pack->type)."',"                 : "").
            (isset($pack->delta_notification)   ? "'".addslashes($pack->delta_notification)."',"   : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return items::getItem($pack);
    }

    //Takes in game JSON, all fields optional except item_id + user_id + key
    public static function updateItem($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$pack->item_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE items SET ".
            (isset($pack->name)                 ? "name                 = '".addslashes($pack->name)."', "                 : "").
            (isset($pack->description)          ? "description          = '".addslashes($pack->description)."', "          : "").
            (isset($pack->icon_media_id)        ? "icon_media_id        = '".addslashes($pack->icon_media_id)."', "        : "").
            (isset($pack->media_id)             ? "media_id             = '".addslashes($pack->media_id)."', "             : "").
            (isset($pack->droppable)            ? "droppable            = '".addslashes($pack->droppable)."', "            : "").
            (isset($pack->destroyable)          ? "destroyable          = '".addslashes($pack->destroyable)."', "          : "").
            (isset($pack->max_qty_in_inventory) ? "max_qty_in_inventory = '".addslashes($pack->max_qty_in_inventory)."', " : "").
            (isset($pack->weight)               ? "weight               = '".addslashes($pack->weight)."', "               : "").
            (isset($pack->url)                  ? "url                  = '".addslashes($pack->url)."', "                  : "").
            (isset($pack->type)                 ? "type                 = '".addslashes($pack->type)."', "                 : "").
            (isset($pack->delta_notification)   ? "delta_notification   = '".addslashes($pack->delta_notification)."', "   : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE item_id = '{$pack->item_id}'"
        );

        games::bumpGameVersion($pack);
        return items::getItem($pack);
    }

    private static function itemObjectFromSQL($sql_item)
    {
        if(!$sql_item) return $sql_item;
        $item = new stdClass();
        $item->item_id              = $sql_item->item_id;
        $item->game_id              = $sql_item->game_id;
        $item->name                 = $sql_item->name;
        $item->description          = $sql_item->description;
        $item->icon_media_id        = $sql_item->icon_media_id;
        $item->media_id             = $sql_item->media_id;
        $item->media_id_2           = $sql_item->media_id_2;
        $item->media_id_3           = $sql_item->media_id_3;
        $item->droppable            = $sql_item->droppable;
        $item->destroyable          = $sql_item->destroyable;
        $item->max_qty_in_inventory = $sql_item->max_qty_in_inventory;
        $item->weight               = $sql_item->weight;
        $item->url                  = $sql_item->url;
        $item->type                 = $sql_item->type;
        $item->delta_notification   = $sql_item->delta_notification;

        return $item;
    }

    public static function getItem($pack)
    {
        $sql_item = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$pack->item_id}' LIMIT 1");
        return new return_package(0,items::itemObjectFromSQL($sql_item));
    }

    public static function getItemsForGame($pack)
    {
        $sql_items = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$pack->game_id}'");
        $items = array();
        for($i = 0; $i < count($sql_items); $i++)
            if($ob = items::itemObjectFromSQL($sql_items[$i])) $items[] = $ob;

        return new return_package(0,$items);
    }

    public static function deleteItem($pack)
    {
        $item = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$pack->item_id}'");
        $pack->auth->game_id = $item->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM items WHERE item_id = '{$pack->item_id}' LIMIT 1");
        //cleanup
        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE link_type = 'EXIT_TO_ITEM' AND link_id = '{$pack->item_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOption($pack);
        }

        $tabs = dbconnection::queryArray("SELECT * FROM tabs WHERE type = 'ITEM' AND content_id = '{$pack->item_id}'");
        for($i = 0; $i < count($tabs); $i++)
        {
            $pack->tab_id = $tabs[$i]->tab_id;
            tabs::deleteTab($pack);
        }

        $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE object_type = 'ITEM' AND object_id = '{$pack->item_id}'");
        for($i = 0; $i < count($tags); $i++)
        {
            $pack->object_tag_id = $tags[$i]->object_tag_id;
            tags::deleteObjectTag($pack);
        }

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'ITEM' AND object_id = '{$pack->item_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstance($pack);
        }

        $factories = dbconnection::queryArray("SELECT * FROM factories WHERE object_type = 'ITEM' AND object_id = '{$pack->item_id}'");
        for($i = 0; $i < count($factories); $i++)
        {
            $pack->factory_id = $factories[$i]->factory_id;
            factories::deleteFactory($pack);
        }

        $events = dbconnection::queryArray("SELECT * FROM events WHERE (event = 'GIVE_ITEM_PLAYER' OR event = 'TAKE_ITEM_PLAYER' OR event = 'GIVE_ITEM_GAME' OR event = 'TAKE_ITEM_GAME') AND content_id = '{$pack->item_id}'");
        for($i = 0; $i < count($events); $i++)
        {
            $pack->event_id = $events[$i]->event_id;
            events::deleteEvent($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_VIEWED_ITEM' AND content_id = '{$pack->item_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }
        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_ITEM' AND content_id = '{$pack->item_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }
        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'GAME_HAS_ITEM' AND content_id = '{$pack->item_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
?>
