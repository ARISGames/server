<html>
<head>
<?php
    require_once('../../config.class.php');
    require_once('items.php');

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
<br />
<table>
<?php
foreach($backPack->contents as $content)
{
	echo "<tr><td><table border='1'>";
	$numAttribs = 2;
	$colTitles = "<tr><td align=\"center\" valign=\"top\"></td><td align=\"center\" valign=\"top\"><b>Quantity</b></td>";
	$colData = "<tr><td align=\"center\" valign=\"top\"><img src = \"../../gamedata/{$content->icon_game_id}/{$content->icon_file_name}\" alt=\"{$content->icon_name}\" title=\"{$content->icon_name}\"/><br /><b>{$content->name}</b></td><td align=\"center\" valign=\"top\">{$content->qty}</td>";

	if($content->type == "NORMAL")
	{
		if($content->weight != 0)
		{
			$numAttribs++;
			$colTitles = $colTitles."<td align=\"center\" valign=\"top\"><b>Weight</b></td>";
			$colData = $colData."<td align=\"center\" valign=\"top\">{$content->weight}</td>";
		}

		if($content->media_name)
		{
			$numAttribs++;
			$colTitles = $colTitles."</td><td align=\"center\" valign=\"top\"><b>Media</b></td>";
			$colData = $colData."<td align=\"center\" valign=\"top\"><img src=\"../../gamedata/{$content->media_game_id}/{$content->media_file_name}\" alt=\"{$content->media_name}\" title=\"{$content->media_name}\" /></td>";
		}
	}
	else if($content->type == "URL")
	{
		$numAttribs++;
		$colTitles = $colTitles."<td align=\"center\" valign=\"top\"><b>URL</b></td>";
		$colData = $colData."<td align=\"center\" valign=\"top\"><a href=\"{$content->url}\">Web</a></td>";
	}

	$colTitles = $colTitles."</tr>";
	$colData = $colData."</tr>";

	echo $colTitles;
	echo $colData;

	echo "</table></td></tr><tr><td><br /><br /></td></tr>";
}
?>
</table>
</body>
</html>
