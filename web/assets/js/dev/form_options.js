/*jslint browser: true, ass: true, plusplus: true, white: true */

/** @author Chris Wright <cvbacklogui@daverandom.com> */

/**
 * The CvPlsBacklog namespace
 */
(function () {
    'use strict';

    /**
     * Namespace declaration
     */
    var CvPlsBacklog = window.CvPlsBacklog = {};

    /**
     * Get the integer value of a variable and throw an error if it is not a valid integer
     *
     * @param {*} data
     * @returns {int}
     * @throws Error
     */
    function getIntValue(data) {
        var value = parseInt(data, 10);

        if (isNaN(value)) {
            throw new Error('The integer value of ' + data + ' is NaN');
        }

        return value;
    }

    /**
     * The DataTable class
     *
     * Responsible for managing the data displayed in the table
     */
    (function () {
        var DISPLAY_NO = 0,
            DISPLAY_YES = 1,
            DataTable;

        /**
         * Link click event callback
         *
         * @param {Event} ev
         */
        function onLinkClick(ev) {
            ev.preventDefault();
            window.open(ev.currentTarget.href, this.linkTarget);
        }

        /**
         * @constructor
         * @param {HTMLTableElement}             element
         * @param {CvPlsBacklog.QuestionCounter} voteCounter
         * @param {int}                          [autoRefreshInterval]
         */
        DataTable = CvPlsBacklog.DataTable = function (element, voteCounter) {
            this.element = element;
            this.voteCounter = voteCounter;

            this.voteTypeDisplayStatus = {};
        };

        /**
         * @type {HTMLTableElement}
         */
        DataTable.prototype.element = undefined;

        /**
         * @type {CvPlsBacklog.QuestionCounter}
         */
        DataTable.prototype.voteCounter = undefined;

        /**
         * @type {object}
         */
        DataTable.prototype.rowCounts = undefined;

        /**
         * @type {object}
         */
        DataTable.prototype.voteTypeDisplayStatus = undefined;

        /**
         * @type {string}
         */
        DataTable.prototype.linkTarget = '_self';

        /**
         * Initialisation routine
         *
         * Binds the link click callback to all vote rows in the table and builds the vote count map
         */
        DataTable.prototype.init = function () {
            var rows, links, i, j, rowCount, linkCount, onLinkClickBound, voteType,
                countCurrent = 0, countTotal = 0;

            onLinkClickBound = onLinkClick.bind(this);

            rows = this.element.querySelectorAll('tbody tr:not(.error-message)');
            this.rowCounts = {};

            for (i = 0, rowCount = rows.length; i < rowCount; i++) {
                voteType = rows[i].className;

                if (this.voteTypeDisplayStatus[voteType] === undefined) {
                    this.voteTypeDisplayStatus[voteType] = DISPLAY_YES;
                }

                if (this.rowCounts[voteType] === undefined) {
                    this.rowCounts[voteType] = 0;
                }
                this.rowCounts[voteType]++;

                links = rows[i].querySelectorAll('a');
                for (j = 0, linkCount = links.length; j < linkCount; j++) {
                    if (!links[j].hasAttribute('data-hasclickhandler')) {
                        links[j].addEventListener('click', onLinkClickBound, false);
                        links[j].setAttribute('data-hasclickhandler', 'true');
                    }
                }
            }

            for (voteType in this.rowCounts) {
                if (this.rowCounts.hasOwnProperty(voteType)) {
                    countTotal += this.rowCounts[voteType];

                    if (this.voteTypeDisplayStatus[voteType] === DISPLAY_YES) {
                        countCurrent += this.rowCounts[voteType];
                    }
                }
            }

            this.voteCounter.setCurrent(countCurrent);
            this.voteCounter.setTotal(countTotal);
        };

        /**
         * Hides all rows with the specified vote type
         *
         * @param {string} voteType
         */
        DataTable.prototype.hideVoteType = function (voteType) {
            if (!this.element.classList.contains('hide-' + voteType)) {
                this.element.classList.add('hide-' + voteType);

                this.voteTypeDisplayStatus[voteType] = DISPLAY_NO;
                if (this.rowCounts[voteType] !== undefined) {
                    this.voteCounter.subtractCurrent(this.rowCounts[voteType]);
                }
            }
        };

        /**
         * Shows all rows of the specified vote type
         *
         * @param voteType
         */
        DataTable.prototype.showVoteType = function (voteType) {
            if (this.element.classList.contains('hide-' + voteType)) {
                this.element.classList.remove('hide-' + voteType);

                this.voteTypeDisplayStatus[voteType] = DISPLAY_YES;
                if (this.rowCounts[voteType] !== undefined) {
                    this.voteCounter.addCurrent(this.rowCounts[voteType]);
                }
            }
        };

        /**
         * Set whether question links will open in a new tab
         *
         * @param {boolean} openInTabs
         */
        DataTable.prototype.setOpenInTabs = function (openInTabs) {
            this.linkTarget = openInTabs ? '_blank' : '_self';
        };

        /**
         * Refresh the table data
         *
         * @param {Function} [callback] Called when the refresh operation completes
         */
        DataTable.prototype.refresh = function (callback) {
            // TODO: implement this

            this.init();
            callback();
        };
    }());

    /**
     * The DataTableAutoRefresher class
     *
     * Responsible for instructing the data table to refresh and updating the refresh status display
     */
    (function () {
        /**
         * Updates the counter display and schedules another ticker callback
         *
         * @param {int} now
         */
        function updateCounter(now) {
            var secs, diff = this.nextRefresh - now;

            if (this.textNode !== undefined) {
                secs = Math.ceil(diff / 1000);
                this.textNode.data = secs;
            }

            this.timeoutHandle = setTimeout(tick.bind(this), diff % 1000);
        }

        /**
         * Callback called when the refresh operation is completed
         */
        function onRefreshComplete() {
            var now = (new Date()).getTime();
            this.nextRefresh = now + (this.interval * 1000);
            updateCounter.call(this, now);
        }

        /**
         * Updates the counter display and refreshes the table
         */
        function refreshTable() {
            if (this.textNode !== undefined) {
                this.textNode.data = 'Updating...';
            }

            this.dataTable.refresh(onRefreshComplete.bind(this));
        }

        /**
         * Callback called once a second when refresh is enabled
         */
        function tick() {
            var now = (new Date()).getTime();

            if (now >= this.nextRefresh) {
                refreshTable.call(this);
            } else {
                updateCounter.call(this, now);
            }
        }

        /**
         * @constructor
         * @param {CvPlsBacklog.DataTable} dataTable
         * @param {HTMLElement}            [element]
         */
        var DataTableAutoRefresher = CvPlsBacklog.DataTableAutoRefresher = function (dataTable, statusElement) {
            this.dataTable = dataTable;

            if (statusElement && statusElement.firstChild && statusElement.firstChild instanceof Text) {
                this.textNode = statusElement.firstChild;
            }
        };

        /**
         * @type {CvPlsBacklog.DataTable}
         */
        DataTableAutoRefresher.prototype.dataTable = undefined;

        /**
         * @type {HTMLElement|undefined}
         */
        DataTableAutoRefresher.prototype.textNode = undefined;

        /**
         * @type {int}
         */
        DataTableAutoRefresher.prototype.interval = undefined;

        /**
         * @type {int}
         */
        DataTableAutoRefresher.prototype.nextRefresh = undefined;

        /**
         * @type {int|undefined}
         */
        DataTableAutoRefresher.prototype.timeoutHandle = undefined;

        /**
         * Set the interval at which the table will automatically refresh
         *
         * @param {int} interval New interval value in seconds
         * @throws {Error} When the new interval value is not a valid integer
         */
        DataTableAutoRefresher.prototype.setInterval = function (interval) {
            var diff;

            interval = getIntValue(interval);
            diff = interval - this.interval;
            this.interval = interval;

            if (this.timeoutHandle !== undefined && diff) {
                this.nextRefresh += diff;
            }
        };

        /**
         * Enable the auto-refresh ticker
         */
        DataTableAutoRefresher.prototype.enable = function (refreshNow) {
            if (this.timeoutHandle === undefined) {
                if (refreshNow) {
                    refreshTable.call(this);
                } else {
                    onRefreshComplete.call(this);
                }
            }
        };

        /**
         * Disable the auto-refresh ticker
         */
        DataTableAutoRefresher.prototype.disable = function () {
            if (this.timeoutHandle !== undefined) {
                clearTimeout(this.timeoutHandle);
                this.timeoutHandle = undefined;
            }
        };
    }());

    /**
     * The AutoRefreshSelect class
     *
     * Responsible for handling user actions on the refresh interval select and saving the user's selection
     */
    (function () {
        /**
         * Update the saved interval value and pass the new value along to the refresher
         *
         * @param {int} newValue
         */
        function setRefreshInterval(newValue) {
            if (newValue) {
                this.dataTableRefresher.setInterval(newValue);
                this.dataTableRefresher.enable();
            } else {
                this.dataTableRefresher.disable();
            }

            this.settingsManager.saveSetting('autoRefreshInterval', newValue);
        }

        /**
         * Callback called by the change event of the underlying element
         */
        function onSelectChange() {
            setRefreshInterval.call(this, getIntValue(this.element.options[this.element.selectedIndex].value));
        }

        /**
         * @constructor
         * @param {HTMLElement}                         element
         * @param {CvPlsBacklog.DataTableAutoRefresher} dataTableRefresher
         * @param {CvPlsBacklog.SettingsManager}        settingsManager
         */
        var AutoRefreshSelect = CvPlsBacklog.AutoRefreshSelect = function (element, dataTableRefresher, settingsManager) {
            this.element = element;
            this.dataTableRefresher = dataTableRefresher;
            this.settingsManager = settingsManager;
        };

        /**
         * @type {HTMLElement}
         */
        AutoRefreshSelect.prototype.element = undefined;

        /**
         * @type {CvPlsBacklog.DataTableAutoRefresher}
         */
        AutoRefreshSelect.prototype.dataTableRefresher = undefined;

        /**
         * @type {CvPlsBacklog.SettingsManager}
         */
        AutoRefreshSelect.prototype.settingsManager = undefined;

        /**
         * Initialisation routine
         *
         * Sets the value of the underlying element to the saved value and adds the change event handler
         */
        AutoRefreshSelect.prototype.init = function () {
            var i, l, savedValue = this.settingsManager.getSetting('autoRefreshInterval');

            for (i = 0, l = this.element.options.length; i < l; i++) {
                if (getIntValue(this.element.options[i].value) === savedValue) {
                    this.element.selectedIndex = i;
                    break;
                }
            }

            if (savedValue !== undefined) {
                setRefreshInterval.call(this, savedValue);
            }

            this.element.addEventListener('change', onSelectChange.bind(this), false);
        };
    }());

    /**
     * The QuestionCounter class
     *
     * Responsible for updating the counter for the number of questions currently displayed
     */
    (function () {
        /**
         * @constructor
         * @param {HTMLElement} element An HTML element who's first child is a text node
         * @throws {Error} When the supplied element does not have a text node as its first child
         */
        var QuestionCounter = CvPlsBacklog.QuestionCounter = function (currentElement, totalElement) {
            if (!currentElement || !currentElement.firstChild || !(currentElement.firstChild instanceof Text)) {
                throw new Error('The supplied current element does not have a text node as its first child');
            }
            this.currentTextNode = currentElement.firstChild;

            if (!totalElement || !totalElement.firstChild || !(totalElement.firstChild instanceof Text)) {
                throw new Error('The supplied total element does not have a text node as its first child');
            }
            this.totalTextNode = totalElement.firstChild;
        };

        /**
         * @type {Text}
         */
        QuestionCounter.prototype.currentTextNode = undefined;

        /**
         * @type {Text}
         */
        QuestionCounter.prototype.totalTextNode = undefined;

        /**
         * Set the current displayed value
         *
         * @param {int} newValue
         * @throws {Error} When the new value is not a valid integer
         */
        QuestionCounter.prototype.setCurrent = function (newValue) {
            this.currentTextNode.data = String(getIntValue(newValue));
        };

        /**
         * Set the current displayed value
         *
         * @param {int} newValue
         * @throws {Error} When the new value is not a valid integer
         */
        QuestionCounter.prototype.setTotal = function (newValue) {
            this.totalTextNode.data = String(getIntValue(newValue));
        };

        /**
         * Get the displayed value
         *
         * @returns {int}
         * @throws {Error} When the current displayed value is not a valid integer
         */
        QuestionCounter.prototype.getCurrent = function () {
            return getIntValue(this.currentTextNode.data);
        };

        /**
         * Add a value to the displayed value
         *
         * @param {int} toAdd
         * @throws {Error} When the value to add is not a valid integer
         */
        QuestionCounter.prototype.addCurrent = function (toAdd) {
            this.currentTextNode.data = String(this.getCurrent() + getIntValue(toAdd));
        };

        /**
         * Subtract a value from the displayed value
         *
         * @param {int} toSubtract
         * @throws {Error} When the value to subtract is not a valid integer
         */
        QuestionCounter.prototype.subtractCurrent = function (toSubtract) {
            this.currentTextNode.data = String(this.getCurrent() - getIntValue(toSubtract));
        };
    }());

    /**
     * The SettingsManager class
     *
     * Responsible for storing and retrieving persistent data
     */
    (function () {
        /**
         * @constructor
         * @param {object} storageEngine Object implementing the localStorage interface
         */
        var SettingsManager = CvPlsBacklog.SettingsManager = function (storageEngine) {
            this.storageEngine = storageEngine;
        };

        /**
         * @type {object} Object implementing the localStorage interface
         */
        SettingsManager.prototype.storageEngine = undefined;

        /**
         * Get a value from the storage layer
         *
         * @param {string} key
         * @returns {*} The stored value or undefined if the key is invalid
         */
        SettingsManager.prototype.getSetting = function (key) {
            try {
                return JSON.parse(this.storageEngine.getItem(key));
            } catch (e) {
                // we return undefined because null, false and 0 are all valid JSON-encodable values
                return undefined;
            }
        };

        /**
         * Set a value in the storage layer
         *
         * @param {string} key
         * @param {*} value
         */
        SettingsManager.prototype.saveSetting = function (key, value) {
            this.storageEngine.setItem(key, JSON.stringify(value));
        };
    }());

    /**
     * The Checkbox base class
     *
     * Shared functionality for checkbox wrapper implementations
     */
    (function () {
        /**
         * @constructor
         */
        var Checkbox = CvPlsBacklog.Checkbox = function () {
            // base class, constructor implemented by children
        };

        /**
         * @type {HTMLInputElement}
         */
        Checkbox.prototype.element = undefined;

        /**
         * @type {CvPlsBacklog.DataTable}
         */
        Checkbox.prototype.dataTable = undefined;

        /**
         * @type {CvPlsBacklog.SettingsManager}
         */
        Checkbox.prototype.settingsManager = undefined;

        /**
         * @type {string}
         */
        Checkbox.prototype.settingName = undefined;

        /**
         * Initialisation routine
         *
         * Updates the checkbox with the current setting and binds the change handler
         */
        Checkbox.prototype.init = function () {
            this.element.checked = this.getCurrentSetting();

            this.element.addEventListener('change', this.onChange.bind(this), false);
            this.onChange();
        };

        /**
         * Reset the checkbox and it's setting to it's initial state as defined by the server
         */
        Checkbox.prototype.reset = function () {
            this.element.checked = this.element.hasAttribute('checked');
            this.onChange();
        };

        /**
         * Get the current saved value of the associated setting
         *
         * @returns {boolean}
         */
        Checkbox.prototype.getCurrentSetting = function () {
            var storedValue = this.settingsManager.getSetting(this.settingName);

            if (typeof storedValue !== 'boolean') {
                return this.element.hasAttribute('checked');
            }

            return Boolean(storedValue);
        };

        /**
         * Update the saved value of the associated setting to the current checked status of the checkbox
         */
        Checkbox.prototype.saveCurrentSetting = function () {
            this.settingsManager.saveSetting(this.settingName, this.element.checked);
        };
    }());

    /**
     * The LinkTargetCheckbox concrete class
     *
     * Responsible for handling user interactions with the link target checkbox
     */
    (function () {
        /**
         * @constructor
         * @param {HTMLInputElement}             element
         * @param {CvPlsBacklog.DataTable}       dataTable
         * @param {CvPlsBacklog.SettingsManager} settingsManager
         */
        var LinkTargetCheckbox = CvPlsBacklog.LinkTargetCheckbox = function (element, dataTable, settingsManager) {
            this.element = element;
            this.dataTable = dataTable;
            this.settingsManager = settingsManager;

            this.settingName = 'linkTarget';
        };

        /**
         * Extend base class
         */
        LinkTargetCheckbox.prototype = new CvPlsBacklog.Checkbox();

        /**
         * Change event handler for the associated checkbox element
         */
        LinkTargetCheckbox.prototype.onChange = function () {
            this.dataTable.setOpenInTabs(this.element.checked);

            this.saveCurrentSetting();
        };
    }());

    /**
     * The VoteTypeCheckbox concrete class
     *
     * Responsible for handling user interactions with the vote type checkboxes
     */
    (function () {
        /**
         * @constructor
         * @param {HTMLInputElement}             element
         * @param {CvPlsBacklog.DataTable}       dataTable
         * @param {CvPlsBacklog.SettingsManager} settingsManager
         */
        var VoteTypeCheckbox = CvPlsBacklog.VoteTypeCheckbox = function (element, dataTable, settingsManager) {
            this.element = element;
            this.dataTable = dataTable;
            this.settingsManager = settingsManager;

            this.voteType = element.id.split('-').pop();
            this.settingName = 'voteType-' + this.voteType;
        };

        /**
         * Extend base class
         */
        VoteTypeCheckbox.prototype = new CvPlsBacklog.Checkbox();

        /**
         * @type {string} The vote type identifier with which this checkbox is associated
         */
        VoteTypeCheckbox.prototype.voteType = undefined;

        /**
         * Change event handler for the associated checkbox element
         */
        VoteTypeCheckbox.prototype.onChange = function () {
            if (this.element.checked) {
                this.dataTable.hideVoteType(this.voteType);
            } else {
                this.dataTable.showVoteType(this.voteType);
            }

            this.saveCurrentSetting();
        };
    }());

    /**
     * The FormResetter class
     *
     * Handles the button that resets the form to default settings
     */
    (function () {
        /**
         * Click event handler for the underlying element
         */
        function onButtonClick() {
            var i, controlCount;

            for (i = 0, controlCount = this.controls.length; i < controlCount; i++) {
                this.controls[i].reset();
            }
        }

        /**
         * @constructor
         * @param {HTMLElement} element
         */
        var FormResetter = CvPlsBacklog.FormResetter = function (element) {
            this.element = element;
        };

        /**
         * @type {HTMLElement}
         */
        FormResetter.prototype.element = undefined;

        /**
         * @type {Checkbox[]}
         */
        FormResetter.prototype.controls = undefined;

        /**
         * Initialisation routine
         *
         * Binds the click callback to the underlying element and initialises the control array
         */
        FormResetter.prototype.init = function () {
            this.controls = [];

            this.element.addEventListener('click', onButtonClick.bind(this), false);
        };

        /**
         * Add a control to the reset list
         *
         * @param control
         * @returns {Number}
         */
        FormResetter.prototype.addControl = function (control) {
            return this.controls.push(control);
        };
    }());

    /**
     * Bootstrap
     */
    (function () {
        var i, l, voteTypes, dataTable, settingsManager, formResetter, control, autoRefreshSelect;

        // List of vote type prefixes
        voteTypes = ['cv', 'delv', 'ro', 'rv', 'adelv'];

        settingsManager = new CvPlsBacklog.SettingsManager(window.localStorage);

        dataTable = new CvPlsBacklog.DataTable(
            document.getElementById('data-table'),
            new CvPlsBacklog.QuestionCounter(
                document.getElementById('questions-count-current'),
                document.getElementById('questions-count-total')
            )
        );
        dataTable.init();

        formResetter = new CvPlsBacklog.FormResetter(document.getElementById('reset-options'));
        formResetter.init();

        control = new CvPlsBacklog.LinkTargetCheckbox(
            document.getElementById('check-tabs'),
            dataTable,
            settingsManager
        );
        formResetter.addControl(control);
        control.init();

        for (i = 0, l = voteTypes.length; i < l; i++) {
            control = new CvPlsBacklog.VoteTypeCheckbox(
                document.getElementById('check-' + voteTypes[i]),
                dataTable,
                settingsManager
            );
            formResetter.addControl(control);
            control.init();
        }

        autoRefreshSelect = new CvPlsBacklog.AutoRefreshSelect(
            document.getElementById('refresh-interval'),
            new CvPlsBacklog.DataTableAutoRefresher(dataTable, document.getElementById('refresh-notice')),
            settingsManager
        );
        autoRefreshSelect.init();
    }());
}());
