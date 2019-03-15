CREATE INDEX note_comment_note_id ON note_comments(game_id, note_id);
DROP INDEX note_comment_note_id ON notes;
CREATE INDEX note_like_note_id ON note_likes(game_id, note_id);
DROP INDEX note_like_note_id ON notes;
