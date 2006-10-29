<?php

//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$pId = (int) $_GET['projectId'];
//SQL CODE AREA
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//GET project details
$query = "SELECT projects.name, projects.description, projects.desiredOutcome, projectstatus.dateCreated,
	projectstatus.dateCompleted, projectstatus.lastModified, projectattributes.deadline, projectattributes.repeat,
	projectattributes.suppress, projectattributes.suppressUntil, projectattributes.isSomeday
	FROM projects,projectattributes, projectstatus
	WHERE projectstatus.projectId = projects.projectId AND projectattributes.projectId = projects.projectId AND
	projects.projectId = '$pId'";
$result = mysql_query($query) or die ("Error in query");
$project = mysql_fetch_assoc($result);
if ($project['isSomeday']=="y") $pType="s";
else $pType="p";
mysql_free_result($result);

//Function to select items of a specific type
function doitemquery($projectId,$type,$completed='n') {
	if ($completed=="y") $compq = "itemstatus.dateCompleted > 0";
	else $compq = "itemstatus.dateCompleted IS NULL OR itemstatus.dateCompleted = '0000-00-00'";

	$query = "SELECT items.itemId, items.title, items.description, itemstatus.dateCreated, itemstatus.dateCompleted,
		context.contextId, context.name AS cname, itemattributes.deadline, itemattributes.repeat,
		itemattributes.suppress, itemattributes.suppressUntil
		FROM items, itemattributes, itemstatus, context
		WHERE itemstatus.itemId = items.itemId AND itemattributes.itemId = items.itemId AND
		itemattributes.contextId = context.contextId AND itemattributes.projectId = '$projectId'
		AND itemattributes.type = '$type' AND (".$compq.") ORDER BY items.title ASC, cname ASC";
	$result = mysql_query($query) or die ("Error in query");
	return $result;
}

//select all nextactions for test
$query = "SELECT projectId, nextaction FROM nextactions";
$result = mysql_query($query) or die ("Error in query");
$nextactions = array();
while ($nextactiontest = mysql_fetch_assoc($result)) {
	//populates $nextactions with itemIds using projectId as key
	$nextactions[$nextactiontest['projectId']] = $nextactiontest['nextaction'];
}
//Find previous and next projects
$compq = "(projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00')
			AND (((CURDATE()>=DATE_ADD(projectattributes.deadline, INTERVAL -(projectattributes.suppressUntil) DAY))
			OR projectattributes.suppress='n'))";
$isSomeday="n";
$query="SELECT projects.projectId, projects.name, projects.description, projectattributes.categoryId, categories.category,
		projectattributes.deadline, projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil
		FROM projects, projectattributes, projectstatus, categories
		WHERE projectattributes.projectId=projects.projectId AND projectattributes.categoryId=categories.categoryId
		AND projectstatus.projectId=projects.projectId AND projectattributes.isSomeday = '$isSomeday' AND ".$compq."
		ORDER BY categories.category, projects.name ASC";

$result = mysql_query($query) or die ("Error in query");
$c=0;
while($row = mysql_fetch_assoc($result)){
    $ids[$c]=$row['projectId'];
    if($ids[$c]==$pId){
        $id=$c;
    }
    $c++;
}

$n=sizeof($ids);
if(isset($id)){
    if($id==$n-1){
        $nextId=$ids[0];
    }else{
        $nextId=$ids[$id+1];
    }
    if($id==0){
        $previousId=$ids[$n-1];
    }else{
        $previousId=$ids[$id-1];
    }
}


//PAGE DISPLAY AREA
if ($pType=="s") $typename="Someday/Maybe";
else $typename="Project";

echo '<form action="processItemUpdate.php?projectId='.$pId.'" method="post">'."\n";

echo "<h1>".$typename."&nbsp;Report:&nbsp;".stripslashes($project['name'])."</h1>\n";
echo '[ <a href="project.php?projectId='.$pId.'" title="Edit '.stripslashes($project['name']).'">Edit</a> ]'."\n";
if(isset($previousId)){
    echo '[ <a href="projectReport.php?projectId='.$previousId.'" title="Previous Project">Previous</a> ]'."\n";
}
if(isset($nextId)){
    echo '[ <a href="projectReport.php?projectId='.$nextId.'" title="Next Project">Next</a> ]'."\n";
}
echo '<p>Created: '.$project['dateCreated']."<br />\n";
echo 'Description: '.stripslashes($project['description'])."<br />\n";
if ($project['desiredOutcome']!="") echo 'Desired Outcome:&nbsp;'.stripslashes($project['desiredOutcome'])."<br />\n";
if ($project['deadline']!=NULL && $project['deadline']!="0000-00-00") echo 'Deadline:&nbsp;'.$project['deadline']."<br />\n";
if ($project['repeat']>0) echo 'Repeat every&nbsp;'.$project['repeat'].'&nbsp;days'."<br />\n";
if ($project['suppress']=='y') echo 'Suppressed Until:&nbsp;'.$project['suppressUntil']."<br />\n";
if ($project['dateCompleted']>0) echo 'Completed On:&nbsp;'.$project['dateCompleted']."\n";
echo "</p>\n";
//Create iteration arrays
$type = array("a","w","r");
$typelabel = array("a" => "Actions","w" => "Waiting On", "r" => "References");
$completed = array("n","y");

//table display loop
foreach ($completed as $comp) {
foreach ($type as $value) {
	echo "<div class='reportsection'>\n";
	if ($comp=="y") echo '<h2>Completed&nbsp;'.$typelabel[$value]."</h2>\n";
	else echo '<h2><a href = "item.php?type='.$value.'&projectId='.$pId.'&pType='.$pType.'" title="Add new '.str_replace("s","",$typelabel[$value]).'">'.$typelabel[$value]."</a></h2>\n";

	$result=doitemquery($pId,$value,$comp);
	if (mysql_num_rows($result) > 0) {
		$counter=0;
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
                echo "          <td>Next</td>\n";               
		echo "		<td>".$typelabel[$value]."</td>\n";
		echo "		<td>Description</td>\n";
		echo "		<td>Context</td>\n";
		echo "		<td>Date Created</td>\n";
		if ($comp=="n") {
			echo "		<td>Deadline</td>\n";
			echo "		<td>Repeat</td>\n";
			echo "		<td>Suppress</td>\n";
			echo "		<td>Completed</td>\n";
		}
		echo "	</thead>\n";

		while($row = mysql_fetch_assoc($result)) {

			//if nextaction, add icon in front of action (* for now)
			if ($key = array_search($row['itemId'],$nextactions)) {
				echo "	<tr class = 'nextactionrow'>\n";
                                $naText='<td align=center><input type="radio"';
                                $naText.=' name="isNext" value="';
                                $naText.=$row['itemId'].'" checked><br></td>';
                                echo $naText;
                                
				echo '		<td class="nextactioncell"><a href="item.php?itemId='.$row['itemId'].'&pType='.$pType.'" class="nextactionlink" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'"><span class="nextActionMarker" title="Next Action">*</span>'.stripslashes($row['title'])."</a></td>\n";
			} else {
				echo "	<tr>\n";
                                
                                $naText='<td align=center><input type="radio"';                  
                                $naText.=' name="isNext" value="';
                                $naText.=$row['itemId'].'"><br></td>';
                                echo $naText;

				echo '		<td><a href = "item.php?itemId='.$row['itemId'].'&pType='.$pType.'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</a></td>\n";
			}

			echo '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
			echo '		<td><a href = "reportContext.php?contextId='.$row['contextId'].'" title="Go to '.htmlspecialchars(stripslashes($row['cname'])).' context report">'.stripslashes($row['cname'])."</a></td>\n";
			echo "		<td>".date("D M j, Y",strtotime($row['dateCreated']))."</td>\n";

			if ($comp=="n") {
				echo "		<td>";
				//Blank out empty deadlines
				if(($row['deadline']) == "0000-00-00") echo "&nbsp;";
				//highlight overdue actions
				elseif(($row['deadline']) < date("Y-m-d")) echo '<font color="red"><strong title="Overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
				//highlight actions due
				elseif(($row['deadline']) == date("Y-m-d")) echo '<font color="green"><strong title="Due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
				else echo date("D M j, Y",strtotime($row['deadline']));
				echo "</td>\n";

				if ($row['repeat']=="0") echo "		<td>--</td>\n";
				else echo "		<td>".$row['repeat']."</td>\n";

				if ($row['suppress']=="y") $suppressText=$row['suppressUntil'];
				else $suppressText="--";
				echo "		<td>".$suppressText."</td>\n";

				echo '		<td align=center><input type="checkbox" align="center" name="completedNas[]" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" value="';
				echo $row['itemId'];
				echo '"></td>'."\n";
				}

			echo "	</tr>\n";
			$counter = $counter+1;
		}
		echo "</table>\n";
		echo '<input type="hidden" name="referrer" value="p">'."\n";
		if ($comp=="n") echo '<input type="submit" align="right" class="button" value="Complete '.$typelabel[$value].'" name="submit">'."\n";

			if($counter==0){
				echo 'No&nbsp;'.$typelabel[$value]."&nbsp;items\n";
				}
			}
		else echo "<p>None</p>\n";

		echo "</div>\n";
	}
}

echo "</form>\n";

mysql_free_result($result);
mysql_close($connection);

include_once('footer.php');
?>
