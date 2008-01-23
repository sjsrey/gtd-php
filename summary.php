<?php
//INCLUDES
include_once('header.php');

$values=array();

//SQL Code

//Select notes
$values['filterquery'] = " WHERE ".sqlparts("notefilter",$config,$values);
$reminderresult = query("getnotes",$config,$values,$sort);

//get # space contexts
$res = query("countspacecontexts",$config,$values,$sort);
$numbercontexts=(is_array($res[0]))?(int) $res[0]['COUNT(*)']:0;

//count active items
$values['type'] = "a";
$values['isSomeday'] = "n";
$values['childfilterquery'] = " WHERE ".sqlparts("typefilter",$config,$values)
                                ." AND ".sqlparts("issomeday",$config,$values)
                                ." AND ".sqlparts("activeitems",$config,$values)
                                ." AND ".sqlparts("pendingitems",$config,$values);
$values['parentfilterquery'] = " WHERE " .sqlparts("liveparents",$config,$values);
//get # nextactions
$res = query("countnextactions",$config,$values,$sort);
$nextactionsdue=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0);
if (is_array($res))
    foreach ($res as $line)
        $nextactionsdue[$line['duecategory']]=$line['nnextactions'];
$numbernextactions=array_sum($nextactionsdue);

// get # actions
$res =query("countactions",$config,$values,$sort);
$numberitems =($res)?(int) $res[0]['nactions']:0;

// get and count active projects
$values['type']= "p";
$values['isSomeday'] = "n";

$stem  = " WHERE ".sqlparts("typefilter",$config,$values)
        ." AND ".sqlparts("activeitems",$config,$values)
        ." AND ".sqlparts("pendingitems",$config,$values);

$values['filterquery'] = $stem." AND ".sqlparts("issomeday",$config,$values);
$pres = query("getitems",$config,$values,$sort);
$numberprojects=($pres)?count($pres):0;

//get and count someday projects
$values['isSomeday'] = "y";
$values['filterquery'] = $stem." AND ".sqlparts("issomeday",$config,$values);
$sm = query("getitems",$config,$values,$sort);
$numbersomeday=($sm)?count($sm):0;


//PAGE DISPLAY CODE
echo "<h2>GTD Summary</h2>\n";
echo '<h4>Today is '.date($config['datemask']).'. (Week '.date("W").'/52 &amp; Day '.date("z").'/'.(365+date("L")).')</h4>'."\n";

echo "<div class='reportsection'>\n";
if ($reminderresult) {
        echo "<br /><h3>Reminder Notes</h3>";
        $tablehtml="";
        foreach ($reminderresult as $row) {
                $notehtml .= "<p>".date($config['datemask'],strtotime($row['date'])).": ";
                $notehtml .= '<a href = "note.php?noteId='.$row['ticklerId'].'&amp;referrer=s" title="Edit '.makeclean($row['title']).'">'.makeclean($row['title'])."</a>";
                if ($row['note']!="") $notehtml .= " - ".trimTaggedString($row['note']);
                $notehtml .= "</p>\n";
        }
    echo $notehtml;
    }
echo "</div>";

echo "<div class='reportsection'>\n";
echo "<h3>Next Actions</h3>\n";

if($numbernextactions==1) {
    $verb='is';
    $plural='';
} else {
    $verb='are';
    $plural='s';
}
$space1=" in $numbercontexts <a href='reportContext.php'>Spatial Context"
        .(($numbercontexts==1)?'':'s') . "</a>";
if ($config["contextsummary"] === 'nextaction') {
    $space2='';
} else {
    $space2=$space1;
    $space1='';
}
echo "<p>There $verb $numbernextactions"
    ," <a href='listItems.php?type=a&amp;nextonly=true'>Next Action$plural</a> pending$space1, of which "
    ,($nextactionsdue['2']==1)?'1 action is':"{$nextactionsdue['2']} actions are"
    ," due today, "
    ,($nextactionsdue['3']==1)?'1 is':"{$nextactionsdue['3']} are"
    ," now overdue, and "
    ,($nextactionsdue['1']==1)?"1 has its deadline":"{$nextactionsdue['1']} have deadlines"
    ,"  in the coming 7 days. Altogether, there are $numberitems <a href='listItems.php?type=a'>Action"
    ,($numberitems==1)?'':'s'
    ,"</a>$space2.</p>\n</div>\n";

    echo "<div class='reportsection'>\n";
	echo "<h3>Projects</h3>\n";

    if($numberprojects==1){
        echo '<p>There is 1 active <a href="listItems.php?type=p">Project</a>.</p>'."\n";
    }else{
        echo '<p>There are ' .$numberprojects. ' active <a href="listItems.php?type=p">Projects</a>.</p>'."\n";
    }

	if($numberprojects) {
        echo "<table summary='table of projects'><tbody>\n"
            ,columnedTable(3,$pres)
            ,"</tbody></table>\n";
    }
	echo "</div>\n";

    echo "<div class='reportsection'>\n";
	echo "<h3>Someday/Maybes</h3>\n";

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

	include_once('footer.php');
?>
