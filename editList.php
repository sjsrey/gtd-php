<?php
	include_once('header.php');
	include_once('config.php');

	$listId =(int) $_GET["listId"];
	$listTitle =(string) $_GET['listTitle'];

	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");
	
	$query = "SELECT title, description, categoryId FROM list WHERE listId = '$listId'";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$row = mysql_fetch_array($result);

	echo "<h2>Edit List: $listTitle</h2>\n";	
	echo '<form action="updateList.php?listId='.$listId.'" method="POST">'."\n";
	echo '<table border="0">'."\n";
	echo "	<tr>\n";
	echo "		<td>List Title</td>\n";
	echo "		<td>Category</td></tr>\n";
	echo "	<tr>\n";
	echo "		<td>";
	echo '<input type="text" name="newlistTitle" size="50" value="'.$row[0].'"></td>'."\n";

//SELECT categoryId, category, description from categories

	$catquery = "select * from categories";
	$catresult = mysql_query($catquery) or die("Error in query");

	echo '		<td><select name="newcategoryId">'."\n";
		while($catrow = mysql_fetch_row($catresult)){
			if ($catrow[0]==$row[2]) echo "			<option value='" .$catrow[0] . "' SELECTED>".stripslashes($catrow[1])."</option>\n";
			else echo "			<option value='".$catrow[0]."'>".stripslashes($catrow[1])."</option>\n";
			}
        echo "		</select></td>\n";
		echo "	</tr>\n";

	echo '	<tr><td colspan="2">Description</td></tr>'."\n";
	echo '	<tr><td colspan="2">';
	echo '<textarea cols="80" rows="2" name="newdescription" wrap=virtual">';
	echo $row[1];
	echo "</textarea></td></tr>\n";
	echo '	<tr><td>Delete List&nbsp;<input type="checkbox" name="delete" title="ALL items will be deleted!" value="y"></td><td></td></tr>'."\n";
	echo "</table>\n";
	echo "<br />\n";
	echo '<input type="submit" class="button" value="Update List" name="submit">'."\n";
	echo '<input type="reset" class="button" value="Reset">'."\n";

	include_once('footer.php');
?>
