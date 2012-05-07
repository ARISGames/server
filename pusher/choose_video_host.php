<?php require_once('pusher_config.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Host Room</title>
	<script src="http://js.pusher.com/1.12/pusher.min.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
	<script src="json2.js"></script>
	<script type="text/javascript">

		/*
		*
		* <PUSHER STUFF>
		*
		*/

		//Pusher config stuff
		var appKey = '<?php echo $key; ?>';
		var public_channel = 'choose-video';
		var presence_channel = 'presence-choose-video';
		var presence_auth = 'presence_auth.php';

		//My recieved events
		var join_event = 'pusher:member_added';
		var leave_event = 'pusher:member_removed';
		var vote_event = 'vote_cast';
		var video_end_event = 'ready_to_play';
		var subscribe_event = 'pusher:subscription_succeeded';
		
		//My sent events
		var update_event = 'update';
		var play_1_event = 'play_1';
		var play_2_event = 'play_2';

		//For debugging info in host webpage
		var public_console = 'public_console';
		var presence_console = 'presence_console';
		var data_console = 'data_console';

		//RECEIVE
		var pusher = new Pusher(appKey);

		//Public
		var pub_channel = pusher.subscribe(public_channel);
			//VIDEO END
		pub_channel.bind(video_end_event, function(data) {
			if(stateObj.state != PLAYING_STATE) return;
			appendConsole(public_console, "<font color='#00FF00'>RECEIVED:</font> "+video_end_event+"<br />"+JSON.stringify(data));
			if(stateObj.totalVoters > 0) setState(WAITING_FOR_VOTERS_STATE);
			else setState(WAITING_FOR_CLIENT_STATE);
		});

		//Presence
		Pusher.channel_auth_endpoint = presence_auth;
		var pres_channel = pusher.subscribe(presence_channel);
			//START
		pres_channel.bind(subscribe_event, function(members) {
			appendConsole(presence_console, "<font color='#0000FF'>PUSHER:</font> "+members.count);
  			stateObj.totalVoters = members.count-1;
			if(stateObj.totalVoters > 0) setState(WAITING_FOR_VOTERS_STATE);
			update();
		})
			//JOIN
		pres_channel.bind(join_event, function(data) {
			appendConsole(presence_console, "<font color='#00FF00'>RECEIVED:</font> "+join_event+"<br />"+JSON.stringify(data));
			stateObj.totalVoters++;
			if(stateObj.totalVoters == 1) setState(WAITING_FOR_VOTERS_STATE);
			update();
		});
			//LEAVE
		pres_channel.bind(leave_event, function(data) {
			appendConsole(presence_console, "<font color='#00FF00'>RECEIVED:</font> "+leave_event+"<br />"+JSON.stringify(data));
			if(stateObj.totalVoters > 0) stateObj.totalVoters--;
			if(stateObj.totalVoters == 0) setState(WAITING_FOR_CLIENT_STATE);
			update();
		});
			//VOTE
		pres_channel.bind(vote_event, function(data) {
			if(stateObj.state != VOTING_STATE) return;
			appendConsole(presence_console, "<font color='#00FF00'>RECEIVED:</font> "+vote_event+"<br />"+data);
			if(data == "1") stateObj.votesFor1++;
			if(data == "2") stateObj.votesFor2++;
		});

		//SEND
		function sendRequest(channel, event, data, console)
        	{
                	var xmlhttp;
                	xmlhttp=new XMLHttpRequest();
                	xmlhttp.open("POST","http://dev.arisgames.org/server/pusher/pusher_send.php",false);
                	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			var postData = "";
			if(channel != "") postData += "channel="+channel+"&";
			if(event != "") postData += "event="+event+"&";
			if(data != "") postData += "data="+data+"&";
			postData = postData.substring(0,postData.length-1);
                	xmlhttp.send(postData); //Synchronous call
	
			appendConsole(console, "<font color='#FF0000'>SENT:</font> "+postData);
        	}

		function appendConsole(console, info)
		{
			document.getElementById(console).innerHTML = "<br />"+info+"<br />" + document.getElementById(console).innerHTML;
		}

		function clearConsoles()
		{
			document.getElementById(public_console).innerHTML = "";
			document.getElementById(presence_console).innerHTML = "";
		}

		function update()
		{
			sendRequest(presence_channel, update_event, JSON.stringify(stateObj), presence_console);
			document.getElementById(data_console).innerHTML = "STATE:"+stateObj.state+"<br />TOTAL VOTERS:"+stateObj.totalVoters+"<br />VOTESFOR1:"+stateObj.votesFor1+"<br />VOTESFOR2:"+stateObj.votesFor2+"<br />TIMEREMAINING:"+stateObj.timeRemaining;
		}

		function play()
		{
			var event = (stateObj.votesFor1 >= stateObj.votesFor2) ? play_1_event : play_2_event;
			sendRequest(public_channel, event, JSON.stringify(stateObj), public_console);
			stateObj.votesFor1 = 0;
			stateObj.votesFor2 = 0;
		}

		/*
		*
		* </PUSHER STUFF>
		*
		*/

		/*
		*
		* <VOTE DATA>
		*
		*/
		
		//States
		var WAITING_FOR_CLIENT_STATE = "WAITING_FOR_CLIENT";
		var WAITING_FOR_VOTERS_STATE = "WAITING_FOR_VOTERS";
		var VOTING_STATE = "VOTING";
		var PLAYING_STATE = "PLAYING";
	
		//Dials 'n Knobs
		var TIME_TO_WAIT_FOR_JOIN = 15;
		var TIME_TO_WAIT_FOR_VOTE = 20;

		//Containers
		var timer;
		function timerTick()
		{

			if(stateObj.timeRemaining == 0 || (stateObj.votesFor1 + stateObj.votesFor2 >= stateObj.totalVoters))
			{
				if(stateObj.state == WAITING_FOR_VOTERS_STATE)
				{
					setState(VOTING_STATE);
				}
				else if(stateObj.state == VOTING_STATE)
				{
					if(stateObj.totalVoters > 0) setState(PLAYING_STATE);
					else setState(WAITING_FOR_CLIENT_STATE);
				}
			}
			else
			{
				stateObj.timeRemaining--;
				timer = setTimeout('timerTick()',1000);
			}
			update();
		}
		var stateObj = new Object();
		stateObj.totalVoters = 0;
		function resetStateObj()
		{
			stateObj.state = WAITING_FOR_CLIENT_STATE;
			//stateObj.totalVoters = 0; //NEVER SET THIS!!!
			stateObj.votesFor1 = 0;
			stateObj.votesFor2 = 0;
			stateObj.timeRemaining = 0;
		}
		resetStateObj();

		function setState(state)
		{
			stateObj.state = state;
			switch(state){
				case WAITING_FOR_CLIENT_STATE:
					resetStateObj();
					break;
				case WAITING_FOR_VOTERS_STATE:
					stateObj.timeRemaining = TIME_TO_WAIT_FOR_JOIN;
					timer = setTimeout('timerTick()',1000);
					break;
				case VOTING_STATE:
					stateObj.timeRemaining = TIME_TO_WAIT_FOR_VOTE;
					timer = setTimeout('timerTick()',1000);
					break;
				case PLAYING_STATE:
					play();
					break;
			}
		}

		/*
		*
		* </VOTE DATA>
		*
		*/
	
		function updateNumVoters()
		{
			stateObj.totalVoters = document.getElementById('numVoterChanger').value;
			update();
		}

		function load()
		{
			document.getElementById(data_console).innerHTML = "STATE:"+stateObj.state+"<br />TOTAL VOTERS:"+stateObj.totalVoters+"<br />VOTESFOR1:"+stateObj.votesFor1+"<br />VOTESFOR2:"+stateObj.votesFor2+"<br />TIMEREMAINING:"+stateObj.timeRemaining;
		}
	</script>
</head>
<body onload="load()">
	<input type='button' value='clear consoles' onClick='clearConsoles()' /><br />
	<table>
		<tr>
		<td width="300px">
			<u>DATA</u><br />
	<input id='numVoterChanger' type='text' value='' />
	<input type='button' value='update num voters' onClick='updateNumVoters()' />
		</td>
		<td width="300px">
			<u>PUBLIC</u>
		</td>
		<td width="300px">
			<u>PRESENCE</u>
		</td>
		</tr>
		<tr>
		<td valign="top">
	
			<div id="data_console">
			</div>

		</td>
		<td valign="top">
	
			<div id="public_console">
			</div>

		</td>
		<td valign="top">

			<div id="presence_console">
			</div>

		</td>
		</tr>
	</table>
	<input type='button' value='clear consoles' onClick='clearConsoles()' />
	
</body>
</html>

