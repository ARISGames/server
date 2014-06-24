<?php
require_once("module.php");

class Scratch extends Module
{
	public function topPlayersWithMostLikedNotes($gameId, $startFlag="0000-00-00 00:00:00", $endFlag="9999-99-99 12:59:59")
	{
		$notes = Module::queryArray("SELECT note_id, owner_id FROM notes WHERE game_id = '{$gameId}' AND created > '{$startFlag}' AND created < '{$endFlag}'");
		$playerLikes = array();
		for($i = 0; $i < count($notes); $i++)
		{
			if(!$playerLikes[$notes[$i]->owner_id]) $playerLikes[$notes[$i]->owner_id] = 0;
			if(Module::queryObject("SELECT player_id FROM note_likes WHERE note_id = '{$notes[$i]->note_id}' LIMIT 1"))
				$playerLikes[$notes[$i]->owner_id]++;
		}
		$playerLikeObjects = array();
		foreach($playerLikes as $pidkey => $countval)
		{
			$plo = new stdClass();
			$plo->player_id = $pidkey;
			$plo->liked_notes = $countval;
			$plo->display_name = Module::queryObject("SELECT display_name FROM players WHERE player_id = '{$pidkey}'")->display_name;
			$playerLikeObjects[] = $plo;
		}

		return $playerLikeObjects;
	}
}

?>

