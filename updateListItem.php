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
$listId = (int) $_POST['list'];
$newdateCompleted = $_POST['newdateCompleted'];
$listItemId = (int) $_GET['listItemId'];
$delete=$_POST['delete']{0};

//SQL CODE AREA
if($delete=="y") {
        $query= "delete from listItems where listItemId='$listItemId'";
        $result = mysql_query($query) or die ("Error in query");
        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listReport.php?listId='.$listId.'">';
        echo "<p>Number of Records Deleted: ";
        echo mysql_affected_rows();
	}
else {
	$query = "update listItems
	set notes = '$newnotes', item = '$newitem', listId = '$listId', dateCompleted='$newdateCompleted'
	where listItemId ='$listItemId'";
	$result = mysql_query($query) or die ("Error in query");
	}

mysql_close($connection);
include_once('footer.php');
?>


