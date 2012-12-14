<?php
require_once("module.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Conversations extends Module
{	

	/**
	 * Fetch all conversations for an npc, including node information
	 *
	 * @param integer $gameId The game identifier
	 * @param integer $npcId The game identifier
	 * @return returnData
	 * @returns a returnData object containing an array of conversations with embedded node info
	 * @see returnData
	 */
	public function getConversationsWithNodeForNpc($gameId, $npcId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

                $query = "SELECT game_npc_conversations.*, game_nodes.* 
                FROM 
                (SELECT npc_conversations.*, npc_conversations.text AS conversation_text FROM npc_conversations WHERE game_id = {$prefix} AND npc_id = {$npcId}) AS game_npc_conversations 
                JOIN 
                (SELECT * FROM nodes WHERE game_id = {$prefix}) AS game_nodes 
                ON 
                game_npc_conversations.node_id = game_nodes.node_id 
                ORDER BY sort_index";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		return new returnData(0, $rsResult);	
	}

	public function swapSortIndex($gameId, $npcId, $a, $b){
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM npc_conversations WHERE game_id = {$prefix} AND npc_id = '{$npcId}' AND (conversation_id = '{$a}' OR conversation_id = '{$b}')";
		$result = mysql_query($query);
		$convos = array();
		while($convo = mysql_fetch_object($result)){
			$convos[$convo->conversation_id] = $convo;
		}

		$query = "UPDATE npc_conversations SET sort_index = '{$convos[$a]->sort_index}' WHERE game_id = '{$prefix}' AND conversation_id = '{$b}'";
		mysql_query($query);
		$query = "UPDATE npc_conversations SET sort_index = '{$convos[$b]->sort_index}' WHERE game_id = '{$prefix}' AND conversation_id = '{$a}'";
		mysql_query($query);

		return new returnData(0);
	}

	/**
	 * Create a conversation and related node for an npc
	 *
	 * @param integer $gameId The game identifier
	 * @param integer $npcId The game identifier
	 * @param string $conversationText The conversation link text as wit will appear to the player
	 * @param string $nodeText The node script

	 * @return returnData
	 * @returns a returnData object containing the newly created conversation_id and node_id
	 * @see returnData
	 */
	public function createConversationWithNode($gameId, $npcId, $conversationText, $nodeText, $index)
	{

		$conversationText = addslashes($conversationText);	
		$nodeText = addslashes($nodeText);
		$prefix = Module::getPrefix($gameId);

                $nodeText = str_replace("“", "\"", $nodeText);
                $nodeText = str_replace("”", "\"", $nodeText);

		$query = "INSERT INTO nodes (game_id, text)
			VALUES ('{$prefix}','{$nodeText}')";

		NetDebug::trace("createNode: Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		$newNodeId = mysql_insert_id();


		$query = "INSERT INTO npc_conversations (npc_id, game_id, node_id, text, sort_index)
			VALUES ('{$npcId}','{$prefix}','{$newNodeId}','{$conversationText}','{$index}')";
		NetDebug::trace("createConversation: Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		$newConversationId = mysql_insert_id();

		$ids = (object) array('conversation_id' => $newConversationId, 'node_id' => $newNodeId);

		return new returnData(0, $ids);

	}



	/**
	 * Update a conversation and related node for an npc
	 *
	 * @param integer $gameId The game identifier
	 * @param integer $conversationId The conversation identifier
	 * @param string $conversationText The conversation link text as wit will appear to the player
	 * @param string $nodeText The node script

	 * @return returnData
	 * @returns a returnData object
	 * @see returnData
	 */
	public function updateConversationWithNode($gameId, $conversationId, $conversationText, $nodeText, $index)
	{

		$conversationText = addslashes($conversationText);	
		$nodeText = addslashes($nodeText);
		$prefix = Module::getPrefix($gameId);

		$query = "SELECT node_id FROM npc_conversations WHERE game_id = '{$prefix}' AND conversation_id = {$conversationId} LIMIT 1";
		NetDebug::trace("Running a query = $query");	
		$nodeIdRs = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);			
		$nodeIdObject = @mysql_fetch_object($nodeIdRs);
		if (!$nodeIdObject) return new returnData(2, NULL, "No such conversation");			
		$nodeId = $nodeIdObject->node_id;

                $nodeText = str_replace("“", "\"", $nodeText);
                $nodeText = str_replace("”", "\"", $nodeText);

		$query = "UPDATE nodes SET text = '{$nodeText}', title = '{$conversationText}' WHERE game_id = '{$prefix}' AND node_id = {$nodeId}";
		NetDebug::trace("Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	


		$query = "UPDATE npc_conversations SET text = '{$conversationText}', sort_index = '{$index}' WHERE game_id = '{$prefix}' AND conversation_id = {$conversationId}";
		NetDebug::trace("Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	


		return new returnData(0, TRUE);

	}	


	/**
	 * Delete a specific conversation and related node
	 *
	 * @param integer $gameId The game identifier
	 * @param integer $conversationId The conversation identifier
	 *     
	 * @return returnData
	 * @returns a returnData object
	 * @see returnData
	 */	

	public function deleteConversationWithNode($gameId, $conversationId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT node_id FROM npc_conversations WHERE game_id = '{$prefix}' AND conversation_id = {$conversationId} LIMIT 1";
		NetDebug::trace("Running a query = $query");	
		$nodeIdRs = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);			
		$nodeIdObject = @mysql_fetch_object($nodeIdRs);
		if (!$nodeIdObject) return new returnData(2, NULL, "No such conversation");			
		$nodeId = $nodeIdObject->node_id;

		Nodes::deleteNode($gameId, $nodeId);

		$query = "DELETE FROM npc_conversations WHERE game_id = '{$prefix}' AND conversation_id = {$conversationId}";
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, NULL, 'invalid conversation id');
	}	
}
