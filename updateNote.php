<?php
//INCLUDES
include_once('gtdfuncs.php');
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//FORM DATA COLLECTION AND PARSING
$title = mysql_real_escape_string($_POST['title']);
$note = mysql_real_escape_string($_POST['note']);
$date = $_POST['date'];
$delete = $_POST['delete']{0};
$noteId = (int) $_GET['noteId'];

//SQL CODE AREA
if($delete=="y"){
        $query= "delete from tickler where ticklerId='$noteId'";
        $result = mysql_query($query) or die ("Error in query");
        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=tickler.php" />';
	/* Turn debug off
        echo "<p>Number of Records Deleted: ";
        echo mysql_affected_rows();
	 */
	}

else {
        $query = "UPDATE tickler SET date = '$date', note = '$note', title = '$title' WHERE ticklerId = '$noteId'";
        $result = mysql_query($query) or die ("Error in query");

        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=tickler.php" />';
	}

mysql_close($connection);
include_once('footer.php');
?>
