<?php
require_once("dbconnection.php");
require_once("return_package.php");
require_once("media.php");
require_once("editors.php");

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
        foreach ($data as $row) {
            if ($row->media_id) {
                $row->media = media::getMedia($row)->data;
            }
        }

        return new return_package(0, $data);
    }

    public static function deleteField($pack)
    {
        $game_id = intval($pack->game_id);
        $field_id = intval($pack->field_id);

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $fields = dbconnection::queryArray("SELECT * FROM fields WHERE game_id = $game_id AND field_id = $field_id");
        if (count($fields)) {
            dbconnection::query("DELETE FROM field_data WHERE field_id = '{$field_id}'");
            // ^ really should have game_id but not in the table. so we do the select check instead
            dbconnection::query("DELETE FROM field_options WHERE game_id = $game_id AND field_id = '{$field_id}'");
            dbconnection::query("DELETE FROM fields WHERE game_id = $game_id AND field_id = '{$field_id}'");
        } else {
            return new return_package(1, NULL, "Field not found");
        }

        return new return_package(0);
    }
}
