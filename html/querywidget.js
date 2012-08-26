/**
 * Class QueryWidget
 *
 * Provide methods to handle back end tasks.
 * @copyright  Winans Creative 2012
 * @author     Blair Winans <blair@winanscreative.com>
 * @package    Backend
 */
 
var QueryWidget = new Class({

	Implements: Options,
	
	//Options
	options: {
		container: null,
		rowcontainer: 'div.queryrow',
		buttoncontainer: 'div.querybuttons',
		querycontainer: 'div.query',
		dragcontainer: 'div.querybody',
		dragelements: 'a.drag',
	
		initCallback: (function(){}),

    },
	
	//Properties
	container: null,
	rows: [],
	sortables: [],
	grouplevels: [],
	
	initialize: function(options)
	{
		this.setOptions(options);
		
		this.container = document.id(this.options.container);
		this.rows = this.container.getElements(this.options.rowcontainer);
				
		//Set up Sortables
		this.refreshSortables();
		
		// Store the grouping levels
		this.initializeGroupLevels();
		
		//Callback
		this.options.initCallback();
	},
	
	
	/**
	 * Set up the sortables
	 *
	 * @return	void
	 */
	refreshSortables: function()
	{
		this.sortables = new Sortables('#'+this.options.container+' '+this.options.dragcontainer, {
			constrain:true,
			handle: this.options.dragelements,
			onStart: function(el,clone) { el.addClass('dragging') },
			onComplete: function(el) { this.updateIndices(); el.removeClass('dragging'); }.bind(this)
		});
		
		this.container.getElements('a.rup, a.rdown').each(function(el){el.addClass('invisible')});
		this.container.getElements('a.drag').each(function(el){
			el.removeClass('invisible').addEvents({
				mousedown: function(ev){ ev.target.getParent(this.options.rowcontainer).addClass('dragging') }.bind(this),
				mouseup: function(ev){ ev.target.getParent(this.options.rowcontainer).removeClass('dragging') }.bind(this)
			});
		}.bind(this));
	},
	
	
	/**
	 * Store the group level of each row
	 *
	 * @return	void
	 */
	initializeGroupLevels: function()
	{
		this.grouplevel = 1;
		
		this.rows.each( function(row, index)
		{
			if (row.hasClass('group_start'))
				this.grouplevel++;
			else if (row.hasClass('group_stop'))
				this.grouplevel--;
				
			// Store the group level for the current element (using its index)
			this.grouplevels[index] = this.grouplevel;
		
		}.bind(this));
	},
	
	
	/**
	 * Handle any commands sent from buttons
	 *
	 * @param	Element
	 * @param	string
	 * @param	int
	 * @return	void
	 */
	doCommand: function(el, command, index)
	{
		switch (command)
		{
			case 'rnew':
				this.rowNew(el,index);
				break;
				
			case 'rcopy':
				this.rowCopy(el,index);
				break;
				
			case 'rup':
				this.rowUp(el,index);
				break;
				
			case 'rdown':
				this.rowDown(el,index);
				break;
				
			case 'rdelete':
				this.rowDelete(el,index);
				break;
				
			case 'rindent':
				this.rowIndent(el,index);
				break;
		}
	
	},
	
	/**
	 * Add a new row after a given index
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	rowNew: function(el, index)
	{
		var currRow = this.rows[index];
		var newRow = currRow.clone().inject(currRow,'after');
		
		//Now clear inputs/selects
		newRow.getElements('input').each( function(el){ el.value = ''; });
		newRow.getElements('option').each( function(el){ el.selected = false; });
		
		//Update Chosen
		this.updateChosen(newRow);
		//Update indices
		this.updateIndices();
	},
	
	
	/**
	 * Duplicate a row after a given index
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	rowCopy: function(el, index)
	{
		var currRow = this.rows[index];
		var newRow = currRow.clone().inject(currRow,'after');
		
		//Update Chosen
		this.updateChosen(newRow);
		//Update indices
		this.updateIndices();
	},
	
	
	/**
	 * Add a grouping row before and after a given index
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	rowIndent: function(el, index)
	{
		var currRow = this.rows[index];
		var beforeRow = currRow.clone().addClass('group_start').inject(currRow,'before');
		var afterRow = currRow.clone().addClass('group_stop').inject(currRow,'after');
		
		//Update indices
		this.updateIndices();
	},
	
	
	/**
	 * Move a row up in the index
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	rowUp: function(el, index)
	{
		var aboveRow = this.rows[index-1];
		var currRow = this.rows[index];
		
		if(aboveRow)
		{
			aboveRow.inject(currRow,'after');
			//Reset indices
			this.updateIndices();
		}
	},
	
	
	/**
	 * Move a row down in the index
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	rowDown: function(el, index)
	{
		var belowRow = this.rows[index+1];
		var currRow= this.rows[index];
		
		if(belowRow)
		{
			currRow.inject(belowRow,'after');
			//Reset indices
			this.updateIndices();
		}
	},
	
	
	/**
	 * Delete a row
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	rowDelete: function(el, index)
	{
		if (this.rows[index] && this.rows.length > 1)
		{
			if (this.rows[index].hasClass('group_start') || this.rows[index].hasClass('group_stop'))
				this.deleteRelatedGroupRow(index);
			
			this.rows[index].dispose();
			this.updateIndices();
		}
	},
	
	
	/**
	 * Delete a grouping row's related row so the groups stay together properly
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	deleteRelatedGroupRow: function(index)
	{
		var grouplevel = this.grouplevels[index];
		
		// Delete a group "start" row's related "stop" row
		if (this.rows[index].hasClass('group_start'))
		{
			for (var i = index+1; i < this.rows.length; i++)
			{
				if (this.rows[i].hasClass('group_stop') && this.grouplevels[i] == grouplevel)
				{
					this.rows[i].dispose();
					break;
				}
			}
		}
		
		// Delete a group "stop" row's related "start" row
		else
		{
			for (var i = index-1; i >= 0; i--)
			{
				if (this.rows[i].hasClass('group_start') && this.grouplevels[i] == grouplevel)
				{
					this.rows[i].dispose();
					break;
				}
			}
		}
	},
	
	
	/**
	 * Update Chosen selects
	 *
	 * @param	Element
	 * @return	void
	 */
	updateChosen: function(row)
	{
		if(Chosen)
		{
			//First we need to delete the cloned chosen divs
			row.getElements('div.chzn-container').each(function(chzn){ chzn.dispose(); });
			row.getElements('select.tl_chosen').erase('id').chosen().fireEvent("change");
		}
	},
	
	
	/**
	 * Reset the index values of all input/select fields and buttons in a row
	 *
	 * @param	Element
	 * @return	void
	 */
	updateIndices: function()
	{
		//Reset the row index
		this.rows = this.container.getElements(this.options.rowcontainer);
		
		this.grouplevel = 1;
		
		this.rows.each( function(row, index)
		{
			if (row.hasClass('group_start'))
			{
				this.grouplevel++;
				this.reformatGroupingRow(row, 'group_start');
			}
			
			this.setElementRowLevel(row);
			this.showHideAndOr(row, index);
			
			// Store the group level for the current element (using its index)
			this.grouplevels[index] = this.grouplevel;
			
			//Inputs
			var inputs = row.getElements('input,select');
			inputs.each(function(input)
			{
				if(input.get('name'))
				{
					input.name = input.name.replace(/\[[0-9]+\]/ig, '[' + index + ']');
				}
			});
			
			//Buttons
			var buttons = row.getElements(this.options.buttoncontainer+' a');
			buttons.each(function(button)
			{
				//Reset HREF
				if(button.get('href'))
				{
					button.href = button.href.replace(/\&rid=\d*[0-9]/ig, '&rid=' + index);
				}
				//Reset ONCLICK
				if(button.get('onclick'))
				{
					//Remove all other events
					button.removeEvents();
					//Need to do it this way or else it gets evaluated as an event
					button.attributes['onclick'].value = button.attributes['onclick'].value.replace(/\d*[0-9]\);/ig, index+');');
				}
			}.bind(this));
			
			if (row.hasClass('group_stop'))
			{
				this.reformatGroupingRow(row, 'group_stop');
				this.grouplevel--;
			}
			
			//Set/hide AND/ORs - First show all
			//var andOr = row.getElement('div.andor').addClass('invisible'); //Hiding these ALWAYS for now until we can figure out grouping
			/*var endAndOr = row.getElement('div.end_andor').removeClass('invisible');
			
			if(index==(this.rows.length-1))
			{
				endAndOr.addClass('invisible');
			}*/
			
		}.bind(this));
		
		this.refreshSortables();
		
	},
	
	
	/**
	 * Set up a grouping row so it 
	 *
	 * @param	Element
	 * @param	string
	 * @return	void
	 */
	reformatGroupingRow: function(el, startstop)
	{
		var groupvalEls = el.getElements('input');	// Elements that will have their values set to the start/stop value
		var hideEls = el.getElements('.rnew, .rcopy, .rindent, .querytable');	// Elements that will have to be hidden
		
		// Set values to the group start/stop
		if (groupvalEls.length > 0)
		{
			groupvalEls.each(function(groupvalEl)
			{
				groupvalEl.set('value', '|'+startstop+'|');
				
			}.bind(this));
		}
		
		// Hide elements
		if (hideEls.length > 0)
		{
			hideEls.each(function(hideEl)
			{
				hideEl.setStyle('display', 'none');
			});
		}
	},
	
	
	/**
	 * Set or update the row's level
	 *
	 * @param	Element
	 * @return	void
	 */
	setElementRowLevel: function(el)
	{
		// Remove the previous/next level if necessary
		if (el.hasClass('level_'+(this.grouplevel - 1)))
			el.removeClass('level_'+(this.grouplevel - 1));
		if (el.hasClass('level_'+(this.grouplevel + 1)))
			el.removeClass('level_'+(this.grouplevel + 1));
		
		// Add the current level
		if (!el.hasClass('level_'+(this.grouplevel)))
			el.addClass('level_' + this.grouplevel);
		
		var level = el.getElement('.grouplevel span');
		
		if (level)
			level.set('text', this.grouplevel)
	},
	
	
	/**
	 * Show or hide the row's AND/OR
	 *
	 * @param	Element
	 * @param	int
	 * @return	void
	 */
	showHideAndOr: function(el, index)
	{
		var elAndOr = el.getElement('div.andor');
		
		if (!elAndOr)
			return;
		
		var blnAfterGroupStart = false;
		
		// Get the previous row's value to see if it was a group "start"
		if (index != 0)
		{
			var elValue = this.rows[index-1].getElement('input.value');
			
			if (elValue && elValue.get('value') == '|group_start|')
				blnAfterGroupStart = true;
		}
		
		// Hide the AND/OR if it's the first row, it's a group "stop" row, or it's the first row after a "start" row
		if (index == 0 || el.hasClass('group_stop') || blnAfterGroupStart)
		{
			if (!elAndOr.hasClass('invisible'))
				elAndOr.addClass('invisible');
		}
		else
		{
			if (elAndOr.hasClass('invisible'))
				elAndOr.removeClass('invisible');
		}
	}
	
	
});
 