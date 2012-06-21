<?php
require_once("module.php");

class Spawnables extends Module
{
  public function createSpawnable($gameId, $type, $typeId, $amount, $area, $amountRestriction, $locationBoundType, $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle)
  {
    $query = "INSERT INTO spawnables (game_id, type, type_id, amount, area, amount_restriction, location_bound_type, latitude, longitude, spawn_probability, spawn_rate, delete_when_viewed, time_to_live, error_range, force_view, hidden, allow_quick_travel, wiggle) VALUES ($gameId, '{$type}', $typeId, $amount, $area, '{$amountRestriction}', '{$locationBoundType}', $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle);";
    mysql_query($query);
    return new returnData(0);
  }

  public function hasSpawnable($gameId, $type, $typeId)
  {
    $query = "SELECT * FROM spawnables WHERE game_id = $gameId AND type = '$type' AND type_id = $typeId"; 
    $obj = mysql_query($query);
    if(mysql_num_rows($obj) > 0) return true;
    else return false;
  }

  public function deleteSpawnable($spawnableId)
  {
    $query = "SELECT * FROM spawnables WHERE spawnable_id = $spawnableId";
    $result = mysql_query($query);
    $obj = mysql_fetch_object($result);
    if($obj)
    {
      $query = "DELETE FROM spawnables WHERE spawnable_id = $spawnableId";
      mysql_query($query);
      $query = "DELETE FROM ".$obj->game_id."_requirements WHERE content_type = 'Spawnable' AND content_id = $spawnableId";
      mysql_query($query);
    }
    return new returnData(0);
  }

  public static function deleteSpawnablesOfObject($gameId, $type, $typeId)
  {
    Module::serverErrorLog("Started");
    $query = "SELECT * FROM spawnables WHERE game_id = $gameId AND type = '".$type."' AND type_id = $typeId";
    $result = mysql_query($query);
    $obj = mysql_fetch_object($result);
    Module::serverErrorLog("Here");
    if($obj)
    {
      Module::serverErrorLog("Deleting");
      $query = "DELETE FROM spawnables WHERE spawnable_id = $obj->spawnable_id";
      mysql_query($query);
      Module::serverErrorLog("Deleter");
      $query = "DELETE FROM ".$gameId."_requirements WHERE content_type = 'Spawnable' AND content_id = $obj->spawnable_id";
      mysql_query($query);
      Module::serverErrorLog("Deleted");
    }
    return new returnData(0);
  }

  public function updateSpawnable($spawnableId, $gameId, $type, $typeId, $amount, $area, $amountRestriction, $locationBoundType, $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle)
  {
    $query = "UPDATE spawnables SET amount = $amount, area = $area, amount_restriction = '{$amountRestriction}', location_bound_type = '{$locationBoundType}', latitude = $lat, longitude = $lon, spawn_probability = $spawnProbability, spawn_rate = $spawnRate, delete_when_viewed = $deleteWhenViewed, time_to_live = $timeToLive, error_range = $errorRange, force_view = $forceView, hidden = $hidden, allow_quick_travel = $allowQuickTravel, wiggle = $wiggle WHERE game_id = $gameId AND type = '{$type}' AND type_id = $typeId";
    Module::serverErrorLog($query);
    mysql_query($query);
    Module::serverErrorLog(mysql_error());
    return new returnData(0);
  }

  public function createSpawnableForObject($gameId, $type, $typeId)
  {
    $query = "INSERT INTO spawnables (game_id, type, type_id) VALUES ($gameId, '$type', $typeId)";
    $result = mysql_query($query);
    return Spawnables::getSpawnableForObject($gameId, $type, $typeId);
  }

  public function getSpawnableForObject($gameId, $type, $typeId)
  {
    $query = "SELECT * FROM spawnables WHERE game_id = $gameId AND type = '".$type."' AND type_id = '".$typeId."' LIMIT 1";
    $result = mysql_query($query);
    $obj = mysql_fetch_object($result);
    if($obj) return new returnData(0, $obj);
    else return new returnData(1, "No Spawnables For Object");
  }

  public function getSpawnablesForGame($gameId)
  {
    $query = "SELECT * FROM spawnables WHERE game_id = $gameId";
    $result = mysql_query($query);
    $spawnables = array();
    while($obj = mysql_fetch_object($result))
    {
      $spawnables[] = $obj;
    }
    return new returnData(0, $spawnables);
  }
}
