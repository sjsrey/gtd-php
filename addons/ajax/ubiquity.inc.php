<?php
    if (!headers_sent()) header('Content-Type: application/javascript; charset=utf-8');
    include_once 'gtdfuncs.inc.php';
?>
/*----------------------------------------------------------------
This is the command file for the ubiquity instructions for gtd-php

At the bottom of this page, set "Auto-update this feed" to true.
----------------------------------------------------------------*/

/*jslint browser: false, eqeqeq: true, undef: true */
/*global Application,CmdUtils,jQuery,noun_arb_text,displayMessage */

var kver = "200901061428",
    kPath = "<?php echo getAbsolutePath(); ?>",
    kDoLog = true,
    kProjectCache = "gtdphp.ubiquity.projects";

if (kDoLog) { CmdUtils.log(kver + " gtd-ubiquity initialising now"); }


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
    cache: false,
    data: aData,
    dataType: "xml",
    error: function gtd_ajaxerr() { displayMessage("Failed ajax call"); },
    success: aSucceed,
    type: 'post',
    url: kPath + "processItems.php"
  });
}


// ------------------------ noun: parent project
var noun_type_gtdparent = {
  _name: "gtdphp parent project",
  parentList: null,
  //'default': function gtdp_default() { return this.subsuggest('')[0]; },
  
  getParents: function gtd_getparents(aOnDone) {
    var self=this;
    if (kDoLog) { CmdUtils.log('off to get parents'); }
    jQuery.getJSON(kPath + "addon.php?addonid=ajax&action=list&url=sendJSON.inc.php&type=p",
                   function gtd_json(aParents) {
                     if (kDoLog) {CmdUtils.log('assigning parents');}
                     self.parentList = aParents;
                     Application.storage.set(kProjectCache, aParents);
                     if (aOnDone) { aOnDone(); }
                   });
  },

  subsuggest: function gtd_subsuggest(aText, aHtml) {
    if (kDoLog) { CmdUtils.log('in subsuggest searching for parent ' + aText); }
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
        if (kDoLog) { CmdUtils.log("got " + out.length + " async results"); }
      });
      return [];
    }
    return self.subsuggest(aText, aHtml);
  }

};


// --------------------- command object template
function makegtd(aObj) {
  var field, template = {
    homepage: "http://www.gtd-php.com/Developers/Ubiquity",
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


// ------ list next actions containing a particular search string
makegtd({
  name: "gtdlist",
  description: "Searches live next actions for a particular word or phrase",
  takes: { "search term": noun_arb_text },

  _filter: function gtdlist_filter(aNeedle) {
    return "type=a&nextonly=true&needle=" + encodeURIComponent(aNeedle.text);
  },

  _ajaxTimer: false,
  
  preview: function gtdlist_preview(aPblock, aNeedle) {
    var prompt, timer,
        that = this,
        filter = this._filter(aNeedle);
    if (aNeedle.text === "") {
      prompt = "Searching for all live next actions (enter more text to narrow down the search)";
      timer = 3000; // allow 3000ms (=3s) before AJAX call if there's no prompt string
    }
    else {
      prompt = "Searching for <i>" + aNeedle.text + "</i> ..."
      timer = 500; // 500ms should be long enough between key presses to lag JSON call reasonably, without undue delays or redundant calls
    }
    aPblock.innerHTML = prompt;
    if (this._ajaxTimer) { Utils.clearTimeout(this._ajaxTimer); }
    this._ajaxTimer = Utils.setTimeout(function gtdlist_doAjax() {
        jQuery.getJSON(
          kPath + "addon.php?addonid=ajax&action=getTable&url=sendJSON.inc.php&" +
                  filter,
          function gtd_json(json) {
            if (kDoLog) {CmdUtils.log("back with html table of actions");}
            var table,
                // the table needs some heavy CSS styling to look half-decent
                // TODO really needs more styling
                css = "<style>" +
                      "#gtdlist{} " +
                      "#gtdlist td.col-title {font-size:0.9em;} " +
                      "#gtdlist td.col-shortdesc,#gtdlist td.col-parent, " +
                        "#gtdlist th.col-checkbox {font-size:0.6em;} " +
                      "</style>",
                tablehtml=json.table;

            // change hrefs to include the correct base path
            tablehtml = tablehtml.replace(/(href\s*=\s*['""'])/gi,"$1"+kPath);

            // create jQuery object with the newly-generated table, and our CSS
            table = jQuery(css + "<table id='gtdlist' summary='next actions'>" +
                             tablehtml + "</table>");

            // if we've got an empty table, just say so, and quit
            if (!table.find("tbody tr").length) {
              table.empty().remove();
              aPblock.innerHTML = "Nothing found";
            }

            // remove all images from the table
            table.find('img').remove();

            // display the table of actions in the preview block
            jQuery(aPblock).empty().append(table);

            // add the AJAX trigger to the completion checkboxes
            table.find("td.col-checkbox :checkbox").change(function gtd_checkedBox(aEvent) {
              // checkbox can only be clicked once; it's just been clicked, so disable it now
              this.disabled = true;

              // this function will be executed on a checkbox when it is clicked
              if (kDoLog) { CmdUtils.log("Checkbox " + this.value + " clicked"); }

              // do AJAX call to mark item completed
              gtdDoAjax(
                { itemId: this.value, action: "complete" },
                function gtd_itemCompleted(xml, status) {
                  // back from AJAX call

                  var row = jQuery(aEvent.target).parents("tr"),   // this is the row we are dealing with:
                      thisTab = row.parents("table").eq(0);        // table to which the row belongs

                  // report the successful completion to the user
                  thisTab.parent().
                    append(jQuery("<br/><b>Completed:</b> <i>" +
                                  row.find("td.col-title").text() + "</i>"));

                  // item has been completed, so remove from table
                  row.remove();

                  if (!thisTab.find("tbody tr").length) {
                    if (kDoLog) { CmdUtils.log("last listed action completed"); }
                    // table is now empty, so remove it,
                    thisTab.remove();
                  }
                } // end of AJAX success call
              ); // end of call to gtdDoAjax
            }); // end of onChange function for checkboxes
          } // end of success function for JSON call to retrieve next actions
        ); // end of JSON call
      }, // end of _doAJAX function
      timer); // end of setTimeout call
  },

  
  execute: function gtdlist_execute(aNeedle) {
    /* everything goes on in preview,
     * so we don't really have an execute function to speak of:
     * just open a new tab showing the list of items we've been displaying.
     */
    Utils.openUrlInBrowser( kPath + "listItems.php?" + this._filter(aNeedle) );
  }
});


// ------ create a reference
makegtd({
  name: "gtdref",
  description: "Adds a reference to the current URL",
  help: "Adds a gtd-php reference to the current URL, or, " +
        "if a link is selected, to the destination of that link",

  modifiers: { title: noun_arb_text },
  
  takes: { parent: noun_type_gtdparent },

  _getDocHrefAndTitle: function gtdref_getDocHrefAndTitle(aMods) {
    try {
      var document = CmdUtils.getDocument(),
          currenturl = document.location.href,
          title = document.title;
      if (aMods.title.html) title = aMods.title.html;
      return { done: true, url: currenturl, title: title };
    } catch (err) {
      return { done: false, message: "Unable to get location or title for current page, so cannot create a GTD reference for it" };
    }
  },
  
  preview: function gtdref_preview(aPblock, aParent, aMods) {
    var docInfo = this._getDocHrefAndTitle(aMods);
    if (!docInfo.done) {
      aPblock.innerHTML = docInfo.message;
      return false;
    }
    aPblock.innerHTML = 'Creates a reference to this page as a child of "' +
      aParent.html + '", with a title of: "' + docInfo.title + '"';
    return true;
  },

  execute: function gtdref_exec(aParent, aMods) {
    var docInfo = this._getDocHrefAndTitle(aMods);
    if (!docInfo.done) {
      displayMessage(docInfo.message);
      return false;
    }

    gtdDoAjax(
      { action: "create",
        type: "r",
        parentId: aParent.data.itemId,
        title: docInfo.title,
        description: "webpage: <a href='" + docInfo.url  + "'>" + docInfo.title + "</a>"
      },
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


// --------------------- ubiquity utility to initialise list of projects
// initialize list of projects
noun_type_gtdparent.parentList = Application.storage.get(kProjectCache, null);
if (noun_type_gtdparent.parentList === null) {
  noun_type_gtdparent.getParents();
}