

<html><head>
  
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

  
  <link rel="stylesheet" type="text/css" href="resetpassword.css">
  <link rel="stylesheet" type="text/css" href="style.css" media="screen" />
  <title>Reset ARIS Password</title>
  
</head><body>
<div id="header" class="inners">

<div class="logo">
<?php
    require_once('./server/config.class.php');
echo "<a href='".Config::serverWWWPath."'><img src='".Config::serverWWWPath."/wp-content/uploads/2010/08/ARISLogo1.png' border='0' class='png' alt='ARIS - Mobile Learning Experiences' /></a>";
?>
</div>
<br><span id="logotext"><br>Change Your Password</span>
<ul id="nav-ie" class="topnav fl fr sf-js-enabled sf-shadow"><li ><a href="/"><span></span></a></li></ul>    </div>		           

</div>

<form method="POST" action="resetpassword_confirm.php" name="ResetPassword"><br>
<div class="tab">
<?php
    require_once('./server/config.class.php'); 
    // setup database connection
    $conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
    mysql_select_db (Config::dbSchema);
    mysql_query("set names utf8");
    mysql_query("set charset set utf8");
    
    // Check if user is in the database
    if ($_GET['t'] == 'e')
      $query = "SELECT name AS user_name FROM editors WHERE editor_id = ".$_GET['i'];
    else 
      $query = "SELECT user_name FROM players WHERE player_id = ".$_GET['i'];
    
    $rsResult = @mysql_query($query);
    $editorRecord = mysql_fetch_array($rsResult);
    
    echo "<input type = 'hidden' name='accounttype' value='".$_GET['t']."'>";
    echo "<input type = 'hidden' name='oldpassword' value='".$_GET['p']."'>";
    echo "<input type = 'hidden' name='editorid' value='".$_GET['i']."'>";
    echo "<input type = 'hidden' name='username' value='".$editorRecord['user_name']."'>";
    echo "<table cellspacing='20'><tr><td>Username: </td><td>".$editorRecord['user_name']."</td>";

?>
<tr><td>New Password:<br></td> <td><input name="newpassword" type="password"></td></tr>
<tr><td>Confirm New Password:<br></td> <td><input name="confirmpassword" type="password"></td></tr>
<tr><td></td><td><input name="Submit" value="Submit" type="submit"><br></td></tr>
</table>
</div>
</form>

</body></html>