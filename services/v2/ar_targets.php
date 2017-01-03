<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("triggers.php");

class ar_targets extends dbconnection
{
    //Takes in ar_target JSON, all fields optional except game_id, user_id, key
    public static function createARTarget($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->ar_target_id = dbconnection::queryInsert(
            "INSERT INTO ar_targets (".
            "game_id,".
            (isset($pack->name)          ? "name,"          : "").
            (isset($pack->vuforia_index) ? "vuforia_index," : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            (isset($pack->name)          ? "'".addslashes($pack->name)."',"          : "").
            (isset($pack->vuforia_index) ? "'".addslashes($pack->vuforia_index)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return ar_targets::getARTarget($pack);
    }

    //Takes in ar_target JSON, all fields optional except ar_target_id, user_id, key
    public static function updateARTarget($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE ar_targets SET ".
            (isset($pack->name)          ? "name          = '".addslashes($pack->name)."', "          : "").
            (isset($pack->vuforia_index) ? "vuforia_index = '".addslashes($pack->vuforia_index)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE ar_target_id = '{$pack->ar_target_id}'"
        );

        games::bumpGameVersion($pack);
        return ar_targets::getARTarget($pack);
    }

    private static function arTargetObjectFromSQL($sql_ar_target)
    {
        if(!$sql_ar_target) return $sql_ar_target;
        $ar_target = new stdClass();
        $ar_target->ar_target_id  = $sql_ar_target->ar_target_id;
        $ar_target->game_id       = $sql_ar_target->game_id;
        $ar_target->name          = $sql_ar_target->name;
        $ar_target->vuforia_index = $sql_ar_target->vuforia_index;

        return $ar_target;
    }

    public static function getARTarget($pack)
    {
        $sql_ar_target = dbconnection::queryObject("SELECT * FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}' LIMIT 1");
        return new return_package(0,ar_targets::arTargetObjectFromSQL($sql_ar_target));
    }

    public static function getARTargetsForGame($pack)
    {
        $sql_ar_targets = dbconnection::queryArray("SELECT * FROM ar_targets WHERE game_id = '{$pack->game_id}'");
        $ar_targets = array();
        for($i = 0; $i < count($sql_ar_targets); $i++)
            if($ob = ar_targets::arTargetObjectFromSQL($sql_ar_targets[$i])) $ar_targets[] = $ob;

        return new return_package(0,$ar_targets);
    }

    public static function deleteARTarget($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}' LIMIT 1");

        $triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE type = 'AR' AND ar_target_id  = '{$pack->ar_target_id}'");
        for($i = 0; $i < count($triggers); $i++)
        {
            $pack->trigger_id = $triggers[$i]->trigger_id;
            triggers::deleteTrigger($pack);
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    //Takes in JSON, requires auth, game_id, file_name, data
    public static function uploadARTargetDB($pack)
    {
      $pack->auth->permission = "read_write";
      if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

      //validate name/type
      $filenameext = strtolower(substr($pack->file_name,strrpos($pack->file_name,'.')+1));
      if($filenameext != "zip") return new return_package(1,NULL,"Invalid filetype: '{$filenameext}'");
      $filenamebase = basename($pack->file_name,".zip");
      $fsfolder = Config::v2_gamedata_folder."/".$pack->game_id."/ar";
      mkdir($fsfolder."/thumbs"); //will create $fsfolder and $fsfolder/thumbs
      $fspath = $fsfolder."/vuforiadb.zip";

      //write file
      $fp = fopen($fspath, 'w');
      if(!$fp) return new return_package(1,NULL,"Couldn't open file:$fspath");
      fwrite($fp,base64_decode($pack->data));
      fclose($fp);

      //open zip
      $zip = new ZipArchive;
      $res = $zip->open($fspath);
      if(!$res) return new return_package(1,NULL,"Couldn't open zip");

      //verify contents
      if($zip->numFiles != 2) return new return_package(1,NULL,"Invalid DB zip");
      $xmlpresent = false;
      $datpresent = false;
      for($i = 0; $i < $zip->numFiles; $i++)
      {
        $name = $zip->statIndex($i)['name'];
             if($name == "vuforiadb/".$filenamebase.".xml") $xmlpresent = true;
        else if($name == "vuforiadb/".$filenamebase.".dat") $datpresent = true;
        else return new return_package(1,NULL,"Invalid contents of DB zip");
      }
      if(!($xmlpresent && $datpresent)) return new return_package(1,NULL,"Invalid contents of DB zip");

      //extract
      $zip->extractTo($fsfolder);
      $zip->close();

      //situate contents
      rename($fsfolder."/vuforiadb/".$filenamebase.".xml", $fsfolder."/vuforiadb.xml");
      rename($fsfolder."/vuforiadb/".$filenamebase.".dat", $fsfolder."/vuforiadb.dat");
      //cleanup
      unlink($fspath); //zip
      rmdir($fsfolder."/vuforiadb");

      //parse xml
      $names = array();
      $namesadded = array();
      $fp = fopen($fsfolder."/vuforiadb.xml", 'r');
      if(!$fp) return new return_package(1,NULL,"Can't open DB XML");
      $preamble = "<ImageTarget name=\"";
      $preamblelen = strlen($preamble);
      while($line = fgets($fp))
      {
        $pos = strpos($line,$preamble);
        $start = $pos+$preamblelen
        if($pos)
        {
          $end = strpos($line,"\"",$start+1);
          $names[] = substr($line,$start,$end-$start);
          $namesadded = false;
        }
      }
      fwrite($fp,base64_decode($pack->data));
      fclose($fp);

      //merge xml into dataset
      $cur_targets = ar_targets::getARTargetsForGame($pack);
      $tmppack = new stdClass();
      $tmppack->auth = $pack->auth;
      $tmppack->game_id = $pack->game_id;
      //update/delete
      for($i = 0; $i < count($cur_targets); $i++)
      {
        $found = false;
        for($j = 0; !$found && $j < count($names); $j++)
        {
          if($cur_targets[$i]->name == $names[$j])
          {
            $tmppack->name = $cur_targets[$i]->name;
            $tmppack->vuforia_index = $j;
            ar_targets::updateARTarget($tmppack);
            $namesadded[$j] = true;
            $found = true;
          }
        }
        if(!$found)
        {
          $tmppack->ar_target_id = $cur_target[$i]->ar_target_id;
          ar_targets::deleteARTarget($tmppack);
        }
      }
      //add
      for($j = 0; $j < count($names); $j++)
      {
        if(!$namesadded[$j]) //already added
        {
          $tmppack->name = $names[$j];
          $tmppack->vuforia_index = $j;
          ar_targets::createARTarget($tmppack);
          $namesadded[$j] = true;
        }
      }

      //parse contents of meta zip
      $fspath = $fsfolder."/vuforiametadb.zip";
      copy($fsfolder."/vuforiadb.dat",$fspath);
      //open metazip
      $zip = new ZipArchive;
      $res = $zip->open($fspath);
      if(!$res) return new return_package(1,NULL,"Couldn't open meta zip");

      //extract
      $zip->extractTo($fsfolder."/thumbs");
      $zip->close();

      for($j = 0; $j < count($names); $j++)
        rename($fsfolder."thumbs/vuforiametadb/".$names[$j].".tex.jpg", $fsfolder."/thumbs/".$names[$j].".jpg");

      //cleanup
      unlink($fspath); //zip

      games::bumpGameVersion($pack);
      return ar_targets::getARTargetsForGame($pack);
    }
}
?>
