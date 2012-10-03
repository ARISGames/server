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
        self.callback(self);
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
        if(self.odd)
        {
            if(self.selected) self.html.style.backgroundColor = '#AAAAFF';
            else self.html.style.backgroundColor = '#AAAAAA';
        }
        else
        {
            if(self.selected) self.html.style.backgroundColor = '#CCCCFF';
            else self.html.style.backgroundColor = '#CCCCCC';
        }
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
    this.clicked = function()
    {
        self.callback(self, !self.selected);
        if(self.selected)self.deselect();
        else self.select();
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
        if(self.odd)
        {
            if(self.selected) self.html.style.backgroundColor = '#AAAAFF';
            else self.html.style.backgroundColor = '#AAAAAA';
        }
        else
        {
            if(self.selected) self.html.style.backgroundColor = '#CCCCFF';
            else self.html.style.backgroundColor = '#CCCCCC';
        }
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
        this.html.children[0].children[2].innerHTML = this.object.username;

        //{"media_id":"115","type":"PHOTO","text":"","game_id":"2556","title":"September 11th 2012 02:08:50 PM","file_name":"aris9ff36f34bbc4575767e96ec005b22870.jpg","media_url":"http://dev.arisgames.org/server/gamedata/2556/aris9ff36f34bbc4575767e96ec005b22870.jpg"}
        for(var i = 0; i < this.object.contents.length; i++)
        {
            var content = document.getElementById('note_content_cell_construct').cloneNode(true);
            switch(this.object.contents[i].type)
            {
                case 'TEXT':
                    content.innerHTML = this.object.contents[i].text;
                    break;
                case 'PHOTO':
                    content.innerHTML = '<img src="'+this.object.contents[i].media_url+'" />';
                    break;
                case 'AUDIO':
                    content.innerHTML = this.object.contents[i].media_url;
                    break;
                case 'VIDEO':
                    content.innerHTML = this.object.contents[i].media_url;
                    break;
            }
            this.html.children[2].appendChild(content);
        }
    }

    this.constructHTML();
}
