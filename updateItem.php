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
$description = mysql_real_escape_string($_POST['description']);
$projectId = (int) $_POST['projectId'];
$contextId = (int) $_POST['contextId'];
$completed = $_POST['completed'];
$timeframeId = (int) $_POST['timeframeId'];
$dateCompleted = $_POST['dateCompleted'];
$delete = $_POST['delete']{0};
$itemId = (int) $_GET['itemId'];
$repeat = (int) $_POST['repeat'];
$deadline = $_POST['deadline'];
$suppress = $_POST['suppress']{0};
$suppressUntil = (int) $_POST['suppressUntil'];
$type=$_POST['type']{0};
$nextAction=$_POST['nextAction']{0};

if ($suppress!="y") $suppress="n";

//SQL CODE AREA
if($delete=="y"){
        $query= "delete from items where itemId='$itemId'";
        $result = mysql_query($query) or die ("Error in query");

        $query= "delete from itemattributes where itemId='$itemId'";
        $result = mysql_query($query) or die ("Error in query");

        $query= "delete from itemstatus where itemId='$itemId'";
        $result = mysql_query($query) or die ("Error in query");

        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=projectReport.php?projectId='.$projectId.'" />';
        echo "<p>Number of Records Deleted: ";
        echo mysql_affected_rows();

	if(nextAction=='y') {
	        $query= "delete from nextactions where nextAction='$itemId'";
       		$result = mysql_query($query) or die ("Error in query");
		}
	}

else {
        $projectTitle=getProjectTitle($projectId);

        $query = "UPDATE items
            SET description = '$description', title = '$title'
            WHERE itemId = '$itemId'";
        $result = mysql_query($query) or die ("Error in query");

	$query = "UPDATE itemattributes
		SET type = '$type', projectId = '$projectId', contextId = '$contextId', timeframeId = '$timeframeId', 
		deadline ='$deadline', `repeat` = '$repeat', suppress='$suppress', suppressUntil='$suppressUntil' 
		WHERE itemId = '$itemId'";
        $result = mysql_query($query) or die ("Error in query");

	$query = "UPDATE itemstatus
		SET dateCompleted = '$dateCompleted'
		WHERE itemId = '$itemId'";
        $result = mysql_query($query) or die ("Error in query");

	if($nextAction=='y') {
		$query = "INSERT INTO nextactions (projectId,nextaction) VALUES ('$projectId','$itemId') 
			ON DUPLICATE KEY UPDATE nextaction='$itemId'";
       		$result = mysql_query($query) or die ("Error in query");
		}

	else {
	        $query= "DELETE FROM nextactions WHERE nextAction='$itemId'";
       		$result = mysql_query($query) or die ("Error in query");
		}


        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=projectReport.php?projectId='.$projectId.'" />';
	}

mysql_close($connection);
include_once('footer.php');
?>
