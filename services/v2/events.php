<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class events extends dbconnection
{	
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
            (isset($pack->event)       ? "event,"       : "").
            (isset($pack->amount)      ? "amount,"      : "").
            (isset($pack->object_type) ? "object_type," : "").
            (isset($pack->object_id)   ? "object_id,"   : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->event)       ? "'".addslashes($pack->event)."',"       : "").
            (isset($pack->amount)      ? "'".addslashes($pack->amount)."',"      : "").
            (isset($pack->object_type) ? "'".addslashes($pack->object_type)."'," : "").
            (isset($pack->object_id)   ? "'".addslashes($pack->object_id)."',"   : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return events::getEventPack($pack);
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
            (isset($pack->event)       ? "event      = '".addslashes($pack->event)."', "        : "").
            (isset($pack->amount)      ? "amount      = '".addslashes($pack->amount)."', "      : "").
            (isset($pack->object_type) ? "object_type = '".addslashes($pack->object_type)."', " : "").
            (isset($pack->object_id)   ? "object_id   = '".addslashes($pack->object_id)."', "   : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE event_id = '{$pack->event_id}'"
        );

        return events::getEventPack($pack);
    }

    private static function eventObjectFromSQL($sql_event)
    {
        if(!$sql_event) return $sql_event;
        $event = new stdClass();
        $event->event_id    = $sql_event->event_id;
        $event->game_id     = $sql_event->game_id;
        $event->event       = $sql_event->event;
        $event->amount      = $sql_event->amount;
        $event->object_type = $sql_event->object_type;
        $event->object_id   = $sql_event->object_id;

        return $event;
    }

    public static function getEvent($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return events::getEventPack($glob); }
    public static function getEventPack($pack)
    {
        $sql_event = dbconnection::queryObject("SELECT * FROM events WHERE event_id = '{$pack->event_id}' LIMIT 1");
        return new return_package(0,events::eventObjectFromSQL($sql_event));
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
        $sql_events = dbconnection::queryArray("SELECT * FROM events WHERE object_type = '{$pack->object_type}' AND object_id = '{$pack->object_id}'");
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
