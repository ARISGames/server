<?php
require_once("dbconnection.php");
require_once("util.php");
require_once("return_package.php");

class misc extends dbconnection
{

public static function getLeaderboard($pack)
{
  $insts = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND object_type = 'ITEM' AND object_id = '{$pack->item_id}' AND owner_type = 'USER' ORDER BY qty DESC LIMIT 10;");
  $entries = array();

  for($i = 0; $i < count($insts); $i++)
  {
    $inst = $insts[$i];
    $user = dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$inst->owner_id}';");

    $entries[$i] = new stdClass();
    $entries[$i]->qty = $inst->qty;
    if($user)
    {
      $entries[$i]->user_id = $user->user_id;
      $entries[$i]->user_name = $user->user_name;
      $entries[$i]->display_name = $user->display_name;
    }
    else
    {
      $entries[$i]->user_id = 0;
      $entries[$i]->user_name = "(user not found)";
      $entries[$i]->display_name = "(user not found)";
    }
  }
  return new return_package(0, $entries);
}

}
?>
