<?php
    require_once('./server/config.class.php');
    // Function to display form
    function showForm($errorName=false,$errorOldPassword=false,$errorNewPassword=false, $errorVerifyPassword=false){
        if ($errorName)  $errorTextName  = " Username is not found.  Please enter a valid username.";
        if ($errorOldPassword) $errorTextOldPassword = " The old password is incorrect. Please try again.";
        if ($errorNewPassword)  $errorTextNewPassword = " Your new password needs to be at least 6 characters long.";
        if ($errorVerifyPassword)  $errorTextVerifyPassword = " The new passwords are not the same. Please verify your new password.";
        
        echo '<html><head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type"><link rel="stylesheet" type="text/css" href="resetpassword.css"><link rel="stylesheet" type="text/css" href="http://arisgames.org/wp-content/themes/Play/style.css" media="screen" /><title>Reset ARIS Password</title></head><body><div id="header" class="inners"><div class="content-head">	<div class="logo"><a href="http://arisgames.org"><img src="http://arisgames.org/wp-content/uploads/2010/08/ARISLogo1.png" border="0" class="png" alt="ARIS - Mobile Learning Experiences" /></a></div><div class="menu">	<ul id="nav-ie" class="topnav fl fr sf-js-enabled sf-shadow"><li ><a href="/"><span></span></a></li><li class="page_item page-item-2062"><a href="http://arisgames.org/press/" title="Press"><span>Press</span></a></li><li class="page_item page-item-2573 current_page_item"><a href="http://arisgames.org/design-jam-recap/" title="Design Jam Recap"><span>Design Jam Recap</span></a></li><li class="page_item page-item-1470"><a href="http://arisgames.org/demo/" title="Demo"><span>Demo</span></a></li><li class="page_item page-item-1520"><a href="http://arisgames.org/blog/" title="Community Blog"><span>Community Blog</span></a></li><li class="page_item page-item-1971"><a href="http://arisgames.org/projects-and-papers/" title="Projects"><span>Projects</span></a></li><li class="page_item page-item-5"><a href="http://arisgames.org/design-team/" title="Design Team"><span>Design Team</span></a></li></ul>    </div>		  <div class="clear"></div>             </div></div><h1>Change Your ARIS Password<br></h1><br>';
        

        echo "<form action='resetpassword_confirm.php' method='POST' name='ResetPassword'>";
        echo "<div class='tab'>";
        echo "<br>ARIS Account Type:<br>";
        echo "<table cellspacing='7'><td>";
        echo "<input type='radio' name='accounttype' value='player' ";
        if ($_POST['accounttype'] == 'player') echo "checked";
        echo "> Player</td>  ";
        echo "<td><input type='radio' name='accounttype' value='editor' ";
        if ($_POST['accounttype'] == 'editor') echo "checked";  
        echo "> Editor</td></table><br>";
        echo "Username: <br>";
        echo "<input name='username' value='". $_POST['username'] . "'>";
        if ($errorName) echo "<span class='red'>$errorTextName</span>";  // display error if needed
        //echo "<br><br>id:";
        //echo "<br><input name='editorid' value='". $_POST['editorid'] . "'>";
        echo "<br><br>Old Password:<br>";
        echo "<input type = 'password' name='oldpassword' value='". $_POST['oldpassword']  . "'>";
        if ($errorOldPassword) echo "<span class='red'>$errorTextOldPassword</span>"; // display error if needed
        echo "<br>";
        echo "<br>New Password:<br>";
        echo "<input name='newpassword' type='password' value='". $_POST['newpassword'] . "'>"; 
        if ($errorNewPassword) echo "<span class='red'>$errorTextNewPassword</span>";
        echo "<br><br>Confirm New Password:<br>";  // display error if needed
        echo "<input name='confirmpassword' type='password' value='". $_POST['confirmpassword'] . "'>";
        if ($errorVerifyPassword) echo "<span class='red'>$errorTextVerifyPassword</span>";  // display error if needed
        echo "<br><br><input name='Submit' value='Submit' type='submit'><br>";
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
        
        // Check if username is in the database
        if ($accounttype == 'editor')
            $query = "SELECT password FROM editors WHERE name= '$username'";// AND editor_id = '$editorid'";
        else 
            $query = "SELECT password FROM players WHERE user_name= '$username'";
        
		$rsResult = @mysql_query($query);
		if (mysql_num_rows($rsResult) < 1) $errorName = true;
		$editorRecord = mysql_fetch_array($rsResult);
        
        
        // Check if old password matches
        if (MD5($oldpassword) != $editorRecord['password']) $errorOldPassword = true;

        // Check if new password is long enough
        if (strlen($newpassword) < 6) $errorNewPassword = true;
        
        // Check if new password matches confirm password
        if ($newpassword != $confirmpassword) $errorVerifyPassword = true;
        
        // Display the form again as there was an error
        if ($errorName || $errorOldPassword || $errorNewPassword || $errorVerifyPassword) {
            showForm($errorName,$errorOldPassword,$errorNewPassword, $errorVerifyPassword);
        } else {
            
            //echo $accounttype;
        
            
            // update new password in database
            if ($accounttype == 'editor') {
                $query = "UPDATE editors 
                SET password = MD5('{$newpassword}')
                WHERE password = MD5('{$oldpassword}')
                AND name = '{$username}'";
            } else {
                $query = "UPDATE players 
                SET password = MD5('{$newpassword}') 
                WHERE password = MD5('{$oldpassword}') 
                AND user_name = '{$username}'";
            }
            
            //echo $query;
            
            @mysql_query($query);
            
             echo '<html><head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type"><link rel="stylesheet" type="text/css" href="resetpassword.css"><link rel="stylesheet" type="text/css" href="http://arisgames.org/wp-content/themes/Play/style.css" media="screen" /><title>Reset ARIS Password</title></head><body><div id="header" class="inners"><div class="content-head">	<div class="logo"><a href="http://arisgames.org"><img src="http://arisgames.org/wp-content/uploads/2010/08/ARISLogo1.png" border="0" class="png" alt="ARIS - Mobile Learning Experiences" /></a></div><div class="menu">	<ul id="nav-ie" class="topnav fl fr sf-js-enabled sf-shadow"><li ><a href="/"><span></span></a></li><li class="page_item page-item-2062"><a href="http://arisgames.org/press/" title="Press"><span>Press</span></a></li><li class="page_item page-item-2573 current_page_item"><a href="http://arisgames.org/design-jam-recap/" title="Design Jam Recap"><span>Design Jam Recap</span></a></li><li class="page_item page-item-1470"><a href="http://arisgames.org/demo/" title="Demo"><span>Demo</span></a></li><li class="page_item page-item-1520"><a href="http://arisgames.org/blog/" title="Community Blog"><span>Community Blog</span></a></li><li class="page_item page-item-1971"><a href="http://arisgames.org/projects-and-papers/" title="Projects"><span>Projects</span></a></li><li class="page_item page-item-5"><a href="http://arisgames.org/design-team/" title="Design Team"><span>Design Team</span></a></li></ul>    </div>		  <div class="clear"></div>             </div></div><h1>Change Your ARIS Password<br></h1><br>';
            
            if (mysql_affected_rows() < 1) 
                echo '<div class="tab">There was a problem changing your password.</div>';
            else 
                echo '<div class="tab">Your password has been successfully changed.</div>';
             
        }
        
    }
?> 