<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values=array();
$values['itemId'] = (int) $_GET['itemId'];

//Get item details
$result = query("selectitem",$config,$values,$options,$sort);
$item = $result[0];

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
//Find previous and next projects
$values['isSomeday']="n";
$values['type']=$item['type'];
$values['filterquery']  = sqlparts("typefilter-w",$config,$values);
$values['filterquery'] .= sqlparts("activeitems",$config,$values);
$values['filterquery'] .= sqlparts("issomeday",$config,$values);
$result = query("getitems",$config,$values,$options,$sort);

$c=0;
if ($result!="-1") {
    foreach ($result as $row) {
        $ids[$c]=$row['itemId'];
        $titles[$c]=$row['title'];
        if($ids[$c]==$values['itemId']){
            $id=$c;
            }
        $c++;
        }
    }

$n=sizeof($ids);
if(isset($id)){
    if($id==$n-1){
        $nextId=$ids[0];
        $nexttitle=$titles[0];
    }else{
        $nextId=$ids[$id+1];
        $nexttitle=$titles[$id+1];
    }
    if($id==0){
        $previousId=$ids[$n-1];
        $previoustitle=$titles[$n-1];
    }else{
        $previousId=$ids[$id-1];
        $previoustitle=$titles[$id-1];
    }
}

//PAGE DISPLAY AREA

//set item labels
$typename=array();
$typename=array("m" => "Values",
                "v" => "Visions",
                "g" => "Goals",
                "o" => "Roles",
                "p" => "Projects",
                "s" => "Someday/Maybes",
                "a" => "Actions",
                "w" => "Waiting Ons",
                "r" => "References",
                "i" => "Inbox Items");

$childtype=array();  //I don't like this... but it's the best solution at the moment...

switch ($item['type']) {
    case "m" : $childtype=array("v"); break;
    case "v" : $childtype=array("g"); break;
    case "g" : $childtype=array("o"); break;
    case "o" : $childtype=array("p"); break;
    case "p" : $childtype=array("a","w","r"); break;
    case "s" : $childtype=array("a","w","r"); break;
    case "a" : $childtype=NULL; break;
    case "w" : $childtype=NULL; break;
    case "r" : $childtype=NULL; break;
    case "i" : $childtype=NULL; break;
    default  : $childtype=NULL;
    }

echo "<h1>".str_replace("s","",$typename[$item['type']])."&nbsp;Report:&nbsp;".htmlspecialchars(stripslashes($item['title']))."</h1>\n";

//Edit, next, and previous buttons
echo '[ <a href="item.php?itemId='.$values['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($item['title'])).'">Edit</a> ]'."\n";
if(isset($previousId)) echo '[ <a href="itemReport.php?itemId='.$previousId.'" title="'.$previoustitle.'">Previous</a> ]'."\n";
if(isset($nextId))  echo '[ <a href="itemReport.php?itemId='.$nextId.'" title="'.$nexttitle.'">Next</a> ]'."\n";

//Item details
echo '<p>Created: '.$item['dateCreated']."<br />\n";
if ($item['description']!="") echo 'Description: '.htmlspecialchars(stripslashes($item['description']))."<br />\n";
if ($item['desiredOutcome']!="") echo 'Desired Outcome:&nbsp;'.htmlspecialchars(stripslashes($item['desiredOutcome']))."<br />\n";
if ($item['categoryId']>0) echo 'Category:&nbsp;'.htmlspecialchars(stripslashes($item['category']))."<br />\n";
if ($item['contextId']>0) echo 'Space Context:&nbsp;'.htmlspecialchars(stripslashes($item['cname']))."<br />\n";
if ($item['timeframeId']>0) echo 'Time Context:&nbsp;'.htmlspecialchars(stripslashes($item['timeframe']))."<br />\n";
if ($item['deadline']!=NULL && $item['deadline']!="0000-00-00") echo 'Deadline:&nbsp;'.$item['deadline']."<br />\n";
if ($item['repeat']>0) echo 'Repeat every&nbsp;'.$item['repeat'].'&nbsp;days'."<br />\n";
if ($item['suppress']=='y') echo 'Suppressed Until:&nbsp;'.$item['suppressUntil']."<br />\n";
if ($item['dateCompleted']>0) echo 'Completed On:&nbsp;'.$item['dateCompleted']."\n";
echo "</p>\n";


if ($childtype!=NULL) {
echo '<form action="processItemUpdate.php?itemId='.$values['itemId'].'" method="post">'."\n";

//Create iteration arrays
$completed = array("n","y");

//table display loop
foreach ($completed as $comp) {
    foreach ($childtype as $value) {
	echo "<div class='reportsection'>\n";
	if ($comp=="y") echo '<h2>Completed&nbsp;'.$typename[$value]."</h2>\n";
	else echo '<h2><a href = "item.php?parentId='.$values['itemId'].'&type='.$value.'" title="Add new '.str_replace("s","",$typename[$value]).'">'.$typename[$value]."</a></h2>\n";

    //Select items by type
    $values['type']=$value;
    $values['parentId']=$values['itemId'];
    $values['filterquery'] = sqlparts("typefilter",$config,$values);
    if ($comp=="y") {
		$values['filterquery'] .= sqlparts("completeditems",$config,$values);
		$result = query("getchildren",$config,$values,$options,$sort);
	} else {
		$values['filterquery'] .= sqlparts("incompleteitems",$config,$values);  //suppressed items will be shown on report page
		$result = query("getchildren",$config,$values,$options,$sort);
	}

        if ($result != "-1") {
		$counter=0;
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
            if ($comp!="y") echo "          <td>Next</td>\n";
		echo "		<td>".$typename[$value]."</td>\n";
		echo "		<td>Description</td>\n";
		echo "		<td>Context</td>\n";
                echo "          <td>Date Created</td>\n";
		if ($comp=="n") {
                        echo "          <td>SuppressUntil</td>\n";
			echo "		<td>Deadline</td>\n";
			echo "		<td>Repeat</td>\n";
			echo "		<td>Completed</td>\n";
		} else {
			echo "		<td>Date Completed</td>\n";
		}
		echo "	</thead>\n";

		foreach ($result as $row) {
			if ($key = in_array($row['itemId'],$nextactions) && $comp!="y") {
                                echo "	<tr class = 'nextactionrow'>\n";
                                $naText='<td align=center><input type="radio"';
                                $naText.=' name="isNext" value="';
                                $naText.=$row['itemId'].'" checked><br></td>'."\n";
                                echo $naText;

                                //if nextaction, add icon in front of action (* for now)
				echo '		<td class="nextactioncell"><a href="item.php?itemId='.$row['itemId'].'" class="nextactionlink" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'"><span class="nextActionMarker" title="Next Action">*</span>'.htmlspecialchars(stripslashes($row['title']))."</a></td>\n";
			} else {
				echo "	<tr>\n";

                                $naText='<td align=center><input type="radio"';
                                $naText.=' name="isNext" value="';
                                $naText.=$row['itemId'].'"><br></td>'."\n";
                                if ($comp!="y") echo $naText;

				echo '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.htmlspecialchars(stripslashes($row['title']))."</a></td>\n";
			}

			echo '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
			echo '		<td><a href = "reportContext.php?contextId='.$row['contextId'].'" title="Go to '.htmlspecialchars(stripslashes($row['cname'])).' context report">'.htmlspecialchars(stripslashes($row['cname']))."</a></td>\n";

                                echo "          <td>".date("D M j, Y",strtotime($row['dateCreated']))."</td>\n";

                        if ($comp=="n") {                                
                                    //Calculate reminder date as # suppress days prior to deadline
                                    if ($row['suppress']=="y") {
                                    $dm=(int)substr($row['deadline'],5,2);
                                    $dd=(int)substr($row['deadline'],8,2);
                                    $dy=(int)substr($row['deadline'],0,4);
                                    $remind=mktime(0,0,0,$dm,($dd-(int)$row['suppressUntil']),$dy);
                                    $reminddate=gmdate("Y-m-d", $remind);
                                    echo "         <td>".date("D M j, Y",strtotime($reminddate))."</td>\n";
                                    }
                                    else echo "<td></td>";
                                    
				echo "		<td>";
				//Blank out empty deadlines
				if(($row['deadline']) == "0000-00-00") echo "&nbsp;";
				//highlight overdue actions
				elseif(($row['deadline']) < date("Y-m-d")) echo '<font color="red"><strong title="Overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
				//highlight actions due
				elseif(($row['deadline']) == date("Y-m-d")) echo '<font color="green"><strong title="Due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
				else echo date("D M j, Y",strtotime($row['deadline']));
				echo "</td>\n";

				if ($row['repeat']=="0") echo "		<td></td>\n";
				else echo "		<td>".$row['repeat']."</td>\n";


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
}
include_once('footer.php');
?>
