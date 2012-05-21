<html>
<head>
<?php
    require_once('../../config.class.php');
	require_once('npcs.php');
    
    $conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
    mysql_select_db (Config::dbSchema);
    mysql_query("set names utf8");
    mysql_query("set charset set utf8");
    
	$gameId = $_GET['gameId'];
	echo "Game Id: {$gameId}<br />";
	$characters = Npcs::getNpcsInfoForGameIdFormattedForArisConvoOutput(intval($gameId));
?>
</head>
<body>
<table border>
<?php
foreach($characters as $character)
{
	echo "<tr><td colspan='4'><b>$character->name</b></td></td>";
	echo "<tr><td><b>Option</b></td><td><b>Script</b></td><td><b>Requirements</b></td><td><b>Exchanges</b></td></tr>";
	foreach($character->scripts as $script)
	{
		echo "<tr><td>$script->option</td><td>$script->content</td><td><ul>";
		//Display Reqs
		foreach($script->req as $requirement)
		{
			echo "<li>{$requirement->requirement} with id {$requirement->rDetail1} of amount {$requirement->rDetail2} ({$requirement->boole})</li>";
		}
		echo "</ul></td><td><ul>";
		//Display Exchanges
		foreach($script->exchange as $exchange)
		{
			echo "<li>{$exchange->action} with id {$exchange->obj} of amount {$exchange->amount}</li>";
		}
		echo "</ul></td></tr>";
	}
    echo "<tr><td colspan='4'>-</td></tr>";
}
?>
</table>
</body>
</html>