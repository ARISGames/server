<?php
require_once("../v2/util.php");
require_once("migration_dbconnection.php");
require_once("migration_return_package.php");

class mig_users extends migration_dbconnection
{    
    //Used by other services
    public static function authenticateUser($pack)
    {
        $userId     = addslashes($pack->user_id);
        $permission = addslashes($pack->permission);
        $key        = addslashes($pack->key);

        $user = migration_dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$userId}' LIMIT 1","v2");
        if($user && $user->{$permission."_key"} == $key) return true;
        util::serverErrorLog("Failed Editor Authentication!"); return false;
    }

    public static function createUser($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return mig_users::createUserPack($glob); }
    public static function createUserPack($pack)
    {
        if(migration_dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'","v2"))
            return new migration_return_package(1, NULL, "User already exists");

        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$pack->password);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);

        $pack->user_id = migration_dbconnection::queryInsert(
            "INSERT INTO users (".
            "user_name,".
            "hash,".
            "salt,".
            "read_key,".
            "write_key,".
            "read_write_key,".
            "display_name,".
            (isset($pack->email)    ? "email,"    : "").
            (isset($pack->media_id) ? "media_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->user_name)."',".
            "'".addslashes($hash)."',".
            "'".addslashes($salt)."',".
            "'".addslashes($read)."',".
            "'".addslashes($write)."',".
            "'".addslashes($read_write)."',".
            (isset($pack->display_name) ? "'".addslashes($pack->display_name)."'," : "'".addslashes($pack->user_name)."',").
            (isset($pack->email)        ? "'".addslashes($pack->email)."',"        : "").
            (isset($pack->media_id)     ? "'".addslashes($pack->media_id)."',"     : "").
            "CURRENT_TIMESTAMP".
            ")"
        ,"v2");

        $pack->permission = "read_write";
        return mig_users::logInPack($pack);
    }

    //Takes in user JSON, requires either (user_name and password) or (auth pack)
    public static function logIn($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return mig_users::logInPack($glob); }
    public static function logInPack($pack)
    {
        if($pack->auth && $pack->auth->user_id)
        {
            $pack->auth->permission = "read_write";
            if(!mig_users::authenticateUser($pack->auth)) return new migration_return_package(6, NULL, "Failed Authentication");
            $user = migration_dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$pack->user_id}'","v2");
        }
        else if(!($user = migration_dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'","v2")) || hash("sha256",$user->salt.$pack->password) != $user->hash)
            return new migration_return_package(1, NULL, "Incorrect username/password");

        $ret = new stdClass();
        $ret->user_id      = $user->user_id;
        $ret->user_name    = $user->user_name;
        $ret->display_name = $user->display_name;
        $ret->media_id     = $user->media_id;
        if($pack->permission == "read")       $ret->read_key       = $user->read_key;
        if($pack->permission == "write")      $ret->write_key      = $user->write_key;
        if($pack->permission == "read_write") $ret->read_write_key = $user->read_write_key;

        migration_dbconnection::queryInsert("INSERT INTO user_log (user_id, event_type, created) VALUES ('{$ret->user_id}', 'LOG_IN', CURRENT_TIMESTAMP);","v2");
        return new migration_return_package(0, $ret);
    }
}
?>
