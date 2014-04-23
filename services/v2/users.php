<?php

require_once("dbconnection.php");
require_once("util.php");
require_once("returnData.php");

class users extends dbconnection
{
    public function createUser($username, $password) //note- password expected to be md5'd or something. don't be passing plaintext across the tubes...
    {
        if(dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$username}'"))
            return new returnData(1, NULL, "User already exists");

        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$password);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);
        dbconnection::query("INSERT INTO users (user_name, display_name, salt, hash, read_key, write_key, read_write_key, created) VALUES ('{$username}', '{$username}','{$salt}','{$hash}','{$read}','{$write}','{$read_write}', CURRENT_TIMESTAMP)");
        return users::logIn($username, $password, "read_write");
    }

    public function logIn($username, $password, $permission)
    {
        if(!($user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$username}'")) || hash("sha256",$user->salt.$password) != $user->hash)
            return new returnData(1, NULL, "Incorrect username/password");

        $ret = new stdClass();
        $ret->user_id = $user->user_id;
        $ret->user_name = $user->user_name;
        $ret->display_name = $user->display_name;
        $ret->media_id = $user->media_id;
        if($permission == "read")
            $ret->read_key = $user->read_key;
        if($permission == "write")
            $ret->write_key = $user->write_key;
        if($permission == "read_write")
            $ret->read_write_key = $user->read_write_key;

        return new returnData(0, $ret);
    }

    public function authenticateUser($userId, $key, $permission)
    {
        $permission = addslashes($permission);
        $key        = addslashes($key);

        $user = dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$userId}' LIMIT 1");
        if($user && $user->{$permission."_key"} == $key) return true;
        util::serverErrorLog("Failed Editor Authentication!"); return false;
    }

    public function changePassword($username, $oldPass, $newPass)
    {	
        $user = users::logIn($username, $oldPass, "read_write")->data;
        if(!$user) return new returnData(1, NULL, "Incorrect username/password");

        //if changing password, invalidate all keys
        $salt       = util::rand_string(64);
        $hash       = hash("sha256",$salt.$newPass);
        $read       = util::rand_string(64);
        $write      = util::rand_string(64);
        $read_write = util::rand_string(64);
        dbconnection::query("UPDATE users SET salt = '{$salt}', hash = '{$hash}', read_key = '{$read_ley}', write_key = '{$write_key}', read_write_key = '{$read_write_key}' WHERE user_id = '{$user->user_id}'");

        return new returnData(0, NULL);
    }	

    public function resetAndEmailNewPassword($strEmail)
    {
        //oh god terrible email validation
        $user = null;
        if(strrpos($strEmail, "@") === false) $user = dbconnection::queryObject("SELECT * FROM users WHERE user_name = '{$strEmail}' LIMIT 1");
        else                                  $user = dbconnection::queryObject("SELECT * FROM users WHERE email = '{$strEmail}' LIMIT 1");

        if(!$user) return new returnData(4, NULL, "Not a user");

        $userId = $user->user_id;
        $username = $user->user_name;
        $email = $user->email;
        $scrambledpassword = MD5($user->password);

        //email it to them
        $subject = "ARIS Password Request";
        $body = "We received a forgotten password request for your ARIS account. If you did not make this request, do nothing and your account info will not change. <br><br>To reset your password, simply click the link below. Please remember that passwords are case sensitive. If you are not able to click on the link, please copy and paste it into your web browser.<br><br> <a href='".Config::serverWWWPath."/resetpassword.php?t=p&i=$userId&p=$scrambledpassword'>".Config::serverWWWPath."/resetpassword.php?t=p&i=$userId&p=$scrambledpassword</a> <br><br> Regards, <br>ARIS";

        if(util::sendEmail($email, $subject, $body)) return new returnData(0, NULL);
        else return new returnData(5, NULL, "Mail could not be sent");
    }

    public function emailUserName($strEmail)
    {
        if(!$user = dbconnection::queryObject("SELECT * FROM users WHERE email = '{$strEmail}' LIMIT 1"))
            return new returnData(4, NULL, "Email is not a user");

        $subject = "Recover ARIS Login Information";
        $body = "Your ARIS username is: {$user->user_name}";

        if(util::sendEmail($strEmail, $subject, $body)) return new returnData(0, NULL);
        else return new returnData(5, NULL, "Mail could not be sent");
    }
}
?>
