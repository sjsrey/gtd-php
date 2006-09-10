<?php
	include_once('header.php');
	include_once('config.php');
	$listId = (int) $_GET['listId'];
	$listTitle = (string) $_GET['listTitle'];
 
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");

	mysql_select_db($db) or die ("Unable to select database!");

	echo "<h1>List Report: $listTitle</h1>\n";
	echo '<form action="processListUpdate.php?listId='.$listId.'" method="POST">'."\n";
	
	echo '[ <a href="editList.php?listId='.$listId.'&listTitle='.$listTitle.'">Edit List</a> ]'."\n";
	echo "<br />\n";

	echo '<h2><a href = "newListItem.php?listId='.$listId.'" style="text-decoration:none">List Items</a></h2>'."\n";

	$query = "SELECT listItems.listItemId, listItems.item, listItems.notes, listItems.listId
		FROM listItems
		LEFT JOIN list on listItems.listId = list.listId
		WHERE list.listId = '$listId' AND (listItems.dateCompleted is not null and listItems.dateCompleted='0000-00-00')";
	$result = mysql_query($query) or die ("Error in query");

	if (mysql_num_rows($result) > 0){
		$counter=0;
		
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Item</td>\n";
		echo "		<td>Description</td>\n"; 
		echo "		<td>Completed</td>\n";
		echo "	</thead>\n";
		
		while($row = mysql_fetch_row($result)){
                echo "	<tr>\n";
                $listItemId = $row[0];
                echo '		<td><a href = "editListItem.php?listItemId='.$listItemId.'" title="Edit '.htmlspecialchars(stripslashes($row[1])).'">'.stripslashes($row[1])."</td>\n";
                echo "		<td>".stripslashes($row[2])."</td>\n";
                echo '		<td align="center"><input type="checkbox" align="center" name="completedLis[]" title="Complete '.htmlspecialchars(stripslashes($row[1])).'" value="';
                echo $listItemId;
                echo '"></td>'."\n";
                echo "	</tr>\n";
                $counter = $counter+1;
		}
		echo "</table>\n\n";
	    echo '<input type="submit" align="right" class="button" value="Update List Items" name="submit">'."\n";
		if($counter==0){
			echo "No list items";
		}
	}
	else{
		echo "None";
	}

	$query = "SELECT listItems.listItemId, listItems.item, listItems.notes, listItems.listId
		FROM listItems
		LEFT JOIN list on listItems.listId = list.listId
		WHERE list.listId = '$listId' and (listItems.dateCompleted!='0000-00-00' and listItems.dateCompleted is not null)";

	$result = mysql_query($query) or die ("Error in query");

	echo "<h2>Completed List Items</h2>\n";
	if (mysql_num_rows($result) > 0){
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Item</td>\n";
		echo "		<td>Notes</td>\n";
//		echo '<td>Completed</td>';
		echo "	</thead>\n";
		while($row = mysql_fetch_row($result)){
			echo "	<tr>\n";
			$listItemId = $row[0];
			echo '		<td align = "left">';
			echo '<a href = "editListItem.php?listItemId='.$listItemId.'">'.stripslashes($row[1])."</a></td>\n";
			echo "		<td>".$row[2]."</td>\n";
//			echo '<td align="center">  <input type="checkbox" align="center" name="completedListitem[]" title="Complete '.htmlspecialchars(stripslashes($row[1])).'" value="';
//			echo $listItemId;
//			echo '"';
//			echo "</td></tr>";
			echo "	</tr>\n";
		}
		echo "</table>\n";
	}
	else{
		echo "None";
	}



	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
