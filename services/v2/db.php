<?php
require_once("dbconnection.php");
require_once("users.php");

class db extends dbconnection
{
    public static function upgrade($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return db::upgradePack($glob); }
    public static function upgradePack($pack)
    {
        //find existing upgrades
        $upgrade_files = scandir("db/upgrades", 0);
        $existing_upgrades = array();
        for($i = 0; $i < count($upgrade_files); $i++)
        {
            $file = $upgrade_files[$i];

            if(!preg_match("@\d\d*\.\d\d*\.sql@is",$file)) continue; //file of form #.#.sql
            $maj = intval(substr($file,0,strpos($file,".")));
            $min = intval(substr($file,strpos($file,".")+1,-4));

            if(!isset($existing_upgrades[$maj]))
                $existing_upgrades[$maj] = array();
            $existing_upgrades[$maj][$min] = true;
        }

        //find completed upgrades
        $upgrade_records = dbconnection::queryArray("SELECT * FROM db_upgrades ORDER BY version_major ASC, version_minor ASC");
        $completed_upgrades = array();
        for($i = 0; $i < count($upgrade_records); $i++)
        {
            $record = $upgrade_records[$i];

            $maj = $record->version_major;
            $min = $record->version_minor;

            if(!isset($completed_upgrades[$maj]))
                $completed_upgrades[$maj] = array();
            $completed_upgrades[$maj][$min] = true;
        }

        //find and perform incomplete upgrades
        for($i = 0; $i < count($existing_upgrades); $i++)
        {
            for($j = 0; $j < count($existing_upgrades[$i]); $j++)
            {
                if(!isset($completed_upgrades[$i][$j]))
                    db::applyUpgrade(0,$i,$j);
            }
        }

        return new return_package(0);
    }

    private static function applyUpgrade($user_id, $maj, $min)
    {
        $file = "db/upgrades/".$maj.".".$min.".sql";

        $upgrade = fopen($file, "r");
        while(!feof($upgrade))
        {
            $query = fgets($upgrade);
            if(preg_match("@^\s*$@is",$query)) continue; //ignore whitespace
            dbconnection::query($query);
        }
        fclose($upgrade);

        dbconnection::queryInsert("INSERT INTO db_upgrades (user_id, version_major, version_minor, timestamp) VALUES ('{$user_id}', '{$maj}', '{$min}', CURRENT_TIMESTAMP)");
    }
}
?>
