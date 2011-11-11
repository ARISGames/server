<?php 
    require_once('../../config.class.php');

    $conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
    mysql_select_db (Config::dbSchema);
    mysql_query("set names utf8");
    mysql_query("set charset set utf8");

	$username = $_POST["username"];
	$password = $_POST["password"];

	$query = "SELECT player_id FROM players WHERE user_name = '{$username}' AND password = '".md5($password)."'";
	$result = mysql_query($query);
	if(mysql_num_rows($result) == 0)
		echo 'false';
	else
	{
		$id = mysql_fetch_object($result);
		echo $id->player_id;
	}
?>
