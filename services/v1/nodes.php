<?php
require_once("module.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Nodes extends Module
{	
    public function getNodes($gameId)
    {
        $query = "SELECT game_nodes.*, game_npc_conversations.npc_id, game_npcs.name FROM (SELECT * FROM nodes WHERE game_id = '{$gameId}') AS game_nodes LEFT JOIN (SELECT * FROM npc_conversations WHERE game_id = '{$gameId}') AS game_npc_conversations ON game_nodes.node_id = game_npc_conversations.node_id LEFT JOIN (SELECT * FROM npcs WHERE game_id = '{$gameId}') AS game_npcs ON game_npc_conversations.npc_id = game_npcs.npc_id ORDER BY npc_id DESC";
        //^ Where mysql boys become mysql men //calm down- that's literally just two joins.
        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);	

    }

    public function getNode($gameId, $intNodeId)
    {
        $query = "SELECT * FROM nodes WHERE game_id = {$gameId} AND node_id = {$intNodeId} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $node = mysql_fetch_object($rsResult);		
        if (!$node) return new returnData(2, NULL, "invalid node id");

        return new returnData(0, $node);
    }

    public function createNode($gameId, $strTitle, $strText, $intMediaId, $intIconMediaId,
            $strOpt1Text, $intOpt1NodeId, 
            $strOpt2Text, $intOpt2NodeId,
            $strOpt3Text, $intOpt3NodeId,
            $strQACorrectAnswer, $intQAIncorrectNodeId, $intQACorrectNodeId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strTitle = addslashes($strTitle);	
        $strText = addslashes($strText);	
        $strOpt1Text = addslashes($strOpt1Text);	
        $strOpt2Text = addslashes($strOpt2Text);
        $strOpt3Text = addslashes($strOpt3Text);	
        $strQACorrectAnswer = addslashes($strQACorrectAnswer);		

        $query = "INSERT INTO nodes 
            (game_id, title, text, media_id, icon_media_id,
             opt1_text, opt1_node_id, 
             opt2_text, opt2_node_id, 
             opt3_text, opt3_node_id,
             require_answer_string, 
             require_answer_incorrect_node_id, 
             require_answer_correct_node_id)
            VALUES ('{$gameId}', '{$strTitle}', '{$strText}', '{$intMediaId}', '{$intIconMediaId}',
                    '{$strOpt1Text}', '{$intOpt1NodeId}',
                    '{$strOpt2Text}','{$intOpt2NodeId}',
                    '{$strOpt3Text}','{$intOpt3NodeId}',
                    '{$strQACorrectAnswer}', 
                    '{$intQAIncorrectNodeId}', 
                    '{$intQACorrectNodeId}')";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

        return new returnData(0, mysql_insert_id());
    }

    public function updateNode($gameId, $intNodeId, $strTitle, $strText, $intMediaId, $intIconMediaId,
            $strOpt1Text, $intOpt1NodeId, 
            $strOpt2Text, $intOpt2NodeId,
            $strOpt3Text, $intOpt3NodeId,
            $strQACorrectAnswer, $intQAIncorrectNodeId, $intQACorrectNodeId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strTitle = addslashes($strTitle);	
        $strText = addslashes($strText);	
        $strOpt1Text = addslashes($strOpt1Text);	
        $strOpt2Text = addslashes($strOpt2Text);
        $strOpt3Text = addslashes($strOpt3Text);	
        $strQACorrectAnswer = addslashes($strQACorrectAnswer);


        $query = "UPDATE nodes 
            SET title = '{$strTitle}', text = '{$strText}',
                media_id = '{$intMediaId}', icon_media_id = '{$intIconMediaId}',
                opt1_text = '{$strOpt1Text}', opt1_node_id = '{$intOpt1NodeId}',
                opt2_text = '{$strOpt2Text}', opt2_node_id = '{$intOpt2NodeId}',
                opt3_text = '{$strOpt3Text}', opt3_node_id = '{$intOpt3NodeId}',
                require_answer_string = '{$strQACorrectAnswer}', 
                require_answer_incorrect_node_id = '{$intQAIncorrectNodeId}', 
                require_answer_correct_node_id = '{$intQACorrectNodeId}'
                    WHERE game_id = {$gameId} AND node_id = '{$intNodeId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	

        if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
        else return new returnData(0, FALSE, "Success Running:" . $query);
    }

    public function deleteNodesReferencedByObject($gameId, $type, $intNpcId)
    {
        $query = "SELECT node_id FROM npc_conversations WHERE game_id = {$gameId} AND npc_id = {$intNpcId}";
        $result = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        while($nid = mysql_fetch_object($result))
            Nodes::deleteNode($gameId, $nid->node_id);

        return new returnData(0);
    }

    public function deleteNode($gameId, $intNodeId)
    {
        Locations::deleteLocationsForObject($gameId, 'Node', $intNodeId);
        Requirements::deleteRequirementsForRequirementObject($gameId, 'Node', $intNodeId);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'Node', $intNodeId);

        $query = "DELETE FROM nodes WHERE game_id = {$gameId} AND node_id = {$intNodeId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, NULL, 'invalid node id');
    }	
}
