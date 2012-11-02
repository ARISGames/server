<?php
require_once("module.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Nodes extends Module
{	

	/**
	 * Fetch all nodes
	 * @returns the nodes rs
	 */
	public function getNodes($intGameId)
	{
		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT game_nodes.*, game_npc_conversations.npc_id, game_npcs.name FROM (SELECT * FROM nodes WHERE game_id = '{$prefix}') AS game_nodes LEFT JOIN (SELECT * FROM npc_conversations WHERE game_id = '{$prefix}') AS game_npc_conversations ON game_nodes.node_id = game_npc_conversations.node_id LEFT JOIN (SELECT * FROM npcs WHERE game_id = '{$prefix}') AS game_npcs ON game_npc_conversations.npc_id = game_npcs.npc_id ORDER BY npc_id DESC";
		//^ Where mysql boys become mysql men 
		$rsResult = @mysql_query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);	

	}

	/**
	 * Fetch a specific nodes
	 * @returns a single node
	 */
	public function getNode($intGameId, $intNodeID)
	{
		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM nodes WHERE game_id = {$prefix} AND node_id = {$intNodeID} LIMIT 1";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		$node = mysql_fetch_object($rsResult);		
		if (!$node) return new returnData(2, NULL, "invalid node id");

		return new returnData(0, $node);

	}


	/**
	 * Create a node
	 * @returns the new nodeID on success
	 */
	public function createNode($intGameId, $strTitle, $strText, $intMediaID, $intIconMediaID,
			$strOpt1Text, $intOpt1NodeID, 
			$strOpt2Text, $intOpt2NodeID,
			$strOpt3Text, $intOpt3NodeID,
			$strQACorrectAnswer, $intQAIncorrectNodeID, $intQACorrectNodeID)
	{
		$strTitle = addslashes($strTitle);	
		$strText = addslashes($strText);	
		$strOpt1Text = addslashes($strOpt1Text);	
		$strOpt2Text = addslashes($strOpt2Text);
		$strOpt3Text = addslashes($strOpt3Text);	
		$strQACorrectAnswer = addslashes($strQACorrectAnswer);		


		$prefix = Module::getPrefix($intGameId);
		$query = "INSERT INTO nodes 
			(game_id, title, text, media_id, icon_media_id,
			 opt1_text, opt1_node_id, 
			 opt2_text, opt2_node_id, 
			 opt3_text, opt3_node_id,
			 require_answer_string, 
			 require_answer_incorrect_node_id, 
			 require_answer_correct_node_id)
			VALUES ('{$prefix}', '{$strTitle}', '{$strText}', '{$intMediaID}', '{$intIconMediaID}',
					'{$strOpt1Text}', '{$intOpt1NodeID}',
					'{$strOpt2Text}','{$intOpt2NodeID}',
					'{$strOpt3Text}','{$intOpt3NodeID}',
					'{$strQACorrectAnswer}', 
					'{$intQAIncorrectNodeID}', 
					'{$intQACorrectNodeID}')";

		NetDebug::trace("createNode: Running a query = $query");	

		@mysql_query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

		return new returnData(0, mysql_insert_id());
	}

	/**
	 * Update a specific node
	 * @returns true if a record was updated, falso if no changes were made
	 */
	public function updateNode($intGameId, $intNodeID, $strTitle, $strText, $intMediaID, $intIconMediaID,
			$strOpt1Text, $intOpt1NodeID, 
			$strOpt2Text, $intOpt2NodeID,
			$strOpt3Text, $intOpt3NodeID,
			$strQACorrectAnswer, $intQAIncorrectNodeID, $intQACorrectNodeID)
	{
		$strTitle = addslashes($strTitle);	
		$strText = addslashes($strText);	
		$strOpt1Text = addslashes($strOpt1Text);	
		$strOpt2Text = addslashes($strOpt2Text);
		$strOpt3Text = addslashes($strOpt3Text);	
		$strQACorrectAnswer = addslashes($strQACorrectAnswer);

		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");


		$query = "UPDATE nodes 
			SET title = '{$strTitle}', text = '{$strText}',
			    media_id = '{$intMediaID}', icon_media_id = '{$intIconMediaID}',
			    opt1_text = '{$strOpt1Text}', opt1_node_id = '{$intOpt1NodeID}',
			    opt2_text = '{$strOpt2Text}', opt2_node_id = '{$intOpt2NodeID}',
			    opt3_text = '{$strOpt3Text}', opt3_node_id = '{$intOpt3NodeID}',
			    require_answer_string = '{$strQACorrectAnswer}', 
			    require_answer_incorrect_node_id = '{$intQAIncorrectNodeID}', 
			    require_answer_correct_node_id = '{$intQACorrectNodeID}'
				    WHERE game_id = {$prefix} AND node_id = '{$intNodeID}'";

		NetDebug::trace("updateNode: Running a query = $query");	

		mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	


		if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
		else return new returnData(0, FALSE, "Success Running:" . $query);
	}

        public function deleteNodesReferencedByObject($intGameId, $type, $intNpcId)
        {
		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT node_id FROM npc_conversations WHERE game_id = {$intGameId} AND npc_id = {$intNpcId}";
		$result = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

                while($nid = mysql_fetch_object($result))
                    Nodes::deleteNode($intGameId, $nid->node_id);

		return new returnData(0);
        }

	/**
	 * Delete a specific nodes
	 * @returns returnCode 0 if successfull
	 */
	public function deleteNode($intGameId, $intNodeID)
	{
		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		Locations::deleteLocationsForObject($intGameId, 'Node', $intNodeID);
		Requirements::deleteRequirementsForRequirementObject($intGameId, 'Node', $intNodeID);
		PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($intGameId, 'Node', $intNodeID);


		$query = "DELETE FROM nodes WHERE game_id = {$prefix} AND node_id = {$intNodeID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, NULL, 'invalid node id');
	}	


	/**
	 * Get a list of objects that refer to the specified node
	 * @returns a list of object types and ids
	 */
	public function getReferrers($intGameId, $intNodeID)
	{
		$prefix = Module::getPrefix($intGameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Find locations
		$query = "SELECT location_id FROM locations WHERE 
			type  = 'Node' AND type_id = {$intNodeID} AND game_id = '{$prefix}'";
		$rsLocations = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error in Locations query");

		//Find Nodes
		$query = "SELECT node_id FROM nodes WHERE
                        game_id = '{$prefix}' AND
			(opt1_node_id  = {$intNodeID} or
			opt2_node_id  = {$intNodeID} or
			opt3_node_id  = {$intNodeID})";
		$rsNodes = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error in Nodes Query");


		//Find NPCs
		$query = "SELECT game_npcs.npc_id FROM (SELECT * FROM npcs WHERE game_id = '{$prefix}') AS game_npcs 
			JOIN (SELECT * FROM npc_conversations WHERE game_id = '{$prefix}') AS game_npc_conversations
			ON game_npcs.npc_id = game_npc_conversations.npc_id
			WHERE game_npc_conversations.node_id = {$intNodeID}";
		NetDebug::trace($query);			
		$rsNpcs = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error in NPC Query");



		//Combine them together
		$referrers = array();
		while ($row = mysql_fetch_array($rsLocations)){
			$referrers[] = array('type'=>'Location', 'id' => $row['location_id']);
		}
		while ($row = mysql_fetch_array($rsNodes)){
			$referrers[] = array('type'=>'Node', 'id' => $row['node_id']);
		}
		while ($row = mysql_fetch_array($rsNpcs)){
			$referrers[] = array('type'=>'Npc', 'id' => $row['npc_id']);
		}

		return new returnData(0,$referrers);
	}	



}
