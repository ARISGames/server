<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");
require_once("instances.php");

class events extends dbconnection
{
    public function createEventPackage($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->event_package_id = dbconnection::queryInsert(
            "INSERT INTO event_packages (".
            "game_id,".
            (isset($pack->name)          ? "name,"          : "").
            (isset($pack->icon_media_id) ? "icon_media_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)          ? "'".addslashes($pack->name)."',"          : "").
            (isset($pack->icon_media_id) ? "'".addslashes($pack->icon_media_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        for($i = 0; $pack->events && $i < count($pack->events); $i++)
        {
            $pack->events[$i]->event_package_id = $pack->event_package_id;
            $pack->events[$i]->game_id = $pack->game_id;
            $pack->events[$i]->auth = $pack->auth;
            events::createEvent($pack->events[$i]);
        }

        return events::getEventPackage($pack);
    }

    //Takes in event JSON, all fields optional except game_id + user_id + key
    public static function createEvent($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(isset($pack->event) && $pack->event == 'GIVE_ITEM') $pack->event = 'GIVE_ITEM_PLAYER';
        if(isset($pack->event) && $pack->event == 'TAKE_ITEM') $pack->event = 'TAKE_ITEM_PLAYER';

        $pack->event_id = dbconnection::queryInsert(
            "INSERT INTO events (".
            "game_id,".
            "event_package_id,".
            (isset($pack->event)      ? "event,"      : "").
            (isset($pack->qty)        ? "qty,"        : "").
            (isset($pack->content_id) ? "content_id," : "").
            (isset($pack->script)     ? "script,"     : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->event_package_id)."',".
            (isset($pack->event)      ? "'".addslashes($pack->event)."',"      : "").
            (isset($pack->qty)        ? "'".addslashes($pack->qty)."',"        : "").
            (isset($pack->content_id) ? "'".addslashes($pack->content_id)."'," : "").
            (isset($pack->script)     ? "'".addslashes($pack->script)."',"     : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return events::getEvent($pack);
    }

    public function updateEventPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$pack->event_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        $auth = $pack->auth; //save for later (getEventPackagePack unsets it)

        if(!$pack->event_package_id) return;

        dbconnection::query(
            "UPDATE event_packages SET ".
            (isset($pack->name)          ? "name          = '".addslashes($pack->name)."', "          : "").
            (isset($pack->icon_media_id) ? "icon_media_id = '".addslashes($pack->icon_media_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE event_package_id = '".addslashes($pack->event_package_id)."'"
        );

        $reqEvents = $pack->events;
        $curEvents = events::getEventPackage($pack)->data->events;
        for($i = 0; $i < count($reqEvents); $i++)
        {
          $reqEvents[$i]->game_id = $pack->auth->game_id;
          $reqEvents[$i]->event_package_id = $pack->event_package_id;
          $reqEvents[$i]->auth = $auth;
        }
        for($i = 0; $i < count($curEvents); $i++)
        {
          $curEvents[$i]->game_id = $pack->auth->game_id;
          $curEvents[$i]->event_package_id = $pack->event_package_id;
          $curEvents[$i]->auth = $auth;
        }

        $eventsToDelete = array();
        $eventsToAdd = array();
        $eventsToUpdate = array();
        //find to-update and to-delete
        for($i = 0; $i < count($curEvents); $i++)
        {
            $found = false;
            for($j = 0; $j < count($reqEvents); $j++)
            {
                if($curEvents[$i]->event_id == $reqEvents[$j]->event_id)
                {
                    $eventsToUpdate[] = $reqEvents[$j];
                    $found = true;
                }
            }
            if(!$found) $eventsToDelete[] = $curEvents[$i];
        }
        //find to-add
        for($i = 0; $i < count($reqEvents); $i++)
        {
            $found = false;
            if($reqEvents[$i]->event_id)
            {
              for($j = 0; $j < count($curEvents); $j++)
              {
                  if($reqEvents[$i]->event_id == $curEvents[$j]->event_id)
                      $found = true;
              }
            }
            if(!$found) $eventsToAdd[] = $reqEvents[$i];
        }

        for($i = 0; $i < count($eventsToDelete); $i++) events::deleteEvent($eventsToDelete[$i]);
        for($i = 0; $i < count($eventsToUpdate); $i++) events::updateEvent($eventsToUpdate[$i]);
        for($i = 0; $i < count($eventsToAdd);    $i++) events::createEvent($eventsToAdd[$i]);

        return events::getEventPackage($pack);
    }

    //Takes in game JSON, all fields optional except event_id + user_id + key
    public static function updateEvent($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE events SET ".
            (isset($pack->event)      ? "event      = '".addslashes($pack->event)."', "      : "").
            (isset($pack->qty)        ? "qty        = '".addslashes($pack->qty)."', "        : "").
            (isset($pack->content_id) ? "content_id = '".addslashes($pack->content_id)."', " : "").
            (isset($pack->script)     ? "script     = '".addslashes($pack->script)."', "     : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE event_id = '{$pack->event_id}'"
        );

        return events::getEvent($pack);
    }

    private static function eventObjectFromSQL($sql_event)
    {
        if(!$sql_event) return $sql_event;
        $event = new stdClass();
        $event->event_id         = $sql_event->event_id;
        $event->game_id          = $sql_event->game_id;
        $event->event_package_id = $sql_event->event_package_id;
        $event->event            = $sql_event->event;
        $event->qty              = $sql_event->qty;
        $event->content_id       = $sql_event->content_id;
        $event->script           = $sql_event->script;
        if($event->event == 'GIVE_ITEM_PLAYER') $event->event = 'GIVE_ITEM';
        if($event->event == 'TAKE_ITEM_PLAYER') $event->event = 'TAKE_ITEM';

        return $event;
    }

    public function getEventPackage($pack)
    {
        $event_package = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$pack->event_package_id}'");
        $event_package->events = dbconnection::queryArray("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'");

        for($i = 0; $i < count($event_package->events); $i++)
          $event_package->events[$i] = events::eventObjectFromSQL($event_package->events[$i]);

        return new return_package(0,$event_package);
    }

    public function getEventPackagesForGame($pack)
    {
      $event_packages = dbconnection::queryArray("SELECT * FROM event_packages WHERE game_id = '{$pack->game_id}'");
      return new return_package(0,$event_packages);
    }

    public static function getEvent($pack)
    {
        $sql_event = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}' LIMIT 1");
        return new return_package(0,events::eventObjectFromSQL($sql_event));
    }

    public static function getEventsForEventPackage($pack)
    {
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'");
        $events = array();
        for($i = 0; $i < count($sql_events); $i++)
            if($ob = events::eventObjectFromSQL($sql_events[$i])) $events[] = $ob;

        return new return_package(0,$events);
    }

    public static function getEventsForGame($pack)
    {
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE game_id = '{$pack->game_id}'");
        $events = array();
        for($i = 0; $i < count($sql_events); $i++)
            if($ob = events::eventObjectFromSQL($sql_events[$i])) $events[] = $ob;

        return new return_package(0,$events);
    }

    public static function getEventsForObject($pack)
    {
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE object_type = '{$pack->object_type}' AND content_id = '{$pack->content_id}'");
        $events = array();
        for($i = 0; $i < count($sql_events); $i++)
            $events[] = events::eventObjectFromSQL($sql_events[$i]);

        return new return_package(0,$events);
    }

    public static function deleteEventPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM events_packages WHERE event_package_id = '{$pack->event_package_id}' LIMIT 1");

        //cleanup
        $events = dbconnection::queryArray("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'");
        for($i = 0; $i < count($events); $i++)
        {
            $pack->event_id = $events[$i]->event_id;
            events::deleteEvent($pack);
        }

        dbconnection::query("UPDATE dialog_scripts SET event_package_id = 0 WHERE event_package_id = '{$pack->event_package_id}'");
        dbconnection::query("UPDATE plaques SET event_package_id = 0 WHERE event_package_id = '{$pack->event_package_id}'");
        dbconnection::query("UPDATE quests SET active_event_package_id = 0 WHERE active_event_package_id = '{$pack->event_package_id}'");
        dbconnection::query("UPDATE quests SET complete_event_package_id = 0 WHERE complete_event_package_id = '{$pack->event_package_id}'");

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'EVENT_PACKAGE' AND object_id = '{$pack->event_package_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstance($pack);
        }

        return new return_package(0);
    }

    public static function deleteEvent($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM events WHERE event_id = '{$pack->event_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
