<?php

require_once("migration_dbconnection.php");

require_once("../v1/players.php");
require_once("../v1/editors.php");
require_once("../v2/users.php");

class migration extends migration_dbconnection
{	
    public function migrateUser($playerName, $playerPass, $editorName, $editorPass, $newName, $newPass)
    {
        $v1Player = Players::getLoginPlayerObject($playerName, $playerPass);
        $v1Editor = Editors::getToken($editorName, $editorPass, "read_write");
        if($v1Player->player_id && $v1Editor->editor_id)
            $v2User = users::createUser($newName, $newPass);
        else return "Invalid Credentials";
        if($v2User)
            migration_dbconnection::query("INSERT INTO user_migrations (v2_user_id, v1_player_id, v1_editor_id) VALUES ('{$v2User->user_id}', '{$v1Player->player_id}', '{$v1Editor->editor_id}')");
        else return "New Username taken";
    }

    public function migrateGame($v1GameId, $v1EditorId, $v1Token, $v2Username, $v2Password)
    {

    }

    public function duplicateGame($gameId, $userId, $key)
    {
        if(!users::authenticateUser($userId, $key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

	//Add back in when requirements not being deleted is fixed, recheck for other issues
	//$errorString = Conversations::searchGameForErrors($gameId);
	//if($errorString) return new returnData(3, NULL, $errorString);
        
	Module::serverErrorLog("Duplicating Game Id:".$gameId);

        $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = {$gameId} LIMIT 1");
        if (!$game) return new returnData(2, NULL, "invalid game id");

        $compatibleName = false;
        $appendNo = 1;
        while(!$compatibleName)
        {
            $query = "SELECT * FROM games WHERE name = '".addslashes($game->name)."_copy".$appendNo."'";
            $result = dbconnection::query($query);
            if(mysql_fetch_object($result))
                $appendNo++;
            else
                $compatibleName = true;
        }
        $game->name = $game->name."_copy".$appendNo;

        $newGameId = Games::createGame($game->name, $game->description, 
                $game->icon_media_id, $game->media_id,
                $game->ready_for_public, $game->is_locational,
                $game->on_launch_node_id, $game->game_complete_node_id,
                $game->allow_share_note_to_map, $game->allow_share_note_to_book, $game->allow_player_tags, $game->allow_player_comments, $game->allow_note_likes,
                $game->pc_media_id, $game->use_player_pic,
                $game->map_type, $game->show_player_location,
                $game->full_quick_travel,
                $game->inventory_weight_cap, $game->allow_trading, 
                $userId, $key)->data;

        //Remove the tabs created by createGame
        dbconnection::query("DELETE FROM game_tab_data WHERE game_id = {$newGameId}");

        $result = dbconnection::query("SELECT * FROM game_tab_data WHERE game_id = {$gameId}");
        while($result && $row = mysql_fetch_object($result))
            dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index, tab_detail_1) VALUES ('{$newGameId}', '{$row->tab}', '{$row->tab_index}', '{$row->tab_detail_1}')");

        $query = "SELECT * FROM requirements WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO requirements (game_id, content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4) VALUES ('{$newGameId}', '{$row->content_type}', '{$row->content_id}', '{$row->requirement}', '{$row->not_operator}', '{$row->boolean_operator}', '{$row->requirement_detail_1}', '{$row->requirement_detail_2}', '{$row->requirement_detail_3}', '{$row->requirement_detail_4}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM quests WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO quests (game_id, name, description, text_when_complete, sort_index, go_function, active_media_id, complete_media_id, full_screen_notify, active_icon_media_id, complete_icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '".addSlashes($row->text_when_complete)."', '{$row->sort_index}', '{$row->go_function}', '{$row->active_media_id}', '{$row->complete_media_id}', '{$row->full_screen_notify}', '{$row->active_icon_media_id}', '{$row->complete_icon_media_id}')";

            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_HAS_COMPLETED_QUEST' AND requirement_detail_1 = '{$row->quest_id}'";
            dbconnection::query($query);


            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND (content_type = 'QuestDisplay' OR content_type = 'QuestComplete') AND content_id = '{$row->quest_id}'";
            dbconnection::query($query);
        }

        $newFolderIds = array();
        $query = "SELECT * FROM folders WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO folders (game_id, name, parent_id, previous_id, is_open) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$row->parent_id}', '{$row->previous_id}', '{$row->is_open}')";
            dbconnection::query($query);
            $newFolderIds[($row->folder_id)] = mysql_insert_id();
        }

        $query = "SELECT * FROM folders WHERE game_id = {$newGameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            if($row->folder_id != 0){
                $query = "UPDATE folders SET parent_id = {$newFolderIds[($row->parent_id)]} WHERE game_id = '{$newGameId}' AND folder_id = {$row->folder_id}";
                dbconnection::query($query);
            }
        }

        $query = "SELECT * FROM folder_contents WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO folder_contents (game_id, folder_id, content_type, content_id, previous_id) VALUES ('{$newGameId}', '{$newFolderIds[($row->folder_id)]}', '{$row->content_type}', '{$row->content_id}', '{$row->previous_id}')";
            dbconnection::query($query);

            if($row->folder_id != 0){
                $query = "UPDATE folder_contents SET folder_id = {$newFolderIds[($row->folder_id)]} WHERE game_id = '{$newGameId}' AND object_content_id = {$row->object_content_id}";
                dbconnection::query($query); 
            }
        }

        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO qrcodes (game_id, link_type, link_id, code, match_media_id) VALUES ('{$newGameId}', '{$row->link_type}', '{$row->link_id}', '{$row->code}', '{$row->match_media_id}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM fountains WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO fountains (game_id, type, location_id, spawn_probability, spawn_rate, max_amount, last_spawned, active) VALUES ('{$newGameId}', '{$row->type}', '{$row->location_id}', '{$row->spawn_probability}', '{$row->spawn_rate}', '{$row->max_amount}', '{$row->last_spawned}', '{$row->active}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM spawnables WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO spawnables (game_id, type, type_id, amount, max_area, amount_restriction, location_bound_type, latitude, longitude, spawn_probability, spawn_rate, delete_when_viewed, last_spawned, error_range, force_view, hidden, allow_quick_travel, wiggle, time_to_live, active, location_name, show_title, min_area) VALUES ('{$newGameId}', '{$row->type}', '{$row->type_id}', '{$row->amount}', '{$row->max_area}', '{$row->amount_restriction}', '{$row->location_bound_type}', '{$row->latitude}', '{$row->longitude}', '{$row->spawn_probability}', '{$row->spawn_rate}', '{$row->delete_when_viewed}', '{$row->last_spawned}', '{$row->error_range}', '{$row->force_view}', '{$row->hidden}', '{$row->allow_quick_travel}', '{$row->wiggle}', '{$row->time_to_live}', '{$row->active}', '{$row->location_name}', '{$row->show_title}', '{$row->min_area}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE fountains SET location_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Spawnable' AND location_id = {$row->spawnable_id}";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM locations WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO locations (game_id, name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->latitude}', '{$row->longitude}', '{$row->error}', '{$row->type}', '{$row->type_id}', '{$row->icon_media_id}', '{$row->item_qty}', '{$row->hidden}', '{$row->force_view}', '{$row->allow_quick_travel}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE fountains SET location_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Location' AND location_id = {$row->location_id}";
            dbconnection::query($query);

            $query = "UPDATE qrcodes SET link_id = {$newId} WHERE game_id = '{$newGameId}' AND link_type = 'Location' AND link_id = {$row->location_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Location' AND content_id = {$row->location_id}";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM npc_conversations WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO npc_conversations (game_id, npc_id, node_id, text, sort_index) VALUES ('{$newGameId}', '{$row->npc_id}', '{$row->node_id}', '".addSlashes($row->text)."', '{$row->sort_index}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM player_state_changes WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO player_state_changes (game_id, event_type, event_detail, action, action_detail, action_amount) VALUES ('{$newGameId}', '{$row->event_type}', '{$row->event_detail}', '{$row->action}', '{$row->action_detail}', '{$row->action_amount}')";
            dbconnection::query($query);
        }

        $newNpcIds = array();
        $query = "SELECT * FROM npcs WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){

            $query = "INSERT INTO npcs (game_id, name, description, text, closing, media_id, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '".addSlashes($row->text)."', '".addSlashes($row->closing)."', '{$row->media_id}', '{$row->icon_media_id}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newNpcIds[($row->npc_id)] = $newId;

            $query = "UPDATE npc_conversations SET npc_id = {$newId} WHERE game_id = '{$newGameId}' AND npc_id = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Npc' AND content_id = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Npc' AND type_id = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_NPC' AND event_detail = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_VIEWED_NPC' AND requirement_detail_1 = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Npc' AND type_id = {$row->npc_id}";
            dbconnection::query($query);
        }

        $newNodeIds = array();
        $query = "SELECT * FROM nodes WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->title)."', '".addSlashes($row->text)."', '{$row->opt1_text}', '{$row->opt1_node_id}', '{$row->opt2_text}', '{$row->opt2_node_id}', '{$row->opt3_text}', '{$row->opt3_node_id}', '{$row->require_answer_incorrect_node_id}', '{$row->require_answer_string}', '{$row->require_answer_correct_node_id}', '{$row->media_id}', '{$row->icon_media_id}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newNodeIds[($row->node_id)] = $newId;

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Node' AND content_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Node' AND type_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE npc_conversations SET node_id = {$newId} WHERE game_id = '{$newGameId}' AND node_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_NODE' AND event_detail = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Node' AND content_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_VIEWED_NODE' AND requirement_detail_1 = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Node' AND type_id = {$row->node_id}";
            dbconnection::query($query);

            if ($row->node_id == $game->on_launch_node_id) {
                $query = "UPDATE games SET on_launch_node_id = {$newId} WHERE game_id = '{$newGameId}'";
                dbconnection::query($query);
            }
            if ($row->node_id == $game->game_complete_node_id) {
                $query = "UPDATE games SET game_complete_node_id = {$newId} WHERE game_id = '{$newGameId}'";
                dbconnection::query($query);
            }
        }

        $newItemIds = array();
        $query = "SELECT * FROM items WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO items (game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->is_attribute}', '{$row->icon_media_id}', '{$row->media_id}', '{$row->dropable}', '{$row->destroyable}', '{$row->max_qty_in_inventory}', '{$row->creator_player_id}', '{$row->origin_latitude}', '{$row->origin_longitude}', '{$row->origin_timestamp}', '{$row->weight}', '{$row->url}', '{$row->type}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newItemIds[($row->item_id)] = $newId;

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Item' AND content_id = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Item' AND type_id = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_ITEM' AND event_detail = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET action_detail = {$newId} WHERE game_id = '{$newGameId}' AND action_detail = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND (requirement = 'PLAYER_HAS_ITEM' OR requirement = 'PLAYER_VIEWED_ITEM') AND requirement_detail_1 = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Item' AND type_id = {$row->item_id}";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM aug_bubble_media WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO aug_bubble_media (game_id, aug_bubble_id, media_id, text, index) VALUES ('{$newGameId}', '{$row->aug_bubble_id}', '{$row->media_id}', '{$row->text}', '{$row->index}')";
            dbconnection::query($query);
        }

        $newAugBubbleIds = array();
        $query = "SELECT * FROM aug_bubbles WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO aug_bubbles (game_id, name, description, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->icon_media_id}')).";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newAugBubbleIds[($row->aug_bubble_id)] = $newId;

            $query = "UPDATE aug_bubble_media SET aug_bubble_id = {$newId} WHERE aug_bubble_id = {$row->aug_bubble_id}";
            dbconnection::query($query);
            $query = "UPDATE locations SET type_id = {$newId} WHERE type = 'AugBubble' AND type_id = {$row->aug_bubble_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE content_type = 'AugBubble' AND content_id = {$row->aug_bubble_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_AUGBUBBLE' OR requirement = 'PLAYER_VIEWED_AUGBUBBLE') AND requirement_detail_1 = {$row->aug_bubble_id}  AND game_id = '{$newGameId}'";
            dbconnection::query($query);
        }

        $newWebPageIds = array();
        $query = "SELECT * FROM web_pages WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO web_pages (game_id, name, url, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$row->url}', '{$row->icon_media_id}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newWebPageIds[($row->web_page_id)] = $newId;

            $query = "UPDATE locations SET type_id = {$newId} WHERE type = 'WebPage' AND type_id = {$row->web_page_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE content_type = 'WebPage' AND content_id = {$row->web_page_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_WEBPAGE' OR requirement = 'PLAYER_VIEWED_WEBPAGE') AND requirement_detail_1 = {$row->web_page_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM web_hooks WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO web_hooks (game_id, name, url, incoming) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->url)."', '{$row->incoming}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE requirements SET content_id = {$newId} WHERE content_type = 'OutgoingWebHook' AND content_id = {$row->web_hook_id}  AND game_id = '{$newGameId}'";
            dbconnection::query($query);
        }

        $originalMediaId = array();
        $newMediaId = array();
        $query = "SELECT * FROM media WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $newMediaFilePath = $newGameId.substr($row->file_path,strpos($row->file_path,'/'));
            $query = "INSERT INTO media (game_id, name, file_path, is_icon) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$newMediaFilePath}', '{$row->is_icon}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newMediaIds[($row->media_id)] = $newId;

            if($row->file_path != "" && substr($row->file_path,-1) != "/" && file_exists("../../gamedata/" . $row->file_path)) copy(("../../gamedata/" . $row->file_path),("../../gamedata/" . $newMediaFilePath));

            $query = "UPDATE items SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE items SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE locations SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE nodes SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE nodes SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE npcs SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE npcs SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE qrcodes SET match_media_id = {$newId} WHERE match_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET active_icon_media_id = {$newId} WHERE active_icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET complete_icon_media_id = {$newId} WHERE complete_icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET active_media_id = {$newId} WHERE active_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET complete_media_id = {$newId} WHERE complete_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE aug_bubbles SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE aug_bubble_media SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE games SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE games SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE games SET pc_media_id = {$newId} WHERE pc_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE web_pages SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
        }

        //NOTE: substr removes <?xml version="1.0" ? //> from the beginning of the text
        $query = "SELECT node_id FROM npc_conversations WHERE game_id = {$newGameId}";
        $result = dbconnection::query($query);
        while($result && ($npcConvo = mysql_fetch_object($result))) {
            $query = "SELECT node_id, text FROM nodes WHERE node_id = {$npcConvo->node_id}";
            $resultNode = dbconnection::query($query);
            if($result && ($node = mysql_fetch_object($resultNode))){
                $inputString = $node->text;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE nodes SET text = '".addslashes($output)."' WHERE node_id = {$node->node_id} AND game_id = {$newGameId}";
                    dbconnection::query($updateQuery);
                }
            }
        }

        $query = "SELECT * FROM npcs WHERE game_id = {$newGameId}";
        $result = dbconnection::query($query);
        while($result && ($row = mysql_fetch_object($result))) {
            if($row->text){
                $inputString = $row->text;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE npcs SET text = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$newGameId}";
                    dbconnection::query($updateQuery);
                }
            }
            if($row->closing){
                $inputString = $row->closing;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE npcs SET closing = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$newGameId}";
                    dbconnection::query($updateQuery);
                }
            }
        }

        return new returnData(0, $newGameId, NULL);
    }

    static function replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds)
    {
        $kTagExitToPlaque = "exitToPlaque";
        $kTagExitToWebPage = "exitToWebPage";
        $kTagExitToCharacter = "exitToCharacter";
        $kTagExitToPanoramic = "exitToPanoramic";
        $kTagExitToItem = "exitToItem";
        $kTagVideo = "video";
        $kTagId = "id";
        $kTagPanoramic = "panoramic";
        $kTagWebpage = "webpage";
        $kTagPlaque = "plaque";
        $kTagItem = "item";
        $kTagMedia = "mediaId";

        //& sign will break xml parser, so this is necessary
        $inputString = str_replace("&", "&#x26;", $inputString);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($inputString);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        if($xml){

            foreach($xml->attributes() as $attributeTitle => $attributeValue)
            { 
                if(strcmp($attributeTitle, $kTagExitToWebPage) == 0)
                    $xml[$attributeTitle] = $newWebPageIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToPanoramic) == 0)
                    $xml[$attributeTitle] = $newAugBubbleIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagMedia) == 0)
                    $xml[$attributeTitle] = $newMediaIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToPlaque) == 0)
                    $xml[$attributeTitle] = $newNodeIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToCharacter) == 0)
                    $xml[$attributeTitle] = $newNpcIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToItem) == 0)
                    $xml[$attributeTitle] = $newItemIds[intval($attributeValue)];
            }

            foreach($xml->children() as $child)
            {
                foreach($child->attributes() as $attributeTitle => $attributeValue)
                { 
                    if(strcmp($attributeTitle, $kTagExitToWebPage) == 0)
                        $child[$attributeTitle] = $newWebPageIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToPanoramic) == 0)
                        $child[$attributeTitle] = $newAugBubbleIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagMedia) == 0)
                        $child[$attributeTitle] = $newMediaIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagVideo) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newMediaIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagPanoramic) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newAugBubbleIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagWebpage) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newWebPageIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToPlaque) == 0)
                        $child[$attributeTitle] = $newNodeIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToCharacter) == 0)
                        $child[$attributeTitle] = $newNpcIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToItem) == 0)
                        $child[$attributeTitle] = $newItemIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagPlaque) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newNodeIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagItem) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newItemIds[intval($attributeValue)];
                }
            }
            $output = $xml->asXML();
            $output = str_replace("&#x2019;", "'", $output);
            $output = str_replace("&amp;", "&", $output);
            $output = str_replace("&#x2014;", "-", $output);
            $output = str_replace("&#x201C;", "\"", $output);
            $output = str_replace("&#x201D;", "\"", $output);
            $output = str_replace("&#xB0;", "°", $output);
            $output = str_replace("&#xAE;", "®", $output);
            $output = str_replace("&#x2122;", "™", $output);
            $output = str_replace("&#xA9;", "©", $output);
            return $output;
        }
        return false;
    }
}
?>
