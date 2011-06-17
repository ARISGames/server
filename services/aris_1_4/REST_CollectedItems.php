<?php

require_once('../../config.class.php');
require_once('media.php');

$conn = mysql_pconnect(Config::dbHost, Config::dbUser, Config::dbPass);
mysql_select_db (Config::dbSchema);
$prefix = $_REQUEST['gameId'];
$query = "SELECT {$prefix}_items.*, media.*, players.*
			FROM {$prefix}_items, media, players
			WHERE {$prefix}_items.media_id = media.media_id AND {$prefix}_items.creator_player_id = players.player_id";
$result = @mysql_query($query);

if (mysql_error()) die ("<html><body>ERROR: Bad gameId</body></html>");


// Creates an array of strings to hold the lines of the KML file.
$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
$kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
$kml[] = ' <Document>';


// Iterates through the rows, printing a node for each row.
while ($row = @mysql_fetch_assoc($result)) 
{
  $kml[] = ' <Placemark id="placemark' . $row['item_id'] . '">';
  $kml[] = ' <name>' . htmlentities($row['name']) . '</name>';
  
  $mediaURL = Config::gamedataWWWPath . "/{$_REQUEST['gameId']}/{$row['file_name']}";
  
  $mediaObject = new Media;
  $type = $mediaObject->getMediaType($mediaURL);
  
  if ($type == Media::MEDIA_IMAGE) $mediaHtml = "<a target = '_blank' href = '{$mediaURL}'><img src = '$mediaURL' width = '100%'/></a>";
  else if ($type == Media::MEDIA_AUDIO) $mediaHtml = "<a target = '_blank' href = '{$mediaURL}'>Link to Audio</a>"; 
  else if ($type == Media::MEDIA_VIDEO) $mediaHtml = '<div style="margin-left: auto; margin-right:auto;">
              <object height="175" width="212">
                <param value="' . $mediaURL . '" name="movie">
                <param value="transparent" name="wmode">
                <embed wmode="transparent" type="application/x-shockwave-flash"
                   src="'.$mediaURL.'" height="175"
                   width="212">
               </object>
             </div>';

/*
<![CDATA[<div style="font-size:larger">
          <div>
            <div style="width: 212px; font-size: 12px;">
              <b>The Spaghetti Film</b>
            </div>
            <div style="font-size: 11px;">
              <a target="_blank" href="http://www.youtube.com/?v=FICUvrVlyXc">
                http://www.youtube.com/?v=FICUvrVlyXc</a><br>
            </div><br>
            <div style="margin-left: auto; margin-right:auto;">
              <object height="175" width="212">
                <param value="http://www.youtube.com/v/FICUvrVlyXc" name="movie">
                <param value="transparent" name="wmode">
                <embed wmode="transparent" type="application/x-shockwave-flash"
                   src="http://www.youtube.com/v/FICUvrVlyXc" height="175"
                   width="212">
               </object>
             </div>
           </div>
          </div>
          <div style="font-size: smaller; margin-top: 1em;">Saved from 
            <a href="http://maps.google.com/ig/add?synd=mpl&pid=mpl&moduleurl=
                http:%2F%2Fwww.google.com%2Fig%2Fmodules%2Fmapplets-youtube.xml&hl=en&gl=us">
                YouTubeVideos</a>
          </div>
         ]]>

  */
  
            
  
  $description = array("<![CDATA[");
  $description[] = "<strong>Created By:</strong> {$row['user_name']}<br/>";
  $description[] = "<strong>Date:</strong> {$row['origin_timestamp']}<br/>";
  $description[] = '<p>' . htmlentities($row['description']) . '</p>';
  $description[] = $mediaHtml;
  $description[] = "]]>";
  $descriptionHtml = join("\n", $description);
  
  $kml[] = ' <description>' . $descriptionHtml . '</description>';
  $kml[] = ' <Point>';
  $kml[] = ' <coordinates>' . $row['origin_longitude'] . ','  . $row['origin_latitude'] . '</coordinates>';
  $kml[] = ' </Point>';
  $kml[] = ' </Placemark>';
 
} 

// End KML file
$kml[] = ' </Document>';
$kml[] = '</kml>';
$kmlOutput = join("\n", $kml);

switch ($_REQUEST['type']) {
	case 'kml':
		header('Content-type: application/vnd.google-earth.kml+xml');
		header('Content-Disposition: attachment; filename="PlayerCollectedItems.kml"');
		echo $kmlOutput;
		break;
	case 'map':
		$kmlPath = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?type=kml&gameId=' . $_REQUEST['gameId'];
		//echo $kmlPath;
		include ('REST_GoogleMapWithKMLVariable.inc.php');
		break;
	default:
		echo 'Please add a "type" GET variable of "kml" or "map"';	
}


?>