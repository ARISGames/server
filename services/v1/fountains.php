<?php
require_once("module.php");
/*
   +-------------------+------------------------------+------+-----+-------------------+-----------------------------+
   | Field             | Type                         | Null | Key | Default           | Extra                       |
   +-------------------+------------------------------+------+-----+-------------------+-----------------------------+
   | fountain_id       | int(11)                      | NO   | PRI | NULL              | auto_increment              |
   | game_id           | int(11)                      | NO   |     | NULL              |                             |
   | type              | enum('Location','Spawnable') | NO   |     | NULL              |                             |
   | location_id       | int(11)                      | NO   |     | NULL              |                             |
   | spawn_probability | double                       | NO   |     | NULL              |                             |
   | spawn_rate        | int(11)                      | NO   |     | NULL              |                             |
   | max_amount        | int(11)                      | NO   |     | NULL              |                             |
   | last_spawned      | timestamp                    | NO   |     | CURRENT_TIMESTAMP | on update CURRENT_TIMESTAMP |
   | active            | tinyint(1)                   | NO   |     | 1                 |                             |
   +-------------------+------------------------------+------+-----+-------------------+-----------------------------+
 */
class Fountains extends Module
{
    public static function createFountain($gameId, $type, $locationId, $spawnProbability, $spawnRate, $maxAmount)
    {
        if($type == '') $type = 'Location';

        if($fountainId = Fountains::hasFountain($gameId, $locationId))
            $query = "UPDATE fountains SET active = 1 WHERE game_id = $gameId AND location_id = $locationId";
        else
            $query = "INSERT INTO fountains (game_id, type, location_id, spawn_probability, spawn_rate, max_amount, active) VALUES 
            ($gameId, '{$type}', $locationId, spawn_probability, spawn_rate, max_amount, 1)";

        mysql_query($query);
        $fountainId = mysql_insert_id();
        return new returnData(0,$fountainId);
    }

    public static function hasActiveFountain($gameId, $locationId)
    {
        $query = "SELECT * FROM fountains WHERE game_id = $gameId AND location_id = $locationId AND active = 1"; 
        $result = mysql_query($query);
        if($obj = mysql_fetch_object($result)) return $obj->fountain_id;
        else return false;
    }

    public static function hasFountain($gameId, $locationId)
    {
        $query = "SELECT * FROM fountains WHERE game_id = $gameId AND location_id = $locationId"; 
        $result = mysql_query($query);
        if($obj = mysql_fetch_object($result)) return $obj->fountain_id;
        else return false;
    }

    public static function deleteFountain($fountainId)
    {
        $query = "UPDATE fountains SET active = 0 WHERE fountain_id = $fountainId";
        mysql_query($query);
        /*
        //This does a hard delete
        $query = "DELETE FROM fountains WHERE fountain_id = $fountainId";
        mysql_query($query);
         */
        return new returnData(0);
    }

    public static function deleteFountainOfLocation($gameId, $locationId)
    {
        if($fountainId = Fountains::hasFountain($gameId, $locationId))
            Fountains::deleteFountain($fountainId);
        return new returnData(0);
    }

    //Optionally by fountainId or by gameId and locationId
    public static function updateFountain($fountainId = 0, $gameId, $locationId, $type, $spawnProbability, $spawnRate, $maxAmount, $active)
    {
        if($fountainId == 0)
            $query = "UPDATE fountains SET type = '{$type}',  spawn_probability = $spawnProbability, spawn_rate = $spawnRate, max_amount = $maxAmount, active = $active WHERE game_id = $gameId AND location_id = $locationId";
        else
            $query = "UPDATE fountains SET location_id = $locationId, type = '{$type}',  spawn_probability = $spawnProbability, spawn_rate = $spawnRate, max_amount = $maxAmount, active = $active WHERE fountain_id = $fountainId";
        mysql_query($query);
        return new returnData(0);
    }

    public static function createFountainForLocation($gameId, $locationId)
    {
        Fountains::createFountain($gameId, 'Location', $locationId, 50, 10, 5);
        return Fountains::getFountainForLocation($gameId, $locationId);
    }

    public static function getFountainForLocation($gameId, $locationId)
    {
        $query = "SELECT * FROM fountains WHERE game_id = $gameId AND location_id = $locationId AND active = 1 LIMIT 1";
        $result = mysql_query($query);
        $obj = mysql_fetch_object($result);
        if($obj) return new returnData(0, $obj);
        else return new returnData(1, "No Fountains For Location");
    }

    public static function getFountainsForGame($gameId)
    {
        $query = "SELECT * FROM fountains WHERE game_id = $gameId AND active = 1";
        $result = mysql_query($query);
        $fountains = array();
        while($obj = mysql_fetch_object($result))
            $fountains[] = $obj;
        return new returnData(0, $fountains);
    }
}
