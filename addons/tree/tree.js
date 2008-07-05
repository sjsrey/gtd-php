/*jslint browser: true, eqeqeq: true, nomen: true, undef: true */
/*global GTD,jQuery,escape,unescape,$NBtheseTagsAreForJSLint */
(function($){
    var spanclicked,dismisspopup,thisId;
    /*
        ------------------------------------------------------------------------
    */
    function popupkeypress(e) {
        /*TODO - would be nice to do some keyboard stuff for the popup menu.
            Either shortcut keys for each option, or up/down/enter to move selection
        */
        if (e.keyCode===27) { //escape
            dismisspopup();
            return false;
        }
    }
    /*
        ------------------------------------------------------------------------
    */
    dismisspopup=function () {
        if (spanclicked!=null) {
            spanclicked.removeClass('activated');
            $('#treepopup').hide();
            $(document).
                unbind('keypress',popupkeypress).
                unbind('click',dismisspopup);
            spanclicked=null;
        }
    };
    /*
        ------------------------------------------------------------------------
    */
    function treeclicked(e) {
        // the user has clicked somewhere on the tree - work out where, and act accordingly
        var tag,clicked;
        clicked=$(e.target);
        tag=e.target.tagName;
        if (spanclicked!=null) {dismisspopup();}
        switch (tag) {
            //------------------------------------------------------------------
            case 'SPAN': // user has clicked on a title - if we can expand/contract it, do so
                if (clicked.parent().hasClass('treeexpand')) {
                    clicked.
                        nextAll('ul').toggleClass('treehid'). // toggle visibility of child items
                        end().
                        parent().toggleClass('treecollapse'); // toggle class which displays the + sign
                }
                return false; // have dealt with click, so stop propagation
            //------------------------------------------------------------------
            case 'IMG': // user has clicked on the action icon
                spanclicked=clicked.parent().children('span');
                spanclicked.addClass('activated');
                thisId=clicked.siblings('input[name=id]').val();
                $('#treepopup').
                    find('a[href]').
                        each(function(){
                            this.href=this.href.replace(/itemId=[0-9]+/,"itemId="+thisId);
                        }).
                        end().
                    show().
                    css({top:(10+e.pageY)+"px",left:(10+e.pageX)+"px"});
                $(document).
                    bind('keypress',popupkeypress).
                    bind('click',dismisspopup);
                return false;  // have dealt with click, so stop propagation
            //------------------------------------------------------------------
/*            default:
                alert(tag);
                break;
*/        }
    }
    /*
        ------------------------------------------------------------------------
    */
    GTD.tree={
        //------------------------------------------------------------------
        expand:function (box,type) {
            // user wishes to toggle display of all (check)list items
            if (box.checked) {
                $('.tree'+type).
                    removeClass('treecollapse').
                    children('ul').removeClass('treehid');
            } else {
                $('.tree'+type+'.treeexpand').
                    addClass('treecollapse').
                    children('ul').addClass('treehid');
            }
            return true;
        },
        //------------------------------------------------------------------
        prune:function() {
            var oktodelete,countdesc,form,message,allDescendants,nexturl,
                id=thisId,
                saveclicked=spanclicked.parent('li');
                
            dismisspopup();

            // count number of items that would be deleted, and highlight block to be deleted
            allDescendants=saveclicked.
                addClass('activated').
                find('li').
                andSelf();
            countdesc=allDescendants.length-1;

            // get confirmation from the user
            message='Delete this item';
            if (countdesc) {
                message=message+' and its '+countdesc+' descendant';
                if (countdesc>1) {message=message+'s';}
                message=message+'?';
            }
            oktodelete=confirm(message);
            saveclicked.removeClass('activated');
            if (oktodelete) {
                form=$('#pruningform');
                allDescendants.each(function() {
                    form.append("'<input type='hidden' name='isMarked[]' value='"+
                        $(this).children('input[name=id]').val()+
                        "' />");
                });
                nexturl='index.php';
                $('input[name=referrer]',form).val(nexturl);
                $('#pruningform').submit();
            }
            return false;
        },
        //------------------------------------------------------------------
        show:function (type) {
            // user has changed which part of the hierarchy will be displayed
            var which='';
            switch (type) { // NB each case deliberately flows through into the next, so the "which" variable accumulates
                case 'a': // display all actions, inbox, waiting-on, references
                    which=which+'.treea,.treei,.treew,.treer,'; // deliberately flows into next case
                case 'p':
                    which=which+'.treep,'; // deliberately flows into next case
                case 'g': // goals
                    which=which+'.treeg,'; // deliberately flows into next case
                case 'o':
                    which=which+'.treeo,'; // deliberately flows into next case
                case 'v':
                    which=which+'.treev,'; // deliberately flows into next case
                case 'm':
                    which=which+'.treem,.treeC,.treeL,.treeT';
            }
            $('#trees li').
                filter(':not('+which+')').addClass('treehid'). // hide items NOT listed in the "which" variable
                end().
                filter(which).removeClass('treehid'); // show items that ARE listed in the "which" variable
            return true;
        },
        //------------------------------------------------------------------
        showdone:function(box) {
            // user is toggling display of completed items
            if (box.checked) {
                $('span.treedone').parent().removeClass('donehid');
            } else {
                $('span.treedone').parent().addClass('donehid');
            }
        }
        //------------------------------------------------------------------
    };
    /*
        ------------------------------------------------------------------------
    */
    $(document).ready(function() {      // when doc is loaded,
        $('#trees').click(treeclicked); //   set the event-handler for clicking somewhere on the tree - the other events are set in html
    });
})(jQuery);
