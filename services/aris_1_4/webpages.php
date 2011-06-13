<?php
    require_once("module.php");
    require_once("media.php");
    require_once("games.php");
    require_once("locations.php");
    require_once("playerStateChanges.php");
    require_once("editorFoldersAndContent.php");
    
    class WebPages extends Module
    {
        
        
        /**
         * Gets the webpages within a game
         * @param integer $gameID The game identifier
         * @return returnData
         * @returns a returnData object containing an array of webpages
         * @see returnData
         */
        public static function getWebPages($gameId)
        {
            
            $prefix = Module::getPrefix($gameId);
            if (!$prefix) return new returnData(1, NULL, "invalid game id");
            
            
            $query = "SELECT * FROM web_pages WHERE game_id = '{$gameId}'";
            NetDebug::trace($query);
            
            
            $rsResult = @mysql_query($query);
            
            if (mysql_error()) return new returnData(3, NULL, "SQL Error");
            return new returnData(0, $rsResult);
        }
        
        
        
        /**
         * Gets a single web page from a game
         *
         * @param integer $gameID The game identifier
         * @param integer $webPageId The webPage identifier
         * @return returnData
         * @returns a returnData object containing an webPages
         * @see returnData
         */
        public static function getWebPage($gameId, $webPageId)
        {
            
            $prefix = Module::getPrefix($gameId);
            if (!$prefix) return new returnData(1, NULL, "invalid game id");
            
            $query = "SELECT * FROM web_pages WHERE game_id = '{$gameId}' AND web_page_id = '{$webPageId}' LIMIT 1";
            
            $rsResult = @mysql_query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error");
            
            $webPage = @mysql_fetch_object($rsResult);
            if (!$webPage) return new returnData(2, NULL, "invalid web page id");
            
            return new returnData(0, $webPage);
            
        }
        
        /**
         * Creates a single Web Page from a game
         * 
         * @param integer $gameId The game identifier
         * @param string $name The name
         * @param string $url The website to reach
         * @param integer $iconMediaId The webpage's media identifier
         * @return returnData
         * @returns a returnData object containing the new webpage identifier
         * @see returnData
         */
        public static function createWebPage($gameId, $name, $url, $iconMediaId)
        {
            $name = addslashes($name);	
            
            $prefix = Module::getPrefix($gameId);
            if (!$prefix) return new returnData(1, NULL, "invalid game id");
            
            $query = "INSERT INTO web_pages 
            (game_id, name, url, icon_media_id)
            VALUES ('{$gameId}', '{$name}', 
            '{$url}',
            '{$iconMediaId}')";
            
            NetDebug::trace("createWebPage: Running a query = $query");	
            
            @mysql_query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		
            
            return new returnData(0, mysql_insert_id());
        }
        
        
        
        /**
         * Updates an WebPage's properties
         *
         * @param integer $gameId The game identifier
         * @param integer $webPageId The webpage identifier
         * @param string $name The new name
         * @param string $url The website to reach
         * @param integer $iconMediaId The new icon media identifier
         * @return returnData
         * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
         * @see returnData
         */
        public static function updateWebPage($gameId, $webPageId, $name, $url, $iconMediaId)
        {
            $prefix = Module::getPrefix($gameId);
            
            $name = addslashes($name);	
            
            if (!$prefix) return new returnData(1, NULL, "invalid game id");
            
            $query = "UPDATE web_pages 
            SET name = '{$name}', 
            url = '{$url}', 
            icon_media_id = '{$iconMediaId}' 
            WHERE web_page_id = '{$webPageId}'";
            
            NetDebug::trace("updateWebPage: Running a query = $query");	
            
            @mysql_query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);
            
            if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
            else return new returnData(0, FALSE, "Success Running:" . $query);
            
            
        }
        
        
        /**
         * Deletes an WebPage from a game, removing any refrence made to it in the rest of the game
         *
         * When this service runs, locations, requirements, playerStatechanges and player inventories
         * are updated to remove any refrence to the deleted webPage.
         *
         * @param integer $gameId The game identifier
         * @param integer $webPageId The webpage identifier
         * @return returnData
         * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
         * @see returnData
         */
        public static function deleteWebPage($gameId, $webPageId)
        {
            $prefix = Module::getPrefix($gameId);
            if (!$prefix) return new returnData(1, NULL, "invalid game id");
            
            Locations::deleteLocationsForObject($gameId, 'WebPage', $webPageId);
            Requirements::deleteRequirementsForRequirementObject($gameId, 'WebPage', $webPageId);
            PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'WebPage', $webPageId);
            
            $query = "DELETE FROM web_pages WHERE game_id = '{$gameId}' AND web_page_id = {$webPageId}";
            
            $rsResult = @mysql_query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error");
            
            if (mysql_affected_rows()) {
                return new returnData(0, TRUE);
            }
            else {
                return new returnData(0, FALSE);
            }
            
        }	
    }