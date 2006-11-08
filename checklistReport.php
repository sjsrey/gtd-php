<?php

////////////////////////////////////////////////////////
//File: ChecklistReport.php                           //
//Description: Show details about individual checklist//
//Accessed From: listChecklist.php                    //
//Links to: editChecklist.php, newChecklistItem.php   //
////////////////////////////////////////////////////////

//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values['checklistId'] = (int) $_GET['checklistId'];

//SQL CODE
$result = query("selectchecklist",$config,$values,$options,$sort);
if ($result!="-1") $row=$result[0];

$result= query("getchecklistitems",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
echo '<form action="processChecklistUpdate.php?checklistId='.$row['checklistId'].'" method="POST">'."\n";
echo "<h1>Checklist Report: {$row['title']}</h1>\n";
echo '[ <a href="editChecklist.php?checklistId='.$row['checklistId'].'&checklistTitle='.$row['checklistTitle'].'">Edit Checklist</a> ]'."\n";
echo "<br />\n";
echo '<h2><a href = "newChecklistItem.php?checklistId='.$row['checklistId'].'" style="text-decoration:none">Checklist Items</a></h2>'."\n";

if ($result!="-1") {
        echo "<table class='datatable'>\n";
        echo "	<thead>\n";
        echo "		<td>Item</td>\n";
        echo "		<td>Notes</td>\n";
        echo "		<td>Checked</td>\n";
        echo "	</thead>\n";

    foreach ($result as $currentrow) {
        echo "	<tr>\n";
        echo '		<td><a href = "editChecklistItem.php?checklistItemId='.$currentrow['checklistitemId'].'">'.$currentrow['item']."</a></td>\n";
        echo "		<td>".$currentrow['notes']."</td>\n";
        echo '		<td align="center"><input type="checkbox" name="checkedClis[]" value="'.$currentrow['checklistitemId'].'" ';
        if ($currentrow['checked']=='y') echo 'CHECKED';
        echo "></td>\n";
        echo "	</tr>\n";
        }

    echo "</table>\n";
        echo '<p>&nbsp;&nbsp;Clear Checklist&nbsp;<input type="checkbox" name="clear" value="y"></p>'."\n";
        echo '<p><input type="submit" align="right" class="button" value="Update Checklist Items" name="submit">'."\n";
        echo '<input type="reset" class="button" value="Reset to Saved State"></p>'."\n";
    }

else {
    $message="You have no items.";
    $prompt="Would you like to create a new item?";
    $yeslink="newChecklistItem.php?checklistId=".$row['checklistId'];
    nothingFound($message,$prompt,$yeslink);
        }


include_once('footer.php');
?>
