<?php
    require_once('./config.class.php');
    // Function to display form
    function showForm($errorName=false,$errorOldPassword=false,$errorNewPassword=false, $errorVerifyPassword=false){
        if ($errorName)  $errorTextName  = " Username is not found.  Please enter a valid username.";
        if ($errorOldPassword) $errorTextOldPassword = " This form has expired. If you still want to change your password, click the 'Forgot Password' link again in ARIS to be sent a new email for changing your password.";
        if ($errorNewPassword)  $errorTextNewPassword = " Please enter a new password.";
        if ($errorVerifyPassword)  $errorTextVerifyPassword = " The new passwords are not the same. Please verify your new password.";
        
        echo '<html><head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type"><link rel="stylesheet" type="text/css" href="resetpassword.css"><link rel="stylesheet" type="text/css" href="../style.css" media="screen" /><title>Reset ARIS Password</title></head><body><div id="header" class="inners"><div class="logo">';
        echo "<a href='http://arisgames.org'><img src='http://arisgames.org/wp-content/uploads/2010/08/ARISLogo1.png' border='0' class='png' alt='ARIS - Mobile Learning Experiences' /></a>";
        echo '</div><br><span id="logotext"><br>Change Your Password</span><ul id="nav-ie" class="topnav fl fr sf-js-enabled sf-shadow"><li ><a href="/"><span></span></a></li></ul>    </div>		         </div>';
        
        
        
        echo "<form action='resetpassword.php' method='POST' name='ResetPassword'>";
        echo "<div class='tab'>";
        
        if (!empty($_GET)) {
            // setup database connection
            $conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
            mysql_select_db (Config::dbSchema);
            mysql_query("set names utf8");
            mysql_query("set charset set utf8");
            
            // Check if user is in the database
            if ($_GET['t'] == 'e')
                $query = "SELECT name AS user_name FROM editors WHERE editor_id = ".$_GET['i'];
            else if ($_GET['t'] == 'p')
                $query = "SELECT user_name FROM players WHERE player_id = ".$_GET['i'];
            
            $rsResult = @mysql_query($query);
            $editorRecord = mysql_fetch_array($rsResult);
            
            echo "<input type = 'hidden' name='accounttype' value='".$_GET['t']."'>";
            echo "<input type = 'hidden' name='oldpassword' value='".$_GET['p']."'>";
            echo "<input type = 'hidden' name='editorid' value='".$_GET['i']."'>";
            echo "<input type = 'hidden' name='username' value='".$editorRecord['user_name']."'>";
            echo "<table cellspacing='20'><tr><td>Username: </td><td>".$editorRecord['user_name']."</td>";
        } else {
            
            echo "<input type = 'hidden' name='accounttype' value='". $_POST['accounttype']  . "'>";
            echo "<input type = 'hidden' name='oldpassword' value='". $_POST['oldpassword']  . "'>";
            echo "<input type = 'hidden' name='editorid' value='". $_POST['editorid']  . "'><br>";
            echo "<input type = 'hidden' name='username' value='". $_POST['username']  . "'>";
            echo "<table cellspacing='20'><tr><td>Username: </td><td>".$_POST['username']."</td>";
        }
        
        echo "<tr><td>New Password:<br></td> <td><input name='newpassword' type='password' value='";
        echo isset($_POST['newpassword']) ? $_POST['newpassword'] : "";
        echo "'>";
        if ($errorNewPassword) echo "<span class='red'>$errorTextNewPassword</span>";
        echo "</td></tr><tr><td>Confirm New Password:<br></td> <td><input name='confirmpassword' type='password' value='";
        echo isset($_POST['confirmpassword']) ? $_POST['confirmpassword'] : "";
        echo "'>";
        if ($errorVerifyPassword) echo "<span class='red'>$errorTextVerifyPassword</span>";  // display error if needed
        echo "</td></tr><tr><td></td><td><input name='Submit' value='Submit' type='submit'><br></td></tr>";
        echo "</table>";
        if ($errorName) echo "<br><span class='red'>$errorTextName</span>";
        if ($errorOldPassword) echo "<br><span class='red'>$errorTextOldPassword</span>";
        echo "</form>";
    }
    
    
    if (!isset($_POST['Submit'])) {
        showForm();
    } else {
        //Init error variables
        $errorName  = false;
        $errorOldPassword = false;
        $errorNewPassword = false;
        $errorVerifyPassword  = false;
        
        $username  = isset($_POST['username'])  ? trim($_POST['username'])  : '';
        $oldpassword = isset($_POST['oldpassword']) ? trim($_POST['oldpassword']) : '';
        $newpassword  = isset($_POST['newpassword'])  ? trim($_POST['newpassword'])  : '';
        $confirmpassword  = isset($_POST['confirmpassword'])  ? trim($_POST['confirmpassword'])  : '';
        $editorid  = isset($_POST['editorid'])  ? trim($_POST['editorid'])  : '';
        $accounttype  = isset($_POST['accounttype'])  ? trim($_POST['accounttype'])  : '';
        
        
        // setup database connection
        $conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
        mysql_select_db (Config::dbSchema);
		mysql_query("set names utf8");
		mysql_query("set charset set utf8");
        
        // Check if user is in the database
        if ($accounttype == 'e')
            $query = "SELECT password FROM editors WHERE editor_id = $editorid";
        else 
            $query = "SELECT password FROM players WHERE player_id = $editorid";
        
        
		$rsResult = @mysql_query($query);
		if (mysql_num_rows($rsResult) < 1) $errorName = true;
		$editorRecord = mysql_fetch_array($rsResult);
        
        
        // Check if double-MD5 old password matches
        if ($oldpassword != MD5($editorRecord['password'])) $errorOldPassword = true;
        
        // Check if new password is long enough
        if (strlen($newpassword) < 1) $errorNewPassword = true;
        
        // Check if new password matches confirm password
        if ($newpassword != $confirmpassword) $errorVerifyPassword = true;
        
        // Display the form again as there was an error
        if ($errorName || $errorOldPassword || $errorNewPassword || $errorVerifyPassword) {
            showForm($errorName,$errorOldPassword,$errorNewPassword, $errorVerifyPassword);
        } else {
            
            // update new password in database
            if ($accounttype == 'e') {
                $query = "UPDATE editors 
                SET password = MD5('{$newpassword}')
                WHERE editor_id = $editorid";
            } else {
                $query = "UPDATE players 
                SET password = MD5('{$newpassword}') 
                WHERE player_id = $editorid";
            }
            
            @mysql_query($query);
            
            echo '<html><head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type"><link rel="stylesheet" type="text/css" href="resetpassword.css"><link rel="stylesheet" type="text/css" href="../style.css" media="screen" /><title>Reset ARIS Password</title></head><body><div id="header" class="inners"><div class="logo">';
            echo "<a href='http://arisgames.org'><img src='http://arisgames.org/wp-content/uploads/2010/08/ARISLogo1.png' border='0' class='png' alt='ARIS - Mobile Learning Experiences' /></a>";
            echo '</div><br><span id="logotext"><br>Change Your Password</span><ul id="nav-ie" class="topnav fl fr sf-js-enabled sf-shadow"><li ><a href="/"><span></span></a></li></ul>    </div>		         </div>';
            
            
            if (mysql_affected_rows() < 1) 
                echo '<div class="tab">A problem has occured, and your password has not been changed.<br>(This could occur if your new password is the same as your old password.) </div>';
            else 
                echo '<div class="tab">Your password has been successfully changed.</div>';
            
        }
        
    }
    ?> 
