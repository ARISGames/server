<?php

echo '
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>ARIS Player Created Media</title>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
function initialize() {
    var myLatlng = new google.maps.LatLng(49.496675,-102.65625);
    var myOptions = {
zoom: 4,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }

    var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    var georssLayer = new google.maps.KmlLayer("' . $kmlPath .'");
    georssLayer.setMap(map);
}
</script>
</head>
<body style="margin:0px; padding:0px;" onload="initialize()">
<div id="map_canvas" style="width:100%; height:100%"></div>
</body>
</html>';

?>
