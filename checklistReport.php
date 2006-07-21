<?php

////////////////////////////////////////////////////////
//File: ChecklistReport.php                           //
//Description: Show details about individual checklist//
//Accessed From: listChecklist.php                    //
//Links to: editChecklist.php, newChecklistItem.php   //
////////////////////////////////////////////////////////

	include_once('header.php');
	include_once('config.php');
	$checklistId = (int) $_GET['checklistId'];
	$checklistTitle =(string) $_GET['checklistTitle'];
 
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");

	mysql_select_db($db) or die ("unable to select database!");

	echo '<form action="processChecklistUpdate.php?checklistId='.$checklistId.'" method="POST">'."\n";
	echo "<h1>Checklist Report: $checklistTitle</h1>\n";
	
	echo '[ <a href="editChecklist.php?checklistId='.$checklistId.'&checklistTitle='.$checklistTitle.'">Edit Checklist</a> ]'."\n";
	echo "<br />\n";

	echo '<h2><a href = "newChecklistItem.php?checklistId='.$checklistId.'" style="text-decoration:none">Checklist Items</a></h2>'."\n";

	$query = "SELECT checklistItems.checklistitemId, checklistItems.item, checklistItems.notes, checklistItems.checklistId, checklistItems.checked
		FROM checklistItems
		LEFT JOIN checklist on checklistItems.checklistId = checklist.checklistId
		WHERE checklist.checklistId = '$checklistId' ORDER BY checklistItems.checked DESC, checklistItems.item ASC";
	$result = mysql_query($query) or die ("Error in query");

	if (mysql_num_rows($result) > 0){
		$counter=0;
		
		echo "<table cellpadding=2 border=1>\n";
		echo "	<tr>\n";
		echo "		<th>Item</th>\n";
		echo "		<th>Notes</th>\n"; 
		echo "		<th>Checked</th>\n";
		echo "	</tr>\n";
		
		while($row = mysql_fetch_row($result)){
                echo "	<tr>\n";
                $checklistItemId = $row[0];
                echo '		<td><a href = "editChecklistItem.php?checklistItemId='.$checklistItemId.'">'.$row[1]."</a></td>\n";
                echo "		<td>".$row[2]."</td>\n";
		echo '		<td align="center"><input type="checkbox" name="checkedClis[]" value="'.$checklistItemId.'" ';
		if ($row[4]=='y') echo 'CHECKED';
		echo "></td>\n";
                echo "	</tr>\n";
                $counter = $counter+1;
		}
		echo "</table>\n";

		echo '<p>&nbsp;&nbsp;Clear Checklist&nbsp;<input type="checkbox" name="clear" value="y"></p>'."\n";

		echo '<p><input type="submit" align="right" class="button" value="Update Checklist Items" name="submit">'."\n";
		echo '<input type="reset" class="button" value="Reset to Saved State"></p>'."\n";
		if($counter==0){
			echo "No checklist items\n";
		}
	}



	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
