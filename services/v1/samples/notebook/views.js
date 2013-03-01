function ActionButton(html, callback)
{
    /* 
     * Default Color: #DDDDDD; (light gray)
     * Hovered: Darken uniformly (-111111)
     * Selected: Set blue (|0000FF)
     */
    var self = this; // <- I hate javascript.
    this.hover = function()
    {
        self.hovered = true;
        if(self.selected) self.html.style.backgroundColor = '#CCCCFF';
        else self.html.style.backgroundColor = '#CCCCCC';
    };
    this.unhover = function()
    {
        self.hovered = false;
        if(self.selected) self.html.style.backgroundColor = '#DDDDFF';
        else self.html.style.backgroundColor = '#DDDDDD';
    };
    this.select = function()
    {
        self.selected = true;
        if(self.hovered) self.html.style.backgroundColor = '#CCCCFF';
        else self.html.style.backgroundColor = '#DDDDFF';
    };
    this.deselect = function()
    {
        self.selected = false;
        if(self.hovered) self.html.style.backgroundColor = '#CCCCCC'
        else self.html.style.backgroundColor = '#DDDDDD';
        self.callback(self);
    };
    this.hovered = false;
    this.selected = false;

    this.html = html;
    this.callback = callback;
    this.html.addEventListener('mouseover', this.hover, false);
    this.html.addEventListener('mouseout', this.unhover, false);
    this.html.addEventListener('mousedown', this.select, false);
    this.html.addEventListener('mouseup', this.deselect, false);
}

/* Note- the callback is in charge of enforcing the single selection */
/* Sends (self) to callback on click */
function SingleSelectionButton(html, callback)
{
    /* 
     * Default Color: #DDDDDD; (light gray)
     * Hovered: Darken uniformly (-111111)
     * Selected: Set blue (|0000FF)
     */
    var self = this; // <- I hate javascript.
    this.hover = function()
    {
        self.hovered = true;
        if(self.selected) self.html.style.backgroundColor = '#CCCCFF';
        else self.html.style.backgroundColor = '#CCCCCC';
    };
    this.unhover = function()
    {
        self.hovered = false;
        if(self.selected) self.html.style.backgroundColor = '#DDDDFF';
        else self.html.style.backgroundColor = '#DDDDDD';
    };
    this.select = function()
    {
        self.callback(self);
        self.selected = true;
        if(self.hovered) self.html.style.backgroundColor = '#CCCCFF';
        else self.html.style.backgroundColor = '#DDDDFF';
    };
    this.deselect = function()
    {
        self.selected = false;
        if(self.hovered) self.html.style.backgroundColor = '#CCCCCC'
        else self.html.style.backgroundColor = '#DDDDDD';
    };
    this.hovered = false;
    this.selected = false;

    this.html = html;
    this.callback = callback;
    this.html.addEventListener('mouseover', this.hover, false);
    this.html.addEventListener('mouseout', this.unhover, false);
    this.html.addEventListener('click', this.select, false);
}

	
function MapMarker(callback, object)
{
    var self = this; // <- I hate javascript.
    this.callback = callback;
    this.object = object;
	//this.marker = //new google.maps.Marker({ position:this.object.geoloc, map:model.views.gmap, });
	console.log(this.object);
	
	if (this.object.contents[0] == null)
		return;
		
		
	var imageMarker = new RichMarker({
          position: this.object.geoloc,
          map: model.views.gmap,
          draggable: false,
        //  content: "<div height='40' width='30' style='border:2px solid white; -moz-box-shadow:0px 0px 10px #000; -webkit-box-shadow:0px 0px 10px #000; box-shadow:0px 0px 10px #000;'><img src='" + this.object.contents[0].media_url + "' style=" + boxStyle + "height='40' width='30'/></div>"
		   content: constructMarker(this.object)
          });
		
		//imageMarker.setShadow('0px -3px 4px rgba(88,88,88,0.2)');
		
		// old way of doing it without using richmarker library
		/*var imageIcon = new google.maps.MarkerImage(
    		this.object.contents[0].media_url,
    		null, // size is determined at runtime 
    		null, // origin is 0,0 
    		null, // anchor is bottom center of the scaled image 
    		new google.maps.Size(56, 75)
			);
		this.marker.setIcon(imageIcon);*/
	
   this.marker = imageMarker;
   google.maps.event.addListener(this.marker, 'click', function(e) { self.callback(self); });
   
}

function constructMarker(note) {
	var html;
	var mediaURL = getMediaToUse(note);
	mediaType = mediaToUseType(note);
	var clip;
	var size;
	
	if (mediaType == "PHOTO") {
		clip = "clip:rect(2px 30px 32px 2px)";
		size = "height='40' width='30'";
		position = "top:0;left:0;";
		
	} else
	{
		clip = "";
		size = "height = '25' width = '25'";
		position = "top:4;left:6;";
	}
	
	html  = "<div style='cursor:pointer;'><img src='./images/speechBubble.png' height='51' width='43'/> <img src='" + getMediaToUse(note) + "'   style='" + position + " position:absolute;" + clip + "' " + size + "/></div><div style='top:1;left:33; position:absolute' >" +   getIconsForNoteContents(note) +"</div>"	;
	
	return html;
}

function getMediaToUse(note) {
	var mediaURL = "";
	
	//for (i = 0; i < note.contents.length; i++) {
		//if (note.contents[i].type == "PHOTO")
		//	return note.contents[i].media_url;
	//}
	if (note.contents[0].type == "PHOTO")
		mediaURL = "./images/defaultImageIcon.png";
	else if (note.contents[0].type == "TEXT")
		mediaURL = "./images/defaultTextIcon.png";
	else if (note.contents[0].type == "AUDIO")
		mediaURL = "./images/defaultAudioIcon.png";
	else if (note.contents[0].type == "VIDEO")
		mediaURL = "./images/defaultVideoIcon.png";
	
	return mediaURL;
}

function mediaToUseType(note) {
	
	/*for (i = 0; i < note.contents.length; i++) {
		if (note.contents[i].type == "PHOTO")
			return "PHOTO";
	}*/
	
	return note.contents[0].type;
}


function getIconsForNoteContents(note)
	{
		if (note.contents[0] == null)
			return "";
			
		var textCount = 0;
		var audioCount = 0;
		var videoCount = 0;
		var photoCount = 0;
		
		for (i = 0; i < note.contents.length; i++) {
	
			if (note.contents[i].type == "AUDIO")
				audioCount++;
			else if (note.contents[i].type == "VIDEO")
				videoCount++;
			else if (note.contents[i].type == "PHOTO")
				photoCount++;
			else  if (note.contents[i].type == "TEXT")
				textCount++;
		}
		
		var iconHTML = "";
		if (textCount > 0)
			iconHTML += '<img src="./images/defaultTextIcon.png" height=8px;><br>';
		if (audioCount > 0)
			iconHTML += '<img src="./images/defaultAudioIcon.png" height=8px;><br>';
		if (photoCount > 0)
			iconHTML += '<img src="./images/defaultImageIcon.png" height=8px;><br> ';
		if (videoCount > 0)
			iconHTML += '<img src="./images/defaultVideoIcon.png" height=8px;>';

		return iconHTML;
	};

/* Sends (self, selected) to callback on click */
function SelectionCell(html, odd, callback, object)
{
    /* 
     * Default Color: #DDDDDD; (light gray)
     * Odd Id'd: Darken uniformly (-222222) 
     * Hovered: Darken uniformly (-111111)
     * Selected: Set blue (|0000FF)
     */
    var self = this; // <- I hate javascript.
    this.hover = function()
    {
        self.hovered = true;
        if(self.selected) self.html.style.backgroundColor = '#AAAAFF';
        else self.html.style.backgroundColor = '#AAAAAA';
    };
    this.unhover = function()
    {
        self.hovered = false;
        if(self.odd)
        {
            if(self.selected) self.html.style.backgroundColor = '#BBBBFF';
            else self.html.style.backgroundColor = '#BBBBBB';
        }
        else
        {
            if(self.selected) self.html.style.backgroundColor = '#DDDDFF';
            else self.html.style.backgroundColor = '#DDDDDD';
        }
    };
    this.select = function()
    {
        self.selected = true;
        if(self.odd)
        {
            if(self.hovered) self.html.style.backgroundColor = '#AAAAFF';
            else self.html.style.backgroundColor = '#BBBBFF';
        }
        else
        {
            if(self.hovered) self.html.style.backgroundColor = '#CCCCFF';
            else self.html.style.backgroundColor = '#DDDDFF';
        }
		self.html.firstChild.innerHTML = changeCheckBox(self.html.firstChild.innerHTML, true);
		
    };
    this.deselect = function()
    {
        self.selected = false;
        if(self.odd)
        {
            if(self.hovered) self.html.style.backgroundColor = '#AAAAAA'
            else self.html.style.backgroundColor = '#BBBBBB';
        }
        else
        {
            if(self.hovered) self.html.style.backgroundColor = '#CCCCCC'
            else self.html.style.backgroundColor = '#DDDDDD';
        }
		self.html.firstChild.innerHTML = changeCheckBox(self.html.firstChild.innerHTML, false);
	
    };
    this.clicked = function()
    {
        if(self.selected)self.deselect();
        else self.select();
        self.callback(self, !self.selected);
    }

    this.hovered = false;
    this.selected = false;

    this.html = html;
    this.odd = odd;
    this.callback = callback;
    this.object = object;
    this.html.addEventListener('mouseover', this.hover, false);
    this.html.addEventListener('mouseout', this.unhover, false);
    this.html.addEventListener('click', this.clicked, false);

    this.deselect(); //give self odd coloring
}

function changeCheckBox(innerHTML, checked)
{
	
	var checkboxCheckedFilename = "checkbox.png";
	var checkboxUncheckedFilename = "checkboxUnchecked.png";
	var htmlCheckboxChecked = '<img src="./images/' + checkboxCheckedFilename + '" height="14px";>  ';
	var htmlCheckboxUnchecked = '<img src="./images/' + checkboxUncheckedFilename + '" height="14px";>  ';
	
	// clear out previous check box
	console.log("orininal inner html: " + innerHTML);
	var checkBoxLoc = innerHTML.indexOf(checkboxCheckedFilename);
	if (checkBoxLoc >= 0) 
		innerHTML = innerHTML.substr(htmlCheckboxChecked.length+4, innerHTML.length);
	console.log("checkBoxLoc:" + checkBoxLoc);
	checkBoxLoc = innerHTML.indexOf(checkboxUncheckedFilename);
	if (checkBoxLoc >= 0) 
		innerHTML = innerHTML.substr(htmlCheckboxUnchecked.length+4, innerHTML.length);
	console.log("checkBoxLoc 2:" + checkBoxLoc);
	console.log("cleaned inner html: " + innerHTML);
	
	// insert new check box
	if (checked == true) 
		innerHTML = htmlCheckboxChecked + innerHTML;
	else
		innerHTML = htmlCheckboxUnchecked + innerHTML;
	console.log("new inner html: " + innerHTML);
		
	return innerHTML;
}

/* Note- the callback is in charge of enforcing the single selection */
/* Sends (self) to callback on click */
function SingleSelectionCell(html, odd, callback, object)
{
    /* 
     * Default Color: #DDDDDD; (light gray)
     * Odd Id'd: Darken uniformly (-222222) 
     * Hovered: Darken uniformly (-111111)
     * Selected: Set blue (|0000FF)
     */
    var self = this; // <- I hate javascript.
    this.hover = function()
    {
        self.hovered = true;
        if(self.selected) self.html.style.backgroundColor = '#AAAAFF';
        else self.html.style.backgroundColor = '#AAAAAA';
    };
    this.unhover = function()
    {
        self.hovered = false;
        if(self.odd)
        {
            if(self.selected) self.html.style.backgroundColor = '#BBBBFF';
            else self.html.style.backgroundColor = '#BBBBBB';
        }
        else
        {
            if(self.selected) self.html.style.backgroundColor = '#DDDDFF';
            else self.html.style.backgroundColor = '#DDDDDD';
        }
    };
    this.select = function()
    {
        self.callback(self);
        self.selected = true;
        if(self.odd)
        {
            if(self.hovered) self.html.style.backgroundColor = '#AAAAFF';
            else self.html.style.backgroundColor = '#BBBBFF';
        }
        else
        {
            if(self.hovered) self.html.style.backgroundColor = '#CCCCFF';
            else self.html.style.backgroundColor = '#DDDDFF';
        }
    };
    this.deselect = function()
    {
        self.selected = false;
        if(self.odd)
        {
            if(self.hovered) self.html.style.backgroundColor = '#AAAAAA'
            else self.html.style.backgroundColor = '#BBBBBB';
        }
        else
        {
            if(self.hovered) self.html.style.backgroundColor = '#CCCCCC'
            else self.html.style.backgroundColor = '#DDDDDD';
        }
    };

    this.hovered = false;
    this.selected = false;

    this.html = html;
    this.odd = odd;
    this.callback = callback;
    this.object = object;
    this.html.addEventListener('mouseover', this.hover, false);
    this.html.addEventListener('mouseout', this.unhover, false);
    this.html.addEventListener('click', this.select, false);

    this.deselect(); //Set its color
}

this.playerPicForNote = function(username) 
{
	var picHTML = '  <img src="' + model.getProfilePicForContributor(username) + '"vertical-align:middle; height=40px;> ';
	return picHTML;
};
	

function NoteView(html, object)
{
    this.html = html;
    this.object = object;

    this.constructHTML = function()
    {
        if(!this.object) return; 

        //Ok. This next bit of codes is going to look ridiculous... but since the DOM has no easy way of heirarchical access, its the best I can think of.
        //I recommend opening 'index.html' and finding the xml defining 'note_view_construct' (the DOM node cloned to be this.html) as reference (vim command ':sp index.html')
        this.html.children[0].children[0].innerHTML = this.object.title;
        this.html.children[0].children[1].innerHTML = this.object.likes+' likes, '+this.object.comments.length+' comments';
		console.log(this.object.username);
        this.html.children[0].children[2].innerHTML = playerPicForNote(this.object.username) + this.object.username;

        this.html.children[1].innerHTML = 'Tags: '+object.tagString;

        //Content
        for(var i = 0; i < this.object.contents.length; i++)
            this.html.children[2].appendChild(this.constructContentHTML(this.object.contents[i]));

        //Comments
        for(var i = 0; i < this.object.comments.length; i++)
            this.html.children[4].appendChild(this.constructCommentHTML(this.object.comments[i]));
    };
	

    this.constructContentHTML = function(content)
    {
        var contentHTML = document.getElementById('note_content_cell_construct').cloneNode(true);
        contentHTML.setAttribute('id','');
        switch(content.type)
        {
            case 'TEXT':
                contentHTML.innerHTML = content.text;
                break;
            case 'PHOTO':
                contentHTML.innerHTML = '<img class="note_media" src="'+content.media_url+'" />';
                break;
            case 'AUDIO':
                //contentHTML.innerHTML = '<audio class="note_media" controls="controls"><source src="'+content.media_url+'" type="audio/mpeg"><a href="'+content.media_url+'">audio</a></audio>';
                contentHTML.innerHTML = '<a href="'+content.media_url+'">audio</a>';
                break;
            case 'VIDEO':
                contentHTML.innerHTML = '<video class="note_media" controls="controls"><source src="'+content.media_url+'"><a href="'+content.media_url+'">video</a></video>';
                break;
        }
        return contentHTML;
    };

    this.constructCommentHTML = function(comment)
    {
        var commentHTML = document.getElementById('note_comment_cell_construct').cloneNode(true);
        commentHTML.setAttribute('id','');
        commentHTML.children[0].innerHTML = comment.username;
        commentHTML.appendChild(this.constructContentHTML({"type":"TEXT","text":comment.title}));
        for(var i = 0; i < comment.contents.length; i++)
            commentHTML.appendChild(this.constructContentHTML(comment.contents[i]));
        return commentHTML;
    }

    this.constructHTML();
}
