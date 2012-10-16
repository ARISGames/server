<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name = "viewport" content = "width = device-width">

<link rel="stylesheet" href="./stats.css">

<?php

// CONSTANTS

$numTopGames = 10; // number of top games to show in list
$CURDIR = __DIR__ . DIRECTORY_SEPARATOR;

require($CURDIR . '../config.class.php');
$sqlLink = mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass) or die('MySQL error: ' . mysql_error());
mysql_select_db(Config::dbSchema) or die('MySQL error: ' . mysql_error());

    $query = 'SELECT COUNT(DISTINCT player_id) AS count FROM player_log WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND NOW()';
    $result = mysql_query($query);
    $numCurrentPlayers1Object = mysql_fetch_object($result);
    $numCurrentPlayers1 = $numCurrentPlayers1Object->count;
    
    $query = 'SELECT COUNT(DISTINCT player_id) AS count FROM player_log WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND NOW()';
    $result = mysql_query($query);
    $numCurrentPlayers15Object = mysql_fetch_object($result);
    $numCurrentPlayers15 = $numCurrentPlayers15Object->count;
    
    $query = 'SELECT COUNT(DISTINCT player_id) AS count FROM player_log WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND NOW()';
    $result = mysql_query($query);
    $numCurrentPlayers60Object = mysql_fetch_object($result);
    $numCurrentPlayers60 = $numCurrentPlayers60Object->count;
    
    $query = 'SELECT COUNT(DISTINCT player_id) AS count FROM player_log WHERE timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW()';
    $result = mysql_query($query);
    $numCurrentPlayers1440Object = mysql_fetch_object($result);
    $numCurrentPlayers1440 = $numCurrentPlayers1440Object->count;

?>

<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">


function initialize()
{
  var latlng = new google.maps.LatLng(0,0);
  var myOptions = {
    zoom: 2,
    center: latlng,
    mapTypeId: google.maps.MapTypeId.SATELLITE };

  var map = new google.maps.Map(document.getElementById("mapContainer"), myOptions);

  <?php generatePlayerLocations(); ?>
  <?php generateGameLocations(); ?>
  
  ShowDiv('topTenElementForWeek');
  currentTopTenElement = 'topTenElementForWeek';

  populateGraphs();
}

function populateGraphs()
{
  <?php generateGraphData() ?>

  var playersGraph = document.getElementById("playersGraph");      
  var cwidth = playersGraph.width;
  var cheight = playersGraph.height;
  var barwidth = cwidth/range;
  var ctx=playersGraph.getContext("2d");
  ctx.fillStyle="#FF9900";
  for(var i = 0; i < range; i++)
  {
    ctx.fillRect(cwidth-(i*barwidth),cheight,-1*barwidth,-1*gdata.players.countarray[i]/gdata.players.max*cheight);
  }

  var gamesGraph = document.getElementById("gamesGraph");      
  var cwidth = gamesGraph.width;
  var cheight = gamesGraph.height;
  var barwidth = cwidth/range;
  var ctx=gamesGraph.getContext("2d");
  ctx.fillStyle="#336699";
  for(var i = 0; i < range; i++)
  {
    ctx.fillRect(cwidth-(i*barwidth),cheight,-1*barwidth,-1*gdata.games.countarray[i]/gdata.games.max*cheight);
  }

  var editorsGraph = document.getElementById("editorsGraph");      
  var cwidth = editorsGraph.width;
  var cheight = editorsGraph.height;
  var barwidth = cwidth/range;
  var ctx=editorsGraph.getContext("2d");
  ctx.fillStyle="#7BB31A";
  for(var i = 0; i < range; i++)
  {
    ctx.fillRect(cwidth-(i*barwidth),cheight,-1*barwidth,-1*gdata.editors.countarray[i]/gdata.editors.max*cheight);
  }
}

// topTenArray and topTenButtons must be parallel arrays

var topTenArray = ['topTenElementForDay', 'topTenElementForWeek', 'topTenElementForMonth'];
var topTenButtons = ['topTenDayButton', 'topTenWeekButton', 'topTenMonthButton'];

function enableTopTenItem(divId)
{
  for (var i = 0; i < topTenArray.length; i++)
  {
    if (divId == topTenArray[i])
    {
      document.getElementById(divId).style.display= 'block';
      document.getElementById(topTenButtons[i]).style.backgroundColor = "#336699";
    }
    else
    {
      document.getElementById(topTenArray[i]).style.display= 'none';
      document.getElementById(topTenButtons[i]).style.backgroundColor = "#555";
    }
  }
  currentTopTenElement = divId;
}

function ShowDiv(divId)
{
  document.getElementById(divId).style.display= 'block';
}

function ShowHide(divId)
{
  if (document.getElementById(divId).style.display == 'none')
  {
    document.getElementById(divId).style.display= 'block';
  }
  else
  {
    document.getElementById(divId).style.display = 'none';
  }
}

// API allows only 20 reverse geocoding lookups in a short amount of time (not feasible)
function reverseLatLng(lat, lng)
{
  var latlng = new google.maps.LatLng(lat, lng);
  var geocoder = new google.maps.Geocoder();
  geocoder.geocode({'latLng': latlng}, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK)
    {
      if (results[1])
      {
        return results[1].formatted_address;
      } 
      else
      {
        alert("No results found");
      }
    } 
    else
    {
      alert("Geocoder failed due to: " + status);
    }});
}

</script>


<script type="text/javascript">
//Google Analytics
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-10031673-2']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<title>ARIS live stats</title>
</head>

<body onload="initialize()">

<div id="topPlate" class="backgroundPlate"> </div>

<div id="main">
<div id="header" >
  <a href="http://arisgames.org">
    <img src="http://arisgames.org/wp-content/uploads/2010/08/ARISLogo1.png" id="title" class="png" alt="ARIS - Mobile Learning Experiences" />
  </a>   
  <span id="logotext">LIVE STATS</span>
</div>

<div id = "mapEncasing">
    <div id="mapContainer"></div>
<img src="shadow.png" alt="shadow" style="width:100%"/>

<div id = "onlineNow" style ="color:white;position:relative;bottom:65px;left:80px;z-index:500000000;">
<?php echo "<b>Unique Players Now: $numCurrentPlayers1 | 15 Minutes: $numCurrentPlayers15 | 1 Hour: $numCurrentPlayers60 | Past 24 Hours: $numCurrentPlayers1440</b>"; ?>
</div>
<br />

<div id="mainStatsContainer">

  <div id="totalPlayers" class="statsContainer">
    <p class="bigNum"><span style="color: #FF9900"><?php generatePlayersTotal(); ?></span></p>
    <p class="bigText">players</p>
    <canvas id="playersGraph" class="graph">
    </canvas>
    <div class="graphText">new players/month</div>
  </div>
  
  <div id="totalEditors" class="statsContainer">
    <p class="bigNum"><span style="color: #7BB31A"><?php generateEditorsTotal(); ?></span></p>
    <p class="bigText">creators</p>
    <canvas id="editorsGraph" class="graph">
    </canvas>
    <div class="graphText">new creators/month</div>
  </div>
  
  <div id="totalGames" class="statsContainer">
    <p class="bigNum"><span style="color: #336699"><?php generateGamesTotal(); ?></span></p>
    <p class="bigText">games</p>  
    <canvas id="gamesGraph" class="graph">
    </canvas>
    <div class="graphText">new games/month</div>
  </div>
</div>

<div id="topTenGames">
  <h1 style="margin-bottom: 5px">top <?php echo $GLOBALS['numTopGames']; ?> games of the</h1>
  
  <div id="topTenButtonBox">
    <button id="topTenDayButton" onclick="javascript:enableTopTenItem('topTenElementForDay')">day</button>
    <button id="topTenWeekButton" onclick="javascript:enableTopTenItem('topTenElementForWeek')">week</button>
    <button id="topTenMonthButton" onclick="javascript:enableTopTenItem('topTenElementForMonth')">month</button>
    <script type="text/javascript">
      document.getElementById("topTenWeekButton").style.backgroundColor = '#336699';
    </script> 
  </div>   
  
  <?php @generateTopGames(day); ?>
  <?php @generateTopGames(week); ?>
  <?php @generateTopGames(month); ?>
  
</div>

</div>

<div id="bottomPlate" class="backgroundPlate"> 
  <div id="bottomBar">
    <div id="feed" style="float:left;">
      Stay Connected
      <a href="http://www.arisgames.org/feed"></a>
      <a href="http://www.flickr.com/photos/academictech/sets/72157623910424967/"><img alt="flickr icon" src="http://arisgames.org/wp-content/themes/Play/images/profiles/rss_16.png" /></a>
    </div>
    <div id="links" style="float:right;">
      <a href="http://www.arisgames.org/press">Press</a> &nbsp;
      <a href="http://www.arisgames.org/design-jam-recap">Design Jam Recap</a> &nbsp;
      <a href="http://www.arisgames.org/demo">Demo</a> &nbsp;
      <a href="http://www.arisgames.org/blog">Community Blog</a> &nbsp;
      <a href="http://www.arisgames.org/projects-and-papers">Projects</a> &nbsp;
      <a href="http://www.arisgames.org/design-team">Design Team</a> &nbsp;
    </div>
  </div>
</div>

</body>
</html>

<?php

function truncate_text($text, $nbrChar, $append='...')
{
  if(strlen($text) > $nbrChar)
  {
    $text = substr($text, 0, $nbrChar);
    $text .= $append;
  }
  return $text;
}

function generatePlayerLocations()
{
  // do not include locations that are test locations (0,0)
  $query = 'SELECT latitude, longitude
            FROM players
            WHERE latitude <> 0
            AND longitude <> 0';

  $interval = ' AND updated BETWEEN DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND NOW()';
  $result = mysql_query($query.$interval);
  while ($row = mysql_fetch_object($result))
    echo 'new google.maps.Marker({ position: new google.maps.LatLng(' . $row->latitude . ',' . $row->longitude . '), map: map, icon: \'http://arisgames.org/server/stats/map_icons/player_alpha_100.png\' });' . "\n";


  $interval = ' AND updated BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW()';
  $result = mysql_query($query.$interval);
  while ($row = mysql_fetch_object($result))
    echo 'new google.maps.Marker({ position: new google.maps.LatLng(' . $row->latitude . ',' . $row->longitude . '), map: map, icon: \'http://arisgames.org/server/stats/map_icons/player_alpha_66.png\' });' . "\n";

/*
  $interval = ' AND updated BETWEEN DATE_SUB(NOW(), INTERVAL 1 WEEK) AND NOW()';
  $result = mysql_query($query.$interval);
  while ($row = mysql_fetch_object($result))
    echo 'new google.maps.Marker({ position: new google.maps.LatLng(' . $row->latitude . ',' . $row->longitude . '), map: map, icon: \'http://arisgames.org/server/stats/map_icons/player_alpha_33.png\' });' . "\n";
*/
}

function generateGameLocations()
{
    return; //<- UNCOMMENT TO SHOW GAME LOCATIONS AS WELL
  // do not include locations that are test locations (0,0)
  $query = "SELECT DISTINCT games.game_id, games.created, players.latitude, players.longitude FROM games LEFT JOIN player_log ON games.game_id = player_log.game_id LEFT JOIN players ON player_log.player_id = players.player_id WHERE players.latitude IS NOT NULL";

  $interval = ' AND games.created BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW() GROUP BY games.game_id';
  $result = mysql_query($query.$interval);
  while ($row = mysql_fetch_object($result))
    echo 'new google.maps.Marker({ position: new google.maps.LatLng(' . $row->latitude . ',' . $row->longitude . '), map: map, icon: \'http://arisgames.org/server/stats/map_icons/game_alpha_100.png\' });' . "\n";


  $interval = ' AND games.created BETWEEN DATE_SUB(NOW(), INTERVAL 1 WEEK) AND NOW() GROUP BY games.game_id';
  $result = mysql_query($query.$interval);
  while ($row = mysql_fetch_object($result))
    echo 'new google.maps.Marker({ position: new google.maps.LatLng(' . $row->latitude . ',' . $row->longitude . '), map: map, icon: \'http://arisgames.org/server/stats/map_icons/game_alpha_66.png\' });' . "\n";

  $interval = ' AND games.created BETWEEN DATE_SUB(NOW(), INTERVAL 1 MONTH) AND NOW() GROUP BY games.game_id';
  $result = mysql_query($query.$interval);
  while ($row = mysql_fetch_object($result))
    echo 'new google.maps.Marker({ position: new google.maps.LatLng(' . $row->latitude . ',' . $row->longitude . '), map: map, icon: \'http://arisgames.org/server/stats/map_icons/game_alpha_33.png\' });' . "\n";

}

function generateGamesTotal()
{
  $query = 'SELECT COUNT(DISTINCT game_id) AS count FROM games';
  $result = mysql_query($query);
  
  $numGames = mysql_fetch_object($result)->count;
  
  echo $numGames;
}

function generatePlayersTotal()
{
  $query = 'SELECT COUNT(DISTINCT player_id) AS count FROM players';
  $result = mysql_query($query);
  
  $numPlayers = mysql_fetch_object($result)->count;
  
  echo $numPlayers;
}

function generateEditorsTotal()
{
  $query = 'SELECT COUNT(DISTINCT editor_id) AS count FROM editors';
  $result = mysql_query($query);
  
  $numEditors = mysql_fetch_object($result)->count;
  
  echo $numEditors;
}

function generateTopGames($timeframe)
{
  if ($timeframe == 'day')
  {
    $topTenDivName = 'topTenElementForDay';
    $queryInterval = '1 DAY';
  }
  else if ($timeframe == 'week')
  {
    $topTenDivName = 'topTenElementForWeek';
    $queryInterval = '7 DAY';
  }
  else if ($timeframe == 'month')
  {
    $topTenDivName = 'topTenElementForMonth';
    $queryInterval = '1 MONTH';
  }

  $query = '
SELECT media.file_name as file_name, temp.game_id, temp.name, temp.description, temp.count FROM
(SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count
FROM games
INNER JOIN player_log ON games.game_id = player_log.game_id
WHERE player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL '.$queryInterval.') AND NOW()
GROUP BY games.game_id 
HAVING count > 1) as temp 
LEFT JOIN media ON temp.icon_media_id = media.media_id 
GROUP BY game_id
HAVING count > 1
ORDER BY count DESC
';

  $result = mysql_query($query);

  $counter = 0;

	echo "<div id=\"" . $topTenDivName . "\">\n";
	
  while ($game = mysql_fetch_object($result))
  {
	  $counter++;
	  
    if ($counter > $GLOBALS['numTopGames'])
    {
      break;
    }
    
	  $name = $game->name;
	  $gameid = $game->game_id;
	  $count = $game->count;
	  $iconFileURL = $game->file_name;
	  $description = truncate_text($game->description, 215);   
    
      $query = "SELECT name FROM game_editors LEFT JOIN editors ON game_editors.editor_id = editors.editor_id WHERE game_editors.game_id = $gameid";
      
      //$query  = "SELECT * FROM game_editors LEFT JOIN editors ON game_editors.editor_id = editors.editor_id WHERE game_editors.game_id = $gameid";
           
      $authorResult = mysql_query($query);
      $authors = array();
      while($author = mysql_fetch_object($authorResult))
          $authors[] = $author;
	  echo "<div class=\"topTenElement\">\n";
	  
	  if ($iconFileURL)
	  {
	    $iconURL = 'http://www.arisgames.org/server/gamedata/' . $gameid . '/' . $iconFileURL;
	  }
    else
    {      
      $iconURL = 'defaultLogo.png';
    }
    echo '<div class="topTenNumBox"><div class="topTenNum"><img class="topTenImg" alt="img" width="64" height="64" src="' . $iconURL . "\" /></div></div>\n";
	  echo '<div class="topTenGameNameAndDesc"><p class="topTenName"><strong>' . $name . "</strong></p>\n";
      $editorString = '<p class="topTenAuthor">';
      foreach ($authors as $author)
	  $editorString =  $editorString.$author->name .", ";
      
      $editorString = substr($editorString,0,strlen($editorString)-2);
      $editorString =  $editorString."</p>";
      echo $editorString;

	  echo '<p class="topTenDescription">' . $description . "</p></div>\n";
	  echo '<div class="topTenGameCount"><span class="topTenPlayerCount">' . $count .'</span><p class="topTenPlayerText">players</p></div>';
	  
	  /*
	  // get the location (use the google maps api to get a name for the long/lat)

    $query2 = 'SELECT longitude, latitude FROM ' . $game->id . '_locations LIMIT 1';
    $result2 = mysql_query($query2);
    
    if (mysql_num_rows($result2) == 1)
    {
      $row = mysql_fetch_object($result2);
      
      if ($row->latitude != 0 && $row->longitude != 0)
      {      
        $toplat = $row->latitude;
        $toplong = $row->longitude;
      }
    }   
    
	  echo '<div class="topTenLocation">';
	  //echo '<script type="text/javascript">';
	  //echo 'document.write(reverseLatLng(' . $toplat . ', ' . $toplong . '));</script>';
    //echo "</div>\n"; */
     
    echo "</div>\n"; 
  }
  echo "</div>\n";
}

function monthsago($i)
{
	return "DATE_SUB(NOW(), INTERVAL $i MONTH)";
}

function getCountsForTable($t, $range)
{
	$query = "SELECT SUM(created BETWEEN ".monthsago(1)." AND ".monthsago(0).") AS '_1_months_ago'";
	for($i = 1; $i < $range; $i++)
	{
		$query = $query . ", SUM(created BETWEEN ".monthsago($i+1)." AND ".monthsago($i).") AS '_".($i+1)."_months_ago'";
	}
	$query = $query." FROM $t;";
	//echo "/*query=".$query."*/\n";
	$result = mysql_query($query);
	$counts = mysql_fetch_array($result);

	$countarray = array();
	$max = 0;
	for($i = 0; $i < $range; $i++)
	{
		$countarray[] = $counts[$i];	
		if($counts[$i] > $max)
			$max = $counts[$i];
	}
	$graph->countarray = $countarray;
	$graph->max = $max;
	return $graph;
}

function generateGraphData()
{
  $range = 24;
  $graphData->players = getCountsForTable("players", $range);
  $graphData->games = getCountsForTable("games", $range);
  $graphData->editors = getCountsForTable("editors", $range);
  
  $graphData = json_encode($graphData);
  echo "var gdata = $graphData;";
  echo "var range = $range;";
}

?>
