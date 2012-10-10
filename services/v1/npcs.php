<?php
require_once("module.php");
require_once("players.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Npcs extends Module
{

	/**
	 * Fetch all Npcs
	 * @returns the npc rs
	 */
	public function getNpcs($intGameID)
	{

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_npcs";

		$rsResult = @mysql_query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);	

	}

	/**
	 * Fetch a specific npc
	 * @returns a single npc
	 */
	public function getNpc($intGameID, $intNpcID)
	{

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_npcs WHERE npc_id = {$intNpcID} LIMIT 1";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		$npc = @mysql_fetch_object($rsResult);
		if (!$npc) return new returnData(2, NULL, "invalid npc id");

		return new returnData(0, $npc);		
	}


	/**
	 * Fetch a specific npc with the conversation options that meet the requirements
	 * @returns a single npc
	 */
	public function getNpcWithConversationsForPlayer($intGameID, $intNpcID, $intPlayerID)
	{

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//get the npc
		$npcReturnData = Npcs::getNpc($intGameID, $intNpcID);
		if ($npcReturnData->returnCode > 0) return $npcReturnData;
		$npc = $npcReturnData->data;

		//get the options for this npc and player
		$conversationsReturnData = Npcs::getConversationsForPlayer($intGameID, $intNpcID, $intPlayerID);
		if ($npcReturnData->returnCode > 0) return $optionsReturnData;
		$conversationsArray = $conversationsReturnData->data;

		$npc->conversationOptions = $conversationsArray;

		return new returnData(0, $npc);

	}

	/**
	 * Fetch the conversation options from a paticular npc for a player, after viewing a node 
	 * @returns nm array of conversaion options
	 */
	public function getNpcConversationsForPlayerAfterViewingNode($intGameID, $intNpcID, $intPlayerID, $intNodeID)
	{	
		//update the player log
		Players::nodeViewed($intGameID, $intPlayerID, $intNodeID);

		//get the options for this npc and player
		$conversationsReturnData = Npcs::getConversationsForPlayer($intGameID, $intNpcID, $intPlayerID);
		if ($npcReturnData->returnCode > 0) return $optionsReturnData;
		$conversationsArray = $conversationsReturnData->data;

		return new returnData(0, $conversationsArray);	
	}

	/**
	 * Create a new NPC
	 *
	 * @param integer $gameID The game identifier
	 * @param string $name The NPC's name
	 * @param string $description Authoring notes
	 * @param string $greeting The script that plays when the charecter is greeted
	 * @param string $closing The script that plays when no conversations remain
	 * @param integer $mediaID The image media for the NPC
	 * @param integer $iconMediaID The icon image media for the NPC 
	 * @return returnData
	 * @returns a returnData object containing the NpcID of the newly created NPC in the data
	 * @see returnData
	 */
	public function createNpc($gameID, $name, $description, $greeting, $closing, $mediaID, $iconMediaID)
	{
                $greeting = str_replace("“", "\"", $greeting);
                $greeting = str_replace("”", "\"", $greeting);
                $closing = str_replace("“", "\"", $closing);
                $closing = str_replace("”", "\"", $closing);
		$name = addslashes($name);	
		$description = addslashes($description);	
		$greeting = addslashes($greeting);	
		$closing = addslashes($closing);

		$prefix = Module::getPrefix($gameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO {$prefix}_npcs 
			(name, description, text, closing, media_id, icon_media_id)
			VALUES ('{$name}', '{$description}', '{$greeting}', '{$closing}','{$mediaID}','{$iconMediaID}')";

		NetDebug::trace("createNpc: Running a query = $query");	

		@mysql_query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		return new returnData(0, mysql_insert_id());		
	}



	/**
	 * Update an NPC
	 *
	 * @param integer $gameID The game identifier
	 * @param integer $npcID The NPC identifier	 
	 * @param string $name The NPC's new name
	 * @param string $description new Authoring notes
	 * @param string $greeting The new script that plays when the charecter is greeted
	 * @param string $closing The new script that plays when no conversations remain
	 * @param integer $mediaID The new image media for the NPC
	 * @param integer $iconMediaID The new icon image media for the NPC 
	 * @return returnData
	 * @returns a returnData object containing TRUE if the NPC was changed, FALSE otherwise
	 * @see returnData
	 */
	public function updateNpc($gameID, $npcID, 
			$name, $description, $greeting, $closing, $mediaID, $iconMediaID)
	{
                $greeting = str_replace("“", "\"", $greeting);
                $greeting = str_replace("”", "\"", $greeting);
                $closing = str_replace("“", "\"", $closing);
                $closing = str_replace("”", "\"", $closing);
		$name = addslashes($name);	
		$description = addslashes($description);	
		$greeting = addslashes($greeting);			
		$closing = addslashes($closing);			

		$prefix = Module::getPrefix($gameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		$query = "UPDATE {$prefix}_npcs 
			SET name = '{$name}', description = '{$description}',
			    text = '{$greeting}', closing = '{$closing}', 
			    media_id = '{$mediaID}', icon_media_id = '{$iconMediaID}'
				    WHERE npc_id = '{$npcID}'";

		NetDebug::trace("updateNpc: Running a query = $query");	

		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());	

		if (mysql_affected_rows()) return new returnData(0, TRUE, "");
		else return new returnData(0, FALSE, "");

	}


	/**
	 * Delete a specific NPC
	 * @returns a single node
	 */
	public function deleteNpc($intGameID, $intNpcID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		Locations::deleteLocationsForObject($intGameID, 'Npc', $intNpcID);
		Requirements::deleteRequirementsForRequirementObject($intGameID, 'Npc', $intNpcID);
		PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($intGameID, 'Npc', $intNpcID);
                Nodes::deleteNodesReferencedByObject($intGameID, 'Npc', $intNpcID);

		$query = "DELETE FROM {$prefix}_npcs WHERE npc_id = {$intNpcID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, 'invalid npc id');

	}	


	/**
	 * Create a conversation option for the NPC to link to a node
	 * @returns the new conversationID on success
	 */
	public function createConversation($intGameID, $intNpcID, $intNodeID, $strText)
	{
		$strText = addslashes($strText);	

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		$query = "INSERT INTO {$prefix}_npc_conversations 
			(npc_id, node_id, text)
			VALUES ('{$intNpcID}', '{$intNodeID}', '{$strText}')";

		NetDebug::trace("createConversation: Running a query = $query");	

		@mysql_query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		return new returnData(0, mysql_insert_id());		
	}




	/**
	 * Fetch the conversations for a given NPC
	 * @returns a recordset of conversations
	 */
	public function getConversations($intGameID, $intNpcID) {

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		$query = "SELECT * FROM {$prefix}_npc_conversations WHERE npc_id = '{$intNpcID}' ORDER BY sort_index";

		NetDebug::trace("getConversations: Running a query = $query");	

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		return new returnData(0, $rsResult);		

	}	

	/**
	 * Fetch the conversations for a given NPC
	 * @returns a recordset of conversations
	 */
	public function getConversationsForPlayer($intGameID, $intNpcID, $intPlayerID) {

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		NetDebug::trace("getConversationsForPlayer beginning");	

		$conversationsReturnData= Npcs::getConversations($intGameID, $intNpcID);	
		$conversations = $conversationsReturnData->data;


		$conversationsWithRequirementsMet = array();

		while ($conversation = mysql_fetch_array($conversations)) {
			NetDebug::trace("Testing Conversation {$conversation['conversation_id']}");	

			if (Module::objectMeetsRequirements ($prefix, $intPlayerID, 'Node',  $conversation['node_id']) ) {
				$query = "SELECT * FROM player_log WHERE game_id = '{$intGameID}' AND player_id = '{$intPlayerID}' AND event_type = '".Module::kLOG_VIEW_NODE."' AND event_detail_1 = '".$conversation['node_id']."' AND deleted = '0'";
				$result = mysql_query($query);
				if(mysql_num_rows($result) > 0) $conversation['has_viewed'] = true;
				else $conversation['has_viewed'] = false;
				$conversationsWithRequirementsMet[] = $conversation;
			}
		}

		return new returnData(0, $conversationsWithRequirementsMet);

	}	

	/**
	 * Update Conversation
	 * @returns true if a record was updated, false if no changes were made (could be becasue conversation id is invalid)
	 */
	public function updateConversation($intGameID, $intConverationID, $intNewNPC, $intNewNode, $strNewText)
	{
		$strText = addslashes($strText);	

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE {$prefix}_npc_conversations 
			SET npc_id = '{$intNewNPC}', node_id = '{$intNewNode}', text = '{$strNewText}'
			WHERE conversation_id = {$intConverationID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);

	}	


	/**
	 * Get a list of objects that refer to the specified npc
	 * @returns a list of object types and ids
	 */
	public function getReferrers($intGameID, $intNpcID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Find locations
		$query = "SELECT location_id FROM {$prefix}_locations WHERE 
			type  = 'Npc' and type_id = {$intNpcID}";
		$rsLocations = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error in Locations query");

		$referrers = array();
		while ($row = mysql_fetch_array($rsLocations)){
			$referrers[] = array('type'=>'Location', 'id' => $row['location_id']);
		}


		return new returnData(0,$referrers);
	}	



	/**
	 * Delete a specific NPC Conversation option
	 * @returns true on success
	 */
	public function deleteConversation($intGameID, $intConverationID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "DELETE FROM {$prefix}_npc_conversations WHERE conversation_id = {$intConverationID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, 'invalid conversation id');

	}	


	public function getNpcsInfoForGameIdFormattedForArisConvoOutput($intGameId)
	{
		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		$characters = array();

		$query = "SELECT * FROM {$prefix}_npcs";
		$npcs = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error);

		while($npc = mysql_fetch_object($npcs))
		{
			$character = new stdClass();
			$character->name = $npc->name;
			$scripts = array();

			//Greeting
			$script = new stdClass();
			$script->option = "Greeting";
			$script->content = $npc->text;
			$script->req = "(start conversation)";
			$script->exchange = "n/a";
			$scripts[] = $script;

			//Convos
			$query = "SELECT * FROM {$prefix}_npc_conversations WHERE npc_id = '{$npc->npc_id}'";
			$convos = @mysql_query($query);
			if (mysql_error()) return new returnData(1, NULL, mysql_error);
			while($convo = mysql_fetch_object($convos))
			{
				$script = new stdClass();
				$script->option = $convo->text;
				$query = "SELECT * FROM {$prefix}_nodes WHERE node_id = '{$convo->node_id}'";
				$nodeRow = @mysql_query($query);
				if (mysql_error()) return new returnData(1, NULL, mysql_error);
				$node = mysql_fetch_object($nodeRow);
				$script->content = $node->text;

				$requirements = array();
				$query = "SELECT * FROM {$prefix}_requirements WHERE content_type = 'Node' AND content_id = '{$node->node_id}'";
				$reqs = @mysql_query($query);
				if (mysql_error()) return new returnData(1, NULL, mysql_error);
				while($reqObj = mysql_fetch_object($reqs))
				{
					$req = new stdClass();
					$req->requirement = $reqObj->requirement;
					$req->boole = $reqObj->boolean_operator;
					$req->rDetail1 = $reqObj->requirement_detail_1;
					$req->rDetail2 = $reqObj->requirement_detail_2;
					$req->rDetail3 = $reqObj->requirement_detail_3;
					$requirements[] = $req;
				}
				$script->req = $requirements;

				$exchanges = array();
				$query = "SELECT * FROM {$prefix}_player_state_changes WHERE event_type = 'VIEW_NODE' AND event_detail = '{$node->node_id}'";
				$exchngs = @mysql_query($query);
				if (mysql_error()) return new returnData(1, NULL, mysql_error);
				while($exchangeObj = mysql_fetch_object($exchngs))
				{
					$exchange = new stdClass();
					$exchange->action = $exchangeObj->action;
					$exchange->obj = $exchangeObj->action_detail;
					$exchange->amount = $exchangeObj->action_amount;
					$exchanges[] = $exchange;
				}
				$script->exchange = $exchanges;
				$scripts[] = $script;
			}

			//Closing
			$script = new stdClass();
			$script->option = "Closing";
			$script->content = $npc->closing;
			$script->req = "(end conversation)";
			$script->exchange = "n/a";
			$scripts[] = $script;

			$character->scripts = $scripts;
			$characters[] = $character;
		}

		return $characters;
	}
}
