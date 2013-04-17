<?php
require("module.php");

class Editors extends Module
{
    public function getToken($username, $password, $permission)
    {
        $username   = addslashes($username);
        $password   = addslashes($password);
        $permission = addslashes($permission);

        $e = Module::queryObject("SELECT editor_id, ".$permission."_token FROM editors WHERE name = '".$username."' AND password = MD5('".$password."') LIMIT 1");
        if($e)
        {
            if($e->{$permission."_token"} == "")
            {
                $e->{$permission."_token"} = Utils::rand_string(64);
                Module::query("UPDATE editors SET ".$permission."_token = '".$e->{$permission."_token"}."' WHERE editor_id = ".$e->editor_id);
            }
            return new returnData(0, $e);
        }
        else return new returnData(4, NULL, "Bad Username or Password");
    }

    public function getEditorsWithEmail($email)
    {
        $eArray = Module::queryArray("SELECT editor_id, email FROM editors WHERE email LIKE '".$email."'");
        return new returnData(0,$eArray);
    }

    public function createEditor($strUser, $strPassword, $strEmail, $strComments)
    {	
        $query = "SELECT editor_id FROM editors WHERE name = '{$strUser}' LIMIT 1";
        if(mysql_fetch_array(Module::query($query))) return new returnData(4, NULL, 'user exists');

        $query = "SELECT editor_id FROM editors WHERE email = '{$strEmail}' LIMIT 1";
        if (mysql_fetch_array(Module::query($query))) return new returnData(5, NULL, 'email exists');

        $strComments = addslashes($strComments);

        $query = "INSERT INTO editors (name, password, email, comments, created, read_write_token) 
            VALUES ('{$strUser}',MD5('$strPassword'),'{$strEmail}','{$strComments}', NOW(), '".Utils::rand_string(64)."')";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

        $subject = "Welcome to the ARIS Alpha Editor!";
        $body = "<p><strong>You signed up to become an editor for ARIS!</strong> To get things started well, we wanted to make sure you knew about a few things and had places to look for help.</p>
            <p>For starters, there are demo videos and documentation at http://arisgames.org/make/training-videos/.</p>
            <p>If you have questions and want to talk with other users, join our google group at http://groups.google.com/group/arisgames. You can post a new discussion there or send an email to arisgames@googlegroups.com.</p>
            <p>If you discover bugs or have new ideas, please tell us at http://arisgames.lighthouseapp.com.</p>
            <p>Just so you don't forget, your username is $strUser and your password is $strPassword</p>
            <p>Good luck making games!</p>";
            
        Module::sendEmail($strEmail, $subject, $body);
        return Editors::getToken($strUser, $strPassword, "read_write");
    }

    public function deleteEditor($strUser, $strPassword)
    {	
        $query = "DELETE FROM editors WHERE name = '{$strUser}' AND password = '".md5($strPassword)."';";
        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
        return new returnData(0); 
    }

    public function changePassword($intEditorID, $strOldPassword, $strNewPassword)
    {	
        if ($strOldPassword == $strNewPassword) return new returnData(0, NULL);

        $query = "UPDATE editors 
            SET password = MD5('{$strNewPassword}'), read_write_token = '".rand_string(64)."'
            WHERE password = MD5('{$strOldPassword}')
            AND editor_id = {$intEditorID}";

        Module::query($query);

        if (mysql_affected_rows() < 1) return new returnData(4, NULL, 'No editors exist with matching ID and password');
        return new returnData(0, NULL);
    }	

    public function resetAndEmailNewPassword($strEmail)
    {
        $query2 = "SELECT editor_id, password FROM editors WHERE email = '{$strEmail}'";
        $result = Module::query($query2);
        if (!$editor = mysql_fetch_array($result)) return new returnData(4, NULL, "Not an editor");

        $editorid = $editor['editor_id'];
        $scrambledpassword = MD5($editor['password']);

        //email it to them
        $subject = "ARIS Password Request";
        $body = "We received a forgotten password request for your ARIS account. If you did not make this request, do nothing and your account info will not change. <br><br>To reset your password, simply click the link below. Please remember that passwords are case sensitive. If you are not able to click on the link, please copy and paste it into your web browser.<br><br> <a href='".Config::serverWWWPath."/resetpassword.php?t=e&i=$editorid&p=$scrambledpassword'>".Config::serverWWWPath."/resetpassword.php?t=e&i=$editorid&p=$scrambledpassword</a> <br><br> Regards, <br>ARIS";

        if (Module::sendEmail($strEmail, $subject, $body)) return new returnData(0, NULL);
        else return new returnData(5, NULL, "Mail could not be sent");
    }

    public function emailUserName($strEmail)
    {
        //set the editor record to this pw
        $query = "SELECT * FROM editors	WHERE email = '{$strEmail}'";

        $result = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, 'SQL Error' . mysql_error());

        if (!$editor = mysql_fetch_array($result)) return new returnData(4, NULL, "Email is not an editor");

        $subject = "Recover ARIS Login Information";
        $body = "Your ARIS username is: {$editor['name']}";

        if (Module::sendEmail($strEmail, $subject, $body)) return new returnData(0, NULL);
        else return new returnData(5, NULL, "Mail could not be sent");
    }
}
