<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("games.php");
require_once("instances.php");
require_once("triggers.php");
require_once("quests.php");
require_once("overlays.php");
require_once("tabs.php");
require_once("dialogs.php");
require_once("requirements.php");
require_once("util.php");
require_once("return_package.php");

class log extends dbconnection
{
  public static function addLog($pack)
  {
    $pack->auth->permission = "read_write";
    if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
    //needs impl
    return new return_package(0);
  }

  public static function getLogsForGame($pack)
  {
    $pack->auth->permission = "read_write";
    if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
    $logs = dbconnection::queryArray("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND deleted = 0;");

    if($pack->human) $logs = log::humanizeLogs($logs);

    return new return_package(0, $logs);
  }

  public static function getLogsForPlayer($pack)
  {
    $pack->auth->permission = "read_write";
    if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
    $logs = dbconnection::queryArray("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND deleted = 0;");

    if($pack->human) $logs = log::humanizeLogs($logs);

    return new return_package(0, $logs);
  }

  //NOTE- only works if not multiple game's entries in given list
  private static function humanizeLogs($logs)
  {
    if(count($logs) == 0) return $logs;

    //just snatch game id info from first avail log
    $gid = 0;
    for($i = 0; $i < count($logs) && !$gid; $i++)
      $gid = $logs[$i]->game_id;

    $item_list = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$gid}';");
    $item_map = array(); for($i = 0; $i < count($item_list); $i++) $item_map[$item_list[$i]->item_id] = $item_list[$i];
    $plaque_list = dbconnection::queryArray("SELECT * FROM plaques WHERE game_id = '{$gid}';");
    $plaque_map = array(); for($i = 0; $i < count($plaque_list); $i++) $plaque_map[$plaque_list[$i]->plaque_id] = $plaque_list[$i];
    $dialog_list = dbconnection::queryArray("SELECT * FROM dialogs WHERE game_id = '{$gid}';");
    $dialog_map = array(); for($i = 0; $i < count($dialog_list); $i++) $dialog_map[$dialog_list[$i]->dialog_id] = $dialog_list[$i];
    $dialog_script_list = dbconnection::queryArray("SELECT * FROM dialog_scripts WHERE game_id = '{$gid}';");
    $dialog_script_map = array(); for($i = 0; $i < count($dialog_script_list); $i++) $dialog_script_map[$dialog_script_list[$i]->dialog_script_id] = $dialog_script_list[$i];
    $web_page_list = dbconnection::queryArray("SELECT * FROM web_pages WHERE game_id = '{$gid}';");
    $web_page_map = array(); for($i = 0; $i < count($web_page_list); $i++) $web_page_map[$web_page_list[$i]->web_page_id] = $web_page_list[$i];
    $note_list = dbconnection::queryArray("SELECT * FROM notes WHERE game_id = '{$gid}';");
    $note_map = array(); for($i = 0; $i < count($note_list); $i++) $note_map[$note_list[$i]->note_id] = $note_list[$i];
    $trigger_list = dbconnection::queryArray("SELECT * FROM triggers WHERE game_id = '{$gid}';");
    $trigger_map = array(); for($i = 0; $i < count($trigger_list); $i++) $trigger_map[$trigger_list[$i]->trigger_id] = $trigger_list[$i];
    $instance_list = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$gid}';");
    $instance_map = array(); for($i = 0; $i < count($instance_list); $i++) $instance_map[$instance_list[$i]->instance_id] = $instance_list[$i];
    $event_package_list = dbconnection::queryArray("SELECT * FROM event_packages WHERE game_id = '{$gid}';");
    $event_package_map = array(); for($i = 0; $i < count($event_package_list); $i++) $event_package_map[$event_package_list[$i]->event_package_id] = $event_package_list[$i];
    $scene_list = dbconnection::queryArray("SELECT * FROM scenes WHERE game_id = '{$gid}';");
    $scene_map = array(); for($i = 0; $i < count($scene_list); $i++) $scene_map[$scene_list[$i]->scene_id] = $scene_list[$i];
    $quest_list = dbconnection::queryArray("SELECT * FROM quests WHERE game_id = '{$gid}';");
    $quest_map = array(); for($i = 0; $i < count($quest_list); $i++) $quest_map[$quest_list[$i]->quest_id] = $quest_list[$i];
    $tab_list = dbconnection::queryArray("SELECT * FROM tabs WHERE game_id = '{$gid}';");
    $tab_map = array(); for($i = 0; $i < count($tab_list); $i++) $tab_map[$tab_list[$i]->tab_id] = $tab_list[$i];

    for($i = 0; $i < count($logs); $i++)
    {
      $l = $logs[$i];
      switch($l->event_type)
      {
        case 'NONE':
          $l->human = "Null Log";
          break;
        case 'LOG_IN':
          $l->human = "User Logged In";
          break;
        case 'BEGIN_GAME':
          $l->human = "User Began Game";
          break;
        case 'RESET_GAME':
          $l->human = "User Reset Game";
          break;
        case 'MOVE':
          $l->human = "User Moved";
          break;
        case 'RECEIVE_ITEM':
          $l->human = "User Received {$l->qty} {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'LOSE_ITEM':
          $l->human = "User Lost {$l->qty} {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'GAME_RECEIVE_ITEM':
          $l->human = "Game Received {$l->qty} {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'GAME_LOSE_ITEM':
          $l->human = "Game Lost {$l->qty} {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'GROUP_RECEIVE_ITEM':
          $l->human = "Group Received {$l->qty} {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'GROUP_LOSE_ITEM':
          $l->human = "Group Lost {$l->qty} {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'VIEW_TAB':
          $l->human = "User Viewed {$tab_map[$l->content_id]->name} (Tab)";
          break;
        case 'VIEW_INSTANCE':
          $l->human = "User Viewed {$instance_map[$l->content_id]->name} (Instance)";
          break;
        case 'VIEW_PLAQUE':
          $l->human = "User Viewed {$plaque_map[$l->content_id]->name} (Plaque)";
          break;
        case 'VIEW_ITEM':
          $l->human = "User Viewed {$item_map[$l->content_id]->name} (Item)";
          break;
        case 'VIEW_DIALOG':
          $l->human = "User Viewed {$dialog_map[$l->content_id]->name} (Dialog)";
          break;
        case 'VIEW_DIALOG_SCRIPT':
          $l->human = "User Viewed {$dialog_script_map[$l->content_id]->text} (Dialog Script)";
          break;
        case 'VIEW_WEB_PAGE':
          $l->human = "User Viewed {$web_page_map[$l->content_id]->name} (Web Page)";
          break;
        case 'VIEW_NOTE':
          $l->human = "User Viewed {$note_map[$l->content_id]->name} (Note)";
          break;
        case 'TRIGGER_TRIGGER':
          $l->human = "User Triggered {$trigger_map[$l->content_id]->name} (Trigger)";
          break;
        case 'CHANGE_SCENE':
          $l->human = "User Changed {$scene_map[$l->content_id]->name} (Scene)";
          break;
        case 'RUN_EVENT_PACKAGE':
          $l->human = "User Ran {$event_package_map[$l->content_id]->name} (Event Package)";
          break;
        case 'COMPLETE_QUEST':
          $l->human = "User Completed {$quest_map[$l->content_id]->name} (Quest)";
          break;
        case 'CREATE_NOTE':
          $l->human = "User Created {$note_map[$l->content_id]->name} (Note)";
          break;
        case 'GIVE_NOTE_LIKE':
          $l->human = "User Liked {$note_map[$l->content_id]->name} (Note)";
          break;
        case 'GET_NOTE_LIKE':
          $l->human = "User Got Liked {$note_map[$l->content_id]->name} (Note)";
          break;
        case 'GIVE_NOTE_COMMENT':
          $l->human = "User Commented {$note_map[$l->content_id]->name} (Note)";
          break;
        case 'GET_NOTE_COMMENT':
          $l->human = "User Got Commented {$note_map[$l->content_id]->name} (Note)";
          break;
        case 'UPLOAD_MEDIA_ITEM':
          $l->human = "User Uploaded Media";
          break;
        case 'UPLOAD_MEDIA_ITEM_IMAGE':
          $l->human = "User Uploaded Image";
          break;
        case 'UPLOAD_MEDIA_ITEM_AUDIO':
          $l->human = "User Uploaded Audio";
          break;
        case 'UPLOAD_MEDIA_ITEM_VIDEO':
          $l->human = "User Uploaded Video";
          break;
        case 'RECEIVE_WEBHOOK':
          $l->human = "User Received Webhook";
          break;
        case 'SEND_WEBHOOK':
          $l->human = "User Sent Webhook";
          break;
      }
    }
    return $logs;
  }
}

?>
