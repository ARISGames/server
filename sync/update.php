<?php

$version = 'aris_1_5';
require_once("../services/{$version}/module.php");

$json = file_get_contents('php://input');
$data = json_decode($json);


/* print_r($data); */
$game_id = $data->gameId;
$player_id = $data->playerId;

$return = array('game' => $game_id);

$link = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
if (!$link) {
  echo json_encode($return);
  exit(); 
}
mysql_select_db (Config::dbSchema);

if (isset($data->player_logs)) {
  foreach($data->player_logs as $player_log) {
    // check if already exists
    $result = mysql_query("SELECT * FROM player_log WHERE UNIX_TIMESTAMP(timestamp) = " . $player_log->timestamp);
    if (mysql_num_rows($result) > 0) {
      $return['player_logs'][] = $player_log->id;
      continue;
    }
  
    $event_detail_1 = isset($player_log->event_detail_1) ? "{$player_log->event_detail_1}" : "";
    $event_detail_2 = isset($player_log->event_detail_1) ? "{$player_log->event_detail_2}" : "";
    $query = sprintf("INSERT INTO player_log (player_id, game_id, timestamp, event_type, event_detail_1, event_detail_2) VALUES(%d, %d, FROM_UNIXTIME(%d), '%s', '%s', '%s')", $player_log->player_id, $player_log->game_id, $player_log->timestamp, $player_log->event_type, $event_detail_1, $event_detail_2);
    if(mysql_query($query)) {
      $return['player_logs'][] = $player_log->id;
    }
  }
}

if (isset($data->player_items)) {
  foreach($data->player_items as $player_item) {
    // check if exists
    $result = mysql_query("SELECT * FROM {$game_id}_player_items WHERE item_id = {$player_item->item_id} AND player_id = $player_id");
    if (mysql_num_rows($result) == 0) {
      $query = sprintf("INSERT INTO {$game_id}_player_items (player_id, item_id, qty) VALUES (%d, %d, %d)", $player_id, $player_item->item_id, $player_item->qty);
      if(mysql_query($query)) {
        $return['player_items'][] = $player_item->id;
      }
    }
    else {
      $row = mysql_fetch_object($result);
      if ($row->qty == $player_item->qty) {
        $return['player_items'][] = $player_item->id;
      }
      else {
        mysql_query("UPDATE {$game_id}_player_items SET qty = {$player_item->qty} WHERE item_id = {$player_item->item_id} AND player_id = $player_id");
        if (mysql_affected_rows() > 0) {
          $return['player_items'][] = $player_item->id;
        }
      }
    }
  }
}

if (isset($data->locations)) {
  foreach($data->locations as $location) {
    $result = mysql_query("SELECT * FROM {$game_id}_locations WHERE location_id = {$location->location_id}");
    if (mysql_num_rows($result) > 0) {
      $row = mysql_fetch_object($result);
      if ($row->item_qty == $location->item_qty) {
        $return['locations'][] = $location->id;
      }
      else {
        mysql_query("UPDATE {$game_id}_locations SET item_qty = {$location->item_qty} WHERE location_id = {$location->location_id}");
        if (mysql_affected_rows() > 0) {
           $return['locations'][] = $location->id;
        }
      }
    }
  }
}


echo json_encode($return);