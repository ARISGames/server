<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

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
            (isset($pack->name) ? "name," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name) ? "'".addslashes($pack->name)."'," : "").
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

        if(!$pack->event_package_id) return;

        dbconnection::query(
            "UPDATE event_packages SET ".
            (isset($pack->name) ? "name = '".addslashes($pack->name)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE event_package_id = '".addslashes($pack->event_package_id)."'"
        );

        $auth = $pack->auth; //save for later (getEventPackagePack unsets it)
        $reqEvents = $pack->events;
        $curEvents = events::getEventPackage($pack)->data->events;

        $eventsToDelete = array();
        $eventsToAdd = array();
        $eventsToUpdate = array();
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
        for($i = 0; $i < count($reqEvents); $i++)
        {
            $found = false;
            for($j = 0; $j < count($curEvents); $j++)
            {
                if($reqEvents[$i]->event_id == $curEvents[$j]->event_id)
                    $found = true;
            }
            if(!$found) $eventsToAdd[] = $reqEvents[$i];
        }

        for($i = 0; $i < count($eventsToDelete); $i++)
        { $eventsToDelete[$i]->auth = $auth; events::deleteEvent($eventsToDelete[$i]); }
        for($i = 0; $i < count($eventsToUpdate); $i++)
        { $eventsToUpdate[$i]->auth = $auth; events::updateEvent($eventsToUpdate[$i]); }
        for($i = 0; $i < count($eventsToAdd); $i++)
        { $eventsToAdd[$i]->event_package_id = $pack->event_package_id; $eventsToAdd[$i]->auth = $auth; events::createEvent($eventsToAdd[$i]); }

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
        $sql_root = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$pack->event_package_id}'");
        $pack->event_package_id = $sql_root->event_package_id;
        $pack->game_id = $sql_root->game_id;
        $pack->name = $sql_root->name;

        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'");
        $pack->events = array();

        for($i = 0; $i < count($sql_events); $i++)
        {
            $pack->events[$i] = events::getEvent($sql_events[$i])->data;
            unset($pack->events[$i]->event_package_id);
        }

        unset($pack->auth);
        return new return_package(0,$pack);
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
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}'")->game_id;
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
