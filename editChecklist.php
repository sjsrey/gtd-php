<?php
	include_once('header.php');
	include_once('config.php');
	$checklistId =(int) $_GET["checklistId"];
	$checklistTitle =(string) $_GET['checklistTitle'];

	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");

	mysql_select_db($db) or die ("unable to select database!");
	
	$query = "SELECT title, description, categoryId FROM checklist WHERE checklistId = '$checklistId'";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$row = mysql_fetch_array($result);

	echo "<h2>Edit Checklist: $checklistTitle</h2>";	
	echo '<form action="updateChecklist.php?checklistId='.$checklistId.'" method="post">';
	echo '<table border="0">';
	echo '<tr><td>Checklist Title</td><td>Category</td></tr>';
	echo '<tr><td>';
	echo '<input type="text" name="newchecklistTitle" size="50" value="'.$row[0].'"></td>';

		$catquery = "select * from categories";
		$catresult = mysql_query($catquery) or die("error in query: $catquery.  ".mysql_error());

	echo '<td><select name="newcategoryId">';
		while($catrow = mysql_fetch_row($catresult)){
			if ($catrow[0]==$row[2]) echo "<option value='" .$catrow[0] . "' SELECTED>".$catrow[1]."</option>\n";
			else echo "<option value='".$catrow[0]."'>".$catrow[1]."</option>\n";
			}
        echo '</select></td></tr>';

	echo '<tr><td colspan="2">Description</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<textarea cols="80" rows="2" name="newdescription" wrap=virtual">';
	echo $row[1];
	echo '</textarea></td></tr>';

        echo '<tr><td>Delete Checklist&nbsp;<input type="checkbox" name="delete" value="y" title="ALL items will be deleted!"</td>';
        echo '<td>Clear Checklist&nbsp;<input type="checkbox" name="clear" value="clear"></td></tr>';

	echo '</table>';
	echo '<br />';
	echo '<input type="submit" class="button" value="Update Checklist" name="submit">';
	echo '<input type="reset" class="button" value="Reset">';

	include_once('footer.php');
?>
