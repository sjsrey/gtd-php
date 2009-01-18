/*jslint browser: true, eqeqeq: true, nomen: true, undef: true */
/*global GTD,jQuery,escape,unescape,$NBtheseTagsAreForJSLint */
(function tree_js($){
  var spanclicked = null, dismisspopup, thisId;
  /*
      ------------------------------------------------------------------------
  */
  function popupkeypress(e) {
    /*TODO - would be nice to do some keyboard stuff for the popup menu.
        Either shortcut keys for each option, or up/down/enter to move selection
    */
    if (e.keyCode === 27) { //escape
      dismisspopup();
      return false;
    }
  }
  /*
      ------------------------------------------------------------------------
  */
  dismisspopup = function dismisspopup() {
    if (spanclicked !== null) {
      spanclicked.removeClass('activated');
      $('#treepopup').hide();
      $(document).unbind('keydown', popupkeypress).
                  unbind('click', dismisspopup);
      spanclicked=null;
    }
  };
  /*
      ------------------------------------------------------------------------
  */
  function treeCleanNeeded() {
    return $("#categoryselect").val() || $("#contextselect").val();
  }
  /*
      ------------------------------------------------------------------------
  */
  function cleanTree(force) {
  
    $('body').css("cursor","wait");
    if (force || treeCleanNeeded()) {
      $("#trees").find(".allchildrenhid,.hidbutwith").
                  removeClass("allchildrenhid hidbutwith");
    }
  }
  /*
      ------------------------------------------------------------------------
  */
  function makeTreeSane() {
  
    if (treeCleanNeeded()) {
      $("#trees li").
        filter(":has(li:visible>span:visible)"). // find any items that have visible children,
          children("span:hidden").               // but would otherwise be hidden
            addClass("hidbutwith").              // and add a class to ensure they're visible but with greyed title
          end().
        end().
        filter(":visible").                      // then find visible rows
          children("span:hidden").               // for which the title is still hidden
            parent().
              addClass("allchildrenhid");        // and hide the row itself
    }
    
    $('body').css("cursor","auto");
  }
  /*
      ------------------------------------------------------------------------
  */
  function treeClicked(e) {
    // the user has clicked somewhere on the tree - work out where, and act accordingly
    var tag, clicked;
    clicked = $(e.target);
    tag = e.target.tagName.toLowerCase();
    if (spanclicked !== null) { dismisspopup(); }
    switch (tag) {
      //------------------------------------------------------------------
      case 'span': // user has clicked on a title - if we can expand/contract it, do so
        if (clicked.parent().hasClass('treeexpand')) {
          clicked.
            nextAll('ul').
              toggleClass('treehid'). // toggle visibility of child items
            end().
            parent().
              toggleClass('treecollapse'); // toggle class which displays the + sign
        }
        return false; // have dealt with click, so stop propagation
      //------------------------------------------------------------------
      case 'img': // user has clicked on the action icon
        spanclicked = clicked.parent().children('span');
        spanclicked.addClass('activated');
        thisId = clicked.siblings('input[name=id]').val();
        $('#treepopup').
          find('a[href]').
            each(function setPopupHrefs(){
              this.href = this.href.replace(/itemId=[0-9]+/, "itemId=" + thisId);
            }).
          end().
          show().
          css({ top: (10 + e.pageY) + "px", left: (10 + e.pageX) + "px" });
        $(document).bind('keydown', popupkeypress).
                    bind('click', dismisspopup);
        return false;  // have dealt with click, so stop propagation
      //------------------------------------------------------------------
/*      default:
          alert(tag);
          break;
*/
    } // end of switch
  }
  /*
      ------------------------------------------------------------------------
  */
  function treeShow(direction, type) {
    // user has changed which part of the hierarchy will be displayed

    cleanTree();

    setTimeout(function(){ // forces a yield, as this operation may take some time

      var thisNdx, thisType,
          which = "",
          from = GTD.tree.types[0],
          to = $("[name=showLiveTo]:checked").val(),
          inRange = false;

      // live tree re-display only happens when changing "show to"
      for (thisNdx in GTD.tree.types) {
        thisType = GTD.tree.types[thisNdx];
        if (from === thisType) { inRange = true; }
        if (inRange) {
          if (thisType === "p") {
            which = which + ".treeC,.treeL,.treeT,";
          }
          else if (thisType === 'a') {
            which = which + ".treei,.treew,.treer,";
          }
          which = which + ".tree" + thisType + ",";
        }
        if (to === thisType) { break; }
      }

      $("#trees li").
          not(which).addClass("treehid").       // hide items NOT listed in the "which" variable
          end().
          filter(which).removeClass("treehid"); // show items that ARE listed in the "which" variable

      makeTreeSane();
    }, 1);

    return true;
  }
  /*
      ------------------------------------------------------------------------
  */
GTD.tree = {

    //------------------------------------------------------------------
    dofilter: function tree_dofilter(sender) {
      var where = sender.id,
          what = $(sender).val(),
          prefix = (where === "categoryselect") ? "category" : "context",
          tree = $("#trees"),
          treeitems = tree.find("li"),
          filter = "." + prefix + what,
          filterhid = prefix + "hid";

      cleanTree(true);
      tree.find("." + filterhid).removeClass(filterhid);

      if (what !== "0") { // user has selected one specific category or context
          // add the hiding-class to items outside this category/context
        treeitems.not(filter).
          children("span").
            addClass(filterhid);
      }
      makeTreeSane();
    },

    //------------------------------------------------------------------
    expand: function tree_expand(box, type) {
        // user wishes to toggle display of all (check)list items
      cleanTree();
      if (box.checked) {
        $(".tree" + type).removeClass("treecollapse").
                          children("ul").removeClass("treehid");
      }
      else {
        $(".tree" + type + ".treeexpand").addClass("treecollapse").
                                          children("ul").addClass("treehid");
      }
      makeTreeSane();
      return true;
    },

    //------------------------------------------------------------------
    init: function tree_init(types) {
      this.types = types;
      $('#treepopup').css("display", "none").removeClass('hidden');
      $("#trees").click(treeClicked); //   set the event-handler for clicking somewhere on the tree - the other events are set in html
      $("[name=showLiveTo]").click(function showTo() {
        treeShow("to", this.value);
      });
      $("#liveTreeOptions").removeClass("invisible");
    },

    //------------------------------------------------------------------
    prune: function tree_prune() {
      var oktodelete, countdesc, form, message, allDescendants, nexturl,
          saveclicked = spanclicked.parent("li");
          
      dismisspopup();
      cleanTree();

      // count number of items that would be deleted, and highlight block to be deleted
      allDescendants = saveclicked.addClass("activated").find("li").
                                   andSelf();
      countdesc = allDescendants.length - 1;

      // get confirmation from the user
      message = "Delete this item";
      if (countdesc) {
        message = message + " and its " + countdesc + " descendant";
        if (countdesc > 1) { message = message + "s"; }
        message = message + "?";
      }
      oktodelete = confirm(message);
      saveclicked.removeClass("activated");
      if (oktodelete) {
        form = $("#pruningform");
        allDescendants.each(function prune_makeform() {
          form.append("'<input type='hidden' name='isMarked[]' value='" +
                $(this).children("input[name=id]").val() +
                "' />");
        });
        nexturl = "index.php";
        $("input[name=referrer]",form).val(nexturl);
        $("#pruningform").submit();
      }
      makeTreeSane();
      return false;
    },

    //------------------------------------------------------------------
    showDone: function tree_showdone(box) {
      cleanTree();
      // user is toggling display of completed items
      if (box.checked) {
        $("span.treedone").parent().removeClass("donehid");
      }
      else {
        $("span.treedone").parent().addClass("donehid");
      }
      makeTreeSane();
    },

    //------------------------------------------------------------------
    showSomeday: function tree_showsomeday(box) {
      cleanTree();
      // user is toggling display of someday items
      if (box.checked) {
        $("span.someday").parent().removeClass("somedayhid");
      }
      else {
        $("span.someday").parent().addClass("somedayhid");
      }
      makeTreeSane();
      },

    //------------------------------------------------------------------
    toggleOptions: function tree_toggleOptions(extended) {
      $("#liveTreeOptions,#extendedTreeOptions").toggleClass("hidden");
    },
    
    //------------------------------------------------------------------
    showNext: function tree_shownext(box) {
      cleanTree();
      // user is toggling display of non-next actions
      if (box.checked) {
        $("span.treenotNA").parent().addClass("nexthid");
      }
      else {
        $("span.treenotNA").parent().removeClass("nexthid");
      }
      makeTreeSane();
    }
    //------------------------------------------------------------------
    
  };
})(jQuery);
