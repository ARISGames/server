<?php require_once('pusher_config.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Seasons Game</title>
	<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
	<script src="http://js.pusher.com/1.11/pusher.min.js" type="text/javascript"></script>
	<script type="text/javascript">

		var delay = 2000;

		var seasons = ['Spring', 'Summer', 'Fall', 'Winter'];
		var seasonsSurvived = [false, false, false, false];
		var seasonHits = [
			[0,0,0], //Sprint
			[-50,0,0],//Summer
			[0,0,0], //Fall
			[0,0,-50]];//Winter
		var SEASON_SPRING = 0;
		var SEASON_SUMMER = 1;
		var SEASON_FALL = 2;
		var SEASON_WINTER = 3;
		var curSeason = SEASON_SPRING; 

		//Graphical
		var graph;
		var ctx;
		var bars = [100, 100, 100];//Keep these 100- to change size edit graph html
		var bar_reducts = [2,4,3];
		var bar_increase = [10,10,10];
		var BAR_FOOD = 0;
 		var BAR_HEALTH = 1;
		var BAR_HEAT = 2;

		var pusher = new Pusher('<?php echo $key; ?>');
		Pusher.channel_auth_endpoint = '<?php echo $private_auth; ?>';

		var arduino_test_channel = pusher.subscribe('<?php echo $arduino_channel;?>');
		arduino_test_channel.bind('arduino_event_register', function(data) { document.getElementById('season').innerHTML = "Connected!"; });
		arduino_test_channel.bind('arduino_event_1', function(data){ arduinoEvent(1); });
		arduino_test_channel.bind('arduino_event_2', function(data){ arduinoEvent(2); });
		arduino_test_channel.bind('arduino_event_3', function(data){ arduinoEvent(3); });
		arduino_test_channel.bind('arduino_event_4', function(data){ arduinoEvent(4); });
		arduino_test_channel.bind('arduino_event_5', function(data){ arduinoEvent(5); });
		arduino_test_channel.bind('arduino_event_6', function(data){ arduinoEvent(6); });
		arduino_test_channel.bind('arduino_event_7', function(data){ arduinoEvent(7); });

		function load()
		{
			graph = document.getElementById("graph");
			graph.spacing = 10;
			ctx = graph.getContext("2d");

			ctx.fillStyle = "#000000";
			ctx.fillRect(0, 0, graph.width, graph.height);
			ctx.fillStyle = "#FF0000";
			var barwidth = graph.width/bars.length;
			for(var i = 0; i < bars.length; i++)
			{
				ctx.fillRect((i*barwidth)+graph.spacing, graph.height, barwidth-(2*graph.spacing), -1 * (bars[i] * graph.height/100));
			}
			window.setTimeout("tick()", delay);
		}

		function arduinoEvent(i)
		{
			switch(i)
			{
			case 1:
				bars[BAR_FOOD]+=bar_increase[BAR_FOOD];
				break;
			case 2:
				bars[BAR_HEALTH]+=bar_increase[BAR_HEALTH];
				break;
			case 3:
				bars[BAR_HEAT]+=bar_increase[BAR_HEAT];
				break;
			case 4:
				hitSeason(SEASON_SPRING);
				break;
			case 5:
				hitSeason(SEASON_SUMMER);
				break;
			case 6:
				hitSeason(SEASON_FALL);
				break;
			case 7:
				hitSeason(SEASON_WINTER);
				break;
			}
			displayGraphs();
		}

		function hitSeason(season)
		{
			seasonsSurvived[curSeason] = true;
			for(var i = 0; i < bars.length; i++)
				bars[i]+=seasonHits[season][i];
			curSeason = season;
			document.getElementById('season').innerHTML = seasons[season];
			displayGraphs();
		}

		function checkLoss()
		{
			for(var i = 0; i < bars.length; i++)
				if(bars[i] == 0) return true;
			return false;
		}

		function checkWin()
		{
			for(var i = 0; i < seasons.length; i++)
				if(!seasonsSurvived[i]) return false;
			return true;
		}

		function displayGraphs()
		{
			ctx.fillStyle = "#000000";
			ctx.fillRect(0, 0, graph.width, graph.height);
			ctx.fillStyle = "#FF0000";
			var barwidth = graph.width/bars.length;
			for(var i = 0; i < bars.length; i++)
			{
				if(bars[i] < 0) bars[i]=0;
				if(bars[i] > 100) bars[i]=100;
				ctx.fillRect((i*barwidth)+graph.spacing, graph.height, barwidth-(2*graph.spacing), -1 * (bars[i] * graph.height/100));
			}
		}

		function tick()
		{
			for(var i = 0; i < bars.length; i++)
			{
				bars[i]-=bar_reducts[i];
			}
			displayGraphs();

			if(checkLoss()) document.getElementById('season').innerHTML = "LOSE";
			else
			{
				if(!checkWin()) window.setTimeout("tick()", delay);
				else document.getElementById('season').innerHTML = "WIN";
			}
		}

	</script>
</head>
<body onLoad="load();">
	<div id="season" style="position:absolute; color:white;">waiting...</div>	
	<canvas id="graph" width="300" height="400" spacing="10"></canvas>
	<div id="BarLabels" style="position:relative; top:-20px;">
		<span id="Food" style="position:absolute; left:15px;">Food</span>
		<span id="Health" style="position:absolute; left:115px;">Health</span>
		<span id="Heat" style="position:absolute; left:215px;">Heat</span>
	</div>
</body>
</html>
