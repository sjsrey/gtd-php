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
		echo "<h2>New Checklist Item</h2>\n";		
		echo '<form action="processChecklistItem.php" method="POST">'."\n";
		
		echo "<table>\n";
		echo "	<tr>\n";
		echo "		<td>Checklist</td>\n";
		echo '		<td><select name="checklistId">'."\n";
		while($row = mysql_fetch_row($result)){
			if($row[0]==$checklistId){
				echo "			<option selected value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}else{
				echo "			<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}
		}
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n\n";

		echo "<table>\n";
		echo "	<tr>\n";
		echo "		<td>Item</td>\n";
		echo '		<td><input type="text" name="item" value="'.$item.'"></td>'."\n";
		echo "	<tr>\n";
		echo "		<td>Notes</td>\n";
		echo '		<td><textarea cols="60" rows="3" name="notes" wrap="virtual">';
		echo $notes;
		echo "</textarea></td>\n";
		echo "	</tr>\n";
		echo "</table>\n\n";
		echo "<br />\n";
		echo '<input type="submit" class="button" value="Add List Item" name="submit">'."\n";
		echo '<input type="reset" class="button" value="Reset">'."\n";
		
	}
	else{
		echo "No rows found!\n";
	}
	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
