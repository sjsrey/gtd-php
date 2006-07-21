<?php
//INCLUDES
include_once('gtdfuncs.php');
include_once('header.php');
include_once('config.php');

//RETRIEVE URL VARIABLES
$projectId =(int) $_GET["projectId"];
$type = $_GET['type']{0};

//SQL CODE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect");
mysql_select_db($db) or die ("unable to select database!");

//Get project details
if ($projectId>0) {
	$query= "SELECT projects.projectId, projects.name, projects.description, projects.desiredOutcome, 
		projectstatus.dateCreated, projectstatus.dateCompleted, projectattributes.categoryId, projectattributes.deadline,
		projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil, projectattributes.isSomeday 
		FROM projects, projectattributes, projectstatus 
		WHERE projectstatus.projectId=projects.projectId and projectattributes.projectId=projects.projectId and 
		projects.projectId = '$projectId'";
	$result = mysql_query($query) or die ("Error in query");
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	if ($type=$row['isSomeday']=="y") $type='s';
	else $type='p';
	}

//select all categories for dropdown list
$query = "SELECT categories.categoryId, categories.category from categories ORDER BY categories.category ASC";
$result = mysql_query($query) or die("Error in query");
$cshtml="";
while($catrow = mysql_fetch_assoc($result)) {
	if($catrow['categoryId']==$row['categoryId']){
		$cshtml .= "			<option selected value='" .$catrow['categoryId'] . "'>" . stripslashes($catrow['category']) . "</option>\n";
	}
	else {
		$cshtml .= "			<option value='" .$catrow['categoryId'] . "'>" . stripslashes($catrow['category']) . "</option>\n";
	}
}
mysql_free_result($result);

//PAGE DISPLAY CODE
//determine project labels
if ($type=="s") $typename="Someday/Maybe";
else $typename="Project";

if ($projectId>0) {
	echo "<h2>Edit&nbsp;".$typename."</h2>\n";	
	echo '<form action="updateProject.php?projectId='.$projectId.'" method="post">'."\n";
}

else {
	echo "<h2>New&nbsp;".$typename."</h2>\n";
	echo '<form action="processProject.php" method="post">'."\n";
}


echo '<table border="0">'."\n";
echo "	<tr>\n";
echo '		<td>Category&nbsp;<select name="categoryId">'."\n";
echo $cshtml;
echo '		</select></td>'."\n";
echo '		<td><input type="checkbox" name="isSomeday" value="y" title="Places project in Someday file"';
if ($type=='s') echo 'CHECKED';
echo '>&nbsp;Someday</td>'."\n";
echo '		<td><input type="checkbox" name="delete" value="y" title="Deletes project and ALL associated items">&nbsp;Delete&nbsp;'.$typename.'</td>'."\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td><input type="checkbox" name="suppress" value="y" title="Hides this project from the active view"';
if ($row['suppress']=="y") echo " CHECKED";
echo '>Tickle&nbsp;<input type="text" size="3" name="suppressUntil" value="'.$row['suppressUntil'].'">'.'&nbsp;days before deadline</td>'."\n";
echo '		<td colspan="2">Deadline:&nbsp;';
if ($row['deadline']=="0000-00-00" || $row['deadline']==NULL || $row['deadline']>date("Y-m-d")) {
	DateDropDown(365,"deadline",$row['deadline']);
	}
else echo '<input type="text" size="10" name="deadline" value="'.$row['deadline'].'" />';
echo "</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td>Repeat every&nbsp;<input type="text" name="repeat" size="3" value="'.$row['repeat'].'">&nbsp;days</td>';
echo '<td colspan="2">Completed:&nbsp;';

if ($row['dateCompleted']=="0000-00-00" || $row['dateCompleted']==NULL) {
        DateDropDown(365,"dateCompleted",$currentrow['dateCompleted']);
        }
else echo '<input type="text" size="10" name="dateCompleted" value="'.$row['dateCompleted'].'" />'."\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";

echo "<table>\n";
echo "	<tr>\n";
echo '		<td colspan="3">Project Name</td>'."\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td colspan="3">';
echo '<input type="text" name="name" size="79" value="'.stripslashes($row['name']).'"></td>'."\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td colspan="2">Description</td>'."\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td colspan="2"><textarea cols="77" rows="8" name="description" wrap=virtual">'.stripslashes($row['description'])."</textarea></td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td colspan="2">Desired Outcome</td>'."\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo '		<td colspan="2"><textarea cols="77" rows="4" name="outcome" wrap=virtual">'.stripslashes($row['desiredOutcome'])."</textarea></td>\n";
echo "	</tr>\n";
echo "</table>\n";

if ($projectId>0) {
	echo '<table>';
	echo "	<tr>\n";
	echo '		<td>Date Added:&nbsp;'.$row['dateCreated']."</td>\n";
	echo '		<td>Last Modified:&nbsp;'.$row['lastModified']."</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
}

echo "<br />\n";
echo '<input type="hidden" name="type" value='.$type.'" />'."\n";

if ($projectId>0) {
	echo '<input type="submit" class="button" value="Update '.$typename.'" name="submit">'."\n";
	}

else echo '<input type="submit" class="button" value="Add '.$typename.'" name="submit">'."\n";

include_once('footer.php');
?>
