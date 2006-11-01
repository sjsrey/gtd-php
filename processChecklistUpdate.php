<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$date=$_POST['date'];
$checklistItemId = (int) $_POST['checklistItemId'];
$checklistId = (int) $_GET['checklistId'];
$checkedClis = $_POST['checkedClis'];
$clear = $_POST['clear']{0};
$date=date('Y-m-d');

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$checklistId.'&checklistTitle='.$checklistTitle.'">';

$query= "UPDATE checklistItems SET checked='n' where checklistId='$checklistId'";
$result = mysql_query($query) or die ("Error in query");

if ((isset($checkedClis))&&($clear!="y")) {

	foreach ($checkedClis as $Cli) {
		$query= "update checklistItems set checked='y' where checklistItemId='$Cli'";
		$result = mysql_query($query) or die ("Error in query");
		}
	}

        $query = "select checklistId from checklistItems where checklistItemId='$checklistItemId'";
        $result=mysql_query($query);
        $row=mysql_fetch_row($result);
        $checklistId=$row[0];


mysql_close($connection);
include_once('footer.php');
?>

