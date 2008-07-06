/*jslint browser: true, eqeqeq: true, nomen: true, undef: true */
/*global GTD,unescape,Calendar */
/* global unescape: because decodeURIcomponent corrupts some characters in AJAX
                    but this will change when we get proper i18n/utf8/mbcs handling in gtd-php
    
   global GTD:      object for holding public functions and public variables
   global Calendar: javascript routine for handling calendar UI, in calendar.js
   ======================================================================================
*/
(function() {
var freezediv,focusOn,grabKey,oldTablePosition,SORT_COLUMN_INDEX;
// ======================================================================================
function toggleVis (thisRule) {
/*
 * toggle the display value in a particular CSS rule
 *
 * thisRule: the DOM object of the CSS rule
 */
	thisRule.style.display=(thisRule.style.display==="none")?"block":"none";
}
// ======================================================================================
function keyPressHandler(e) {
/*
 * event-handler for key presses, for when we are toggling the display of debug-log text
 *
 *  e: DOM event object
 */
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
function manualcellindex(cell) {
/*
 * IE7 calculates cellIndex incorrectly when some cells are hidden,
 *  so a manual cellIndex is required.
 * This won't handle COLSPAN gracefully.  I expect.
 *
 * cell: the DOM object of the cell we want the index of
 */
    var i,row,max,testcell,j=0;
    row=cell.parentNode;
    max=row.childNodes.length;
    for (i=0;i<max;i++) {
        testcell=row.childNodes[i];
        if (cell===row.childNodes[i]) {return j;}
        if ((testcell.tagName==='TH') || (testcell.tagName==='TD')) {j++;}
    }
    return false;
}
// ======================================================================================
function getDocPath(){
    return window.location.pathname.replace(/^(.*\/)[^\/]*$/,"$1");
}
// ======================================================================================
/*
 * sortTable amended for GTD-PHP
 * Based on code from: http://kryogenix.org/code/browser/sorttable/
 * Copyright (c) 1997-2007 Stuart Langridge
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
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
        if (cell.className.match('nosort')) {continue;}
        txt = ts_getInnerText(cell);
        cell.innerHTML = '<a href="#" class="sortheader" '+
        'onclick="GTD.resortTable(this);return false;">' +
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
/*
    end of sorting functions
    ======================================================================================
    start of declarations of public functions
*/
/*  Copyright Mihai Bazon, 2002, 2003  |  http://dynarch.com/mishoo/
 * ---------------------------------------------------------------------------
 * modified for gtd-php
 *
 * The DHTML Calendar
 *
 * Details and latest version at:
 * http://dynarch.com/mishoo/calendar.epl
 *
 * This script is distributed under the GNU Lesser General Public License.
 * Read the entire license text here: http://www.gnu.org/licenses/lgpl.html
 *
 * This file defines helper functions for setting up the calendar.  They are
 * intended to help non-programmers get a working calendar on their site
 * quickly.  This script should not be seen as part of the calendar.  It just
 * shows you what one can do with the calendar, while in the same time
 * providing a quick and simple method for setting it up.  If you need
 * exhaustive customization of the calendar creation process feel free to
 * modify this code to suit your needs (this is recommended and much better
 * than modifying calendar.js itself).
 */

// $Id: calendar-setup.js,v 1.2 2006/07/08 19:20:34 serge Exp $

/**
 *  This function "patches" an input field (or other element) to use a calendar
 *  widget for date selection.
 *
 *  The "params" is a single object that can have the following properties:
 *
 *    prop. name   | description
 *  -------------------------------------------------------------------------------------------------
 *   inputField    | the ID of an input field to store the date
 *   displayArea   | the ID of a DIV or other element to show the date
 *   button        | ID of a button or other element that will trigger the calendar
 *   eventName     | event that will trigger the calendar, without the "on" prefix (default: "click")
 *   ifFormat      | date format that will be stored in the input field
 *   daFormat      | the date format that will be used to display the date in displayArea
 *   singleClick   | (true/false) wether the calendar is in single click mode or not (default: true)
 *   firstDay      | numeric: 0 to 6.  "0" means display Sunday first, "1" means display Monday first, etc.
 *   align         | alignment (default: "Br"); if you don't know what's this see the calendar documentation
 *   range         | array with 2 elements.  Default: [1900, 2999] -- the range of years available
 *   weekNumbers   | (true/false) if it's true (default) the calendar will display week numbers
 *   flat          | null or element ID; if not null the calendar will be a flat calendar having the parent with the given ID
 *   flatCallback  | function that receives a JS Date object and returns an URL to point the browser to (for flat calendar)
 *   disableFunc   | function that receives a JS Date object and should return true if that date has to be disabled in the calendar
 *   onSelect      | function that gets called when a date is selected.  You don't _have_ to supply this (the default is generally okay)
 *   onClose       | function that gets called when the calendar is closed.  [default]
 *   onUpdate      | function that gets called after the date is updated in the input field.  Receives a reference to the calendar.
 *   date          | the date that the calendar will be initially displayed to
 *   showsTime     | default: false; if true the calendar will include a time selector
 *   timeFormat    | the time format; can be "12" or "24", default is "12"
 *   electric      | if true (default) then given fields/date areas are updated for each move; otherwise they're updated only on close
 *   step          | configures the step of the years in drop-down boxes; default: 2
 *   position      | configures the calendar absolute position; default: null
 *   cache         | if "true" (but default: "false") it will reuse the same calendar object, where possible
 *   showOthers    | if "true" (but default: "false") it will show days from other months too
 *
 *  None of them is required, they all have default values.  However, if you
 *  pass none of "inputField", "displayArea" or "button" you'll get a warning
 *  saying "nothing to setup".
 */
if (typeof Calendar!=='undefined') {
    Calendar.setup = function (params) {
    	function param_default(pname, def) { if (typeof params[pname] === "undefined") { params[pname] = def; } }

    	param_default("inputField",     null);
    	param_default("displayArea",    null);
    	param_default("button",         null);
    	param_default("eventName",      "click");
    	param_default("ifFormat",       "%Y-%m-%d");
    	param_default("daFormat",       "%Y-%m-%d");
    	param_default("singleClick",    true);
    	param_default("disableFunc",    null);
    	param_default("dateStatusFunc", params.disableFunc);	// takes precedence if both are defined
    	param_default("dateText",       null);
    	param_default("firstDay",       null);
    	param_default("align",          "Br");
    	param_default("range",          [1900, 2999]);
    	param_default("weekNumbers",    true);
    	param_default("flat",           null);
    	param_default("flatCallback",   null);
    	param_default("onSelect",       null);
    	param_default("onClose",        null);
    	param_default("onUpdate",       null);
    	param_default("date",           null);
    	param_default("showsTime",      false);
    	param_default("timeFormat",     "24");
    	param_default("electric",       true);
    	param_default("step",           2);
    	param_default("position",       null);
    	param_default("cache",          false);
    	param_default("showOthers",     false);
    	param_default("multiple",       null);

    	var tmp = ["inputField", "displayArea", "button"];
    	for (var i in tmp) {
    		if (typeof params[tmp[i]] === "string") {
    			params[tmp[i]] = document.getElementById(params[tmp[i]]);
    		}
    	}
    	if (!(params.flat || params.multiple || params.inputField || params.displayArea || params.button)) {
    		alert("Calendar.setup:\n  Nothing to setup (no fields found).  Please check your code");
    		return false;
    	}

    	function onSelect(cal) {
    		var p = cal.params;
    		var update = (cal.dateClicked || p.electric);
    		if (update && p.inputField) {
    			p.inputField.value = cal.date.print(p.ifFormat);
    			if (typeof p.inputField.onchange === "function") {
    				p.inputField.onchange();
                }
    		}
    		if (update && p.displayArea) {
    			p.displayArea.innerHTML = cal.date.print(p.daFormat);
            }
    		if (update && typeof p.onUpdate === "function") {
    			p.onUpdate(cal);
            }
    		if (update && p.flat) {
    			if (typeof p.flatCallback === "function") {
    				p.flatCallback(cal);
                }
    		}
    		if (update && p.singleClick && cal.dateClicked) {
    			cal.callCloseHandler();
            }
    	}
        //------------------------------------------------------------------------
        function showCal() {
    		var dateEl = params.inputField || params.displayArea;
    		var dateFmt = params.inputField ? params.ifFormat : params.daFormat;
    		var mustCreate = false;
    		var cal = window.calendar;
    		if (dateEl) {
    			params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
            }
    		if (!(cal && params.cache)) {
    			window.calendar = cal = new Calendar(params.firstDay,
    							     params.date,
    							     params.onSelect || onSelect,
    							     params.onClose || function(cal) { cal.hide(); });
    			cal.showsTime = params.showsTime;
    			cal.time24 = (params.timeFormat === "24");
    			cal.weekNumbers = params.weekNumbers;
    			mustCreate = true;
    		} else {
    			if (params.date) {cal.setDate(params.date);}
    			cal.hide();
    		}
    		if (params.multiple) {
    			cal.multiple = {};
    			for (var i = params.multiple.length; --i >= 0;) {
    				var d = params.multiple[i];
    				var ds = d.print("%Y%m%d");
    				cal.multiple[ds] = d;
    			}
    		}
    		cal.showsOtherMonths = params.showOthers;
    		cal.yearStep = params.step;
    		cal.setRange(params.range[0], params.range[1]);
    		cal.params = params;
    		cal.setDateStatusHandler(params.dateStatusFunc);
    		cal.getDateText = params.dateText;
    		cal.setDateFormat(dateFmt);
    		if (mustCreate) {cal.create();}
    		cal.refresh();
    		if (!params.position) {
    			cal.showAtElement(params.button || params.displayArea || params.inputField, params.align);
    		} else {
    			cal.showAt(params.position[0], params.position[1]);
            }
    		return false;
        }
        //------------------------------------------------------------------------
        /* these events to show the calendar are inclusive OR, not eXclusive OR
         * it's possible that more than one route will be used to show the calendar.
         * In this case, either clicking on the calendar field, or on the button next to it
         */
        if (params.button)      {params.button[     "on" + params.eventName]=showCal;}
        if (params.displayArea) {params.displayArea["on" + params.eventName]=showCal;}
        if (params.inputField)  {params.inputField[ "on" + params.eventName]=showCal;}
    };
}
// ======================================================================================
if (typeof window.GTD==='undefined') {window.GTD={};}
GTD=window.GTD;
// ======================================================================================
GTD.addEvent=function ( obj, type, fn ) {
/* (c) John Resig - open source
 * a cross-browser function to attach events to DOM objects
 *
 * obj:  the DOM object of the event we are attaching to
 * type: string containing the name of the event to handle
 * fn:   the function to be triggered when the event occurs
 */
    if ( obj.attachEvent ) {
        obj['e'+type+fn] = fn;
        obj[type+fn] = function(){obj['e'+type+fn]( window.event );};
        obj.attachEvent( 'on'+type, obj[type+fn] );
    } else { obj.addEventListener( type, fn, false ); }
};
// ======================================================================================
GTD.completeToday=function (datefield) {
/*
 * enter today's date into the completion date field
 *
 * datefield: string of the id of the date field
 */
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
GTD.confirmDelete=function(elem) {
/*
 * confirm that the user wishes to delete the current item in item.php
 * elem: 
 */
   var myform;
   if (confirm("Delete this item?")) {
       myform=document.getElementById('itemform');
       myform.elements.doDelete.value='y';
       myform.submit();
   }
   return false;
};
// ======================================================================================
GTD.cookieGet=function (name,path) {
/* TODO - confirm path
 * get a cookie
 * returns: value of the cookie
 *
 * name: name of the cookie
 */
    if (!document.cookie) {return null;}
    var i,max,cookies,
        testval=encodeURIComponent(name)+'=',
        namelen=testval.length;

    cookies = document.cookie.split(';');
    max=cookies.length;
    for (i=0; i<max; i++) {
        var cookie = cookies[i].replace(/^ *(.*) *$/,"$1");
        // Does this cookie string begin with the name we want?
        if (cookie.substring(0, namelen)===testval) {
            return decodeURIComponent(cookie.substring(namelen));
        }
    }
    return null;
};
//--------------------------------------------------
GTD.cookieSet=function (name,value,path,maxagedays) {
/*
 * Set a cookie
 *
 * name: name of the cookie
 * value: value of the cookie
 * path: path of the cookie (defaults to current path)
 * maxagedays: number of days until cookie should expire (defaults to 1 year)
 */
    var maxage,expires;
    if (path===undefined) {path=getDocPath();}
    if (maxagedays===undefined) {maxagedays=365;}
    expires=new Date();
    maxage=maxagedays * 24 * 60 * 60;
    if (value === null) {
        value = '';
        maxage = -1;
        expires.setTime(expires.getTime() - 1);
    } else {
        expires.setTime(expires.getTime() + maxage*1000);
    }
    document.cookie = encodeURIComponent(name)+'='+encodeURIComponent(value)+
        '; max-age='+maxage+
        '; expires='+expires.toGMTString()+
        '; path='+path;
};
// ======================================================================================
GTD.debugInit=function (keyToCatch) {
/*
 * initialise the key-handler, passing through the user-specified key that will toggle display of debug-logs
 *
 * keyToCatch: key to toggle, e.g. 'h'
 */
	grabKey=keyToCatch;
	GTD.addEvent(document,'keypress',keyPressHandler);
};
// ======================================================================================
GTD.filtertoggle=function (which) {
/*
 * toggle the enabled/disabled status of form elements in the filter form in listItems
 *
 * which:
 */
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
    // always force the core filter items to be enabled:
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
/*
 * pass the focus to a specific item on a form
 *
 * id: if present, this will specify the id of the form element to be focussed.
 *     In its absence, if we've previously called this function, then recall which
 *     element was focussed then (stored in global variable focusOn), and focus on that again.
 *     If there is no id, and no focusOn, then the first active form element will receive focus
 */
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
/*
 * hide all on-screen elements with a blanket DIV laid over everything
 *
 * tofreeze: boolean - true if overlaying blanket, false if removing it
 */
	if (typeof freezediv==='undefined') { // blanket DIV doesn't exist yet, so create it
    	freezediv=document.createElement('div');
    	freezediv.id="freezer";
        freezediv.style.display="none";
    	//freezediv.appendChild(document.createElement('span')); // necessary for problem with addevent betweeen Opera and Safari
    	document.body.appendChild(freezediv);
    }
	freezediv.style.display=(tofreeze)?"block":"none";
};
// ======================================================================================
GTD.initcalendar=function(container) {
/*
 * initialise the calendars
 *
 * container: the DOM item within which the calendar triggers are held
 */
    var i,id,allinputs,max,firstDayOfWeek,classRegExp;
    firstDayOfWeek=parseInt(document.getElementById('firstDayOfWeek').value,10);
    allinputs=container.getElementsByTagName('input');
    classRegExp=new RegExp("(^|\\s)hasdate(\\s|$)");
    max=allinputs.length;
    for (i=0;i<max;i++) {
        if (classRegExp.test(allinputs[i].className) ) {
            id=allinputs[i].id;
            Calendar.setup( { firstDay  : firstDayOfWeek,
                inputField: id,
                button    : id+'_trigger'
            });
        }
    }
};
// ======================================================================================
GTD.ParentSelector=function(ids,titles,types,onetype) {
/*
 * constructor for the dynamic parent-selector in item.php
 *
 * ids:
 * titles:
 * types:
 * onetype:
 */
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
}; 
// -------------------------------------------------------------------------
GTD.ParentSelector.prototype.close=function() {
/*
 * close the parent-selector
 */
    document.onkeypress=null;
    document.getElementById('searcher').style.display='none';
    GTD.freeze(false);
    document.getElementById('parenttable').style.position=oldTablePosition;
    GTD.focusOnForm(0);
    this.inSearch=false;
};
// -------------------------------------------------------------------------
GTD.ParentSelector.prototype.gocreateparent=function(id,title,type,typename,rownum) {
/*
 * user has requested to create a new item, which will become the parent of the current item
 *
 * id:
 * title:
 * type:
 * typename:
 * rownum:
 */
    document.forms[0].afterCreate.value=
        document.forms[0].referrer.value=
        'item.php?nextId=0&amp;type='+type;
    document.forms[0].submit();
    return false;
};
// -------------------------------------------------------------------------
GTD.ParentSelector.prototype.gotparent=function (id,title,type,typename,rownum) {
/*
 * add the clicked parent to the list of the item's parents
 *
 * id:
 * title:
 * type:
 * typename:
 * rownum:
 */
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
// -------------------------------------------------------------------------
GTD.ParentSelector.prototype.makeline=function(id,title,type,typename,i,useTypes,onetype) {
/*
 * create a line to go into the list of parents, displaying a specific parent
 *
 * id:
 * title:
 * type:
 * typename:
 * i:
 * useTypes:
 * onetype:
 */
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
// -------------------------------------------------------------------------
GTD.ParentSelector.prototype.refinesearch=function(needle) {
/*
 * refine the list of parents displayed, based on a partial string entered by the user
 *
 * needle:
 */
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
// -------------------------------------------------------------------------
GTD.ParentSelector.prototype.search=function() {
/*
 * something to do with setting up the auto-search in the parent box
 *
 */
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
// -------------------------------------------------------------------------
GTD.removeParent=function (id) {
/*
 * remove a parent from the displayed list of parents for this item
 *
 * id: string or integer- the itemId of the parent being removed
 */
    var row=document.getElementById('parentrow'+id);
    row.parentNode.removeChild(row);
};
/*
    // end of ParentSelector constructor
    ======================================================================================
*/
GTD.resortTable=function (lnk) {
/*
 * user has clicked on a column-heading: sort the table by that column
 *
 * lnk: the element that the user clicked
 */
    var i,j,max,td,column,table,itm,itmh,firstRow,newRows,allth,ci,sortfn,
        re=/ sort(up|down)/;
    max=lnk.childNodes.length;
    td = getParent(lnk,'TD') || getParent(lnk,'TH');
    column = manualcellindex(td);
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
        td.className = td.className.replace(re,"") + " sortup";
        newRows.reverse();
        lnk.setAttribute('sortdir','up');
    } else {
        td.className = td.className.replace(re,"") + " sortdown";
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
        if (allth[ci].className.match(re)) {
            if (allth[ci] !== td && getParent(allth[ci],"table") === getParent(td,"table")) { // in the same table as us?
                allth[ci].className = allth[ci].className.replace(re,"");
            }
        }
    }
};
// ======================================================================================
GTD.showrecurbox=function (what,where) {
/*
 * populates and displayes the custom recurrence box on item.php
 *
 * what:
 * where:
 */
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
/*
 * in item.php, user has clicked on one of the tag names: add it to the list of tags for this object
 *
 * newtaglink: DOM object of tag being clicked
 */
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
GTD.tagShow=function(anchor) {
/*
 * user is toggling the display of all tags in item.php
 * anchor: the DOM object of the link being clicked
 */
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
GTD.toggleHidden=function (parent,link,show) {
/*
 * Reveals contents of a hidden section of an itemReport table,
 *  e.g. tickled items, or all completed items
 *
 * parent: id of table
 * link:  the id of the element that the user pressed to reveal the section - we can dispose of that element, now
 * show: string indicating whether revealed item is a block, table-row, inline, block-inline, etc.
 */
    var tab=document.getElementById(parent).getElementsByTagName("*");
    for (var i=0;i<tab.length;i++) {
        if (tab[i].className==='togglehidden') {tab[i].style.display=show;}
    }
    document.getElementById(link).style.display='none';
    return false;
};
// ======================================================================================
GTD.validate=function (form) {
/*
 * validate entries on a form, when it is submitted
 *
 * form: DOM object of the form to be validated
 */
    //------------------------------------------------------------------------
    function checkForNull(field) {
    /*
     * utility function for validate to check whether a field is empty
     */
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
    //------------------------------------------------------------------------
    function checkDate(field,dateFormat) {
    /*
     * utility function for validate to check a date is valid
     */
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
        return tst.match(dateRegEx);
    }
    //------------------------------------------------------------------------
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
/*
 * functions to be called when everything is loaded
 */
    GTD.focusOnForm();
    sortables_init();
    if (typeof GTD.debugKey!=='undefined') {GTD.debugInit(GTD.debugKey);}
});
window.GTD=GTD;
})();
