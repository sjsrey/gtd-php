/*jslint browser: true, eqeqeq: true, nomen: true, undef: true */
/*global GTD,jQuery,escape,unescape,$NBtheseTagsAreForJSLint */
if (!window.GTD.ajaxfuncs) {(function($) { // // wrap the lot in an anonymous function
// ======================================================================================
GTD.ajaxfuncs=true; // simple boolean flag to show that this javascript file has been run
// ======================================================================================
var editor=[],onajax=false,hiddencontexts=[],NAclicked;
// ======================================================================================
$.fn.animateShow=function() {
/*
 * this function does the animation for items which have successfully been updated via AJAX
 * It works by extending jQuery, so that we can execute the function on any jQuery object
 */
    var that=this;
    var disp=(!$.browser.msie && this.get(0).tagName.toLowerCase()==='tr')?'table-row':'block';
    this.css({opacity:0,display:disp}).
        removeClass('togglehidden hidden').
        addClass('ajaxupdated').
        animate({opacity:1},600).
        show();
    setTimeout(function () {that.removeClass("ajaxupdated");},2000);
    // always return the jQuery item we came in with, to allow chaining
    return that;
};

// ======================================================================================
function messagepopup(text,xmldata,top,left) {
    var sep='',msgbox;
    if (typeof text==='object') {
        msgbox=text;
    } else {
        msgbox=$(document.createElement('div')). // message box for message(s) returned via AJAX
            addClass('success').
            appendTo($('body')).
            text(text).
            css({
                display:'none',                         
                position:'absolute',
                top:top,
                left:left,
                zIndex:2000
            });
    }
    $('gtdphp result line',xmldata).
        each(function() {
            msgbox.append(sep+$(this).text());
            sep='<br/>';
        });
    msgbox.animateShow();
    return msgbox;
}
// ======================================================================================
function Live_field(source,inputtype,savefunc,resetfunc,expandfunc) {
/*
 * constructor to create an input field for editing an item, embedded in a table.
 *  One editing-row will typioally consist of several Live_fields
 *
 * source:    the DOM object into which the editor should be placed, and whose value it edits
 * inputtype: indicates whether this field is a TEXT, TEXTAREA, or SAVECANCEL i.e. the SAVE/CANCEL/EXPAND buttons
 * savefunc: function to be executed when the field is being saved
 * resetfunc: function to be executed if the user cancels / resets rather than saving
 * expandfunc: function to be executed if the user requests a form to edit the entire item
 */
    var a1, a2,a3,that=this,jsource=$(source),width,height,old;
    width=source.clientWidth;
    height=source.clientHeight;
    old=jsource.clone(true);    // keep a backup of the source object as it was when we create the editing field

    function setWidth(px) {
        return Math.round(8.001+(px-that.baseWidth)/that.em);
    }

    this.Set=function(save) { // function to save or reset this particular field
        jsource.replaceWith(old);
        if (save!==false) {
            switch(inputtype) {
                //--------------------------------------------------
                case 'text':
                    $(old.get(0).childNodes).
                        filter(function() {
                            return (this.textContent!=='');
                        }).
                        text(save);
                    break;
                //--------------------------------------------------
                case 'textarea':
                    old.html('');
                    old.append(save);
                    break;
                //--------------------------------------------------
            }
        }
    };

    jsource.empty();
    switch(inputtype) {
        case 'saveCancel':  // creating SAVE/CANCEL/EXPAND butttons
            a1=document.createElement('img');
            a1.className='add';
            a1.src=GTD.ajax.dir+'save.gif';
            a1.alt='save changes';
            a1.title='save';
            a1.id='savebutton';
            $(a1).click(savefunc);

            a2=document.createElement('span');
            a2.title='cancel';
            a2.appendChild(document.createTextNode('X'));
            a2.className='ajaxcancel';
            $(a2).click(resetfunc);

            source.appendChild(a1);
            source.appendChild(document.createTextNode(' '));
            source.appendChild(a2);
            if (expandfunc!==undefined) {
                a3=document.createElement('span');
                a3.title='expand';
                a3.appendChild(document.createTextNode('+'));
                a3.id='ajaxexpand';
                $(a3).click(expandfunc);
                source.appendChild(document.createTextNode(' '));
                source.appendChild(a3);
            }
            break;
            //--------------------------------------------------
        case 'text':        // creating a standard TEXT input
            this.field=document.createElement('input');
            this.field.type='text';
            source.appendChild(this.field);

            if (this.em===-1) { // calculate em width by changing size from 10 cols to 11
                this.field.size=10;
                this.baseWidth=Live_field.prototype.baseWidth=this.field.clientWidth;
                this.field.size++;
                this.em=Live_field.prototype.em=this.field.clientWidth-this.baseWidth;
            }

            this.field.size=setWidth(width);
            this.field.value=old.text();
            break;
            //--------------------------------------------------
        case 'textarea':    // creating a TEXTAREA
            this.field=document.createElement(inputtype);
            source.appendChild(this.field);

            if (this.em===-1) { // calculate em width by changing size from 10 cols to 11
                this.field.cols=10;
                this.baseWidth=Live_field.prototype.baseWidth=this.field.clientWidth;
                this.field.cols++;
                this.em=Live_field.prototype.em=this.field.clientWidth-this.baseWidth;
                this.field.rows=1;
                this.baseHeight=Live_field.prototype.baseHeight=this.field.clientHeight;
                this.field.rows++;
                this.lineHeight=Live_field.prototype.lineHeight=this.field.clientHeight-this.baseHeight;
            }
            this.field.cols=setWidth(width); // then dividing available width by em width
            this.field.rows=Math.round(1.001+(height-that.baseHeight)/that.lineHeight+($.browser.mozilla?0:1));
            var txt=old.html();
            $(this.field).text(txt.replace(/<br *\/*>/gi,''));//dummy closure for PSPad: */
            break;
            //--------------------------------------------------
        default:
            break;
            //--------------------------------------------------
    }
    return false;
}
// -----------------------------------------------------------------------------
Live_field.prototype.em=-1;
Live_field.prototype.baseWidth=-1;
// ======================================================================================
function updateItem(row,xmldata) {
/* this function is called when we return from an AJAX call successfully.
 * Now we update the display of the edited item.
 *
 * row: the DOM object of the row being edited
 * xmldata: an XML object containing the returned AJAX data
 */
    var i,max,oldId,newId,done,fields,newval,newvalues,donedate,xmlfields,rowclasses,selects;
    row=$(row);
    oldId=row.find('input[name=id]').val();
    newvalues=$('gtdphp values',xmldata);
    newId=newvalues.children('newitemId').text();
    donedate=newvalues.children('dateCompleted').text();
    done= (donedate !== "NULL" && donedate !== "");
    if (newId!=='' && newId!==oldId) { // got a new ID, either from a recurred item or a newly created one
        // if item has been recurred into a new itemID, then clone the row
        if (oldId==='0') {
            // we have just created an item: if there's an NA checkbox, add a click event to it
            row.find('.col-NA :checkbox').
                click(NAclicked);
        } else {
            row.clone().
                insertAfter(row).
                removeClass('inajax onajaxcall').
                find(':checkbox').
                    attr('disabled',true).
                    unbind().
                    end().
                find('.col-lastModified').
                    text( newvalues.children('lastModified').text() ).
                    end().
                find('.col-dateCompleted').
                    text( newvalues.children('oldDateCompleted').text() ).
                    end().
                find('.col-ajax img').
                    hide().
                    end().
                animateShow();
        }

        // update some fields to reflect the new ID
        row.
            find('[name=itemId],input[name=id],[name="isNAs[]"]:checkbox,[name="isMarked[]"]:checkbox').
                val(newId).
                end().
            find('a[href]').                      // amend the newId in all href links, too, where of the form itemId=NNN
                each(function(){
                    this.href=this.href.replace('itemId='+oldId,'itemId='+newId);
                }).
                end().
            children('.col-dateCreated').
                text(newvalues.children('dateCreated'));
    }

    row.find('[name="isMarked[]"]:checkbox').
        attr('checked',done);

    fields=['deadline','dateCompleted','tickledate','dateCreated','lastModified'];
    max=fields.length;
    for (i=0;i<max;i++) {
        newval=newvalues.children(fields[i]);
        if (newval.length) {
            row.find('.col-'+fields[i]).text(newval.text());
        }
    }
    if ((newval=newvalues.children('tagname')).length) {
        row.find('.col-tags').text(newval.text());
    }
    
    xmlfields=['contextid','categoryid','timeframeid'];
    rowclasses=['context','category','timeframe'];
    selects=['space','category','time'];
    for (i=0;i<3;i++) {
        if ((newval=newvalues.children(xmlfields[i])).length) {
            row.find('.col-'+rowclasses[i]).text(
                $('#multi'+selects[i]+' option[value='+newval.text()+']').text()
            );
        }
    }
    // if it's not a checklist, and item is completed, disable the checkboxes, to prevent further AJAXing of this item which might create a clash
    if (done && $('input[name=ptype]').val()!=='C') {
        row.find(':checkbox').
            unbind().
            attr('disabled',true);
    }
    row.removeClass('inajax onajaxcall').
        animateShow();
}
// ======================================================================================
function doAJAXupdate(thisnode,overlay) {
/*
 *  function to do the AJAX call to update an item
 *
 * thisnode: the DOM node of any element on the row being edited
 * overlay: an object containing key:value pairs to be sent to the server as data
 */
    var node,data,row;
    row=$(thisnode).parents('tr').get(0);
    if ($(row).hasClass('inajax')) {return false;}
    $(row).addClass('inajax onajaxcall').animate({opacity:0.1},100);
    node=thisnode;
    data={
        itemId      : node.value,
        output      : 'xml'
    };
    $.extend(data,overlay);
    $.ajax({
        cache:false,
        data:data,
        dataType:'xml',
        error:function (arg1,arg2,arg3) {
            $('#debuglog').empty().text(arg1.responseText);
            $(row).removeClass('inajax onajaxcall');
        },
        success:function (xmldata, textStatus) {
            $('#debuglog').
                empty().
                append($('gtdphp log',xmldata).text());     // dump debug data if present
            updateItem(row,xmldata);                        // update the row with the new data
            var rowpos=$(row).position();                   // get the screen position of the row we operated on
            messagepopup('',xmldata,rowpos.top,rowpos.left).// show success message over that row
                animate({left:rowpos.left},5000).           // wait for 5 seconds
                queue(function(){
                    if (data.action==='delete') {           // if we were deleting, 
                        $(row).remove();                    // remove the rows from the table
                    }
                    $(this).remove().dequeue();             // and then disappear
                });
        },
        type:'POST',
        url:'processItems.php'
    });
    return true;
}

NAclicked=function (event) {
    doAJAXupdate(this,{action: this.checked?'makeNA':'removeNA'});
};

// ======================================================================================
function itemEditor(row) {
/*
 * constructor to create editing fields for item title/desc/outcome
 * row: the DOM object of the row where the item to be edited, is displayed
 */
    this.ended=true;
    if ($(row).hasClass('inajax')) {return false;}
    var that=this,gotfullitemform,itemId,expandme,newdiv,tdtitle,tddesc,tdsave,
        tdoutcome,namefield,descfield,outcomefield,iconfield,thisurl,
        unoccupiedcells,max,i,checkfields=['isSomeday','nextAction'];
    this.ended=false;
    $(row).addClass('inajax').
        find('.col-ajax img,:checkbox').
            hide().
        end().find('a[href]').
            click(function(){
                return false;
            });
    itemId=$('input[name=id]',row).val() || '0';
    gotfullitemform=false;
    expandme=false;
    newdiv=document.createElement('div');
    $(newdiv).
        addClass('ajaxform hidden').
        appendTo(document.body);
    tdtitle=$('.col-title',row).get(0);
    tddesc=$('.col-description',row).get(0);
    tdoutcome=$('.col-desiredOutcome',row).get(0);

    unoccupiedcells=[];
    max=$('td',row).length;
    for (i=0;i<max;i++) {unoccupiedcells[i]=true;}
    if (tddesc) {
        descfield=new Live_field(tddesc,'textarea');
        unoccupiedcells[$('td',row).index(tddesc)]=false;
        descfield.field.focus();
    } else {descfield=null;}
    if (tdoutcome) {
        outcomefield=new Live_field(tdoutcome,'textarea');
        unoccupiedcells[$('td',row).index(tdoutcome)]=false;
        outcomefield.field.focus();
    } else {outcomefield=null;}
    if (tdtitle) {
        namefield=new Live_field(tdtitle,'text');
        unoccupiedcells[$('td',row).index(tdtitle)]=false;
        namefield.field.focus();
    } else {namefield=null;}
    tdsave=$('td.col-ajax',row).get(0);
    // --------------------------------------------------------
    this.cancelFull=function() {
        that.reset();
        GTD.freeze(false);
    };
    // --------------------------------------------------------
    this.saveFull=function() { // TOFIX - javascript is doing odd things to £ signs
        var mydata,form=$('form',newdiv).get(0);
        // first, validate the form
        if (!GTD.validate(form)) {return false;}
        mydata = $(form).serialize() + '&output=xml&fromjavascript=true';
        // freeze the form to prevent any edits while we're out on AJAX
        $('*',newdiv).unbind().attr('disabled',true);
        // send form to processItems.php in AJAX call
        $.ajax({
            cache:false,
            data:mydata,
            dataType:'xml',
            error:function (arg1,arg2,arg3) {
                window.status='Failed full update: '+arg2;
                $('#debuglog').empty().text(arg1.responseText);
                that.cancelFull();
            },
            success:function (xmldata, textStatus) {
                var results,descback,outback,title,fields,thisSelect,thisfld;
                results =$('gtdphp values',xmldata);
                descback=$('description',results).text();
                outback =$('desiredOutcome',results).text();
                title  =$('title',results).text();
                if (namefield) {
                    namefield.Set(title);
                } else {
                    $('.col-title',row).text(title);
                }
                if (descfield) {
                    descfield.Set(descback);
                } else {
                    $('.col-shortdesc',row).text(descback);
                }
                if (outcomefield) {
                    outcomefield.Set(outback);
                } else {
                    $('.col-shortoutcome',row).text(outback);
                }

                for (i=0;i<checkfields.length;i++) {
                    $('[name="'+checkfields[i]+'"]:checkbox',row).attr('checked',$(checkfields[i],results).text()==='y');
                }
                // grab the text for each category name from the select boxes in the form
                fields=['context','category','timeframe'];
                for (i=0;i<fields.length;i++) {
                    thisSelect=$('select[name='+fields[i]+'Id]',newdiv);
                    if (thisSelect.length) {
                        thisfld=$('.col-'+fields[i],row);
                        thisfld.text(thisSelect.get(0).options[thisSelect.get(0).selectedIndex].text);
                    }
                }
                $('[name="isNAs[]"]:checkbox',row).attr('checked',$('nextaction',results).text()==='y');
                $('#debuglog').empty().append($('gtdphp log',xmldata).text());
                GTD.freeze(false);
                that.tidyUp();
                updateItem(row,xmldata);
                window.status=$('gtdphp result line',xmldata).text();
            },
            type:'POST',
            url:'processItems.php'
        });
    };
    // --------------------------------------------------------
    function fillfullform() {
        if (namefield!==null) {$('[name=title]',newdiv).val(namefield.field.value);}
        if (descfield!==null) {$('[name=description]',newdiv).val(descfield.field.value);}
        if (outcomefield!==null) {$('[name=desiredOutcome]',newdiv).val(outcomefield.field.value);}
        if (itemId==='0') {$('[name=action]',newdiv).val('create');}
    }
    // --------------------------------------------------------
    function showfullform() {
        var cancelbtn,savebtn;
        $('*',row).unbind('keypress');
        fillfullform();
        $(newdiv).
            removeClass('hidden').
            css('top',window.scrollY+5);
            
        GTD.initcalendar(newdiv);
        // assign save and cancel functions to buttons
        cancelbtn=document.createElement('span');
        cancelbtn.appendChild(document.createTextNode('x'));
        cancelbtn.className='ajaxcancel';
        cancelbtn.title='Cancel changes';
        $(cancelbtn).click(that.cancelFull);

        savebtn=document.createElement('img');
        savebtn.src=GTD.ajax.dir+'save.gif';
        savebtn.id='ajaxsave';
        savebtn.alt='save item';
        savebtn.title='Save changes';
        $(savebtn).click(that.saveFull);
        $('#errorbox').prepend(cancelbtn);
        $('#errorbox').prepend(savebtn);
        $('*',newdiv).keypress(function(e){  // TODO consider splitting out into a central key-handler
            if (e.keyCode===27) {
                that.cancelFull();
                return false;
            }
            return true;
        });
    }
    // --------------------------------------------------------
    this.expand=function() {
        GTD.freeze(true);
        if (gotfullitemform) {
            showfullform();
        } else {
            expandme=true;
        }
    };
    // --------------------------------------------------------
    this.tidyUp=function () {
        $(row).
            removeClass('inajax onajaxcall').
            find('*').
                unbind('keypress').
                filter('a[href]').unbind('click').end().
                filter(':checkbox,img').show();
        iconfield.Set(false);
        $(newdiv).remove();
        newdiv=null;
        this.ended=true;
    };
    // --------------------------------------------------------
    this.save=function() {
        var mydata;
        $(row).animate({opacity:0.1},100).addClass('onajaxcall').click(false);
        if (itemId==='0') {
            // populate the form, and submit that
            fillfullform();
            return that.saveFull();
        } else {
            mydata={output:'xml',
                    fromjavascript:true,
                    action:'updateText',
                    itemId:itemId};
        }
        if (namefield) {mydata.title=namefield.field.value;}
        if (descfield) {mydata.description=descfield.field.value;}
        if (outcomefield) {mydata.desiredOutcome=outcomefield.field.value;}

        // do Ajax Call
        $.ajax({
            cache:false,
            data:mydata,
            dataType:'xml',
            error:function (arg1,arg2,arg3) {
                var dbg=$('responseText',arg1).text()+
                        $('responseXml parseError reason',arg1).text()+
                        $('responseXml parseError srcText',arg1).text();
                window.status='Failed update: '+dbg;
                $('#debuglog').empty().text(dbg);
                that.reset();
            },
            success:function (xmldata, textStatus) {
                if (namefield) {
                    namefield.Set($('gtdphp values title',xmldata).text());
                }
                if (descfield) {
                    descfield.Set($('gtdphp values description',xmldata).text());
                }
                if (outcomefield) {
                    outcomefield.Set($('gtdphp values desiredOutcome',xmldata).text());
                }
                window.status=$('gtdphp result line',xmldata).text();
                $('#debuglog').empty().append($('gtdphp log',xmldata).text());
                $(row).animateShow();
                that.tidyUp();
                updateItem(row,xmldata);
            },
            type:'POST',
            url:'processItems.php'
        });
    };
    // --------------------------------------------------------
    this.reset=function() { // reset function
        if (namefield) {namefield.Set(false);}
        if (descfield) {descfield.Set(false);}
        if (outcomefield) {outcomefield.Set(false);}
        if (itemId==='0') {$(row).remove();}
        that.tidyUp();
    };
    // --------------------------------------------------------
    iconfield=new Live_field(tdsave,'saveCancel',this.save,this.reset,this.expand);
    $('input,textarea',row).keypress(function(e){  //   // TODO consider splitting out into a central key-handler
        if (e.keyCode===27) {
            that.reset();
            return false;
        }
        if (this.tagName.toLowerCase()!=='textarea' && e.keyCode===13) {
            that.save();
            return false;
        }
        return true;
    });
    // --------------------------------------------------------
    //trigger AJAX call to get the full item.php form, so that it's ready if the user clicks on the expand button
    if (itemId==='0') {
        thisurl=$(row).parents('form').find('tr.creator td.col-title a[href]').attr('href')+'&ajax=true';
    } else {
        thisurl='item.php?ajax=true&itemId='+itemId;
    }
    $.ajax({
        dataType:'html',
        error:function (arg1,arg2,arg3) {
            window.status='Failed to retrieve full item form: '+arg2;
            newdiv=null;
            $('#ajaxexpand',tdsave).remove();
        },
        success:function (htmldata, textStatus) {
            newdiv.innerHTML=htmldata;
            gotfullitemform=true;
            if (expandme) {showfullform();}
        },
        type:'GET',
        url:thisurl
    });
    // --------------------------------------------------------
    return true;
}
// ======================================================================================
function createAjaxEditor(cell,EditorConstructor) {
/*
 * create an editor to create/edit a list item, and store all active editors in an array
 *
 * cell: the DOM object of a cell in the row of the item we are going to edit
 * EditorConstructor: the constructor function for the editor
 */
    var i,max=editor.length;
    for (i=0;i<max;i++) { // cycle through our array of extant editors, looking for an empty slot
        if (editor[i]===null || editor[i].ended) {break;}
    }
    editor[i]=null; // dispose of any existing editor in this slot
    editor[i]=new EditorConstructor($(cell).parents('tr').get(0)); // create a new editor
    return false;
}
/* ======================================================================================
    context toggling - author Aurélien Bompard
*/
function toggleContext(e) {
/*
 * event-handler for a checkbox being toggled in the contexts summary table:
 *      toggle the display of a specific context
 *
 * e: the jQuery event object
 */
    var max,
        cookieval='',
        context=e.data.context,
        cookiesep='',
        pos = jQuery.inArray(context, hiddencontexts);
    
    $("div"+context).slideToggle("fast");

    // store display status in a cookie
    if (pos !== -1) {
        delete hiddencontexts[pos];
    } else {
        hiddencontexts[hiddencontexts.length] = context;
    }
    max=hiddencontexts.length;
    for (var i=0; i<max; i++) {
        if (hiddencontexts[i]) {
            cookieval=cookieval+cookiesep+hiddencontexts[i];
            cookiesep='/';
        }
    }
    GTD.cookieSet("hidecontexts",cookieval);
    return false;
}
// ------------------------------------------------------------------------
function addAjaxToggleContext(node,context) {
/*
 * add a checkbox to a single cell in the summary table in reportContext,
 * to allow the user to toggle the visibility of individual space-contexts and time-contexts
 *
 * node: the DOM object of the cell in the summary table where the checkbox should appear
 * context: the unique ID of the context being toggled by this checkbox
 */
    var editbox=$("<input type='checkbox' />").
        attr('checked',true);
    if ($(node).parents('tbody').length) { // in the table body
        editbox.
            css('cursor','pointer').
            bind('change',{context:context},toggleContext);
    }
    if (jQuery.inArray(context, hiddencontexts) !== -1) {
        editbox.attr('checked',false);
        $("div"+context).slideToggle("fast");
    }
    editbox.prependTo(node);
}
// ------------------------------------------------------------------------
function initContextToggle() {
/*
 * initialise the AJAX interface for reportContext.
 * Add checkboxes to toggle visibility of individual contexts, and load most
 * recent set of user-specified hidden contexts from cookie, so that the previous
 * state is always restored
 */
    var cookieval,lastcell,context,toggleheadercell,total,headcells,node;
    cookieval = GTD.cookieGet('hidecontexts');
    hiddencontexts= (cookieval===null) ? [] : cookieval.split("/");

    // add checkboxes to toggle the space-contexts, at the end of each row on the summary table
    toggleheadercell=document.createElement('td');
    toggleheadercell.appendChild(document.createTextNode('Show'));
    $('table#contexttable').children('thead').children('tr').append(toggleheadercell);
    $('table#contexttable').
        children('tbody').children('tr').
            each(function() {
                lastcell = $(this).find("td:last").children("a");
                context = lastcell.attr("href");
                if (context) {context=context.substring(2);}
                total = lastcell.html();
                node=document.createElement('td');
                this.appendChild(node);
                if (context!=='' && total) {
                    addAjaxToggleContext(node,'#dc'+context);
                }
            });

    // now add checkboxes to toggle the time-contexts, at the bottom of each column on the summary table
    headcells=$('table#contexttable>thead>tr>th');  // we shall need to parse the header row, below
    $('table#contexttable tr:last').
        clone().                            // add a row
        appendTo('table#contexttable').     //   to the bottom of the table
        find('td').slice(0,-1).             // skip the last cell
            each(function(ndx) {
                if (ndx===0) {              // just label the first cell
                    $(this).
                        empty().
                        text('Show');
                } else {
                    context=headcells.
                                eq(ndx).
                                attr('id');                   // if the equivalent cell in the header has no id
                    if (context===undefined) {  
                        context='';                           // then make the checkbox inactive
                    } else {
                        context=context.replace(/^thtc/,'');  // else extract time-context from the header
                        total=$(this).text();                 // get number of items in this context
                    }
                    $(this).empty();                          // empty the cell before inserting checkbox
                    if (context!=='' && total) {
                        addAjaxToggleContext(this,'.t'+context);
                    }
                }
            });
}
/*
    end of context-toggling code
  ======================================================================================
*/
function addAjaxEditIcon(node,functioncall) {
/*
 * Add an ajax edit icon to each row in a table of items
 *
 * node:
 * functioncall:
 */
    var editcell,editimg;
    editcell=document.createElement('td');
    editcell.className='col-ajax nosort';
    if ($(node).hasClass('sortbottom')) {
        editcell.appendChild(document.createTextNode(''));
    } else {
        editimg=document.createElement('img');
        editimg.alt=editimg.title='edit item live, in situ';
        editimg.src=GTD.ajax.dir+'ajaxedit.gif';
        if ($(node).parents('tbody').length) { // in the table body
            $(editimg).css('cursor','pointer').click(function() {
                createAjaxEditor(this,functioncall);
            });
        }
        editcell.appendChild(editimg);
    }
    $(node).prepend(editcell);
}
// ======================================================================================
function checkboxclicked(e) {
/*
 * event handler for when a checkbox gets clicked in listItems. This checkbox might do
 * one of many different activities, depending on the value of the action select box
 * at the top of the column
 */
    var cbox,ajaxdata={};
    // first see if we've got a drop-down box attached to the table, where the user can change the action
    cbox=$(this).
        parents('table').
        find('th.col-checkbox select');
    if (cbox.length) {
        ajaxdata.action=cbox.val();
        switch (ajaxdata.action) {
            case 'category':
                ajaxdata.categoryId   = $('#multicategory').val();
                break;
            case 'space':
                ajaxdata.contextId    = $('#multispace').val();
                break;
            case 'tag':
                ajaxdata.tag          = $('#multitag').val();
                break;
            case 'time':
                ajaxdata.timeframeId  = $('#multitime').val();
                break;
            case 'delete': // get confirmation from user
                if (!confirm('Really delete this?')) {
                    return false;
                }
                break;
        }
    } else {
        ajaxdata.action=this.checked ? 'complete' : 'clearCheckmark';
    }
    doAJAXupdate(this,ajaxdata);
}
// ======================================================================================
function createFormForNewItem() {
/*
 * create a table row within which the user can create a new item of a specific type
 */
    var row,newrow;
    newrow=
        (row=$(this).parents('form').
            find('tr.creatortemplate').
            eq(0) ).
        clone().
        insertBefore(row).
        removeClass('sortbottom hidden creatortemplate').
        attr('id','r0');
    newrow.
        find('td.col-ajax').
        remove();
    addAjaxEditIcon(newrow.get(0),itemEditor);
    createAjaxEditor(newrow.find('td').get(0),itemEditor);
    newrow.find('td.col-title input').focus().select();
    return false;
}
// ======================================================================================
function createfirstchild(e) {
/*
 * we are in itemReport.  The user wishes to create a child for the item.  No items of this
 * type yet exist.  So instead of having the child-table visible, we've just got a link,
 * saying something like: "No Goals", with  the AJAX icon next to it.  The user has clicked
 * on that AJAX icon.  This is the event-handler for that click
 */
    var tablename=this.id.substring(1);
    $(this.parentNode).remove();        // hide the link
    $('#'+tablename).
        parents('div.hidden').
            removeClass('hidden').      // show the table
        end().
        find('.creator td.col-ajax').
            click();                    // and trigger the usual AJAX item-creation editor
}
/* ======================================================================================
    functions for handling the multi-select: that is, applying the same action,
    such as changing item category, to several items in the listItems table
*/
// -------------------------------------------------------------------------
function movemultiselect() {
/*
 * ensure that the multi-action SELECT is close to the checkbox column.
 * If the checkbox col is towards the right, then right flush it, else left flush it
 */
    var table,checkbox,container,newleft;
    table=$('table:has(th.col-checkbox)');
    checkbox=table.find('th.col-checkbox');
    container=$('#multicontainer');
    
    if ($.browser.safari) {
      /* Hack needed for safari, because it fails to resize the checkbox column.
       * This hack forces the column to be too wide, temporarily; it can then
       * find the real width of the select box, and size the column to fit that.
       */
        $('th:has(#multiaction)').
                width(150).
                width($('#multiaction').width()+2);
    }

    if (table.filter('th.col-checkbox').hasClass('hidden') || !checkbox.length) {
        container.hide();
    } else {
        container.show();
        newleft=checkbox.width()+
            checkbox.position().left -
            table.position().left-
            $('.multispan',container).width();
        container.css('marginLeft', (newleft>0) ? newleft : 0);
    }
}
// -------------------------------------------------------------------------
function multichange() {
/*
 * an action-type has been selected from the multiaction dropdown
 */
    if (onajax) {return false;}
    var action=$('#multiaction').val();
    $('#multiprompt').text('');
    $('#multicategory,#multitag,#multispace,#multitime').
        each(function () { // display or hide the multi-action SELECTs as required
                if ( (this.id==='multi'+action) ) {
                    $(this).show(); //focus(); too annoying at present
                    $('#multiprompt').text('Select a '+action+(
                        (action==='space' || action==='time')?' context':'')
                        );
                    movemultiselect();
                } else {
                    $(this).hide();
                }
            });
    return true;
}
/*
    end of routines for handling the multi-select
    ======================================================================================
    routines for the user management of the display and sorting columns
*/
var colselectorclose;
// -------------------------------------------------------------------------
function reordercolumns() {
/*
 * reorder the columns in the table, based on the user's reordering
 */
    var x,thisclass,max,tablehead,table,insertAfterClass,totcols,onviscol,
        colorder=[],
        colstatus={};
        
    function movecol() {
        $('.'+thisclass,this).insertAfter($('.'+insertAfterClass,this));
    }

    table=$('#collist').data('linkedtable');
    tablehead=table.find('thead th,thead td'); // array of tableheadings
    totcols=tablehead.length;

    $('#collist li').each(function() {
        thisclass=$(this).data('selectclass');
        if (thisclass!==undefined) {
            colstatus[thisclass]=$(this).hasClass('colshown');
            if (colstatus[thisclass]) {
                colorder.push(thisclass);
            }
        }
    });

    // hide any currently-shown columns which the user has chosen to hide
    for (thisclass in colstatus) {
        if (colstatus.hasOwnProperty(thisclass)) {
            if (!colstatus[thisclass] && !tablehead.filter('.'+thisclass).hasClass('hidden')) {
                table.find('.'+thisclass).addClass('hidden');
            }
        }
    }
    
    // order columns, and show any currently-hidden columns that the user has chosen to show
    max=colorder.length;
    onviscol=1;
    for (x=0;x<max;x++) {
        thisclass=colorder[x];
        /*
            put column in right place:
            
            we are currently processing column 'thisclass',
            and have processed the table up to column: onviscol.

            Run forwards until the next visible column,
            and see if it matches this one.  if so. we are done.

            If not, move thisclass in front of onviscol
        */
        
        while(onviscol<totcols && !colstatus[tablehead.eq(onviscol).data('selectclass')]) { // Run forwards until the next visible column
            onviscol++;
        }
        
        if (!tablehead.eq(onviscol).hasClass(thisclass)) {
            // If onviscol is not the thisclass column, move the thisclass column in front of onviscol
            insertAfterClass=tablehead.
                eq(onviscol-1).
                data('selectclass');
            table.find('tr').each(movecol);
            // now the tablehead array is out of order, so fix it
            tablehead=table.find('thead th,thead td');
        }
        onviscol++;

        // and finally, show column if hidden
        if (tablehead.filter('.'+thisclass).hasClass('hidden')) {
            table.find('.'+thisclass).removeClass('hidden');
        }
    }
    movemultiselect();
}
// -------------------------------------------------------------------------
function colselectorclicked(e) {
/*
 * Event handler for when the user has toggled the display of a column
 * TOFIX something odd happens when hiding the checkbox column
 * e: jQuery event
 */
    var target=$(e.target);
    if (!target.parents().andSelf().filter('#colselector').length) {
        // clicked outside the box, so remove it
        colselectorclose();
        return true;
    }
    if (target.parents('#colpreviewspan').length) {
        // user has clicked on preview checkbox, so process that
        return true;
    }
    target.
        toggleClass('colhidden').
        toggleClass('colshown');
        
    if ($('#colpreview').attr('checked')) {
        reordercolumns();
    }

    return false;
}
// -------------------------------------------------------------------------
function movecolumn(e,ui) {
/*
 * Event handler for the jQuery UI sortable function: the user has changed the order of columns
 *
 * e: jQuery event
 * ui: jQuery UI info for the originating item of this event
 */
    // update the display, if preview is on
    if ($('#colpreview').attr('checked')) {reordercolumns();}
}
// -------------------------------------------------------------------------
function columnpreviewtoggle(e) {
/*
 * Event handler for the user toggling whether live previews are shown of column reordering / showing / hiding
 *
 * e: jQuery event
 */
    if ($('#colpreview').attr('checked')) {reordercolumns();}
    e.stopPropagation();
    return true;     // and allow checkbox to be ticked
}
// -------------------------------------------------------------------------
function saveperspective(e) {
/*
 * Event handler for the user requesting to save the perspective
 *
 * e: jQuery event
 */
    var data,sortcol,msgbox,pos;
    // build the variables to send in the AJAX request
    data={output:'xml','show[]':[],'columns[]':[]};
    data.uri=$('#uri').val();

    // get visible columns from classes in #colselector
    $('#colselector li').each(function() {
        var thisli=$(this),
            thisname=thisli.data('selectclass').replace(/^col-/,'');
        data['columns[]'].push(thisname);
        if (thisli.hasClass('colshown')) {
            data['show[]'].push(thisname);
        }
    });

    // get current sort order, if any
    sortcol=$('th.sortup,th.sortdown');
    if (sortcol.length) {
        data.sort=sortcol.data('selectclass').replace(/^col-/,'');
        data.sort+=sortcol.hasClass('sortup') ? ' DESC' : ' ASC';
    }

    // TODO get name - popup an item box getting the name. For now, just use the page title as generated, stripping out any numbers
    data.name=$('#pagetitle').text().replace(/[0-9]* /g,'');

    pos=$('#colselector').position();
    msgbox=messagepopup('Saving view','',pos.top,pos.left); // show success message over the popup box
    colselectorclose();                                     // close the popup box, 
    $.ajax({                                                // and make the AJAX call
        cache:false,
        data:data,
        dataType:'xml',
        error:function (arg1,arg2,arg3) {
            $('#debuglog').empty().text(arg1.responseText);
            msgbox.empty().
                text('Failed to save this view').
                animateShow().
                animate({left:pos.left},5000).              // wait for 5 seconds
                queue(function(){
                    $(this).remove().dequeue();             // and then disappear
                });
        },
        success:function(xmldata) {
            // TODO functions to handle errors, and successful returns, from AJAX call
            $('#debuglog').
                empty().
                append($('gtdphp log',xmldata).text());
            msgbox.empty().
                text($('gtdphp values success',xmldata).text() ?
                        'Saved this view' : 'Failed to save this view');
            messagepopup(msgbox,xmldata);
            msgbox.
                animateShow().
                animate({left:pos.left},5000).              // wait for 5 seconds
                queue(function(){
                    $(this).remove().dequeue();             // and then disappear
                });
        },
        type:'POST',
        url:'processViews.php'
    });
}
// -------------------------------------------------------------------------
function showcolumnselector(e) {
/*
 * Event handler for when the user has activated the column selecter/orderer UI
 *
 * e: jQuery event
 */
    var coldiv,collist,
        classregex=/^.*(col-\S+).*$/;
        
    if (document.getElementById('colselector')) {
        return colselectorclose(e); // already showing selection box, so close it
    }

    coldiv=$(document.createElement('div')).                     // the container div for our popup
        attr({id:'colselector'}).
        appendTo('#main').
        css({top:(10+e.pageY)+"px",left:(10+e.pageX)+"px"}).    // position just below and to the right of mouse click
        append(
            $(document.createElement('span')).                  // insert a SPAN into the DIV
                attr('id','colpreviewspan').
                append(
                    $("<input type='checkbox' />").             // the SPAN contains a CHECKBOX
                        attr({id:'colpreview',name:'colpreview',checked:true})
                ).
                append(
                    $(document.createElement('label')).         // and the SPAN also contains a LABEL
                        attr('for','colpreview').
                        text('preview')
                ).
                click(columnpreviewtoggle)                      // when the CHECKBOX is clicked, run columnpreviewtoggle
        ).
/*        append(
            $(document.createElement('span')).         
                attr({id:'saveperspective',
                    title:'Save this perspective as the default display for this page'}
                ).
                click(saveperspective)                      // when the save icon is clicked, do an AJAX save
        ).
*/        append(
            $(document.createElement('span')).         
                attr({id:'closeselector',
                    title:'close this column-selector box'}
                ).
                click(colselectorclose)                      
        );

    collist=$(document.createElement('ul')).                    // insert a UL into the popup DIV
        appendTo(coldiv).
        attr('id','collist').
        data('linkedtable',$(e.target).parents('table').eq(0)). // record which TABLE we are tweaking
        sortable({update:movecolumn,distance:5});               // use the jQuery UI drag-and-drop sorter

    $(e.target).
        parents('thead').
        find('th').
        each(function() {
            var selectclass=this.className.replace(classregex,"$1");
            if (selectclass==='col-ajax') {
                $(this).data('selectclass',selectclass);
            } else {
                $(document.createElement('li')).                    // add one LI to the popup for each table heading
                    addClass($(this).hasClass('hidden')?'colhidden':'colshown'). // and we want to show whether each column is currently hidden or shown
                    text(                                           // set the label on the LI:
                        (selectclass==='col-checkbox') ?
                            ($('select',this).val()+' checkbox') : // use the SELECT value, if it's a checkbox,
                            $(this).data('selectclass',selectclass). // (save the processed class name with the table header cell, for fast access later)
                                text()                             // not a checkbox, so use column header text to label the LI
                        ).                        
                    appendTo(collist).                          // add the LI to the UL
                    data('selectclass',selectclass);            // and save the CLASS associated with that heading (e.g. col-parent), because that identifies the cells we'll be manipulating via this LI
            }
        });
    $(document).click(colselectorclicked);                      // catch ALL clicks, wherever they are, while the popup is on-screen
    return false;
}
// -------------------------------------------------------------------------
colselectorclose=function() {
/*
 * clean-up function to close the UI for column order/selection
 */
    reordercolumns();
    $('#colselector').empty().remove();
    $(document).unbind('click',colselectorclicked);
    return true;
};
/*
    end of routines for user management of the display and sorting columns
 ======================================================================================

    The publicly visible javascript functions are below:
        all the functions above are just utility functions for these

 ======================================================================================*/
if (typeof GTD.ajax==='undefined') {GTD.ajax={};} // create the publicly visible object
// ======================================================================================
GTD.ajax.initcontext=function() {
    this.inititem();
    initContextToggle();
};
// ======================================================================================
GTD.ajax.inititem=function() {
/*
 * initialise AJAX handling for itemReport, reportContext, listItems, (orphans?)
 */
    $("table:has(.col-title) tbody tr").
        each(function() {
            addAjaxEditIcon(this,itemEditor);
        }).
        find('.col-NA :checkbox').
            click(NAclicked).
        end().find('.col-checkbox :checkbox').
            click(checkboxclicked).
        end().filter('.creator').
            find('td.col-ajax').
                append(document.createTextNode('+')).
                addClass('addlink').
                css('cursor','pointer').
                click(createFormForNewItem).
                attr('title','Create new list item');

    $("table:has(.col-title) thead tr").
        prepend(
            $(document.createElement('th')).
                addClass('col-ajax nosort')
        );

    $("input:submit:not(#filtersubmit),input:reset").
        css({display:'none'});
        
    return true;
};
// ======================================================================================
GTD.ajax.multisetup=function() {
/*
 * setup the interface in listItems to allow the user to apply an action
 * such as changing catetgory or tagging, to several items
 */
    GTD.ajax.inititem();
    $('table:has(.col-title)').
        find('thead').
            find('.col-ajax').                // find the table-header cell for the AJAX column
                addClass('ajaxeye').          // add the eye icon to it
                click(showcolumnselector).    // add the click-handler to it
                end().
            find('th.col-checkbox').          // find the table-header cell for the checkbox column
                empty().                      // remove its current contents
                addClass('nosort').           // don't sort by this column
                prepend(                      // put our action SELECT box into the header cell
                    $('#multiaction').
                        change(multichange).  // add click handler to the SELECT box
                        change()              // ensure that the right category/context SELECT is displayed from the start
                ). 
                end().
            end().
        before(                               // put the container of the category/context SELECT boxes just above the table
            $('#multicontainer').
                removeClass('hidden')         // and display the container (though SELECT boxes will remain hidden for now)
        );
    movemultiselect();
    return true;
};
// ======================================================================================
GTD.ajax.setNoChildren=function(tables) {
/*
 * initialisation function for itemReport, enabling ajax-creation of children
 *
 * tables: array of DOM objects of tables to be processed
 */
    var tableid,nochild;
    for (tableid in tables) {if (tables.hasOwnProperty(tableid)) {
        nochild=$(tables[tableid]);

        $('#'+tableid).
            parents('div').
            eq(0).
            addClass('hidden').     // hide the corresponding table
            after(nochild);          // restore the text reporting that there are no children of this type

        // and now add an ajax button to the no-children text, which will hide the text, display the table, and trigger the creation
        nochild.prepend(
            $('<img />').attr({
                    alt  :'Create a new child',
                    id   :'i'+tableid,
                    src  :GTD.ajax.dir+'ajaxedit.gif',
                    title:'Create a new child'
                }).
                css('cursor','pointer').
                click(createfirstchild)
            );
        }
    }
};
// ======================================================================================
GTD.ajax.setTabs=function() {
/*
 * display the tabs in the preferences screen
 */
    var list;
    $('#optionsform').
        prepend(
            list=$(document.createElement('ul')).addClass('tabbar')
            ).
        find('h2').
            wrapInner(document.createElement('li')).
            children('li').
                appendTo(list).
                click(function() { // change which tab is displayed
                    var clickedtab=$(this);
                    if (clickedtab.hasClass('selectedTab')) { return false; }
                    $('ul.tabbar li').
                        filter('.selectedtab').
                            removeClass('selectedtab');
                    clickedtab.addClass('selectedtab');
                    $('div.tabsheet').
                        hide().
                        filter('[id='+clickedtab.text()+']').
                            show();
                }).
                filter(':first').
                    click().
                end().
            end().
            remove();
    $('#footer').before('<p>Note that Apply will save changes on all tabs; Reset will reset all tabs</p>');
};
/* ======================================================================================
GTD.ajax.tagKeypress=function(e) {
 *
 * event handler to auto-suggest existing tags, when the user is typing in the 'tags' box
 * !!! TODO - this is only the skeleton - not yet functional !!!
 *
 * e: event object
 *
    var pressed,key;
    if (window.event) {pressed=window.event.keyCode;} else {pressed=e.charCode;}
    key = String.fromCharCode(pressed);

     * if we're currently offering a tag in a tooltip,
        check to see if enter or tab was pressed, and if so, add the tooltip tag
        to the field, and return false
        if esc pressed, then dismiss the tooltip
    *


    if (key!==' ' && key!==',') {
        // get everything in field after last comma

        // trim it

        // prepend comma

        // seek match in GTD.tags

        // if found, offer labels in tooltip, with first one highlighted

        // need to grab mouse events on tooltip too
    }
    return true;
};
*
    ======================================================================================
*/
GTD.ParentSelector.prototype.creatorlines=[];
GTD.ParentSelector.prototype.saverow='';
GTD.ParentSelector.prototype.onAjax=false;
GTD.ParentSelector.prototype.gotparentold=GTD.ParentSelector.prototype.gotparent;
GTD.ParentSelector.prototype.oldmakeline=GTD.ParentSelector.prototype.makeline;
// ======================================================================================
GTD.ParentSelector.prototype.liveclear=function () {
/*
 * cancelling the creation of an item parent
 */
    var that=GTD.parentselect;
    if (that.editingrow!==-1) {
        $('#searchresults>p').eq(that.editingrow).replaceWith(that.saverow);
        that.editingrow=-1;
        that.onAjax=false;
    }
    $('#freezer').removeClass('ontop');
    return false;
};
// ======================================================================================
GTD.ParentSelector.prototype.gocreateparent=function(id,title,type,typename,rownum) {
/*
 * create a mini-form to create a parent item
 *
 * id:
 * title:
 * type:
 * typename:
 * rownum:
 */
    var livename,thisrow,livesave,livedesc,that=this,
        ENTERTITLE="enter title here",
        FORCETITLE="Title cannot be blank",
        ENTERDESC="optional description";
    // ----------------------------------------------
    function clearfield(thisfield) {
        // empty a field
        if (thisfield.className!=='') {
            thisfield.value='';
            thisfield.className='';
        }
        return true;
    }
    // ----------------------------------------------
    
    // insert a one-row table at the top of the search results
    var tb,tr;
    tb=document.createElement('table');
    tr=document.createElement('tr');
    
    this.editingrow=this.creatorlines[rownum];
    thisrow=document.getElementById('searchresults').childNodes[this.editingrow];
    this.saverow=$(thisrow).clone(true);
    while(thisrow.hasChildNodes()) {thisrow.removeChild(thisrow.lastChild);}
    /*
                if (this.onAjax) {return false;}
            if (e.keyCode===13) {
                $(livesave).trigger('click');
                return false;
            }
    */
    livename           = document.createElement('input');
    livename.type      = 'text';
    livename.id        = 'livename';
    livename.value     = ENTERTITLE;
    livename.title     = typename+' title';
    thisrow.appendChild(livename);

    livedesc           = document.createElement('input');
    livedesc.type      = 'text';
    livedesc.id        = 'livedesc';
    livedesc.value     = ENTERDESC;
    livedesc.className = 'firstclick';
    livedesc.title     = typename+' description';
    $(livedesc).unbind("focus").bind("focus",clearfield);
    thisrow.appendChild(document.createTextNode(' '));
    thisrow.appendChild(livedesc);

    livesave           = document.createElement('a');
    livesave.className = 'add';
    livesave.id        = 'livesave';
    livesave.href      = '#';
    livesave.title     = 'create this '+typename;
    $(livesave).click(function(e) {
        var someday,thisdesc,myurl,mydata;
        if (livename.value==='' || livename.value===FORCETITLE || livename.value===ENTERTITLE) {
            livename.value=FORCETITLE;
            livename.focus();
            livename.select();
            return false;
        }
        that.onAjax=true;
        $('#freezer').addClass('ontop'); // freeze screen and change cursor to waiting symbol

        someday='n';
        if (type==='s') {
            someday='y';
            type='p';
        }
        thisdesc=(livedesc.value===ENTERDESC)?'':livedesc.value;
        // now do ajax call to save
        myurl='processItems.php';
        mydata={title       : livename.value,
                 description : thisdesc,
                 action      : 'createbasic',
                 type        : type,
                 isSomeday   : someday,
                 output      : 'xml'
                 };
        $.ajax({
            cache:false,
            data:mydata,
            dataType:'xml',
            error:function (arg1,arg2,arg3) {
                that.liveclear();
            },
            success:function (xmldata, textStatus) {
                var newitem,title,type,typename,id,box,titlelc,line,i,max;
                newitem=$('gtdphp values',xmldata);
                title = $('title',newitem).text();
                type  = $('type',newitem).text();
                id    = $('newitemId',newitem).text();
                box=document.getElementById('searchresults');
                if ($('isSomeday',newitem).text()==='y') {type='s';}
                window.status=$('gtdphp result',xmldata).text();
                newitem=null;

                that.liveclear();

                titlelc=title.toLowerCase();
                that.parentIds.unshift(id);
                that.ptitles.unshift(title);
                that.ptitleslc.unshift(titlelc);
                that.ptypes.unshift(type);

                typename=GTD.typenames[type];
                line=that.makeline(id,title,type,typename,0,true,typename);
                box.insertBefore(line, box.firstChild);
                $(line).animateShow();
                max=that.creatorlines.length;
                for (i=0;i<max;i++) {that.creatorlines[i]++;}
                that.gotparent(id,title,type,typename,0);
                that.onAjax=false;
            },
            type:'POST',
            url:myurl
            });
    });
    livesave.appendChild(document.createTextNode("+"));
    thisrow.appendChild(document.createTextNode(' '));
    thisrow.appendChild(livesave);

    var livequit       = document.createElement('a');
    livequit.className = 'remove';
    livequit.id        = 'livesave';
    livequit.href      = '#';
    livequit.title     = 'cancel';
    $(livequit).click(this.liveclear);
    livequit.appendChild(document.createTextNode("x"));
    thisrow.appendChild(document.createTextNode(' '));
    thisrow.appendChild(livequit);

    livename.focus();
    livename.select();
    return true;
};
// ======================================================================================
GTD.ParentSelector.prototype.gotparent=function (id,title,type,typename,rowNum) {
/*
 * add the clicked parent to the list of the item's parents
 *
 * id:
 * title:
 * type:
 * typename:
 * rowNum:
 */
    if (this.editingrow!==-1) {this.liveclear();}
    this.gotparentold(id,title,type,typename,rowNum);
    if ($('#categoryId').val()!=='0'){return false;}

    // if category is unselected, get parent info via AJAX and put it in the form
    $.getJSON(GTD.urlprefix+'sendJSON.php&itemId='+id,
        function(json){
            var formfield, thisform=$('form:first'),max,i,
                fields=['categoryId','contextId','deadline'];
            max=fields.length;
            for (i=0;i<max;i++) {
                formfield=thisform.find('[name='+fields[i]+']');
                if (formfield.val()==='' || formfield.val()==='0') {
                    formfield.val(json[fields[i]]);
                }
            }
        });
};
// ======================================================================================
GTD.ParentSelector.prototype.makeline=function(id,title,type,typename,i,useTypes,onetype) {
/*
 * display a specific parent, to go into the list of parents
 *
 * id:
 * title:
 * type:
 * typename:
 * i:
 * useTypes:
 * onetype:
 */
    var that=this,line=document.createElement('p'),thisi,anchor,linetext=title;
    if (id==='0') {
        thisi=this.creatorlines.length;
        this.creatorlines.push(i);
        line.className='creator';
    } else {thisi='';}
    anchor=document.createElement('a');
    anchor.href='#';
    $(anchor).
        unbind('click').
        click(function() {
            that.gotparent(id,title,type,typename,thisi);
        });
    anchor.appendChild(document.createTextNode('+'));
    anchor.className='add';
    line.appendChild(anchor);
    if (useTypes) {linetext += " ("+typename+")";}
    line.appendChild(document.createTextNode(linetext));
    line.style.display=(!useTypes || typename===onetype)?'block':'none';
    return line;
};
// ======================================================================================
GTD.toggleHidden=function (parent,link,dummy) {
/*
 * Replaces function of same name in gtdfuncs.js, because we can do the same thing prettier, here.
 *
 * Reveals contents of a hidden section of an itemReport table,
 *  e.g. tickled items, or all completed items
 *
 * parent: id of table
 * link:  the id of the element that the user pressed to reveal the section - we can dispose of that element, now
 */
    $('#'+link).remove();
    $('tr.togglehidden','#'+parent).animateShow();
    return false;
};
// ======================================================================================
})(jQuery);} // end of the enclosing anonymous function: the extra () ensures that the anonymous function is executed immediately
