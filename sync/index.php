<?php
//ini_set('display_errors', 'On');
//error_reporting(E_ALL);
$version = "v1";

require_once("actions.php");
require_once('/var/www/html/server/config.class.php');
require_once("/var/www/html/server/services/{$version}/module.php");

//'/Users/mglumac/Development/arisgames/server/sync' .

/*
/    [action] => get_media_info
    [id] => 238
    [gameId] => 2
*/

class NetDebug {

	static function trace($dummy) {
		// do nothing, just to get it working
		//print_r($dummy);
	}
}


// Testing 
/*
$_REQUEST['get_media_info'] = 'get_media_info';
$_REQUEST['id'] = 238; 
$_REQUEST['gameId'] = 2;

$_REQUEST['action'] = 'get_media_info';
$_REQUEST['id'] = 1; 
$_REQUEST['gameId'] = 2;


$_REQUEST['action'] = 'get_all';
$_REQUEST['id'] = 173;
$_REQUEST['player_id'] = 98;

$_REQUEST['action'] = 'get_all';
$_REQUEST['id'] = 174;
$_REQUEST['player_id'] = 98;
*/

$action = $_REQUEST['action'];
switch ($action) {
  case 'get_media_info':
    $id = $_REQUEST['id'];
    $game_id = $_REQUEST['gameId'];
    $sync = new Sync();
    $media_info = $sync->get_media_info($id, $game_id);
    echo json_encode($media_info);
    break;
    
  case 'get_all':
    $id = $_REQUEST['id'];
    $player_id = $_REQUEST['player_id'];
    $actions = new Sync();
    $info = $actions->get_all($id, $player_id);
    $data = json_encode($info);
    header("Content-Length: " . strlen($data));
    echo $data;
    break;
    
  default: 
    echo json_encode(array('error' => 'Unknown action: ' . $action));
    break;
}

?>
