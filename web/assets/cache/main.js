var CvPlsBacklog={};
(function(){function a(a){a.preventDefault();window.open(a.currentTarget.href,this.linkTarget)}function b(){this.init();this.refreshTimer=setTimeout(b.bind(this),this.autoRefreshInterval)}CvPlsBacklog.DataTableHandler=function(a,b,d){this.element=a;this.voteCounter=b;this.autoRefreshInterval=void 0!==d?1E3*Number(d):6E4};CvPlsBacklog.DataTableHandler.prototype.element=null;CvPlsBacklog.DataTableHandler.prototype.voteCounter=null;CvPlsBacklog.DataTableHandler.prototype.autoRefreshInterval=null;CvPlsBacklog.DataTableHandler.prototype.rowCounts=
null;CvPlsBacklog.DataTableHandler.prototype.linkTarget="_self";CvPlsBacklog.DataTableHandler.prototype.refreshTimer=null;CvPlsBacklog.DataTableHandler.prototype.init=function(){var c,b,d,f,e,h,k;this.rowCounts={};k=a.bind(this);c=this.element.querySelectorAll("tbody tr:not(.error-message)");d=0;for(e=c.length;d<e;d++)for(void 0===this.rowCounts[c[d].className]&&(this.rowCounts[c[d].className]=0),this.rowCounts[c[d].className]++,b=c[d].querySelectorAll("a"),f=0,h=b.length;f<h;f++)b[f].addEventListener("click",
k,!1);this.voteCounter.setValue(e)};CvPlsBacklog.DataTableHandler.prototype.hideVoteType=function(a){this.element.classList.contains("hide-"+a)||(this.element.classList.add("hide-"+a),this.voteCounter.subtract(this.rowCounts[a]))};CvPlsBacklog.DataTableHandler.prototype.showVoteType=function(a){this.element.classList.contains("hide-"+a)&&(this.element.classList.remove("hide-"+a),this.voteCounter.add(this.rowCounts[a]))};CvPlsBacklog.DataTableHandler.prototype.setOpenInTabs=function(a){this.linkTarget=
a?"_blank":"_self"};CvPlsBacklog.DataTableHandler.prototype.setAutoRefresh=function(a){a&&null===this.refreshTimer?this.refreshTimer=setTimeout(b.bind(this),this.autoRefreshInterval):null!==this.refreshTimer&&(clearTimeout(this.refreshTimer),this.refreshTimer=null)}})();
(function(){CvPlsBacklog.QuestionCounterHandler=function(a){this.textNode=a.firstChild};CvPlsBacklog.QuestionCounterHandler.prototype.textNode=null;CvPlsBacklog.QuestionCounterHandler.prototype.setValue=function(a){this.textNode.data=a};CvPlsBacklog.QuestionCounterHandler.prototype.getValue=function(){return parseInt(this.textNode.data,10)};CvPlsBacklog.QuestionCounterHandler.prototype.add=function(a){this.textNode.data=parseInt(this.textNode.data,10)+a};CvPlsBacklog.QuestionCounterHandler.prototype.subtract=
function(a){this.textNode.data=parseInt(this.textNode.data,10)-a}})();(function(){CvPlsBacklog.SettingsManager=function(a){this.storageEngine=a};CvPlsBacklog.SettingsManager.prototype.storageEngine=null;CvPlsBacklog.SettingsManager.prototype.getSetting=function(a){try{return JSON.parse(this.storageEngine.getItem(a))}catch(b){}};CvPlsBacklog.SettingsManager.prototype.saveSetting=function(a,b){this.storageEngine.setItem(a,JSON.stringify(b))}})();
(function(){CvPlsBacklog.CheckboxHandler=function(a,b,c){this.element=a;this.dataTable=b;this.settingsManager=c};CvPlsBacklog.CheckboxHandler.prototype.element=null;CvPlsBacklog.CheckboxHandler.prototype.dataTable=null;CvPlsBacklog.CheckboxHandler.prototype.settingsManager=null;CvPlsBacklog.CheckboxHandler.prototype.settingName=null;CvPlsBacklog.CheckboxHandler.prototype.init=function(){this.element.checked=this.getCurrentSetting();this.element.addEventListener("change",this.onChange.bind(this),!1);
this.onChange()};CvPlsBacklog.CheckboxHandler.prototype.reset=function(){this.element.checked=this.element.hasAttribute("checked");this.onChange()};CvPlsBacklog.CheckboxHandler.prototype.getCurrentSetting=function(){var a=this.settingsManager.getSetting(this.settingName);return"boolean"!==typeof a?this.element.hasAttribute("checked"):Boolean(a)};CvPlsBacklog.CheckboxHandler.prototype.saveCurrentSetting=function(){this.settingsManager.saveSetting(this.settingName,this.element.checked)}})();
(function(){CvPlsBacklog.LinkTargetCheckboxHandler=function(a,b,c){this.element=a;this.dataTable=b;this.settingsManager=c;this.settingName="linkTarget"};CvPlsBacklog.LinkTargetCheckboxHandler.prototype=new CvPlsBacklog.CheckboxHandler;CvPlsBacklog.LinkTargetCheckboxHandler.prototype.onChange=function(){this.dataTable.setOpenInTabs(this.element.checked);this.saveCurrentSetting()}})();
(function(){CvPlsBacklog.VoteTypeCheckboxHandler=function(a,b,c){this.element=a;this.dataTable=b;this.settingsManager=c;this.voteType=a.id.split("-").pop();this.settingName="voteType-"+this.voteType};CvPlsBacklog.VoteTypeCheckboxHandler.prototype=new CvPlsBacklog.CheckboxHandler;CvPlsBacklog.VoteTypeCheckboxHandler.prototype.voteType="";CvPlsBacklog.VoteTypeCheckboxHandler.prototype.onChange=function(){this.element.checked?this.dataTable.hideVoteType(this.voteType):this.dataTable.showVoteType(this.voteType);
this.saveCurrentSetting()}})();
(function(){CvPlsBacklog.FormResetHandler=function(a){this.element=a};CvPlsBacklog.FormResetHandler.prototype.element=null;CvPlsBacklog.FormResetHandler.prototype.controls=null;CvPlsBacklog.FormResetHandler.prototype.init=function(){this.controls=[];this.element.addEventListener("click",this.onClick.bind(this),!1)};CvPlsBacklog.FormResetHandler.prototype.addControl=function(a){return this.controls.push(a)};CvPlsBacklog.FormResetHandler.prototype.onClick=function(){var a,b;a=0;for(b=this.controls.length;a<
b;a++)this.controls[a].reset()}})();
(function(){var a,b,c,g,d,f,e;c=["cv","delv","ro","rv","adelv"];g=new CvPlsBacklog.DataTableHandler(document.getElementById("data-table"),new CvPlsBacklog.QuestionCounterHandler(document.getElementById("questions-count")));g.init();d=new CvPlsBacklog.SettingsManager(window.localStorage);f=new CvPlsBacklog.FormResetHandler(document.getElementById("reset-options"));f.init();e=new CvPlsBacklog.LinkTargetCheckboxHandler(document.getElementById("check-tabs"),g,d);f.addControl(e);e.init();a=0;for(b=c.length;a<
b;a++)e=new CvPlsBacklog.VoteTypeCheckboxHandler(document.getElementById("check-"+c[a]),g,d),f.addControl(e),e.init()})();(function(){var a,b;a=document.getElementById("data-table-head");b=a.offsetTop;window.addEventListener("scroll",function(){window.scrollY>=b?a.classList.add("sticky"):a.classList.remove("sticky")})})();
