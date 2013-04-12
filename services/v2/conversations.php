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
}
