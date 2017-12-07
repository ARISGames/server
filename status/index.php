<!DOCTYPE html>
<html>
<head>
  <title>ARIS Status</title>
</head>
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
    msg = 'Web API is online. User #1 is ';
    if (res.data != null && res.data.user_name != null) {
      msg += '"' + res.data.user_name + '"';
    } else {
      msg += JSON.stringify(res.data);
    }
  }
  document.getElementById('api-results').innerHTML = msg;
});

</script>
<body>

<h1>ARIS Status</h1>

<p>
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir('../services/v2/');

require_once("dbconnection.php");

class status extends dbconnection {
  public function testStatus() {
    $res = dbconnection::queryArray("SHOW TABLES;");
    if ($res === false) {
      return "Database could not be reached.";
    } else {
      return "Database is online, with " . count($res) . " tables.";
    }
    var_dump($res);
  }
}

$con = new status();
echo $con->testStatus();

?>
</p>

<p id="api-results"></p>

<!--

TODO Pusher loopback test

-->

</body>
</html>
