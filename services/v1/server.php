<?php
require_once("module.php");

class Server extends Module
{
    public static function migrateDB()
    {
        $migrations = array();
        if($migrationsDir = opendir(Config::migrationsDir))
        {
            while($migration = readdir($migrationsDir))
            {
                if(preg_match('/^[0..9]+\.sql$/',$name))
                {
                    $migrations[intval(substr($migration, 0, -4))] = $migration;
                }
            }
        }
        
        $migrated = array();
        if(Module::queryObject("SHOW TABLES LIKE 'aris_migrations'"))
        {
            $knownVersions = Mysql::queryArray("SELECT * FROM aris_migrations");
            foreach($knownVersions as $version)
            {
                if(!$migrated[$version->version_major]) $migrated[$version->version_major] = array();
                    $migrated[$version->version_major][$version->version_minor] = $version->timestamp;
            }
        }

        foreach($migrations as $major => $file)
        {
            if($migrated[$major+1]) continue;

            $file_handle = fopen(Config::migrationsDir."/".$file, "r");
            $minor = 0;
            while(!feof($file_handle)) 
            {
                $query = fgets($file_handle);
                if(!$migration[$major][$minor])
                {
                    //mysql_query($query);
                    echo $query;
                    if(mysql_error())
                    {
                        $error = "Error upgrading database to version ".$major.".".$minor.". Error was:\n".mysql_error()."\n in query:\n".$query;
                        Module::serverErrorLog($error);
                        echo $error;
                        return $error;
                    }
                    Module::query("INSERT INTO aris_migrations (version_major, version_minor) VALUES ('".$major."','".$minor."')");
                }
                $minor++;
            }
            fclose($file_handle);
        }
    }
}
