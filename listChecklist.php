<?php
	include_once('header.php');

	echo "<h2>Checklists</h2>\n";

//SELECT categoryId, category, description FROM categories ORDER by category ASC

//SJK  Allows viewing of checklists by category
	echo '<form action="listChecklist.php" method="post">'."\n";
	echo "<p>Category:&nbsp;\n";
	$categoryId=(int) $_POST['categoryId'];
	$query = "select * from categories";
	$result = mysql_query($query) or die("Error in query");
	echo '<select name="categoryId" title="Filter checklists by category">'."\n";
	echo '	<option value="0">All</option>'."\n";
	while($row = mysql_fetch_row($result)){
		if($row[0]==$categoryId){
			echo "	<option selected value='" .$row[0] . "'>" .stripslashes($row[1])."</option>\n";
		} else {
			echo "	<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}
	}
	echo "</select>\n";
	mysql_free_result($result);
	echo '<input type="submit" align="right" class="button" value="Update" name="submit">'."\n";
	echo "</p>\n";  // $

	if ($categoryId==NULL) $categoryId='0';
	if ($categoryId=='0') {
	   $query = "SELECT checklist.checklistId, checklist.title, checklist.description,
				checklist.categoryId, categories.category
				FROM checklist, categories
				WHERE checklist.categoryId=categories.categoryId
				ORDER BY categories.category ASC";
		$result = mysql_query($query) or die ("Error in query");
	} else {
	   $query = "SELECT checklist.checklistId, checklist.title, checklist.description,
				checklist.categoryId, categories.category
				FROM checklist, categories
				WHERE checklist.categoryId=categories.categoryId AND checklist.categoryId='$categoryId'
				ORDER BY categories.category ASC";
		$result = mysql_query($query) or die ("Error in query");
	}


	if (mysql_num_rows($result) > 0){
		echo "<p>Select checklist for report.</p>\n";
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Category</td>\n";
		echo "		<td>Title</td>\n";
		echo "		<td>Description</td>\n";
		echo "	</thead>\n";
		while($row = mysql_fetch_row($result)){
			echo "	<tr>\n";
			echo "		<td>".stripslashes($row[4])."</td>\n";
			echo '		<td><a href="checklistReport.php?checklistId='.$row[0].'&checklistTitle='.urlencode($row[1]).'">'.stripslashes($row[1])."</a></td>\n";
			echo "		<td>".stripslashes($row[2])."</td>\n";
			echo "	</tr>\n";
		}
		echo "</table>\n";
	} else {
		$message="You have not defined any checklists yet.";
		$prompt="Would you like to create a new checklist?";
		$yeslink="newChecklist.php";
		nothingFound($message,$prompt,$yeslink);
	}

	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
