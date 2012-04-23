<!DOCTYPE html>
<html>
<head>
	<title>Choose Video</title>
	<script src="http://js.pusher.com/1.11/pusher.min.js" type="text/javascript"></script>
	<script type="text/javascript">

		//RECEIVE
		var pusher = new Pusher('7fe26fe9f55d4b78ea02');

		//Public
		var pub_channel = pusher.subscribe('arduino-choose_video_channel');
		pub_channel.bind('<?php echo $public_event; ?>', function(data) {
			document.getElementById('public_messages').innerHTML = document.getElementById('public_messages').innerHTML + "<br />\nMessage Received (public): "+data;
		});
		pub_channel.bind('<?php echo "client-".$public_event; ?>', function(data) {
			document.getElementById('public_messages').innerHTML = document.getElementById('public_messages').innerHTML + "<br />\nMessage Received (public- client): "+data;
		});

		//Arduino
		var arduino_channel = pusher.subscribe('<?php echo $arduino_channel; ?>');
		arduino_channel.bind('arduino_event_register', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_register): "+data;
		});
		arduino_channel.bind('arduino_event_1', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_1): "+data;
		});
		arduino_channel.bind('arduino_event_2', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_2): "+data;
		});
		arduino_channel.bind('arduino_event_3', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_3): "+data;
		});
		arduino_channel.bind('arduino_event_4', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_4): "+data;
		});
		arduino_channel.bind('arduino_event_5', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_5): "+data;
		});
		arduino_channel.bind('arduino_event_6', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_6): "+data;
		});
		arduino_channel.bind('arduino_event_7', function(data) {
			document.getElementById('arduino_messages').innerHTML = document.getElementById('arduino_messages').innerHTML + "<br />\nMessage Received (arduino_event_7): "+data;
		});
		

		//SEND
		function sendRequest(data, room)
        	{
                	var xmlhttp;
                	xmlhttp=new XMLHttpRequest();
                	xmlhttp.open("POST","http://arisgames.org/devserver/pusher/"+room+"_send.php",false);
                	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                	xmlhttp.send(room+'_data='+data); //Synchronous call
	
			document.getElementById(room+'_messages').innerHTML = document.getElementById(room+'_messages').innerHTML + "<br />\nMessage Sent ("+room+"): "+xmlhttp.responseText;
        	}

        	function message(room)
        	{
			sendRequest(document.getElementById(room+'_sendtext').value, room);
		}
	</script>
</head>
<body>
	<table>

		<td valign="top">

			<input type="text" id="arduino_sendtext"></input>
			<input type="button" value="Send" onClick="message('arduino');"></input>
			<div id="arduino_messages">
			Waiting...
			</div>

		</td>
		</tr>
	</table>
	
</body>
</html>
