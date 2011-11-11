<html>
<head>
<script type="text/javascript">
function $(id)
{
	return document.getElementById(id);
}

function checkLogin()
{
	alert("hello?");
	var xmlhttp;
	xmlhttp=new XMLHttpRequest();
	xmlhttp.open("POST","loginwebbackpackverify.php", false);
	xmlhttp.send("username="+$("user").value+"&password="+$("pass").value);
	var response = xmlhttp.responseText;
	if(response == 'false')
	{
		alert("Incorrect Username and/or Password. Please try again.");
	}
	else
	{
		//window.location.href='webbackpack.php?playerId='+response+'&gameId=179';
	}
}
</script>
</head>
<body>
<form>
Username:<input type="text" id="user" />
Password:<input type="text" id="pass" />
<input type="button" value="Submit" onClick="checkLogin()" />
</form>
<div id="test">
hello
</div>
</body>
</html>
