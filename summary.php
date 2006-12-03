<?php
//INCLUDES
include_once('header.php');

$values=array();

//SQL Code

//Select notes
$values['filterquery'] = sqlparts("notefilter",$config,$values);
$reminderresult = query("getnotes",$config,$values,$options,$sort);

//get # space contexts
$numbercontexts = query("countspacecontexts",$config,$values,$options,$sort);

//get # nextactions
$numbernextactions = query("countnextactions",$config,$values,$options,$sort);

//count active items
$values['type'] = "a";
$values['isSomeday'] = "n";
$values['filterquery']  = sqlparts("typefilter",$config,$values);
$values['filterquery'] .= sqlparts("issomeday",$config,$values);
$values['filterquery'] .= sqlparts("activeitems",$config,$values);
$numberitems = query("countitems",$config,$values,$options,$sort);

//count active projects
$values['type']= "p";
$values['isSomeday'] = "n";
$values['filterquery']  = sqlparts("typefilter",$config,$values);
$values['filterquery'] .= sqlparts("issomeday",$config,$values);
$values['filterquery'] .= sqlparts("activeitems",$config,$values);
$numberprojects = query("countitems",$config,$values,$options,$sort);

//count someday projects
$values['type']= "p";
$values['isSomeday'] = "y";
$values['filterquery']  = sqlparts("typefilter",$config,$values);
$values['filterquery'] .= sqlparts("issomeday",$config,$values);
$values['filterquery'] .= sqlparts("activeitems",$config,$values);
$numbersomeday = query("countitems",$config,$values,$options,$sort);

//get active projects
$values['type']= "p";
$values['isSomeday'] = "n";
$values['filterquery']  = sqlparts("typefilter-w",$config,$values);
$values['filterquery'] .= sqlparts("issomeday",$config,$values);
$values['filterquery'] .= sqlparts("activeitems",$config,$values);
$pres = query("getitems",$config,$values,$options,$sort);

//get someday projects
$values['type']= "p";
$values['isSomeday'] = "y";
$values['filterquery']  = sqlparts("typefilter-w",$config,$values);
$values['filterquery'] .= sqlparts("isSomeday",$config,$values);
$values['filterquery'] .= sqlparts("activeitems",$config,$values);
$sm = query("getitems",$config,$values,$options,$sort);


//set empty database counts to zero
    if($numbercontexts[0]['ncontexts']=="") $numbercontexts[0]['ncontexts']="0";
    if($numberprojects[0]['nitems']=="" || $pres=="-1") $numberprojects[0]['nitems']="0";
    if($numbersomeday[0]['nitems']=="" || $sm=="-1") $numbersomeday[0]['nitems']="0";
    if($numberitems[0]['nitems']=="") $numberitems[0]['nitems']="0";
    if($numbernextactions[0]['nnextactions']=="") $numbernextactions[0]['nnextactions']="0";

//PAGE DISPLAY CODE
echo "<h2>GTD Summary</h2>\n";
echo '<h4>Today is '.date("l, F jS, Y").'. (Week '.date("W").'/52 & Day '.date("z").'/'.(365+date("L")).')</h4>'."\n";

echo "<div class='reportsection'>\n";
if ($reminderresult!="-1") {
        echo "<br /><h3>Reminder Notes</h3>";
        $tablehtml="";
        foreach ($reminderresult as $row) {
                $notehtml .= "<p>".date("l, M jS Y",strtotime($row['date'])).": ";
                $notehtml .= '<a href = "note.php?noteId='.$row['ticklerId'].'&referrer=s" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</a>";
                if ($row['note']!="") $notehtml .= " - ".nl2br(stripslashes($row['note']));
                $notehtml .= "</p>\n";
        }
    echo $notehtml;
    }

echo "</div>";
echo "<div class='reportsection'>\n";
echo '<p>Reminder notes can be added <a href="note.php?referrer=s" Title="Add new reminder">here</a>.</p>'."\n";

echo "<div class='reportsection'>\n";
    echo "<h3>Next Actions</h3>\n";
if($numbernextactions[0]['nnextactions']==1) {
            echo '<p>There is ' .$numbernextactions[0]['nnextactions']. ' <a href="listItems.php?type=n">Next Action</a> pending';
        } else {
            echo '<p>There are ' .$numbernextactions[0]['nnextactions']. ' <a href="listItems.php?type=n">Next Actions</a> pending';
        }
echo ' out of a total of ' .$numberitems[0]['nitems']. ' <a href="listItems.php?type=a">Actions</a>.';
    echo "</p>\n";
    echo "</div>\n";

echo "<div class='reportsection'>\n";
    echo "<h3>Contexts</h3>\n";
if($numbercontexts[0]['ncontexts']==1) {
    echo '<p>There is ' .$numbercontexts[0]['ncontexts']. ' <a href="reportContext.php?type=n">Spatial Context</a>.<p>'."\n";
} else {
    echo '<p>There are ' .$numbercontexts[0]['ncontexts']. ' <a href="reportContext.php?type=n">Spatial Contexts</a>.<p>'."\n";
    }
    echo "</div>\n";

    $i=0;
    $w1=$numberprojects[0]['nitems']/3;
    if ($pres!=-1) {
    foreach($pres as $row) {
            if($i < $w1){
                    $c1[]=stripslashes($row['title']);
                    $i1[]=$row['itemId'];
                    $q1[]=stripslashes($row['description']);
            }
            elseif($i< 2*$w1){
                    $c2[]=stripslashes($row['title']);
                    $i2[]=$row['itemId'];
                    $q2[]=stripslashes($row['description']);
            }
            else{
                    $c3[]=stripslashes($row['title']);
                    $i3[]=$row['itemId'];
                    $q3[]=stripslashes($row['description']);
            }
            $i+=1;
            }
    }

//Somedays
   if($numbersomeday!='-1'){
	$i=0;
        $w2=$numbersomeday[0]['nitems']/3;
        if ($sm!=-1) {
	foreach($sm as $row) {
                if($i < $w2){
                        $d1[]=stripslashes($row['title']);
                        $j1[]=$row['itemId'];
                        $k1[]=stripslashes($row['description']);
                }
                elseif($i< 2*$w2){
                        $d2[]=stripslashes($row['title']);
                        $j2[]=$row['itemId'];
                        $k2[]=stripslashes($row['description']);
                }
                else{
                        $d3[]=stripslashes($row['title']);
                        $j3[]=$row['itemId'];
                        $k3[]=stripslashes($row['description']);
                }
                $i+=1;
            }
        }
   }

    echo "<div class='reportsection'>\n";
	echo "<h3>Project</h3>\n";

    if($numberprojects[0]['nitems']==1){
        echo '<p>There is ' .$numberprojects[0]['nitems']. ' <a href="listItems.php?type=p">Project</a>.<p>'."\n";
    }else{
        echo '<p>There are ' .$numberprojects[0]['nitems']. ' <a href="listItems.php?type=p">Projects</a>.<p>'."\n";
    }

	$s='<table>'."\n";
	$nr = count($c1);

	for($i=0;$i<$nr;$i+=1){
		$s.="	<tr>\n";
		$s.='		<td><a href="itemReport.php?itemId='.$i1[$i].'" title="'.$q1[$i].'">'.$c1[$i]."</a></td>\n";
		if ($i2[$i]!="" || $nr>1) $s.='		<td><a href="itemReport.php?itemId='.$i2[$i].'" title="'.$q2[$i].'">'.$c2[$i]."</a></td>\n";
		if ($i3[$i]!="" || $nr>1) $s.='		<td><a href="itemReport.php?itemId='.$i3[$i].'" title="'.$q3[$i].'">'.$c3[$i]."</a></td>\n";
		$s.="	</tr>\n";
	}

	$s.="</table>\n";

	echo $s;
	echo "</div>\n";

    echo "<div class='reportsection'>\n";
	echo "<h3>Someday/Maybes</h3>\n";

    if($numbersomeday!='-1')
    if($numbersomeday[0]['nitems']==1){
        echo '<p>There is ' .$numbersomeday[0]['nitems']. ' <a href="listItems.php?type=s">Someday/Maybe</a>.</p>'."\n";
    }else{
        echo '<p>There are ' .$numbersomeday[0]['nitems']. ' <a href="listItems.php?type=s">Someday/Maybes</a>.</p>'."\n";
    }


	$t='<table>'."\n";
	$nr = count($d1);

	for($i=0;$i<$nr;$i+=1){
		$t.="	<tr>\n";
		$t.='		<td><a href="itemReport.php?itemId='.$j1[$i].'" title="'.$k1[$i].'">'.$d1[$i]."</a></td>\n";
		if ($j2[$i]!="" || $nr>1) $t.='		<td><a href="itemReport.php?itemId='.$j2[$i].'" title="'.$k2[$i].'">'.$d2[$i]."</a></td>\n";
		if ($j3[$i]!="" || $nr>1) $t.='		<td><a href="itemReport.php?itemId='.$j3[$i].'" title="'.$k3[$i].'">'.$d3[$i]."</a></td>\n";
		$t.="	</tr>\n";
	}

	$t.="</table>\n";

	echo $t;
	echo "</div>\n";

	include_once('footer.php');
?>
