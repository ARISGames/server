function Model()
{
    this.gameJSONText = '';
    this.gameData = {};
    this.backpacks = [];

    //All notes in order they were received 
    this.notes = [];
    this.addNote = function(note)
    {
        this.notes[this.notes.length] = note;
    }
    //All notes (no order)
    this.mapNotes = [];
    this.mapMarkers = [];
    this.addMapNote = function(mapNote)
    {
        mapNote.geoloc = new google.maps.LatLng(mapNote.lat, mapNote.lon);
        this.mapNotes[this.mapNotes.length] = mapNote;
    }
    //All notes ordered alphabetically by owner name
    this.contributorNotes = [];
    this.addContributorNote = function(contributorNote)
    {
        for(var i = 0; i < this.contributorNotes.length; i++)
        {
            if(this.contributorNotes[i].username.toLowerCase() >= contributorNote.username.toLowerCase())
            {
                this.contributorNotes.splice(i, 0, contributorNote);
                return;
            }
        }
        this.contributorNotes[this.contributorNotes.length] = contributorNote;
    }

    //All notes ordered alphabetically by first alphabetical tag
    this.tagNotes = [];
    this.addTagNote = function(tagNote)
    {
        for(var i = 0; i < this.tagNotes.length; i++)
        {
            if(this.tagNotes[i].tagString.toLowerCase() >= tagNote.tagString.toLowerCase())
            {
                this.tagNotes.splice(i, 0, tagNote);
                return;
            }
        }
        this.tagNotes[this.tagNotes.length] = tagNote;
    }

    //All notes ordered by total amount of likes on self/comments
    this.popularNotes = [];
    this.addPopularNote = function(popularNote)
    {
        for(var i = 0; i < this.popularNotes.length; i++)
        {
            if(this.popularNotes[i].popularity <= popularNote.popularity)
            {
                this.popularNotes.splice(i, 0, popularNote);
                return;
            }
        }
        this.popularNotes[this.popularNotes.length] = popularNote;
    }

    //List of all contributors to any note in game (whether owner of note or just comment) ordered alphabetically
    this.contributors = [];
    this.contributorMapCells = [];
    this.contributorListCells = [];
    this.addContributor = function(contributor)
    {
        for(var i = 0; i < this.contributors.length; i++)
        {
            if(this.contributors[i] == contributor) return;
            if(this.contributors[i].toLowerCase() > contributor.toLowerCase())
            {
                this.contributors.splice(i, 0, contributor);
                return;
            }
        }
        this.contributors[this.contributors.length] = contributor;
    }
    this.mapContributorSelected = function(contributor)
    {
        for(var i = 0; i < this.contributorMapCells.length; i++)
        {
            if(this.contributorMapCells[i].object == contributor)
                return this.contributorMapCells[i].selected;
        }
        return false;
    }
    this.listContributorSelected = function(contributor)
    {
        for(var i = 0; i < this.contributorListCells.length; i++)
        {
            if(this.contributorListCells[i].object == contributor)
                return this.contributorListCells[i].selected;
        }
        return false;
    }

    //List of all tags in any note in game ordered alphabetically 
    this.tags = [];
    this.tagMapCells = [];
    this.tagListCells = [];
    this.addTag = function(tag)
    {
        for(var i = 0; i < this.tags.length; i++)
        {
            if(this.tags[i] == tag) return;
            if(this.tags[i].toLowerCase() > tag.toLowerCase())
            {
                this.tags.splice(i, 0, tag);
                return;
            }
        }
        this.tags[this.tags.length] = tag;
    }
    this.mapTagsSelected = function(tags)
    {
        //n^2! oh noes!
        for(var i = 0; i < this.tagMapCells.length; i++)
        {
            for(var j = 0; j < tags.length; j++)
            {
                if(this.tagMapCells[i].object == tags[j].tag && this.tagMapCells[i].selected)
                    return true;
            }
        }
        return false;
    }
    this.listTagsSelected = function(tags)
    {
        //n^2! oh noes!
        for(var i = 0; i < this.tagListCells.length; i++)
        {
            for(var j = 0; j < tags.length; j++)
            {
                if(this.tagListCells[i].object == tags[j].tag && this.tagListCells[i].selected)
                    return true;
            }
        }
        return false;
    }

	this.numberOfNotesForTag = function(tag)
    {
		var notesForTag = 0;
		for(var i = 0; i < this.notes.length; i++)
        {
			for (var j = 0; j < this.notes[i].tags.length; j++) 
			{		
				if (this.notes[i].tags[j].tag.toLowerCase() == tag.toLowerCase())
					notesForTag ++;
			}
		}
        return notesForTag;
    }
	
	
	this.numberOfNotesForContributor = function(contributor)
    {
		var notesForContributor = 0;
		for(var i = 0; i < this.notes.length; i++)
        {
			if (this.notes[i].username.toLowerCase() == contributor.toLowerCase())
				notesForContributor ++;
		}
        return notesForContributor;
    }



    this.views = new function Views()
    {
        //Layout/Sort Button Containers
        this.layoutSelector = document.getElementById('header_selector_layout');
        this.sortSelector = document.getElementById('header_selector_sort');

        //Layout/Sort Buttons 
        this.mapLayoutButton = new SingleSelectionButton(document.getElementById('header_selector_layout_map'), controller.setLayout);
        this.listLayoutButton = new SingleSelectionButton(document.getElementById('header_selector_layout_list'), controller.setLayout);
        this.contributorSortButton = new SingleSelectionButton(document.getElementById('header_selector_sort_contributor'), controller.setSort);
        this.tagSortButton = new SingleSelectionButton(document.getElementById('header_selector_sort_tag'), controller.setSort);
        this.popularitySortButton = new SingleSelectionButton(document.getElementById('header_selector_sort_popularity'), controller.setSort);

        //Layouts
        this.mapLayout = document.getElementById('map_layout');
        this.listLayout = document.getElementById('list_layout');

        //Side Panel Selectors (& containers)
        this.contributorMapFilterSelector = document.getElementById('contributor_map_filter_selector');
        this.contributorMapFilterSelectorContainer = document.getElementById('contributor_map_filter_selector_container');
        this.tagMapFilterSelector = document.getElementById('tag_map_filter_selector');
        this.tagMapFilterSelectorContainer = document.getElementById('tag_map_filter_selector_container');
        this.contributorListFilterSelector = document.getElementById('contributor_list_filter_selector');
        this.contributorListFilterSelectorContainer = document.getElementById('contributor_list_filter_selector_container');
        this.tagListFilterSelector = document.getElementById('tag_list_filter_selector');
        this.tagListFilterSelectorContainer = document.getElementById('tag_list_filter_selector_container');
        this.noteListSelector = document.getElementById('note_list_selector');
        this.noteListSelectorContainer = document.getElementById('note_list_selector_container');
        
        //Side Panel Cells
        //  Map
        //      Contributor
        this.constructContributorMapFilterSelectorCell = document.getElementById('contributor_map_filter_selector_cell_construct');
        this.defaultContributorMapFilterSelectorCell = document.getElementById('contributor_map_filter_selector_cell_default');
        this.helperContributorMapFilterSelectorCell = document.getElementById('contributor_map_filter_selector_cell_helper');
        this.helperButtonContributorMapFilterSelectorCellSelectAll = new ActionButton(document.getElementById('contributor_map_filter_selector_cell_helper_select_all'), controller.selectAllMapContributors);
        this.helperButtonContributorMapFilterSelectorCellDeselectAll = new ActionButton(document.getElementById('contributor_map_filter_selector_cell_helper_select_none'), controller.deselectAllMapContributors);
        //      Tag
        this.constructTagMapFilterSelectorCell = document.getElementById('tag_map_filter_selector_cell_construct');
        this.defaultTagMapFilterSelectorCell = document.getElementById('tag_map_filter_selector_cell_default');
        this.helperTagMapFilterSelectorCell = document.getElementById('tag_map_filter_selector_cell_helper');
        this.helperButtonTagMapFilterSelectorCellSelectAll = new ActionButton(document.getElementById('tag_map_filter_selector_cell_helper_select_all'), controller.selectAllMapTags);
        this.helperButtonTagMapFilterSelectorCellDeselectAll = new ActionButton(document.getElementById('tag_map_filter_selector_cell_helper_select_none'), controller.deselectAllMapTags);
        //  List
        //      Contributor
        this.constructContributorListFilterSelectorCell = document.getElementById('contributor_list_filter_selector_cell_construct');
        this.defaultContributorListFilterSelectorCell = document.getElementById('contributor_list_filter_selector_cell_default');
        this.helperContributorListFilterSelectorCell = document.getElementById('contributor_list_filter_selector_cell_helper');
        this.helperButtonContributorListFilterSelectorCellSelectAll = new ActionButton(document.getElementById('contributor_list_filter_selector_cell_helper_select_all'), controller.selectAllListContributors);
        this.helperButtonContributorListFilterSelectorCellDeselectAll = new ActionButton(document.getElementById('contributor_list_filter_selector_cell_helper_select_none'), controller.deselectAllListContributors);
        //      Tag
        this.constructTagListFilterSelectorCell = document.getElementById('tag_list_filter_selector_cell_construct');
        this.defaultTagListFilterSelectorCell = document.getElementById('tag_list_filter_selector_cell_default');
        this.helperTagListFilterSelectorCell = document.getElementById('tag_list_filter_selector_cell_helper');
        this.helperButtonTagListFilterSelectorCellSelectAll = new ActionButton(document.getElementById('tag_list_filter_selector_cell_helper_select_all'), controller.selectAllListTags);
        this.helperButtonTagListFilterSelectorCellDeselectAll = new ActionButton(document.getElementById('tag_list_filter_selector_cell_helper_select_none'), controller.deselectAllListTags);
        //      Note
        this.contributorNoteCells = [];
        this.tagNoteCells = [];
        this.popularNoteCells = [];
        this.constructNoteListSelectorCell = document.getElementById('note_list_selector_cell_construct');
        this.defaultNoteListSelectorCell = document.getElementById('note_list_selector_cell_default');

        //Content
        this.mapNoteViewContainer = document.getElementById('map_note_view_container');
        this.mapNoteViewContainer.addEventListener('click', function(e) { e.stopPropagation(); });
        this.mapNoteViewCloseButton = new ActionButton(document.getElementById('map_note_view_close_button'), controller.hideMapNoteView);
        this.listNoteViewContainer = document.getElementById('list_note_view_container');
        this.constructNoteView = document.getElementById('note_view_construct');
        this.defaultNoteView = document.getElementById('note_view_default');
        this.noteView = new NoteView(this.defaultNoteView, null);

        //Map
        this.map = document.getElementById('map');
        var centerLoc = new google.maps.LatLng(0, 0);
        var myOptions = { zoom:5, center:centerLoc, mapTypeId:google.maps.MapTypeId.ROADMAP };
        this.gmap = new google.maps.Map(this.map, myOptions);
    };

    document.addEventListener('keydown', function(e) { if(e.keyIdentifier == 'Up' || e.keyIdentifier == 'Down') { controller.displayNextNote(e.keyIdentifier); e.stopPropagation(); e.preventDefault(); } }, false);
}
