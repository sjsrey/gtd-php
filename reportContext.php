<?php
//INCLUDES
include_once('header.php');

//SQL CODE AREA

//obtain all contexts
$contextResults = query("getspacecontexts",$config,$values,$option,$sort);
$contextNames=array();
foreach ($contextResults as $row) {
	$contextNames[$row[contextId]]=stripslashes($row[name]);
	}

//obtain all timeframes
$timeframeResults = query("gettimecontexts",$config,$values,$options,$sort);
$timeframeNames=array();
foreach($timeframeResults as $row) {
	$timeframeNames[$row[timeframeId]]=stripslashes($row[timeframe]);
	$timeframeDesc[$row[timeframeId]]=htmlspecialchars(stripslashes($row[timeframe]));
	}

//select all nextactions for test
$result = query("getnextactions",$config,$values,$options,$sort);
$nextactions = array();
if ($result!="-1") {
    $i=0;
    foreach ($result as $row) {
        $nextactions[$i] = $row['nextaction'];
        $i++;
        }
    }

//obtain all active item timeframes and count instances of each
if ($config['contextsummary'] == "all") $itemresults = query("countcontextreport_all",$config,$values,$options,$sort);
else $itemresults = query("countcontextreport_naonly",$config,$values,$options,$sort);

foreach ($itemresults as $contextRow) {
	$contextArray[$contextRow['contextId']][$contextRow['timeframeId']] = $contextRow['count'];
	}

//PAGE DISPLAY CODE
echo "<h2>Contexts Summary</h2>\n";
echo "<h3>Spatial Context (row), Temporal Context (column)</h3>\n";

//context table
echo "<table class='datatable'>\n";
echo "	<thead>\n";
echo "		<td>Context</td>\n";
foreach ($timeframeNames as $tcId => $tname) {
	echo '		<td><a href="editTimeContext.php?tcId='.$tcId.'" title="Edit the '.htmlspecialchars(stripslashes($tname)).' time context">'.stripslashes($tname)."</a></td>\n";
	}
echo "		<td>Total</td>\n";
echo "	</thead>\n";
$contextTotal=0;
$timeframeTotal=0;
foreach ($contextNames as $contextId => $cname) {
	$contextCount=0;
	echo "	<tr>\n";
	echo '		<td><a href="editContext.php?contextId='.$contextId.'" title="Edit the '.htmlspecialchars($cname).' context">'.$cname."</a></td>\n";
	foreach ($timeframeNames as $timeframeId => $tname) {
		if ($contextArray[$contextId][$timeframeId]!="") {
			$count=$contextArray[$contextId][$timeframeId];
			$contextCount=$contextCount+$count;
			echo '		<td><a href="#'.$cname.'_'.$timeframeId.'">'.$count."</a></td>\n";
			}
		else echo "		<td>0</td>\n";
		}
	echo '		<td><a href="#'.htmlspecialchars($cname).'">'.$contextCount."</a></td>\n";
	$contextTotal=$contextTotal+$contextCount;
	echo "	</tr>\n";
	}
echo "	<tr>\n";
echo "		<td>Total</td>\n";
foreach ($timeframeNames as $timeframeId => $tname) {
	$timeframeCount=0;
	foreach ($contextNames as $contextId => $cname) {
		if ($contextArray[$contextId][$timeframeId]!="") {
			$count=$contextArray[$contextId][$timeframeId];
			$timeframeCount=$timeframeCount+$count;
			}
		}
	echo "		<td>".$timeframeCount."</td>\n";
	}
echo "		<td>".$contextTotal."</td>\n";
echo "	</tr>\n";
echo "</table>\n";
echo "\n";
echo "<p>To move to a particular space-time context, select the number.<br />To edit a context select the context name.</p>\n";

//Item listings by context and timeframe
foreach ($contextArray as $values['contextId'] => $timeframe) {

    echo '<a name="'.$contextNames[$values['contextId']].'"></a>'."\n";
    echo '<h2>Context:&nbsp;'.$contextNames[$values['contextId']]."</h2>\n";

    foreach ($timeframe as $values['timeframeId'] => $itemCount) {
        echo '<a name="'.$contextNames[$values['contextId']].'_'.$values['timeframeId'].'"></a>'."\n";
        echo '<h3>Time Context:&nbsp;'.$timeframeNames[$values['timeframeId']]."</h3>\n";

        $values['type'] = "a";
        $values['isSomeday'] = "n";
        $values['filterquery'] = sqlparts("activeitemsandproject",$config,$values);
        $values['filterquery'] .= sqlparts("timeframefilter",$config,$values);
        $values['filterquery'] .= sqlparts("contextfilter",$config,$values);
        $values['filterquery'] .= sqlparts("typefilter",$config,$values);
        $values['filterquery'] .= sqlparts("isSomeday",$config,$values);
        $result = query("getitems",$config,$values,$options,$sort);

        $tablehtml="";
        foreach ($result as $row) {
            $tablehtml .= "	<tr>\n";
            $tablehtml .= '		<td><a href = "projectReport.php?projectId='.$row['projectId'].'" title="Go to '.htmlspecialchars(stripslashes($row['pname'])).' project report">'.stripslashes($row['pname'])."</a></td>\n";
            //if nextaction, add icon in front of action (* for now)
            if ($key = array_search($row['itemId'],$nextactions)) $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars($row['title']).'">*&nbsp;'.stripslashes($row['title'])."</td>\n";
            else $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</td>\n";
            $tablehtml .= '		<td>'.nl2br(substr(stripslashes($row['description']),0,72))."</td>\n";
            $tablehtml .= "		<td>";
            if(($row['deadline']) == "0000-00-00") $tablehtml .= "&nbsp;";
            elseif(($row['deadline']) < date("Y-m-d")) $tablehtml .= '<font color="red"><strong title ="Overdue">'.$row['deadline'].'</strong></font>';  //highlight overdue actions
            elseif(($row['deadline']) == date("Y-m-d")) $tablehtml .= '<font color="green"><strong title="Due today">'.$row['deadline'].'</strong></font>'; //highlight actions due today
            else $tablehtml .= $row['deadline'];
            $tablehtml .= "</td>\n";
            if ($row['repeat']=="0") $tablehtml .= "		<td>--</td>\n";
            else $tablehtml .= "		<td>".$row['repeat']."</td>\n";
                                    $tablehtml .= '		<td align="center"><input type="checkbox" align="center" name="completedNas[]" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" value="'; // where is the </td> tag?
                                    $tablehtml .= $row['itemId'];
                                    $tablehtml .= '"></td>'."\n";
            $tablehtml .= "	</tr>\n";
                    }

    if ($tablehtml!="") {
        echo '<form action="processItemUpdate.php?type='.$type.'&contextId='.$contextId.'&referrer=c" method="post">';
        echo "<table class='datatable'>\n";
        echo "	<thead>\n";
        echo "		<td>Project</td>\n";
        echo "		<td>Action</td>\n";
        echo "		<td>Description</td>\n";
        echo "		<td>Deadline</td>\n";
        echo "		<td>Repeat</td>\n";
        echo "		<td>Completed</td>\n";
        echo "	</thead>\n";
        echo $tablehtml;
        echo "</table>\n";
        echo '<input type="submit" class="button" value="Update Actions" name="submit"></form>'."\n";
        }

        else echo "<h4>Nothing was found</h4>\n";
        }
    }

include_once('footer.php');
?>
