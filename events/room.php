<?php require_once('../config.class.php'); ?>
<?php require_once('pusher_defaults.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Pusher Room</title>
    <script src="http://js.pusher.com/1.11/pusher.min.js" type="text/javascript"></script>
    <script type="text/javascript">
    //Separate script tag, because it's just a helper

        function humanTimeStamp()
        {
            var date = new Date();
            var datevalues = [
                date.getFullYear(),
                date.getMonth()+1,
                date.getDate(),
                date.getHours(),
                date.getMinutes(),
                date.getSeconds()
            ];
            return date.getHours()+":"+date.getMinutes()+":"+date.getSeconds();
        }

    </script>
    <script type="text/javascript">

	//RECEIVE
	var pusher = new Pusher('<?php echo Config::pusher_key; ?>');

	//Public
	var pub_channel = pusher.subscribe('<?php echo $public_default_channel; ?>');
	pub_channel.bind('<?php echo $public_default_event; ?>', function(data) {
            var message = document.createElement('div');
            message.innerHTML = "<div class='messagetitle receivedmessagetitle'>Received: <span class='messagesubtitle'>("+humanTimeStamp()+")</span></div><div class='messagecontent'>"+data+"</div>";
	    document.getElementById('public_messages').insertBefore(message,document.getElementById('public_messages').firstChild);
	});

	//Private
	Pusher.channel_auth_endpoint = '<?php echo 'http://dev.arisgames.org/server/events/'.$private_default_auth; ?>';
	var priv_channel = pusher.subscribe('<?php echo $private_default_channel; ?>');
	priv_channel.bind('<?php echo $private_default_event; ?>', function(data) {
            var message = document.createElement('div');
            message.innerHTML = "<div class='messagetitle receivedmessagetitle'>Received: <span class='messagesubtitle'>("+humanTimeStamp()+")</span></div><div class='messagecontent'>"+data+"</div>";
	    document.getElementById('private_messages').insertBefore(message,document.getElementById('private_messages').firstChild);
	});

        /* Can only 'authorize' once- either leave this commented out, or comment out 'Private'
	//Presence
	Pusher.channel_auth_endpoint = '<?php echo 'http://dev.arisgames.org/server/events/'.$presence_default_auth; ?>';
	var pres_channel = pusher.subscribe('<?php echo $presence_default_channel; ?>');
	pres_channel.bind('<?php echo $presence_default_event; ?>', function(data) {
            var message = document.createElement('div');
            message.innerHTML = "<div class='messagetitle receivedmessagetitle'>Received: <span class='messagesubtitle'>("+humanTimeStamp()+")</span></div><div class='messagecontent'>"+data+"</div>";
	    document.getElementById('presence_messages').insertBefore(message,document.getElementById('presence_messages').firstChild);
	});
        */

	//SEND
	function sendRequest(room, channel, event, data)
        {
            var xmlhttp;
            xmlhttp=new XMLHttpRequest();
            xmlhttp.onreadystatechange = function(){ 
                if(xmlhttp.readyState == 4)
                {
                    var message = document.createElement('div');
                    message.innerHTML = "<div class='messagetitle sentmessagetitle'>Sent: <span class='messagesubtitle'>("+humanTimeStamp()+")</span></div><div class='messagecontent'>"+xmlhttp.responseText+"</div>";
		    document.getElementById(room+'_messages').insertBefore(message,document.getElementById(room+'_messages').firstChild);
                } 
            };
            xmlhttp.open("POST","http://dev.arisgames.org/server/events/send.php",true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send('channel='+channel+'&event='+event+'&data='+data);
        }

        function message(room)
        {
            var channel = '<?php echo $default_channel; ?>';
            var event = '<?php echo $default_event; ?>';
            var data = '<?php echo $default_data; ?>';
            switch(room)
            {
                case 'public':
                    channel = '<?php echo $public_default_channel; ?>';
                    event = '<?php echo $public_default_event; ?>';
                    data = document.getElementById(room+'_senddata').value;
                    break;
                case 'private':
                    channel = '<?php echo $private_default_channel; ?>';
                    event = '<?php echo $private_default_event; ?>';
                    data = document.getElementById(room+'_senddata').value;
                    break;
                case 'presence':
                    channel = '<?php echo $presence_default_channel; ?>';
                    event = '<?php echo $presence_default_event; ?>';
                    data = document.getElementById(room+'_senddata').value;
                    break;
                case 'custom':
                    channel = document.getElementById(room+'_sendchannel').value;
                    event = document.getElementById(room+'_sendevent').value;
                    data = document.getElementById(room+'_senddata').value;
                    break;
                default:
                    alert('sent do invalid room...');
                    break;
            }
	    sendRequest(room, channel, event, data);
        }
    </script>
    <style>
        .channel
        {
            float:left;
            width:300px;
        }

        .channeltitle
        {
            font-weight:bold;
            color:orange;
        }
        
        .message_area
        {
            height:200px;
            overflow:scroll;
        }

        .message
        {
        }

        .messagetitle
        {
            font-weight:bold;
        }

        .sentmessagetitle
        {
            color:blue;
        }

        .receivedmessagetitle
        {
            color:green;
        }

        .messagesubtitle
        {
            font-size:small;
        }

        .messagecontent
        {
            font-size:small;
        }
    </style>
</head>
<body>
    <div class='channel'>
	<div class='channeltitle'>PUBLIC</div>
        <table>
            <tr><td>Data: </td> <td><input type="text" id="public_senddata"></input></td></tr>
        </table>
	<input type="button" value="Send" onClick="message('public');"></input>

	<div id="public_messages" class='message_area'>
	    <div>Waiting...</div>
	</div>
    </div>

    <div class='channel'>
	<div class='channeltitle'>PRIVATE</div>
        <table>
            <tr><td>Data: </td> <td><input type="text" id="private_senddata"></input></td></tr>
        </table>
	<input type="button" value="Send" onClick="message('private');"></input>

	<div id="private_messages" class='message_area'>
	    <div>Waiting...</div>
	</div>
    </div>

    <div class='channel'>
	<div class='channeltitle'>PRESENCE</div>
        *will fail to recieve*
        <table>
            <tr><td>Data: </td> <td><input type="text" id="presence_senddata"></input></td></tr>
        </table>
	<input type="button" value="Send" onClick="message('presence');"></input>

	<div id="presence_messages" class='message_area'>
	    <div>Waiting...</div>
	</div>
    </div>

    <div class='channel'>
	<div class='channeltitle'>CUSTOM</div>
        *can't receive*
        <table>
            <tr><td>Channel: </td> <td><input type="text" id="custom_sendchannel"></input></td></tr>
            <tr><td>Event: </td> <td><input type="text" id="custom_sendevent"></input></td></tr>
            <tr><td>Data: </td> <td><input type="text" id="custom_senddata"></input></td></tr>
        </table>
	<input type="button" value="Send" onClick="message('custom');"></input>

	<div id="custom_messages" class='message_area'>
	    <div>Waiting...</div>
	</div>
    </div>
</body>
</html>

