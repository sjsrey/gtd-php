<?php
$title='Summary';
include_once 'header.inc.php';

$values=array();

//SQL Code

//get # space contexts
$res = query("countspacecontexts",$values);
$numbercontexts=(is_array($res[0]))?(int) $res[0]['COUNT(*)']:0;

//count active items
$values['type'] = "a";
$values['isSomeday'] = "n";
$values['childfilterquery'] = " WHERE ".sqlparts("typefilter",$values)
                                ." AND ".sqlparts("issomeday",$values)
                                ." AND ".sqlparts("activeitems",$values)
                                ." AND ".sqlparts("pendingitems",$values);
$values['filterquery'] = " WHERE ".sqlparts("liveparents",$values);
//get # nextactions
$res = query("countnextactions",$values);
$actionsdue=$nextactionsdue=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0);
if (is_array($res))
    foreach ($res as $line) {
        $nextactionsdue[$line['duecategory']]=$line['nnextactions'];
        $actionsdue[$line['duecategory']]=$line['nactions'];
    }
$numbernextactions=array_sum($nextactionsdue);
$numberactions=array_sum($actionsdue);

// get and count active projects
$values['type']= "p";
$values['isSomeday'] = "n";

$stem  = " WHERE ".sqlparts("typefilter",$values)
        ." AND ".sqlparts("activeitems",$values)
        ." AND ".sqlparts("pendingitems",$values);

$values['filterquery'] = $stem." AND ".sqlparts("issomeday",$values);
$pres = query("getitems",$values);
$numberprojects=($pres)?count($pres):0;

//get and count someday projects
$values['isSomeday'] = "y";
$values['filterquery'] = $stem." AND ".sqlparts("issomeday",$values);
$sm = query("getitems",$values);
$numbersomeday=($sm)?count($sm):0;

// count inbox items
$values=array('type'=>'i');
$values['filterquery']=' WHERE '.sqlparts('typefilter',$values)
                       .' AND '.sqlparts('pendingitems',$values);
$result = query("counttype",$values);
$inboxcount= ($result) ? $result[0]['cnt'] : 0;

gtd_handleEvent(_GTD_ON_DATA,$pagename);
/*----------------------------------------------------------------
    display page
*/
echo '<h4>Today is '.date($_SESSION['config']['datemask']).'. (Week '.date("W").'/52 &amp; Day '.date("z").'/'.(365+date("L")).')</h4>'."\n";

echo "<div class='reportsection'>\n";

if($numbernextactions==1) {
    $verb='is';
    $plural='';
} else {
    $verb='are';
    $plural='s';
}
$space1=" in $numbercontexts <a href='reportContext.php'>Spatial Context"
        .(($numbercontexts==1)?'':'s') . "</a>";
if ($_SESSION['config']["contextsummary"]) {
    $space2='';
} else {
    $space2=$space1;
    $space1='';
}
echo "<p>There $verb $numbernextactions"
    ," <a href='listItems.php?type=a&amp;nextonly=true'>Next Action$plural</a> pending$space1, of which <span"
    ,($nextactionsdue['2']==0)?'>' : " class='due'>"
    ,($nextactionsdue['2']==1)?'1 action is':"{$nextactionsdue['2']} actions are"
    ," due today</span>, <span"
    ,($nextactionsdue['3']==0)?'>' : " class='overdue'>"
    ,($nextactionsdue['3']==1)?'1 is':"{$nextactionsdue['3']} are"
    ," now overdue</span>, and <span"
    ,($nextactionsdue['1']==0)?'>' : " class='comingdue'>"
    ,($nextactionsdue['1']==1)?"1 has its deadline":"{$nextactionsdue['1']} have deadlines"
    ,"  in the coming 7 days</span>. Altogether, there "
    ,($numberactions==1)?'is 1':"are $numberactions"
    ," <a href='listItems.php?type=a'>Action"
    ,($numberactions==1)?'':'s'
    ,"</a>$space2, \n"
    ,($inboxcount)?"and <a href='listItems.php?type=i'>$inboxcount inbox item(s)</a>":'and no inbox items'
    ,".</p>\n";

echo "</div>\n<div class='reportsection'>\n";

$numdue=0;
$numoverdue=0;
for ($i=0; $i<$numberprojects;$i++) {
    if (empty($pres[$i]['deadline'])) {
        // do nothing
    } elseif ($pres[$i]['deadline'] < date("Y-m-d")) {
        $pres[$i]['td.class']='celloverdue';
        $pres[$i]['td.title']='Project overdue';
        $numoverdue++;
    } elseif ($pres[$i]['deadline'] === date("Y-m-d")) {
        $pres[$i]['td.class']='celldue';
        $pres[$i]['td.title']='Project due for completion today';
        $numdue++;
    }
}
echo '<p>';
if($numberprojects==1){
    echo 'There is 1 active <a href="listItems.php?type=p">Project</a>'
        ,($numdue)    ?", which is <span class='due'>due today</span>":''
        ,($numoverdue)?", which is <span class='overdue'>overdue</span>"  :'';
}else{
    echo "There are $numberprojects active <a href='listItems.php?type=p'>Projects</a>"
        ," with <span"
        ,($numdue)?" class='due'>":'>'
        ,"$numdue due today</span> and <span"
        ,($numoverdue)?" class='overdue'>":'>'
        ,"$numoverdue overdue</span>";
}
echo ".</p>\n";

if($numberprojects) {
    echo "<table summary='table of projects'><tbody>\n"
        ,columnedTable(3,$pres)
        ,"</tbody></table>\n";
}
echo "</div>\n";

echo "<div class='reportsection'>\n";

if($numbersomeday==1){
    echo '<p>There is 1 <a href="listItems.php?type=p&amp;someday=true">Someday/Maybe</a>.</p>'."\n";
}else{
    echo '<p>There are ' .$numbersomeday.' <a href="listItems.php?type=p&amp;someday=true">Someday/Maybes</a>.</p>'."\n";
}

if($numbersomeday) {
    echo "<table summary='table of someday/maybe items'><tbody>\n"
        ,columnedTable(3,$sm)
        ,"</tbody></table>\n";
}
echo "</div>\n";

if ( $_SESSION['config']['showTreeInSummary'] ) {
	echo "<div class='reportsection'>\n";
	$_REQUEST['addonid']="tree";
	$_REQUEST['url']="maketree.inc.php";
	if ( !array_key_exists('showfrom', $_GET ) ) $_GET['showfrom']="p";
	if ( !array_key_exists( 'showto',$_GET ) ) $_GET['showto']="p";
	include 'addon.php';
	echo "</div>\n";
} 
include_once 'footer.inc.php';
?>
