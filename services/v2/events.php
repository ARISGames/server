<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class events extends dbconnection
{	
    public function createEventPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::createEventPackagePack($glob); }
    public function createEventPackagePack($pack)
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

        return events::getEventPackagePack($pack);
    }

    //Takes in event JSON, all fields optional except game_id + user_id + key
    public static function createEvent($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::createEventPack($glob); }
    public static function createEventPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->event_id = dbconnection::queryInsert(
            "INSERT INTO events (".
            "game_id,".
            "event_package_id,".
            (isset($pack->event)      ? "event,"      : "").
            (isset($pack->qty)        ? "qty,"        : "").
            (isset($pack->content_id) ? "content_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->event_package_id)."',".
            (isset($pack->event)      ? "'".addslashes($pack->event)."',"      : "").
            (isset($pack->qty)        ? "'".addslashes($pack->qty)."',"        : "").
            (isset($pack->content_id) ? "'".addslashes($pack->content_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return events::getEventPack($pack);
    }

    public function updateEventPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::updateEventPackagePack($glob); }
    public function updateEventPackagePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$pack->event_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->event_package_id) return;

        dbconnection::query(
            "UPDATE event_packages SET ".
            (isset($pack->name) ? "name = '".addslashes($pack->name)."', " : "").
            "last_updated = CURRENT_TIMESTAMP ".
            "WHERE event_package_id = '".addslashes($pack->event_package_id)."'"
        );

        return events::getEventPackagePack($pack);
    }

    //Takes in game JSON, all fields optional except event_id + user_id + key
    public static function updateEvent($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::updateEventPack($glob); }
    public static function updateEventPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE events SET ".
            (isset($pack->event)      ? "event      = '".addslashes($pack->event)."', "      : "").
            (isset($pack->qty)        ? "qty        = '".addslashes($pack->qty)."', "        : "").
            (isset($pack->content_id) ? "content_id = '".addslashes($pack->content_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE event_id = '{$pack->event_id}'"
        );

        return events::getEventPack($pack);
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

        return $event;
    }

    public function getEventPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::getEventPackagePack($glob); }
    public function getEventPackagePack($pack)
    {
        $sql_root = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$pack->event_package_id}'");
        $pack->event_package_id = $sql_root->event_package_id;
        $pack->game_id = $sql_root->game_id;
        $pack->name = $sql_root->name;

        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'");
        $pack->events = array();

        for($i = 0; $i < count($sql_events); $i++)
        {
            $pack->events[$i] = events::getEventPack($sql_events[$i])->data;
            unset($pack->events[$i]->event_package_id);
        }

        unset($pack->auth);
        return new return_package(0,$pack);
    }

    public static function getEvent($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::getEventPack($glob); }
    public static function getEventPack($pack)
    {
        $sql_event = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}' LIMIT 1");
        return new return_package(0,events::eventObjectFromSQL($sql_event));
    }

    public static function getEventsForEventPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::getEventsForEventPackagePack($glob); }
    public static function getEventsForEventPackagePack($pack)
    {
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE event_package_id = '{$pack->event_package_id}'");
        $events = array();
        for($i = 0; $i < count($sql_events); $i++)
            if($ob = events::eventObjectFromSQL($sql_events[$i])) $events[] = $ob;

        return new return_package(0,$events);
    }

    public static function getEventsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::getEventsForGamePack($glob); }
    public static function getEventsForGamePack($pack)
    {
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE game_id = '{$pack->game_id}'");
        $events = array();
        for($i = 0; $i < count($sql_events); $i++)
            if($ob = events::eventObjectFromSQL($sql_events[$i])) $events[] = $ob;

        return new return_package(0,$events);
    }

    public static function getEventsForObject($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::getEventsForObjectPack($glob); }
    public static function getEventsForObjectPack($pack)
    {
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE object_type = '{$pack->object_type}' AND content_id = '{$pack->content_id}'");
        $events = array();
        for($i = 0; $i < count($sql_events); $i++)
            $events[] = events::eventObjectFromSQL($sql_events[$i]);

        return new return_package(0,$events);
    }

    public static function deleteEvent($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::deleteEventPack($glob); }
    public static function deleteEventPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM events WHERE event_id = '{$pack->event_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
