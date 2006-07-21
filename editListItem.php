<?php

//INCLUDES
	include_once('header.php');
	include_once('config.php');

//RETRIEVE URL VARIABLES
	$listItemId =(int) $_GET["listItemId"];

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

//SQL CODE AREA
	$query = "SELECT listItemId, item, notes, listId, dateCompleted from listItems where listItemId = $listItemId";
	$result = mysql_query($query) or die ("Error in query");
	$currentrow = mysql_fetch_row($result);
	$listItemId = $currentrow[0];
	$item = stripslashes($currentrow[1]);
	$notes = stripslashes($currentrow[2]);
	$listId = $currentrow[3];
	$dateCompleted = $currentrow[4];
	
	echo "<h1>Edit List Item</h1>";

//SELECT listId, title, categoryId, description from list ORDER BY title

	$query = "SELECT * from list ORDER BY title";
	$result = mysql_query($query) or die ("Error in query");
	echo '<form action="updateListItem.php?listItemId='.$listItemId.'" method="post">';
	echo '<table border="0">';
	echo '<tr><td>List</td>';
	echo '<td><select name="list">';
	while($row = mysql_fetch_row($result)){
		if($row[0]==$listId){
			echo "<option selected value='" .$row[0] . "'>".stripslashes($row[1])."</option>\n";
		}else{
			echo "<option value='" .$row[0] . "'>" .stripslashes($row[1])."</option>\n";
		}
	}
	echo '</td>';
	mysql_free_result($result);

	echo '<td>Date Completed:</td>';
	echo '<td><input type="text" name="newdateCompleted" size="13" value="';
	echo $dateCompleted;
	echo '"></td>';
	echo '</tr>';
	echo "</table>";

	echo "<table>";
	echo '<tr><td>Title</td></tr>';
	echo '<tr><td><textarea cols="80" rows="2" name="newitem" wrap=virtual">';
	echo $item;
	echo '</textarea></td></tr>';
	echo '<tr><td>Description</td></tr>';
	echo '<tr><td><textarea cols="80" rows="4" name="newnotes" wrap=virtual">';
	echo $notes;
	echo ' </textarea></td></tr>';
        echo '<tr><td>Delete List Item&nbsp;<input type="checkbox" name="delete" value="delete"></td></tr>';
	echo '</table>';
	echo '<br>';
	echo '<input type="submit" value="Update List Item" name="submit">';
	echo '<input type="reset" value="Reset">';
	include_once('footer.php');
?>
