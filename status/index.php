<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir('../services/v2/');

require_once("dbconnection.php");
require_once("../../events/pusher_defaults.php");

?><!DOCTYPE html>
<html>
<head>
<title>ARIS Server Status</title>
<link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600|Roboto:300,400|Volkhov" rel="stylesheet">
<style type="text/css">
body {
  background-color: rgb(17,19,24);
  color: #eee;
  font-family: Roboto;
  letter-spacing: 1px;
  text-align: center;
}
.green-light {
  display: inline-block;
  height: 20px;
  width: 20px;
  border-radius: 10px;
  background-color: rgb(60,220,60);
  margin-right: 10px;
}
.red-light {
  display: inline-block;
  height: 20px;
  width: 20px;
  border-radius: 10px;
  background-color: rgb(247,0,0);
  margin-right: 10px;
}
h1 {
  margin-top: 50px;
}
</style>
<script type="text/javascript" src="https://js.pusher.com/1.11/pusher.min.js"></script>
<script type="text/javascript">

function sendRequest(fn, params, method)
{
  var xmlhttp;
  xmlhttp=new XMLHttpRequest();
  xmlhttp.open(method,"../json.php/v2."+fn,false);
  xmlhttp.setRequestHeader("Content-type", "application/json");
  xmlhttp.send(params); //Synchronous call

  return JSON.parse(xmlhttp.responseText);
}

document.addEventListener('DOMContentLoaded', function(){
  var res = sendRequest('users.getUser', JSON.stringify({user_id: 1}), 'POST');
  var msg = '';
  if (res != null && res.returnCode === 0) {
    document.getElementById('api-light').classList.add('green-light');
    document.getElementById('api-light').classList.remove('red-light');
    msg = 'Web API is online.';
  } else {
    msg = 'Web API call was unsuccessful.';
  }
  document.getElementById('api-text').innerHTML = msg;
});

var pm_config =
{
  pusher_key: '<?php echo Config::pusher_key; ?>',
  private_default_auth: '<?php echo $private_default_auth; ?>',
  send_url: '<?php echo $send_url; ?>',
  private_default_channel: '<?php echo $private_default_channel; ?>'
}

var PusherMan = function(key, auth_url, send_url, channel, eventArray, callbackArray)
{
  this.pusher = new Pusher(key, {'encrypted':true});

  Pusher.channel_auth_endpoint = auth_url;
  this.channel = this.pusher.subscribe(channel);
  for(var i = 0; i < eventArray.length; i++) {
    this.channel.bind(eventArray[i], callbackArray[i]);
  }
  this.sendData = function(event, data)
  {
    var xmlhttp;
    xmlhttp=new XMLHttpRequest();
    xmlhttp.open("POST",send_url,true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send('channel='+channel+'&event='+event+'&data='+data); //Async call, don't care about response.
  }
}

var timestamp = Date.now() + '-' + Math.floor(Math.random() * 1000);

function onLoopback(res) {
  if (res === timestamp) {
    document.getElementById('pusher-light').classList.add('green-light');
    document.getElementById('pusher-light').classList.remove('red-light');
    document.getElementById('pusher-text').innerHTML = 'Pusher loopback successful.';
    pm.pusher.disconnect();
  }
}

var pm = new PusherMan(
  pm_config.pusher_key,
  '../events/' + pm_config.private_default_auth,
  '../events/' + pm_config.send_url,
  pm_config.private_default_channel,
  ["LOOPBACK_TEST"],
  [onLoopback]
);

function trySend() {
  var connected = pm.channel.subscribed;
  if (connected) {
    pm.sendData("LOOPBACK_TEST", timestamp);
  } else {
    setTimeout(function(){
      trySend();
    }, 100);
  }
}
trySend();

</script>
</head>
<body>

<h1>ARIS Server Status</h1>

<p>
<?php

class status extends dbconnection {
  public function testStatus() {
    $res = dbconnection::queryArray("SHOW TABLES;");
    if ($res === false || count($res) === 0) {
      return "<span class=\"red-light\"></span> Database could not be reached.";
    } else {
      return "<span class=\"green-light\"></span> Database is online.";
    }
    var_dump($res);
  }
}

$con = new status();
echo $con->testStatus();

?>
</p>

<p id="api-results">
  <span id="api-light" class="red-light"></span>
  <span id="api-text">Waiting for API results...</span>
</p>

<p id="pusher-results">
  <span id="pusher-light" class="red-light"></span>
  <span id="pusher-text">Waiting for Pusher results...</span>
</p>

</body>
</html>
