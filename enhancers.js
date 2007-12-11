// ======================================================================================
function toggleHidden(parent,show,link) {
    var tab=document.getElementById(parent).getElementsByTagName("*");
    for (var i=0;i<tab.length;i++) {
        if (tab[i].className=='togglehidden')
            tab[i].style.display=show;
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
	if( navigator.userAgent.indexOf('Konqueror')>-1) {
        gtd_freezediv.style.backgroundColor="transparent"; // because Konqueror doesn't do transparency/opacity
    }
}; // end of freezer.init
// ======================================================================================
function gtd_freeze (tofreeze) {
	var freezestatus;
	if (typeof(gtd_freezediv)=="undefined") gtd_freezer_init();
	if (tofreeze) freezestatus="block"; else freezestatus="none";
	gtd_freezediv.style.display=freezestatus;
};
// ======================================================================================
var parentIds;
var ptitles;
var ptitleslc;
var ptypes;
var searchtype;
var qtype;
function gtd_searchdiv_init(ids,titles,types,onetype) {
    parentIds=ids;
    ptypes=types;
    ptitles=new Array();
    ptitleslc=new Array();

    var box=document.getElementById('searchresults');
    if (box.hasChildNodes()) box.removeChild(box.lastChild);
    var line; var anchor; var linetext; var thistype;
    var useTypes=(types.length);
    if (!useTypes) qtype=thistype=mapTypeToName(onetype);

    for (var i=0;i<titles.length;i++) {
        ptitles[i]=unescape(titles[i]);
        ptitleslc[i]=ptitles[i].toLowerCase();
        if (useTypes) thistype=mapTypeToName(types[i]);
        
        line=document.createElement('p');
        anchor=document.createElement('a');
        anchor.href="javascript:gtd_gotparent("+parentIds[i]+',"'
                + ptitles[i]+'","' + thistype+'");';

        anchor.appendChild(document.createTextNode('+'));
        line.appendChild(anchor);
        linetext = ptitles[i];
        if (useTypes) linetext += " ("+thistype+")";
        line.appendChild(document.createTextNode(linetext));
        line.style.display=(!useTypes || types[i]==onetype)?'block':'none';
        box.appendChild(line);
    }
}
// ======================================================================================
function gtd_gotparent(id,title,type) {
    if (document.getElementById('parentrow'+id)) return;
    var newrow=document.createElement('tr');
    newrow.id='parentrow'+id;
    
    var cell=document.createElement('td');
    var anchor=document.createElement('a');
    anchor.href="javascript:removeParent("+id+");"
    anchor.title='remove as parent';
    anchor.className='remove';
    anchor.appendChild(document.createTextNode('X'));
    cell.appendChild(anchor)
    newrow.appendChild(cell);
    
    cell=document.createElement('td');
    anchor=document.createElement('a');
    anchor.href="itemReport.php?itemId="+id;
    anchor.title='view parent';
    anchor.appendChild(document.createTextNode(title));
    cell.appendChild(anchor)
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
    if (inSearch) return;
    inSearch=true;
    gtd_freeze(true);
    document.getElementById('searcher').style.display='block';
    document.getElementById("searcherneedle").focus();
    document.onkeypress=gtd_gotkey;
    with (document.getElementById('parenttable').style) {
        oldTablePosition=position;
        position='fixed';
        left=0;
    }
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
    if (window.event) pressed=window.event.keyCode; else pressed=key.keyCode;
    if (pressed===27) {
        gtd_closesearch();
        document.onkeypress=null;
        return false;
    }
}
// ======================================================================================
function gtd_refinesearch(needle) {
    if (typeof(needle)=='string') { // initialising
        qtype=needle;
        document.getElementById('searcherneedle').value='';
        document.getElementById('radio'+qtype).checked=true;
    } else if (needle.name=='qtype') {
        qtype=needle.value;
    }
    searchstring=document.getElementById('searcherneedle').value.toLowerCase();
    var skiptype=(ptypes.length<2 || qtype=='0');
    var skipsearch=(searchstring.length==0);
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
        return true
    } else return typenames[type];
}
// ======================================================================================
