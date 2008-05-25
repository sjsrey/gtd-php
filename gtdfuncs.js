/*jslint browser: true, eqeqeq: true, nomen: true, undef: true */
/*global unescape,Calendar */
/*
    NB we need unescape, because decodeURIcomponent corrupts some characters in AJAX
    - but this will change when we get proper i18n/utf8/mbcs handling in gtd-php
*/
var GTD; // object for holding public functions and public variables
(function() {
// ======================================================================================
var gtd_freezediv,focusOn,grabKey,oldTablePosition,SORT_COLUMN_INDEX;
// ======================================================================================
function checkForNull(field) {
    switch (field.type) {
    	case "text":
    		var tst=field.value;
			tst.replace(" ","");
		    var isblank=(tst === '');
		    return isblank;
		case "checkbox":
			return !field.checked;
		default:
			return false;
	}
}
// ======================================================================================
function checkDate(field,dateFormat) {
    if (checkForNull(field)) {return true;}
    // The validity of the format itself should be checked when set in user preferences.  This function assumes that the format passed in is valid.

    // Build the regular expression
    var format = dateFormat;
    format = format.replace(/([^mcyd])/,'[$1]');         // Any char that is not m, c, y, or d is a literal
    format = format.replace(/dd/,'[0-3][0-9]');          // First char of a day can be 0-3, second 0-9
    format = format.replace(/mm/,'[0-1][0-9]');          // First char of a month can be 0 or 1, second 0-9
    format = format.replace(/yy/,'[0-9][0-9]');          // Year must be two chars
    format = format.replace(/cc/,'[0-9][0-9]');          // Century must be two chars

    var dateRegEx = new RegExp(format);
    var tst=field.value;
    return (tst.match(dateRegEx));
}
// ======================================================================================
function toggleVis (thisRule) {
	thisRule.style.display=(thisRule.style.display==="none")?"block":"none";
}
// ======================================================================================
function keyUpHandler(e) {
    var character,targetNodeName,code;
	if (!e) {e = window.event;}
	if (e.target && e.target.nodeName) {
		targetNodeName = e.target.nodeName.toLowerCase();
		if (targetNodeName === "textarea" ||
              (targetNodeName === "input" && e.target.type && e.target.type.toLowerCase() === "text")) {
			return false;}
	}
	if (e.keyCode) {
        code = e.keyCode;
    } else if (e.which) {code = e.which;}
	character = String.fromCharCode(code);
	if (character===grabKey) {
		if (document.styleSheets[0].cssRules) {
			toggleVis(document.styleSheets[0].cssRules[0]);
		} else {
			toggleVis(document.styleSheets[0].rules[0]);
			toggleVis(document.styleSheets[0].rules[1]);
		}
	}
	return false;
}
// ======================================================================================
/*
sortTable amended for GTD-PHP

Based on code from: http://kryogenix.org/code/browser/sorttable/
Copyright (c) 1997-2007 Stuart Langridge

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
function ts_getInnerText(el) {
    var str,cs,l,i;
	if (typeof el === "string") {return el;}
	if (typeof el === "undefined") { return el; }
	if (el.innerText) {return el.innerText;}	//Not needed but it is faster
	str = "";

	cs = el.childNodes;
	l = cs.length;
	for (i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += ts_getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
	}
	return str;
}

function ts_makeSortable(table) {
    var firstRow,cell,txt,i,max;
    if (table.rows && table.rows.length > 0) {
        firstRow = table.rows[0];
    }
    if (!firstRow) {return;}

    // We have a first row: assume it's the header, and make its contents clickable links
    max=firstRow.cells.length;
    for (i=0;i<max;i++) {
        cell = firstRow.cells[i];
        txt = ts_getInnerText(cell);
        cell.innerHTML = '<a href="#" class="sortheader" '+
        'onclick="GTD.resortTable(this, '+i+');return false;">' +
        txt+'</a>';
    }
}

function sortables_init() {
    var tbls,ti,thisTbl;
    // Find all tables with class sortable and make them sortable
    if (!document.getElementsByTagName) {return;}
    tbls = document.getElementsByTagName("table");
    for (ti=0;ti<tbls.length;ti++) {
        thisTbl = tbls[ti];
        if (((' '+thisTbl.className+' ').indexOf("sortable") !== -1) && thisTbl.id) {
            //initTable(thisTbl.id);
            ts_makeSortable(thisTbl);
        }
    }
}
function getParent(el, pTagName) {
	if (el === null) {return null;}
	else if (el.nodeType === 1 && el.tagName.toLowerCase() === pTagName.toLowerCase()) {	// Gecko bug, supposed to be uppercase
		return el;
	} else {return getParent(el.parentNode, pTagName);}
}

function ts_sort_date(a,b) {
    // y2k notes: two digit years less than 50 are treated as 20XX, greater than 50 are treated as 19XX
    var aa,bb,dt1,yr,dt2;
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    if (aa.length === 10) {
        dt1 = aa.substr(6,4)+aa.substr(3,2)+aa.substr(0,2);
    } else {
        yr = aa.substr(6,2);
        if (parseInt(yr,10) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt1 = yr+aa.substr(3,2)+aa.substr(0,2);
    }
    if (bb.length === 10) {
        dt2 = bb.substr(6,4)+bb.substr(3,2)+bb.substr(0,2);
    } else {
        yr = bb.substr(6,2);
        if (parseInt(yr,10) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt2 = yr+bb.substr(3,2)+bb.substr(0,2);
    }
    if (dt1===dt2) {return (a.rowIndex-b.rowIndex);}
    if (dt1<dt2) {return -100;}
    return 100;
}

function ts_sort_currency(a,b) {
    var aa,bb,retval;
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    retval = parseFloat(aa) - parseFloat(bb);
	if (retval===0) {return (a.rowIndex-b.rowIndex);} else {return retval;}
}

function ts_sort_numeric(a,b) {
    var aa,bb;
    aa = parseFloat(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]));
    if (isNaN(aa)) {aa = 0;}
    bb = parseFloat(ts_getInnerText(b.cells[SORT_COLUMN_INDEX]));
    if (isNaN(bb)) {bb = 0;}
    if (aa===bb) {return (a.rowIndex-b.rowIndex);} else {return aa-bb;}
}

function ts_sort_caseinsensitive(a,b) {
    var aa,bb;
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).toLowerCase();
    bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]).toLowerCase();
    if (aa===bb) {return (a.rowIndex-b.rowIndex);}
    if (aa<bb) {return -100;}
    return 100;
}

function ts_sort_checkbox(a,b) {
    var aa,bb;
    aa = a.cells[SORT_COLUMN_INDEX].firstChild.checked;
    bb = b.cells[SORT_COLUMN_INDEX].firstChild.checked;
    if (aa===bb) {return (a.rowIndex-b.rowIndex);}
    if (aa) {return -100;}
    return 100;
}
/* ======================================================================================
    start of declarations of public functions
*/
if (typeof window.GTD==='undefined') {window.GTD={};}
GTD=window.GTD;
GTD.addEvent=function ( obj, type, fn ) { // (c) John Resig - open source
    if ( obj.attachEvent ) {
        obj['e'+type+fn] = fn;
        obj[type+fn] = function(){obj['e'+type+fn]( window.event );};
        obj.attachEvent( 'on'+type, obj[type+fn] );
    } else { obj.addEventListener( type, fn, false ); }
};
// ======================================================================================
GTD.completeToday=function (datefield) {
    var now,m,d,y,newdate;
	now=new Date();
	m  = now.getMonth()+1;
	d  = now.getDate();
	y  = now.getFullYear();
	m=(m < 10) ? ("0" + m) : m;
	d=(d < 10) ? ("0" + d) : d;
	newdate=""+y+"-"+m+"-"+d;
	document.getElementById(datefield).value=newdate;
    //	return true;
};
// ======================================================================================
GTD.confirmDelete=function(elem) { // confirm that the user wishes to delete the current item in item.php
   var myform;
   if (confirm("Delete this item?")) {
       myform=document.getElementById('itemform');
       myform.elements.doDelete.value='y';
       myform.submit();
   }
   return false;
};
// ======================================================================================
GTD.debugInit=function (keyToCatch) {
	grabKey=keyToCatch;
	GTD.addEvent(document,'keypress',keyUpHandler);
};
// ======================================================================================
GTD.filtertoggle=function (which) {
    var box,max,isOn,i;
    box=document.getElementById('everything');
    if (which==='all') {
        isOn=false;
    } else {isOn=box.checked;}
    max=box.form.elements.length;
    for (i=0;i<max;i++) {
        if (typeof box.form.elements[i].disabled!=='undefined') {
            box.form.elements[i].disabled=isOn;
        }
    }
    document.getElementById('type').disabled=
        document.getElementById('needle').disabled=
        document.getElementById('filtersubmit').disabled=
        document.getElementById('tags').disabled=
        box.disabled=false;
    box.focus();
    return true;
};
// ======================================================================================
GTD.focusOnForm=function (id) {
    var tst,i;
    if (typeof(id)==='string') {
        document.getElementById(id).focus();
        focusOn=id;
    }
    if(typeof(focusOn)==='string') {return;}
    if (document.forms.length) {
        for (i = 0; i < document.forms[0].length; i++) {
            tst=document.forms[0].elements[i].type;
            if ( (tst === "button") || (tst === "checkbox") || (
                    tst === "radio") || (tst === "select") ||
                    (tst === "select-one") || (tst === "text") ||
                    (tst === "textarea") ) {
                if (!document.forms[0].elements[i].disabled) {
                  document.forms[0].elements[i].focus();
                  break;
                }
            }
        }
    }
};
// ======================================================================================
GTD.freeze=function (tofreeze) {
	if (typeof gtd_freezediv==='undefined') {
    	gtd_freezediv=document.createElement('div');
    	gtd_freezediv.id="freezer";
        gtd_freezediv.style.display="none";
    	//gtd_freezediv.appendChild(document.createElement('span')); // necessary for problem with addevent betweeen Opera and Safari
    	document.body.appendChild(gtd_freezediv);
    }
	gtd_freezediv.style.display=(tofreeze)?"block":"none";
};
// ======================================================================================
GTD.initcalendar=function(container) {
    var i,id,alldates,alllbuttons,onedate,max,firstDayOfWeek,classRegExp;
    firstDayOfWeek=parseInt((document.getElementById('firstDayOfWeek')).value,10);
    alllbuttons=document.getElementsByTagName('input');
    classRegExp=new RegExp("(^|\\s)hasdate(\\s|$)");
    max=alllbuttons.length;
    for (i=0;i<max;i++) {
        if (classRegExp.test(alllbuttons[i].className) ) {
            id=alllbuttons[i].id;
            Calendar.setup( { firstDay  : firstDayOfWeek,
                              inputField: id,
                              button    : id+'_trigger'
            });
        }
    }
};
// ======================================================================================
GTD.ParentSelector=function(ids,titles,types,onetype) { // constructor
    var i,line,type,typename,useTypes,max,
        box=document.getElementById('searchresults');
    this.inSearch=false;
    this.ptitleslc=[];
    this.ptypes=[];
    this.ptitles=[];
    this.parentIds=[];
    this.editingrow=-1;
    this.parentIds=ids;
    this.ptypes=types;
    this.qtype=onetype;
    if (box.hasChildNodes()) {box.removeChild(box.lastChild);}
    useTypes=(types.length>0);
    if (!useTypes) {
        typename=GTD.typenames[onetype];
        type=onetype;
    }
    max=titles.length;
    for (i=0;i<max;i++) {
        this.ptitles[i]=unescape(titles[i]);
        this.ptitleslc[i]=this.ptitles[i].toLowerCase();
        if (useTypes) {
            typename=GTD.typenames[types[i]];
            type=types[i];
        }
        line=this.makeline(this.parentIds[i],this.ptitles[i],type,typename,i,useTypes,onetype);
        box.appendChild(line);
    }
}; // end of ParentSelector constructor
// ======================================================================================
GTD.ParentSelector.prototype.close=function() {
    document.onkeypress=null;
    document.getElementById('searcher').style.display='none';
    GTD.freeze(false);
    document.getElementById('parenttable').style.position=oldTablePosition;
    GTD.focusOnForm(0);
    this.inSearch=false;
};
// ======================================================================================
GTD.ParentSelector.prototype.gocreateparent=function(id,title,type,typename,rownum) {
    document.forms[0].afterCreate.value=
        document.forms[0].referrer.value=
        'item.php?nextId=0&amp;type='+type;
    document.forms[0].submit();
    return false;
};
// ======================================================================================
GTD.ParentSelector.prototype.gotparent=function (id,title,type,typename,rownum) { // add the clicked parent to the list of the item's parents
    var newrow,anchor,cell,cell1,cell2;
    if (id==='0') {
        this.gocreateparent(id,title,type,typename,rownum);
        return false;
    }
    if (document.getElementById('parentrow'+id)) {return;}

    newrow=document.createElement('tr');
    cell=document.createElement('td');
    anchor=document.createElement('a');
    cell1=document.createElement('td');
    cell2=document.createElement('td');

    newrow.id='parentrow'+id;
    anchor.href='#';
    GTD.addEvent(anchor,'click',function(){return GTD.removeParent(id);});
    anchor.title='remove as parent';
    anchor.className='remove';
    anchor.appendChild(document.createTextNode('X'));
    cell.appendChild(anchor);
    newrow.appendChild(cell);

    anchor=document.createElement('a');
    anchor.href="itemReport.php?itemId="+id;
    anchor.title='view parent';
    anchor.appendChild(document.createTextNode(title));
    cell1.appendChild(anchor);
    newrow.appendChild(cell1);

    cell2.appendChild(document.createTextNode(typename));
    var input=document.createElement('input');
    input.type='hidden';
    input.name='parentId[]';
    input.value=id;
    cell2.appendChild(input);
    newrow.appendChild(cell2);

    document.getElementById("parentlist").appendChild(newrow);
    return true;
};
// ======================================================================================
GTD.ParentSelector.prototype.makeline=function(id,title,type,typename,i,useTypes,onetype) { // display a specific parent, to go into the list of parents
    var line=document.createElement('p'),
        thisi='',
        that=this,
        anchor=document.createElement('a'),
        linetext=title;
    anchor.href='#';
    GTD.addEvent(anchor,'click',function() {
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
GTD.ParentSelector.prototype.refinesearch=function(needle) {
    var i,max,ok,box,skiptype,skipsearch,
        searchstring=document.getElementById('searcherneedle').value.toLowerCase();
        box=document.getElementById('searchresults');
    if (typeof(needle)==='string') { // initialising
        this.qtype=needle;
        document.getElementById('searcherneedle').value='';
        document.getElementById('radio'+this.qtype).checked=true;
    } else if (needle.name==='qtype') {
        this.qtype=needle.value;
    }
    skiptype=(this.ptypes.length<2 || this.qtype==='0');
    skipsearch=(searchstring.length===0);
    max=this.parentIds.length;
    for (i=0;i<max;i++) {
        ok= ( ( skipsearch ||
                (this.ptitleslc[i].indexOf(searchstring)>-1) ||
                this.parentIds[i]==='0') &&
            (skiptype || this.ptypes[i]===this.qtype) );
        box.childNodes[i].style.display=(ok)?'block':'none';
    }
};
// ======================================================================================
GTD.ParentSelector.prototype.search=function() {
    var parenttable,that=this;
    if (this.inSearch) {return false;}
    this.inSearch=true;
    GTD.freeze(true);
    document.getElementById('searcher').style.display='block';
    document.getElementById("searcherneedle").focus();
    document.onkeypress=function(e) {
        var pressed;
        if (window.event) {pressed=window.event.keyCode;} else {pressed=e.keyCode;}
        if (pressed===27) {
            that.close();
            return false;
        }
        return true;
    };
    parenttable=document.getElementById('parenttable');
    oldTablePosition=parenttable.style.position;
    parenttable.style.position='fixed';
    parenttable.style.left=0;
};
// ======================================================================================
GTD.removeParent=function (id) {
    var row=document.getElementById('parentrow'+id);
    row.parentNode.removeChild(row);
};
// ======================================================================================
GTD.resortTable=function (lnk,clid) {
    var i,j,max,td,column,table,itm,itmh,firstRow,newRows,
        allth,ci,sortfn,ARROW;
    max=lnk.childNodes.length;
    td = lnk.parentNode;
    column = clid || td.cellIndex;
    table = getParent(td,'TABLE');

    // Work out a type for the column
    if (table.rows.length <= 1) {return;}
    itm = ts_getInnerText(table.tBodies[0].rows[0].cells[column]);
    itmh = table.tBodies[0].rows[0].cells[column].innerHTML;
    sortfn = ts_sort_caseinsensitive;
    if (itmh.match(/^<input.*(radio|checkbox).*>$/)) {sortfn = ts_sort_checkbox;}
    else if (itm.match(/^\d\d[\/\-]\d\d[\/\-]\d\d\d\d$/)) {sortfn = ts_sort_date;}
    else if (itm.match(/^\d\d[\/\-]\d\d[\/\-]\d\d$/)) {sortfn = ts_sort_date;}
    else if (itm.match(/^[£$]/)) {sortfn = ts_sort_currency;}
    else if (itm.match(/^[\d\.]+$/)) {sortfn = ts_sort_numeric;}
    SORT_COLUMN_INDEX = column;
    firstRow = newRows = [];
    max=table.rows[0].length;
    for (i=0;i<max;i++) { firstRow[i] = table.rows[0][i]; }
    max=table.tBodies[0].rows.length;
    for (j=0;j<max;j++) { newRows[j] = table.tBodies[0].rows[j]; }

    newRows.sort(sortfn);

    if (lnk.getAttribute("sortdir") === 'down') {
        td.className = td.className.replace(/ sort(up|down)/,"") + " sortup";
        newRows.reverse();
        lnk.setAttribute('sortdir','up');
    } else {
        td.className = td.className.replace(/ sort(up|down)/,"") + " sortdown";
        lnk.setAttribute('sortdir','down');
    }

    // We appendChild rows that already exist to the tbody, so it moves them rather than creating new ones
    // don't do sortbottom rows
    max=newRows.length;
    for (i=0;i<max;i++) { if (!newRows[i].className || (newRows[i].className && (newRows[i].className.indexOf('sortbottom') === -1))) {table.tBodies[0].appendChild(newRows[i]);}}
    // do sortbottom rows only
    for (i=0;i<max;i++) { if (newRows[i].className && (newRows[i].className.indexOf('sortbottom') !== -1)) {table.tBodies[0].appendChild(newRows[i]);}}

    // Delete any other arrows there may be showing
    allth = document.getElementsByTagName("th");
    max=allth.length;
    for (ci=0;ci<max;ci++) {
        if (allth[ci].className.match(/.* sort(up|down).*/)) {
            if (allth[ci] !== td && getParent(allth[ci],"table") === getParent(td,"table")) { // in the same table as us?
                allth[ci].className = allth[ci].className.replace(/ sort(up|down)/,"");
            }
        }
    }
};
// ======================================================================================
GTD.showrecurbox=function (what,where) {
    var startdate,t,mth,wk,dte,day,daynum,startday,form,days,recurbox,newhome;
    recurbox=document.getElementById(what);
    newhome=where.parentNode;
    while(newhome.hasChildNodes()) {newhome.removeChild(newhome.lastChild);}
    newhome.appendChild(recurbox);
    recurbox.style.display='block';
    form=getParent(recurbox,'form');
    // if there's already a recurrence value, then we can now end
    if (form.elements.icstext.value!=='') {return false;}
    //  otherwise, populate the recurrence form with useful defaults based on existing deadline/tickler/today
    startdate = form.elements.deadline.value || form.elements.tickledate.value || form.elements.dateCompleted.value;
    startday=new Date();
    if (startdate) {
        t=Date.UTC( startdate.substr(0,4),
                        startdate.substr(5,2)-1,
                        startdate.substr(8,2));
        startday.setTime(t);
    }
    mth=1+startday.getMonth();
    dte=startday.getDate();
    daynum=startday.getDay();
    days=['SU','MO','TU','WE','TH','FR','SA'];
    day=days[daynum];
    wk=Math.round((dte+3)/7);
    form.elements['WEEKLYday[]'][daynum].checked=true;
    form.elements.MONTHLYweekday.value=
        form.elements.YEARLYweekday.value=day;
    form.elements.MONTHLYdate.value=
        form.elements.YEARLYdate.value=dte;
    form.elements.MONTHLYweek.value=
        form.elements.YEARLYweeknum.value=wk;
    form.elements.YEARLYmonth.value=
        form.elements.YEARLYweekmonth.value=mth;
    return false;
};
// ======================================================================================
GTD.tagAdd=function(newtaglink) {
    var tagfield,currentTags,newtag,testtags,rawval;
    tagfield=document.getElementById('tags');
    rawval=tagfield.value;
    currentTags=','+rawval.toLowerCase()+',';
    newtag=newtaglink.text;
    testtags=currentTags.replace(/\s*,\s*/g,',');
    if (testtags.search(','+newtag+',')===-1) {
        if (rawval.search(/,\s*$/)===-1 && rawval.search(/^\s*$/)===-1) {
            tagfield.value=rawval.value+',';
        }
        tagfield.value=tagfield.value+newtag+',';
    }
    return false;
};
// ======================================================================================
GTD.tagKeypress=function(e) {
    var pressed,key;
    if (window.event) {pressed=window.event.keyCode;} else {pressed=e.charCode;}
    key = String.fromCharCode(pressed);

    /* if we're currently offering a tag in a tooltip,
        check to see if enter or tab was pressed, and if so, add the tooltip tag
        to the field, and return false
        if esc pressed, then dismiss the tooltip
    */
        
    
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
// ======================================================================================
GTD.tagShow=function(anchor) {
    var taglist=document.getElementById("taglist");
    if (taglist.style.display==="inline") {
        document.getElementById("taglist").style.display="none";
        anchor.firstChild.textContent='Show all';
    } else {
        document.getElementById("taglist").style.display="inline";
        anchor.firstChild.textContent='Hide all';
    }
    return false;
};
// ======================================================================================
GTD.toggleHidden=function (parent,show,link) {
    var tab=document.getElementById(parent).getElementsByTagName("*");
    for (var i=0;i<tab.length;i++) {
        if (tab[i].className==='togglehidden') {tab[i].style.display=show;}
    }
    document.getElementById(link).style.display='none';
    return false;
};
// ======================================================================================
GTD.validate=function (form) {
    var thisfield,checkType,itemErrorMessage,itemValue,passed,i,formValid,
        formErrorMessage,requiredList,requiredItem,max,dateFormat,fieldRequires;

    // Ensure validate is being used correctly

    if(typeof form.elements.required === 'undefined') {
        document.getElementById("errorMessage").innerHTML="Error: Validate function needs 'required' form field.";
        return false;
    }
    if(typeof form.elements.dateformat === 'undefined') {
        document.getElementById("errorMessage").innerHTML="Error: Validate function needs 'dateformat' form field.";
        return false;
    }

    // Parse required list and check each required field
    formValid=true;
    formErrorMessage="Please correct the following:<br />";
    requiredList = form.required.value.split(",");

    // remove any previous error flags
    max=requiredList.length;
    for(i = 0;i<max;i++){
        requiredItem = requiredList[i].split(":");
        form.elements[requiredItem[0]].className='';
        if (requiredItem[1]==='depends') {form.elements[requiredItem[3]].className='';}
    }

    for(i = 0;i<max;i++){
        requiredItem = requiredList[i].split(":");
        thisfield = form.elements[requiredItem[0]];
        if (thisfield.type==='hidden') {continue;} // don't process hidden fields

        checkType = requiredItem[1];
        itemErrorMessage = requiredItem[2];
        itemValue = thisfield.value;

        switch (checkType) {
            case "date":
                dateFormat = form.dateformat.value;
                passed=checkDate(thisfield,dateFormat);
                break;
            case "notnull":
        		passed=!checkForNull(thisfield);
                break;
            case "depends":
            	fieldRequires = form.elements[requiredItem[3]];
            	passed=checkForNull(thisfield) || !checkForNull(fieldRequires) || (fieldRequires.type==='hidden');
            	if (!passed) {fieldRequires.className='formerror';}
				break;
            default:
                document.getElementById("errorMessage").innerHTML="Error: Required type not valid.";
                return false;
        }
        if (!passed) {
            if (formValid) {thisfield.focus();}
            formValid=false;
            formErrorMessage += itemErrorMessage + "<br />";
            thisfield.className='formerror';
        }
    }

    document.getElementById("errorMessage").innerHTML=(formValid) ? '' : formErrorMessage;
    return formValid;
};
// ======================================================================================
GTD.addEvent(window,'load', function() {
    GTD.focusOnForm();
    sortables_init();
    if (typeof GTD.debugKey!=='undefined') {GTD.debugInit(GTD.debugKey);}
});
window.GTD=GTD;
})();
