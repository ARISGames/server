<?php
//NOTE- Cannot require conflicting class names (case insensitive)!
//query the db raw for any problematic classnames
require_once("../v1/players.php");
require_once("../v1/editors.php");
require_once("../v1/games.php");
require_once("../v2/users.php");

//require gross copypastad stubs to account for above problem
require_once("games.php");

//actually meaningful migration includes
require_once("migration_dbconnection.php");
require_once("migration_return_package.php");

class migration extends migration_dbconnection
{	
    //Would be better if it used tokens rather than name/pass combos, but v1 player has no token
    public function migrateUser($playerName, $playerPass, $editorName, $editorPass, $newName, $newPass, $newDisplay, $newEmail)
    {
        $Players = new Players;
        $Editors = new Editors;
        $users = new users;

        $v1Player = $Players->getLoginPlayerObject($playerName, $playerPass)->data;
        $v1Editor = $Editors->getToken($editorName, $editorPass, "read_write")->data;

        if($playerName && !$v1Player) return new migration_return_package(1,NULL,"Player Credentials Invalid");
        if($editorName && !$v1Editor) return new migration_return_package(1,NULL,"Editor Credentials Invalid");
        if(!$v1Player  && !$v1Editor) return new migration_return_package(1,NULL,"No Data to Migrate");

        $userpack = new stdClass();
        $userpack->user_name = $newName;
        $userpack->password = $newPass;
        $userpack->display_name = $newDisplay;
        $userpack->email = $newEmail;
        $userpack->permission = "read_write";
        $v2User = $users->logInPack($userpack)->data;
        if(!$v2User) //user doesn't exists
        {
            //Don't create new user if trying to migrate from already migrated data
            if($v1Player && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_player_id = '{$v1Player->player_id}'"))
                return new migration_return_package(1,NULL,"Player already migrated.");
            if($v1Editor && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_editor_id = '{$v1Editor->editor_id}'"))
                return new migration_return_package(1,NULL,"Editor already migrated.");

            $v2User = $users->createUserPack($userpack)->data;
            if(!$v2User) return new migration_return_package(1,NULL,"Username Taken");
        }
        else
        {
            //Don't link existing data if already linked to other user
            if($v1Player && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_player_id = '{$v1Player->player_id}' AND v2_user_id != '{$v2User->user_id}'"))
                return new migration_return_package(1,NULL,"Player already migrated.");
            if($v1Editor && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_editor_id = '{$v1Editor->editor_id} AND v2_user_id != '{$v2User->user_id}''"))
                return new migration_return_package(1,NULL,"Editor already migrated.");
        }

        if(!$v1Player) { $v1Player = new stdClass(); $v1Player->player_id = 0; }
        if(!$v1Editor) { $v1Editor = new stdClass(); $v1Editor->editor_id = 0; }

        if(migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v2_user_id = '{$v2User->user_id}'")) //already in migrations
            migration_dbconnection::query("UPDATE user_migrations SET v1_player_id = '{$v1Player->player_id}',  v1_editor_id = '{$v1Editor->editor_id}', v1_read_write_token = '{$v1Editor->read_write_token}' WHERE v2_user_id = '{$v2User->user_id}'");
        else //not in migrations
            migration_dbconnection::query("INSERT INTO user_migrations (v2_user_id, v2_read_write_key, v1_player_id, v1_editor_id, v1_read_write_token) VALUES ('{$v2User->user_id}', '{$v2User->read_write_key}', '{$v1Player->player_id}', '{$v1Editor->editor_id}', '{$v1Editor->read_write_token}')");

        return new migration_return_package(0,true);
    }

    public function migrateGame($v1GameId, $v1EditorId, $v1EditorToken)
    {
        $Editors = new Editors;
        $Games = new Games;
        $migGames = new mig_games;

        $migData = migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_editor_id = '{$v1EditorId}'");
        if(!$migData) return new migration_return_package(1, NULL, "Editor not migrated");
        if($migData->v1_read_write_token != $v1EditorToken) return new migration_return_package(1, NULL, "Editor Authentication Failed");

        $v2Auth = new stdClass();
        $v2Auth->user_id = $migData->v2_user_id;
        $v2Auth->key = $migData->v2_read_write_key;
        $v2Auth->permission = "read_write";
        
        $oldGame = $Games->getGame($v1GameId)->data;
        //conform old terminology to new
        $oldGame->allow_note_player_tags = $oldGame->allow_player_tags;
        $oldGame->auth = $v2Auth;
        $v2GameId = $migGames->createGame($oldGame);

        $maps = new stdClass();
        $maps->media = migration::migrateMedia($v1GameId, $v2GameId);

        //update game media refrences
        $v2Game = migration_dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$v2GameId}'","v2");
        migration_dbconnection::query("UPDATE games SET media_id = '{$maps->media[$v2Game->media_id]}', icon_media_id = '{$maps->media[$v2Game->icon_media_id]}'","v2");
        $v2Game = migration_dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$v2GameId}'","v2"); //get updated game data

        $maps->plaques = migration::migratePlaques($v1GameId, $v2GameId,$maps);
        $maps->items = migration::migrateItems($v1GameId, $v2GameId,$maps);
        $maps->webpages = migration::migrateWebpages($v1GameId, $v2GameId,$maps);
        $maps->dialogs = migration::migrateDialogs($v1GameId, $v2GameId,$maps);
        //$maps->notes = migration::migrateNotes($v1GameId, $v2GameId,$maps);
        $maps->tabs = migration::migrateTabs($v1GameId, $v2GameId,$maps);

        return new migration_return_package(0,$v2Game);
    }

    public function migrateMedia($v1GameId, $v2GameId)
    {
        $mediaIdMap = array();
        $mediaIdMap[0] = 0; //preserve default/no media

        $media = migration_dbconnection::queryArray("SELECT * FROM media WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($media); $i++)
        {
            $mediaIdMap[$media[$i]->media_id] = 0; //set it to 0 in case of failure
            if(!file_exists(Config::gamedataFSPath."/".$media[$i]->file_path)) continue;
            $filename = substr($media[$i]->file_path, strpos($media[$i]->file_path,'/')+1);
            copy(Config::gamedataFSPath."/".$media[$i]->file_path,Config::v2_gamedata_folder."/".$filename);
            $newMediaId = migration_dbconnection::queryInsert("INSERT INTO media (game_id, file_folder, file_name, display_name, created) VALUES ('{$v2GameId}','{$v2GameId}','{$filename}','{$media[$i]->name}',CURRENT_TIMESTAMP)", "v2");
            $mediaIdMap[$media[$i]->media_id] = $newMediaId;
        }
        return $mediaIdMap;
    }

    public function migratePlaques($v1GameId, $v2GameId, $maps)
    {
        $plaqueIdMap = array();
        $plaqueIdMap[0] = 0;

        $plaques = migration_dbconnection::queryArray("SELECT * FROM nodes WHERE game_id = '{$v1GameId}'","v1");

        //find plaques that are actually npc options so we can ignore them
        $invalidMap = array();
        $npcPlaques = migration_dbconnection::queryArray("SELECT * FROM npc_conversations WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($npcPlaques); $i++)
            $invalidMap[$npcPlaques[$i]->node_id] = true;

        for($i = 0; $i < count($plaques); $i++)
        {
            $plaqueIdMap[$plaques[$i]->node_id] = 0; //set it to 0 in case of failure
            if($invalidMap[$plaques[$i]->node_id]) continue; //this plaque actually an npc option- ignore

            $newPlaqueId = migration_dbconnection::queryInsert("INSERT INTO plaques (game_id, name, description, icon_media_id, media_id, created) VALUES ('{$v2GameId}','{$plaques[$i]->title}','{$plaques[$i]->text}','{$maps->media[$plaques[$i]->icon_media_id]}','{$maps->media[$plaques[$i]->media_id]}',CURRENT_TIMESTAMP)", "v2");
            $plaqueIdMap[$plaques[$i]->node_id] = $newPlaqueId;
        }
        return $plaqueIdMap;
    }

    public function migrateItems($v1GameId, $v2GameId, $maps)
    {
        $itemIdMap = array();
        $itemIdMap[0] = 0;

        $items = migration_dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($items); $i++)
        {
            $itemIdMap[$items[$i]->item_id] = 0; //set it to 0 in case of failure
            $newItemId = migration_dbconnection::queryInsert("INSERT INTO items (game_id, name, description, icon_media_id, media_id, droppable, destroyable, max_qty_in_inventory, weight, url, type, created) VALUES ('{$v2GameId}','{$items[$i]->name}','{$items[$i]->description}','{$maps->media[$items[$i]->icon_media_id]}','{$maps->media[$items[$i]->media_id]}','{$items[$i]->dropable}','{$items[$i]->destroyable}','{$items[$i]->max_qty_in_inventory}','{$items[$i]->weight}','{$items[$i]->url}','{$items[$i]->type}',CURRENT_TIMESTAMP)", "v2");
            $itemIdMap[$items[$i]->item_id] = $newItemId;
        }
        return $itemIdMap;
    }

    public function migrateWebpages($v1GameId, $v2GameId, $maps)
    {
        $webpageIdMap = array();
        $webpageIdMap[0] = 0;

        $webpages = migration_dbconnection::queryArray("SELECT * FROM web_pages WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($webpages); $i++)
        {
            $webpageIdMap[$webpages[$i]->web_page_id] = 0; //set it to 0 in case of failure
            $newWebpageId = migration_dbconnection::queryInsert("INSERT INTO web_pages (game_id, name, icon_media_id, url, created) VALUES ('{$v2GameId}','{$webpages[$i]->name}','{$maps->media[$webpages[$i]->icon_media_id]}','{$webpages[$i]->url}',CURRENT_TIMESTAMP)", "v2");
            $webpageIdMap[$webpages[$i]->web_page_id] = $newWebpageId;
        }
        return $webpageIdMap;
    }

    public function migrateDialogs($v1GameId, $v2GameId, $maps)
    {
        $dialogIdMap = array();
        $dialogIdMap[0] = 0;

        $dialogs = migration_dbconnection::queryArray("SELECT * FROM npcs WHERE game_id = '{$v1GameId}'","v1");

        //construct map of nodes for quick recall as referenced by npc_conversations
        $nodes = migration_dbconnection::queryArray("SELECT * FROM nodes WHERE game_id = '{$v1GameId}'","v1");
        $nodeMap = array();
        for($i = 0; $i < count($nodes); $i++)
            $nodeMap[$nodes[$i]->node_id] = $nodes[$i];

        for($i = 0; $i < count($dialogs); $i++)
        {
            $dialogIdMap[$dialogs[$i]->npc_id] = 0; //set it to 0 in case of failure
            $newDialogId = migration_dbconnection::queryInsert("INSERT INTO dialogs (game_id, name, description, icon_media_id, created) VALUES ('{$v2GameId}','{$dialogs[$i]->name}','{$dialogs[$i]->description}','{$maps->media[$dialogs[$i]->icon_media_id]}',CURRENT_TIMESTAMP)", "v2");

            $newCharacterId = migration_dbconnection::queryInsert("INSERT INTO dialog_characters (game_id, name, title, media_id, created) VALUES ('{$v2GameId}','{$dialogs[$i]->name}','{$dialogs[$i]->name}','{$maps->media[$dialogs[$i]->media_id]}',CURRENT_TIMESTAMP)", "v2");
            $newScriptId = 0;

            //create intro script if exists, and treat it as the root script for all others
            if($dialogs[$i]->text && $dialogs[$i]->text != "")
                $newScriptId = migration::textToScript("Greet", $dialogs[$i]->text, $v2GameId, $newDialogId, $newCharacterId, $dialogs[$i]->name, $maps->media[$dialogs[$i]->media_id], $newScriptId, $maps);

            $options = migration_dbconnection::queryArray("SELECT * FROM npc_conversations WHERE game_id = '{$v1GameId}' AND npc_id = '{$dialogs[$i]->npc_id}'","v1");
            for($j = 0; $j < count($options); $j++)
                migration::textToScript($options[$j]->text, $nodeMap[$options[$j]->node_id]->text, $v2GameId, $newDialogId, $newCharacterId, $dialogs[$i]->name, $maps->media[$dialogs[$i]->media_id], $newScriptId, $maps);

            $dialogIdMap[$dialogs[$i]->npc_id] = $newDialogId;
        }
        return $dialogIdMap;
    }

    //helper for migrateDialogs
    //returns the id of the LAST of the newly created chain of scripts. (aka the to-be-parent of any more scripts)
    //disclaimer: you should probably read up on regular expressions before messing around with this...
    public function textToScript($option, $text, $gameId, $dialogId, $rootCharacterId, $rootCharacterTitle, $rootCharacterMediaId, $parentScriptId, $maps)
    {
        //testing scripts
        //$text = "<dialog banana=\"testing\" butNot=\"12\" America='555'><npc mediaId = \"59\">\nHere is the first thing I will say</npc><npc mediaId = \"60\">Second Thing!!!</npc></dialog>";
        //$text = "<d  but >dialog </dialog>"

        //The case where no parsing is necessary 
        if(!preg_match("@<\s*dialog(ue)?\s*(\w*=[\"']\w*[\"']\s*)*>(.*?)<\s*/\s*dialog(ue)?\s*>@is",$text,$matches))
        {
            //phew. Nothing complicated. 
            $newScriptId = migration_dbconnection::queryInsert("INSERT INTO dialog_scripts 
            (game_id, dialog_id, parent_dialog_script_id, dialog_character_id, text, prompt, created) VALUES 
            ('{$gameId}','{$dialogId}','{$parentScriptId}','{$rootCharacterId}','{$text}','{$option}',CURRENT_TIMESTAMP)", "v2");
            return $newScriptId;
        }

        //buckle up...
        $newScriptId = $parentScriptId;
        $dialogContents = $matches[3]; //$dialogContents will be the string between the dialog tags
        while(!preg_match("@^\s*$@s",$dialogContents))
        {
            preg_match("@<\s*([^\s>]*)([^>]*)@is",$dialogContents,$matches);
            $tag = $matches[1]; //$tag will be the tag type (example: "npc")
            $attribs = $matches[2]; //$attribs will be the string of attributes on tag (example: "mediaId='123' title='billy'")
            preg_match("@<\s*".$tag."[^>]*>(.*?)<\s*\/\s*".$tag."\s*>(.*)@is",$dialogContents,$matches);
            $tag_contents = $matches[1]; //$tag_contents will be the string between npc tags (example: "Hi!")
            $dialogContents = $matches[2]; //$dialog_contents will be the rest of the dialog contents that still need parsing

            $characterId = 0;
            if(preg_match("@npc@i",$tag))
            {
                $characterId = $rootCharacterId; //assume clean npc tag, use default character

                $title = "";
                $mediaId = 0;
                while(preg_match("@^\s*([^\s=]*)\s*=\s*[\"']([^\"']*)[\"']\s*(.*)@is",$attribs,$matches))
                {
                    //In the example:  mediaId="123" name="billy"
                    $attrib_name = $matches[1]; //mediaId
                    $attrib_value = $matches[2]; //123
                    $attribs = $matches[3]; //name="billy"

                    if(preg_match("@title@i",$attrib_name)) $title = $attrib_value;
                    if(preg_match("@mediaId@i",$attrib_name)) $mediaId = $attrib_value;
                }

                if($title != "" || $mediaId != 0)
                {
                    if($title == "") $title = $rootCharacterTitle;
                    if($mediaId == 0) $title = $rootCharacterMediaId;
                    $characterId = migration_dbconnection::queryInsert("INSERT INTO dialog_characters (game_id, name, title, media_id, created) VALUES ('{$gameId}','{$title}','{$title}','{$rootCharacterMediaId}',CURRENT_TIMESTAMP)", "v2");
                }
            }
            else
            {
                //while($attribs
                //preg_match($
            }

            $newScriptId = migration_dbconnection::queryInsert("INSERT INTO dialog_scripts (game_id, dialog_id, parent_dialog_script_id, dialog_character_id, text, prompt, created) VALUES ('{$gameId}','{$dialogId}','{$newScriptId}','{$rootCharacterId}','{$tag_contents}','{$option}',CURRENT_TIMESTAMP)", "v2");
            $option = "Continue"; //set option for all but first script to 'continue'
        }
        return $newScriptId;
    }

    public function migrateTabs($v1GameId, $v2GameId, $maps)
    {
        $tabIdMap = array();
        $tabIdMap[0] = 0; //preserve default/no tab

        //remove defaults created at game creation, just to simplify things
        migration_dbconnection::query("DELETE FROM tabs WHERE game_id = '{$v2GameId}'","v2");

        $tabs = migration_dbconnection::queryArray("SELECT * FROM game_tab_data WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($tabs); $i++)
        {
            $tabIdMap[$tabs[$i]->id] = 0; //set it to 0 in case of failure
            if($tabs[$i]->sort_index < 1) continue; //in new model, disabled tabs are simply deleted

            //old: 'GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME','NPC','ITEM','NODE','WEBPAGE'
            //new: 'MAP','DECODER','SCANNER','QUESTS','INVENTORY','PLAYER','NOTE','DIALOG','ITEM','PLAQUE','WEBPAGE'
            $newType = $tabs[$i]->tab;
            $newDetail = 0;
            if($tabs[$i]->tab == "NEARBY") continue;
            if($tabs[$i]->tab == "STARTOVER") continue;
            if($tabs[$i]->tab == "PICKGAME") continue;
            if($tabs[$i]->tab == "GPS")       { $newType = "MAP";      $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "QUESTS")    { $newType = "QUESTS";   $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "INVENTORY") { $newType = "MAP";      $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "PLAYER")    { $newType = "PLAYER";   $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "NOTE")      { $newType = "NOTE";     $newDetail = $maps->notes[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "NPC")       { $newType = "DIALOG";   $newDetail = $maps->dialogs[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "ITEM")      { $newType = "ITEM";     $newDetail = $maps->items[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "NODE")      { $newType = "PLAQUE";   $newDetail = $maps->plaques[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "WEBPAGE")   { $newType = "WEB_PAGE"; $newDetail = $maps->webpages[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "QR") $newType = ($tab[$i]->tab_detail_1 == 0 || $tab[$i]->tab_detail_1 == 2) ? "SCANNER" : "DECODER";

            $newTabId = migration_dbconnection::queryInsert("INSERT INTO tabs (game_id, type, sort_index, tab_detail_1, created) VALUES 
            ('{$v2GameId}','{$newType}','{$tabs[$i]->tab_index}','{$newDetail}',CURRENT_TIMESTAMP)", "v2");
            $tabIdMap[$tabs[$i]->tab] = $newTabId;
        }
        return $tabIdMap;
    }

    public function duplicateGame($gameId, $userId, $key)
    {
    /*
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
        */
    }

    static function replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds)
    {
    /*
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
        */
    }
}
?>
