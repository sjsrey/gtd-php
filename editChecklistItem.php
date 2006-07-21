<?php
	include_once('header.php');
	include_once('config.php');
	$checklistItemId =$_GET["checklistItemId"];

	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");

	mysql_select_db($db) or die ("unable to select database!");

	$query = "SELECT checklistItemId, item, notes, checklistId, checked from checklistItems where checklistItemId = $checklistItemId";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$currentrow = mysql_fetch_row($result);
	$checklistItemId = $currentrow[0];
	$item = $currentrow[1];
	$notes = $currentrow[2];
	$checklistId = $currentrow[3];
	$checked = $currentrow[4];
	
	echo "<h1>Edit checklist Item</h1>\n";

//SELECT checklistId, title, categoryId, description from checklist ORDER BY title

	$query = "SELECT * from checklist ORDER BY title";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	echo '<form action="updateChecklistItem.php?checklistItemId='.$checklistItemId.'" method="post">'."\n";
	echo '<table border="0">'."\n";
	echo "	<tr>\n";
	echo "		<td>checklist</td>\n";
	echo '		<td><select name="checklist">'."\n";
	while($row = mysql_fetch_row($result)){
		if($row[0]==$checklistId){
			echo "			<option selected value='" .$row[0] . "'>" . stripslashes($row[1]) . "</option>";
		}else{
			echo $row[0];
			echo $checklistId;
			echo "			<option value='" .$row[0] . "'>" . stripslashes($row[1]) . "</option>";
		}
	}
	echo $row[0];
	echo $checklistId;
	echo "</td>\n";
	mysql_free_result($result);
	echo "		<td>Completed:</td>\n";
	echo '		<td><input type="checkbox" name="checked" value="y" ';
		if ($checked=='y') echo 'CHECKED';
	echo '"></td>'."\n";

	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table>\n";
	echo "	<tr><td>Item</td></tr>\n";
	echo '	<tr><td><textarea cols="80" rows="2" name="newitem" wrap=virtual">'.$item.'</textarea></td></tr>'."\n";
	echo "	<tr><td>Notes</td></tr>\n";
	echo '	<tr><td><textarea cols="80" rows="4" name="newnotes" wrap=virtual">'.$notes.'</textarea></td></tr>'."\n";
	echo '	<tr><td>Delete checklist Item&nbsp;<input type="checkbox" name="delete" value="delete"></td></tr>'."\n";
	echo "</table>\n";
	echo "<br />\n";
	echo '<input type="submit" value="Update checklist Item" name="submit">'."\n";
	echo '<input type="reset" value="Reset">'."\n";
	include_once('footer.php');
?>
