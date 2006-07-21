<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
$newitem=mysql_real_escape_string($_POST['newitem']);
$newnotes=mysql_real_escape_string($_POST['newnotes']);
$checklistId = (int) $_POST['checklist'];
$newchecked = $_POST['checked']{0};
if($newchecked!="y") $newchecked='n';
$checklistItemId = (int) $_GET['checklistItemId'];
$delete=$_POST['delete']{0};

//SQL CODE AREA
if($delete=="y") {
        $query= "delete from checklistItems where checklistItemId='$checklistItemId'";
        $result = mysql_query($query) or die ("Error in query: $query. ".mysql_error());
        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=checklistReport.php?checklistId='.$checklistId.'">';
        echo "<p>Number of Records Deleted: ";
        echo mysql_affected_rows();
	}
else {
    $query = "select title from checklist where checklistId='$checklistId'";
    $result = mysql_query($query) or die ("Error in query: $query. ".mysql_error());
    $row=mysql_fetch_row($result);
    $title=$row[0];
	$query = "update checklistItems
	set notes = '$newnotes', item = '$newitem', checklistId = '$checklistId', checked='$newchecked'
	where checklistItemId ='$checklistItemId'";
	$result = mysql_query($query) or die ("Error in query: $query. ".mysql_error());
    echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=checklistReport.php?checklistId='.$checklistId.'&checklistTitle='.$title.'">';
	echo "Number of Records Updated: ";
	echo mysql_affected_rows();
	}

mysql_close($connection);
include_once('footer.php');
?>


