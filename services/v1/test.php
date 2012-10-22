<?php
require_once("module.php");
require_once("qrcodes.php");
require_once("games.php");

class Test extends Module
{
	public function killOrphansBeforeMigration()
	{ 
		set_time_limit(100000);

		//Create QR codes for locations without them
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			$result = mysql_query("SELECT * FROM ".$i."_locations LEFT JOIN ".$i."_qrcodes ON ".$i."_locations.location_id = ".$i."_qrcodes.link_id WHERE link_id IS NULL");
			while($result && $table = mysql_fetch_object($result))
				QRCodes::createQRCode($i, 'Location', $table->location_id);
		}

		//Delete QR Codes who don't have locations
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			$result = mysql_query("SELECT * FROM ".$i."_qrcodes LEFT JOIN ".$i."_locations ON ".$i."_qrcodes.link_id = ".$i."_locations.location_id WHERE location_id IS NULL");
			while($result && $table = mysql_fetch_object($result))
				mysql_query("DELETE FROM ".$i."_qrcodes WHERE qr_code_id = ".$table->qr_code_id);
		}

		//Delete locations who don't have objects
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			//'Node','Event','Item','Npc','WebPage','AugBubble','PlayerNote'
			$query = "SELECT locations.* FROM ".$i."_locations AS locations 
				LEFT JOIN ".$i."_nodes AS nodes ON locations.type = 'Node' AND locations.type_id = nodes.node_id
				LEFT JOIN ".$i."_items AS items ON locations.type = 'Item' AND locations.type_id = items.item_id
				LEFT JOIN ".$i."_npcs AS npcs ON locations.type = 'Npc' AND locations.type_id = npcs.npc_id
				LEFT JOIN (SELECT * FROM web_pages WHERE game_id = ".$i.") AS web_pages ON locations.type = 'WebPage' AND locations.type_id = web_pages.web_page_id
				LEFT JOIN (SELECT * FROM aug_bubbles WHERE game_id = ".$i.") AS aug_bubbles ON locations.type = 'AugBubble' AND locations.type_id = aug_bubbles.aug_bubble_id
				LEFT JOIN (SELECT * FROM notes WHERE game_id = ".$i.") AS notes ON locations.type = 'PlayerNote' AND locations.type_id = notes.note_id
				WHERE 
				nodes.node_id IS NULL AND
				items.item_id IS NULL AND
				npcs.npc_id IS NULL AND
				web_pages.web_page_id IS NULL AND
				aug_bubbles.aug_bubble_id IS NULL AND
				notes.note_id IS NULL";
			$result = mysql_query($query);
			//echo $query."\n";
			while($result && $table = mysql_fetch_object($result))
				mysql_query("DELETE FROM ".$i."_locations WHERE location_id = ".$table->location_id);
		}
		//Deletes Spawnable data without an object
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			//'Node','Event','Item','Npc','WebPage','AugBubble','PlayerNote'
			$query = "SELECT spawnables.* FROM (SELECT * FROM spawnables WHERE game_id = ".$i.") AS spawnables
				LEFT JOIN ".$i."_nodes AS nodes ON spawnables.type = 'Node' AND spawnables.type_id = nodes.node_id
				LEFT JOIN ".$i."_items AS items ON spawnables.type = 'Item' AND spawnables.type_id = items.item_id
				LEFT JOIN ".$i."_npcs AS npcs ON spawnables.type = 'Npc' AND spawnables.type_id = npcs.npc_id
				LEFT JOIN (SELECT * FROM web_pages WHERE game_id = ".$i.") AS web_pages ON spawnables.type = 'WebPage' AND spawnables.type_id = web_pages.web_page_id
				LEFT JOIN (SELECT * FROM aug_bubbles WHERE game_id = ".$i.") AS aug_bubbles ON spawnables.type = 'AugBubble' AND spawnables.type_id = aug_bubbles.aug_bubble_id
				LEFT JOIN (SELECT * FROM notes WHERE game_id = ".$i.") AS notes ON spawnables.type = 'PlayerNote' AND spawnables.type_id = notes.note_id
				WHERE 
				nodes.node_id IS NULL AND
				items.item_id IS NULL AND
				npcs.npc_id IS NULL AND
				web_pages.web_page_id IS NULL AND
				aug_bubbles.aug_bubble_id IS NULL AND
				notes.note_id IS NULL";
			$result = mysql_query($query);
			while($result && $table = mysql_fetch_object($result))
				mysql_query("DELETE FROM spawnables WHERE spawnable_id  = ".$table->spawnable_id);
		}


		$query = "SELECT * FROM games";
		$resultMainGame = mysql_query($query);
		while($result && $row = mysql_fetch_object($resultMainGame)){ 
			$gid = $row->game_id;
			$query = "SELECT * FROM {$gid}_requirements";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 

				if($row->content_type == "Node"){
					$result = mysql_query("SELECT * FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
				}
				else if($row->content_type == "QuestDisplay" || $row->content_type == "QuestComplete"){
					$result = mysql_query("SELECT * FROM {$gid}_quests WHERE quest_id = {$row->content_id}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
				}
				else if($row->content_type == "Location"){
					$result = mysql_query("SELECT * FROM {$gid}_locations WHERE location_id = {$row->content_id}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
				}
				else if($row->content_type == "OutgoingWebHook"){
					$result = mysql_query("SELECT * FROM web_hooks WHERE web_hook_id = {$row->content_id}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
				}
				else if($row->content_type == "Spawnable"){
					$result = mysql_query("SELECT * FROM spawnables WHERE spawnable_id = {$row->content_id}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
				}

				if($row->requirement == "PLAYER_HAS_ITEM" || $row->requirement == "PLAYER_VIEWED_ITEM"){
					if(!$row->requirement_detail_1){
						Module::serverErrorLog("Noticed a requirement with no item id in game: {$gid} with requirement id: {$row->requirement_id}");	
					}
					else{
						$result = mysql_query("SELECT * FROM {$gid}_items WHERE item_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}"); 
					}
				} 
				else if($row->requirement == "PLAYER_VIEWED_NODE"){
					//  Module::serverErrorLog("Kiling Orphans: {$gid} looking at {$row->requirement_id} with detail_1 of {$row->requirement_detail_1}");
					if(!$row->requirement_detail_1){
						//   Module::serverErrorLog("Noticed a requirement with no node id in game: {$gid}");	
					}
					else{
						$result = mysql_query("SELECT * FROM {$gid}_nodes WHERE node_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
					}
				}
				else if($row->requirement == "PLAYER_VIEWED_NPC"){
					if(!$row->requirement_detail_1){
						//  Module::serverErrorLog("Noticed a requirement with no npc id in game: {$gid}");	
					}
					else{
						$result = mysql_query("SELECT * FROM {$gid}_npcs WHERE npc_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
					}
				}
				else if($row->requirement == "PLAYER_VIEWED_WEBPAGE"){
					if(!$row->requirement_detail_1){
						Module::serverErrorLog("Noticed a requirement with no webpage id in game: {$gid}");	
					}
					else{
						$result = mysql_query("SELECT * FROM web_pages WHERE web_page_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
					}
				}
				else if($row->requirement == "PLAYER_VIEWED_AUGBUBBLE"){
					if(!$row->requirement_detail_1){
						Module::serverErrorLog("Noticed a requirement with no augbubble id in game: {$gid}");	
					}
					else{
						$result = mysql_query("SELECT * FROM aug_bubbles WHERE aug_bubble_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
					}
				}
				else if($row->requirement == "PLAYER_HAS_COMPLETED_QUEST"){
					if(!$row->requirement_detail_1){
						//  Module::serverErrorLog("Noticed a requirement no quest id in game: {$gid}");	
					}
					else{
						$result = mysql_query("SELECT * FROM {$gid}_quests WHERE quest_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
					}
				}
				else if($row->requirement == "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK"){
					if(!$row->requirement_detail_1){
						Module::serverErrorLog("Noticed a requirement with no webhook id in game: {$gid}");	
					}
					else{
						$result = mysql_query("SELECT * FROM web_hooks WHERE web_hook_id = {$row->requirement_detail_1}");
						if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_requirements WHERE requirement_id = {$row->requirement_id}");
					}
				} 
			}
			$query = "SELECT * FROM {$gid}_player_items";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 
				$result = mysql_query("SELECT * FROM {$gid}_items WHERE item_id = {$row->item_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_items WHERE id = {$row->id}");
				$result = mysql_query("SELECT * FROM players WHERE player_id = {$row->player_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_items WHERE id = {$row->id}"); 
			} 
			$query = "SELECT * FROM {$gid}_player_state_changes";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 
				if($row->event_type == "VIEW_ITEM"){
					$result = mysql_query("SELECT * FROM {$gid}_items WHERE item_id = {$row->event_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}");
				}
				else if($row->event_type == "VIEW_NODE"){
					$result = mysql_query("SELECT * FROM {$gid}_nodes WHERE node_id = {$row->event_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}");
				}
				else if($row->event_type == "VIEW_NPC"){
					$result = mysql_query("SELECT * FROM {$gid}_npcs WHERE npc_id = {$row->event_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}"); 
				} 
				else if($row->event_type == "VIEW_WEBPAGE"){
					$result = mysql_query("SELECT * FROM web_pages WHERE web_page_id = {$row->event_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}"); 
				} 
				else if($row->event_type == "VIEW_AUGBUBBLE"){
					$result = mysql_query("SELECT * FROM aug_bubbles WHERE aug_bubble_id = {$row->event_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}"); 
				} 
				else if($row->event_type == "RECEIVE_WEBHOOK"){
					$result = mysql_query("SELECT * FROM web_hooks WHERE web_hook_id = {$row->event_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}"); 
				}
				else if($row->action == "GIVE_ITEM" || $row->action == "TAKE_ITEM"){
					$result = mysql_query("SELECT * FROM {$gid}_items WHERE item_id = {$row->action_detail}");
					if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_player_state_changes WHERE id = {$row->id}"); 
				} 
			}
			$query = "SELECT * FROM note_likes";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 
				$result = mysql_query("SELECT * FROM notes WHERE note_id = {$row->note_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM note_likes WHERE note_id = {$row->note_id}"); 
			} 
			$query = "SELECT * FROM note_content";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 
				$result = mysql_query("SELECT * FROM notes WHERE note_id = {$row->note_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM note_content WHERE note_id = {$row->note_id}"); 
			} 
			$query = "SELECT * FROM note_tags";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 
				$result = mysql_query("SELECT * FROM notes WHERE note_id = {$row->note_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM note_tags WHERE note_id = {$row->note_id}"); 
			} 
			$query = "SELECT * FROM {$gid}_npc_conversations";
			$resultMain = mysql_query($query);
			while($resultMain && $row = mysql_fetch_object($resultMain)){ 
				$result = mysql_query("SELECT * FROM {$gid}_npcs WHERE npc_id = {$row->npc_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_npc_conversations WHERE conversation_id = {$row->conversation_id}"); 
				$result = mysql_query("SELECT * FROM {$gid}_npcs WHERE npc_id = {$row->npc_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM {$gid}_npc_conversations WHERE conversation_id = {$row->conversation_id}"); 
			}
		}
		return 5;
	}

	public function killOrphansAfterMigration()
	{
		//Create QR codes for locations without them
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			$result = mysql_query("SELECT * FROM (SELECT * FROM locations WHERE game_id = {$i}) AS game_locations LEFT JOIN (SELECT * FROM qrcodes WHERE game_id = {$i}) AS game_qrcodes ON game_locations.location_id = game_qrcodes.link_id WHERE link_id IS NULL");
			while($table = mysql_fetch_object($result))
				QRCodes::createQRCode($i, 'Location', $table->location_id);
		}

		//Delete QR Codes who don't have locations
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			$result = mysql_query("SELECT * FROM  (SELECT * FROM qrcodes WHERE game_id = {$i}) AS game_qrcodes LEFT JOIN (SELECT * FROM locations WHERE game_id = {$i}) AS game_locations ON game_qrcodes.link_id = game_locations.location_id WHERE location_id IS NULL");
			while($table = mysql_fetch_object($result))
				mysql_query("DELETE FROM qrcodes WHERE game_id = {$i} AND qr_code_id = ".$table->qr_code_id);
		}

		//Delete locations who don't have objects
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			//'Node','Event','Item','Npc','WebPage','AugBubble','PlayerNote'
			$query = "SELECT locations.* FROM (SELECT * FROM locations WHERE game_id = {$i}) AS locations 
				LEFT JOIN (SELECT * FROM nodes WHERE game_id = {$i}) AS nodes ON locations.type = 'Node' AND locations.type_id = nodes.node_id
				LEFT JOIN (SELECT * FROM items WHERE game_id = {$i}) AS items  ON locations.type = 'Item' AND locations.type_id = items.item_id
				LEFT JOIN (SELECT * FROM npcs WHERE game_id = {$i}) AS npcs ON locations.type = 'Npc' AND locations.type_id = npcs.npc_id
				LEFT JOIN (SELECT * FROM web_pages WHERE game_id = ".$i.") AS web_pages ON locations.type = 'WebPage' AND locations.type_id = web_pages.web_page_id
				LEFT JOIN (SELECT * FROM aug_bubbles WHERE game_id = ".$i.") AS aug_bubbles ON locations.type = 'AugBubble' AND locations.type_id = aug_bubbles.aug_bubble_id
				LEFT JOIN (SELECT * FROM notes WHERE game_id = ".$i.") AS notes ON locations.type = 'PlayerNote' AND locations.type_id = notes.note_id
				WHERE 
				nodes.node_id IS NULL AND
				items.item_id IS NULL AND
				npcs.npc_id IS NULL AND
				web_pages.web_page_id IS NULL AND
				aug_bubbles.aug_bubble_id IS NULL AND
				notes.note_id IS NULL";
			$result = mysql_query($query);
			//echo $query."\n";
			while($table = mysql_fetch_object($result))
				mysql_query("DELETE FROM locations WHERE game_id = {$i} AND location_id = ".$table->location_id);
		}

		//Deletes Spawnable data without an object
		for($i = 0; $i < 4000; $i++)
		{
			if(!Module::getPrefix($i)) continue;
			//'Node','Event','Item','Npc','WebPage','AugBubble','PlayerNote'
			$query = "SELECT spawnables.* FROM (SELECT * FROM spawnables WHERE game_id = ".$i.") AS spawnables
				LEFT JOIN (SELECT * FROM nodes WHERE game_id = {$i}) AS nodes ON spawnables.type = 'Node' AND spawnables.type_id = nodes.node_id
				LEFT JOIN (SELECT * FROM items WHERE game_id = {$i}) AS items ON spawnables.type = 'Item' AND spawnables.type_id = items.item_id
				LEFT JOIN (SELECT * FROM npcs WHERE game_id = {$i}) AS npcs ON spawnables.type = 'Npc' AND spawnables.type_id = npcs.npc_id
				LEFT JOIN (SELECT * FROM web_pages WHERE game_id = ".$i.") AS web_pages ON spawnables.type = 'WebPage' AND spawnables.type_id = web_pages.web_page_id
				LEFT JOIN (SELECT * FROM aug_bubbles WHERE game_id = ".$i.") AS aug_bubbles ON spawnables.type = 'AugBubble' AND spawnables.type_id = aug_bubbles.aug_bubble_id
				LEFT JOIN (SELECT * FROM notes WHERE game_id = ".$i.") AS notes ON spawnables.type = 'PlayerNote' AND spawnables.type_id = notes.note_id
				WHERE 
				nodes.node_id IS NULL AND
				items.item_id IS NULL AND
				npcs.npc_id IS NULL AND
				web_pages.web_page_id IS NULL AND
				aug_bubbles.aug_bubble_id IS NULL AND
				notes.note_id IS NULL";
			$result = mysql_query($query);
			while($table = mysql_fetch_object($result))
				mysql_query("DELETE FROM spawnables WHERE spawnable_id  = ".$table->spawnable_id);
		}
		$query = "SELECT * FROM requirements";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 

			if($row->content_type == "Node"){
				$result = mysql_query("SELECT * FROM nodes WHERE node_id = {$row->content_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->content_type == "QuestDisplay" || $row->content_type == "QuestComplete"){
				$result = mysql_query("SELECT * FROM quests WHERE quest_id = {$row->content_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->content_type == "Location"){
				$result = mysql_query("SELECT * FROM locations WHERE location_id = {$row->content_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->content_type == "OutgoingWebHook"){
				$result = mysql_query("SELECT * FROM web_hooks WHERE web_hook_id = {$row->content_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->content_type == "Spawnable"){
				$result = mysql_query("SELECT * FROM spawnables WHERE spawnable_id = {$row->content_id}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}

			if($row->requirement == "PLAYER_HAS_ITEM" || $row->requirement == "PLAYER_VIEWED_ITEM"){
				$result = mysql_query("SELECT * FROM items WHERE item_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}"); 
			} 
			else if($row->requirement == "PLAYER_VIEWED_NODE"){
				$result = mysql_query("SELECT * FROM nodes WHERE node_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->requirement == "PLAYER_VIEWED_NPC"){
				$result = mysql_query("SELECT * FROM npcs WHERE npc_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->requirement == "PLAYER_VIEWED_WEBPAGE"){
				$result = mysql_query("SELECT * FROM web_pages WHERE web_page_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->requirement == "PLAYER_VIEWED_AUGBUBBLE"){
				$result = mysql_query("SELECT * FROM aug_bubbles WHERE aug_bubble_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM" || $row->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE" || $row->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO"  || $row->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO"){
				$result = mysql_query("SELECT * FROM media WHERE media_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->requirement == "PLAYER_HAS_COMPLETED_QUEST"){
				$result = mysql_query("SELECT * FROM quests WHERE quest_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			}
			else if($row->requirement == "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK"){
				$result = mysql_query("SELECT * FROM web_hooks WHERE web_hook_id = {$row->requirement_detail_1}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM requirements WHERE requirement_id = {$row->requirement_id}");
			} 
		}
		$query = "SELECT * FROM player_items";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			$result = mysql_query("SELECT * FROM items WHERE item_id = {$row->item_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_items WHERE id = {$row->id}");
			$result = mysql_query("SELECT * FROM players WHERE player_id = {$row->player_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_items WHERE id = {$row->id}"); 
		} 
		$query = "SELECT * FROM player_state_changes";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			if($row->event_type == "VIEW_ITEM"){
				$result = mysql_query("SELECT * FROM items WHERE item_id = {$row->event_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}");
			}
			else if($row->event_type == "VIEW_NODE"){
				$result = mysql_query("SELECT * FROM nodes WHERE node_id = {$row->event_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}");
			}
			else if($row->event_type == "VIEW_NPC"){
				$result = mysql_query("SELECT * FROM npcs WHERE npc_id = {$row->event_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}"); 
			} 
			else if($row->event_type == "VIEW_WEBPAGE"){
				$result = mysql_query("SELECT * FROM web_pages WHERE web_page_id = {$row->event_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}"); 
			} 
			else if($row->event_type == "VIEW_AUGBUBBLE"){
				$result = mysql_query("SELECT * FROM aug_bubbles WHERE aug_bubble_id = {$row->event_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}"); 
			} 
			else if($row->event_type == "RECEIVE_WEBHOOK"){
				$result = mysql_query("SELECT * FROM web_hooks WHERE web_hook_id = {$row->event_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}"); 
			}
			else if($row->action == "GIVE_ITEM" || $row->action == "TAKE_ITEM"){
				$result = mysql_query("SELECT * FROM items WHERE item_id = {$row->action_detail}");
				if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM player_state_changes WHERE id = {$row->id}"); 
			} 
		}
		$query = "SELECT * FROM note_likes";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			$result = mysql_query("SELECT * FROM notes WHERE note_id = {$row->note_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM note_likes WHERE note_id = {$row->note_id}"); 
		} 
		$query = "SELECT * FROM note_content";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			$result = mysql_query("SELECT * FROM notes WHERE note_id = {$row->note_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM note_content WHERE note_id = {$row->note_id}"); 
		} 
		$query = "SELECT * FROM note_tags";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			$result = mysql_query("SELECT * FROM notes WHERE note_id = {$row->note_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM note_tags WHERE note_id = {$row->note_id}"); 
		} 
		$query = "SELECT * FROM npc_conversations";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			$result = mysql_query("SELECT * FROM npcs WHERE npc_id = {$row->npc_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM npc_conversations WHERE conversation_id = {$row->conversation_id}"); 
			$result = mysql_query("SELECT * FROM npcs WHERE npc_id = {$row->npc_id}");
			if($result && mysql_num_rows($result) < 1) mysql_query("DELETE FROM npc_conversations WHERE conversation_id = {$row->conversation_id}"); 
		}
		return 7;
	}

	public function searchGameForErrors($gid){

		$query = "SELECT name FROM games WHERE game_id = {$gid}";
		$name = mysql_query($query);

		Module::serverErrorLog("\nLooking for problems in {$name}\nNote: This check does not quarantee there are no errors in your game, but only checks for a few common mistakes.\n");	

		$query = "SELECT * FROM {$gid}_requirements";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			if(!$row->requirement_detail_1){
				if($row->requirement == "PLAYER_HAS_ITEM" || $row->requirement == "PLAYER_VIEWED_ITEM"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which item the player needs to have/have viewed.\n");	
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
				            Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which item the player needs to have/have viewed.\n");	
                                        }
					if(!$row->requirement_detail_2 && $row->requirement == "Player_HAS_ITEM"){
						if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that requires the player has a certain item, but not the quantity of that item needed.\n");	
						else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
				            Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that requires that the player has a certain item, but not the quantity of that item neeeded.\n");	
}
					}
				} 
				else if($row->requirement == "PLAYER_VIEWED_NODE"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which node the player needed to view in order to satisfy that requirement.\n");	
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
						Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of  {$scriptTitle} that doesn't specify which node the player needed to view in order to satisfy that requirement.\n");	
}
				}
				else if($row->requirement == "PLAYER_VIEWED_NPC"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which character the player needed to view in order to satisfy that requirement.\n");	
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which character the player needed to view in order to satisfy that requirement.\n");	
}
				}
				else if($row->requirement == "PLAYER_VIEWED_WEBPAGE"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which web page the player needed to view in order to satisfy that requirement.\n");	
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which web page the player needed to view in order to satisfy that requirement.\n");	
}

				}
				else if($row->requirement == "PLAYER_VIEWED_AUGBUBBLE"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which panoramic the player needed to view in order to satisfy that requirement.\n");
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which panoramic the player needed to view in order to satisfy that requirement.\n");
}
				}
				else if($row->requirement == "PLAYER_HAS_COMPLETED_QUEST"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which quest the player needed to complete in order to satisfy that requirement.\n");	
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which quest the player needed to complete in order to satisfy that requirement.\n");	
}
				}
				else if($row->requirement == "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK"){
					if(!($row->content_type == "Node")) Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which incoming web hook the player needed to receive in order to satisfy that requirement.\n");	
					else{
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
Module::serverErrorLog("\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which incoming web hook the player needed to receive in order to satisfy that requirement.\n");	
}

				} 
			}
		}
		$query = "SELECT * FROM {$gid}_player_state_changes";
		$resultMain = mysql_query($query);
		while($resultMain && $row = mysql_fetch_object($resultMain)){ 
			if($row->event_type == "VIEW_ITEM"){
				if(!$row->action_detail){
					Module::serverErrorLog("\nThere is an item of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n");	
				}
				if(!$row->action_amount){
					Module::serverErrorLog("\nThere is an item of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n");	
				}
			}
			else if($row->event_type == "VIEW_NODE"){
				if(!$row->action_detail){
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->event_detail}");
					Module::serverErrorLog("\nThere is a node with the title of {$scriptTitle} that doesn't specify what item to give or take when viewed.\n");	
				}
				if(!$row->action_amount){
                                            $scriptTitle = mysql_query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->event_detail}");
					Module::serverErrorLog("\nThere is a node with the title of {$scriptTitle} that doesn't specify what quantity of an item to give or take when viewed.\n");	
				}
			}
			else if($row->event_type == "VIEW_NPC"){
				if(!$row->action_detail){
					Module::serverErrorLog("\nThere is a character of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n");	
				}
				if(!$row->action_amount){
					Module::serverErrorLog("\nThere is a character of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n");	
				}
			} 
			else if($row->event_type == "VIEW_WEBPAGE"){
				if(!$row->action_detail){
					Module::serverErrorLog("\nThere is a web page of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n");	
				}
				if(!$row->action_amount){
					Module::serverErrorLog("\nThere is a web page of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n");	
				}
			} 
			else if($row->event_type == "VIEW_AUGBUBBLE"){
				if(!$row->action_detail){
					Module::serverErrorLog("\nThere is a panoramic of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n");	
				}
				if(!$row->action_amount){
					Module::serverErrorLog("\nThere is a panoramic of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n");	
				}
			} 
			else if($row->event_type == "RECEIVE_WEBHOOK"){
				if(!$row->action_detail){
					Module::serverErrorLog("\nThere is an web hook of id: {$row->event_detail} that doesn't specify what item to give or take when received.\n");	
				}
				if(!$row->action_amount){
					Module::serverErrorLog("\nThere is an web hook of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when received.\n");	
				}
			}
		}

		$query = "SELECT * FROM {$gid}_nodes";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if(!$output) Module::serverErrorLog("\nThere is improperly formatted xml in the node with title:\n{$row->title}\nand text:\n{$row->text}\n");
				}
			}
		}

		$query = "SELECT * FROM {$gid}_npcs";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if(!$output) Module::serverErrorLog("\nThere is improperly formatted xml in the npc with name:\n{$row->name}\nand greeting:\n{$row->text}\n");
				}
			}
			if($row->closing){
				$inputString = $row->closing;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if(!$output) Module::serverErrorLog("\nThere is improperly formatted xml in the npc with name:\n{$row->name}\nand closing:\n{$row->text}\n");
				}
			}
		}  
	}
	public function fixBadQuotes(){
		$gameResult = mysql_query("SELECT * FROM games");
		while($gameResult && $game = mysql_fetch_object($gameResult)){
		$gid = $game->game_id;
		$query = "SELECT * FROM {$gid}_nodes";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
					if($output) {
						$output = str_replace("“", "\"", $inputString);
						$output = str_replace("”", "\"", $inputString);
                                                $updateQuery = "UPDATE {$gid}_nodes SET text = '".addslashes($output)."' WHERE node_id = {$row->node_id} AND game_id = {$newPrefix}";
                                                mysql_query($updateQuery);
					}
				}
			}
		}

		$query = "SELECT * FROM {$gid}_npcs";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
                                        if($output) {
						$output = str_replace("“", "\"", $inputString);
                                                $output = str_replace("”", "\"", $inputString);
                                                $updateQuery = "UPDATE {$gid}_npcs SET text = '".addslashes($output)."' WHERE npc_id = {$row->npc_id}";
                                                mysql_query($updateQuery);
                                        }
				}
			}
			if($row->closing){
				$inputString = $row->closing;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					@$output = simplexml_load_string($inputString);
                                        if($output) {
						$output = str_replace("“", "\"", $inputString);
                                                $output = str_replace("”", "\"", $inputString);
                                                $updateQuery = "UPDATE {$gid}_npcs SET closing = '".addslashes($output)."' WHERE npc_id = {$row->npc_id}";
                                                mysql_query($updateQuery);
                                        }
				}
			}
                   }
		}
	}
}
