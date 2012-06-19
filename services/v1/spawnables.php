<?php
require_once("module.php");

class Spawnables extends Module
{
  protected function createSpawnable($gameId, $type, $typeId, $amount, $area, $amountRestriction, $locationBoundType, $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle)
  {
    $query = "INSERT INTO spawnables (game_id, type, type_id, amount, area, amount_restriction, location_bound_type, latitude, longitude, spawn_probability, spawn_rate, delete_when_viewed, time_to_live, error_range, force_view, hidden, allow_quick_travel, wiggle) VALUES ($gameId, '{$type}', $typeId, $amount, $area, '{$amountRestriction}', '{$locationBoundType}', $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle);";
    mysql_query($query);
    return new returnData(0);
  }

  protected function deleteSpawnable($spawnableId)
  {
    $query = "DELETE FROM spawnables WHERE spawnable_id = $spawnableId";
    mysql_query($query);
    return new returnData(0);
  }

  protected function deleteSpawnablesOfObject($gameId, $type, $typeId)
  {
    $query = "DELETE FROM spawnables WHERE game_id = $gameId AND type = $type AND type_id = $typeId";
    mysql_query($query);
    return new returnData(0);
  }

  protected function updateSpawnable($spawnableId, $gameId, $type, $typeId, $amount, $area, $amountRestriction, $locationBoundType, $lat, $lon, $spawnProbability, $spawnRate, $deleteWhenViewed, $timeToLive, $errorRange, $forceView, $hidden, $allowQuickTravel, $wiggle)
  {
    $query = "UPDATE spawnables SET game_id = $gameId, type = '{$type}', type_id = $typeId, amount = $amount, area = $area, amount_restriction = '{$amountRestriction}', location_bound_type = '{$locationBoundType}', latitude = $lat, longitude = $lon, spawn_probability = $spawnProbability, spawn_rate = $spawnRate, delete_when_viewed = $deleteWhenViewed, time_to_live = $timeToLive, error_range = $errorRange, force_view = $forceView, hidden = $hidden, allow_quick_travel = $allowQuickTravel, wiggle = $wiggle";
    mysql_query($query);
    return new returnData(0);
  }

  protected function getSpawnablesForGame($gameId)
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
