<?php

//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect");
mysql_select_db($db) or die ("Unable to select database!");

//SQL CODE
//select all nextactions for test
$query = "SELECT projectId, nextaction FROM nextactions";
$result = mysql_query($query) or die ("Error in query");
$nextactions = array();
while ($nextactiontest = mysql_fetch_assoc($result)) {
	//populates $nextactions with itemIds using projectId as key
	$nextactions[$nextactiontest['projectId']] = $nextactiontest['nextaction'];
	}

//Select notes
$query = "SELECT ticklerId, title, note, date FROM tickler WHERE (date IS NULL OR date = '0000-00-00') OR (CURDATE()<= date)";
$reminderResult = mysql_query($query) or die ("Error in query");

//Select suppressed items
$query = "SELECT itemattributes.projectId, projects.name AS pname, items.title, items.description, itemstatus.dateCreated, 
	context.contextId, context.name AS cname, items.itemId, itemstatus.dateCompleted, itemattributes.deadline,
	itemattributes.repeat, itemattributes.suppress, itemattributes.suppressUntil, itemattributes.type
	FROM items, itemattributes, itemstatus, projects, projectstatus, context
	WHERE itemstatus.itemId = items.itemId AND itemattributes.itemId = items.itemId
	AND itemattributes.contextId = context.contextId AND itemattributes.projectId = projects.projectId
	AND projectstatus.projectId = itemattributes.projectId
	AND (itemstatus.dateCompleted IS NULL OR itemstatus.dateCompleted = '0000-00-00')
	AND (projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00') AND (itemattributes.suppress='y')
	ORDER BY itemattributes.deadline, cname, pname";
$itemResult = mysql_query($query) or die ("Error in query");

//Select suppressed projects
$query = "SELECT projects.projectId, projects.name, projects.description, projectstatus.dateCreated,
        categories.categoryId, categories.category AS cname, projectattributes.deadline,
        projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil
        FROM projects, projectattributes, projectstatus, categories
        WHERE projectstatus.projectId = projects.projectId AND projectattributes.projectId = projects.projectId
	AND categories.categoryId=projectattributes.categoryId
        AND (projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00') AND (projectattributes.suppress='y')
        ORDER BY projectattributes.deadline, cname, projects.name";
$projectResult = mysql_query($query) or die ("Error in query");

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

	if (mysql_num_rows($reminderResult) > 0) {
		echo "<br /><h3>Reminder Notes</h3>";
		$tablehtml="";		
		while($row = mysql_fetch_assoc($reminderResult)) {
			$tablehtml .= "	<tr>\n";
			$tablehtml .= "		<td>".$row['date']."</td>\n";
			$tablehtml .= '		<td><a href = "note.php?noteId='.$row['ticklerId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</td>\n";
			$tablehtml .= '		<td>'.nl2br(stripslashes($row['note']))."</td>\n";
			$tablehtml .= "	</tr>\n";
		}

		echo '<form action="processNoteUpdate.php" method="post">'."\n";
		echo "<table>\n";
		echo "	<tr>\n";
		echo "		<th>Reminder</th>\n";
		echo "		<th>Title</th>\n";
		echo "		<th>Note</th>\n";
		echo "	</tr>\n";
		echo $tablehtml;
		echo "</table>\n";
	}

	if (mysql_num_rows($itemResult) > 0) {
		echo "<br />\n";
		echo "<h3>Actions, WaitingOn, and References</h3>\n";
		$tablehtml="";		
		while($row = mysql_fetch_assoc($itemResult)) {
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
				//$tablehtml .= "</td>\n";
				//if nextaction, add icon in front of action (* for now)
				if ($key = array_search($row['itemId'],$nextactions)) $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">*&nbsp;'.stripslashes($row['title'])."</td>\n";
				else $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title'])."</td>\n";
				$tablehtml .= '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
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
			echo "<table>\n";
			echo "	<tr>\n";
			echo "		<th>Reminder</th>\n";
			echo "		<th>Type</th>\n";
			echo "		<th>Project</th>\n";
			echo "		<th>Name</th>\n";
			echo "		<th>Description</th>\n";
			echo "		<th>Context</th>\n";
			echo "		<th>Deadline</th>\n";
			echo "		<th>Repeat</th>\n";
			echo "		<th>Completed</th>\n";
			echo "	</tr>\n";
			echo $tablehtml;
			echo "</table>\n";
			echo '<input type="hidden" name="type" value="'.$type.'" />'."\n";
			echo '<input type="hidden" name="contextId" value="'.$contextId.'" />'."\n";
			echo '<input type="hidden" name="referrer" value="tickler" />'."\n";
			echo '<input type="submit" class="button" value="Complete Items" name="submit"></form>'."\n";
		}

	if (mysql_num_rows($projectResult) > 0) {
		echo "<br />\n";
		echo "<h3>Projects and Someday/Maybe</h3>\n";
		$tablehtml="";		
		while($row = mysql_fetch_assoc($projectResult)) {
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
			$tablehtml .= '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
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
			echo "<table>\n";
			echo "	<tr>\n";
			echo "		<th>Reminder</th>\n";
			echo "		<th>Name</th>\n";
			echo "		<th>Description</th>\n";
			echo "		<th>Category</th>\n";
			echo "		<th>Deadline</th>\n";
			echo "		<th>Repeat</th>\n";
			echo "		<th>Completed</th>\n";
			echo "		<th>&nbsp;</th>\n";
			echo "	</tr>\n";
			echo $tablehtml;
			echo "</table>\n";
			echo '<input type="hidden" name="referrer" value="tickler" />'."\n";
			echo '<input type="submit" class="button" value="Complete Projects" name="submit" />'."\n";
			echo "</form>\n";
		}


	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
