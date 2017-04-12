<?php
require_once("dbconnection.php");
require_once("return_package.php");

class fields extends dbconnection
{
    public static function getFieldsForGame($pack)
    {
        $game_id = intval($pack->game_id);
        if ($game_id <= 0) return new return_package(6, NULL, "Invalid game ID");

        $fields = dbconnection::queryArray("SELECT * FROM fields WHERE game_id = $game_id");
        $options = dbconnection::queryArray("SELECT * FROM field_options WHERE game_id = $game_id");

        return new return_package(0, array(
            'fields' => $fields,
            'options' => $options,
        ));
    }

    public static function getFieldDataForNote($pack)
    {
        $note_id = intval($pack->note_id);
        if ($note_id <= 0) return new return_package(6, NULL, "Invalid note ID");

        $data = dbconnection::queryArray("SELECT * FROM field_data WHERE note_id = $note_id");

        return new return_package(0, $data);
    }
}
