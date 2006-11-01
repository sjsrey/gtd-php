<?php

////////////////////////////////////////////////////////
//File: ChecklistReport.php                           //
//Description: Show details about individual checklist//
//Accessed From: listChecklist.php                    //
//Links to: editChecklist.php, newChecklistItem.php   //
////////////////////////////////////////////////////////

	include_once('header.php');

	$values['checklistId'] = (int) $_GET['checklistId'];

        $result = query("selectchecklist",$config,$values,$options,$sort);
        if ($result!="-1") $row=$result[0];

	echo '<form action="processChecklistUpdate.php?checklistId='.$row['checklistId'].'" method="POST">'."\n";
	echo "<h1>Checklist Report: {$row['title']}</h1>\n";

	echo '[ <a href="editChecklist.php?checklistId='.$row['checklistId'].'&checklistTitle='.$row['checklistTitle'].'">Edit Checklist</a> ]'."\n";
	echo "<br />\n";

	echo '<h2><a href = "newChecklistItem.php?checklistId='.$row['checklistId'].'" style="text-decoration:none">Checklist Items</a></h2>'."\n";

	$query = "SELECT checklistItems.checklistitemId, checklistItems.item, checklistItems.notes, checklistItems.checklistId, checklistItems.checked
		FROM checklistItems
		LEFT JOIN checklist on checklistItems.checklistId = checklist.checklistId
		WHERE checklist.checklistId = '{$values['checklistId']}' ORDER BY checklistItems.checked DESC, checklistItems.item ASC";
	$result = mysql_query($query) or die ("Error in query");

	if (mysql_num_rows($result) > 0){
		$counter=0;

		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Item</td>\n";
		echo "		<td>Notes</td>\n";
		echo "		<td>Checked</td>\n";
		echo "	</thead>\n";

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
