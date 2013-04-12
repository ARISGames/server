<?php





$version = 'v1';

require_once('../config.class.php');
chdir("../services/{$version}");


require_once("module.php");

/*

url for image: http://maps.googleapis.com/maps/api/staticmap?center=42.375,-72.516546&zoom=16&size=1024x1024&maptype=roadmap&sensor=false&scale=2

google api zoom => iphone scale
16 => 9
17 => 4
18 => 2
19 => 1 

*/


class ModuleExt extends Module {
	public function getPrefix($gameId) {
		return Module::getPrefix($gameId);
	}
}
class Sync {
  
  function __construct() {
  }
  

  public function  get_media_info($id, $game_id) {
    require_once("./media.php");
    
    $media = new Media();
    $media_info = @$media->getMediaObject($game_id, $id);
    if ($media_info->data->is_default) {
      $game_id = 0;
    }
    $filename = Config::gamedataFSPath . '/' . $media_info->data->file_path;
    $md5 = md5_file($filename);
    $data = (object)array('data' => $media_info->data, 'md5' => $md5);
    return $data;
  }
  
  private function scan_tiles($dir, $path, &$maps) {
    $files = scandir($dir . $path);
    foreach($files as $file) {
        if ($file[0] === '.') continue;
        if (is_file($dir . $path . '/' . $file)) {
            $md5 = md5_file($dir . $path . '/' . $file);
            $maps[] = array('filename' => $file, 'path' => $path, 'md5' => $md5);
        }
        else {
            $this->scan_tiles($dir, $path . '/' . $file, $maps);
        }
    }    
  }
  
  public function get_maps($id) {
    $maps = array();
    $dir = Config::gamedataFSPath. '/' . $id . '/';
    $path = 'tiles';
    if (!file_exists($dir . $path)) {
	    $latitude = 42.375;
	    $longitude = -72.516546;
	    $zoom = 8;
	   $result = mysql_query("SELECT media_id, file_name FROM media WHERE game_id = $id AND file_name REGEXP 'map[0-9]+.png'");

	  while ($map_file = mysql_fetch_object($result)) {
	   preg_match('/map([0-9]+)\.png/', $map_file->file_name, $matches);
       $zoom = (int)$matches[1];
	   $maps[] = array('media_id' => $map_file->media_id, 'latitude' => $latitude, 'longitude' => $longitude, 'zoom' => $zoom);
 	  }
    }
    else {
        $this->scan_tiles($dir, $path, $maps);
    } 
    //first see if we have tiles
    //error_log(print_r($maps, 1));
    
    return $maps;
  }
  
  public function get_all($id, $player_id) {
    require_once("./games.php");
    require_once("./quests.php");
    require_once("./items.php");
    require_once("./locations.php");
    require_once("./npcs.php");
    
    
    $games = new Games();
	  $out = array();
	  $result = $games->getGame($id);
	  $fullGame = $games->getFullGameObject($id, $player_id);
	  $maps = $this->get_maps($id);
	  /*
	  if ($id === 173) {
	    $latitude = 42.375;
	    $longitude = -72.516546;
	    $zoom = 8;
	  }
	  else {
	    //$latitude = 42.2083;
	    $latitude = 42.20800;
	    //$longitude = -72.616196;
	    //$longitude = -72.6109;
	    $longitude = -72.61156;
	    $zoom = 4;
	  }
	  while ($map_file = mysql_fetch_object($result)) {
	   preg_match('/map([0-9]+)\.(png|jpg)/', $map_file->file_name, $matches);
       $zoom = (int)$matches[1];
	   $maps[] = array('media_id' => $map_file->media_id, 'latitude' => $latitude, 'longitude' => $longitude, 'zoom' => $zoom);
 	  }	  
 	  */
	  $fullGame->maps = $maps;
	  $out['game'] = $fullGame;
	  
    $sql = "SELECT player_id, user_name, latitude, longitude FROM players WHERE player_id = '$player_id'";
    $result = mysql_query($sql);
    $out['player'] = mysql_fetch_object($result);
	  
	  
	  $items = new Items();
	  $result = $items->getItems($id);
	  $out['items'] = array();
	  while ($item = mysql_fetch_object($result->data)) {
	   $out['items'][] = $item;
 	  }
	
	  $quests = new Quests();
	  $result = $quests->getQuests($id);
	  $out['quests'] = array();
	  while ($quest = mysql_fetch_object($result->data)) {
		  $out['quests'][] = $quest;
	  }
	  
	  $nodes = new Nodes();
	  $result = $nodes->getNodes($id);
	  $out['nodes'] = array();
	  while ($node = mysql_fetch_object($result->data)) {
	    $out['nodes'][] = $node;
	  }	  
	  $locations = new Locations();
	  $result = $locations->getLocations($id);
	  $out['locations'] = array();
	  while ($location = mysql_fetch_object($result->data)) {
	    $out['locations'][] = $location;
	  }
	  
	  $npcs = new Npcs();
	  $result = $npcs->getNpcs($id);
	  $out['npcs'] = array();
	  while ($npc = mysql_fetch_object($result->data)) {
	    $out['npcs'][] = $npc;
	  }
	
	  //print_r(Config::dbUser);
	  $prefix = ModuleExt::getPrefix($id);
	  $sql = "SELECT * FROM requirements WHERE game_id = '$id'";
	  $result = mysql_query($sql);
	  $out['requirements'] = array();
	  while ($requirement = mysql_fetch_object($result)) {
		  $out['requirements'][] = $requirement;
	  }
	  
	  $sql = "SELECT * FROM player_log WHERE game_id = '$id' AND player_id = '$player_id' AND deleted <> 1";
	  $result = mysql_query($sql);
	  $out['player_logs'] = array();
	  while ($requirement = mysql_fetch_object($result)) {
		  $out['player_logs'][] = $requirement;
	  }
	  
	  // tabs
	  $result = $games->getTabBarItemsForGame($id);
	  $out['tabs'] = array();
	  while ($tab = mysql_fetch_object($result->data)) {
	   $out['tabs'][] = $tab;
 	  }
 	  
 	  // media
	  /*
	  $out['media'] = array();
	  while ($media = mysql_fetch_object($result->data)) {
	    $out['media'][] = $media;
	  }
	  */
	  // items for player
	  $result = $items->getItemsForPlayer($id, $player_id);
	  $out['player_items'] = array();
	  while ($player_item = mysql_fetch_object($result->data)) {
		  $out['player_items'][] = $player_item;
	  }
	  
	  // conversations
	  $sql = "SELECT * FROM npc_conversations WHERE game_id = '$id'";
	  $result = mysql_query($sql);
	  $out['npc_conversations'] = array();
	  while ($conversation = mysql_fetch_object($result)) {
		  $out['npc_conversations'][] = $conversation;
	  }
	  
	  // qrcodes
	  $qrcodes = new QRCodes();
	  $result = $qrcodes->getQRCodes($id);
	  $out['qrcodes'] = array();
	  while ($qrcodes = mysql_fetch_object($result->data)) {
	    $out['qrcodes'][] = $qrcodes;
	  }
	  
	  // player state changes
	  $sql = "SELECT * FROM player_state_changes WHERE game_id = '$id'";
	  $result = mysql_query($sql);
	  $out['player_state_changes'] = array();
	  while ($state_change = mysql_fetch_object($result)) {
		  $out['player_state_changes'][] = $state_change;
	  }	  
	  
	  return $out;
  }

}

