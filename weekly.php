<?php
//INCLUDES
include_once('header.php');

//SQL CODE AREA
//select active projects
        $values['isSomeday']="n";
        $result = query("selectprojects",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
	echo "<h2>The Weekly Review</h2>
<table>
	<thead><td>Step</td><td>Description</td></thead>
	<tr><td>Gather all loose papers</td><td></td></tr>
	<tr><td>Process all notes</td><td></td></tr>
	<tr><td>Check all voice mail</td><td></td></tr>
	<tr><td>Review Email Inbox </td><td>Move each item to an action, waiting, or reference folder.</td></tr>
	<tr><td>Review Email action box </td><td>Put next action reminder into system for each one, delete completed emails or move to waiting on or reference boxes as approriate.</td></tr>
	<tr><td>Review Email waiting on box </td><td>Put waiting on reminder into system for each one, delete completed emails or move to reference box as approriate.</td></tr>
	<tr><td>Review previous calendar</td><td>Transfer any missed actions to system</td></tr>
	<tr><td>Purge agendas</td><td></td></tr>
	<tr><td>Review upcoming calendar</td><td>Capture actions about arrangements and preparations for any upcoming events</td></tr>
	<tr><td>Empty your head</td><td>Put in writing any new <a href=\"project.php?type=p\" title=\"Add project\">projects</a>, <a href=\"item.php?type=a\" title=\"Add action\">actions</a>, <a href=\"item.php?type=w\" title=\"Add waitingOn\">waitingOn</a>, <a href=\"item.php?type=r\" title=\"Add reference\">references</a>, and <a href=\"project.php?type=s\" title=\"Add Someday/Maybe\">someday/maybes</a> that are not yet in the system.</td></tr>
	<tr><td>Review <a href=\"listProjects.php?pType=p\">Projects list</a></td><td>Evaluate status of each project, goals, outcomes, one by one, ensuring that at least one next action exists for each.\n";

	echo "		<p><ul>Projects without Next Actions defined:\n";

        $counter=0;
	foreach($result as $row) {
            $values['projectId']=$row['projectId'];
            $nonext=query("selectnextaction",$config,$values);
		if ($nonext=="-1") {
                    echo '			<li><a href="projectReport.php?projectId='.$row['projectId'].'" title="Go to '.htmlspecialchars(stripslashes($row['name'])).'  project report">'.stripslashes($row['name'])."</a></li>\n";
	       $counter++;
                } 
           }
        if ($counter==0) echo "<li>None!</li>";
	echo "		</ul></p>\n";
	echo "	</td></tr>\n";
	echo '	<tr><td>Review <a href="listItems.php?type=a">Actions list</a></td><td>Mark off any completed actions, review for reminders of further actions to capture.</td></tr>'."\n";
	echo '	<tr><td>Review <a href="listItems.php?type=w">WaitingOn list</a></td><td>Mark off any returned items, and determine any further actions.</td></tr>'."\n";
	echo '	<tr><td>Review <a href="listList.php">Lists</a></td><td>Review relevant lists for actionable items or projects.</td></tr>'."\n";
	echo '	<tr><td>Review <a href="listChecklist.php">Checklists</a></td><td>Review relevant Checklists for actionable items or projects.</td></tr>'."\n";
	echo '	<tr><td>Review <a href="listProjects.php?pType=s">Someday/Maybe List</a></td><td>Add new fun things, move any existing items into Projects if they are ready to go</td></tr>'."\n";
	echo '	<tr><td>Review support files</td><td></td></tr>'."\n";
	echo '	<tr><td>Review <a href="listGoals.php">Goals</a></td><td>Check off goals from this week. Define goals for upcoming week</td></tr>'."\n";
	echo '	<tr><td>Brainstorm</td><td>Get creative with any new <a href="project.php?type=p" title="Add project">projects</a> or
		<a href="project.php?type=s" title="Add Someday/Maybe">someday/maybes</a> that may further your values, visions, goals, or areas of responsibility.</td></tr>'."\n";
	echo "</table>\n";

	include_once('footer.php');
?>
