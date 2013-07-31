<?php

// remove

/*
require_once("../services/aris_1_5/module.php");

$data = json_decode(file_get_contents("php://input"));

class NetDebug {

	static function trace($dummy) {
		// do nothing, just to get it working
	}
}

class ModuleExt extends Module {
	public function getPrefix($gameId) {
		return Module::getPrefix($gameId);
	}
}


function sync($data) {
	require_once('../services/aris/games.php');
	require_once('../services/aris/quests.php');
	
	$gameId = $data->gameId;
	$games = new Games();
	$out = array();
	//$result = $games->getGame($gameId);
	$result = $games->getGames();
  while ($game = mysql_fetch_object($result->data)) {
		print_r($game);
	}
	return;
	
	$game = Game::getFullGameObject($intGameId, $intPlayerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0);
	//$out['game'] = $result->data;
	
	$quests = new Quests();
	$result = $quests->getQuests($gameId);
	$out['quests'] = array();
	while ($quest = mysql_fetch_object($result->data)) {
		$out['quests'][] = $quest;
	}
	
	$media = new Media();
	$result = $media->getMedia($gameId);
	$out['media'] = $result->data;
	
	
	//print_r(Config::dbUser);
	$prefix = ModuleExt::getPrefix($gameId);
	$sql = "SELECT * FROM {$prefix}_requirements";
	$result = mysql_query($sql);
	while ($requirement = mysql_fetch_object($result)) {
		$out['requirements'][] = $requirement;
	}
	
	// TODO;
	// items
	
	//return $out;
}
*/


//$out = sync((object)array("gameId" => 172, "playerId" => 98));
//print_r($out);
//$out = sync($data);
//echo json_encode($out);

