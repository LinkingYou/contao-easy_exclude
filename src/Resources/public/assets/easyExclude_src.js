/**
 * EasyExclude Extension for Contao Open Source CMS
 *
 * @author     Frank Müller <frank.mueller@linking-you.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

const REQUEST_TOKEN = Contao.request_token;
const CONTAO_LOADING = 'Loading data';

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

	initialize: function(options) {
		var self = this;
		this.setOptions(options);

		// add the onchange event to the usergroup dropdown
		$('easyExclude_usergroup').addEvent('change', function() {
			// get all fields
			self.fields = $$('div.easyExclude');

			self.usergroup = this.get('value');
			if(self.usergroup > 0) {
				self.cleanTheScreen();
				self.addCheckboxes();
				self.updateStates();
			}
			else {
				self.cleanTheScreen();
			}
		});


		// how great we have a hook to register functions upon the event that occurs whenever a subpalette is being loaded!!
		window.addEvent('subpalette', function() {
			$('easyExclude_usergroup').fireEvent('change');
		});
	},


	updateStates: function() {
		var self = this;

		new Request.JSON({
			url: window.location.href,
			data: 'isAjax=1&action=easyExcludeGetFieldRights&usergroup=' + this.usergroup + '&table=' + this.options.table + '&REQUEST_TOKEN=' + REQUEST_TOKEN,
			onRequest: function() {
				AjaxRequest.displayBox(CONTAO_LOADING + ' …');
			},
			onComplete: function() {
				AjaxRequest.hideBox();
			},
			onFailure: function() {
				alert('failed');
			},
			onSuccess: function(response, responseText) {
				if(response.content) {
					response.content.each(function(allowedField) {
						// set checkboxes true
						var key = 'easyExclude_' + allowedField + '_' + self.usergroup;

						if($(key)) {
							$(key).checked = true;
						}
					});
				}

				// update the background color
				self.changeBackgroundColor();
			}
		}).send();
	},


	addCheckboxes: function() {
		var self = this;

		this.fields.each(function(field) {
			var key = 'easyExclude_' + self.filterForFieldName(field.get('class')) + '_' + self.usergroup;
			var cbx = null;

			// load the disposed element if there is already one, otherwise generate it the first time
			if(self.checkboxes[key]) {
				cbx = self.checkboxes[key];
			}
			else {
				cbx = new Element('input', {
					type: 'checkbox',
					name: key,
					id: key,
					'class': 'tl_checkbox easyExcludeCheckbox',
					events: {
						change: function() {
							self.saveChange(field, this);
						}
					}
				});
			}

			// and inject it into the field
			cbx.inject(field, 'top');
		});
	},


	saveChange: function(field, cbx) {
		var self = this;
		var state = (cbx.checked) ? 1 : 0;

		var objRequest = new Request.JSON({
			url: window.location.href,
			data: 'isAjax=1&action=easyExcludeSaveChange&usergroup=' + this.usergroup + '&field=' + self.filterForFieldName(field.get('class')) + '&table=' + this.options.table + '&state=' + state + '&REQUEST_TOKEN=' + REQUEST_TOKEN,
			onRequest: function() {
				AjaxRequest.displayBox(CONTAO_LOADING + ' …');
			},
			onComplete: function() {
				AjaxRequest.hideBox();
			},
			onFailure: function() {
				alert('failed');
			},
			onSuccess: function(responseJSON, responseText) {
				// update the background color
				self.changeBackgroundColor();
			}
		}).send();
	},


	filterForFieldName: function(classString) {
		var matches = /easyExcludeFN_([\S]+)/i.exec(classString);
		return matches[1];
	},


	changeBackgroundColor: function() {
		$$('input.easyExcludeCheckbox').each(function(cbx) {
			var field = cbx.getParent('div.easyExclude');
			if (cbx.checked) {
				field.setStyle('background', '#E4FFD4');
			}
			else {
				field.setStyle('background', '#EBEBEB');
			}
		});
	},


	cleanTheScreen: function() {
		// dispose checkboxes
		$$('input.easyExcludeCheckbox').each(function(cbx) {
			var key = cbx.get('id');
			// add it to the object literal
			this.checkboxes[key] = cbx.dispose();
		}.bind(this));

		// update the background color
		this.fields.each(function(field) {
			field.setStyle('background', '');
		});
	}
});
