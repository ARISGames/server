<?php
require_once("dbconnection.php");
require_once("util.php");
require_once("return_package.php");

require_once("media.php");

require_once("../migration/bridge.php"); //for migration injection

class users extends dbconnection
{
    //Used by other services
    public static function authenticateUser($pack)
    {
        $userId     = addslashes($pack->user_id);
        $permission = addslashes($pack->permission);
        $key        = addslashes($pack->key);

        $user = dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$userId}' LIMIT 1");
        if($user && $user->{$permission."_key"} == $key) return true;
        util::errorLog("Failed Editor Authentication!"); return false;
    }

    public static function createUser($pack)
    {
        if(!$pack->user_name || $pack->user_name == "") return new return_package(1, NULL, "Empty username invalid.");
        if(dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'")) return new return_package(1, NULL, "User already exists");

        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$pack->password);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);

        $pack->user_id = dbconnection::queryInsert(
            "INSERT INTO users (".
            "user_name,".
            "hash,".
            "salt,".
            "read_key,".
            "write_key,".
            "read_write_key,".
            "display_name,".
            (isset($pack->group_name) ? "group_name," : "").
            (isset($pack->email)      ? "email,"      : "").
            (isset($pack->media_id)   ? "media_id,"   : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->user_name)."',".
            "'".addslashes($hash)."',".
            "'".addslashes($salt)."',".
            "'".addslashes($read)."',".
            "'".addslashes($write)."',".
            "'".addslashes($read_write)."',".
            (isset($pack->display_name) ? "'".addslashes($pack->display_name)."'," : "'".addslashes($pack->user_name)."',").
            (isset($pack->group_name)   ? "'".addslashes($pack->group_name)."',"   : "").
            (isset($pack->email)        ? "'".addslashes($pack->email)."',"        : "").
            (isset($pack->media_id)     ? "'".addslashes($pack->media_id)."',"     : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        $pack->permission = "read_write";
        return users::logIn($pack);
    }

    public static function autoGenerateUser($pack)
    {
        if(isset($pack->group_name) && strlen($pack->group_name))
        {
          //derive from group_name
          $exists = true;
          while($exists)
          {
            $pack->user_name = $pack->group_name.util::rand_string(5);
            $exists = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'");
          }
          $pack->password = $pack->group_name;
        }
        else
        {
          //populate with garbage
          $exists = true;
          while($exists)
          {
            $pack->user_name = util::rand_string(10);
            $exists = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'");
          }
          $pack->password = util::rand_string(10); //this is legit probably never going to be recoverable...
        }
        if(!isset($pack->display_name)) $pack->display_name = ""; //explicitly set display_name so default doesn't get set to the gibberish user_name

        return users::createUser($pack);
    }

    public static function updateUser($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if($pack->media)
        {
            $pack->media->auth = $pack->auth;
            $pack->media->user_id = $pack->user_id;
            $pack->media_id = media::createMedia($pack->media)->data->media_id;
        }

        dbconnection::query(
            "UPDATE users SET ".
            (isset($pack->display_name) ? "display_name = '".addslashes($pack->display_name)."'," : "").
            (isset($pack->email)        ? "email        = '".addslashes($pack->email)."',"        : "").
            (isset($pack->media_id)     ? "media_id     = '".addslashes($pack->media_id)."',"     : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE user_id = '{$pack->user_id}'"
        );

        return users::logIn($pack);
    }

    //Takes in user JSON, requires either (user_name and password) or (auth pack)
    public static function logIn($pack)
    {
        if($pack->auth && $pack->auth->user_id)
        {
            $pack->auth->permission = "read_write";
            if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
            $user = dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$pack->auth->user_id}'");
        }
        else if(!($user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'")) || hash("sha256",$user->salt.$pack->password) != $user->hash)
        {
          /*
          *  BEGIN MIGRATION INJECTION
          */
          //Login Failed. If username/password doesn't exist, check if we can re-route this to a migrate. 
          if(!$user && !$pack->no_auto_migrate)
          {
            //miguser api for reference
                //public function migrateUser($playerName, $playerPass, $editorName, $editorPass, $newName, $newPass, $newDisplay, $newEmail)

            //first try to migrate full editor & player
            $migArgs = $pack->user_name."/".$pack->password."/".$pack->user_name."/".$pack->password."/".$pack->user_name."/".$pack->password."/0/0";
            $mig = bridgeService("migration", "migration", "migrateUser", $migArgs, false);
            if($mig->returnCode != 0)
            {
            //if that doesn't work, just try editor
            $migArgs = "0/0/".$pack->user_name."/".$pack->password."/".$pack->user_name."/".$pack->password."/0/0";
            $mig = bridgeService("migration", "migration", "migrateUser", $migArgs, false);
            }
            //if THAT doesn't work, bad login
            if($mig->returnCode != 0)
                        return new return_package(1, NULL, "Incorrect username/password");
            else //it worked!
            {
              //pretend the user was correctly recieved
              $user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'");
            }
          }
          else //there already exists a user but they have an invalid password (so drop out of migration injection flow)

          /*
          *  END MIGRATION INJECTION
          */

          return new return_package(1, NULL, "Incorrect username/password");
        }

        $ret = new stdClass();
        $ret->user_id      = $user->user_id;
        $ret->user_name    = $user->user_name;
        $ret->display_name = $user->display_name;
        $ret->media_id     = $user->media_id;
        if($pack->permission == "read"       || $pack->auth->permission == "read")       $ret->read_key       = $user->read_key;
        if($pack->permission == "write"      || $pack->auth->permission == "write")      $ret->write_key      = $user->write_key;
        if($pack->permission == "read_write" || $pack->auth->permission == "read_write") $ret->read_write_key = $user->read_write_key;

        dbconnection::queryInsert("INSERT INTO user_log (user_id, event_type, created) VALUES ('{$ret->user_id}', 'LOG_IN', CURRENT_TIMESTAMP);");
        return new return_package(0, $ret);
    }

    public static function changePassword($pack)
    {
        $username = addslashes($pack->user_name);
        $oldPass  = addslashes($pack->old_password);
        $newPass  = addslashes($pack->new_password);
        unset($pack->auth);

        //log in with old
        $pack->password = $pack->old_password;
        $pack->permission = "read_write";
        $user = users::logIn($pack)->data;
        if(!$user->read_write_key) return new return_package(1, NULL, "Incorrect username/password");

        //if changing password, invalidate all keys
        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$newPass);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);
        dbconnection::query("UPDATE users SET ".
            "salt = '{$salt}', ".
            "hash = '{$hash}', ".
            "read_key = '{$read}', ".
            "write_key = '{$write}', ".
            "read_write_key = '{$read_write}' ".
            "WHERE user_id = '{$user->user_id}'"
        );

        //log in with new
        $pack->password = $pack->new_password;
        $pack->permission = "read_write";
        return users::logIn($pack);
    }

    private static function breakPassword($userId)
    {
        $u = dbconnection::queryObject("SELECT hash FROM users WHERE user_id = '{$user_id}'");
        if($u) return MD5($u->hash);
        return MD5($userId."plzstophackingkthxbi");
    }

    public static function fixPassword($pack)
    {
        $user_id = addslashes($pack->user_id);
        $junk = addslashes($pack->junk);
        $newPass  = addslashes($pack->new_password);

        if($junk != users::breakPassword($user_id)) return new returnData(0); //fail, but don't make it obvious

        //if changing password, invalidate all keys
        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$newPass);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);
        dbconnection::query("UPDATE users SET ".
          "salt = '{$salt}', ".
          "hash = '{$hash}', ".
          "read_key = '{$read}', ".
          "write_key = '{$write}', ".
          "read_write_key = '{$read_write}' ".
          "WHERE user_id = '{$user_id}'");

        return new return_package(0, NULL);
    }

    public static function userObjectFromSQL($sql_user)
    {
        //parses only public data into object
        if(!$sql_user) return $sql_user;
        $user = new stdClass();
        $user->user_id       = $sql_user->user_id;
        $user->user_name     = $sql_user->user_name;
        $user->display_name  = $sql_user->display_name;
        $user->media_id      = $sql_user->media_id;

        return $user;
    }

    public static function getUser($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //Note- uses $pack->user_id, NOT $pack->auth->user_id. as in, one user can request public details about another.
        $sql_user = dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$pack->user_id}'");
        return new return_package(0, users::userObjectFromSQL($sql_user));
    }

    public static function getUsersForGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_users = dbconnection::queryArray("SELECT * FROM (SELECT * FROM user_games WHERE game_id = '{$pack->game_id}') as u_gs LEFT JOIN users ON u_gs.user_id = users.user_id");
        $users = array();
        for($i = 0; $i < count($sql_users); $i++)
            if($ob = users::userObjectFromSQL($sql_users[$i])) $users[] = $ob;

        return new return_package(0, $users);
    }

    public static function getUsersForFuzzySearch($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_users = dbconnection::queryArray("SELECT * FROM users WHERE user_name LIKE '%{$pack->search}%' OR display_name LIKE '%{$pack->search}%' OR email LIKE '%{$pack->search}%'");
        $users = array();
        for($i = 0; $i < count($sql_users); $i++)
            if($ob = users::userObjectFromSQL($sql_users[$i])) $users[] = $ob;

        return new return_package(0, $users);
    }

    public static function getUserForSearch($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_user = dbconnection::queryObject("SELECT * FROM users WHERE user_name LIKE '{$pack->search}' OR email LIKE '{$pack->search}'");
        if($sql_user) return new return_package(0, users::userObjectFromSQL($sql_user));
        else          return new return_package(1, NULL, "User not found");
    }

    public static function requestForgotPasswordEmail($pack)
    {
        if($pack->user_name)  $user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}' LIMIT 1");
        else if($pack->email) $user = dbconnection::queryObject("SELECT * FROM users WHERE email = '{$pack->email}' LIMIT 1");

        if(!$user) return new return_package(0);

        $userId = $user->user_id;
        $username = $user->user_name;
        $email = $user->email;
        $junk = users::breakPassword($userId);

        //email it to them
        $subject = "ARIS Password Request";
        $body = "We received a forgotten password request for your ARIS account.
        If you did not make this request, do nothing and your account info will not change.
        <br><br>To reset your password, simply click the link below.
        Please remember that passwords are case sensitive.
        If you are not able to click on the link, please copy and paste it into your web browser.
        <br><br>
        <a href='".Config::serverWWWPath."/services/v2/resetpassword.html?i=$userId&j=$junk'>".Config::serverWWWPath."/services/v2/resetpassword.html?i=$userId&j=$junk</a>
        <br><br> Regards, <br>ARIS";

        util::sendEmail($email, $subject, $body);
        return new return_package(0);
    }
}
?>
