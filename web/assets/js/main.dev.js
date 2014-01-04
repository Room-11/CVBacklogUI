/*jslint white: true, plusplus: true, browser: true */

// Namespace declaration
var CvPlsBacklog = {};

(function() { // class DataTableHandler
	"use strict";

	// Link click event callback
	function linkClick(ev)
	{
		ev.preventDefault();
		window.open(ev.currentTarget.href, this.linkTarget);
	}

	function refreshTable()
	{
		/*
		  code to refresh table goes here
		*/

		// Assuming the table refresh code simply replaces all the <tr> elements in the <tbody>,
		// this will sort the counter etc out afterwards. However, if you leave any existing rows
		// in the table it will need some more intelligent logic to prevent the link click event
		// handlers from being attached multiple times
		this.init();

		// Schedule the next auto-refresh
		this.refreshTimer = setTimeout(refreshTable.bind(this), this.autoRefreshInterval);
	}

	// Constructor
	CvPlsBacklog.DataTableHandler = function(element, voteCounter, autoRefreshInterval)
	{
		this.element = element;
		this.voteCounter = voteCounter;
		this.autoRefreshInterval = autoRefreshInterval !== undefined
			? Number(autoRefreshInterval) * 1000
			: 60000;
	};

	CvPlsBacklog.DataTableHandler.prototype.element             = null;
	CvPlsBacklog.DataTableHandler.prototype.voteCounter         = null;
	CvPlsBacklog.DataTableHandler.prototype.autoRefreshInterval = null;

	CvPlsBacklog.DataTableHandler.prototype.rowCounts           = null;
	CvPlsBacklog.DataTableHandler.prototype.linkTarget          = '_self';
	CvPlsBacklog.DataTableHandler.prototype.refreshTimer        = null;

	CvPlsBacklog.DataTableHandler.prototype.init = function()
	{
		var rows, links, i, j, rowCount, linkCount, linkClickBound;

		this.rowCounts = {};

		linkClickBound = linkClick.bind(this);

		rows = this.element.querySelectorAll('tbody tr:not(.error-message)');
		for (i = 0, rowCount = rows.length; i < rowCount; i++) {
			if (this.rowCounts[rows[i].className] === undefined) {
				this.rowCounts[rows[i].className] = 0;
			}
			this.rowCounts[rows[i].className]++;

			links = rows[i].querySelectorAll('a');
			for (j = 0, linkCount = links.length; j < linkCount; j++) {
				links[j].addEventListener('click', linkClickBound, false);
			}
		}

		this.voteCounter.setValue(rowCount);
	};

	CvPlsBacklog.DataTableHandler.prototype.hideVoteType = function(voteType)
	{
		if (!this.element.classList.contains('hide-' + voteType)) {
			this.element.classList.add('hide-' + voteType);

			this.voteCounter.subtract(this.rowCounts[voteType]);
		}
	};

	CvPlsBacklog.DataTableHandler.prototype.showVoteType = function(voteType)
	{
		if (this.element.classList.contains('hide-' + voteType)) {
			this.element.classList.remove('hide-' + voteType);

			this.voteCounter.add(this.rowCounts[voteType]);
		}
	};

	CvPlsBacklog.DataTableHandler.prototype.setOpenInTabs = function(openInTabs)
	{
		this.linkTarget = openInTabs ? '_blank' : '_self';
	};

	CvPlsBacklog.DataTableHandler.prototype.setAutoRefresh = function(autoRefresh)
	{
		if (autoRefresh && this.refreshTimer === null) {
			this.refreshTimer = setTimeout(refreshTable.bind(this), this.autoRefreshInterval);
		} else if (this.refreshTimer !== null) {
			clearTimeout(this.refreshTimer);
			this.refreshTimer = null;
		}
	};
}());

(function() { // class QuestionCounterHandler
	"use strict";

	// Constructor
	CvPlsBacklog.QuestionCounterHandler = function(el)
	{
		this.textNode = el.firstChild;
	};

	CvPlsBacklog.QuestionCounterHandler.prototype.textNode = null;

	CvPlsBacklog.QuestionCounterHandler.prototype.setValue = function(newValue)
	{
		this.textNode.data = newValue;
	};

	CvPlsBacklog.QuestionCounterHandler.prototype.getValue = function()
	{
		return parseInt(this.textNode.data, 10);
	};

	CvPlsBacklog.QuestionCounterHandler.prototype.add = function(toAdd)
	{
		this.textNode.data = parseInt(this.textNode.data, 10) + toAdd;
	};

	CvPlsBacklog.QuestionCounterHandler.prototype.subtract = function(toSubtract)
	{
		this.textNode.data = parseInt(this.textNode.data, 10) - toSubtract;
	};
}());

(function() { // class SettingsManager
	"use strict";

	CvPlsBacklog.SettingsManager = function(storageEngine)
	{
		this.storageEngine = storageEngine;
	};

	CvPlsBacklog.SettingsManager.prototype.storageEngine = null;

	CvPlsBacklog.SettingsManager.prototype.getSetting = function(key)
	{
		try {
			return JSON.parse(this.storageEngine.getItem(key));
		} catch(ignore) {}
	};

	CvPlsBacklog.SettingsManager.prototype.saveSetting = function(key, value)
	{
		this.storageEngine.setItem(key, JSON.stringify(value));
	};
}());

(function() { // abstract class CheckboxHandler
	"use strict";

	// Constructor
	CvPlsBacklog.CheckboxHandler = function(element, dataTable, settingsManager)
	{
		this.element = element;
		this.dataTable = dataTable;
		this.settingsManager = settingsManager;
	};

	CvPlsBacklog.CheckboxHandler.prototype.element         = null;
	CvPlsBacklog.CheckboxHandler.prototype.dataTable       = null;
	CvPlsBacklog.CheckboxHandler.prototype.settingsManager = null;
	CvPlsBacklog.CheckboxHandler.prototype.settingName     = null;

	CvPlsBacklog.CheckboxHandler.prototype.init = function()
	{
		this.element.checked = this.getCurrentSetting();

		this.element.addEventListener('change', this.onChange.bind(this), false);
		this.onChange();
	};

	CvPlsBacklog.CheckboxHandler.prototype.reset = function()
	{
		this.element.checked = this.element.hasAttribute('checked');
		this.onChange();
	};

	CvPlsBacklog.CheckboxHandler.prototype.getCurrentSetting = function()
	{
		var storedValue = this.settingsManager.getSetting(this.settingName);

		if (typeof storedValue !== "boolean") {
			return this.element.hasAttribute('checked');
		}

		return Boolean(storedValue);
	};

	CvPlsBacklog.CheckboxHandler.prototype.saveCurrentSetting = function()
	{
		this.settingsManager.saveSetting(this.settingName, this.element.checked);
	};
}());

(function() { // class LinkTargetCheckboxHandler extends CheckboxHandler
	"use strict";

	// Constructor
	CvPlsBacklog.LinkTargetCheckboxHandler = function(element, dataTable, settingsManager)
	{
		this.element = element;
		this.dataTable = dataTable;
		this.settingsManager = settingsManager;

		this.settingName = 'linkTarget';
	};

	// Extend base class
	CvPlsBacklog.LinkTargetCheckboxHandler.prototype = new CvPlsBacklog.CheckboxHandler();

	CvPlsBacklog.LinkTargetCheckboxHandler.prototype.onChange = function()
	{
		this.dataTable.setOpenInTabs(this.element.checked);

		this.saveCurrentSetting();
	};
}());

(function() { // class VoteTypeCheckboxHandler extends CheckboxHandler
	"use strict";

	// Constructor
	CvPlsBacklog.VoteTypeCheckboxHandler = function(element, dataTable, settingsManager)
	{
		this.element = element;
		this.dataTable = dataTable;
		this.settingsManager = settingsManager;

		this.voteType = element.id.split('-').pop();
		this.settingName = 'voteType-' + this.voteType;
	};

	// Extend base class
	CvPlsBacklog.VoteTypeCheckboxHandler.prototype = new CvPlsBacklog.CheckboxHandler();

	CvPlsBacklog.VoteTypeCheckboxHandler.prototype.voteType = '';

	CvPlsBacklog.VoteTypeCheckboxHandler.prototype.onChange = function()
	{
		if (this.element.checked) {
			this.dataTable.hideVoteType(this.voteType);
		} else {
			this.dataTable.showVoteType(this.voteType);
		}

		this.saveCurrentSetting();
	};
}());

(function() { // class FormResetHandler
	"use strict";

	// Constructor
	CvPlsBacklog.FormResetHandler = function(element)
	{
		this.element = element;
	};

	CvPlsBacklog.FormResetHandler.prototype.element = null;
	CvPlsBacklog.FormResetHandler.prototype.controls = null;

	CvPlsBacklog.FormResetHandler.prototype.init = function()
	{
		this.controls = [];

		this.element.addEventListener('click', this.onClick.bind(this), false);
	};

	CvPlsBacklog.FormResetHandler.prototype.addControl = function(control)
	{
		return this.controls.push(control);
	};

	CvPlsBacklog.FormResetHandler.prototype.onClick = function()
	{
		var i, controlCount;

		for (i = 0, controlCount = this.controls.length; i < controlCount; i++) {
			this.controls[i].reset();
		}
	};
}());

(function() { // bootstrap
	"use strict";

	var i, l, voteTypes, dataTable, settingsManager, resetHandler, control;

	voteTypes = ['cv', 'delv', 'ro', 'rv', 'adelv'];

	dataTable = new CvPlsBacklog.DataTableHandler(
		document.getElementById('data-table'),
		new CvPlsBacklog.QuestionCounterHandler(document.getElementById('questions-count'))
	);
	dataTable.init();

	settingsManager = new CvPlsBacklog.SettingsManager(window.localStorage);

	resetHandler = new CvPlsBacklog.FormResetHandler(document.getElementById('reset-options'));
	resetHandler.init();

	control = new CvPlsBacklog.LinkTargetCheckboxHandler(
		document.getElementById('check-tabs'),
		dataTable,
		settingsManager
	);
	resetHandler.addControl(control);
	control.init();

	for (i = 0, l = voteTypes.length; i < l; i++) {
		control = new CvPlsBacklog.VoteTypeCheckboxHandler(
			document.getElementById('check-' + voteTypes[i]),
			dataTable,
			settingsManager
		);
		resetHandler.addControl(control);
		control.init();
	}
}());

(function() { // sticky header
	'use strict';

	var tableHeader, origOffsetY;

	tableHeader = document.getElementById('data-table-head');
	origOffsetY = tableHeader.offsetTop;

	window.addEventListener('scroll', function () {
		if (window.scrollY >= origOffsetY) {
			tableHeader.classList.add('sticky');
		} else {
			tableHeader.classList.remove('sticky');
		}
	});
}());