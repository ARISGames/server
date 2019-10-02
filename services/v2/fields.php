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
        $guides = dbconnection::queryArray("SELECT * FROM field_guides WHERE game_id = $game_id");

        // Hide legacy media/pin/caption fields on old siftr clients
        $api = (isset($pack->api) ? intval($pack->api) : 0);
        if ($api < 2) {
            $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = $game_id");
            if ($game) {
                $hideFields = array(
                    intval($game->field_id_preview),
                    intval($game->field_id_pin),
                    intval($game->field_id_caption)
                );

                $fieldsFiltered = array();
                foreach ($fields as $field) {
                    if (!in_array(intval($field->field_id), $hideFields)) {
                        $fieldsFiltered[] = $field;
                    }
                }
                $fields = $fieldsFiltered;
            }
        }

        return new return_package(0, array(
            'fields' => $fields,
            'options' => $options,
            'guides' => $guides,
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
        foreach (array('label', 'required', 'sort_index', 'min', 'max', 'min_color', 'max_color', 'step', 'field_guide_id') as $column) {
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

        if ($pack->field_type === 'SINGLESELECT' || $pack->field_type === 'MULTISELECT') {
            $pack->field_id = $field_id;
            $pack->sort_index = 0;
            $pack->option = '';
            fields::createFieldOption($pack);
        }

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
        foreach (array('label', 'required', 'sort_index', 'min', 'max', 'min_color', 'max_color', 'step') as $column) {
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

        $columns = array('field_id', 'game_id', '`option`', 'sort_index', 'color', 'remnant_id', 'field_guide_id');
        $sort_index = intval($pack->sort_index) || 0;
        $values = array($field_id, $game_id, $pack->option, $sort_index, $pack->color);
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
        foreach (array('option', 'sort_index', 'color', 'remnant_id', 'field_guide_id') as $column) {
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

    public static function convertSiftrToFields($pack)
    {
        $game_id = intval($pack->game_id);
        $pack->game_id = $game_id;

        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) {
            return new return_package(6, NULL, "Failed Authentication");
        }
        if(!editors::authenticateGameEditor($pack->auth) && intval($pack->auth->user_id) !== 1 && intval($pack->auth->user_id) !== 788) {
            return new return_package(6, NULL, "Failed Authentication");
        }

        $game = games::getGame($pack)->data;
        if (!($game->is_siftr)) {
            return new return_package(6, NULL, "This game is not a Siftr.");
        }
        if ($game->field_id_preview || $game->field_id_pin || $game->field_id_caption) {
            return new return_package(6, NULL, "This Siftr is already in the new format.");
        }

        // create media field
        $media_field_id = dbconnection::queryInsert("INSERT INTO
            fields (game_id , field_type, label  , required, sort_index)
            VALUES ($game_id, 'MEDIA'   , 'Photo', 1       , -3        )");
        // for each note, create field_data with its media_id
        dbconnection::query("INSERT INTO
            field_data (note_id, field_id       , field_data, field_option_id, media_id)
            SELECT      note_id, $media_field_id, ''        , 0              , media_id
            FROM notes
            WHERE game_id = $game_id");

        // create text field
        $caption_field_id = dbconnection::queryInsert("INSERT INTO
            fields (game_id , field_type, label    , required, sort_index)
            VALUES ($game_id, 'TEXTAREA', 'Caption', 1       , -2        )");
        // for each note, create field_data with its description
        dbconnection::query("INSERT INTO
            field_data (note_id, field_id         , field_data , field_option_id, media_id)
            SELECT      note_id, $caption_field_id, description, 0              , 0
            FROM notes
            WHERE game_id = $game_id");

        // create category field
        $category_field_id = dbconnection::queryInsert("INSERT INTO
            fields (game_id , field_type    , label     , required, sort_index)
            VALUES ($game_id, 'SINGLESELECT', 'Category', 1       , -1        )");
        // create category field_options
        $tags = tags::getTagsForGame($pack)->data;
        $tag_mapping = array();
        foreach ($tags as $tag) {
            $option = '"' . addslashes($tag->tag) . '"';
            $sort_index = $tag->sort_index;
            $color = '"' . addslashes($tag->color) . '"';
            $field_option_id = dbconnection::queryInsert("INSERT INTO
                field_options (field_id          , game_id , `option`, sort_index , color )
                VALUES        ($category_field_id, $game_id, $option , $sort_index, $color)");
            $tag_mapping[$tag->tag_id] = $field_option_id;
        }
        // for each note, create field_data with the field_option_id corresponding to its tag_id
        $case_expr = 'CASE object_tags.tag_id ';
        foreach ($tag_mapping as $tag_id => $field_option_id) {
            $case_expr .= "WHEN $tag_id THEN $field_option_id ";
        }
        $case_expr .= 'ELSE 0 END';
        dbconnection::query("INSERT INTO
            field_data (note_id      , field_id          , field_data, field_option_id, media_id)
            SELECT      notes.note_id, $category_field_id, ''        , $case_expr     , 0
            FROM notes
            LEFT JOIN object_tags ON object_tags.game_id = notes.game_id AND object_tags.object_type = 'NOTE' AND notes.note_id = object_tags.object_id
            WHERE notes.game_id = $game_id");

        // put the 3 field_ids in the game
        $pack->field_id_preview = $media_field_id;
        $pack->field_id_pin     = $category_field_id;
        $pack->field_id_caption = $caption_field_id;
        games::updateGame($pack);

        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
