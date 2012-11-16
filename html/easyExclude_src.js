/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  terminal42 gmbh
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 */


/**
 * Class EasyExclude
 */
var EasyExclude = new Class({
	Implements: [Options],
	options:
	{
		table: ''
	},
	
	//Properties
	fields: null,
	usergroup: 0,
	checkboxes: {},
	
	/**
	 * Initialize
	 */
	initialize: function(options)
	{
	    var self = this;
		this.setOptions(options);
		
		// add the onchange event to the usergroup dropdown
		$('easyExclude_usergroup').addEvent('change', function()
		{
			// get all fields
		  self.fields = $$('div.easyExclude');
		  
		    self.usergroup = this.get('value');
		    if(self.usergroup > 0)
		    {
			   self.cleanTheScreen();
			   self.addCheckboxes();
			 self.updateStates(); 
		    }
		    else
		    {
			 self.cleanTheScreen();			 
		    }
		});
		
		// how great we have a hook to register functions upon the event that occurs whenever a subpalette is being loaded!!
	   window.addEvent('subpalette', function()
	   {
		  $('easyExclude_usergroup').fireEvent('change');
	   });
	},

	/**
	 * Get all fields
	 */
	updateStates: function() 
	{
	    var self = this;

	   new Request.Contao(
	   {
		  url: window.location.href,
		  data: 'isAjax=1&action=easyExcludeGetFieldRights&usergroup=' + this.usergroup + '&table=' + this.options.table + '&REQUEST_TOKEN=' + REQUEST_TOKEN,
		  onRequest: function()
		  {
			 AjaxRequest.displayBox('Loading data …');
		  },
		  onComplete: function()
		  {
			 AjaxRequest.hideBox();
		  },
		  onFailure: function()
		  {
			 alert('failed');
		  },
		  onSuccess: function(response, responseText)
		  {	
			 if(response)
			 {
			 	// unfortunately Request.Mixed doesn't decode JSON automatically
					var decoded = JSON.decode(response);
					response = (decoded) ? decoded : response;

				response.each(function(allowedField)
				{
				    // set checkboxes true
				    var key = 'easyExclude_' + allowedField + '_' + self.usergroup;

				    if($(key))
				    {
					   $(key).checked = true;
				    }
				});
			 }
			 
			 // update the background color
			 self.changeBackgroundColor();
		  }
	   }).send();	 
	},
	
	
    /**
	* Inject checkboxes 
	*/
    addCheckboxes: function()
    {
	   var self = this;

	   this.fields.each(function(field)
	   {
		  var key = 'easyExclude_' + self.filterForFieldName(field.get('class')) + '_' + self.usergroup;
		  var cbx = null;
		  
		  // load the disposed element if there is already one, otherwise generate it the first time
		  if(self.checkboxes[key])
		  {
			 cbx = self.checkboxes[key];
		  }
		  else
		  {
			 cbx = new Element('input', {
				type: 'checkbox',
				name: key,
				id: key,
				'class': 'tl_checkbox easyExcludeCheckbox',
				events:
				{
				    change: function()
				    {
					   self.saveChange(field, this);
				    }
				}
			 });		    
		  }

		  // and inject it into the field 
		  cbx.inject(field, 'top');
	   });
    },
	 
	 
    saveChange: function(field, cbx)
    {
	   var self = this;
	   var state = (cbx.checked) ? 1 : 0;
	   
	   var objRequest = new Request.Contao(
	   {
		  url: window.location.href,
		  data: 'isAjax=1&action=easyExcludeSaveChange&usergroup=' + this.usergroup + '&field=' + self.filterForFieldName(field.get('class')) + '&table=' + this.options.table + '&state=' + state + '&REQUEST_TOKEN=' + REQUEST_TOKEN,
		  onRequest: function()
		  {
			 AjaxRequest.displayBox('Loading data …');
		  },
		  onComplete: function()
		  {
			 AjaxRequest.hideBox();
		  },
		  onFailure: function()
		  {
			 alert('failed');
		  },
		  onSuccess: function(responseJSON, responseText)
		  {
			 // update the background color
			 self.changeBackgroundColor();
		  }
	   }).send();
	 },
	 
	 
	 /**
	  * Filters a class string for the field name
	  * @param string
	  * @return string
	  */
	  filterForFieldName: function(classString)
	  {
		 var matches = /easyExcludeFN_([\S]+)/i.exec(classString);
		 return matches[1];
	  },
	  
	  
	  /**
	   * Change bg color according to the checkbox state
	   */
	   changeBackgroundColor: function()
	   {
		  $$('input.easyExcludeCheckbox').each(function(cbx)
		  {
			 var field = cbx.getParent('div.easyExclude');
			 if(cbx.checked)
			 {
				field.setStyle('background', '#E4FFD4');
			 }
			 else
			 {
				field.setStyle('background', '#EBEBEB');
			 }			 
		  });
	   },
	   
	   
	   /**
	    * Clean the screen
	    */
	    cleanTheScreen: function()
	    {
		  // dispose checkboxes
		  $$('input.easyExcludeCheckbox').each(function(cbx)
		  {
			 var key = cbx.get('id');			 
			 // add it to the object literal
			 this.checkboxes[key] = cbx.dispose();
		  }.bind(this));

		  // update the background color
		  this.fields.each(function(field)
		  {
			 field.setStyle('background', '');
		  });   
	    }
});
