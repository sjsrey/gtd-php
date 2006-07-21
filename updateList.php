
<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//RETRIEVE FORM URL VARIABLES
$listId = (int) $_GET['listId'];
$newlistTitle=mysql_real_escape_string($_POST['newlistTitle']);
$newcategoryId=(int) $_POST['newcategoryId'];
$newdescription=mysql_real_escape_string($_POST['newdescription']);
$delete=$_POST['delete']{0};
//$delete=$_GET['delete'];

//SQL CODE AREA
if($delete=="y") {

        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listList.php"';
        $query= "delete from list where listId='$listId'";
        $result = mysql_query($query) or die ("Error in query");
        //echo "<p>Number of lists deleted: ";
        //echo mysql_affected_rows();

        $query= "delete from listItems where listId='$listId'";
        $result = mysql_query($query) or die ("Error in query");
        //echo "<p>Number of list items deleted: ";
        //echo mysql_affected_rows();
	}

else {

        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listList.php"';
	$query = "update list
	set title = '$newlistTitle', description = '$newdescription', categoryId = '$newcategoryId' 
	where listId ='$listId'";
	$result = mysql_query($query) or die ("Error in query");
	//echo "<p>Number of Records Updated: ";
	//echo mysql_affected_rows();
	}

mysql_close($connection);
include_once('footer.php');
?>
