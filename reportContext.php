<?php
//INCLUDES
include_once('header.php');

$values=array();

//SQL CODE AREA
//obtain all contexts
$contextResults = query("getspacecontexts",$config,$values,$option,$sort);
$contextNames=array();
foreach ($contextResults as $row) {
	$contextNames[$row[contextId]]=htmlspecialchars(stripslashes($row[name]));
	}

//obtain all timeframes
$timeframeResults = query("gettimecontexts",$config,$values,$options,$sort);
$timeframeNames=array();
foreach($timeframeResults as $row) {
	$timeframeNames[$row[timeframeId]]=htmlspecialchars(stripslashes($row[timeframe]));
	$timeframeDesc[$row[timeframeId]]=htmlspecialchars(stripslashes($row[timeframe]));
	}

//select all nextactions for test
$nextactions=(getNextActionsArray($config,$values,$options,$sort));

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
echo '<table class="datatable sortable" summary="table of contexts" id="contexttable">'."\n";
echo "	<thead><tr>\n";
echo "		<td>Context</td>\n";
foreach ($timeframeNames as $tcId => $tname) {
	echo '		<td><a href="editTimeContext.php?tcId='.$tcId.'" title="Edit the '.htmlspecialchars(stripslashes($tname)).' time context">'.stripslashes($tname)."</a></td>\n";
	}
echo "		<td>Total</td>\n";
echo "	</tr></thead>\n";
$contextTotal=0;
$timeframeTotal=0;
foreach ($contextNames as $contextId => $cname) {
	$contextCount=0;
	echo "	<tr>\n";
	echo '		<td><a href="editContext.php?contextId='.$contextId.'" title="Edit the '.htmlspecialchars(stripslashes($cname)).' context">'.$cname."</a></td>\n";
	foreach ($timeframeNames as $timeframeId => $tname) {
		if ($contextArray[$contextId][$timeframeId]!="") {
			$count=$contextArray[$contextId][$timeframeId];
			$contextCount=$contextCount+$count;
			echo '		<td><a href="#'.$cname.'_'.$timeframeId.'">'.$count."</a></td>\n";
			}
		else echo "		<td>0</td>\n";
		}
	echo '		<td><a href="#'.htmlspecialchars(stripslashes($cname)).'">'.$contextCount."</a></td>\n";
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
    echo '<h2><a href="editContext.php?contextId='.$values['contextId'].'" title="Edit the '.$contextNames[$values['contextId']].' context">Context:&nbsp;'.$contextNames[$values['contextId']]."</a></h2>\n";

    foreach ($timeframe as $values['timeframeId'] => $itemCount) {
        echo '<a name="'.$contextNames[$values['contextId']].'_'.$values['timeframeId'].'"></a>'."\n";
        echo '<h3>Time Context:&nbsp;'.$timeframeNames[$values['timeframeId']]."</h3>\n";

        $values['type'] = "a";
        $values['isSomeday'] = "n";
        $values['childfilterquery']  = " WHERE ".sqlparts("typefilter",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("activeitems",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("timeframefilter",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("contextfilter",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("issomeday",$config,$values);
		$values['childfilterquery'] .= " AND ".sqlparts("isnotcompleteditem",$config,$values);
        $result = query("getitemsandparent",$config,$values,$options,$sort);

        $tablehtml="";
		if (is_array($result)) {
			foreach ($result as $row) {
				$tablehtml .= "	<tr>\n";
				$tablehtml .= '		<td><a href = "item.php?itemId='.$row['parentId'].'" title="Go to '.htmlspecialchars(stripslashes($row['ptitle'])).' project report">'.stripslashes($row['ptitle'])."</a></td>\n";
	
				//if nextaction, add icon in front of action (* for now)
				$tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars($row['title']).'">';
				if ($key == array_search($row['itemId'],$nextactions)) $tablehtml .= '*&nbsp;';
				$tablehtml .= htmlspecialchars(stripslashes($row['title']))."</a></td>\n";
	
				$tablehtml .= '		<td>'.nl2br(trimTaggedString($row['description'],$config['trimLength']))."</td>\n";
				$tablehtml .= prettyDueDate('td',$row['deadline'],$config['datemask'])."\n";
				$tablehtml .= "<td>".((($row['repeat'])=="0")?'&nbsp;':($row['repeat']))."</td>\n";
				$tablehtml .= '		<td align="center"><input type="checkbox" name="isMarked[]" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" value="'; // where is the </td> tag?
				$tablehtml .= $row['itemId'].'" /></td>'."\n	</tr>\n";
			}
		}
    if ($tablehtml!="") {
		$thisurl=parse_url($_SERVER[PHP_SELF]);
		$thisAnchor='c'.$values['contextId'].'t'.$values['timeframeId'];
		echo "<a id=\"{$thisAnchor}\"></a>";
        echo '<form action="processItems.php" method="post">';
        echo '<table class="datatable sortable" summary="table of actions" id="actiontable'.$values['contextId'].'t'.$values['timeframeId'].'">'."\n";
        echo "	<thead><tr>\n";
        echo "		<td>Project</td>\n";
        echo "		<td>Action</td>\n";
        echo "		<td>Description</td>\n";
        echo "		<td>Deadline</td>\n";
        echo "		<td>Repeat</td>\n";
        echo "		<td>Completed</td>\n";
        echo "	</tr></thead>\n";
        echo $tablehtml;
        echo "</table>\n";
		echo '<input type="hidden" name="referrer" value="',basename($thisurl['path']),"#{$thisAnchor}\" />";
		echo '<input type="hidden" name="multi" value="y" />'."\n";
		echo '<input type="hidden" name="action" value="complete" />'."\n";
        echo '<input type="submit" class="button" value="Update Actions" name="submit" /></form>'."\n";
        }

        else echo "<h4>Nothing was found</h4>\n";
        }
    }

include_once('footer.php');
?>
