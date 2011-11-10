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
	$playerId = $_GET['playerId'];
	echo "Game Id: {$gameId}<br />";
	echo "Player Id: {$playerId}<br />";
	$backPack = Items::getInfoForWebBackpack($gameId,$playerId);
	echo "Player: {$backPack->owner->user_name}<br />";
?>
</head>
<body>
<table>
<?php
foreach($backPack->contents as $content)
{
	echo "<tr><td><table>";
	$numAttribs = 2;
	$colTitles = "<tr><td><b>Icon</b></td><td><b>Quantity</b></td>";
	$colData = "<tr><td><img src = \"{$content->icon_file_name}\" alt=\"{$content->icon_name}\" title=\"{$content->icon_name}\"/></td><td>{$content->qty}</td>";

	if($content->type == "NORMAL")
	{
		if($content->weight != 0)
		{
			$numAttribs++;
			$colTitles = $colTitles."<td><b>Weight</b></td>";
			$colData = $colData."<td>{$content->weight}</td>";
		}

		if($content->media_name)
		{
			$numAttribs++;
			$colTitles = $colTitles."</td><td><b>Media</b></td>";
			$colData = $colData."<td><img src=\"{$content->media_file_name}\" alt=\"{$content->media_name}\" title=\"{$content->media_name}\" /></td>";
		}
	}
	else if($content->type == "URL")
	{
		$numAttribs++;
		$colTitles = $colTitles."<td><b>URL</b></td>";
		$colData = $colData."<td><a href=\"{$content->url}\">Web</a></td>";
	}

	$colTitles = $colTitles."</tr>";
	$colData = $colData."</tr>";

	echo "<tr><td colspan='{$numAttribs}'><b>$content->name</b></td></td>";
	echo $colTitles;
	echo $colData;

	echo "</table></td></tr><br />";
}
?>
</table>
</body>
</html>
