<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("requirements.php");
require_once("instances.php");

class triggers extends dbconnection
{
    //Takes in trigger JSON, all fields optional except user_id + key
    public static function createTrigger($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->trigger_id = dbconnection::queryInsert(
            "INSERT INTO triggers (".
            "game_id,".
            (isset($pack->instance_id)                 ? "instance_id,"                 : "").
            (isset($pack->scene_id)                    ? "scene_id,"                    : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            (isset($pack->type)                        ? "type,"                        : "").
            (isset($pack->name)                        ? "name,"                        : "").
            (isset($pack->title)                       ? "title,"                       : "").
            (isset($pack->icon_media_id)               ? "icon_media_id,"               : "").
            (isset($pack->latitude)                    ? "latitude,"                    : "").
            (isset($pack->longitude)                   ? "longitude,"                   : "").
            (isset($pack->distance)                    ? "distance,"                    : "").
            (isset($pack->infinite_distance)           ? "infinite_distance,"           : "").
            (isset($pack->wiggle)                      ? "wiggle,"                      : "").
            (isset($pack->show_title)                  ? "show_title,"                  : "").
            (isset($pack->hidden)                      ? "hidden,"                      : "").
            (isset($pack->trigger_on_enter)            ? "trigger_on_enter,"            : "").
            (isset($pack->qr_code)                     ? "qr_code,"                     : "").
            (isset($pack->seconds)                     ? "seconds,"                     : "").
            (isset($pack->ar_target_id)                ? "ar_target_id,"                : "").
            (isset($pack->ar_target_img_scale_x)       ? "ar_target_img_scale_x,"       : "").
            (isset($pack->ar_target_img_scale_y)       ? "ar_target_img_scale_y,"       : "").
            (isset($pack->beacon_uuid)                 ? "beacon_uuid,"                 : "").
            (isset($pack->beacon_major)                ? "beacon_major,"                : "").
            (isset($pack->beacon_minor)                ? "beacon_minor,"                : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            (isset($pack->instance_id)                 ? "'".addslashes($pack->instance_id)."',"                 : "").
            (isset($pack->scene_id)                    ? "'".addslashes($pack->scene_id)."',"                    : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            (isset($pack->type)                        ? "'".addslashes($pack->type)."',"                        : "").
            (isset($pack->name)                        ? "'".addslashes($pack->name)."',"                        : "").
            (isset($pack->title)                       ? "'".addslashes($pack->title)."',"                       : "").
            (isset($pack->icon_media_id)               ? "'".addslashes($pack->icon_media_id)."',"               : "").
            (isset($pack->latitude)                    ? "'".addslashes($pack->latitude)."',"                    : "").
            (isset($pack->longitude)                   ? "'".addslashes($pack->longitude)."',"                   : "").
            (isset($pack->distance)                    ? "'".addslashes($pack->distance)."',"                    : "").
            (isset($pack->infinite_distance)           ? "'".addslashes($pack->infinite_distance)."',"           : "").
            (isset($pack->wiggle)                      ? "'".addslashes($pack->wiggle)."',"                      : "").
            (isset($pack->show_title)                  ? "'".addslashes($pack->show_title)."',"                  : "").
            (isset($pack->hidden)                      ? "'".addslashes($pack->hidden)."',"                      : "").
            (isset($pack->trigger_on_enter)            ? "'".addslashes($pack->trigger_on_enter)."',"            : "").
            (isset($pack->qr_code)                     ? "'".addslashes($pack->qr_code)."',"                     : "").
            (isset($pack->seconds)                     ? "'".addslashes($pack->seconds)."',"                     : "").
            (isset($pack->ar_target_id)                ? "'".addslashes($pack->ar_target_id)."',"                : "").
            (isset($pack->ar_target_img_scale_x)       ? "'".addslashes($pack->ar_target_img_scale_x)."',"       : "").
            (isset($pack->ar_target_img_scale_y)       ? "'".addslashes($pack->ar_target_img_scale_y)."',"       : "").
            (isset($pack->beacon_uuid)                 ? "'".addslashes($pack->beacon_uuid)."',"                 : "").
            (isset($pack->beacon_major)                ? "'".addslashes($pack->beacon_major)."',"                : "").
            (isset($pack->beacon_minor)                ? "'".addslashes($pack->beacon_minor)."',"                : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return triggers::getTrigger($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateTrigger($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE triggers SET ".
            (isset($pack->instance_id)                 ? "instance_id                 = '".addslashes($pack->instance_id)."', "                 : "").
            (isset($pack->scene_id)                    ? "scene_id                    = '".addslashes($pack->scene_id)."', "                    : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            (isset($pack->type)                        ? "type                        = '".addslashes($pack->type)."', "                        : "").
            (isset($pack->name)                        ? "name                        = '".addslashes($pack->name)."', "                        : "").
            (isset($pack->title)                       ? "title                       = '".addslashes($pack->title)."', "                       : "").
            (isset($pack->icon_media_id)               ? "icon_media_id               = '".addslashes($pack->icon_media_id)."', "               : "").
            (isset($pack->latitude)                    ? "latitude                    = '".addslashes($pack->latitude)."', "                    : "").
            (isset($pack->longitude)                   ? "longitude                   = '".addslashes($pack->longitude)."', "                   : "").
            (isset($pack->distance)                    ? "distance                    = '".addslashes($pack->distance)."', "                    : "").
            (isset($pack->infinite_distance)           ? "infinite_distance           = '".addslashes($pack->infinite_distance)."', "           : "").
            (isset($pack->wiggle)                      ? "wiggle                      = '".addslashes($pack->wiggle)."', "                      : "").
            (isset($pack->show_title)                  ? "show_title                  = '".addslashes($pack->show_title)."', "                  : "").
            (isset($pack->hidden)                      ? "hidden                      = '".addslashes($pack->hidden)."', "                      : "").
            (isset($pack->trigger_on_enter)            ? "trigger_on_enter            = '".addslashes($pack->trigger_on_enter)."', "            : "").
            (isset($pack->qr_code)                     ? "qr_code                     = '".addslashes($pack->qr_code)."', "                     : "").
            (isset($pack->seconds)                     ? "seconds                     = '".addslashes($pack->seconds)."', "                     : "").
            (isset($pack->ar_target_id)                ? "ar_target_id                = '".addslashes($pack->ar_target_id)."', "                : "").
            (isset($pack->ar_target_img_scale_x)       ? "ar_target_img_scale_x       = '".addslashes($pack->ar_target_img_scale_x)."', "       : "").
            (isset($pack->ar_target_img_scale_y)       ? "ar_target_img_scale_y       = '".addslashes($pack->ar_target_img_scale_y)."', "       : "").
            (isset($pack->beacon_uuid)                 ? "beacon_uuid                 = '".addslashes($pack->beacon_uuid)."', "                 : "").
            (isset($pack->beacon_major)                ? "beacon_major                = '".addslashes($pack->beacon_major)."', "                : "").
            (isset($pack->beacon_minor)                ? "beacon_minor                = '".addslashes($pack->beacon_minor)."', "                : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE trigger_id = '{$pack->trigger_id}'"
        );

        games::bumpGameVersion($pack);
        return triggers::getTrigger($pack);
    }

    private static function triggerObjectFromSQL($sql_trigger)
    {
        if(!$sql_trigger) return $sql_trigger;
        $trigger = new stdClass();
        $trigger->trigger_id                  = $sql_trigger->trigger_id;
        $trigger->game_id                     = $sql_trigger->game_id;
        $trigger->instance_id                 = $sql_trigger->instance_id;
        $trigger->scene_id                    = $sql_trigger->scene_id;
        $trigger->requirement_root_package_id = $sql_trigger->requirement_root_package_id;
        $trigger->type                        = $sql_trigger->type;
        $trigger->name                        = $sql_trigger->name;
        $trigger->title                       = $sql_trigger->title;
        $trigger->icon_media_id               = $sql_trigger->icon_media_id;
        $trigger->latitude                    = $sql_trigger->latitude;
        $trigger->longitude                   = $sql_trigger->longitude;
        $trigger->distance                    = $sql_trigger->distance;
        $trigger->infinite_distance           = $sql_trigger->infinite_distance;
        $trigger->wiggle                      = $sql_trigger->wiggle;
        $trigger->show_title                  = $sql_trigger->show_title;
        $trigger->hidden                      = $sql_trigger->hidden;
        $trigger->trigger_on_enter            = $sql_trigger->trigger_on_enter;
        $trigger->qr_code                     = $sql_trigger->qr_code;
        $trigger->seconds                     = $sql_trigger->seconds;
        $trigger->ar_target_id                = $sql_trigger->ar_target_id;
        $trigger->ar_target_img_scale_x       = $sql_trigger->ar_target_img_scale_x;
        $trigger->ar_target_img_scale_y       = $sql_trigger->ar_target_img_scale_y;
        $trigger->beacon_uuid                 = $sql_trigger->beacon_uuid;
        $trigger->beacon_major                = $sql_trigger->beacon_major;
        $trigger->beacon_minor                = $sql_trigger->beacon_minor;
        $trigger->cluster_id                  = $sql_trigger->cluster_id;

        return $trigger;
    }

    public static function getTrigger($pack)
    {
        $sql_trigger = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}' LIMIT 1");
        return new return_package(0,triggers::triggerObjectFromSQL($sql_trigger));
    }

    public static function getTriggersForGame($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE game_id = '{$pack->game_id}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            if($ob = triggers::triggerObjectFromSQL($sql_triggers[$i])) $triggers[] = $ob;

        return new return_package(0,$triggers);
    }

    public static function getTriggersForScene($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE scene_id = '{$pack->scene_id}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            {
            $ob = triggers::triggerObjectFromSQL($sql_triggers[$i]);
            if($ob) $triggers[] = $ob;
            }

        return new return_package(0,$triggers);
    }

    public static function getTriggersForInstance($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE instance_id = '{$instanceId}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            if($ob = triggers::triggerObjectFromSQL($sql_trigger)) $triggers[] = $ob;

        return new return_package(0,$triggers);
    }

    public static function deleteTrigger($pack)
    {
        $trigger = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}'");
        $pack->auth->game_id = $trigger->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        games::bumpGameVersion($pack);
        return triggers::noauth_deleteTrigger($pack);
    }

    //this is a security risk...
    public static function noauth_deleteTrigger($pack)
    {
        //and this "fixes" the security risk...
        if(strpos($_SERVER['REQUEST_URI'],'noauth') !== false) return new return_package(6, NULL, "Attempt to bypass authentication externally.");

        dbconnection::query("DELETE FROM triggers WHERE trigger_id = '{$pack->trigger_id}' LIMIT 1");
        //cleanup
        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE instance_id = '{$trigger->instance_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::noauth_deleteInstance($pack);
        }

        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$trigger->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::noauth_deleteRequirementPackage($pack);
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    private static function distanceMeters($lat1, $lng1, $lat2, $lng2)
    {
        // https://stackoverflow.com/a/837957/509936
        $earthRadius = 6371000; //meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $dist = $earthRadius * $c;
        return $dist;
    }

    private static function cluster($markers, $distance) {
        $clustered = array();
        /* Loop until all markers have been compared. */
        while (count($markers)) {
            $marker  = array_pop($markers);
            $cluster = array();
            /* Compare against all markers which are left. */
            foreach ($markers as $key => $target) {
                $meters = triggers::distanceMeters($marker->latitude, $marker->longitude,
                                        $target->latitude, $target->longitude);
                /* If two markers are closer than given distance remove */
                /* target marker from array and add it to cluster.      */
                if ($distance > $meters) {
                    unset($markers[$key]);
                    $cluster[] = $target;
                }
            }

            /* If a marker has been added to cluster, add also the one  */
            /* we were comparing to and remove the original from array. */
            if (count($cluster) > 0) {
                $cluster[] = $marker;
                $clustered[] = $cluster;
            } else {
                $clustered[] = $marker;
            }
        }
        return $clustered;
    }

    public static function assignClusters($pack)
    {
        $game_id = intval($pack->game_id);

        $factories = dbconnection::queryArray("SELECT * FROM factories WHERE game_id = '{$pack->game_id}' AND location_bound_type = 'CLUSTER'");

        foreach ($factories as $fac) {
            $radius = intval($fac->cluster_radius); // in meters
            $threshold = intval($fac->cluster_threshold); // how many needed for a cluster
            $object_type_cond = '';
            if ($fac->cluster_instance_type) {
                $object_type_cond = "AND instances.object_type = '" . addslashes($fac->cluster_instance_type) . "'";
            }
            $object_id_cond = '';
            if ($fac->cluster_instance_id) {
                $object_id_cond = "AND instances.object_id = " . intval($fac->cluster_instance_id);
            }
            $cluster_type = addslashes($fac->object_type);
            $cluster_id = intval($fac->object_id);

            $sql_triggers = dbconnection::queryArray(
                "SELECT triggers.*
                FROM triggers
                JOIN instances ON triggers.instance_id = instances.instance_id
                WHERE triggers.game_id = {$game_id}
                AND (triggers.cluster_id = 0 OR triggers.cluster_id IS NULL)
                {$object_type_cond}
                {$object_id_cond}
                "
            );

            $sql_clusters = dbconnection::queryArray(
                "SELECT triggers.*
                FROM triggers
                JOIN instances ON triggers.instance_id = instances.instance_id
                WHERE triggers.game_id = {$game_id}
                AND instances.object_type = '{$cluster_type}'
                AND instances.object_id = {$cluster_id}
                "
            );

            $assigned = array();
            $unassigned = array();
            foreach ($sql_triggers as $trigger) {
                $add_to_cluster = null;
                foreach ($sql_clusters as $cluster) {
                    $meters = triggers::distanceMeters($trigger->latitude, $trigger->longitude, $cluster->latitude, $cluster->longitude);
                    if ($meters < $radius) {
                        $add_to_cluster = $cluster;
                        break;
                    }
                }
                if (!is_null($add_to_cluster)) {
                    $assigned[] = [
                        'trigger' => $trigger,
                        'cluster' => $add_to_cluster,
                    ];
                } else {
                    $unassigned[] = $trigger;
                }
            }

            foreach ($assigned as $assignment) {
                $element_trigger_id = intval($assignment['trigger']->trigger_id);
                $cluster_trigger_id = intval($assignment['cluster']->trigger_id);
                dbconnection::query("
                    UPDATE triggers
                    SET cluster_id = $cluster_trigger_id
                    WHERE trigger_id = $element_trigger_id;
                ");
            }

            $new_clusters = triggers::cluster($unassigned, $radius);
            foreach ($new_clusters as $cluster) {
                if (count($cluster) < $threshold) {
                    continue;
                }
                $lat = $cluster[count($cluster) - 1]->latitude;
                $lon = $cluster[count($cluster) - 1]->longitude;
                $cluster_instance_id = dbconnection::queryInsert("INSERT INTO instances (game_id, object_id, object_type, qty, infinite_qty, factory_id, created) VALUES ('{$game_id}', '{$fac->object_id}', '{$fac->object_type}', '1', '0', '{$fac->factory_id}', CURRENT_TIMESTAMP)");
                $cluster_trigger_id = dbconnection::queryInsert("INSERT INTO triggers (game_id, instance_id, scene_id, requirement_root_package_id, type, name, title, latitude, longitude, distance, infinite_distance, wiggle, show_title, hidden, trigger_on_enter, icon_media_id, created) VALUES ('{$game_id}', '{$cluster_instance_id}', {$fac->trigger_scene_id}, '{$fac->trigger_requirement_root_package_id}', 'LOCATION', '{$fac->trigger_title}', '{$fac->trigger_title}', '{$lat}', '{$lon}', '{$fac->trigger_distance}', '{$fac->trigger_infinite_distance}', '{$fac->trigger_wiggle}', '{$fac->trigger_show_title}', '{$fac->trigger_hidden}', '{$fac->trigger_on_enter}', '{$fac->trigger_icon_media_id}', CURRENT_TIMESTAMP);");
                $element_ids = [];
                foreach ($cluster as $element) {
                    $element_ids[] = $element->trigger_id;
                }
                $element_ids = '(' . implode(',', $element_ids) . ')';
                dbconnection::query("
                    UPDATE triggers
                    SET cluster_id = $cluster_trigger_id
                    WHERE trigger_id IN $element_ids;
                ");
            }
        }

        return new return_package(0);
    }
}
?>
