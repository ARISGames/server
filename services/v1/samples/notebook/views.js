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
	//this.marker = new google.maps.Marker({ position:this.object.geoloc, map:model.views.gmap, });  // won't need this eventually
	
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
   model.views.markerclusterer.addMarker(this.marker);
   
   google.maps.event.addListener(this.marker, 'click', function(e) { self.callback(self); });
   
}

function constructMarker(note) {
	var html;
	var mediaURL = getMediaToUse(note);
	mediaType = mediaToUseType(note);
	var clip;
	var size;
	var height;
	var width;
	var left;
	var top;
	
	if (mediaType == "PHOTO") {
		clip = "rect(2px 30px 32px 2px)";
		size = "height='40' width='30'";
		position = "top:0;left:0;";
		height = 40;
		width = 30;
		top = 0;
		left = 0;
		
	} else
	{
		clip = "";
		size = "height = '25' width = '25'";
		position = "top:4;left:6;";
		height = 25;
		width = 25;
		top = 4;
		left = 6;
	}
	
	
	var image = new Image();
	var imageSource = getMediaToUse(note); //"./images/defaultImageIcon.png";
	image.onload = function() {
		//replaceMarkerImage(imageSource);	
	}
	image.src = imageSource;
	image.style.top = top;
	image.style.left = left;
	image.style.position = "absolute";
	image.style.clip = clip;
	image.height = height;
	image.width = width;
	
	var outerDiv = document.createElement('div'); 
	outerDiv.style.cursor = "pointer";
	var innerDiv = document.createElement('div'); 
	innerDiv.style.top = 1;
	innerDiv.style.left = 33;
	innerDiv.style.position = "absolute";
	innerDiv.innerHTML = getIconsForNoteContents(note);
	
	var speechBubble = new Image();
	speechBubble.src = './images/speechBubble.png';
	speechBubble.height = 51;
	speechBubble.width = 43;
	
	outerDiv.appendChild(speechBubble);
	outerDiv.appendChild(image);
	outerDiv.appendChild(innerDiv);
	
	html = outerDiv.outerHTML;
	
	//html  = "<div style=><img src='./images/speechBubble.png' height='51' width='43'/> " + image + " </div><div style='top:1;left:33; position:absolute' >" +   getIconsForNoteContents(note) +"</div>"	;
	
	return html;
}


function getMediaToUse(note) {
	var mediaURL = "";
	
	for (i = 0; i < note.contents.length; i++) {
		if (note.contents[i].type == "PHOTO")
			return note.contents[i].media_url;
	}

	if (note.contents[0].type == "TEXT")
		mediaURL = "./images/defaultTextIcon.png";
	else if (note.contents[0].type == "AUDIO")
		mediaURL = "./images/defaultAudioIcon.png";
	else if (note.contents[0].type == "VIDEO")
		mediaURL = "./images/defaultVideoIcon.png";
	
	return mediaURL;
}

function mediaToUseType(note) {
	
	for (i = 0; i < note.contents.length; i++) {
		if (note.contents[i].type == "PHOTO")
			return "PHOTO";
	}
	
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
	var checkBoxLoc = innerHTML.indexOf(checkboxCheckedFilename);
	if (checkBoxLoc >= 0) 
		innerHTML = innerHTML.substr(htmlCheckboxChecked.length+4, innerHTML.length);
	checkBoxLoc = innerHTML.indexOf(checkboxUncheckedFilename);
	if (checkBoxLoc >= 0) 
		innerHTML = innerHTML.substr(htmlCheckboxUnchecked.length+4, innerHTML.length);
	
	// insert new check box
	if (checked == true) 
		innerHTML = htmlCheckboxChecked + innerHTML;
	else
		innerHTML = htmlCheckboxUnchecked + innerHTML;
		
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

// move to controller
function handleImageFileSelect(files) {
		
	for (var i = 0; i < files.length; i++) {
		var file = files[i];
		var imageType = /image.*/;
		 
		if (!file.type.match(imageType)) {
		  continue;
		}
		 
		var img = document.getElementById("imageThumbnail");
		img.classList.add("obj");
		img.file = file;
		model.currentNote.imageFile = file;
		 
		var reader = new FileReader();
		reader.onload = (function(aImg) { return function(e) { 
			aImg.src = e.target.result; 
			model.currentNote.imageFileURL = e.target.result;
			}; 
		})(img);
		
		reader.readAsDataURL(file);	
		
		console.log
	}
}

//move to controller
function handleAudioFileSelect(files) {
		
	for (var i = 0; i < files.length; i++) {
		var file = files[i];
		var audioType = /audio.*/;

		if (!file.type.match(audioType)) {
		  continue;
		}
		
		// preview audio control
		var audioPreview = document.getElementById("audioPreview");
		audioPreview.src = URL.createObjectURL(file);
		model.currentNote.audioFile = file;
		
	}
}

function showVideo() {
	console.log("showing video");
	unhide("video");
};

function recordAudio() {
	
	try {
      // webkit shim
      window.AudioContext = window.AudioContext || window.webkitAudioContext;
      navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia;
      window.URL = window.URL || window.webkitURL;
      
      model.audio_context = new AudioContext;
      console.log('Audio context set up.');
      console.log('navigator.getUserMedia ' + (navigator.getUserMedia ? 'available.' : 'not present!'));
    } catch (e) {
      alert('No web audio support in this browser!');
    }
    
    navigator.getUserMedia({audio: true}, startUserMedia, function(e) {
      console.log('No live audio input: ' + e);
    });
	
	unhide("startRecording");
	unhide("stopRecording");
	
	
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
		var splitDateCreated = this.object.created.split(/[- :]/);
		var dateCreated = new Date(splitDateCreated[0], splitDateCreated[1]-1, splitDateCreated[2], splitDateCreated[3], splitDateCreated[4], splitDateCreated[5]);
		

		// Apply each element to the Date function

        this.html.children[0].children[0].innerHTML = this.object.title;
        this.html.children[0].children[1].innerHTML = this.object.likes+' likes, '+this.object.comments.length+' comments';
		this.html.children[0].children[2].innerHTML = 'Created: ' + dateCreated.toLocaleString();
        this.html.children[0].children[3].innerHTML = playerPicForNote(this.object.username) + this.object.username;
		console.log(this.object);
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
		var splitDateCreated = comment.created.split(/[- :]/);
		var dateCreated = new Date(splitDateCreated[0], splitDateCreated[1]-1, splitDateCreated[2], splitDateCreated[3], splitDateCreated[4], splitDateCreated[5]);
        commentHTML.children[0].innerHTML = '<br>' + comment.username + ' (' + dateCreated.toLocaleString() + '):';
        commentHTML.appendChild(this.constructContentHTML({"type":"TEXT","text":comment.title}));
        for(var i = 0; i < comment.contents.length; i++)
            commentHTML.appendChild(this.constructContentHTML(comment.contents[i]));
        return commentHTML;
    }

    this.constructHTML();
}

  
function getLocation() {
	if (navigator.geolocation) {
		return navigator.geolocation.getCurrentPosition();
	} else {
		return "Geolocation is not supported by this browser.";
	}
}


function submitNote() 
{
	
	// check for required stuff
	

	// add location to note
	controller.updateNoteLocation(model.currentNote.noteId, model.currentNote.lat, model.currentNote.lon);
		
	// add text to note
	model.currentNote.text = document.getElementById("caption").value;
	if (model.currentNote.text != '')
		controller.addContentToNote(model.currentNote.noteId, 0, "TEXT", model.currentNote.text, '');
	
	// add image content
	if (model.currentNote.imageFile != null) {
		
		console.log (model.currentNote.imageFile);
		
		var oMyForm = new FormData();
		console.log(model.currentNote.imageFile);
		oMyForm.append("file", model.currentNote.imageFile);
		oMyForm.append("path", "3290"); // number 123456 is immediately converted to string "123456"
 
		var oReq = new XMLHttpRequest();
		oReq.open("POST", "http://dev.arisgames.org/server/services/v1/uploadHandler.php");
		oReq.onreadystatechange = function ClientSideUpdate() {
            if (oReq.readyState == 4) 
             	model.currentNote.arisImageFileName = oReq.responseText;
             	controller.addContentToNote(model.currentNote.noteId, model.currentNote.arisImageFileName, "PHOTO", '', '');
           };
		oReq.send(oMyForm);

	}
			
	
	// add tags
	if (document.getElementById("tag1").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag1").value);
	if (document.getElementById("tag2").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag2").value);
	if (document.getElementById("tag3").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag3").value);
	if (document.getElementById("tag4").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag4").value);
	if (document.getElementById("tag5").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag5").value);
	if (document.getElementById("tag6").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag6").value);
	if (document.getElementById("tag7").checked)
		controller.addTagToNote(model.currentNote.noteId, document.getElementById("tag7").value);

	// add audio content (optional)
	if (model.currentNote.audioFile != null) {
		
		
		var oMyForm = new FormData();
		console.log(model.currentNote.audioFile);
		oMyForm.append("file", model.currentNote.audioFile);
		oMyForm.append("path", "3290"); // number 123456 is immediately converted to string "123456"
 
		var oReq = new XMLHttpRequest();
		oReq.open("POST", "http://dev.arisgames.org/server/services/v1/uploadHandler.php");
		oReq.onreadystatechange = function ClientSideUpdate() {
            if (oReq.readyState == 4) 
             	model.currentNote.arisAudioFileName = oReq.responseText;
             	controller.addContentToNote(model.currentNote.noteId, model.currentNote.arisAudioFileName, "AUDIO", '', '');
           };
		oReq.send(oMyForm);

	}
	
	// hide create note view
	controller.hideCreateNoteView();
	controller.hideMapNoteView();
	
}


function cancelNote() 
{
	console.log("cancel note called");
	// add location to note
	controller.deleteNote(model.currentNote.noteId);
	
	// hide create note view
	controller.hideCreateNoteView();
	controller.hideMapNoteView();
	
}

function markerMoved(marker, map){

        var point = marker.getPosition();
        map.panTo(point);
		document.getElementById("latitude").innerHTML = "Latitude: " + point.lat();
		document.getElementById("longitude").innerHTML = "Longitude: " + point.lng();
		console.log("lat:" + point.lat());
		console.log("lon:" + point.lng());

		var geocoder = new google.maps.Geocoder();

		geocoder.geocode({latLng: point}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				if (results[0]) {
					document.getElementById("address").innerHTML = "Approximate Address: " + results[0].formatted_address;
					console.log("formatted_address:" + results[0].formatted_address);
				}
			}
		});
        model.currentNote.lat = point.lat();
		model.currentNote.lon = point.lng();

}

function handleNoGeolocation(errorFlag) {
	if (errorFlag) {
	  var content = 'Error: The Geolocation service failed.';
	} else {
	  var content = 'Error: Your browser doesn\'t support geolocation.';
	}
}


function dataURItoBlob(dataURI) {
    var binary = atob(dataURI.split(',')[1]);
    var array = [];
    for(var i = 0; i < binary.length; i++) {
        array.push(binary.charCodeAt(i));
    }
    return new Blob([new Uint8Array(array)], {type: 'image/jpeg'});
}

function unhide(div) {

	var item = document.getElementById(div);

    if (item) 
    {       
        item.className=(item.className=='hidden')?'unhidden':'hidden';  
    }
 }

  // audio functionality currently doesn't work, but should based on HTML5 spec.
  // keep an eye on https://github.com/mattdiamond/Recorderjs for updates
  function startUserMedia(stream) {
    var input = model.audio_context.createMediaStreamSource(stream);
    console.log('Media stream created.');
    
    input.connect(model.audio_context.destination);
    console.log('Input connected to audio context destination.');
    
    model.recorder = new Recorder(input);
    console.log('Recorder initialised.');
  }

  function startRecording(button) {
    model.recorder && model.recorder.record();
    button.disabled = true;
    button.nextElementSibling.disabled = false;
    console.log('Recording...');
  }

  function stopRecording(button) {
    model.recorder && model.recorder.stop();
    button.disabled = true;
    button.previousElementSibling.disabled = false;
    console.log('Stopped recording.');
    
    // create WAV download link using audio data blob
    createDownloadLink();
    
    //model.recorder.clear();
  }

  function createDownloadLink() {
    model.recorder && model.recorder.exportWAV(function(blob) {
      var url = URL.createObjectURL(blob);
      var li = document.createElement('li');
      var au = document.createElement('audio');
      var hf = document.createElement('a');
      
      au.controls = true;
      au.src = url;
      hf.href = url;
      hf.download = new Date().toISOString() + '.wav';
      hf.innerHTML = hf.download;
      li.appendChild(au);
      li.appendChild(hf);
      recordingslist.appendChild(li);
    });
  }

function NoteCreateView(html)
{
    this.html = html;
    
    controller.createNewNote();
	
    this.constructHTML = function()
    {

        //Ok. This next bit of codes is going to look ridiculous... but since the DOM has no easy way of heirarchical access, its the best I can think of.
        //I recommend opening 'index.html' and finding the xml defining 'note_view_construct' (the DOM node cloned to be this.html) as reference (vim command ':sp index.html')
		
		// Apply each element to the Date function

		/*<div id='note_create_view_construct' class='note_create_view'>
			<div id='note_create_view_image_construct' class='note_create_view_image'>Image:</div>
			<div id='note_create_view_audio_construct' class='note_create_view_audio'>Audio:</div>
			<div id='note_create_view_caption_construct' class='note_create_view_caption'>Caption:</div>
			<div id='note_create_view_location_construct' class='note_create_view_location'>Location:</div>
			<div id='note_create_view_tags_construct' class='note_create_view_tags'>Tags:</div>
			<div id='note_create_view_submit_construct' class='note_create_view_submit'>Submit:</div>
		</div>*/
		
        this.html.children[0].innerHTML = 'Image:<table><tr><th rowspan="2"><img width=200 height=200 id="imageThumbnail"></th><td><input type="file" id="imageFileInput" onchange="handleImageFileSelect(this.files)"></td></tr><tr><td><button id="showCamera" onclick="showVideo()">Camera</button></td></tr></table><br><video id="video" width="200" height="200" autoplay class="hidden"></video><button id="snap">Snap Photo</button><div hidden><canvas id="canvas" width="200" height="200"></canvas></div>';
        this.html.children[1].innerHTML = 'Audio:<br><input type="file" id="audioFileInput" onchange="handleAudioFileSelect(this.files)"><audio controls id="audioPreview"><source src="test.ogg" type="audio/ogg"><source type="audio/mpeg">Your browser does not support the audio element.</audio><button id="recordAudio" onclick="recordAudio()">Record</button> <button class="hidden" id="startRecording" onclick="startRecording(this);">start</button><button id="stopRecording" onclick="stopRecording(this);" class="hidden" disabled>stop</button><br><br>';
		this.html.children[2].innerHTML = 'Caption:<br><textarea id="caption" rows="4" cols="50"></textarea><br><br>';
		this.html.children[3].innerHTML = 'Location:<br><div id="mapCanvas" style="width:300px;height:300px;border:1px solid black;"></div><br><input type="text" name="location" id="searchTextField" style="width:300px"><br>Current location: <div id="latitude"></div><div id="longitude"></div><div id="address"></div><br><br>';
		this.html.children[4].innerHTML = 'Tags:<br><input id="tag1" value="Innovation" type="checkbox">Innovation</input><br><input id="tag2" value="Civil Disobedience" type="checkbox">Civil Disobedience</input><br><input id="tag3" value="Stories of the Past" type="checkbox">Stories of the Past</input><br><input id="tag4" value="Gratitudes" type="checkbox">Gratitudes</input><br><input id="tag4" value="Culture" type="checkbox">Culture</input><br><input id="tag6" value="Bucky\'s List" type="checkbox">Bucky\'s List</input><br><input id="tag7" value="Envisioning the Future" type="checkbox">Envisioning the Future</input><br><br>';
        this.html.children[5].innerHTML = '<br><button id="submitNote" onclick="submitNote()">Submit</button><button id="cancelNote" onclick="cancelNote()">Cancel</button>';
		
	
		document.getElementById("showCamera").addEventListener("click", showVideo());
	
		var refreshIntervalId;
		refreshIntervalId = setInterval(function () { updateMapTimer() }, 300);

function updateMapTimer() 
{
	clearInterval(refreshIntervalId);
 
 
	 var canvas = document.getElementById("canvas"),
		context = canvas.getContext("2d"),
		video = document.getElementById("video"),
		videoObj = { "video": true },
		errBack = function(error) {
			console.log("Video capture error: ", error.code); 
		};

	// Put video listeners into place
	if(navigator.getUserMedia) { // Standard
		navigator.getUserMedia(videoObj, function(stream) {
			video.src = stream;
			video.play();
		}, errBack);
	} else if(navigator.webkitGetUserMedia) { // WebKit-prefixed
		navigator.webkitGetUserMedia(videoObj, function(stream){
			video.src = window.webkitURL.createObjectURL(stream);
			video.play();
		}, errBack);
	}
	
 
   	var map;
	var mapOptions = {
          zoom: 12,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
   
    map = new google.maps.Map(document.getElementById('mapCanvas'), mapOptions);

		var marker = null;
        // Try HTML5 geolocation
        if(navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(function(position) {
            var pos = new google.maps.LatLng(position.coords.latitude,
                                             position.coords.longitude);

			marker = new google.maps.Marker({ 
			  map: map,
              position: pos,
              draggable: true
              });
              
			 google.maps.event.addListener(marker, 'dragend', function() { markerMoved(marker, map); } );

            map.setCenter(pos);
            markerMoved(marker, map);
          }, function() {
            handleNoGeolocation(true);
          });
        } else {
          // Browser doesn't support Geolocation
          handleNoGeolocation(false);
        }
        
      
     var input = document.getElementById('searchTextField');
     var autocomplete = new google.maps.places.Autocomplete(input);
 
      autocomplete.bindTo('bounds', map);
 
 
      google.maps.event.addListener(autocomplete, 'place_changed', function() {
      
        var place = autocomplete.getPlace();
        if (place.geometry.viewport) {
          map.fitBounds(place.geometry.viewport);
        } else {
          map.setCenter(place.geometry.location);
          map.setZoom(17);  // Why 17? Because it looks good.
        }
        
        
        marker.setPosition(place.geometry.location);
        markerMoved(marker, map);
        
 
        var address = '';
        if (place.address_components) {
          address = [
            (place.address_components[0] &&
             place.address_components[0].short_name || ''),
            (place.address_components[1] &&
             place.address_components[1].short_name || ''),
            (place.address_components[2] &&
             place.address_components[2].short_name || '')].join(' ');
        }
    });
    
    
    document.getElementById("snap").addEventListener("click", function() {
		
		console.log("snapPhoto");
		var canvas = document.getElementById("canvas");
		var	context = canvas.getContext("2d");
	
		video = document.getElementById("video");
		context.drawImage(video, 0, 0, 200, 200);
		var image = canvas.toDataURL('image/jpeg');
	
		var img = document.getElementById("imageThumbnail");
   	    img.src = image;
   	    console.log("image: " + image);
   	    model.currentNote.imageFile = dataURItoBlob(image); // it looks like there will eventually be a method canvas.toBlob() method for HTML5 but it is not implemented yet in most browsers as of April 2013
   	    console.log("imageFile: " + model.currentNote.imageFile);
   	    }, false );
      
	}
   
        
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
		var splitDateCreated = comment.created.split(/[- :]/);
		var dateCreated = new Date(splitDateCreated[0], splitDateCreated[1]-1, splitDateCreated[2], splitDateCreated[3], splitDateCreated[4], splitDateCreated[5]);
        commentHTML.children[0].innerHTML = '<br>' + comment.username + ' (' + dateCreated.toLocaleString() + '):';
        commentHTML.appendChild(this.constructContentHTML({"type":"TEXT","text":comment.title}));
        for(var i = 0; i < comment.contents.length; i++)
            commentHTML.appendChild(this.constructContentHTML(comment.contents[i]));
        return commentHTML;
    }


    this.constructHTML();

}


