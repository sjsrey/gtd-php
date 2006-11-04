<?php

//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values['projectId'] = (int) $_GET['projectId'];

//GET project details
$result = query("selectproject",$config,$values,$options,$sort);

$project = $result[0];
if ($project['isSomeday']=="y") $pType="s";
else $pType="p";

//select all nextactions for test
$result = query("selectnextaction",$config,$values,$options,$sort);
$nextactions = array();
if ($result!="-1") {
    $i=0;
    foreach ($result as $row) {
        $nextactions[$i] = $row['nextaction'];
        $i++;
        }
    }
//Find previous and next projects
$values['isSomeday']="n";
$values['filterquery'] = sqlparts("activeprojects",$config,$values);
$values['filterquery'] .= sqlparts("issomeday",$config,$values);
$result = query("getprojects",$config,$values,$options,$sort);
$c=0;
foreach ($result as $row) {
    $ids[$c]=$row['projectId'];
    if($ids[$c]==$values['projectId']){
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

echo '<form action="processItemUpdate.php?projectId='.$values['projectId'].'" method="post">'."\n";

echo "<h1>".$typename."&nbsp;Report:&nbsp;".stripslashes($project['name'])."</h1>\n";
echo '[ <a href="project.php?projectId='.$values['projectId'].'" title="Edit '.stripslashes($project['name']).'">Edit</a> ]'."\n";
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
	else echo '<h2><a href = "item.php?type='.$value.'&projectId='.$values['projectId'].'&pType='.$pType.'" title="Add new '.str_replace("s","",$typelabel[$value]).'">'.$typelabel[$value]."</a></h2>\n";

    //Select items by type
    $values['type']=$value;
    $values['filterquery'] = sqlparts("typefilter",$config,$values);
    $values['filterquery'] .= sqlparts("projectfilter",$config,$values);
    if ($comp=="y") {
		$values['filterquery'] .= sqlparts("completeditems",$config,$values);
		$result = query("getcompleteditems",$config,$values,$options,$sort);
	} else {
		$values['filterquery'] .= sqlparts("activeitemsandproject",$config,$values);
		$result = query("getitems",$config,$values,$options,$sort);
	}
	
        if ($result != "-1") {
		$counter=0;
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
                echo "          <td>Next</td>\n";               
		echo "		<td>".$typelabel[$value]."</td>\n";
		echo "		<td>Description</td>\n";
		echo "		<td>Context</td>\n";
		if ($comp=="n") {
			echo "		<td>Date Created</td>\n";
			echo "		<td>Deadline</td>\n";
			echo "		<td>Repeat</td>\n";
			echo "		<td>Suppress</td>\n";
			echo "		<td>Completed</td>\n";
		} else {
			echo "		<td>Date Completed</td>\n";
		}
		echo "	</thead>\n";

		foreach ($result as $row) {
			//if nextaction, add icon in front of action (* for now)
			if ($key = in_array($row['itemId'],$nextactions)) {
                                echo "	<tr class = 'nextactionrow'>\n";
                                $naText='<td align=center><input type="radio"';
                                $naText.=' name="isNext" value="';
                                $naText.=$row['itemId'].'" checked><br></td>'."\n";
                                echo $naText;
                                
				echo '		<td class="nextactioncell"><a href="item.php?itemId='.$row['itemId'].'&pType='.$pType.'" class="nextactionlink" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'"><span class="nextActionMarker" title="Next Action">*</span>'.stripslashes($row['title'])."</a></td>\n";
			} else {
				echo "	<tr>\n";
                                
                                $naText='<td align=center><input type="radio"';                  
                                $naText.=' name="isNext" value="';
                                $naText.=$row['itemId'].'"><br></td>'."\n";
                                echo $naText;

				echo '		<td><a href = "item.php?itemId='.$row['itemId'].'&pType='.$pType.'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</a></td>\n";
			}

			echo '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
			echo '		<td><a href = "reportContext.php?contextId='.$row['contextId'].'" title="Go to '.htmlspecialchars(stripslashes($row['cname'])).' context report">'.stripslashes($row['cname'])."</a></td>\n";

			if ($comp=="n") {
				echo "		<td>".date("D M j, Y",strtotime($row['dateCreated']))."</td>\n";
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
			} else {
				echo "		<td>".date("D M j, Y",strtotime($row['dateCompleted']))."</td>\n";
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

include_once('footer.php');
?>
