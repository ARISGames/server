<?php
require_once("module.php");

class Movables extends Module
{
    public static function createMovableTable(){

        //Create 'movables' table
        $query = "CREATE TABLE movables (
            movable_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                       game_id INT NOT NULL,
                       type ENUM('Node', 'Item', 'Npc', 'WebPage', 'AugBubble', 'PlayerNote') NOT NULL,
                       type_id INT NOT NULL,
                       algorithm_type ENUM('TOWARD_NEAREST_PLAYER', 'TOWARD_PLAYERS', 'STRAIGHT_LINE', 'CIRCLE', 'CUSTOM') NOT NULL DEFAULT 'TOWARD_NEAREST_PLAYER',
                       algorithm_detail_1 INT NOT NULL DEFAULT 0,
                       algorithm_detail_2 INT NOT NULL DEFAULT 0,
                       velocity INT NOT NULL DEFAULT 0,
                       move_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       active TINYINT(1) NOT NULL DEFAULT 1);";
        mysql_query($query);
    }

    public static function createMovable($gameId, $type, $typeId, $locationName, $algorithm_type, $algorithm_detail, $velocity, $moveStamp, $lat, $lon, $deleteWhenViewed, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle, $showTitle = 0)
    {
        if($movableId = Movables::hasMovable($gameId, $type, $typeId))
            $query = "UPDATE movables SET active = 1 WHERE game_id = $gameId AND type = '$type' AND type_id = $typeId";
        else
            $query = "INSERT INTO movables (game_id, type, type_id, location_name, algorithm_type, algorithm_detail, velocity, latitude, longitude, delete_when_viewed, error_range, force_view, hidden, allow_quick_travel, wiggle, show_title, active) VALUES ($gameId, '{$type}', $typeId, '$locationName', '{$algorithm_type}', $algorithm_detail, $velocity, $lat, $lon, $deleteWhenViewed, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle, $showTitle, 1);";

        mysql_query($query);
        $movableId = mysql_insert_id();
        return new returnData(0,$movableId);
    }

    public static function hasActiveMovable($gameId, $type, $typeId)
    {
        $query = "SELECT * FROM movables WHERE game_id = $gameId AND type = '$type' AND type_id = $typeId AND active = 1"; 
        $result = mysql_query($query);
        if($obj = mysql_fetch_object($result)) return $obj->movable_id;
        else return false;
    }

    public static function hasMovable($gameId, $type, $typeId)
    {
        $query = "SELECT * FROM movables WHERE game_id = $gameId AND type = '$type' AND type_id = $typeId"; 
        $result = mysql_query($query);
        if($obj = mysql_fetch_object($result)) return $obj->movable_id;
        else return false;
    }

    public static function deleteMovable($movableId)
    {
        $query = "UPDATE movables SET active = 0 WHERE movable_id = $movableId";
        mysql_query($query);
        /*
        //This does a hard delete
        $query = "SELECT * FROM movables WHERE movable_id = $movableId";
        $result = mysql_query($query);
        $obj = mysql_fetch_object($result);
        if($obj)
        {
        $query = "DELETE FROM movables WHERE movable_id = $movableId";
        mysql_query($query);
        $query = "DELETE FROM ".$obj->game_id."_requirements WHERE content_type = 'Movable' AND content_id = $movableId";
        mysql_query($query);
        }
         */
        return new returnData(0);
    }

    public static function deleteMovablesOfObject($gameId, $type, $typeId)
    {

        if($movableId = Movables::hasMovable($gameId, $type, $typeId))
            Movables::deleteMovable($movableId);
        return new returnData(0);
    }

    //Optionally by movableId or by gameId, type, and typeId
    public static function updateMovable($movableId = 0, $gameId, $type, $typeId, $locationName, $amount, $minArea, $maxArea, $amountRestriction, $locationBoundType, $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle, $active, $showTitle)
    {
        if($movableId == 0)
            $query = "UPDATE movables SET location_name = '$locationName', amount = $amount, min_area = $minArea, max_area = $maxArea, amount_restriction = '{$amountRestriction}', location_bound_type = '{$locationBoundType}', latitude = $lat, longitude = $lon, spawn_probability = $spawnProbability, spawn_rate = $spawnRate, delete_when_viewed = $deleteWhenViewed, time_to_live = $timeToLive, error_range = $errorRange, force_view = $forceView, hidden = $hidden, allow_quick_travel = $allowQuickTravel, wiggle = $wiggle, show_title = $showTitle, active = $active WHERE game_id = $gameId AND type = '{$type}' AND type_id = $typeId";
        else
            $query = "UPDATE movables SET game_id = $gameId, type = '$type', type_id = $typeId, location_name = '$locationName', amount = $amount, min_area = $minArea, max_area = $maxArea, amount_restriction = '{$amountRestriction}', location_bound_type = '{$locationBoundType}', latitude = $lat, longitude = $lon, spawn_probability = $spawnProbability, spawn_rate = $spawnRate, delete_when_viewed = $deleteWhenViewed, time_to_live = $timeToLive, error_range = $errorRange, force_view = $forceView, hidden = $hidden, allow_quick_travel = $allowQuickTravel, wiggle = $wiggle, show_title = $showTitle, active = $active WHERE movable_id = $movableId";
        mysql_query($query);
        return new returnData(0);
    }

    public static function createMovableForObject($gameId, $type, $typeId)
    {
        switch ($type) {
            case 'Item':
                $query = "SELECT name as title FROM {$gameId}_items WHERE item_id = {$typeId} LIMIT 1";
                break;
            case 'Node':
                $query = "SELECT title FROM {$gameId}_nodes WHERE node_id = {$typeId} LIMIT 1";
                break;
            case 'Npc':
                $query = "SELECT name as title FROM {$gameId}_npcs WHERE npc_id = {$typeId} LIMIT 1";
                break;
            case 'WebPage':
                $query = "SELECT name as title FROM web_pages WHERE web_page_id = {$typeId} LIMIT 1";
                break;
            case 'AugBubble':
                $query = "SELECT name as title FROM aug_bubbles WHERE aug_bubble_id = {$typeId} LIMIT 1";
                break;
        }
        $result = mysql_query($query);
        $obj = mysql_fetch_object($result);
        $title = $obj->title;
        Movables::createMovable($gameId, $type, $typeId, $title, 5, 35, 50, 'PER_PLAYER', 'PLAYER', 0, 0, 50, 10, 0, 100, 15, 0, 0, 0, 1, 0);
        return Movables::getMovableForObject($gameId, $type, $typeId);
    }

    public static function getMovableForObject($gameId, $type, $typeId)
    {
        $query = "SELECT * FROM movables WHERE game_id = $gameId AND type = '".$type."' AND type_id = '".$typeId."' AND active = 1 LIMIT 1";
        $result = mysql_query($query);
        $obj = mysql_fetch_object($result);
        if($obj) return new returnData(0, $obj);
        else return new returnData(1, "No Movables For Object");
    }

    public static function getMovablesForGame($gameId)
    {
        $query = "SELECT * FROM movables WHERE game_id = $gameId AND active = 1";
        $result = mysql_query($query);
        $movables = array();
        while($obj = mysql_fetch_object($result))
        {
            $movables[] = $obj;
        }
        return new returnData(0, $movables);
    }
}
