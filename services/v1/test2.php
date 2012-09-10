<?php
require_once("module.php");
require_once("qrcodes.php");

class Test extends Module
{
    public function killOrphans()
    {
        //Create QR codes for locations without them
        for($i = 0; $i < 4000; $i++)
        {
            if(!Module::getPrefix($i)) continue;
            $result = mysql_query("SELECT * FROM ".$i."_locations LEFT JOIN ".$i."_qrcodes ON ".$i."_locations.location_id = ".$i."_qrcodes.link_id WHERE link_id IS NULL");
            while($table = mysql_fetch_object($result))
                QRCodes::createQRCode($i, 'Location', $table->location_id);
        }

        //Delete QR Codes who don't have locations
        for($i = 0; $i < 4000; $i++)
        {
            if(!Module::getPrefix($i)) continue;
            $result = mysql_query("SELECT * FROM ".$i."_qrcodes LEFT JOIN ".$i."_locations ON ".$i."_qrcodes.link_id = ".$i."_locations.location_id WHERE location_id IS NULL");
            while($table = mysql_fetch_object($result))
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
            while($table = mysql_fetch_object($result))
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
            while($table = mysql_fetch_object($result))
                mysql_query("DELETE FROM spawnables WHERE spawnable_id  = ".$table->spawnable_id);
        }
    }
}

