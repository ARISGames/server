<?php
require("module.php");

class Editors extends Module
{
			
	/**
     * Login to the editor
     * @returns the editorID
     */
	public function login($strUser, $strPassword)
	{
		
		$query = "SELECT * FROM editors 
				WHERE name = '$strUser' and password = rrr LIMIT 1";
		
		//NetDebug::trace($query);

		$rs = @mysql_query($query);
		if (mysql_num_rows($rs) < 1) return new returnData(4, NULL, 'bad username or password');
		
		$editor = @mysql_fetch_array($rs);
		return new returnData(0, intval($editor['editor_id']));
	}
	
	
	/**
     * Create a new editor
     * @returns the new editorID or false if an account name (code 4) or email (code 5) already exists
     */
	public function createEditor($strUser, $strPassword, $strEmail, $strComments)
	{	
	
		$query = "SELECT editor_id FROM editors 
				  WHERE name = '{$strUser}' LIMIT 1";
			
		if (mysql_fetch_array(mysql_query($query))) {
			return new returnData(4, NULL, 'user exists');
		}
		
		$query = "SELECT editor_id FROM editors 
				  WHERE email = '{$strEmail}' LIMIT 1";
			
		if (mysql_fetch_array(mysql_query($query))) {
			return new returnData(5, NULL, 'email exists');
		}
		
		$strComments = addslashes($strComments);
		
		$query = "INSERT INTO editors (name, password, email, comments, created) 
				  VALUES ('{$strUser}',MD5('$strPassword'),'{$strEmail}','{$strComments}', NOW())";
			
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
		
		$subject = "Welcome to the ARIS Alpha Editor!";
 		
        /* OLD BODY
        $body = "<p><strong>You signed up to become an editor for ARIS!</strong> To get things started well, we wanted to make sure you 
 		knew about a few things and had a few folks to call for help.</p>
 		<p>For starters, you should head out to go to http://groups.google.com/group/arisgames and view the 'Getting Started Videos'</p>
 		<p>If you have questions and want to talk with other users, go post a new discussion there or send an email to arisgames@googlegroups.com</p>
 		<p>If you discover bugs or have new ideas, please tell us at http://groups.google.com/group/arisgames/web/feature-requests</p>
 		<p>Just so you don't forget, your username is $strUser and your password is $strPassword</p>
 		<p>Good luck making games!</p>";
        */ 
        
        $body = "<p><strong>You signed up to become an editor for ARIS!</strong> To get things started well, we wanted to make sure you knew about a few things and had places to look for help.</p>
            <p>For starters, there are demo videos and documentation at http://arisgames.org/make/training-videos/.</p>
            <p>If you have questions and want to talk with other users, go to http://groups.google.com/group/arisgames. You can post a new discussion there or send an email to arisgames@googlegroups.com.</p>
            <p>If you discover bugs or have new ideas, please tell us at http://arisgames.lighthouseapp.com.</p>
            <p>Just so you don't forget, your username is $strUser and your password is $strPassword</p>
            <p>Good luck making games!</p>";
 			
 		if (Module::sendEmail($strEmail, $subject, $body)) return new returnData(0, mysql_insert_id());
		else return new returnData(4, mysql_insert_id(), "Account created but email not sent");
	}
	
	
	/**
     * Change an Editor's PAssword
     * @returns 0 on success, 4 bad editorID or password
     */
	public function changePassword($intEditorID, $strOldPassword, $strNewPassword)
	{	
		if ($strOldPassword == $strNewPassword) return new returnData(0, NULL);
		
		$query = "UPDATE editors 
				SET password = MD5('{$strNewPassword}')
				WHERE password = MD5('{$strOldPassword}')
				AND editor_id = {$intEditorID}";
		
		NetDebug::trace($query);
		
		@mysql_query($query);

		if (mysql_affected_rows() < 1) return new returnData(4, NULL, 'No editors exist with matching ID and password');
		return new returnData(0, NULL);
	}	
    
	/**
     * Sends email to with link for changing their password 
     * @returns 0 on success
     */
	public function resetAndEmailNewPassword($strEmail) {
		//create a new password
		/*$chars = 'abcdefghijklmnopqrstuvwxyz1234567890';
		$length = 6;
		$newPass = '';
		for ($i=0; $i<$length; $i++) {
			$char = substr($chars, rand(0,35), 1);
			$newPass .= $char;
		}*/
		//NetDebug::trace("New Password: {$newPass}");

		//set the editor record to this pw
		//$query = "UPDATE editors SET password = MD5('{$newPass}') 
		//		WHERE email = '{$strEmail}'";
		
		//@mysql_query($query);
		//if (mysql_error()) return new returnData(3, NULL, 'SQL Error' . mysql_error());
		//if (!mysql_affected_rows()) return new returnData(4, NULL, "Email is not an editor");
		
        $query2 = "SELECT editor_id, password FROM editors WHERE email = '{$strEmail}'";
        $result = @mysql_query($query2);
        if (!$editor = mysql_fetch_array($result)) return new returnData(4, NULL, "Not an editor");
        
        $editorid = $editor['editor_id'];
        $scrambledpassword = MD5({$editor['password']});
            
		//email it to them
 		$subject = "ARIS Password Request";
 		$body = "We received a forgotten password request for your ARIS account. If you did not make this request, do nothing and your account info will not change. <br><br>To reset your password, simply click the link below. Please remember that passwords are case sensitive. If you are not able to click on the link, please copy and paste it into your web browser.<br> ".Config::serverWWWPath."/resetpassword.php?t=e&i=$editorid&p=$scrambledpassword";

 		if (Module::sendEmail($strEmail, $subject, $body)) return new returnData(0, NULL);
  		else return new returnData(5, NULL, "Mail could not be sent");
	}
	
	public function emailUserName($strEmail) {
		//set the editor record to this pw
		$query = "SELECT * FROM editors	WHERE email = '{$strEmail}'";
		
		$result = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error' . mysql_error());
	
		if (!$editor = mysql_fetch_array($result)) return new returnData(4, NULL, "Email is not an editor");
		
		$subject = "Recover ARIS Login Information";
 		$body = "Your ARIS username is: {$editor['name']}";
 			
 		if (Module::sendEmail($strEmail, $subject, $body)) return new returnData(0, NULL);
  		else return new returnData(5, NULL, "Mail could not be sent");

	}

	

	
	
}
