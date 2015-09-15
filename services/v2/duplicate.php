<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");

class column
{
  public $name; public $meta;
  public function column($name, $meta)
  {
    $this->name = $name; $this->meta = $meta;
    return $this;
  }
}

class table_data
{
  public $table;
  public $columns;
  public $data;
  public function table_data($table, $columns, $data)
  {
    $this->table = $table;
    $this->columns = $columns;
    $this->data = $data;
    return $this;
  }
}

class duplicate extends dbconnection
{
  private static function getSchema(&$tables, &$columns, &$coltablemap)
  {
    $i = 0;

    //'id' = that tables identifier. gets changed (auto-inc) during migration, and must be recorded
    //'map' = a value that maps to the id of other migrated table (value changes during migration)
    //'timestamp' = current timestamp (at time of duplicate)
    //'special' = a value that changes according to something more complicated than just a mapping. must be handled in php.
    //'' = nothing special- copy value as-is

    $tables[] = 'games';
    $columns[] = array();
    $columns[$i][] = new column('game_id','id');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('rating','');
    $columns[$i][] = new column('published','');
    $columns[$i][] = new column('type','');
    $columns[$i][] = new column('intro_scene_id','map');
    $columns[$i][] = new column('latitude','');
    $columns[$i][] = new column('longitude','');
    $columns[$i][] = new column('map_type','');
    $columns[$i][] = new column('map_latitude','');
    $columns[$i][] = new column('map_longitude','');
    $columns[$i][] = new column('map_zoom_level','');
    $columns[$i][] = new column('map_show_player','');
    $columns[$i][] = new column('map_show_players','');
    $columns[$i][] = new column('map_offsite_mode','');
    $columns[$i][] = new column('notebook_allow_comments','');
    $columns[$i][] = new column('notebook_allow_likes','');
    $columns[$i][] = new column('notebook_trigger_scene_id','map');
    $columns[$i][] = new column('notebook_trigger_requirement_root_package_id','map');
    $columns[$i][] = new column('notebook_trigger_title','');
    $columns[$i][] = new column('notebook_trigger_icon_media_id','map');
    $columns[$i][] = new column('notebook_trigger_distance','');
    $columns[$i][] = new column('notebook_trigger_infinite_distance','');
    $columns[$i][] = new column('notebook_trigger_wiggle','');
    $columns[$i][] = new column('notebook_trigger_show_title','');
    $columns[$i][] = new column('notebook_trigger_hidden','');
    $columns[$i][] = new column('notebook_trigger_on_enter','');
    $columns[$i][] = new column('inventory_weight_cap','');
    $columns[$i][] = new column('is_siftr','');
    $columns[$i][] = new column('siftr_url','special');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'dialog_characters';
    $columns[] = array();
    $columns[$i][] = new column('dialog_character_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('title','');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'dialog_options';
    $columns[] = array();
    $columns[$i][] = new column('dialog_option_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('dialog_id','map');
    $columns[$i][] = new column('parent_dialog_script_id','map');
    $columns[$i][] = new column('prompt','');
    $columns[$i][] = new column('link_type','');
    $columns[$i][] = new column('link_id','special');
    $columns[$i][] = new column('link_info','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('sort_index','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'dialog_scripts';
    $columns[] = array();
    $columns[$i][] = new column('dialog_script_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('dialog_id','map');
    $columns[$i][] = new column('dialog_character_id','map');
    $columns[$i][] = new column('text','');
    $columns[$i][] = new column('event_package_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'dialogs';
    $columns[] = array();
    $columns[$i][] = new column('dialog_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('intro_dialog_script_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'event_packages';
    $columns[] = array();
    $columns[$i][] = new column('event_package_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'events';
    $columns[] = array();
    $columns[$i][] = new column('event_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('event_package_id','map');
    $columns[$i][] = new column('event','');
    $columns[$i][] = new column('content_id','special');
    $columns[$i][] = new column('qty','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'factories';
    $columns[] = array();
    $columns[$i][] = new column('factory_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('object_type','');
    $columns[$i][] = new column('object_id','special');
    $columns[$i][] = new column('seconds_per_production','');
    $columns[$i][] = new column('production_probability','');
    $columns[$i][] = new column('max_production','');
    $columns[$i][] = new column('produce_expiration_time','');
    $columns[$i][] = new column('produce_expire_on_view','');
    $columns[$i][] = new column('production_bound_type','');
    $columns[$i][] = new column('location_bound_type','');
    $columns[$i][] = new column('min_production_distance','');
    $columns[$i][] = new column('max_production_distance','');
    $columns[$i][] = new column('production_timestamp','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('trigger_latitude','');
    $columns[$i][] = new column('trigger_longitude','');
    $columns[$i][] = new column('trigger_distance','');
    $columns[$i][] = new column('trigger_infinite_distance','');
    $columns[$i][] = new column('trigger_on_enter','');
    $columns[$i][] = new column('trigger_hidden','');
    $columns[$i][] = new column('trigger_wiggle','');
    $columns[$i][] = new column('trigger_title','');
    $columns[$i][] = new column('trigger_icon_media_id','map');
    $columns[$i][] = new column('trigger_show_title','');
    $columns[$i][] = new column('trigger_requirement_root_package_id','map');
    $columns[$i][] = new column('trigger_scene_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'instances';
    $columns[] = array();
    $columns[$i][] = new column('instance_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('object_type','');
    $columns[$i][] = new column('object_id','special');
    $columns[$i][] = new column('qty','');
    $columns[$i][] = new column('infinite_qty','');
    $columns[$i][] = new column('factory_id','map');
    $columns[$i][] = new column('owner_type','');
    $columns[$i][] = new column('owner_id','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'items';
    $columns[] = array();
    $columns[$i][] = new column('item_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('droppable','');
    $columns[$i][] = new column('destroyable','');
    $columns[$i][] = new column('max_qty_in_inventory','');
    $columns[$i][] = new column('weight','');
    $columns[$i][] = new column('url','');
    $columns[$i][] = new column('type','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'media';
    $columns[] = array();
    $columns[$i][] = new column('media_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('user_id','');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('file_folder','special');
    $columns[$i][] = new column('file_name','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'notes';
    $columns[] = array();
    $columns[$i][] = new column('note_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('user_id','');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('published','');
    $i++;

    $tables[] = 'object_tags';
    $columns[] = array();
    $columns[$i][] = new column('object_tag_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('object_type','');
    $columns[$i][] = new column('object_id','special');
    $columns[$i][] = new column('tag_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'overlays';
    $columns[] = array();
    $columns[$i][] = new column('overlay_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('top_left_latitude','');
    $columns[$i][] = new column('top_left_longitude','');
    $columns[$i][] = new column('top_right_latitude','');
    $columns[$i][] = new column('top_right_longitude','');
    $columns[$i][] = new column('bottom_left_latitude','');
    $columns[$i][] = new column('bottom_left_longitude','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'plaques';
    $columns[] = array();
    $columns[$i][] = new column('plaque_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('event_package_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'quests';
    $columns[] = array();
    $columns[$i][] = new column('quest_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('active_icon_media_id','map');
    $columns[$i][] = new column('active_media_id','map');
    $columns[$i][] = new column('active_description','');
    $columns[$i][] = new column('active_notification_type','');
    $columns[$i][] = new column('active_function','');
    $columns[$i][] = new column('active_event_package_id','map');
    $columns[$i][] = new column('active_requirement_root_package_id','map');
    $columns[$i][] = new column('complete_icon_media_id','map');
    $columns[$i][] = new column('complete_media_id','map');
    $columns[$i][] = new column('complete_description','');
    $columns[$i][] = new column('complete_notification_type','');
    $columns[$i][] = new column('complete_function','');
    $columns[$i][] = new column('complete_event_package_id','map');
    $columns[$i][] = new column('complete_requirement_root_package_id','map');
    $columns[$i][] = new column('sort_index','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'requirement_and_packages';
    $columns[] = array();
    $columns[$i][] = new column('requirement_and_package_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'requirement_atoms';
    $columns[] = array();
    $columns[$i][] = new column('requirement_atom_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('requirement_and_package_id','map');
    $columns[$i][] = new column('bool_operator','');
    $columns[$i][] = new column('requirement','');
    $columns[$i][] = new column('content_id','special');
    $columns[$i][] = new column('distance','');
    $columns[$i][] = new column('qty','');
    $columns[$i][] = new column('latitude','');
    $columns[$i][] = new column('longitude','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'requirement_root_packages';
    $columns[] = array();
    $columns[$i][] = new column('requirement_root_package_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'scenes';
    $columns[] = array();
    $columns[$i][] = new column('scene_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('editor_x','');
    $columns[$i][] = new column('editor_y','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'tabs';
    $columns[] = array();
    $columns[$i][] = new column('tab_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('type','');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('content_id','special');
    $columns[$i][] = new column('info','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('sort_index','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'tags';
    $columns[] = array();
    $columns[$i][] = new column('tag_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('tag','');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('visible','');
    $columns[$i][] = new column('curated','');
    $columns[$i][] = new column('sort_index','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'triggers';
    $columns[] = array();
    $columns[$i][] = new column('trigger_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('instance_id','map');
    $columns[$i][] = new column('scene_id','map');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('type','');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('title','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('latitude','');
    $columns[$i][] = new column('longitude','');
    $columns[$i][] = new column('distance','');
    $columns[$i][] = new column('wiggle','');
    $columns[$i][] = new column('show_title','');
    $columns[$i][] = new column('hidden','');
    $columns[$i][] = new column('trigger_on_enter','');
    $columns[$i][] = new column('qr_code','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $columns[$i][] = new column('infinite_distance','');
    $i++;

    $tables[] = 'web_hooks';
    $columns[] = array();
    $columns[$i][] = new column('web_hook_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('url','');
    $columns[$i][] = new column('incoming','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    $tables[] = 'web_pages';
    $columns[] = array();
    $columns[$i][] = new column('web_page_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('url','');
    $columns[$i][] = new column('created','timestamp');
    $columns[$i][] = new column('last_active','timestamp');
    $i++;

    //final layer of indirection
    $coltablemap['game_id'] = 'games';
    $coltablemap['media_id']                       = 'media';
    $coltablemap['icon_media_id']                  = 'media';
    $coltablemap['complete_media_id']              = 'media';
    $coltablemap['complete_icon_media_id']         = 'media';
    $coltablemap['active_media_id']                = 'media';
    $coltablemap['active_icon_media_id']           = 'media';
    $coltablemap['notebook_trigger_icon_media_id'] = 'media';
    $coltablemap['trigger_icon_media_id']          = 'media';
    $coltablemap['requirement_root_package_id']                  = 'requirement_root_packages';
    $coltablemap['complete_requirement_root_package_id']         = 'requirement_root_packages';
    $coltablemap['active_requirement_root_package_id']           = 'requirement_root_packages';
    $coltablemap['notebook_trigger_requirement_root_package_id'] = 'requirement_root_packages';
    $coltablemap['trigger_requirement_root_package_id']          = 'requirement_root_packages';
    $coltablemap['requirement_and_package_id']                   = 'requirement_and_packages';
    $coltablemap['event_package_id']          = 'event_packages';
    $coltablemap['active_event_package_id']   = 'event_packages';
    $coltablemap['complete_event_package_id'] = 'event_packages';
    $coltablemap['scene_id']                  = 'scenes';
    $coltablemap['notebook_trigger_scene_id'] = 'scenes';
    $coltablemap['intro_scene_id']            = 'scenes';
    $coltablemap['trigger_scene_id']          = 'scenes';
    $coltablemap['instance_id'] = 'instances';
    $coltablemap['tag_id'] = 'tags';
    $coltablemap['note_id'] = 'notes';
    $coltablemap['factory_id'] = 'factories';
    $coltablemap['dialog_id']               = 'dialogs';
    $coltablemap['dialog_character_id']     = 'dialog_characters';
    $coltablemap['intro_dialog_script_id']  = 'dialog_scripts';
    $coltablemap['parent_dialog_script_id'] = 'dialog_scripts';
  }

  public static function duplicateGame($pack)
  {
    $pack->auth->game_id = $pack->game_id;
    $pack->auth->permission = "read_write";
    if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

    $pack->import = duplicate::exportGameData($pack);
    return duplicate::importGameData($pack);
  }

  private static function rcopy($src,$dst)
  {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ($file = readdir($dir)))
    {
      if(($file != '.') && ($file != '..'))
      {
        if(is_dir($src.'/'.$file))
          duplicate::rcopy($src.'/'.$file,$dst.'/'.$file);
        else
          copy($src.'/'.$file,$dst.'/'.$file);
      }
    }
    closedir($dir);
  }

  private static function rdel($dirPath)
  {
    if(!is_dir($dirPath))
      throw new InvalidArgumentException("$dirPath must be a directory");
    if(substr($dirPath, strlen($dirPath) - 1, 1) != '/')
      $dirPath .= '/';

    $files = glob($dirPath . '*', GLOB_MARK);
    foreach($files as $file)
    {
      if(is_dir($file))
        duplicate::rdel($file);
      else
        unlink($file);
    }
    rmdir($dirPath);
  }


  private static function rzip($srcfolder, $destzip)
  {
    $rootPath = realpath($srcfolder);

    $zip = new ZipArchive();
    $zip->open($destzip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

    foreach($files as $name => $file)
    {
      if(!$file->isDir())
      {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        $zip->addFile($filePath, $relativePath);
      }
    }

    $zip->close();
  }

  public static function exportGame($pack)
  {
    $pack->auth->game_id = $pack->game_id;
    $pack->auth->permission = "read_write";
    if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

    $export = duplicate::exportGameData($pack);

    $tmp_export_folder = $export->game_id."_export_".date("mdY_Gis");
    $fs_tmp_export_folder = Config::v2_gamedata_folder."/".$tmp_export_folder;
    if(file_exists($fs_tmp_export_folder)) duplicate::rdel($fs_tmp_export_folder);
    mkdir($fs_tmp_export_folder,0777);
    $jsonfile = fopen($fs_tmp_export_folder."/export.json","w");
    fwrite($jsonfile,json_encode($export));
    fclose($jsonfile);

    duplicate::rcopy(Config::v2_gamedata_folder."/".$export->game_id,$fs_tmp_export_folder."/gamedata");
    duplicate::rzip($fs_tmp_export_folder,$fs_tmp_export_folder.".zip");
    duplicate::rdel($fs_tmp_export_folder);

    return new return_package(0, Config::v2_gamedata_www_path."/".$tmp_export_folder.".zip");
  }

  /*
    {
      game_id:123,
      table_data:
      [
        {
          table:"games",
          columns:
          [
            {
              name:"game_id",
              meta:"id",
            },
            {
              name:"name",
              meta:"",
            },
            ...
          ]
          data:
          [
            {
              game_id:123,
              name:"my game",
              ...
            },
            ...
          ]
        },
        ...
      ]
    }
  */
  private static function exportGameData($pack)
  {
    $tables = array();
    $columns = array();
    $coltablemap = array();
    duplicate::getSchema($tables,$columns,$coltablemap);

    $table_data = array();

    for($i = 0; $i < count($tables); $i++)
    {
      $table = $tables[$i];
      $cols = $columns[$i];
      $old_data = dbconnection::queryArrayAssoc("SELECT * FROM {$table} WHERE game_id = '{$pack->game_id}';");
      $table_data[] = new table_data($table, $cols, $old_data);
    }

    $package = new stdClass();
    $package->game_id = $pack->game_id;
    $package->table_data = $table_data;
    $db_upgrade = dbconnection::queryObject("SELECT * FROM db_upgrades ORDER BY version_major DESC, version_minor DESC LIMIT 1");
    if(!$db_upgrade) {
        $package->db_upgrade = null;
    } else {
        $package->db_upgrade = $db_upgrade->version_major . "." . $db_upgrade->version_minor;
    }
    return $package;
  }

  public static function importGame($pack)
  {
    $pack->auth->permission = "read_write";
    if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

    $zipbasename = substr($pack->zip_name,0,strrpos($pack->zip_name,".zip"));
    $tmp_import_folder = Config::v2_gamedata_folder."/".$zipbasename."_import_".date("mdY_Gis");
    if(file_exists($tmp_import_folder)) return "no";

    if ( isset($pack->raw_upload_id) ) {
      $tmp_zip = Config::raw_uploads_folder . '/' . $pack->raw_upload_id;
    } else if ( isset($pack->zip_data) ) {
      $tmp_zip = $tmp_import_folder.".zip";
      //save data to zip
      $zipfile = fopen($tmp_zip,"w");
      fwrite($zipfile,base64_decode($pack->zip_data));
      fclose($zipfile);
    } else {
      return new return_package(1, NULL, "No ZIP data given to import a game from");
    }

    //unzip to folder
    $zip = new ZipArchive;
    if($zip->open($tmp_zip) === TRUE)
    {
      $zip->extractTo($tmp_import_folder);
      $zip->close();
    }
    unlink($tmp_zip); //get rid of zip

    unset($pack->zip_data); //for readability in debug

    //read text
    $jsonfile = fopen($tmp_import_folder."/export.json", "r");
    $assoc_data = json_decode(fread($jsonfile,filesize($tmp_import_folder."/export.json")),true);
    fclose($jsonfile);

    //convert to non-assoc for non-data tables
    $import = new stdClass();
    $import->game_id = $assoc_data["game_id"];
    $import->table_data = array();

    for($i = 0; $i < count($assoc_data["table_data"]); $i++)
    {
      $import->table_data[$i] = new stdClass();
      $import->table_data[$i]->table = $assoc_data["table_data"][$i]["table"];
      $import->table_data[$i]->columns = array();
      for($j = 0; $j < count($assoc_data["table_data"][$i]["columns"]); $j++)
      {
        $import->table_data[$i]->columns[$j] = new stdClass();
        $import->table_data[$i]->columns[$j]->name = $assoc_data["table_data"][$i]["columns"][$j]["name"];
        $import->table_data[$i]->columns[$j]->meta = $assoc_data["table_data"][$i]["columns"][$j]["meta"];
      }
      $import->table_data[$i]->data = $assoc_data["table_data"][$i]["data"];
    }

    $pack->import = $import;
    $ret = duplicate::importGameData($pack);
    duplicate::rdel($tmp_import_folder); //get rid of zipto
    return $ret;
  }

  private static function importGameData($pack)
  {
    $tables = array(); //not actually used
    $columns = array(); //not actually used
    $coltablemap = array();
    duplicate::getSchema($tables,$columns,$coltablemap);

    $maps = array();
    $import = $pack->import;
    $game_id = $import->game_id;
    $table_data = $import->table_data;

    for($i = 0; $i < count($table_data); $i++)
    {
      $table = $table_data[$i]->table;
      $cols = $table_data[$i]->columns;
      $old_data = $table_data[$i]->data;

      $maps[$table] = array();
      $maps[$table][0] = 0;

      for($j = 0; $j < count($old_data); $j++)
      {
        $old_datum = $old_data[$j];
        $col_query = "";
        $val_query = "";
        $old_id = 0;
        for($k = 0; $k < count($cols); $k++)
        {
          $col = $cols[$k];
          $old_datum[$col->name] = addslashes($old_datum[$col->name]); //best thing I can think of for sanitation...

          if($col->meta == 'id') //id value- let auto-increment handle
          {
            $old_id = $old_datum[$col->name];//just store old id
          }
          else if($col->meta == 'timestamp')
          {
            if($col_query != "")
            {
              $col_query .= ', ';
              $val_query .= ', ';
            }
            $col_query .= "{$col->name}";
            $val_query .= "CURRENT_TIMESTAMP";
          }
          else //just copy value- if meta == 'map' || 'special', will get overwritten in second pass anyways
          {
            if($col_query != "")
            {
              $col_query .= ', ';
              $val_query .= ', ';
            }
            $col_query .= "{$col->name}";
            if($col->meta == 'special')
            {
              if($col->name == 'siftr_url') $val_query .= "NULL"; //needs to be NULL because "" or "0" is non-unique
              else $val_query .= "'0'";
            }
            else if($col->meta == 'map') $val_query .= "'0'"; //set to 0 so botched duplicate won't ruin other games
            else $val_query .= "'{$old_datum[$col->name]}'";
          }
        }
        if($pack->verbose) echo("INSERT INTO {$table} ({$col_query}) VALUES ({$val_query});");
        $maps[$table][$old_id] = dbconnection::queryInsert("INSERT INTO {$table} ({$col_query}) VALUES ({$val_query});");
        if($pack->verbose) echo(" (id: {$maps[$table][$old_id]})\n");
      }
    }

    //NOTE- must do setup normally handled by games::createGame
    dbconnection::query("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$maps['games'][$game_id]}','{$pack->auth->user_id}',CURRENT_TIMESTAMP);");
    mkdir(Config::v2_gamedata_folder."/{$maps['games'][$game_id]}",0777);

    //second pass- fill in bogus mappings with known maps
    for($i = 0; $i < count($table_data); $i++)
    {
      $table = $table_data[$i]->table;
      $cols = $table_data[$i]->columns;
      $old_data = $table_data[$i]->data;

      for($j = 0; $j < count($old_data); $j++)
      {
        $old_datum = $old_data[$j];
        $update_query = "";
        $id_col = "";
        $old_id = 0;
        for($k = 0; $k < count($cols); $k++)
        {
          $col = $cols[$k];
          $old_datum[$col->name] = addslashes($old_datum[$col->name]); //best thing I can think of for sanitation...

          if($col->meta == 'id') //id value- auto-increment handled its creation
          {
            $id_col = $col->name;
            $old_id = $old_datum[$col->name]; //just store old id to find new id to update
          }
          else if($col->meta == '') //boring value- already direct copied in first pass- ignore
          {
          }
          else if($col->meta == 'map') //references other table- update mapped value
          {
            if($update_query != '')
              $update_query .= ', ';

            $update_query .= "{$col->name} = '{$maps[$coltablemap[$col->name]][$old_datum[$col->name]]}'";
          }
          else if($col->meta == 'special')
          {
            if($update_query != '')
              $update_query .= ', ';

            if($col->name == 'siftr_url')
            {
              $update_query .= "siftr_url = NULL";
            }
            else if($col->name == 'file_folder')
            {
              //copy media to new folder

              $filenametitle = substr($old_datum['file_name'],0,strrpos($old_datum['file_name'],'.'));
              $filenameext = substr($old_datum['file_name'],strrpos($old_datum['file_name'],'.'));
              $old_file_path = Config::v2_gamedata_folder."/".$old_datum['file_folder']."/".$old_datum['file_name'];
              $new_file_path = Config::v2_gamedata_folder."/".$maps['games'][$game_id]."/".$old_datum['file_name'];
              $new_file_path_128 = Config::v2_gamedata_folder."/".$maps['games'][$game_id]."/".$filenametitle."_128".$filenameext;

              if(file_exists($old_file_path))
              {
                copy($old_file_path,$new_file_path);

                if(($filenameext == ".jpg" || $filenameext == ".png" || $filenameext == ".gif"))
                {
                  try
                  {
                    if(exif_imagetype($new_file_path))
                    {
                        $image = new Imagick($new_file_path);
                        //aspect fill to 128x128
                        $w = $image->getImageWidth();
                        $h = $image->getImageHeight();
                        if($w < $h) $image->thumbnailImage(128, (128/$w)*$h, 1, 1);
                        else        $image->thumbnailImage((128/$h)*$w, 128, 1, 1);
                        //crop around center
                        $w = $image->getImageWidth();
                        $h = $image->getImageHeight();
                        $image->cropImage(128, 128, ($w-128)/2, ($h-128)/2);
                        $image->writeImage($new_file_path_128);
                    }
                  }
                  catch (ImagickException $e) 
                  {
                  //do nothing
                  }
                }
              }

              $update_query .= "file_folder = '{$maps['games'][$game_id]}'";
            }
            else if($col->name == 'content_id')
            {
              if($table == 'events')
                $update_query .= "content_id = '{$maps['items'][$old_datum['content_id']]}'";
              else if($table == 'requirement_atoms')
              {
                switch($old_datum['requirement'])
                {
                  case 'PLAYER_HAS_ITEM':
                  case 'PLAYER_HAS_TAGGED_ITEM':
                  case 'GAME_HAS_ITEM':
                  case 'GAME_HAS_TAGGED_ITEM':
                  case 'PLAYER_VIEWED_ITEM':
                    $update_query .= "content_id = '{$maps['items'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAYER_VIEWED_PLAQUE':
                    $update_query .= "content_id = '{$maps['plaques'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAYER_VIEWED_DIALOG':
                    $update_query .= "content_id = '{$maps['dialogs'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAYER_VIEWED_DIALOG_SCRIPT':
                    $update_query .= "content_id = '{$maps['dialog_scripts'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAYER_VIEWED_WEB_PAGE':
                    $update_query .= "content_id = '{$maps['web_pages'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAYER_HAS_COMPLETED_QUEST':
                    $update_query .= "content_id = '{$maps['quests'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK':
                    $update_query .= "content_id = '{$maps['web_hooks'][$old_datum['content_id']]}'";
                    break;
                  case 'ALWAYS_TRUE':
                  case 'ALWAYS_FALSE':
                  case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM':
                  case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE':
                  case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO':
                  case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO':
                  case 'PLAYER_HAS_NOTE':
                  case 'PLAYER_HAS_NOTE_WITH_TAG':
                  case 'PLAYER_HAS_NOTE_WITH_LIKES':
                  case 'PLAYER_HAS_NOTE_WITH_COMMENTS':
                  case 'PLAYER_HAS_GIVEN_NOTE_COMMENTS':
                  default:
                    $update_query .= "content_id = '{$old_datum['content_id']}'";
                    break;
                }
              }
              else if($table == 'tabs')
              {
                switch($old_datum['type'])
                {
                  case 'NOTE':
                    $update_query .= "content_id = '{$maps['notes'][$old_datum['content_id']]}'";
                    break;
                  case 'DIALOG':
                    $update_query .= "content_id = '{$maps['dialogs'][$old_datum['content_id']]}'";
                    break;
                  case 'ITEM':
                    $update_query .= "content_id = '{$maps['items'][$old_datum['content_id']]}'";
                    break;
                  case 'PLAQUE':
                    $update_query .= "content_id = '{$maps['plaques'][$old_datum['content_id']]}'";
                    break;
                  case 'WEB_PAGE':
                    $update_query .= "content_id = '{$maps['web_pages'][$old_datum['content_id']]}'";
                    break;
                  case 'MAP':
                  case 'DECODER':
                  case 'SCANNER':
                  case 'QUESTS':
                  case 'INVENTORY':
                  case 'PLAYER':
                  case 'NOTEBOOK':
                  default:
                    $update_query .= "content_id = '{$old_datum['content_id']}'";
                    break;
                }
              }
            }
            else if($col->name == 'object_id')
            {
              switch($old_datum['object_type'])
              {
                case 'PLAQUE':
                  $update_query .= "object_id = '{$maps['plaques'][$old_datum['object_id']]}'";
                  break;
                case 'ITEM':
                  $update_query .= "object_id = '{$maps['items'][$old_datum['object_id']]}'";
                  break;
                case 'DIALOG':
                  $update_query .= "object_id = '{$maps['dialogs'][$old_datum['object_id']]}'";
                  break;
                case 'WEB_PAGE':
                  $update_query .= "object_id = '{$maps['web_pages'][$old_datum['object_id']]}'";
                  break;
                case 'NOTE':
                  $update_query .= "object_id = '{$maps['notes'][$old_datum['object_id']]}'";
                  break;
                case 'FACTORY':
                  $update_query .= "object_id = '{$maps['factories'][$old_datum['object_id']]}'";
                  break;
                case 'SCENE':
                  $update_query .= "object_id = '{$maps['scenes'][$old_datum['object_id']]}'";
                  break;
                default:
                  $update_query .= "object_id = '{$old_datum['object_id']}'";
                  break;
              }
            }
            else if($col->name == 'link_id')
            {
              switch($old_datum['link_type'])
              {
                case 'EXIT_TO_PLAQUE':
                  $update_query .= "link_id = '{$maps['plaques'][$old_datum['link_id']]}'";
                  break;
                case 'EXIT_TO_ITEM':
                  $update_query .= "link_id = '{$maps['items'][$old_datum['link_id']]}'";
                  break;
                case 'EXIT_TO_WEB_PAGE':
                  $update_query .= "link_id = '{$maps['web_pages'][$old_datum['link_id']]}'";
                  break;
                case 'EXIT_TO_DIALOG':
                  $update_query .= "link_id = '{$maps['dialogs'][$old_datum['link_id']]}'";
                  break;
                case 'EXIT_TO_TAB':
                  $update_query .= "link_id = '{$maps['tabs'][$old_datum['link_id']]}'";
                  break;
                case 'DIALOG_SCRIPT':
                  $update_query .= "link_id = '{$maps['dialog_scripts'][$old_datum['link_id']]}'";
                  break;
                case 'EXIT':
                default:
                  $update_query .= "link_id = '{$old_datum['link_id']}'";
                  break;
              }
            }
          }
        }
        if($update_query != "")
        {
          if($pack->verbose) echo("UPDATE {$table} SET {$update_query} WHERE {$id_col} = '{$maps[$table][$old_id]}';");
          dbconnection::query("UPDATE {$table} SET {$update_query} WHERE {$id_col} = '{$maps[$table][$old_id]}';");
          if($pack->verbose) echo(" (id: {$maps[$table][$old_id]})\n");
        }
      }
    }

    $pack->game_id = $maps['games'][$game_id];
    return games::getGame($pack);
  }

}
?>
