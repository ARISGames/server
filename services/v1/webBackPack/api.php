<?php
require_once('webbackpack.php');

	$mode = $_GET['mode']; //web- formats for webpage; json- formats for json;
	if($mode != "web" && $mode != "json") die("Set mode='json' or mode='web'.");
	$gameId = $_GET['gameId'];
	$playerId = $_GET['playerId'];

	//Populate $game->backPacks with array of individuals' backpacks
	$wbp = new webbackpack();
	$game = $wbp->getGameInfo($gameId);
	$game->backPacks = array();

	if(!$playerId)
		$game->backPacks = $wbp->getGameData($gameId);
	else
		if(!is_array($playerId))
			$game->backPacks[] = $wbp->getData($gameId,$playerId,true);
		else
			foreach($playerId as $pid)
				$game->backPacks[] = $wbp->getData($gameId,$pid,true);


	//Print formatting for web page
	if($mode == "web")
		outputForWeb($game);	
	//Print json encoded array of backpacks
	else if($mode == "json")
		echo json_encode($game);
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
	 	        ],
		        "notes":
		        [
		          {
		            "game_id":"177",
		            "note_id":"8",
		            "owner_id":"98",
		            "title":"New Note Changed",
		            "parent_note_id":"0",
		            "sort_index":"0",
                            "public_to_map":"0",
                            "public_to_notebook":"1",
                            "username":"bt",
                            "contents":
		            [
		              {
		                "note_id":"8",
		                "media_id":"247",
		                "type":"AUDIO",
		                "text":"",
		                "sort_index":"0",
		                "game_id":"177",
		                "content_id":"11",
		                "title":"January 11th 2012 01:25:18 PM"
		              }
		              {
		                ...
		              }
		            ],
		            "comments":
		            [
		              {
		                "game_id":"177",
		                "note_id":"10",
		                "owner_id":"98",
		                "title":"J H",
		                "parent_note_id":"8",
		                "sort_index":"0",
		                "public_to_map":"0",
		                "public_to_notebook":"0",
		                "username":"bt",
		                "contents":
				[
				  {
		                    "note_id":"10",
		                    "media_id":"245",
		                    "type":"PHOTO",
		                    "text":"",
		                    "sort_index":"0",
		                    "game_id":"177",
		                    "content_id":"9",
		                    "title":""
		                  },
		                  {
		                    ...
		                  }
		                ],
		                "comments":[],
		                "tags":[],
		                "likes":"1",
		                "player_liked":"1",
		                "icon_media_id":5
		              },
		              {
		                ...
		              }
		            ]
			    "tags":
		            [
		              {
		                "tag":"apple",
		                "player_created":"1"
		              }
		              {
		                ...
		              }
		            ],
		            "likes":"2",
		            "player_liked":"1",
		            "icon_media_id":5
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
	function outputForWeb($game)
	{
		//Output Website Header
		echo 	"<html>\n".
			"<head>\n".
			"<title>Web Back Pack</title>\n".
			"<link rel='stylesheet' type='text/css' href='wbp.css' />\n".
			"</head>\n".
			"<body>\n".
			"<div class='page'>\n";

		//Set header of page
		echo "<div class='pageheader'>\n";
			echo "<div class='gameicon'>\n";
				if($game->icon_media_url)
					echo "<a href='../../../gamedata/".$game->game_id."/".$game->media_url."'><img class='gameiconimage' src='../../../gamedata/".$game->game_id."/".$game->icon_media_url."' alt='".$game->media_name."' /></a>\n";
			echo "</div>\n";//<- class gameicon
			echo "<div class='gametext'>\n";
				echo "<div class='gametitle'>\n";
					echo $game->name;
				echo "</div>\n";//<- class gametitle
				echo "<div class='gameauthors'>\n";
					foreach($game->authors as $author)
					{
						echo "<div class='gameauthor'>\n";
							echo $author->name."\n";
						echo "</div>\n";//<- class gameauthor
					}
				echo "</div>\n";//<- class gameauthors
			echo "</div>\n";//<- class 'gametext'
		echo "</div>\n";//<- class pageheader



		echo "<div class='spacer headerspacer'></div>\n";



		$firstPlayer = true;
		//Run through for each player given
		foreach($game->backPacks as $bp)
		{
			if(!$firstPlayer)
				echo "<div class='spacer playerspacer'> </div>\n";
			else
				$firstPlayer = false;

			//Output player header
			echo "<div class='player'>\n";
			echo "<div class='playerheader'>\n";
				echo "<div class='playericon'>\n";
					echo "<a href='api.php?mode=web&gameId=".$game->game_id."&playerId=".$bp->owner->player_id."'><img class='playericonimage' src='profpic.png' /></a>";
				echo "</div>\n";//<- class 'playericon'
				echo "<div class='playertext'>\n";
				echo "<div class='playername'>".$bp->owner->user_name."</div>\n";
				echo "</div>\n";//<- class 'playertext'
			echo "</div>\n";//<- class 'playerheader'

			$firstSection = true;

			if(count($bp->attributes) == 0 && count($bp->items) == 0 && count($bp->notes) == 0)
			{
				echo "<div class='emptyplayer'> This player has no content to show. Get to playing! </div>\n</div>\n";
				continue;
			}

			//Output player attributes
			if(count($bp->attributes) > 0)
			{
				echo "<div class='section attributes'>\n";
				if(!$firstSection)
					echo "<div class='spacer sectionspacer'> </div>\n";
				else
					$firstSection=false;

				$firstContent = true;
				echo "<div class='sectionheader'>Attributes</div>\n";
				foreach($bp->attributes as $attribute)
				{
					if(!$firstContent)
						echo "<div class='spacer contentspacer'></div>\n";					
					else
						$firstContent = false;

					//Output attribute header
					echo "<div class='content attribute'>\n";
	
						echo "<div class='left'>\n";

							if($attribute->icon_file_name)
							{
								echo "<div class='attrib attribute_attrib attribute_icon'>\n";					
									echo "<img class='thumbnail' src = '../../../gamedata/".$attribute->icon_game_id."/".$attribute->icon_file_name."' alt = '".$attribute->icon_name."'/>\n";
								echo "</div>\n"; //<- class 'attribute_icon_file_name'
							}
	
							if($attribute->media_file_name)
							{
								echo "<div class='attrib attribute_attrib attribute_media'>\n";					
									echo "<a href='../../../gamedata/".$attribute->media_game_id."/".$attribute->media_file_name."'><img class='thumbnail' src = '../../../gamedata/".$attribute->media_game_id."/".$attribute->media_file_name."' alt = '".$attribute->media_name."'/></a>\n";
								echo "</div>\n"; //<- class 'attribute_media_file_name'
							}

						echo "</div>\n";//<- class 'left'
						echo "<div class='right'>\n";

								echo "<div class='contentheader'>\n";
								if($attribute->name)
								{
									echo "<div class='attrib attribute_attrib attrib_name attribute_name'>\n";					
										echo $attribute->name;
									echo "</div>\n"; //<- class 'attribute_name'
									if($attribute->qty && $attribute->qty > 1)
									{
										echo "<div class='attrib attribute_attrib attrib_qty attribute_qty'>\n";					
											echo "Qty: ".$attribute->qty;
										echo "</div>\n"; //<- class 'attribute_qty'
									}
								}
								echo "</div>\n";//<- class 'contentheader'
		
							if($attribute->description)
							{
								echo "<div class='attrib attribute_attrib attribute_description'>\n";					
									echo $attribute->description;
								echo "</div>\n"; //<- class 'attribute_description'
							}
		

						echo "</div>\n";//<- class 'right'
		
					//Output attribute footer
					echo "</div>\n"; //<- class 'attribute'
				}
				echo "</div>\n"; //<- class 'attributes'
			}
	
			//Output player items
			if(count($bp->items) > 0)
			{
				echo "<div class='section items'>\n";
				if(!$firstSection)
					echo "<div class='spacer sectionspacer'> </div>\n";
				else
					$firstSection=false;

				$firstContent = true;
				echo "<div class='sectionheader'>Items</div>\n";
				foreach($bp->items as $item)
				{

					if(!$firstContent)
						echo "<div class='spacer contentspacer'></div>\n";					
					else
						$firstContent = false;

					//Output item header
					echo "<div class='content item'>\n";
	
						echo "<div class='left'>\n";

							if($item->icon_file_name)
							{
								echo "<div class='attrib item_attrib item_icon'>\n";					
									echo "<img class='thumbnail' src = '../../../gamedata/".$item->icon_game_id."/".$item->icon_file_name."' alt = '".$item->icon_name."'/>\n";
								echo "</div>\n"; //<- class 'item_icon_file_name'
							}

							if($item->media_file_name)
							{
								echo "<div class='attrib item_attrib item_media'>\n";					
									echo "<a href='../../../gamedata/".$item->media_game_id."/".$item->media_file_name."'><img class='thumbnail' src = '../../../gamedata/".$item->media_game_id."/".$item->media_file_name."' alt = '".$item->media_name."'/></a>\n";
								echo "</div>\n"; //<- class 'item_media_file_name'
							}

						echo "</div>\n";//<- class 'left'
						echo "<div class='right'>\n";

							echo "<div class='contentheader'>\n";
								if($item->name)
								{
									echo "<div class='attrib item_attrib attrib_name item_name'>\n";
										echo $item->name;
									echo "</div>\n"; //<- class 'item_name'
									if($item->qty && $item->qty > 1)
									{
										echo "<div class='attrib item_attrib attrib_qty item_qty'>\n";
											echo "Qty: ".$item->qty;
										echo "</div>\n"; //<- class 'item_qty'
									}
								}
							echo "</div>\n";//<- class 'contentheader'
			
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
		

						echo "</div>\n";//<- class 'right'
			
					//Output item footer
					echo "</div>\n"; //<- class 'item'
				}
				echo "</div>\n"; //<- class 'items'
			}

			//Output player notes
			if(count($bp->notes) > 0)
			{
				echo "<div class='section notes'>\n";
				if(!$firstSection)
					echo "<div class='spacer sectionspacer'> </div>\n";
				else
					$firstSection=false;

				$firstContent = true;
				echo "<div class='sectionheader'>Notes</div>\n";
				foreach($bp->notes as $note)
				{

					if(!$firstContent)
						echo "<div class='spacer contentspacer'></div>\n";					
					else
						$firstContent = false;

					//Output note header
					echo "<div class='content note'>\n";
						echo "<div class='left'>\n";

							if($note->username)
							{
								echo "<div class='attrib note_attrib note_icon'>\n";
									echo "<a href='api.php?mode=web&gameId=".$game->game_id."&playerId=".$note->owner_id."'><img class='playernoteiconimage' src='profpic.png' /></a><br />".$note->username."\n";
								echo "</div>\n"; //<- class 'note_icon_file_name'
							}

						echo "</div>\n";//<- class 'left'
						echo "<div class='right'>\n";

							if($note->title)
							{
								echo "<div class='attrib note_attrib attrib_title note_title'>\n";					
									echo $note->title;
								echo "</div>\n"; //<- class 'note_title'
							}

								echo "<div class='attrib note_attrib attrib_public note_public_to_notebook'>\n";					
							if($note->public_to_notebook)
									echo "PUBLIC";
							else
									echo "PRIVATE";
								echo "</div>\n"; //<- class 'note_to_notebook'

						echo "</div>\n";

						if(count($note->contents) > 0)
						{
							$firstnotecontent = true;
							foreach($note->contents as $notecontent)
							{
								if(!$firstnotecontent)
									echo "<div class='spacer notecontentspacer'></div>\n";
								else
									$firstnotecontent = false;
								
								echo "<div class='notecontent'>\n";
									echo "<div class='left'>\n";
										if($notecontent->type == "PHOTO")
										{
											echo "<div class='attrib notecontent_attrib notecontent_media'>\n";
												echo "<a href='../../../gamedata/".$notecontent->game_id."/".$notecontent->file_name."'><img class='thumbnail' src='../../../gamedata/".$notecontent->game_id."/".$notecontent->file_name."' /></a>\n";
											echo "</div>\n";//<- class 'notecontent_media'
										}	
										if($notecontent->type == "AUDIO")
										{
											echo "<div class='attrib notecontent_attrib notecontent_media'>\n";
												echo "<a href='../../../gamedata/".$notecontent->game_id."/".$notecontent->file_name."'><img class='thumbnail' src='defaultAudioIcon.png' /></a>\n";
											echo "</div>\n";//<- class 'notecontent_media'
										}	
										if($notecontent->type == "VIDEO")
										{
											echo "<div class='attrib notecontent_attrib notecontent_media'>\n";
												echo "<a href='../../../gamedata/".$notecontent->game_id."/".$notecontent->file_name."'><img class='thumbnail' src='defaultVideoIcon.png' /></a>\n";
											echo "</div>\n";//<- class 'notecontent_media'
										}	
									echo "</div>\n";//<- class 'left'
									echo "<div class='right'>\n";
										if($notecontent->title)
										{
											echo "<div class='attrib notecontent_attrib attrib_title notecontent_title'>\n";
												echo $notecontent->title;
											echo "</div>\n";//<- class 'notecontent_title'
										}	
									echo "</div>\n";//<- class 'right'
								echo "</div>\n";//<- class 'notecontent'
							}
						}
	
						if(count($note->comments) > 0)
						{
							$firstComment = true;
							foreach($note->comments as $comment)
							{
								if(!$firstComment)
									echo "<div class='spacer commentspacer'></div>\n";
								else
									$firstComment = false;

								echo "<div class='comment'>\n";
	
									echo "<div class='left'>\n";
			
										if($comment->username)
										{
											echo "<div class='attrib comment_attrib comment_icon'>\n";					
									echo "<a href='api.php?mode=web&gameId=".$game->game_id."&playerId=".$comment->owner_id."'><img class='playercommenticonimage' src='profpic.png' /></a><br />".$comment->username."\n";
											echo "</div>\n"; //<- class 'comment_icon_file_name'
										}
			
									echo "</div>\n";//<- class 'left'
									echo "<div class='right'>\n";
			
										if($comment->title)
										{
											echo "<div class='attrib comment_attrib attrib_title comment_title'>\n";
												echo $comment->title;
											echo "</div>\n";//<- class 'comment_title'
										}	
	
			
									echo "</div>\n";

									if(count($comment->contents) > 0)
									{
										$firstcommentcontent = true;
										foreach($comment->contents as $commentcontent)
										{
											if(!$firstcommentcontent)
												echo "<div class='spacer commentcontentspacer'></div>\n";
											else
												$firstcommentcontent = false;
									
											echo "<div class='commentcontent'>\n";
												echo "<div class='left'>\n";
													if($commentcontent->type == "PHOTO")
													{
														echo "<div class='attrib commentcontent_attrib commentcontent_media'>\n";
															echo "<a href='../../../gamedata/".$commentcontent->game_id."/".$commentcontent->file_name."'><img class='thumbnail' src='../../../gamedata/".$commentcontent->game_id."/".$commentcontent->file_name."' /></a>\n";
														echo "</div>\n";//<- class 'commentcontent_media'
													}	
													if($commentcontent->type == "AUDIO")
													{
														echo "<div class='attrib commentcontent_attrib commentcontent_media'>\n";
															echo "<a href='../../../gamedata/".$commentcontent->game_id."/".$commentcontent->file_name."'><img class='thumbnail' src='defaultAudioIcon.png' /></a>\n";
														echo "</div>\n";//<- class 'commentcontent_media'
													}	
													if($commentcontent->type == "VIDEO")
													{
														echo "<div class='attrib commentcontent_attrib commentcontent_media'>\n";
															echo "<a href='../../../gamedata/".$commentcontent->game_id."/".$commentcontent->file_name."'><img class='thumbnail' src='defaultVideoIcon.png' /></a>\n";
														echo "</div>\n";//<- class 'commentcontent_media'
													}	
												echo "</div>\n";//<- class 'left'
												echo "<div class='right'>\n";
													if($commentcontent->title)
													{
														echo "<div class='attrib commentcontent_attrib attrib_title commentcontent_title'>\n";
															echo $commentcontent->title;
														echo "</div>\n";//<- class 'commentcontent_title'
													}	
												echo "</div>\n";//<- class 'right'
											echo "</div>\n";//<- class 'commentcontent'

										}
									}
	
								echo "</div>\n";

							}
						}
	
					//Output note footer
					echo "</div>\n"; //<- class 'note'
				}
				echo "</div>\n"; //<- class 'notes'
			}

			//Output player footer
			echo "</div>\n"; //<- class 'player'
		}

		//Output website Footer
		echo	"</div>\n".//<- class 'page'
			"</body>\n".
			"</html>";
	}
?>
