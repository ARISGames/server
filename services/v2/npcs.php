<?php
require_once("module.php");
require_once("players.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Npcs extends Module
{
    public function getNpcs($gameId)
    {
        $query = "SELECT * FROM npcs WHERE game_id = {$gameId}";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);	
    }

    public function getNpc($gameId, $intNpcID)
    {
        $query = "SELECT * FROM npcs WHERE game_id = {$gameId} AND npc_id = {$intNpcID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $npc = @mysql_fetch_object($rsResult);
        if (!$npc) return new returnData(2, NULL, "invalid npc id");

        return new returnData(0, $npc);		
    }

    public function getNpcWithConversationsForPlayer($gameId, $intNpcID, $intPlayerID)
    {
        //get the npc
        $npcReturnData = Npcs::getNpc($gameId, $intNpcID);
        if ($npcReturnData->returnCode > 0) return $npcReturnData;
        $npc = $npcReturnData->data;

        //get the options for this npc and player
        $conversationsReturnData = Npcs::getConversationsForPlayer($gameId, $intNpcID, $intPlayerID);
        if ($npcReturnData->returnCode > 0) return $optionsReturnData;
        $conversationsArray = $conversationsReturnData->data;

        $npc->conversationOptions = $conversationsArray;

        return new returnData(0, $npc);
    }

    public function getNpcConversationsForPlayerAfterViewingNode($gameId, $intNpcID, $intPlayerID, $intNodeID)
    {	
        //update the player log
        Players::nodeViewed($gameId, $intPlayerID, $intNodeID);

        //get the options for this npc and player
        $conversationsReturnData = Npcs::getConversationsForPlayer($gameId, $intNpcID, $intPlayerID);
        if ($npcReturnData->returnCode > 0) return $optionsReturnData;
        $conversationsArray = $conversationsReturnData->data;

        return new returnData(0, $conversationsArray);	
    }

    public function createNpc($gameId, $name, $description, $greeting, $closing, $mediaID, $iconMediaID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $greeting = str_replace("“", "\"", $greeting);
        $greeting = str_replace("”", "\"", $greeting);
        $closing = str_replace("“", "\"", $closing);
        $closing = str_replace("”", "\"", $closing);
        $name = addslashes($name);	
        $description = addslashes($description);	
        $greeting = addslashes($greeting);	
        $closing = addslashes($closing);

        $query = "INSERT INTO npcs 
            (game_id, name, description, text, closing, media_id, icon_media_id)
            VALUES ({$gameId}, '{$name}', '{$description}', '{$greeting}', '{$closing}','{$mediaID}','{$iconMediaID}')";


        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
        return new returnData(0, mysql_insert_id());		
    }

    public function updateNpc($gameId, $npcID, 
            $name, $description, $greeting, $closing, $mediaID, $iconMediaID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $greeting = str_replace("“", "\"", $greeting);
        $greeting = str_replace("”", "\"", $greeting);
        $closing = str_replace("“", "\"", $closing);
        $closing = str_replace("”", "\"", $closing);
        $name = addslashes($name);	
        $description = addslashes($description);	
        $greeting = addslashes($greeting);			
        $closing = addslashes($closing);			

        $query = "UPDATE npcs 
            SET name = '{$name}', description = '{$description}',
                text = '{$greeting}', closing = '{$closing}', 
                media_id = '{$mediaID}', icon_media_id = '{$iconMediaID}'
                    WHERE npc_id = '{$npcID}' AND game_id = {$gameId}";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());	

        if (mysql_affected_rows()) return new returnData(0, TRUE, "");
        else return new returnData(0, FALSE, "");
    }

    public function deleteNpc($gameId, $intNpcID)
    {
        Locations::deleteLocationsForObject($gameId, 'Npc', $intNpcID);
        Requirements::deleteRequirementsForRequirementObject($gameId, 'Npc', $intNpcID);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'Npc', $intNpcID);
        Nodes::deleteNodesReferencedByObject($gameId, 'Npc', $intNpcID);

        $query = "DELETE FROM npcs WHERE npc_id = {$intNpcID} AND game_id = {$gameId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $hasDeletedNPC = mysql_affected_rows();

        $query = "DELETE FROM npc_conversations WHERE npc_id = {$intNpcID} AND game_id = {$gameId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");


        if ($hasDeletedNPC) return new returnData(0);
        else return new returnData(2, 'invalid npc id');

    }	

    public function createConversation($gameId, $intNpcID, $intNodeID, $strText)
    {
        $strText = addslashes($strText);	

        $query = "INSERT INTO npc_conversations 
            (game_id, npc_id, node_id, text)
            VALUES ({$gameId}, '{$intNpcID}', '{$intNodeID}', '{$strText}')";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());		
    }

    public function getConversations($gameId, $intNpcID)
    {
        $query = "SELECT * FROM npc_conversations WHERE game_id = {$gameId} AND npc_id = '{$intNpcID}' ORDER BY sort_index";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, $rsResult);		
    }	

    public function getConversationsForPlayer($gameId, $intNpcID, $intPlayerID)
    {
        $conversationsReturnData= Npcs::getConversations($gameId, $intNpcID);	
        $conversations = $conversationsReturnData->data;

        $conversationsWithRequirementsMet = array();

        while ($conversation = mysql_fetch_array($conversations))
        {
            if (Module::objectMeetsRequirements ($gameId, $intPlayerID, 'Node',  $conversation['node_id']) ) {
                $query = "SELECT * FROM player_log WHERE game_id = '{$gameId}' AND player_id = '{$intPlayerID}' AND event_type = '".Module::kLOG_VIEW_NODE."' AND event_detail_1 = '".$conversation['node_id']."' AND deleted = '0'";
                $result = Module::query($query);
                if(mysql_num_rows($result) > 0) $conversation['has_viewed'] = true;
                else $conversation['has_viewed'] = false;
                $conversationsWithRequirementsMet[] = $conversation;
            }
        }

        return new returnData(0, $conversationsWithRequirementsMet);
    }	

    public function updateConversation($gameId, $intConverationID, $intNewNPC, $intNewNode, $strNewText)
    {
        $strText = addslashes($strText);	

        $query = "UPDATE npc_conversations 
            SET npc_id = '{$intNewNPC}', node_id = '{$intNewNode}', text = '{$strNewText}'
            WHERE game_id = {$gameId} AND conversation_id = {$intConverationID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }	

    public function getReferrers($gameId, $intNpcID)
    {
        //Find locations
        $query = "SELECT location_id FROM locations WHERE 
            type  = 'Npc' and type_id = {$intNpcID} AND game_id = {$gameId}";
        $rsLocations = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error in Locations query");

        $referrers = array();
        while ($row = mysql_fetch_array($rsLocations)){
            $referrers[] = array('type'=>'Location', 'id' => $row['location_id']);
        }

        return new returnData(0,$referrers);
    }	

    public function deleteConversation($gameId, $intConverationID)
    {
        $query = "DELETE FROM npc_conversations WHERE game_id = {$gameId} AND conversation_id = {$intConverationID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(1, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, 'invalid conversation id');
    }	

    public function getNpcsInfoForGameIdFormattedForArisConvoOutput($gameId)
    {
        $characters = array();

        $query = "SELECT * FROM npcs WHERE game_id = {$gameId}";
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
            $query = "SELECT * FROM npc_conversations WHERE game_id = {$gameId} AND npc_id = '{$npc->npc_id}'";
            $convos = Module::query($query);
            if (mysql_error()) return new returnData(1, NULL, mysql_error);
            while($convo = mysql_fetch_object($convos))
            {
                $script = new stdClass();
                $script->option = $convo->text;
                $query = "SELECT * FROM nodes WHERE game_id = {$gameId} AND node_id = '{$convo->node_id}'";
                $nodeRow = Module::query($query);
                if (mysql_error()) return new returnData(1, NULL, mysql_error);
                $node = mysql_fetch_object($nodeRow);
                $script->content = $node->text;

                $requirements = array();
                $query = "SELECT * FROM requirements WHERE content_type = 'Node' AND content_id = '{$node->node_id}' AND game_id = {$gameId}";
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
                $query = "SELECT * FROM player_state_changes WHERE event_type = 'VIEW_NODE' AND event_detail = '{$node->node_id}' AND game_id = {$gameId}";
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
