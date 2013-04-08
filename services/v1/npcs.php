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
    public function getNpcs($intGameId)
    {

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM npcs WHERE game_id = {$prefix}";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);	

    }

    /**
     * Fetch a specific npc
     * @returns a single npc
     */
    public function getNpc($intGameId, $intNpcID)
    {

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM npcs WHERE game_id = {$prefix} AND npc_id = {$intNpcID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $npc = @mysql_fetch_object($rsResult);
        if (!$npc) return new returnData(2, NULL, "invalid npc id");

        return new returnData(0, $npc);		
    }


    /**
     * Fetch a specific npc with the conversation options that meet the requirements
     * @returns a single npc
     */
    public function getNpcWithConversationsForPlayer($intGameId, $intNpcID, $intPlayerID)
    {

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //get the npc
        $npcReturnData = Npcs::getNpc($intGameId, $intNpcID);
        if ($npcReturnData->returnCode > 0) return $npcReturnData;
        $npc = $npcReturnData->data;

        //get the options for this npc and player
        $conversationsReturnData = Npcs::getConversationsForPlayer($intGameId, $intNpcID, $intPlayerID);
        if ($npcReturnData->returnCode > 0) return $optionsReturnData;
        $conversationsArray = $conversationsReturnData->data;

        $npc->conversationOptions = $conversationsArray;

        return new returnData(0, $npc);

    }

    /**
     * Fetch the conversation options from a paticular npc for a player, after viewing a node 
     * @returns nm array of conversaion options
     */
    public function getNpcConversationsForPlayerAfterViewingNode($intGameId, $intNpcID, $intPlayerID, $intNodeID)
    {	
        //update the player log
        Players::nodeViewed($intGameId, $intPlayerID, $intNodeID);

        //get the options for this npc and player
        $conversationsReturnData = Npcs::getConversationsForPlayer($intGameId, $intNpcID, $intPlayerID);
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

        $query = "INSERT INTO npcs 
            (game_id, name, description, text, closing, media_id, icon_media_id)
            VALUES ({$prefix}, '{$name}', '{$description}', '{$greeting}', '{$closing}','{$mediaID}','{$iconMediaID}')";


        Module::query($query);

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

        $query = "UPDATE npcs 
            SET name = '{$name}', description = '{$description}',
                text = '{$greeting}', closing = '{$closing}', 
                media_id = '{$mediaID}', icon_media_id = '{$iconMediaID}'
                    WHERE npc_id = '{$npcID}' AND game_id = {$prefix}";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());	

        if (mysql_affected_rows()) return new returnData(0, TRUE, "");
        else return new returnData(0, FALSE, "");

    }


    /**
     * Delete a specific NPC
     * @returns a single node
     */
    public function deleteNpc($intGameId, $intNpcID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");		

        Locations::deleteLocationsForObject($intGameId, 'Npc', $intNpcID);
        Requirements::deleteRequirementsForRequirementObject($intGameId, 'Npc', $intNpcID);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($intGameId, 'Npc', $intNpcID);
        Nodes::deleteNodesReferencedByObject($intGameId, 'Npc', $intNpcID);

        $query = "DELETE FROM npcs WHERE npc_id = {$intNpcID} AND game_id = {$intGameId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $hasDeletedNPC = mysql_affected_rows();

        $query = "DELETE FROM npc_conversations WHERE npc_id = {$intNpcID} AND game_id = {$intGameId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");


        if ($hasDeletedNPC) return new returnData(0);
        else return new returnData(2, 'invalid npc id');

    }	


    /**
     * Create a conversation option for the NPC to link to a node
     * @returns the new conversationID on success
     */
    public function createConversation($intGameId, $intNpcID, $intNodeID, $strText)
    {
        $strText = addslashes($strText);	

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");		

        $query = "INSERT INTO npc_conversations 
            (game_id, npc_id, node_id, text)
            VALUES ({$prefix}, '{$intNpcID}', '{$intNodeID}', '{$strText}')";


        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());		
    }




    /**
     * Fetch the conversations for a given NPC
     * @returns a recordset of conversations
     */
    public function getConversations($intGameId, $intNpcID) {

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");		

        $query = "SELECT * FROM npc_conversations WHERE game_id = {$prefix} AND npc_id = '{$intNpcID}' ORDER BY sort_index";


        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, $rsResult);		

    }	

    /**
     * Fetch the conversations for a given NPC
     * @returns a recordset of conversations
     */
    public function getConversationsForPlayer($intGameId, $intNpcID, $intPlayerID) {

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");		


        $conversationsReturnData= Npcs::getConversations($intGameId, $intNpcID);	
        $conversations = $conversationsReturnData->data;


        $conversationsWithRequirementsMet = array();

        while ($conversation = mysql_fetch_array($conversations)) {

            if (Module::objectMeetsRequirements ($prefix, $intPlayerID, 'Node',  $conversation['node_id']) ) {
                $query = "SELECT * FROM player_log WHERE game_id = '{$intGameId}' AND player_id = '{$intPlayerID}' AND event_type = '".Module::kLOG_VIEW_NODE."' AND event_detail_1 = '".$conversation['node_id']."' AND deleted = '0'";
                $result = Module::query($query);
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
    public function updateConversation($intGameId, $intConverationID, $intNewNPC, $intNewNode, $strNewText)
    {
        $strText = addslashes($strText);	

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "UPDATE npc_conversations 
            SET npc_id = '{$intNewNPC}', node_id = '{$intNewNode}', text = '{$strNewText}'
            WHERE game_id = {$prefix} AND conversation_id = {$intConverationID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);

    }	


    /**
     * Get a list of objects that refer to the specified npc
     * @returns a list of object types and ids
     */
    public function getReferrers($intGameId, $intNpcID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //Find locations
        $query = "SELECT location_id FROM locations WHERE 
            type  = 'Npc' and type_id = {$intNpcID} AND game_id = {$prefix}";
        $rsLocations = Module::query($query);
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
    public function deleteConversation($intGameId, $intConverationID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "DELETE FROM npc_conversations WHERE game_id = {$prefix} AND conversation_id = {$intConverationID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(1, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, 'invalid conversation id');

    }	


    public function getNpcsInfoForGameIdFormattedForArisConvoOutput($intGameId)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");
        $characters = array();

        $query = "SELECT * FROM npcs WHERE game_id = {$prefix}";
        $npcs = Module::query($query);
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
            $query = "SELECT * FROM npc_conversations WHERE game_id = {$prefix} AND npc_id = '{$npc->npc_id}'";
            $convos = Module::query($query);
            if (mysql_error()) return new returnData(1, NULL, mysql_error);
            while($convo = mysql_fetch_object($convos))
            {
                $script = new stdClass();
                $script->option = $convo->text;
                $query = "SELECT * FROM nodes WHERE game_id = {$prefix} AND node_id = '{$convo->node_id}'";
                $nodeRow = Module::query($query);
                if (mysql_error()) return new returnData(1, NULL, mysql_error);
                $node = mysql_fetch_object($nodeRow);
                $script->content = $node->text;

                $requirements = array();
                $query = "SELECT * FROM requirements WHERE content_type = 'Node' AND content_id = '{$node->node_id}' AND game_id = {$prefix}";
                $reqs = Module::query($query);
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
                $query = "SELECT * FROM player_state_changes WHERE event_type = 'VIEW_NODE' AND event_detail = '{$node->node_id}' AND game_id = {$prefix}";
                $exchngs = Module::query($query);
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
