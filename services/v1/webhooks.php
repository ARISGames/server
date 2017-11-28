<?php
require_once("module.php");

class WebHooks extends Module
{	
    public function getWebHooks($gameId)
    {
        $query = "SELECT * FROM web_hooks WHERE game_id = '{$gameId}'";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, $rsResult);
    }

    public function getWebHook($gameId, $intWebHookId)
    {
        $query = "SELECT * FROM web_hooks WHERE game_id = '{$gameId}' AND web_hook_id = '{$intWebHookId}' LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $event = @mysql_fetch_object($rsResult);
        if (!$event) return new returnData(2, NULL, "invalid quest id");

        return new returnData(0, $event);
    }

    public function createWebHook($gameId, $strName, $strURL, $boolIncoming, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strName = addslashes($strName);	
        $strURL = addslashes($strURL);	

        $query = "INSERT INTO web_hooks
            (game_id, name, url, incoming)
            VALUES ('{$gameId}', '{$strName}','{$strURL}','{$boolIncoming}')";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());
    }

    public function updateWebHook($gameId, $intWebHookId, $strName, $strURL, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strName = addslashes($strName);	
        $strURL = addslashes($strURL);	

        $query = "UPDATE web_hooks
            SET 
            name = '{$strName}',
                 url = '{$strURL}'
                     WHERE web_hook_id = '{$intWebHookId}'";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deleteWebHook($gameId, $intWebHookId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM web_hooks WHERE web_hook_id = {$intWebHookId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (!mysql_affected_rows()) return new returnData(2, NULL, 'invalid event id');

        $query = "DELETE FROM requirements WHERE game_id = {$gameId} AND content_type = 'OutgoingWebHook' AND content_id = '{$intWebHookId}'";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "{$query} SQL Error");

        $query = "DELETE FROM requirements WHERE game_id = {$gameId} AND requirement = 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK' AND requirement_detail_1 = '{$intWebHookId}'";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "{$query} SQL Error");

        return new returnData(0, TRUE);
    }	

    public function setWebHookReq($gameId, $webHookId, $lastLocationId, $playerId)
    {
        if($playerId != NULL)
            Module::processGameEvent($playerId, $gameId, "RECEIVE_WEBHOOK", $webHookId);
        else
        {
            $query = "SELECT player_id FROM player_log WHERE game_id='{$gameId}', event_detail_1='{$lastLocationId}', deleted='0'";
            $result = Module::query($query);
            while($pid = mysql_fetch_object($result))
                Module::processGameEvent($playerId, $gameId, "RECEIVE_WEBHOOK", $webHookId);
        }
    }

    public function fireOffMHSWebhook()
    {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => "index=Locusts"
            ),
        );
        $result = file_get_contents("http://156.99.108.61/cmdstart.php", false, stream_context_create($options));
    }

    public function fireOffDynamicWebHook($playerId, $gameId, $url, $data = "", $method = "GET")
    {
        if($method == "POST")
        {
            if($data != "") $data = $data."&";
            $data = $data."gameid=".$gameId."&playerid=".$playerId;
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => $data,
                ),
            );
            $result = file_get_contents("http://".$url, false, stream_context_create($options));
        }
        else
        {
            if($data == "") $data = "?";
            else            $data = $data."&";
            $url = $url.$data."gameid=".$gameId."&playerid=".$playerId;
            @file_get_contents($url);
        }
        return new returnData(0);
    }
}
?>
