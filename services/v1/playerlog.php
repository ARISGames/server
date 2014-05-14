<?php
require_once("module.php");
require_once("media.php");

/*
+----------------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+------+-----+-------------------+----------------+
| Field          | Type                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Null | Key | Default           | Extra          |
+----------------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+------+-----+-------------------+----------------+
| id             | int(10) unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                           | NO   | PRI | NULL              | auto_increment |
| player_id      | int(10) unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                           | NO   | MUL | NULL              |                |
| game_id        | int(10) unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                           | NO   | MUL | 0                 |                |
| timestamp      | timestamp                                                                                                                                                                                                                                                                                                                                                                                                                                                  | NO   | MUL | CURRENT_TIMESTAMP |                |
| event_type     | enum('COMPLETE_QUEST') | YES  | MUL | NULL              |                |
| event_detail_1 | varchar(50)                                                                                                                                                                                                                                                                                                                                                                                                                                                | YES  |     | NULL              |                |
| event_detail_2 | varchar(50)                                                                                                                                                                                                                                                                                                                                                                                                                                                | YES  |     | NULL              |                |
| event_detail_3 | varchar(50)                                                                                                                                                                                                                                                                                                                                                                                                                                                | YES  |     | NULL              |                |
| deleted        | tinyint(1)                                                                                                                                                                                                                                                                                                                                                                                                                                                 | NO   | MUL | 0                 |                |
+----------------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+------+-----+-------------------+----------------+

+--------------------+----------------------------------------------------------------------+------+-----+-------------------+----------------+
| Field              | Type                                                                 | Null | Key | Default           | Extra          |
+--------------------+----------------------------------------------------------------------+------+-----+-------------------+----------------+
| location_id        | int(11)                                                              | NO   | PRI | NULL              | auto_increment |
| game_id            | int(11)                                                              | NO   | MUL | NULL              |                |
| name               | varchar(255)                                                         | NO   |     | NULL              |                |
| description        | tinytext                                                             | NO   |     | NULL              |                |
| latitude           | double                                                               | NO   |     | 43.0746561        |                |
| longitude          | double                                                               | NO   |     | -89.384422        |                |
| error              | double                                                               | NO   |     | 5                 |                |
| type               | enum('Node','Event','Item','Npc','WebPage','AugBubble','PlayerNote') | NO   |     | Node              |                |
| type_id            | int(11)                                                              | NO   |     | NULL              |                |
| icon_media_id      | int(10) unsigned                                                     | NO   |     | 0                 |                |
| item_qty           | int(11)                                                              | NO   |     | 0                 |                |
| hidden             | enum('0','1')                                                        | NO   |     | 0                 |                |
| force_view         | enum('0','1')                                                        | NO   |     | 0                 |                |
| allow_quick_travel | enum('0','1')                                                        | NO   |     | 0                 |                |
| wiggle             | tinyint(1)                                                           | NO   |     | 0                 |                |
| show_title         | tinyint(1)                                                           | NO   |     | 0                 |                |
| spawnstamp         | timestamp                                                            | NO   |     | CURRENT_TIMESTAMP |                |
+--------------------+----------------------------------------------------------------------+------+-----+-------------------+----------------+

+----------------+------------------+------+-----+------------------------------------------------------------------------+----------------+
| Field          | Type             | Null | Key | Default                                                                | Extra          |
+----------------+------------------+------+-----+------------------------------------------------------------------------+----------------+
| qrcode_id      | int(11)          | NO   | PRI | NULL                                                                   | auto_increment |
| game_id        | int(11)          | NO   | MUL | NULL                                                                   |                |
| link_type      | enum('Location') | NO   |     | Location                                                               |                |
| link_id        | int(11)          | NO   |     | NULL                                                                   |                |
| code           | varchar(255)     | NO   |     | NULL                                                                   |                |
| match_media_id | int(10) unsigned | NO   |     | 0                                                                      |                |
| fail_text      | varchar(256)     | NO   |     | This code doesn't mean anything right now. You should come back later. |                |
+----------------+------------------+------+-----+------------------------------------------------------------------------+----------------+

+--------------+---------------------+------+-----+---------------------+-----------------------------+
| Field        | Type                | Null | Key | Default             | Extra                       |
+--------------+---------------------+------+-----+---------------------+-----------------------------+
| player_id    | int(11) unsigned    | NO   | PRI | NULL                | auto_increment              |
| first_name   | varchar(25)         | YES  |     | NULL                |                             |
| last_name    | varchar(25)         | YES  |     | NULL                |                             |
| email        | varchar(50)         | YES  |     | NULL                |                             |
| media_id     | int(25) unsigned    | NO   |     | 0                   |                             |
| password     | varchar(32)         | YES  |     | NULL                |                             |
| user_name    | varchar(30)         | NO   |     | NULL                |                             |
| latitude     | double              | NO   | MUL | 0                   |                             |
| longitude    | double              | NO   |     | 0                   |                             |
| last_game_id | int(10) unsigned    | NO   | MUL | 0                   |                             |
| created      | timestamp           | NO   | MUL | 0000-00-00 00:00:00 |                             |
| updated      | timestamp           | NO   |     | CURRENT_TIMESTAMP   | on update CURRENT_TIMESTAMP |
| show_on_map  | tinyint(4)          | NO   |     | 1                   |                             |
| display_name | varchar(32)         | NO   |     |                     |                             |
| group_name   | varchar(32)         | NO   |     |                     |                             |
| curator      | tinyint(1) unsigned | NO   |     | 0                   |                             |
| facebook_id  | int(11) unsigned    | NO   |     | 0                   |                             |
+--------------+---------------------+------+-----+---------------------+-----------------------------+

+---------------------------------+-----------------------------------------------------------------------------------------------+------+-----+---------+----------------+
| Field                           | Type                                                                                          | Null | Key | Default | Extra          |
+---------------------------------+-----------------------------------------------------------------------------------------------+------+-----+---------+----------------+
| quest_id                        | int(11) unsigned                                                                              | NO   | PRI | NULL    | auto_increment |
| game_id                         | int(11)                                                                                       | NO   | MUL | NULL    |                |
| name                            | tinytext                                                                                      | NO   |     | NULL    |                |
| description                     | text                                                                                          | NO   |     | NULL    |                |
| text_when_complete              | tinytext                                                                                      | NO   |     | NULL    |                |
| sort_index                      | int(10) unsigned                                                                              | NO   |     | 0       |                |
| active_media_id                 | int(10) unsigned                                                                              | NO   |     | 0       |                |
| complete_media_id               | int(10) unsigned                                                                              | NO   |     | 0       |                |
| full_screen_notify              | tinyint(1)                                                                                    | NO   |     | 1       |                |
| active_icon_media_id            | int(10) unsigned                                                                              | NO   |     | 0       |                |
| complete_icon_media_id          | int(10) unsigned                                                                              | NO   |     | 0       |                |
| go_function                     | enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') | NO   |     | NONE    |                |
| description_notification        | tinytext                                                                                      | NO   |     | NULL    |                |
| text_when_complete_notification | tinytext                                                                                      | NO   |     | NULL    |                |
| active_notification_media_id    | int(10) unsigned                                                                              | NO   |     | 0       |                |
| complete_notification_media_id  | int(10) unsigned                                                                              | NO   |     | 0       |                |
| complete_go_function            | enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') | NO   |     | NONE    |                |
| complete_full_screen_notify     | tinyint(1)                                                                                    | NO   |     | 1       |                |
| active_notif_show_dismiss       | tinyint(1)                                                                                    | NO   |     | 1       |                |
| complete_notif_show_dismiss     | tinyint(1)                                                                                    | NO   |     | 1       |                |
| notif_go_function               | enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') | NO   |     | NONE    |                |
| complete_notif_go_function      | enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') | NO   |     | NONE    |                |
+---------------------------------+-----------------------------------------------------------------------------------------------+------+-----+---------+----------------+

+----------------------+--------------------------------------+------+-----+-------------------+-----------------------------+
| Field                | Type                                 | Null | Key | Default           | Extra                       |
+----------------------+--------------------------------------+------+-----+-------------------+-----------------------------+
| item_id              | int(11) unsigned                     | NO   | PRI | NULL              | auto_increment              |
| game_id              | int(11)                              | NO   | MUL | NULL              |                             |
| name                 | varchar(255)                         | NO   |     | NULL              |                             |
| description          | text                                 | NO   |     | NULL              |                             |
| is_attribute         | enum('0','1')                        | NO   |     | 0                 |                             |
| icon_media_id        | int(10) unsigned                     | NO   |     | 0                 |                             |
| media_id             | int(10) unsigned                     | NO   |     | 0                 |                             |
| dropable             | enum('0','1')                        | NO   |     | 0                 |                             |
| destroyable          | enum('0','1')                        | NO   |     | 0                 |                             |
| max_qty_in_inventory | int(11)                              | NO   |     | -1                |                             |
| creator_player_id    | int(10) unsigned                     | NO   |     | 0                 |                             |
| origin_latitude      | double                               | NO   |     | 0                 |                             |
| origin_longitude     | double                               | NO   |     | 0                 |                             |
| origin_timestamp     | timestamp                            | NO   |     | CURRENT_TIMESTAMP | on update CURRENT_TIMESTAMP |
| weight               | int(10) unsigned                     | NO   |     | 0                 |                             |
| url                  | tinytext                             | NO   |     | NULL              |                             |
| type                 | enum('NORMAL','ATTRIB','URL','NOTE') | NO   |     | NORMAL            |                             |
| tradeable            | tinyint(1)                           | NO   |     | 1                 |                             |
+----------------------+--------------------------------------+------+-----+-------------------+-----------------------------+

+---------------+------------------+------+-----+---------+----------------+
| Field         | Type             | Null | Key | Default | Extra          |
+---------------+------------------+------+-----+---------+----------------+
| npc_id        | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| game_id       | int(11)          | NO   | MUL | NULL    |                |
| name          | varchar(255)     | NO   |     |         |                |
| description   | text             | NO   |     | NULL    |                |
| text          | text             | NO   |     | NULL    |                |
| closing       | text             | NO   |     | NULL    |                |
| media_id      | int(10) unsigned | NO   |     | 0       |                |
| icon_media_id | int(10) unsigned | NO   |     | 0       |                |
+---------------+------------------+------+-----+---------+----------------+

+----------------------------------+------------------+------+-----+---------+----------------+
| Field                            | Type             | Null | Key | Default | Extra          |
+----------------------------------+------------------+------+-----+---------+----------------+
| node_id                          | int(11) unsigned | NO   | PRI | NULL    | auto_increment |
| game_id                          | int(11)          | NO   | MUL | NULL    |                |
| title                            | varchar(255)     | NO   |     | NULL    |                |
| text                             | text             | NO   |     | NULL    |                |
| opt1_text                        | varchar(100)     | YES  |     | NULL    |                |
| opt1_node_id                     | int(11) unsigned | NO   |     | 0       |                |
| opt2_text                        | varchar(100)     | YES  |     | NULL    |                |
| opt2_node_id                     | int(11) unsigned | NO   |     | 0       |                |
| opt3_text                        | varchar(100)     | YES  |     | NULL    |                |
| opt3_node_id                     | int(11) unsigned | NO   |     | 0       |                |
| require_answer_incorrect_node_id | int(11) unsigned | NO   |     | 0       |                |
| require_answer_string            | varchar(50)      | YES  |     | NULL    |                |
| require_answer_correct_node_id   | int(10) unsigned | NO   |     | 0       |                |
| media_id                         | int(10) unsigned | NO   |     | 0       |                |
| icon_media_id                    | int(10) unsigned | NO   |     | 0       |                |
+----------------------------------+------------------+------+-----+---------+----------------+

+---------------+------------------+------+-----+---------+----------------+
| Field         | Type             | Null | Key | Default | Extra          |
+---------------+------------------+------+-----+---------+----------------+
| web_page_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| game_id       | int(10) unsigned | NO   |     | NULL    |                |
| icon_media_id | int(10) unsigned | NO   |     | 4       |                |
| name          | varchar(20)      | NO   |     | NULL    |                |
| url           | tinytext         | NO   |     | NULL    |                |
+---------------+------------------+------+-----+---------+----------------+
*/

class PlayerLog extends Module
{
    public function getPlayerLogs($glob)
    {
        //Grrr amfphp should take care of this...
	$data = file_get_contents("php://input");
        $glob = json_decode($data);

        $reqOutputFormat = $glob->output_format;

        $reqGameId      = $glob->game_id;
        $reqEditorId    = $glob->editor_id;
        $reqEditorToken = $glob->editor_token;

        $reqGroup   = $glob->groupname;
        $reqPlayers = $glob->players;
        $reqPlayer  = $glob->player;

        $reqStartDate = $glob->start_date;
        $reqEndDate   = $glob->end_date;

        $reqGetExpired = $glob->get_expired;
        $reqVerbose    = $glob->verbose;

        //validation
        $expectsNotice = 'Expects JSON argument of minimal form: {"output_format":"json","game_id":1,"editor_id":1,"editor_token":"abc123"}';
	if(!is_string($reqOutputFormat)) $reqOutputFormat = "json"; else $reqOutputFormat = strToLower($reqOutputFormat);
        if($reqOutputFormat != "json" && $reqOutputFormat != "csv" && $reqOutputFormat != "xml")
	return new returnData(1, NULL, "Error- Invalid output format (".$reqOutputFormat.")\n".$expectsNotice);
	if(is_numeric($reqGameId)) $reqGameId = intval($reqGameId);
	else return new returnData(1, NULL, "Error- Empty Game (".$reqGameId.")\n".$expectsNotice);
	if(is_numeric($reqEditorId)) $reqEditorId = intval($reqEditorId);
	else return new returnData(1, NULL, "Error- Empty Editor (".$reqEditorId.")\n".$expectsNotice);
        if(!is_string($reqEditorToken))
        return new returnData(1, NULL, "Error- Invalid EditorToken (".$reqEditorToken.")\n".$expectsNotice);

        if(!Module::authenticateGameEditor($reqGameId, $reqEditorId, $reqEditorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $filterMode = "none";
        if(is_string($reqGroup))   $filterMode = "group";
        if(is_array($reqPlayers))  $filterMode = "players";
        if(is_numeric($reqPlayer)) $filterMode = "player";
        if(!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/",$reqStartDate)) $reqStartDate = "0000-00-00 00:00:00";
        if(!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/",$reqEndDate))   $reqEndDate   = "9999-00-00 00:00:00";
        if(!is_numeric($reqGetExpired)) $reqGetExpired = 0; else if(intval($reqGetExpired) > 0) $reqGetExpired = 1;
        if(!is_numeric($reqVerbose))    $reqVerbose    = 0; else if(intval($reqVerbose)    > 0) $reqVerbose    = 1;


        $playerLogs = array();
        if($filterMode == "group")
        {
            $p = Module::queryArray("SELECT player_id, display_name, media_id, group_name from players WHERE group_name = '{$reqGroup}'");
            for($i = 0; $i < count($p); $i++)
            {
                $log = new stdClass();
                $log->player = $p[$i];
                if($log->player->display_name == "") $log->player->display_name = $log->player->user_name;
                $log->player->pic_url = Media::getMediaObject("player", $p[$i]->media_id)->data->url;
                $playerLogs[] = $log;
            }
        }
        else if($filterMode == "players")
        {
            for($i = 0; $i < count($reqPlayers); $i++)
            {
                $p = Module::queryObject("SELECT player_id, display_name, media_id, group_name from players WHERE player_id = '{$reqPlayers[$i]}'");
                $log = new stdClass();
                $log->player = $p;
                if($log->player->display_name == "") $log->player->display_name = $log->player->user_name;
                $log->player->pic_url = Media::getMediaObject("player", $p->media_id)->data->url;
                $playerLogs[] = $log;
            }
        }
        else if($filterMode == "player")
        {
            $p = Module::queryObject("SELECT player_id, display_name, media_id, group_name from players WHERE player_id = '{$reqPlayer}'");
            $log = new stdClass();
            $log->player = $p;
            if($log->player->display_name == "") $log->player->display_name = $log->player->user_name;
            $log->player->pic_url = Media::getMediaObject("player", $p->media_id)->data->url;
            $playerLogs[] = $log;
        }
        else //get all players for game
        {
	    $r = Module::queryArray("SELECT player_id FROM player_log WHERE game_id = '{$reqGameId}' AND timestamp BETWEEN '{$reqStartDate}' AND '{$reqEndDate}' AND (deleted = 0 OR deleted = {$reqGetExpired}) GROUP BY player_id");
            for($i = 0; $i < count($r); $i++)
            {
                $p = Module::queryObject("SELECT player_id, user_name, display_name, media_id, group_name from players WHERE player_id = '{$r[$i]->player_id}'");
                if(!$p) continue;
                $log = new stdClass();
                $log->player = $p;
                if($log->player->display_name == "") $log->player->display_name = $log->player->user_name;
                $log->player->pic_url = Media::getMediaObject("player", intval($p->media_id))->data->url;
                $playerLogs[] = $log;
            }
        }

        //caches for quick content construction
        $questsA = Module::queryArray("SELECT quest_id, name FROM quests WHERE game_id = '{$reqGameId}'");
        $questsH = array(); for($i = 0; $i < count($questsA); $i++) $questsH[$questsA[$i]->quest_id] = $questsA[$i];
        $itemsA = Module::queryArray("SELECT item_id, name FROM items WHERE game_id = '{$reqGameId}'");
        $itemsH = array(); for($i = 0; $i < count($itemsA); $i++) $itemsH[$itemsA[$i]->item_id] = $itemsA[$i];
        $nodesA = Module::queryArray("SELECT node_id, title FROM nodes WHERE game_id = '{$reqGameId}'");
        $nodesH = array(); for($i = 0; $i < count($nodesA); $i++) $nodesH[$nodesA[$i]->node_id] = $nodesA[$i];
        $npcsA = Module::queryArray("SELECT npc_id, name FROM npcs WHERE game_id = '{$reqGameId}'");
        $npcsH = array(); for($i = 0; $i < count($npcsA); $i++) $npcsH[$npcsA[$i]->npc_id] = $npcsA[$i];
        $webpagesA = Module::queryArray("SELECT web_page_id, name FROM web_pages WHERE game_id = '{$reqGameId}'");
        $webpagesH = array(); for($i = 0; $i < count($webpagesA); $i++) $webpagesH[$webpagesA[$i]->web_page_id] = $webpagesA[$i];
        $locationsA = Module::queryArray("SELECT location_id, name FROM locations WHERE game_id = '{$reqGameId}'");
        $locationsH = array(); for($i = 0; $i < count($locationsA); $i++) $locationsH[$locationsA[$i]->location_id] = $locationsA[$i];
        $qrcodesA = Module::queryArray("SELECT qrcode_id, link_id, code FROM qrcodes WHERE game_id = '{$reqGameId}'");
        $qrcodesH = array(); for($i = 0; $i < count($qrcodesA); $i++) $qrcodesH[$qrcodesA[$i]->code] = $qrcodesA[$i];
        $webhooksA = Module::queryArray("SELECT web_hook_id, name FROM web_hooks WHERE game_id = '{$reqGameId}'");
        $webhooksH = array(); for($i = 0; $i < count($webhooksA); $i++) $webhooksH[$webhooksA[$i]->web_hook_id] = $webhooksA[$i];

        for($i = 0; $i < count($playerLogs); $i++)
        {
            $playerLogs[$i]->log = array();
	    $r = Module::queryArray("SELECT * FROM player_log WHERE player_id = '{$playerLogs[$i]->player->player_id}' AND game_id = '{$reqGameId}' AND  timestamp BETWEEN '{$reqStartDate}' AND '{$reqEndDate}' AND (deleted = 0 OR deleted = {$reqGetExpired})");
            for($j = 0; $j < count($r); $j++)
            {
                $row = new stdClass();
                switch($r[$j]->event_type)
                {
                    case "PICKUP_ITEM":
                        $row->event = "Received Item";
                        $row->object = $itemsH[$r[$j]->event_detail_1]->name;
                        $row->qty = $r[$j]->event_detail_2;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." received ".$row->qty." ".$row->object." (Item).";
                        break;
                    case "DROP_ITEM":
                    case "DESTROY_ITEM":
                        $row->event = "Lost Item";
                        $row->object = $itemsH[$r[$j]->event_detail_1]->name;
                        $row->qty = $r[$j]->event_detail_2;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." lost ".$row->qty." ".$row->object." (Item).";
                        break;
                    case "VIEW_ITEM":
                        $row->event = "Viewed Item";
                        $row->object = $itemsH[$r[$j]->event_detail_1]->name;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed ".$row->object." (Item).";
                        break;
                    case "VIEW_NODE":
                        $row->event = "Viewed Node";
                        $row->object = $nodesH[$r[$j]->event_detail_1]->title;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed ".$row->object." (Node).";
                        break;
                    case "VIEW_NPC":
                        $row->event = "Viewed NPC";
                        $row->object = $npcsH[$r[$j]->event_detail_1]->name;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed ".$row->object." (Npc).";
                        break;
                    case "VIEW_WEBPAGE":
                        $row->event = "Viewed Web Page";
                        $row->object = $webpagesH[$r[$j]->event_detail_1]->name;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed ".$row->object." (Web Page).";
                        break;
                    case "ENTER_QRCODE":
                        $row->event = "Entered QR";
                        $row->code = $r[$j]->event_detail_1;
                        $row->object = $locationsH[$qrcodesH[$r[$j]->event_detail_1]->link_id]->name;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." scanned ".$row->object.".";
                        break;
                    case "COMPLETE_QUEST":
                        $row->event = "Completed Quest";
                        $row->object = $questsH[$r[$j]->event_detail_1]->name;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." completed quest '".$row->object."'.";
                        break;
                    case "VIEW_MAP":
                        $row->event = "Viewed Map";
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed the map.";
                        break;
                    case "VIEW_QUESTS":
                        $row->event = "Viewed Quests";
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed the quests.";
                        break;
                    case "VIEW_INVENTORY":
                        $row->event = "Viewed Inventory";
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." viewed the inventory.";
                        break;
                    case "MOVE":
                        $row->event = "Moved";
                        $row->lat = $r[$j]->event_detail_1;
                        $row->lon = $r[$j]->event_detail_2;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." moved to (".$row->lat.($reqOutputFormat == "csv" ? " " : ",").$row->lon.")";
                        break;
                    case "RECEIVE_WEBHOOK":
                        $row->event = "Received Hook";
                        $row->object = $webhooksH[$r[$j]->event_detail_1]->name;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." received hook '".$row->object."'";
                        break;
                    default:
                        $row->event = $r[$j]->event_type;
                        $row->timestamp = $r[$j]->timestamp;
                        $row->human = $playerLogs[$i]->player->display_name." ".$row->event;
                        break;
                }
                $playerLogs[$i]->log[] = $row;
            }
        }
        if($reqOutputFormat == "json")
            return new returnData(0,$playerLogs);
        if($reqOutputFormat == "csv")
        {
            $csv = ""; //oh man. this is so slow.
            for($i = 0; $i < count($playerLogs); $i++)
            {
                for($j = 0; $j < count($playerLogs[$i]->log); $j++)
                {
                    $csv .= $playerLogs[$i]->player->group_name.",";
                    $csv .= $playerLogs[$i]->player->player_id.",";
                    $csv .= $playerLogs[$i]->player->display_name.",";
                    $csv .= $playerLogs[$i]->log[$j]->timestamp.",";
                    $csv .= $playerLogs[$i]->log[$j]->human."\n";
                }
            }
        }

        file_put_contents(Config::gamedataFSPath."/".$reqGameId."/mostrecentlogrequest.csv",$csv);

        return new returnData(0,Config::gamedataWWWPath."/".$reqGameId."/mostrecentlogrequest.csv");
    }
}
?>
