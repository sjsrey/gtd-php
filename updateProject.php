<?php

///////////////////////////////////////////////////
//File: UpdateProject.php                        //
//Description: Edits project entry in database   //
//Accessed From: project.php                     //
//Links to: ProjectReport.php for updated project//
///////////////////////////////////////////////////

//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//FORM DATA COLLECTION AND PARSING
$projectId = (int) $_GET['projectId'];
$name=mysql_real_escape_string($_POST['name']);
$description=mysql_real_escape_string($_POST['description']);
$desiredOutcome=mysql_real_escape_string($_POST['outcome']);
$dateCreated=$_POST['dateCreated'];
$dateCompleted=$_POST['dateCompleted'];
$delete=$_POST['delete']{0};
$categoryId=(int) $_POST['categoryId'];
if ($_POST['isSomeday']{0}=='y') $isSomeday='y';
else $isSomeday='n';
$repeat = (int) $_POST['repeat'];
$deadline = $_POST['deadline'];
$suppress = $_POST['suppress']{0};
if ($suppress!='y') $suppress='n';
$suppressUntil = (int) $_POST['suppressUntil'];

//SQL CODE AREA
if($delete=="y"){
        $query= "delete from projects where projectId='$projectId'";
        $result = mysql_query($query) or die ("Error in query");

        $query= "delete from projectattributes where projectId='$projectId'";
        $result = mysql_query($query) or die ("Error in query");

        $query= "delete from projectstatus where projectId='$projectId'";
        $result = mysql_query($query) or die ("Error in query");

        $query= "DELETE itemattributes 
		FROM itemattributes, items, itemstatus 
		WHERE items.itemId=itemattributes.itemId AND itemstatus.itemId=itemattributes.itemId 
		AND itemattributes.projectId='$projectId'";
        $result = mysql_query($query) or die ("Error in query"); 

        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listProjects.php" />';
        echo "<p>Number of Items also deleted: ";
        echo mysql_affected_rows();
        }

else {
        $query = "UPDATE projects
            SET description = '$description', name = '$name', desiredOutcome = '$desiredOutcome'
            WHERE projectId ='$projectId'";
        $result = mysql_query($query) or die ("Error in query");

        $query = "UPDATE projectattributes
                SET categoryId = '$categoryId', isSomeday = '$isSomeday', deadline ='$deadline', `repeat` = '$repeat', suppress='$suppress', suppressUntil='$suppressUntil'
                WHERE projectId ='$projectId'";
        $result = mysql_query($query) or die ("Error in query");

        $query = "UPDATE projectstatus
                SET dateCompleted='$dateCompleted'
                WHERE projectId ='$projectId'";
        $result = mysql_query($query) or die ("Error in query");

	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=projectReport.php?projectId='.$projectId.'" />';
	}

mysql_close($connection);
include_once('footer.php');
?>
