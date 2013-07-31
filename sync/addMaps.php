<?php
$version = 'aris_1_5';
require_once("../services/{$version}/module.php");
$game_id = 173;
    $pattern = '/^map\d+\.png/';

// find files to add

$dir = Config::gamedataFSPath . '/' . $game_id;
if ($handle = opendir($dir)) {  
  
  $mapFiles = array();
  while (false !== ($file = readdir($handle))) {        
    preg_match($pattern, $file, $matches);
    if (preg_match($pattern, $file)) {
      $mapFiles[] = $file;
    }
  }
  closedir($handle);
  
  //
  $link = mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
  if (!$link) {
    die('Could not connect: ' . mysql_error());
  }
  $db_selected = mysql_select_db (Config::dbSchema);
  if (!$db_selected) {
    die ('Can\'t use foo : ' . mysql_error());
  }
  
  $result = mysql_query("SELECT * FROM media WHERE game_id = $game_id");
  if (!$result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query_: ' . $query;
    die($message);
  }
  
  $dbMapFiles = array();
  while ($row = mysql_fetch_assoc($result)) {
    $dbMapFiles[] = $row;
  }
  
  // add missing
  foreach ($mapFiles as $mapFile) {
    $found = false;
    foreach ($dbMapFiles as $dbMapFile) {
      if ($dbMapFile['file_name'] == $mapFile) {
        $found = true;
        break;
      }
    }
    if (!$found) {
      $result = mysql_query("INSERT INTO media (game_id, name, file_name, is_icon) VALUES ($game_id, 'Map', '$mapFile', 0)");
      if (!$result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n";
        die($message);
      }
      echo "added $mapFile";
    }
  }  
 
   // remove 
  foreach ($dbMapFiles as $dbMapFile) {
    if (!preg_match($pattern, $dbMapFile['file_name'])) {
      continue;
    }
	  $found = false;
    foreach ($mapFiles as $mapFile) {
      if ($dbMapFile['file_name'] == $mapFile) {
        $found = true;
        break;
      }
    }	
    if (!$found) {
      $query = "DELETE FROM media WHERE game_id = $game_id AND file_name = '{$dbMapFile['file_name']}'";
      $result = mysql_query($query);
      if (!$result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n";
        $message .= 'Whole query_: ' . $query;
        die($message);
      }
      echo "removed {$dbMapFile['file_name']}";
    }

  }
 
}