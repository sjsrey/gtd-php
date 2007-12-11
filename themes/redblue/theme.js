var gtd_selectLists;
var gtd_listtimer=null;
startList = function() {
	/* here need to sniff IE<7, and Firefox 2 on Mac */
	var browser=navigator.userAgent.toLowerCase();
	var isBadIE=(browser.indexOf(" msie 6")!=-1 || browser.indexOf(" msie 5")!=-1);
	if (isBadIE) { /* document.all&&document.getElementById) { */
		navRoot = document.getElementById("menulist");
		for (i=0; i<navRoot.childNodes.length; i++) {
			node = navRoot.childNodes[i];
			if (node.nodeName=="LI") {
			     node.onmouseover=function(){this.className+=" over";};
			     node.onmouseout =function(){this.className=this.className.replace("over", "");};
			}
		}
        gtd_selectLists = document.getElementsByTagName('select');
        document.getElementById("menudiv").onmouseover=gtd_menumouseover;
        document.getElementById("menudiv").onmouseout =gtd_menumouseout;
    }
}
function gtd_menumouseover() {
    if (gtd_listtimer) clearTimeout(gtd_listtimer);
	gtd_selectlistvisibility("hidden");
}
function gtd_menumouseout() {
    if (gtd_listtimer) clearTimeout(gtd_listtimer);
    gtd_listtimer=setTimeout(function() {gtd_selectlistvisibility("visible");},200);
}
function gtd_selectlistvisibility(vis) {
	for (var counter=0; counter<gtd_selectLists.length; counter++)
	   gtd_selectLists[counter].style.visibility=vis;
}
window.onload=startList;
