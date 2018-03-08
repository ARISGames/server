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

    public static function createField($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;
        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $columns = array('game_id', 'field_type');
        $values = array($game_id, $pack->field_type);
        foreach (array('label', 'required') as $column) {
            if (isset($pack->$column)) {
                $columns[] = $column;
                $values[] = $pack->$column;
            }
        }
        $columns = implode(",", $columns);
        $quoted = array();
        foreach ($values as $value) {
            $quoted[] = '"'.addslashes($value).'"';
        }
        $quoted = implode(",", $quoted);

        $field_id = dbconnection::queryInsert("INSERT INTO fields ($columns) VALUES ($quoted)");

        games::bumpGameVersion($pack);
        return new return_package(0); // could return field but siftr editor will just call getFieldsForGame
    }

    public static function updateField($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;
        $field_id = intval($pack->field_id);

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $columns = array();
        $values = array();
        foreach (array('label', 'required') as $column) {
            if (isset($pack->$column)) {
                $columns[] = $column;
                $values[] = $pack->$column;
            }
        }
        $pairs = array();
        foreach ($columns as $key => $column) {
            $pairs[] = $column . ' = "' . addslashes($values[$key]) . '"';
        }
        $pairs = implode(",", $pairs);

        dbconnection::query("UPDATE fields SET $pairs WHERE game_id = $game_id AND field_id = $field_id");
        games::bumpGameVersion($pack);
        return new return_package(0); // could return field but siftr editor will just call getFieldsForGame
    }

    public static function deleteField($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;
        $field_id = intval($pack->field_id);

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $fields = dbconnection::queryArray("SELECT * FROM fields WHERE game_id = $game_id AND field_id = $field_id");
        if (!count($fields)) {
            return new return_package(1, NULL, "Field not found");
        }

        dbconnection::query("DELETE FROM field_data WHERE field_id = '{$field_id}'");
        // ^ really should have game_id but not in the table. so we do the select check instead
        dbconnection::query("DELETE FROM field_options WHERE game_id = $game_id AND field_id = '{$field_id}'");
        dbconnection::query("DELETE FROM fields WHERE game_id = $game_id AND field_id = '{$field_id}'");

        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function createFieldOption($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;
        $field_id = intval($pack->field_id);

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $fields = dbconnection::queryArray("SELECT * FROM fields WHERE game_id = $game_id AND field_id = $field_id");
        if (!count($fields)) {
            return new return_package(1, NULL, "Field not found");
        }

        $columns = array('field_id', 'game_id', '`option`');
        $values = array($field_id, $game_id, $pack->option);
        $columns = implode(",", $columns);
        $quoted = array();
        foreach ($values as $value) {
            $quoted[] = '"'.addslashes($value).'"';
        }
        $quoted = implode(",", $quoted);

        $field_id = dbconnection::queryInsert("INSERT INTO field_options ($columns) VALUES ($quoted)");

        games::bumpGameVersion($pack);
        return new return_package(0); // could return field but siftr editor will just call getFieldsForGame
    }

    public static function updateFieldOption($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;
        $field_id = intval($pack->field_id);
        $field_option_id = intval($pack->field_option_id);

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $columns = array();
        $values = array();
        foreach (array('option') as $column) {
            if (isset($pack->$column)) {
                $columns[] = $column;
                $values[] = $pack->$column;
            }
        }
        $pairs = array();
        foreach ($columns as $key => $column) {
            $pairs[] = '`' . $column . '` = "' . addslashes($values[$key]) . '"';
        }
        $pairs = implode(",", $pairs);

        dbconnection::query("UPDATE field_options SET $pairs WHERE game_id = $game_id AND field_option_id = $field_option_id");
        games::bumpGameVersion($pack);
        return new return_package(0); // could return field but siftr editor will just call getFieldsForGame
    }

    public static function deleteFieldOption($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;
        $field_id = intval($pack->field_id);
        $field_option_id = intval($pack->field_option_id);
        $new_field_option_id = intval($pack->new_field_option_id);

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $fields = dbconnection::queryArray("SELECT * FROM fields WHERE game_id = $game_id AND field_id = $field_id");
        if (!count($fields)) {
            return new return_package(1, NULL, "Field not found");
        }

        if ($new_field_option_id) {
            dbconnection::query("UPDATE field_data SET field_option_id = {$new_field_option_id} WHERE field_id = {$field_id} AND field_option_id = {$field_option_id}");
        } else {
            dbconnection::query("DELETE FROM field_data WHERE field_id = {$field_id} AND field_option_id = {$field_option_id}");
        }
        dbconnection::query("DELETE FROM field_options WHERE field_id = {$field_id} AND field_option_id = {$field_option_id}");

        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
