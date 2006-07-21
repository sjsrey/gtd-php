<?php
	include_once('header.php');
	include_once('config.php');
	$listId = (int) $_GET['listId'];
	$listTitle = (string) $_GET['listTitle'];
 
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");

	mysql_select_db($db) or die ("Unable to select database!");

	echo '<form action="processListUpdate.php?listId='.$listId.'" method="POST">';
	echo "<h1>List Report: $listTitle</h1>";
	
	echo '[ <a href="editList.php?listId='.$listId.'&listTitle='.$listTitle.'">Edit List</a> ]';
	echo "<br />";

	echo '<h2><a href = "newListItem.php?listId='.$listId.'" style="text-decoration:none">List Items</a></h2>';

	$query = "SELECT listItems.listItemId, listItems.item, listItems.notes, listItems.listId
		FROM listItems
		LEFT JOIN list on listItems.listId = list.listId
		WHERE list.listId = '$listId' AND (listItems.dateCompleted is not null and listItems.dateCompleted='0000-00-00')";
	$result = mysql_query($query) or die ("Error in query");

	if (mysql_num_rows($result) > 0){
		$counter=0;
		
		echo "<table cellpadding=2 border=1>";
		echo '<th>Item</th>';
		echo '<th>Description</th>'; 
		echo '<th>Completed</th>';
		echo '</tr>';
		
		while($row = mysql_fetch_row($result)){
                echo "<tr>";
                $listItemId = $row[0];
                echo '<td><a href = "editListItem.php?listItemId='.$listItemId.'" title="Edit '.htmlspecialchars(stripslashes($row[1])).'">'.stripslashes($row[1]).'</td>';
                echo "<td>".stripslashes($row[2])."</td>";
                echo '<td align="center">  <input type="checkbox" align="center" name="completedLis[]" title="Complete '.htmlspecialchars(stripslashes($row[1])).'" value="';
                echo $listItemId;
                echo '"';
                echo "</tr>";
                $counter = $counter+1;
		}
		echo "</table>";
	    echo '<input type="submit" align="right" class="button" value="Update List Items" name="submit">';
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

	echo "<h2>Completed List Items</h2>";
	if (mysql_num_rows($result) > 0){
		echo "<table cellpadding=2 border=1>";
		echo '<th>Item</th>';
		echo '<th>Notes</th>';
//		echo '<th>Completed</th>';
		echo '</tr>';
		while($row = mysql_fetch_row($result)){
				echo "<t	
r>";
				$listItemId = $row[0];
				echo '<td align = "left">';
				echo '<a href = "editListItem.php?listItemId='.$listItemId.'">'.stripslashes($row[1]).'</a></td>';
				echo "<td>".$row[2]."</td>";
//		                echo '<td align="center">  <input type="checkbox" align="center" name="completedListitem[]" title="Complete '.htmlspecialchars(stripslashes($row[1])).'" value="';
//	        	        echo $listItemId;
//        	        	echo '"';
//				echo "</td></tr>";
				echo "</tr>";	

		}
		echo "</table>";
	}
	else{
		echo "None";
	}



	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
