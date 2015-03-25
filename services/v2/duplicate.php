<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("media.php");
require_once("scenes.php");
require_once("return_package.php");

class duplicate extends dbconnection
{
  public static function duplicateGame($pack)
  {
    $pack->auth->game_id = $pack->game_id;
    $pack->auth->permission = "read_write";
    if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

    class column
    {
      public $name; public $meta;
      public function column($name, $meta)
      {
        $this->name = $name; $this->meta = $meta;
        return $this;
      }
    }

    $tables = array();
    $columns = array();
    $i = 0;

    //'id' = that tables identifier. gets changed (auto-inc) during migration, and must be recorded
    //'map' = a value that maps to the id of other migrated table (value changes during migration)
    //'' = nothing special- copy value as-is

    $tables[] = 'dialog_characters';
    $columns[] = array();
    $columns[$i][] = new column('dialog_character_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('title','');
    $columns[$i][] = new column('media_id','map');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'dialog_options';
    $columns[] = array();
    $columns[$i][] = new column('dialog_option_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('dialog_id','map');
    $columns[$i][] = new column('parent_dialog_script_id','map');
    $columns[$i][] = new column('prompt','');
    $columns[$i][] = new column('link_type','');
    $columns[$i][] = new column('link_id','map');
    $columns[$i][] = new column('link_info','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('sort_index','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'dialog_scripts';
    $columns[] = array();
    $columns[$i][] = new column('dialog_script_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('dialog_id','map');
    $columns[$i][] = new column('dialog_character_id','map');
    $columns[$i][] = new column('text','');
    $columns[$i][] = new column('event_package_id','map');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'dialogs';
    $columns[] = array();
    $columns[$i][] = new column('dialog_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('intro_dialog_script_id','map');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'event_packages';
    $columns[] = array();
    $columns[$i][] = new column('event_package_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'events';
    $columns[] = array();
    $columns[$i][] = new column('event_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('event_package_id','map');
    $columns[$i][] = new column('event','');
    $columns[$i][] = new column('content_id','map');
    $columns[$i][] = new column('qty','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'factories';
    $columns[] = array();
    $columns[$i][] = new column('factory_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('object_type','');
    $columns[$i][] = new column('object_id','map');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

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
    $columns[$i][] = new column('siftr_url','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'instances';
    $columns[] = array();
    $columns[$i][] = new column('instance_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('object_type','');
    $columns[$i][] = new column('object_id','map');
    $columns[$i][] = new column('qty','');
    $columns[$i][] = new column('infinite_qty','');
    $columns[$i][] = new column('factory_id','map');
    $columns[$i][] = new column('owner_id','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'media';
    $columns[] = array();
    $columns[$i][] = new column('media_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('user_id','');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('file_folder','');
    $columns[$i][] = new column('file_name','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'object_tags';
    $columns[] = array();
    $columns[$i][] = new column('object_tag_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('object_type','');
    $columns[$i][] = new column('object_id','map');
    $columns[$i][] = new column('tag_id','map');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'requirement_and_packages';
    $columns[] = array();
    $columns[$i][] = new column('requirement_and_package_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'requirement_atoms';
    $columns[] = array();
    $columns[$i][] = new column('requirement_atom_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('requirement_and_package_id','map');
    $columns[$i][] = new column('bool_operator','');
    $columns[$i][] = new column('requirement','');
    $columns[$i][] = new column('content_id','map');
    $columns[$i][] = new column('distance','');
    $columns[$i][] = new column('qty','');
    $columns[$i][] = new column('latitude','');
    $columns[$i][] = new column('longitude','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'requirement_root_packages';
    $columns[] = array();
    $columns[$i][] = new column('requirement_root_package_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'scenes';
    $columns[] = array();
    $columns[$i][] = new column('scene_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('editor_x','');
    $columns[$i][] = new column('editor_y','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'tabs';
    $columns[] = array();
    $columns[$i][] = new column('tab_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('type','');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('description','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('content_id','map');
    $columns[$i][] = new column('info','');
    $columns[$i][] = new column('requirement_root_package_id','map');
    $columns[$i][] = new column('sort_index','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
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
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    $tables[] = 'web_pages';
    $columns[] = array();
    $columns[$i][] = new column('web_page_id','id');
    $columns[$i][] = new column('game_id','map');
    $columns[$i][] = new column('name','');
    $columns[$i][] = new column('icon_media_id','map');
    $columns[$i][] = new column('url','');
    $columns[$i][] = new column('created','');
    $columns[$i][] = new column('last_active','');
    $i++;

    //final layer of indirection
    $coltablemap = array();
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
    $coltablemap['note_id'] = 'notes';
    $coltablemap['factory_id'] = 'factories';
    $coltablemap['dialog_id']               = 'dialogs';
    $coltablemap['dialog_character_id']     = 'dialog_characters';
    $coltablemap['intro_dialog_script_id']  = 'dialog_scripts';
    $coltablemap['parent_dialog_script_id'] = 'dialog_scripts';
    //special maps- point to different things based on context
    $coltablemap['content_id'] = '';
    $coltablemap['object_id']  = '';
    $coltablemap['link_id']    = '';

    $maps = array();
    $oldGameId = $pack->game_id;
    for($i = 0; $i < count($tables); $i++)
    {
      $table = $tables[$i];
      $cols = $columns[$i];

      $maps[$table] = array();
      $maps[$table][0] = 0;

      $old_data = dbconnection::queryArrayAssoc("SELECT * FROM {$table} WHERE game_id = '{$oldGameId}';");

      for($j = 0; $j < count($old_data); $j++)
      {
        $old_datum = $old_data[$j];
        $col_query = "";
        $val_query = "";
        $old_id = 0;
        for($k = 0; $k < count($cols); $k++)
        {
          $col = $cols[$k];
          if($col->meta == 'id') //id value- let auto-increment handle
          {
            $old_id = $old_datum[$col->name];//just store old id
          }
          else if($col->meta == '') //boring value- direct copy
          {
            if($col_query != "")
            {
              $col_query .= ", ";
              $val_query .= ", ";
            }
            $col_query .= "{$col->name}";
            $val_query .= "'{$old_datum[$col->name]}'";
          }
          else if($col->meta == 'map') //references other table- map value
          {
            if($col_query != "")
            {
              $col_query .= ", ";
              $val_query .= ", ";
            }
            $col_query .= "{$col->name}";
            if($coltablemap[$col->name] == '') //special case
            {
              
            }
            else
              $val_query .= "'{$maps[$coltablemap[$col->name]][$old_datum[$col->name]]}'";
          }
        }
        $maps[$table][$old_id] = dbconnection::queryInsert("INSERT INTO {$table} ({$col_query}) VALUES ({$val_query});");
      }
    }

    return games::getGame($pack);
  }
}
?>

