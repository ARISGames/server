<?php
require_once("module.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Conversations extends Module
{	
	public function getConversationsWithNodeForNpc($gameId, $npcId)
	{
		$query = "SELECT game_npc_conversations.*, game_nodes.* 
			FROM 
			(SELECT npc_conversations.*, npc_conversations.text AS conversation_text FROM npc_conversations WHERE game_id = {$gameId} AND npc_id = {$npcId}) AS game_npc_conversations 
			JOIN 
			(SELECT * FROM nodes WHERE game_id = {$gameId}) AS game_nodes 
			ON 
			game_npc_conversations.node_id = game_nodes.node_id 
			ORDER BY sort_index";

		$rsResult = Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		return new returnData(0, $rsResult);	
	}

	public function swapSortIndex($gameId, $npcId, $a, $b, $editorId, $editorToken)
	{
		if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
			return new returnData(6, NULL, "Failed Authentication");

		$query = "SELECT * FROM npc_conversations WHERE game_id = {$gameId} AND npc_id = '{$npcId}' AND (conversation_id = '{$a}' OR conversation_id = '{$b}')";
		$result = Module::query($query);
		$convos = array();
		while($convo = mysql_fetch_object($result)){
			$convos[$convo->conversation_id] = $convo;
		}

		$query = "UPDATE npc_conversations SET sort_index = '{$convos[$a]->sort_index}' WHERE game_id = '{$gameId}' AND conversation_id = '{$b}'";
		Module::query($query);
		$query = "UPDATE npc_conversations SET sort_index = '{$convos[$b]->sort_index}' WHERE game_id = '{$gameId}' AND conversation_id = '{$a}'";
		Module::query($query);

		return new returnData(0);
	}

	public function createConversationWithNode($gameId, $npcId, $conversationText, $nodeText, $index, $editorId, $editorToken)
	{
		if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
			return new returnData(6, NULL, "Failed Authentication");

		$conversationText = addslashes($conversationText);	
		$nodeText = addslashes($nodeText);

		$nodeText = str_replace("“", "\"", $nodeText);
		$nodeText = str_replace("”", "\"", $nodeText);

		$query = "INSERT INTO nodes (game_id, text)
			VALUES ('{$gameId}','{$nodeText}')";

		Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		$newNodeId = mysql_insert_id();


		$query = "INSERT INTO npc_conversations (npc_id, game_id, node_id, text, sort_index)
			VALUES ('{$npcId}','{$gameId}','{$newNodeId}','{$conversationText}','{$index}')";
		Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		$newConversationId = mysql_insert_id();

		$ids = (object) array('conversation_id' => $newConversationId, 'node_id' => $newNodeId);

		return new returnData(0, $ids);

	}

	public function updateConversationWithNode($gameId, $conversationId, $conversationText, $nodeText, $index, $editorId, $editorToken)
	{
		if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
			return new returnData(6, NULL, "Failed Authentication");

		$conversationText = addslashes($conversationText);	
		$nodeText = addslashes($nodeText);

		$query = "SELECT node_id FROM npc_conversations WHERE game_id = '{$gameId}' AND conversation_id = {$conversationId} LIMIT 1";
		$nodeIdRs = Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);			
		$nodeIdObject = @mysql_fetch_object($nodeIdRs);
		if (!$nodeIdObject) return new returnData(2, NULL, "No such conversation");			
		$nodeId = $nodeIdObject->node_id;

		$nodeText = str_replace("“", "\"", $nodeText);
		$nodeText = str_replace("”", "\"", $nodeText);

		$query = "UPDATE nodes SET text = '{$nodeText}', title = '{$conversationText}' WHERE game_id = '{$gameId}' AND node_id = {$nodeId}";
		Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		$query = "UPDATE npc_conversations SET text = '{$conversationText}', sort_index = '{$index}' WHERE game_id = '{$gameId}' AND conversation_id = {$conversationId}";
		Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		return new returnData(0, TRUE);
	}	

	public function deleteConversationWithNode($gameId, $conversationId, $editorId, $editorToken)
	{
		if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
			return new returnData(6, NULL, "Failed Authentication");

		$query = "SELECT node_id FROM npc_conversations WHERE game_id = '{$gameId}' AND conversation_id = {$conversationId} LIMIT 1";
		$nodeIdRs = Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);			
		$nodeIdObject = @mysql_fetch_object($nodeIdRs);
		if (!$nodeIdObject) return new returnData(2, NULL, "No such conversation");			
		$nodeId = $nodeIdObject->node_id;

		Nodes::deleteNode($gameId, $nodeId);

		$query = "DELETE FROM npc_conversations WHERE game_id = '{$gameId}' AND conversation_id = {$conversationId}";
		$rsResult = Module::query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, NULL, 'invalid conversation id');
	}	


	public function searchGameForErrors($gid){

		//     $query = "SELECT name FROM games WHERE game_id = {$gid}";
		//     $name = mysql_query($query);

		//return "\nLooking for problems in {$name}\nNote: This check does not quarantee there are no errors in your game, but only checks for a few common mistakes.\n";	

		$query = "SELECT * FROM requirements WHERE game_id = {$gid}";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			if(!$row->requirement_detail_1){
				if($row->requirement == "PLAYER_HAS_ITEM" || $row->requirement == "PLAYER_VIEWED_ITEM"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which item the player needs to have/have viewed.\n";	
					else{
						$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which item the player needs to have/have viewed.\n";	
					}
					if(!$row->requirement_detail_2 && $row->requirement == "Player_HAS_ITEM"){
						if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that requires the player has a certain item, but not the quantity of that item needed.\n";	
						else{
							$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
							return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that requires that the player has a certain item, but not the quantity of that item neeeded.\n";	
						}
					}
				} 
				else if($row->requirement == "PLAYER_VIEWED_NODE"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which node the player needed to view in order to satisfy that requirement.\n";	
					else{
						$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of  {$scriptTitle} that doesn't specify which node the player needed to view in order to satisfy that requirement.\n";	
					}
				}
				else if($row->requirement == "PLAYER_VIEWED_NPC"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which character the player needed to view in order to satisfy that requirement.\n";	
					else{
						$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which character the player needed to view in order to satisfy that requirement.\n";	
					}
				}
				else if($row->requirement == "PLAYER_VIEWED_WEBPAGE"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which web page the player needed to view in order to satisfy that requirement.\n";	
					else{
						$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which web page the player needed to view in order to satisfy that requirement.\n";	
					}

				}
				else if($row->requirement == "PLAYER_VIEWED_AUGBUBBLE"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which panoramic the player needed to view in order to satisfy that requirement.\n";
					else{
						$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which panoramic the player needed to view in order to satisfy that requirement.\n";
					}
				}
				else if($row->requirement == "PLAYER_HAS_COMPLETED_QUEST"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which quest the player needed to complete in order to satisfy that requirement.\n";	
					else{
						$scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which quest the player needed to complete in order to satisfy that requirement.\n";	
					}
				}
				else if($row->requirement == "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK"){
					if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which incoming web hook the player needed to receive in order to satisfy that requirement.\n";	
					else{
						$scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
						return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which incoming web hook the player needed to receive in order to satisfy that requirement.\n";	
					}

				} 
			}
		}

		$query = "SELECT * FROM {$gid}_player_state_changes";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			if($row->event_type == "VIEW_ITEM"){
				if(!$row->action_detail){
					return "\nThere is an item of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
				}
				if(!$row->action_amount){
					return "\nThere is an item of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
				}
			}
			else if($row->event_type == "VIEW_NODE"){
				if(!$row->action_detail){
					$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->event_detail}");
					return "\nThere is a node with the title of {$scriptTitle} that doesn't specify what item to give or take when viewed.\n";	
				}
				if(!$row->action_amount){
					$scriptTitle = mysql_query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->event_detail}");
					return "\nThere is a node with the title of {$scriptTitle} that doesn't specify what quantity of an item to give or take when viewed.\n";	
				}
			}
			else if($row->event_type == "VIEW_NPC"){
				if(!$row->action_detail){
					return "\nThere is a character of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
				}
				if(!$row->action_amount){
					return "\nThere is a character of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
				}
			} 
			else if($row->event_type == "VIEW_WEBPAGE"){
				if(!$row->action_detail){
					return "\nThere is a web page of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
				}
				if(!$row->action_amount){
					return "\nThere is a web page of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
				}
			} 
			else if($row->event_type == "VIEW_AUGBUBBLE"){
				if(!$row->action_detail){
					return "\nThere is a panoramic of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
				}
				if(!$row->action_amount){
					return "\nThere is a panoramic of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
				}
			} 
			else if($row->event_type == "RECEIVE_WEBHOOK"){
				if(!$row->action_detail){
					return "\nThere is an web hook of id: {$row->event_detail} that doesn't specify what item to give or take when received.\n";	
				}
				if(!$row->action_amount){
					return "\nThere is an web hook of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when received.\n";	
				}
			}
		}

		$query = "SELECT * FROM node WHERE game_id = {$gid}s";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if(!$output) return "\nThere is improperly formatted xml in the node with title:\n{$row->title}\nand text:\n{$row->text}\n";
				}
			}
		}

		$query = "SELECT * FROM npcs WHERE game_id = {$gid}";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if(!$output) return "\nThere is improperly formatted xml in the npc with name:\n{$row->name}\nand greeting:\n{$row->text}\n";
				}
			}
			if($row->closing){
				$inputString = $row->closing;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if(!$output) return "\nThere is improperly formatted xml in the npc with name:\n{$row->name}\nand closing:\n{$row->text}\n";
				}
			}
		}  
	}

}
