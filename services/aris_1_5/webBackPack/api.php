<?php
require_once('webbackpack.php');

	$mode = $_GET['mode']; //web- formats for webpage; json- formats for json;
	if($mode != "web" && $mode != "json") die("Set mode='json' or mode='web'.");
	$gameId = $_GET['gameId'];
	$playerId = $_GET['playerId'];

	//Populate $backPack with array of individuals' backpacks
	$wbp = new webbackpack();
	$backPack = array();

	if(!$playerId)
		$backPack = $wbp->getGameData($gameId);
	else
		if(!is_array($playerId))
			$backPack[] = $wbp->getData($gameId,$playerId);
		else
			foreach($playerId as $pid)
				$backPack[] = $wbp->getData($gameId,$pid);


	//Print formatting for web page
	if($mode == "web")
		outputForWeb($backPack);	
	//Print json encoded array of backpacks
	else if($mode == "json")
		echo json_encode($backPack);
		/*JSON FORMAT-
		[
		  {
		    "game":
		    {
		      "game_id":"_#_also_parent_folder_of_game_media",
		      "name":"_title_of_game",
		      "media_name":"_title_of_game_splash_screen_image",
		      "media_url":"_splash_screen_image_file_location",
		      "icon_media_name":"_title_of_game_icon_image",
		      "icon_media_url":"_icon_file_location"
		    },
		    "owner":
		    {
		      {
		        "user_name":"_name_of_user",
		      },
		      {
		        "attributes":
		        [	
		          {
		            "item_id":"_#",
		            "name":"_name_of_item",
		            "description":"_item_description",
		            "max_qty_in_inventory":"_default_500",
		            "weight":"_default_0",
		            "type":"_ATTRIB",
		            "url":"_url_only_if_type_URL",
		            "qty":"_qty_in_players_inventory",
		            "media_name":"_title_of_item_media",
		            "media_file_name":"_item_media_file_location",
		            "media_game_id":"_game_id_and_parent_folder_of_media",
		            "icon_name":"_title_of_item_icon_media",
		            "icon_file_name":"_item_icon_file_location",
		            "icon_game_id":"_game_id_and_parent_folder_of_icon"
		          }
		          {
		            ...
		          }
		        ],
			"items":
			[	
		          {
		            "item_id":"_#",
		            "name":"_name_of_item",
		            "description":"_item_description",
		            "max_qty_in_inventory":"_default_500",
		            "weight":"_default_0",
		            "type":"_NORMAL_or_URL",
		            "url":"_url_only_if_type_URL",
		            "qty":"_qty_in_players_inventory",
		            "media_name":"_title_of_item_media",
		            "media_file_name":"_item_media_file_location",
		            "media_game_id":"_game_id_and_parent_folder_of_media",
		            "icon_name":"_title_of_item_icon_media",
		            "icon_file_name":"_item_icon_file_location",
		            "icon_game_id":"_game_id_and_parent_folder_of_icon"
		          }
		          {
		            ...
		          }
		        ]

		      }
		    }
		  }
		  {
		    ...
		  }
		]
		*/
		
	//Should not get here
	else
		echo "PHP error.";


	//Construct entire web page programmatically
	function outputForWeb($backPack)
	{
		//Output Website Header
		echo 	"<html>\n".
			"<head>\n".
			"<title>Web Back Pack</title>\n".
			"<link rel='stylesheet' type='text/css' href='wbp.css' />\n".
			"</head>\n".
			"<body>\n";

		$firstPlayer = true;
		//Run through for each player given
		foreach($backPack as $bp)
		{
			if(!$firstPlayer)
				echo "<div class='playerspacer'> </div>\n";
			else
				$firstPlayer = false;

			//Output player header
			echo "<div class='player'>\n";
			echo "<span class='player_name'>".$bp->owner->user_name."</span>\n";

			$firstSection = true;

			if(!$firstSection)
				echo "<div class='sectionspacer'> </div>\n";
			else
				$firstSection=false;
			
			//Output player attributes
			echo "<div class='section attributes'>\n";
			if(count($bp->attributes) > 0)
			{

				$firstContent = true;
				echo "<div class='sectionheader'>Attributes</div>\n";
				foreach($bp->attributes as $attribute)
				{
					if(!$firstContent)
						echo "<div class='contentspacer'></div>\n";					
					else
						$firstContent = false;

					//Output attribute header
					echo "<div class='content attribute'>\n";
	
						echo "<div style='float:left; width:110px;'>\n";

							if($attribute->icon_file_name)
							{
								echo "<div class='attrib attribute_attrib attribute_icon'>\n";					
									echo "<img src = '../../../gamedata/".$attribute->icon_game_id."/".$attribute->icon_file_name."' alt = '".$attribute->icon_name."'/>\n";
								echo "</div>\n"; //<- class 'attribute_icon_file_name'
							}
	
							if($attribute->media_file_name)
							{
								echo "<div class='attrib attribute_attrib attribute_media'>\n";					
									echo "<a href='../../../gamedata/".$attribute->media_game_id."/".$attribute->media_file_name."'><img src = '../../../gamedata/".$attribute->media_game_id."/".$attribute->media_file_name."' alt = '".$attribute->media_name."'/></a>\n";
								echo "</div>\n"; //<- class 'attribute_media_file_name'
							}

						echo "</div>\n";
						echo "<div style-'float:left; width:480px;'>\n";

							if($attribute->name)
							{
								echo "<div class='attrib attribute_attrib attribute_name'>\n";					
									echo $attribute->name;
								echo "</div>\n"; //<- class 'attribute_name'
							}
		
							if($attribute->description)
							{
								echo "<div class='attrib attribute_attrib attribute_description'>\n";					
									echo $attribute->description;
								echo "</div>\n"; //<- class 'attribute_description'
							}
		
							if($attribute->qty)
							{
								echo "<div class='attrib attribute_attrib attribute_qty'>\n";					
									echo "Qty: ".$attribute->qty;
								echo "</div>\n"; //<- class 'attribute_qty'
							}

						echo "</div>\n";
		
					//Output attribute footer
					echo "</div>\n"; //<- class 'attribute'
				}
			}
			echo "</div>\n"; //<- class 'attributes'
	
			if(!$firstSection)
				echo "<div class='sectionspacer'> </div>\n";
			else
				$firstSection=false;

			//Output player items
			echo "<div class='section items'>\n";

			if(count($bp->items) > 0)
			{
				$firstContent = true;
				echo "<div class='sectionheader'>Items</div>\n";
				foreach($bp->items as $item)
				{

					if(!$firstContent)
						echo "<div class='contentspacer'></div>\n";					
					else
						$firstContent = false;

					//Output item header
					echo "<div class='content item'>\n";
	
						echo "<div style='float:left; width:110px;'>\n";

							if($item->icon_file_name)
							{
								echo "<div class='attrib item_attrib item_icon'>\n";					
									echo "<img src = '../../../gamedata/".$item->icon_game_id."/".$item->icon_file_name."' alt = '".$item->icon_name."'/>\n";
								echo "</div>\n"; //<- class 'item_icon_file_name'
							}

							if($item->media_file_name)
							{
								echo "<div class='attrib item_attrib item_media'>\n";					
									echo "<a href='../../../gamedata/".$item->media_game_id."/".$item->media_file_name."'><img src = '../../../gamedata/".$item->media_game_id."/".$item->media_file_name."' alt = '".$item->media_name."'/></a>\n";
								echo "</div>\n"; //<- class 'item_media_file_name'
							}

						echo "</div>\n";
						echo "<div style-'float:left; width:480px;'>\n";

							if($item->name)
							{
								echo "<div class='attrib item_attrib item_name'>\n";					
									echo $item->name;
								echo "</div>\n"; //<- class 'item_name'
							}
		
							if($item->description)
							{
								echo "<div class='attrib item_attrib item_description'>\n";					
									echo $item->description;
								echo "</div>\n"; //<- class 'item_description'
							}
		
							if($item->url)
							{
								echo "<div class='attrib item_attrib item_url'>\n";					
									echo "<a href='{$item->url}'>url</a>";
								echo "</div>\n"; //<- class 'item_url'
							}
		
							if($item->qty)
							{
								echo "<div class='attrib item_attrib item_qty'>\n";					
									echo "Qty: ".$item->qty;
								echo "</div>\n"; //<- class 'item_qty'
							}

						echo "</div>\n";
			
					//Output item footer
					echo "</div>\n"; //<- class 'item'
				}
			}
			echo "</div>\n"; //<- class 'items'

			//Output player footer
			echo "</div>\n"; //<- class 'player'
		}

		//Output website Footer
		echo	"</body>\n".
			"</html>";
	}
?>
