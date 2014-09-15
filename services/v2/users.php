<?php
require_once("dbconnection.php");
require_once("util.php");
require_once("return_package.php");

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
        util::serverErrorLog("Failed Editor Authentication!"); return false;
    }

    //Takes in user JSON, all fields optional except user_id + key
    public static function createUser($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::createUserPack($glob); }
    public static function createUserPack($pack)
    {
        if(dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'"))
            return new return_package(1, NULL, "User already exists");

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
        );

        $pack->permission = "read_write";
        return users::logInPack($pack);
    }

    //Takes in user JSON, requires user_name and password
    public static function logIn($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::logInPack($glob); }
    public static function logInPack($pack)
    {
        if(!($user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$pack->user_name}'")) || hash("sha256",$user->salt.$pack->password) != $user->hash)
            return new return_package(1, NULL, "Incorrect username/password");

        $ret = new stdClass();
        $ret->user_id      = $user->user_id;
        $ret->user_name    = $user->user_name;
        $ret->display_name = $user->display_name;
        $ret->media_id     = $user->media_id;
        if($pack->permission == "read")       $ret->read_key       = $user->read_key;
        if($pack->permission == "write")      $ret->write_key      = $user->write_key;
        if($pack->permission == "read_write") $ret->read_write_key = $user->read_write_key;

        dbconnection::queryInsert("INSERT INTO user_log (user_id, event_type, created) VALUES ('{$ret->user_id}', 'LOG_IN', CURRENT_TIMESTAMP);");
        return new return_package(0, $ret);
    }

    public static function changePassword($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::changePasswordPack($glob); }
    public static function changePasswordPack($pack)
    {	
        $username = addslashes($pack->user_name);
        $oldPass  = addslashes($pack->old_password);
        $newPass  = addslashes($pack->new_password);

        $user = users::logInPack($username, $oldPass, "read_write")->data;
        if(!$user) return new return_package(1, NULL, "Incorrect username/password");

        //if changing password, invalidate all keys
        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$newPass);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);
        dbconnection::query("UPDATE users SET salt = '{$salt}', hash = '{$hash}', read_key = '{$read_ley}', write_key = '{$write_key}', read_write_key = '{$read_write_key}' WHERE user_id = '{$user->user_id}'");

        return new return_package(0, NULL);
    }	

    private static function breakPassword($userId)
    {
        $u = dbconnection::queryObject("SELECT hash FROM users WHERE user_id = '{$user_id}'");
        if($u) return MD5($u->hash);
        return MD5($userId."plzstophackingkthxbi");
    }

    public static function fixPassword($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::fixPasswordPack($glob); }
    public static function fixPasswordPack($pack)
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
        dbconnection::query("UPDATE users SET salt = '{$salt}', hash = '{$hash}', read_key = '{$read_ley}', write_key = '{$write_key}', read_write_key = '{$read_write_key}' WHERE user_id = '{$user_id}'");

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

    public static function getUser($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::getUserPack($glob); }
    public static function getUserPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //Note- uses $pack->user_id, NOT $pack->auth->user_id. as in, one user can request public details about another.
        $sql_user = dbconnection::queryObject("SELECT * FROM user_games WHERE user_id = '{$pack->user_id}'");
        return new return_package(0, users::userObjectFromSQL($sql_user));
    }

    public static function getUsersForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::getUsersForGamePack($glob); }
    public static function getUsersForGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_users = dbconnection::queryArray("SELECT * FROM (SELECT * FROM user_games WHERE game_id = '{$pack->game_id}') as u_gs LEFT JOIN users ON u_gs.user_id = users.user_id");
        $users = array();
        for($i = 0; $i < count($sql_users); $i++)
            if($ob = users::userObjectFromSQL($sql_users[$i])) $users[] = $ob;

        return new return_package(0, $users);
    }

    public static function requestForgotPasswordEmail($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return users::requestForgotPasswordEmailPack($glob); }
    public static function requestForgotPasswordEmailPack($pack)
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
        return new return_package(0, NULL);
    }

    public static function emailUserName($strEmail)
    {
        if(!$user = dbconnection::queryObject("SELECT * FROM users WHERE email = '{$strEmail}' LIMIT 1"))
            return new return_package(4, NULL, "Email is not a user");

        $subject = "Recover ARIS Login Information";
        $body = "Your ARIS username is: {$user->user_name}";

        if(util::sendEmail($strEmail, $subject, $body)) return new return_package(0, NULL);
        else return new return_package(5, NULL, "Mail could not be sent");
    }
}
?>
