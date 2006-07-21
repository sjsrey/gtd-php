
<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
$checklistId = (int) $_GET['checklistId'];
$newchecklistTitle=mysql_real_escape_string($_POST['newchecklistTitle']);
$newcategoryId=(int) $_POST['newcategoryId'];
$newdescription=mysql_real_escape_string($_POST['newdescription']);
$delete=$_POST['delete']{0};

if($delete=="y") {

        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listChecklist.php"';
        $query= "delete from checklist where checklistId='$checklistId'";
        $result = mysql_query($query) or die ("Error in query");
        //echo "<p>Number of checklists deleted: ";
        //echo mysql_affected_rows();

        $query= "delete from checklistItems where checklistId='$checklistId'";
        $result = mysql_query($query) or die ("Error in query");
        //echo "<p>Number of checklist items deleted: ";
        //echo mysql_affected_rows();
	}

else {
        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listChecklist.php"';
echo $checklistId;
	$query = "update checklist
	set title = '$newchecklistTitle', description = '$newdescription', categoryId = '$newcategoryId' 
	where checklistId ='$checklistId'";
	$result = mysql_query($query) or die ("Error in query");
	//echo $query;
	//echo "<p>Number of Records Updated: ";
	//echo mysql_affected_rows();
	}

mysql_close($connection);
include_once('footer.php');
?>
