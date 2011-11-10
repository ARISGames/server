<html>
<head>
<?php
    require_once('../../config.class.php');

    $conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
    mysql_select_db (Config::dbSchema);
    mysql_query("set names utf8");
    mysql_query("set charset set utf8");
?>
<script type="text/javascript">
function $(id)
{
	return document.getElementById(id);
}

function checkLogin()
{
	if($("user").value != "" && $("pass").value != "")
	{
  		var callData = JSON.stringify({"serviceName":"Players", "methodName":"Login","parameters":[$("user").value,$("pass").value]});
    		$.post("../../Php/?contentType=application/json", callData, onSuccess);
	}	
}

function onSuccess(data)
{
alert(data);
}
</script>
</head>
<body>
<form>
Username:<input type="text" id="user" />
Password:<input type="text" id="pass" />
<input type="button" value="Submit" onClick="checkLogin()" />
</form>
</body>
</html>
