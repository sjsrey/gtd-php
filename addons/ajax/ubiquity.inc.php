<?php
    if (!headers_sent()) header('Content-Type: application/javascript; charset=utf-8');
    include_once 'gtdfuncs.inc.php';
?>
var gtdcommandlineversion="200810201000",
    gtdbasepath="<?php echo getAbsolutePath(); ?>",
    doLog=false;
//TODO cache parents list using Application.storage.set(gtdphp.ubiquity.projectlist)p
function gtdSetLastResult(xml) {
    var newid=jQuery(xml).find("itemId").text();
    CmdUtils.setLastResult('<a href="'+gtdbasepath+'itemReport.php?itemId='+
            newid+'">'+jQuery(xml).find("title").text()+'</a>');
    return newid;
}
//===========================================================
var noun_type_gtdparent = {
  _name: "gtdphp parent project",
  parentList:null,
  //---------------------------------------------------------
  subsuggest: function(text, html) {
    if (doLog) {CmdUtils.log('in subsuggest searching for parent '+text);}
    var id,title,teststring,
        suggestions=[],
        i=4;
    if (this.parentList==={}) {
        suggestions.push(CmdUtils.makeSugg('Still awaiting list of parents'));
    } else for (id in noun_type_gtdparent.parentList) {
      if (text==='') {text='.';}
      teststring=new RegExp(text,"i");
      title=noun_type_gtdparent.parentList[id];
      if (teststring.test(title)) {
    	suggestions.push(CmdUtils.makeSugg(title,
            "<a href='"+gtdbasepath+"itemReport.php?itemId="+id+"'>"+title+"</a>",
            {itemId:id}));
    	if (!i--) break;
      }
    }
    return suggestions;
  },
  //---------------------------------------------------------
  suggest: function( text, html, callback ) {
    var self=this;
    if (self.parentList === null) {
        self.parentList={}; // empty the array first, to ensure we only issue the JSON request once
        self._callback=callback;
        if (doLog) {CmdUtils.log('off to get parents in v'+gtdcommandlineversion);}
        jQuery.getJSON(
            gtdbasepath+'addon.php?addonid=ajax&url=sendJSON.php&type=p',
            function(parents) {
                if (doLog) {CmdUtils.log('assigning parents');}
                self.parentList=parents;
                if (doLog) {CmdUtils.log('on callback searching for parent '+text);}
                var out=self.subsuggest(text, html);
                if (doLog) {CmdUtils.log('got '+out.length+' results');}
                for (sug in out) {self._callback(out[sug]);}
            });
        return [];
    }
    return self.subsuggest(text, html);
  }
  //---------------------------------------------------------
};
//===========================================================
CmdUtils.CreateCommand({
  name: "gtdin",
  homepage: "http://www.gtd-php.com/Developers/Ubiquity",
  author: { name: "Andrew Smith"},
  contributors: ["Andrew Smith"],
  license: "GPL",
  description: "Adds a GTD inbox item",
  help: "Provide item title for a new GTD inbox item",
  takes: {"item title": noun_arb_text},
  //---------------------------------------------------------
  preview: function( pblock,title ) {
    pblock.innerHTML = 'Creates inbox item with title: "<i>'+title.text+'"</i>"';
  },
  //---------------------------------------------------------
  execute: function(title) {
    //var itemurl=", <a href='"+gtdbasepath+"item.php?itemId=";
    var postdata={
      action:"create",type:"i",output:"xml",
      fromajax:"true",title:title.text};
    jQuery.ajax({
      type:'post',
      url:gtdbasepath+"processItems.php",
      data:postdata,
      error: function() {displayMessage("Failed ajax call");},
      success:function(xml,text){
        displayMessage("Inbox item created with id: "+
            gtdSetLastResult(xml)+", title: "+title.text);
      },
      dataType:"xml"
    });
  }
  //---------------------------------------------------------
});
//===========================================================
CmdUtils.CreateCommand({
  name: "gtdref",
  homepage: "http://www.gtd-php.com/Developers/Ubiquity",
  author: { name: "Andrew Smith"},
  contributors: ["Andrew Smith"],
  license: "GPL",
  description: "Adds a reference to the current URL",
  help: "Adds a gtd-php reference to the current URL, or,"+
        "if a link is selected, to the destination of that link",

  takes: {"parent": noun_type_gtdparent},
  //---------------------------------------------------------
  preview: function( pblock,parent) {
    try {
        var document=Application.activeWindow.activeTab.document;
    } catch (err) {
        pblock.innerHTML="Unable to get location or title for current page, so cannot create a GTD reference for it";
        return false;
    }
    pblock.innerHTML= 'Creates a reference to this page as a child of: "'+
        parent.html+'"';
  },
  //---------------------------------------------------------
  execute: function(parent) {
    try {
        var document=Application.activeWindow.activeTab.document,
            currenturl=document.location.href,
            title=document.title;
    } catch (err) {
        displayMessage("Unable to get this page's title or URL, so failed to create a reference to it");
        return false;
    }
    var postdata={
      action:"create",type:"r",output:"xml",fromajax:"true",
      parentId:parent.data.itemId,
      title:title,
      description:"webpage: <a href='"+currenturl+"'>"+title+"</a>"
    };

    jQuery.ajax({
      type:'post',
      url:gtdbasepath+"processItems.php",
      data:postdata,
      error: function() {displayMessage("Failed ajax call");},
      success:function(xml,text){
        displayMessage("Reference created with id: "+
            gtdSetLastResult(xml));
      },
      dataType:"xml"
    });
  }
  //---------------------------------------------------------
});
//===========================================================
CmdUtils.CreateCommand({
  name: "gtdna",
  homepage: "http://www.gtd-php.com/Developers/Ubiquity",
  author: { name: "Andrew Smith"},
  contributors: ["Andrew Smith"],
  license: "GPL",
  description: "Adds a next action to gtd-php",
  help: "Adds a next action",
  takes: {title: noun_arb_text},
  modifiers: {parent:noun_type_gtdparent},
  //---------------------------------------------------------
  preview: function( pblock,title,mods) {
    pblock.innerHTML = 'Creates a next action with title: "'+
      title.text+'" as a child of the item: '+mods.parent.html;
  },
  //---------------------------------------------------------
  execute: function(title,mods) {
    var postdata={
      action:"create",type:"a",output:"xml",fromajax:"true",nextaction:'y',
      parentId:mods.parent.data.itemId,
      title:title.text
    };

    jQuery.ajax({
      type:'post',
      url:gtdbasepath+"processItems.php",
      data:postdata,
      error: function() {displayMessage("Failed ajax call");},
      success:function(xml,text){
        displayMessage("Next action created with id: "+
            gtdSetLastResult(xml));
      },
      dataType:"xml"
    });
  }
  //---------------------------------------------------------
});
//===========================================================
