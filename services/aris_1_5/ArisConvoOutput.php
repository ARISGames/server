<html>
<head>
<?php
	require_once('../../config.class.php');
	require_once('npcs.php');
	$gameId = $_GET['gameId'];
	echo "Game Id: {$gameId}<br />";
	$characters = Npcs::getNpcsInfoForGameIdFormattedForArisConvoOutput($gameId);
?>
</head>
<body>
<table border>
<?php
foreach($characters as $character)
{
	echo "<tr><td>$character->name</td></td>";
	echo "<tr><td>Option</td><td>Script</td><td>Requirements</td><td>Exchanges</td></tr>";
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
}
?>
</table>
</body>
</html>