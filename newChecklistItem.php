<?php

//INCLUDES
	include_once('header.php');
	include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

//RETRIEVE URL VARIABLES
	$checklistId = (int) $_GET['checklistId'];

//SQL CODE AREA
	$query = "SELECT checklistId, title from checklist order by title";
	$result = mysql_query($query) or die ("Error in query");

	if (mysql_num_rows($result) > 0){
		echo '<h2>New Checklist Item</h2>';
		
		echo '<form action="processChecklistItem.php" method="POST">';
		
		echo '<table>';
		echo '<tr><td>Checklist</td>';
		echo '<td><select name="checklistId">';
		while($row = mysql_fetch_row($result)){
			if($row[0]==$checklistId){
				echo "<option selected value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}else{
				echo "<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}
		}
		echo '</td>';
		echo '</table>';

		echo "<table>";
		echo '<tr><td>Item</td>';
		echo '<td><input type="text" name="item" value="'.$item.'"></td>';
		echo '<tr><td>Notes</td>';
		echo '<td><textarea cols="60" rows="3" name="notes" wrap=virtual">';
		echo $notes;
		echo '</textarea></td>';
		echo '</tr></table>';
		echo '<br />';
		echo '<input type="submit" class="button" value="Add List Item" name="submit">';
		echo '<input type="reset" class="button" value="Reset">';
		
	}
	else{
		echo "No rows found!";
	}
	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
