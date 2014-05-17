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
            ($pack->email    ? "email,"    : "").
            ($pack->media_id ? "media_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->user_name)."',".
            "'".addslashes($hash)."',".
            "'".addslashes($salt)."',".
            "'".addslashes($read)."',".
            "'".addslashes($write)."',".
            "'".addslashes($read_write)."',".
            ($pack->display_name ? "'".addslashes($pack->display_name)."'," : "'".addslashes($pack->user_name)."',").
            ($pack->email        ? "'".addslashes($pack->email)."',"        : "").
            ($pack->media_id     ? "'".addslashes($pack->media_id)."',"     : "").
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

    public static function resetAndEmailNewPassword($strEmail)
    {
        //oh god terrible email validation
        $user = null;
        if(strrpos($strEmail, "@") === false) $user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$strEmail}' LIMIT 1");
        else                                  $user = dbconnection::queryObject("SELECT * FROM users WHERE email = '{$strEmail}' LIMIT 1");

        if(!$user) return new return_package(4, NULL, "Not a user");

        $userId = $user->user_id;
        $username = $user->user_name;
        $email = $user->email;
        $scrambledpassword = MD5($user->password);

        //email it to them
        $subject = "ARIS Password Request";
        $body = "We received a forgotten password request for your ARIS account. If you did not make this request, do nothing and your account info will not change. <br><br>To reset your password, simply click the link below. Please remember that passwords are case sensitive. If you are not able to click on the link, please copy and paste it into your web browser.<br><br> <a href='".Config::serverWWWPath."/resetpassword.php?t=p&i=$userId&p=$scrambledpassword'>".Config::serverWWWPath."/resetpassword.php?t=p&i=$userId&p=$scrambledpassword</a> <br><br> Regards, <br>ARIS";

        if(util::sendEmail($email, $subject, $body)) return new return_package(0, NULL);
        else return new return_package(5, NULL, "Mail could not be sent");
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
