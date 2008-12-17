<?php
    if (!headers_sent()) header('Content-Type: application/javascript; charset=utf-8');
    include_once 'gtdfuncs.inc.php';
?>
/*jslint browser: false, eqeqeq: true, undef: true */
/*global Application,CmdUtils,jQuery,noun_arb_text,displayMessage */


var kver = "200812161143",
    kPath = "<?php echo getAbsolutePath(); ?>",
    kDoLog = false,
    kProjectCache = "gtdphp.ubiquity.projects";


// --------------------- ubiquity utility to initialise list of projects
function startup_gtd() {
  if (kDoLog) { displayMessage(kver + " gtd-ubiquity initialising now"); }
  // initialize list of projects
  noun_type_gtdparent.parentList = Application.storage.get(kProjectCache, null);
  if (noun_type_gtdparent.parentList === null) {
    noun_type_gtdparent.getParents();
  }
}


// ------------------------------ ubiquity utility to set last query result
function gtdSetLastResult(aXml) {
  var newid = jQuery(aXml).find("itemId").text();
  CmdUtils.setLastResult('<a href="' + kPath + 'itemReport.php?itemId=' +
            newid + '">' + jQuery(aXml).find("title").text() + '</a>');
  return newid;
}


// ---------------------------------- AJAX utility for database-writing API
function gtdDoAjax(aData, aSucceed) {
  var field, template = { output: "xml", fromajax: "true" };
  for (field in template) { aData[field] = template[field]; }
  jQuery.ajax({
    type: 'post',
    url: kPath + "processItems.php",
    data: aData,
    error: function gtd_ajaxerr() { displayMessage("Failed ajax call"); },
    success: aSucceed,
    dataType: "xml"
  });
}


// ------------------------ noun: parent project
var noun_type_gtdparent = {
  _name: "gtdphp parent project",
  parentList: null,
  //'default': function gtdp_default() { return this.subsuggest('')[0]; },
  
  getParents: function gtd_getparents(aOnDone) {
    var self=this;
    if (kDoLog) { displayMessage('off to get parents'); }
    jQuery.getJSON(kPath + "addon.php?addonid=ajax&url=sendJSON.php&type=p",
                   function gtd_json(aParents) {
                     if (kDoLog) {displayMessage('assigning parents');}
                     self.parentList = aParents;
                     Application.storage.set(kProjectCache, aParents);
                     if (aOnDone) { aOnDone(); }
                   });
  },

  subsuggest: function gtd_subsuggest(aText, aHtml) {
    if (kDoLog) { displayMessage('in subsuggest searching for parent ' + aText); }
    var id, title, teststring,
        suggestions = [],
        i = 4;
    if (this.parentList === {}) {
      suggestions.push(CmdUtils.makeSugg('Still awaiting list of parents'));
    }
    else {
      for (id in noun_type_gtdparent.parentList) {
        if (aText === '') { aText = "."; }
        teststring = new RegExp(aText, "i");
        title = noun_type_gtdparent.parentList[id];
        if (teststring.test(title)) {
          suggestions.push(CmdUtils.makeSugg(title,
             "<a href='" + kPath +
	     "itemReport.php?itemId=" + id + "'>" + title + "</a>",
             {itemId: id}));
          if (!i--) { break; }
        }
      }
    }
    return suggestions;
  },

  suggest: function gtd_suggest(aText, aHtml, aCallback) {
    var self = this;
    if (self.parentList === null) {
      self.parentList = {}; // empty the array first, to ensure we only issue the JSON request once
      self._callback = aCallback;
      self.getParents(function gtd_gotparents() {
        out = self.subsuggest(aText, aHtml);
        for (sug in out) { self._callback(out[sug]); }
        //self._callback(out);
        if (kDoLog) { displayMessage("got " + out.length + " async results"); }
      });
      return [];
    }
    return self.subsuggest(aText, aHtml);
  }

};


// --------------------- command object template
function makegtd(aObj) {
  var field, template = {homepage: "http://www.gtd-php.com/Developers/Ubiquity",
    author: {name: "Andrew Smith"},
    license: "GPL",
    icon: kPath + 'favicon.ico'};
  for (field in template) { aObj[field] = template[field]; }
  CmdUtils.CreateCommand(aObj);
}


// ------ create an inbox item
makegtd({
  name: "gtdin",
  description: "Adds a GTD inbox item",
  help: "Provide item title for a new GTD inbox item",
  takes: { "item title": noun_arb_text },

  preview: function gtdin_preview(aPblock, aTitle) {
    aPblock.innerHTML = 'Creates inbox item with title: "<i>' + aTitle.text + '"</i>"';
  },

  execute: function gtdin_exec(aTitle) {
    //var itemurl=", <a href='"+kPath+"item.php?itemId=";
    gtdDoAjax(
      { action: "create", type: "i", title: aTitle.text },
      function gtdin_exec_ajax(aXml,aText){
        displayMessage("Inbox item created with id: " +
            gtdSetLastResult(aXml) + ", title: " + aTitle.text);
      } );
  }
});


// ------ create a reference
makegtd({
  name: "gtdref",
  description: "Adds a reference to the current URL",
  help: "Adds a gtd-php reference to the current URL, or," +
        "if a link is selected, to the destination of that link",

  modifiers: { title: noun_arb_text },
  
  takes: { parent: noun_type_gtdparent },

  _getDocHrefAndTitle: function gtdref_getDocHrefAndTitle() {
    try {
      var document = Application.activeWindow.activeTab.document,
          currenturl = document.location.href,
          title = aMods.title.html || document.title;
      return { done: true, url: currenturl, title: title };
    } catch (err) {
      return { done: false, message: "Unable to get location or title for current page, so cannot create a GTD reference for it" };
    }
  },
  
  preview: function gtdref_preview(aPblock, aParent, aMods) {
    var docInfo=this._getDocHrefAndTitle();
    if (!docInfo.done) {
      aPblock.innerHTML = docInfo.message;
      return false;
    }
    aPblock.innerHTML = 'Creates a reference to this page as a child of: "' +
      aParent.html + '"';
    return true;
  },

  execute: function gtdref_exec(aParent, aMods) {
    var docInfo=this._getDocHrefAndTitle();
    if (!docInfo.done) {
      displayMessage(docInfo.message);
      return false;
    }
    gtdDoAjax(
      {action: "create", type: "r", parentId: aParent.data.itemId, title: title,
        description: "webpage: <a href='" + currenturl+"'>" + title + "</a>"},
      function gtdref_exec_ajax(aXml,aText){
        displayMessage("Reference created with id: " + gtdSetLastResult(aXml));
      } );
    return true;
  }
});


// ------ create a next-action
makegtd({
  name: "gtdna",
  description: "Adds a next action to gtd-php",
  help: "Adds a next action",
  takes: { title: noun_arb_text },
  modifiers: { parent: noun_type_gtdparent },

  preview: function gtdna_preview(aPblock, aTitle, aMods) {
    aPblock.innerHTML = 'Creates a next action with title: "' +
      aTitle.text + '" as a child of the item: ' + aMods.parent.html;
  },

  execute: function gtdna_exec(aTitle, aMods) {
    gtdDoAjax(
      { action: "create",type: "a", nextaction: 'y',
        parentId: aMods.parent.data.itemId, title: aTitle.text },
      function gtdna_exec_ajax(aXml, aText){
        displayMessage("Next action created with id: " +
	  gtdSetLastResult(aXml));
      });
  }
});


// ------ clear and refresh the list of projects
makegtd({
  name: "gtdclear",
  description: "Clears the cache of GTD projects, and regenerates them from the live database",

  preview: function gtdclear_preview(aPblock) {
    aPblock.innerHtml = this.description;
  },

  execute: function gtdclear_exec() {
    Application.storage.set(kProjectCache, null);
    noun_type_gtdparent.parentList = null;
    displayMessage("GTD parents cache cleared: regenerating now");
    noun_type_gtdparent.getParents();
  }

});
