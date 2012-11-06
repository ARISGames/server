<?php require_once('../config.class.php'); ?>
<?php require_once('pusher_defaults.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Pusher Room</title>
	<script src="http://js.pusher.com/1.11/pusher.min.js" type="text/javascript"></script>
	<script type="text/javascript">

		//RECEIVE
		var pusher = new Pusher('<?php echo Config::pusher_key; ?>');

		//Public
		var pub_channel = pusher.subscribe('<?php echo $public_default_channel; ?>');
		pub_channel.bind('<?php echo $public_default_event; ?>', function(data) {
                        var message = document.createElement('div');
                        message.innerHTML = '<b>Message Received:</b> '+data;
			document.getElementById('public_messages').appendChild(message);
		});

		//Private
		Pusher.channel_auth_endpoint = '<?php echo 'http://dev.arisgames.org/server/events/'.$private_default_auth; ?>';
		var priv_channel = pusher.subscribe('<?php echo $private_default_channel; ?>');
		priv_channel.bind('<?php echo $private_default_event; ?>', function(data) {
                        var message = document.createElement('div');
                        message.innerHTML = '<b>Message Received:</b> '+data;
			document.getElementById('private_messages').appendChild(message);
		});

                /* Can only 'authorize' once- either leave this commented out, or comment out 'Private'
		//Presence
		Pusher.channel_auth_endpoint = '<?php echo 'http://dev.arisgames.org/server/events/'.$presence_default_auth; ?>';
		var pres_channel = pusher.subscribe('<?php echo $presence_default_channel; ?>');
		pres_channel.bind('<?php echo $presence_default_event; ?>', function(data) {
                        var message = document.createElement('div');
                        message.innerHTML = '<b>Message Received:</b> '+data;
			document.getElementById('presence_messages').appendChild(message);
		});
                */

		//SEND
		function sendRequest(data, room)
        	{
                	var xmlhttp;
                	xmlhttp=new XMLHttpRequest();
                        xmlhttp.onreadystatechange = function(){ 
                            if(xmlhttp.readyState == 4)
                            {
                                var message = document.createElement('div');
                                message.innerHTML = '<b>Message Sent:</b> '+xmlhttp.responseText;
			        document.getElementById(room+'_messages').appendChild(message);
                            } 
                        };
                	xmlhttp.open("POST","http://dev.arisgames.org/server/events/send.php",true);
                	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                        switch(room)
                        {
                            case 'public':
                	        xmlhttp.send('channel=<?php echo $public_default_channel; ?>&event=<?php echo $public_default_event; ?>&data='+data);
                                break;
                            case 'private':
                	        xmlhttp.send('channel=<?php echo $private_default_channel; ?>&event=<?php echo $private_default_event; ?>&data='+data);
                                break;
                            case 'presence':
                	        xmlhttp.send('channel=<?php echo $presence_default_channel; ?>&event=<?php echo $presence_default_event; ?>&data='+data);
                                break;
                            default:
                                alert('sent do invalid room...');
                                break;
                        }
        	}

        	function message(room)
        	{
			sendRequest(document.getElementById(room+'_sendtext').value, room);
		}
	</script>
</head>
<body>
	<table>
		<tr>
		<td width="300px">
			PUBLIC
		</td>
		<td width="300px">
			PRIVATE
		</td>
		<td width="300px">
			PRESENCE
                        *will fail to recieve*
		</td>
		</tr>
		<tr>
		<td valign="top" width="300px">
			<input type="text" id="public_sendtext"></input>
			<input type="button" value="Send" onClick="message('public');"></input>
		</td>
		<td valign="top" width="300px">
			<input type="text" id="private_sendtext"></input>
			<input type="button" value="Send" onClick="message('private');"></input>
		</td>
		<td valign="top" width="300px">
			<input type="text" id="presence_sendtext"></input>
			<input type="button" value="Send" onClick="message('presence');"></input>
		</td>
		</tr>
                <tr>
                <td valign="top" width="300px" height="90%">
			<div id="public_messages" style="overflow:scroll; height:500px;">
			Waiting...
			</div>
                </td>
                <td valign="top" width="300px" height="90%">
			<div id="private_messages" style="overflow:scroll; height:500px;">
			Waiting...
			</div>
                </td>
                <td valign="top" width="300px" height="90%">
			<div id="presence_messages" style="overflow:scroll; height:500px;">
			Waiting...
			</div>
                </td>
                </tr>
	</table>
	
</body>
</html>

