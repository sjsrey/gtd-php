// ======================================================================================
function toggleHidden(parent,show,link) {
    var tab=document.getElementById(parent).getElementsByTagName("*");
    for (var i=0;i<tab.length;i++) {
        if (tab[i].className=='togglehidden') {tab[i].style.display=show;}
    }
    document.getElementById(link).style.display='none';
}
// ======================================================================================
var gtd_freezediv;
function gtd_freezer_init() {
	gtd_freezediv=document.createElement('div');
	gtd_freezediv.id="freezer";
    gtd_freezediv.style.display="none";
	gtd_freezediv.appendChild(document.createElement('span')); // necessary for problem with addevent betweeen Opera and Safari
	document.body.appendChild(gtd_freezediv);
} // end of freezer.init
// ======================================================================================
function gtd_freeze (tofreeze) {
	var freezestatus;
	if (typeof(gtd_freezediv)=="undefined") {gtd_freezer_init();}
	freezestatus=(tofreeze)?"block":"none";
	gtd_freezediv.style.display=freezestatus;
}
// ======================================================================================
var parentIds;
var ptitles;
var ptitleslc;
var ptypes;
var searchtype;
var qtype;
function gtd_searchdiv_init(ids,titles,types,onetype) {
    var myargs;
    parentIds=ids;
    ptypes=types;
    ptitles=[];
    ptitleslc=[];

    var box=document.getElementById('searchresults');
    if (box.hasChildNodes()) {box.removeChild(box.lastChild);}
    var line; var anchor; var linetext; var thistype;
    var useTypes=(types.length);
    if (!useTypes) {qtype=thistype=mapTypeToName(onetype);}

    for (var i=0;i<titles.length;i++) {
        ptitles[i]=unescape(titles[i]);
        ptitleslc[i]=ptitles[i].toLowerCase();
        if (useTypes) {thistype=mapTypeToName(types[i]);}
        
        anchor=document.createElement('a');
        anchor.href='#';
        anchor.appendChild(document.createTextNode('+'));
        anchor.id=parentIds[i];
        anchor.ptitle=ptitles[i];
        anchor.ptype=thistype;
        addEvent(anchor,'click',function(e){
            myargs=(e.srcElement===undefined)?this:e.srcElement;
            gtd_gotparent(myargs.id,myargs.ptitle,myargs.ptype);
        });

        line=document.createElement('p');
        line.appendChild(anchor);

        linetext = ptitles[i];
        if (useTypes) {linetext += " ("+thistype+")";}
        line.appendChild(document.createTextNode(linetext));
        line.style.display=(!useTypes || types[i]==onetype)?'block':'none';
        box.appendChild(line);
    }
}
// ======================================================================================
function gtd_gotparent(id,title,type) {
    var myargs;
    if (document.getElementById('parentrow'+id)) {return;}
    var newrow=document.createElement('tr');
    newrow.id='parentrow'+id;
    
    var cell=document.createElement('td');
    var anchor=document.createElement('a');
    anchor.href='#';
    anchor.id=id;
    addEvent(anchor,'click',function(e){
            myargs=(e.srcElement===undefined)?this:e.srcElement;
            removeParent(myargs.id);
        });
    anchor.title='remove as parent';
    anchor.className='remove';
    anchor.appendChild(document.createTextNode('X'));
    cell.appendChild(anchor);
    newrow.appendChild(cell);
    
    cell=document.createElement('td');
    anchor=document.createElement('a');
    anchor.href="itemReport.php?itemId="+id;
    anchor.title='view parent';
    anchor.appendChild(document.createTextNode(title));
    cell.appendChild(anchor);
    newrow.appendChild(cell);

    cell=document.createElement('td');
    cell.appendChild(document.createTextNode(type));
    var input=document.createElement('input');
    input.type='hidden';
    input.name='parentId[]';
    input.value=id;
    cell.appendChild(input);
    newrow.appendChild(cell);

    document.getElementById("parentlist").appendChild(newrow);
}
// ======================================================================================
var inSearch;
var oldTablePosition;
function gtd_search() {
    if (inSearch) {return;}
    inSearch=true;
    gtd_freeze(true);
    document.getElementById('searcher').style.display='block';
    document.getElementById("searcherneedle").focus();
    document.onkeypress=gtd_gotkey;
    oldTablePosition=document.getElementById('parenttable').style.position;
    document.getElementById('parenttable').style.position='fixed';
    document.getElementById('parenttable').style.left=0;
}
// ======================================================================================
function gtd_closesearch() {
    document.getElementById('searcher').style.display='none';
    gtd_freeze(false);
    document.getElementById('parenttable').style.position=oldTablePosition;
    focusOnForm(0);
    inSearch=false;
}
// ======================================================================================
function gtd_gotkey(key) {
    var pressed;
    if (window.event) {pressed=window.event.keyCode;} else {pressed=key.keyCode;}
    if (pressed===27) {
        gtd_closesearch();
        document.onkeypress=null;
        return false;
    }
}
// ======================================================================================
function gtd_refinesearch(needle) {
    var searchstring; var i;
    if (typeof(needle)=='string') { // initialising
        qtype=needle;
        document.getElementById('searcherneedle').value='';
        document.getElementById('radio'+qtype).checked=true;
    } else if (needle.name=='qtype') {
        qtype=needle.value;
    }
    searchstring=document.getElementById('searcherneedle').value.toLowerCase();
    var skiptype=(ptypes.length<2 || qtype=='0');
    var skipsearch=(searchstring.length===0);
    var box=document.getElementById('searchresults');
    var ok;
    for (i=0;i<parentIds.length;i++) {
        ok= ( (skipsearch || ptitleslc[i].indexOf(searchstring)>-1) && (skiptype || ptypes[i]==qtype) );
        box.childNodes[i].style.display=(ok)?'block':'none';
    }
}
// ======================================================================================
function removeParent(id) {
    var row=document.getElementById('parentrow'+id);
    row.parentNode.removeChild(row);
}
// ======================================================================================
var typenames; // static variable for mapTypeToName function
function mapTypeToName(type) {
    if (typeof(type)=='object') {
        typenames=type;
        return true;
    } else {return typenames[type];}
}
// ======================================================================================
