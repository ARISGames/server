//Generates the entire formatting of the page on the client. Stores it in this string, and then just plops it in the body content
var htmlcontent = "";

//Easy way to append to the string
function add(string)
{
    htmlcontent+=string;
}

function format_img(src, alt, href, classes)
{
    if(src)
    {
        if(href)
            return "<a href='"+href+"'><img class='"+classes+"' src='"+src+"' alt='"+alt+"' /></a>\n";
        else
            return "<img class='"+classes+"' src='"+src+"' alt='"+alt+"' />\n";
    }
    else if(href)
        return "<a href='"+href+"'><img class='"+classes+"' src='"+href+"' alt='"+alt+"' /></a>\n";
    else return "";
}

function formatPage(game)
{
    add("<div class='page'>\n");

    //Set header of page
    add("<div class='pageheader'>\n");
    add("<div class='gameicon'>\n");
    add(format_img(game.icon_url, game.media_url, game.media_url, 'gameiconimage'));
    add("</div>\n");//<- class gameicon
    add("<div class='gametext'>\n");
    add("<div class='gametitle'>\n");
    add(game.name);
    add("</div>\n");//<- class gametitle
    add("<div class='gameauthors'>\n");
    for(author in game.authors)
    {
        //This next line is absolutely ridiculous and I have no idea why it works
        author = (game.authors[author]);
        add("<div class='gameauthor'>\n");
        add(author.name+"\n");
        add("</div>\n");//<- class gameauthor
    }
    add("</div>\n");//<- class gameauthors
    add("</div>\n");//<- class 'gametext'
    add("<div class='gamelinks'>\n");
    add("<a href='#' onClick='window.open(\"\", \"Dump\"); document.getElementById(\"dumpForm\").submit();'>Download Content</a>");
    add("<form style='display:none;' id='dumpForm' method='POST' target='Dump' action='../../dump.php?gameId="+game.game_id+"'>");
    add("<input id='dumpFormInput' type='text' name='game'></input>");
    add("</form>");//<- form dumpForm
    add("</div>\n");//<- class gamelinks
    add("</div>\n");//<- class pageheader
    
    add("<div class='spacer headerspacer'></div>\n");

    if(game.backpacks && !game.backpacks.length) //game.backbacks is object, not array
    {
        //So we'll put it in an array to keep processing of the data the same
        var bps = new Array(game.backpacks);
        game.backpacks = bps;
    }

    var firstPlayer = true;
    //Run through for each player given
    if(game.backpacks.length > 0)
    {
        for(bp in game.backpacks)
        {
            //This next line is absolutely ridiculous and I have no idea why it works
            bp = (game.backpacks[bp]);
            if(bp == "Invalid Player ID") continue;

            if(!firstPlayer)
                add("<div class='spacer playerspacer'> </div>\n");
            else
                firstPlayer = false;

            //Output player header
            add("<div class='player'>\n");
            add("<div class='playerheader'>\n");
            add("<div class='playericon'>\n");
            if(bp.owner.player_pic_url && bp.owner.player_pic_url != "")
                add(format_img(bp.owner.player_pic_url, '', 'index.html?mode=web&gameId='+game.game_id+'&playerId='+bp.owner.player_id, 'playericonimage'));
            else
                add(format_img('profpic.png', '', 'index.html?mode=web&gameId='+game.game_id+'&playerId='+bp.owner.player_id, 'playericonimage'));
            add("</div>\n");//<- class 'playericon'
            add("<div class='playertext'>\n");
            if(bp.owner.display_name && bp.owner.display_name != "")
                add("<div class='playername'>"+bp.owner.display_name+"</div>\n");
            else
                add("<div class='playername'>"+bp.owner.user_name+"</div>\n");
            add("</div>\n");//<- class 'playertext'
            add("</div>\n");//<- class 'playerheader'

            firstSection = true;

            if(bp.attributes.length == 0 && bp.items.length == 0 && bp.notes.length == 0)
            {
                add("<div class='emptyplayer'> This player has no content to show. Get to playing! </div>\n</div>\n");
                continue;
            }

            //Output player attributes
            if(bp.attributes.length > 0)
            {
                add("<div class='section attributes'>\n");
                if(!firstSection)
                    add("<div class='spacer sectionspacer'> </div>\n");
                else
                    firstSection=false;

                firstContent = true;
                add("<div class='sectionheader'>Attributes</div>\n");
                for(attribute in bp.attributes)
                {
                    //This next line is absolutely ridiculous and I have no idea why it works
                    attribute = (bp.attributes[attribute]);

                    if(!firstContent)
                        add("<div class='spacer contentspacer'></div>\n");					
                    else
                        firstContent = false;

                    //Output attribute header
                    add("<div class='content attribute'>\n");

                    add("<div class='left'>\n");

                    if(attribute.icon_url)
                    {
                        add("<div class='attrib attribute_attrib attribute_icon'>\n");
                        add(format_img(attribute.icon_url, attribute.icon_name, attribute.media_url, 'thumbnail'));
                        add("</div>\n"); //<- class 'attribute_icon_file_path'
                    }


                    add("</div>\n");//<- class 'left'
                    add("<div class='right'>\n");
                    add("<div class='contentheader'>\n");
                    if(attribute.name)
                    {
                        add("<div class='attrib attribute_attrib attrib_name attribute_name'>\n");					
                        add(attribute.name);
                        add("</div>\n"); //<- class 'attribute_name'
                        if(attribute.qty && attribute.qty > 1)
                        {
                            add("<div class='attrib attribute_attrib attrib_qty attribute_qty'>\n");					
                            add("Qty: "+attribute.qty);
                            add("</div>\n"); //<- class 'attribute_qty'
                        }
                    }
                    add("</div>\n");//<- class 'contentheader'

                    if(attribute.description)
                    {
                        add("<div class='attrib attribute_attrib attribute_description'>\n");					
                        add(attribute.description);
                        add("</div>\n"); //<- class 'attribute_description'
                    }


                    add("</div>\n");//<- class 'right'

                    //Output attribute footer
                    add("</div>\n"); //<- class 'attribute'
                }
                add("</div>\n"); //<- class 'attributes'
            }

            //Output player items
            if(bp.items.length > 0)
            {
                add("<div class='section items'>\n");
                if(!firstSection)
                    add("<div class='spacer sectionspacer'> </div>\n");
                else
                    firstSection=false;

                firstContent = true;
                add("<div class='sectionheader'>Items</div>\n");
                for(item in bp.items)
                {
                    //This next line is absolutely ridiculous and I have no idea why it works
                    item = (bp.items[item]);

                    if(!firstContent)
                        add("<div class='spacer contentspacer'></div>\n");					
                    else
                        firstContent = false;

                    //Output item header
                    add("<div class='content item'>\n");

                    add("<div class='left'>\n");

                    if(item.icon_url)
                    {
                        add("<div class='attrib item_attrib item_icon'>\n");					
                        add(format_img(item.icon_url, item.icon_name, item.media_url, 'thumbnail'));
                        add("</div>\n"); //<- class 'item_icon_url'
                    }

                    add("</div>\n");//<- class 'left'
                    add("<div class='right'>\n");

                    add("<div class='contentheader'>\n");
                    if(item.name)
                    {
                        add("<div class='attrib item_attrib attrib_name item_name'>\n");
                        add(item.name);
                        add("</div>\n"); //<- class 'item_name'
                        if(item.qty && item.qty > 1)
                        {
                            add("<div class='attrib item_attrib attrib_qty item_qty'>\n");
                            add("Qty: "+item.qty);
                            add("</div>\n"); //<- class 'item_qty'
                        }
                    }
                    add("</div>\n");//<- class 'contentheader'

                    if(item.description)
                    {
                        add("<div class='attrib item_attrib item_description'>\n");
                        add(item.description);
                        add("</div>\n"); //<- class 'item_description'
                    }

                    if(item.url)
                    {
                        add("<div class='attrib item_attrib item_url'>\n");
                        add("<a href='"+item.url+"'>url</a>");
                        add("</div>\n"); //<- class 'item_url'
                    }


                    add("</div>\n");//<- class 'right'

                    //Output item footer
                    add("</div>\n"); //<- class 'item'
                }
                add("</div>\n"); //<- class 'items'
            }

            //Output player notes
            if(bp.notes.length > 0)
            {
                add("<div class='section notes'>\n");
                if(!firstSection)
                    add("<div class='spacer sectionspacer'> </div>\n");
                else
                    firstSection=false;

                firstContent = true;
                add("<div class='sectionheader'>Notes</div>\n");
                for(note in bp.notes)
                {
                    //This next line is absolutely ridiculous and I have no idea why it works
                    note = (bp.notes[note]);

                    if(!firstContent)
                        add("<div class='spacer contentspacer'></div>\n");					
                    else
                        firstContent = false;

                    //Output note header
                    add("<div class='content note'>\n");
                    add("<div class='left'>\n");

                    if(note.username)
                    {
                        add("<div class='attrib note_attrib note_icon'>\n");
                        add(format_img('profpic.png', '', 'index.html?mode=web&gameId='+game.game_id+'&playerId='+note.owner_id, 'playernoteiconimage'));
                        add("<br />"+note.username+"\n");
                        add("</div>\n"); //<- class 'note_icon_file_path'
                    }

                    add("</div>\n");//<- class 'left'
                    add("<div class='right'>\n");

                    if(note.title)
                    {
                        add("<div class='attrib note_attrib attrib_title note_title'>\n");					
                        add(note.title);
                        add("</div>\n"); //<- class 'note_title'
                    }

                    add("<div class='attrib note_attrib attrib_likes note_likes'>\n");					
                    add(format_img('like.png', '', '', 'likeimage notelikeimage'));
                    add(note.likes);
                    add("</div>\n"); //<- class 'note_likes'

                    add("<div class='attrib note_attrib attrib_public note_public_to_notebook'>\n");					
                    if(note.public_to_notebook == 1 && note.public_to_map == 1)
                        add("PUBLIC");
                    else
                        add("PRIVATE");
                    add("</div>\n"); //<- class 'note_to_notebook'

                    add("</div>\n");

                    if(note.contents.length > 0)
                    {
                        firstnotecontent = true;
                        for(notecontent in note.contents)
                        {
                            //This next line is absolutely ridiculous and I have no idea why it works
                            notecontent = (note.contents[notecontent]);

                            if(!firstnotecontent)
                                add("<div class='spacer notecontentspacer'></div>\n");
                            else
                                firstnotecontent = false;

                            add("<div class='notecontent'>\n");
                            add("<div class='left'>\n");
                            if(notecontent.type == "PHOTO")
                            {
                                add("<div class='attrib notecontent_attrib notecontent_media'>\n");
                                add(format_img(notecontent.media_url, '', notecontent.media_url, 'thumbnail'));
                                add("</div>\n");//<- class 'notecontent_media'
                            }	
                            if(notecontent.type == "AUDIO")
                            {
                                add("<div class='attrib notecontent_attrib notecontent_media'>\n");
                                add(format_img('defaultAudioIcon.png', '', notecontent.media_url, 'thumbnail'));
                                add("</div>\n");//<- class 'notecontent_media'
                            }	
                            if(notecontent.type == "VIDEO")
                            {
                                add("<div class='attrib notecontent_attrib notecontent_media'>\n");
                                add(format_img('defaultVideoIcon.png', '', notecontent.media_url, 'thumbnail'));
                                add("</div>\n");//<- class 'notecontent_media'
                            }	
                            if(notecontent.type == "TEXT")
                            {
                                add("<div class='attrib notecontent_attrib notecontent_media'>\n");
                                add(notecontent.title);
                                add("</div>\n");//<- class 'notecontent_media'
                            }
                            add("</div>\n");//<- class 'left'
                            add("<div class='right'>\n");
                            if(notecontent.type == "TEXT")
                            {
                                add("<div class='attrib notecontent_attrib attrib_title notecontent_title'>\n");
                                add(notecontent.text);
                                add("</div>\n");//<- class 'notecontent_title'
                            }
                            else if(notecontent.title)
                            {
                                add("<div class='attrib notecontent_attrib attrib_title notecontent_title'>\n");
                                add(notecontent.title);
                                add("</div>\n");//<- class 'notecontent_title'
                            }	
                            add("</div>\n");//<- class 'right'
                            add("</div>\n");//<- class 'notecontent'
                        }
                    }

                    if(note.comments.length > 0)
                    {
                        firstComment = true;
                        for(comment in note.comments)
                        {
                            //This next line is absolutely ridiculous and I have no idea why it works
                            comment = (note.comments[comment]);

                            if(!firstComment)
                                add("<div class='spacer commentspacer'></div>\n");
                            else
                                firstComment = false;

                            add("<div class='comment'>\n");

                            add("<div class='left'>\n");

                            if(comment.username)
                            {
                                add("<div class='attrib comment_attrib comment_icon'>\n");
                                add(format_img('profpic.png', '', 'index.html?mode=web&gameId='+game.game_id+'&playerId='+comment.owner_id, 'playercommenticonimage'));
                                add("<br />"+comment.username+"\n");
                                add("</div>\n"); //<- class 'comment_icon_file_path'
                            }

                            add("</div>\n");//<- class 'left'
                            add("<div class='right'>\n");

                            if(comment.title)
                            {
                                add("<div class='attrib comment_attrib attrib_title comment_title'>\n");
                                add(comment.title);
                                add("</div>\n");//<- class 'comment_title'
                            }	

                            add("<div class='attrib comment_attrib attrib_likes comment_likes'>\n");					
                            add(format_img('like.png', '', '', 'likeimage notelikeimage'));
                            add(comment.likes);
                            add("</div>\n"); //<- class 'comment_likes'

                            add("</div>\n");

                            if(comment.contents.length > 0)
                            {
                                firstcommentcontent = true;
                                for(commentcontent in comment.contents)
                                {
                                    //This next line is absolutely ridiculous and I have no idea why it works
                                    commentcontent = (comment.contents[commentcontent]);

                                    if(!firstcommentcontent)
                                        add("<div class='spacer commentcontentspacer'></div>\n");
                                    else
                                        firstcommentcontent = false;

                                    add("<div class='commentcontent'>\n");
                                    add("<div class='left'>\n");
                                    if(commentcontent.type == "PHOTO")
                                    {
                                        add("<div class='attrib commentcontent_attrib commentcontent_media'>\n");
                                        add("<a href='../../../../gamedata/"+commentcontent.file_path+"'><img class='thumbnail' src='../../../../gamedata/"+commentcontent.file_path+"' /></a>\n");
                                        add("</div>\n");//<- class 'commentcontent_media'
                                    }	
                                    if(commentcontent.type == "AUDIO")
                                    {
                                        add("<div class='attrib commentcontent_attrib commentcontent_media'>\n");
                                        add("<a href='../../../../gamedata/"+commentcontent.file_path+"'><img class='thumbnail' src='defaultAudioIcon.png' /></a>\n");
                                        add("</div>\n");//<- class 'commentcontent_media'
                                    }	
                                    if(commentcontent.type == "VIDEO")
                                    {
                                        add("<div class='attrib commentcontent_attrib commentcontent_media'>\n");
                                        add("<a href='../../../../gamedata/"+commentcontent.file_path+"'><img class='thumbnail' src='defaultVideoIcon.png' /></a>\n");
                                        add("</div>\n");//<- class 'commentcontent_media'
                                    }	
                                    add("</div>\n");//<- class 'left'
                                    add("<div class='right'>\n");
                                    if(commentcontent.title)
                                    {
                                        add("<div class='attrib commentcontent_attrib attrib_title commentcontent_title'>\n");
                                        add(commentcontent.title);
                                        add("</div>\n");//<- class 'commentcontent_title'
                                    }	
                                    add("</div>\n");//<- class 'right'
                                    add("</div>\n");//<- class 'commentcontent'

                                }
                            }

                            add("</div>\n");

                        }
                    }

                    //Output note footer
                    add("</div>\n"); //<- class 'note'
                }
                add("</div>\n"); //<- class 'notes'
            }

            //Output player footer
            add("</div>\n"); //<- class 'player'
        }
    }
    //Output website Footer
    add("</div>\n");//<- class 'page'


    document.getElementById('body').innerHTML = htmlcontent;
    document.getElementById('dumpFormInput').value = JSON.stringify(game);
}
