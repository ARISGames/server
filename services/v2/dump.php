<?php
require_once('../../config.class.php');

$conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
if (!$conn) Module::serverErrorLog("Problem Connecting to MySQL: " . mysql_error());
mysql_select_db (Config::dbSchema);
mysql_query("set names utf8");
mysql_query("set charset set utf8");

if(!isset($_GET['gameId']) || !isset($_POST['game'])) 
{
    echo "<html><head></head><body>Invalid game ID / game data.</body></html>";
    return;
}

# recursively remove a directory
function rrmdir($dir) {
    foreach(glob($dir . '/*') as $file) 
    {
        if(is_dir($file))
            rrmdir($file);
        else
            unlink($file);
    }
    rmdir($dir);
}
function writefile($filename, $directory, $contents)
{
    $myFile = $directory."/".$filename;
    $fh = fopen($myFile, 'w') or die("can't open file");
    fwrite($fh, $contents);
    fclose($fh);
}
function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }
    $source = str_replace('\\', '/', realpath($source));
    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);
            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;
            $file = realpath($file);
            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
    return $zip->close();
}

$dumpdir = "../../gamedata/".$_GET['gameId']."/dumpdir";
if(is_dir($dumpdir)) rrmdir($dumpdir);
mkdir($dumpdir);
writefile("jsondata.txt",$dumpdir,$_POST['game']);
$_POST['game'] = json_decode($_POST['game']);
$playersdir = $dumpdir."/players";
mkdir($playersdir);
for($i = 0; $i < count($_POST['game']->backpacks); $i++)
{
    $playerdir = $playersdir."/".$_POST['game']->backpacks[$i]->owner->user_name;
    mkdir($playerdir);
    for($j = 0; $j < count($_POST['game']->backpacks[$i]->notes); $j++)
    {
        $notedir = $playerdir."/".$_POST['game']->backpacks[$i]->notes[$j]->title;
        $tags = "";
        for($k = 0; $k < count($_POST['game']->backpacks[$i]->notes[$j]->tags); $k++)
            $tags .= $_POST['game']->backpacks[$i]->notes[$j]->tags[$k]->tag.", ";
        if($tags != "")
            $tags = "(".substr($tags, strlen($tags)-2).")";
        $notedir .=" ".$tags." ".$_POST['game']->backpacks[$i]->notes[$j]->note_id;
        mkdir($notedir);
        for($k = 0; $k < count($_POST['game']->backpacks[$i]->notes[$j]->contents); $k++)
        {
            switch($_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->type)
            {
                case "TEXT":
                    writefile($_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->title.".txt", $notedir, $_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->text);
                    break;
                case "PHOTO":
                case "AUDIO":
                case "VIDEO":
                    copy(
                        $_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->media_url, 
                        $notedir.
                        "/".
                        $_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->title.
                        " id_".
                        $_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->media_id.
                        substr($_POST['game']->backpacks[$i]->notes[$j]->contents[$k]->file_name, -4)
                    );
                    break;
            }
        }
        $commentsdir = $notedir."/comments";
        mkdir($commentsdir);
        for($k = 0; $k < count($_POST['game']->backpacks[$i]->notes[$j]->comments); $k++)
        {
            $commentdir = $commentsdir."/".$_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->username." ".$_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->note_id;
            mkdir($commentdir);
            writefile("text.txt",$commentdir,$_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->title);
            for($l = 0; $l < count($_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents); $l++)
            {
                switch($_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->type)
                {
                    case "TEXT":
                        writefile($_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->title.".txt", $commentdir, $_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->text);
                        break;
                    case "PHOTO":
                    case "AUDIO":
                    case "VIDEO":
                        copy(
                            $_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->media_url, 
                            $commentdir.
                            "/".
                            $_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->title.
                            " id_".
                            $_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->media_id. 
                            substr($_POST['game']->backpacks[$i]->notes[$j]->comments[$k]->contents[$l]->file_name, -4)
                        );
                        break;
                }
            }
        }
    }
}

$zippath = "../../gamedata/".$_GET['gameId']."/dumpzip.zip";
Zip($dumpdir, $zippath);
header('Location: '.$zippath);
?>
