<?php

//INCLUDES
include_once('header.php');

//SQL CODE
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

//Select notes
$reminderresult = query("getnotes",$config,$values,$options,$sort);

//Select suppressed items
$values['filterquery'] = sqlparts("suppresseditems",$config,$values);
$itemresult = query("getitems",$config,$values,$options,$sort);

//Select suppressed projects
$values['filterquery'] = sqlparts("suppressedprojects",$config,$values);
$projectresult = query("getprojects",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
echo "<h2>Tickler File</h2>\n";
echo '<h4>Today is '.date("l, F jS, Y").'. (Week '.date("w").'/52 & Day '.date("z").'/'.(365+date("L")).")</h4>\n";
echo '<p>To add an item to the tickler file:
	<ul>
		<li>Add a new item (<a href="item.php?type=a" title="Add new action">action</a>,
			<a href="item.php?type=w" title="Add new waitingOn">waiting</a>,
			<a href="item.php?type=r" title="Add new reference">reference</a>),
			<a href="project.php?type=p">project</a>, or <a href="project.php?type=s">someday/maybe</a> as appropriate,</li>
		<li>Select the tickler option, and fill in the details as desired.</li>
	</ul>
	<br />Reminder notes can be added <a href="note.php" Title="Add new reminder">here</a>.</p>';

if ($reminderresult!="-1") {
        echo "<br /><h3>Reminder Notes</h3>";
        $tablehtml="";
        foreach ($reminderresult as $row) {
                $tablehtml .= "	<tr>\n";
                $tablehtml .= "		<td>".$row['date']."</td>\n";
                $tablehtml .= '		<td><a href = "note.php?noteId='.$row['ticklerId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</td>\n";
                $tablehtml .= '		<td>'.nl2br(stripslashes($row['note']))."</td>\n";
                $tablehtml .= "	</tr>\n";
        }

        echo '<form action="processNoteUpdate.php" method="post">'."\n";
        echo "<table class='datatable'>\n";
        echo "	<thead>\n";
        echo "		<td>Reminder</td>\n";
        echo "		<td>Title</td>\n";
        echo "		<td>Note</td>\n";
        echo "	</thead>\n";
        echo $tablehtml;
        echo "</table>\n";
        echo "</form>\n";
}

if ($itemresult!="-1") {
        echo "<br />\n";
        echo "<h3>Actions, WaitingOn, and References</h3>\n";
        $tablehtml="";
        foreach ($itemresult as $row) {
                //Calculate reminder date as # suppress days prior to deadline
                        $dm=(int)substr($row['deadline'],6,2);
                        $dd=(int)substr($row['deadline'],8,2);
                        $dy=(int)substr($row['deadline'],0,4);
                        $remind=mktime(0,0,0,$dm,($dd-(int)$row['suppressUntil']),$dy);
                        $reminddate=gmdate("Y-m-d", $remind);
                //suppress row if reminder date has passed
                if ($reminddate >= date("Y-m-d")) {
                        $tablehtml .= "	<tr>\n";
                        $tablehtml .= "		<td>";
                        $tablehtml .= $reminddate."</td>\n";
                        $tablehtml .= "		<td>";
                        switch ($row['type']) {
                                case "a" : $tablehtml .= 'action';
                                break;
                                case "r" : $tablehtml .= 'reference';
                                break;
                                case "w" : $tablehtml .= 'waiting';
                                break;
                                }
                        $tablehtml .= "</td>\n";
                        $tablehtml .= '		<td><a href = "projectReport.php?projectId='.$row['projectId'].'" title="Go to '.htmlspecialchars(stripslashes($row['pname'])).' project report">'.stripslashes($row['pname'])."</a></td>\n";
                        //if nextaction, add icon in front of action (* for now)
                        if ($key = array_search($row['itemId'],$nextactions)) $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">*&nbsp;'.stripslashes($row['title'])."</td>\n";
                        else $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</td>\n";
                        $tablehtml .= '		<td>'.nl2br(substr(stripslashes($row['description']),0,72))."</td>\n";
                        $tablehtml .= '		<td><a href = "reportContext.php?contextId='.$row['contextId'].'" title="Go to '.htmlspecialchars(stripslashes($row['cname'])).' context report">'.stripslashes($row['cname'])."</td>\n";
                        $tablehtml .= '		<td>';
                        if(($row['deadline']) == "0000-00-00") $tablehtml .= "&nbsp;";
                        elseif(($row['deadline']) < date("Y-m-d")) $tablehtml .= '<font color="red"><strong title="Overdue">'.date("D M j,Y",strtotime($row['deadline'])).'</strong></font>';  //highlight overdue actions
                        elseif(($row['deadline']) == date("Y-m-d")) $tablehtml .= '<font color="green"><strong title="Due today">'.date("D M j,Y",strtotime($row['deadline'])).'</strong></font>'; //highlight actions due today
                        else $tablehtml .= date("D M j, Y",strtotime($row['deadline']));
                        $tablehtml .= "</td>\n";
                        if ($row['repeat']=="0") $tablehtml .= "		<td>--</td>\n";
                        else $tablehtml .= "		<td>".$row['repeat']."</td>\n";
                        $tablehtml .= '		<td align="center">  <input type="checkbox" align="center" name="completedNas[]" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" value="';
                        $tablehtml .= $row['itemId'];
                        $tablehtml .= '" /></td>'."\n";
                        $tablehtml .= "	</tr>\n";
                        }
                }
                echo '<form action="processItemUpdate.php" method="post">';
                echo "<table class='datatable'>\n";
                echo "	<thead>\n";
                echo "		<td>Reminder</td>\n";
                echo "		<td>Type</td>\n";
                echo "		<td>Project</td>\n";
                echo "		<td>Name</td>\n";
                echo "		<td>Description</td>\n";
                echo "		<td>Context</td>\n";
                echo "		<td>Deadline</td>\n";
                echo "		<td>Repeat</td>\n";
                echo "		<td>Completed</td>\n";
                echo "	</thead>\n";
                echo $tablehtml;
                echo "</table>\n";
                echo '<input type="hidden" name="type" value="'.$type.'" />'."\n";
                echo '<input type="hidden" name="contextId" value="'.$contextId.'" />'."\n";
                echo '<input type="hidden" name="referrer" value="tickler" />'."\n";
                echo '<input type="submit" class="button" value="Complete Items" name="submit"></form>'."\n";
        }

if ($projectresult!="-1") {
        echo "<br />\n";
        echo "<h3>Projects and Someday/Maybe</h3>\n";
        $tablehtml="";
        foreach ($projectresult as $row) {
                $tablehtml .= "	<tr>\n";
                $tablehtml .= "		<td>";
                //Calculate reminder date as # suppress days prior to deadline
                $dm=(int)substr($row['deadline'],6,2);
                $dd=(int)substr($row['deadline'],8,2);
                $dy=(int)substr($row['deadline'],0,4);
                $remind=mktime(0,0,0,$dm,($dd-(int)$row['suppressUntil']),$dy);
                                        $reminddate=gmdate("Y-m-d", $remind);
                $tablehtml .= $reminddate."</td>\n";
                $tablehtml .= '		<td><a href = "projectReport.php?projectId='.$row['projectId'].'" title="Go to '.htmlspecialchars(stripslashes($row['name'])).' project report">'.stripslashes($row['name'])."</td>\n";
                $tablehtml .= '		<td>'.nl2br(substr(stripslashes($row['description']),0,72))."</td>\n";
                $tablehtml .= '		<td>'.stripslashes($row['cname'])."</td>\n";
                $tablehtml .= "		<td>";
                if(($row['deadline']) == "0000-00-00") $tablehtml .= "&nbsp;";
                elseif(($row['deadline']) < date("Y-m-d")) $tablehtml .= '<font color="red"><strong title="Overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';  //highlight overdue projects
                elseif(($row['deadline']) == date("Y-m-d")) $tablehtml .= '<font color="green"><strong title="Due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>'; //highlight projects due today
                else $tablehtml .= date("D M j, Y",strtotime($row['deadline']));
                $tablehtml .= "</td>\n";
                if ($row['repeat']=="0") $tablehtml .= "		<td>--</td>\n";
                else $tablehtml .= "		<td>".$row['repeat']."</td>\n";
                                $tablehtml .= '		<td align="center"><input type="checkbox" align="center" name="completedProj[]" title="Complete '.htmlspecialchars(stripslashes($row['name'])).'. Will hide associated items." value="';
                                $tablehtml .= $row['projectId'];
                                $tablehtml .= '" /></td>'."\n";
                $tablehtml .= '		<td><a href="project.php?projectId='.$row['projectId'].'" title="Edit '.htmlspecialchars(stripslashes($row['name'])).'">Edit</td>'."\n";
                $tablehtml .= "	</tr>\n";
        }

                echo '<form action="processProjectUpdate.php" method="post">';
                echo "<table class='datatable'>\n";
                echo "	<thead>\n";
                echo "		<td>Reminder</td>\n";
                echo "		<td>Name</td>\n";
                echo "		<td>Description</td>\n";
                echo "		<td>Category</td>\n";
                echo "		<td>Deadline</td>\n";
                echo "		<td>Repeat</td>\n";
                echo "		<td>Completed</td>\n";
                echo "		<td>&nbsp;</td>\n";
                echo "	</thead>\n";
                echo $tablehtml;
                echo "</table>\n";
                echo '<input type="hidden" name="referrer" value="tickler" />'."\n";
                echo '<input type="submit" class="button" value="Complete Projects" name="submit" />'."\n";
                echo "</form>\n";
        }


include_once('footer.php');
?>
