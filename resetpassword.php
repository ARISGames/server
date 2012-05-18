

<html><head>
  
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

  
  <link rel="stylesheet" type="text/css" href="resetpassword.css">
  <link rel="stylesheet" type="text/css" href="http://arisgames.org/wp-content/themes/Play/style.css" media="screen" />
  <title>Reset ARIS Password</title>

  
</head><body>
<div id="header" class="inners">
<div class="content-head">	

<div class="logo">
<a href="http://arisgames.org"><img src="http://arisgames.org/wp-content/uploads/2010/08/ARISLogo1.png" border="0" class="png" alt="ARIS - Mobile Learning Experiences" /></a>   
</div>

<div class="menu">	
<ul id="nav-ie" class="topnav fl fr sf-js-enabled sf-shadow"><li ><a href="/"><span></span></a></li><li class="page_item page-item-2062"><a href="http://arisgames.org/press/" title="Press"><span>Press</span></a></li>
<li class="page_item page-item-2573 current_page_item"><a href="http://arisgames.org/design-jam-recap/" title="Design Jam Recap"><span>Design Jam Recap</span></a></li>
<li class="page_item page-item-1470"><a href="http://arisgames.org/demo/" title="Demo"><span>Demo</span></a></li>
<li class="page_item page-item-1520"><a href="http://arisgames.org/blog/" title="Community Blog"><span>Community Blog</span></a></li>
<li class="page_item page-item-1971"><a href="http://arisgames.org/projects-and-papers/" title="Projects"><span>Projects</span></a></li>
<li class="page_item page-item-5"><a href="http://arisgames.org/design-team/" title="Design Team"><span>Design Team</span></a></li>
</ul>    </div>		  
<div class="clear"></div>             

</div>
</div>
<h1>
Change Your ARIS Password<br>
</h1>

<br>

<form method="POST" action="resetpassword_confirm.php" name="ResetPassword"><br>
<div class="tab">
ARIS Account Type: <br>

<?php

    /*function myGET() {
        $aGet = array();
        
        if(isset($_GET['query'])) {
            $aGet = explode('/', $_GET['query']);
        }
        
        return $aGet;
    }

    $result = myGET();
    var_dump($result);
    */
    
    echo "<table cellspacing='7'><td>";
    echo "<input type='radio' name='accounttype' value='player' ";
    if ($_GET['t'] == 'p') echo "checked"; 
    echo "> Player</td>  ";
    echo "<td><input type='radio' name='accounttype' value='editor' ";
    if ($_GET['t'] == 'e') echo "checked"; 
    echo "> Editor</td></table><br>";
    echo "Username:  ";
    echo "<br><input name='username' value='". $_GET['u'] . "'><br><br>";
    //echo "id:<br>";
    //echo "<input name='editorid' value='". $_GET['i'] . "'><br>";
    echo "Old Password:<br>";
    echo "<input type = 'password' name='oldpassword' value='". $_GET['p']  . "'><br>";
    
    
    
?>
  

  <br>
New Password:<br>
  <input name="newpassword" type="password"><br>
  <br>
Confirm New Password:<br>
  <input name="confirmpassword" type="password"><br>
  <br>
  <input name="Submit" value="Submit" type="submit"><br>
</div>
</form>

</body></html>