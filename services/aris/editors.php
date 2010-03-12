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
				WHERE name = '$strUser' and password = MD5('{$strPassword}') LIMIT 1";
		
		//NetDebug::trace($query);

		$rs = @mysql_query($query);
		if (mysql_num_rows($rs) < 1) return new returnData(4, NULL, 'bad username or password');
		
		$editor = @mysql_fetch_array($rs);
		return new returnData(0, intval($editor['editor_id']));
	}
	
	
	/**
     * Create a new editor
     * @returns the new editorID or false if an account already exists
     */
	public function createEditor($strUser, $strPassword, $strEmail, $strComments)
	{	
		$query = "SELECT editor_id FROM editors 
				  WHERE name = '{$strUser}' LIMIT 1";
			
		if (mysql_fetch_array(mysql_query($query))) {
			return new returnData(4, NULL, 'user exists');
		}
		
		$query = "INSERT INTO editors (name, password, email, comments) 
				  VALUES ('{$strUser}',MD5('$strPassword'),'{$strEmail}','{$strComments}' )";
			
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
		
		//Email the editor login information to them
		
		return new returnData(0, mysql_insert_id());
	}
	
	/**
     * Reset and email editor a new password- NOT IMPLEMENTED
     * @returns 0 on success
     */
	public function resetAndEmailNewPassword($strEmail) {
		return new returnData(0, NULL);
	}
	
		
	/**
     * Email editor account name - NOT IMPLEMENTED
     * @returns 0 on success
     */
	public function emailUserName($strEmail) {
		return new returnData(0, NULL);
	}
	
	
}