<?php
include_once('header.php');
include_once('config.php');

$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//GET URL AND FORM DATA
$categoryId = (int) $_GET['categoryId'];
$category=mysql_real_escape_string($_POST['category']);
$description=mysql_real_escape_string($_POST['description']);
$delete=$_POST['delete']{0};
$newCategoryId=(int) $_POST['newCategoryId'];

if ($delete=="y") {
	$reassignquery= "update projectattributes set categoryId='$newCategoryId' where categoryId='$categoryId'";
	$reassignresult = mysql_query($reassignquery) or die ("Error in query");
	$deletequery = "delete from categories where categoryId='$categoryId'";
	$deleteresult = mysql_query($deletequery) or die ("Error in query");
	}

else {
	$query = "update categories set category ='$category', description='$description' where categoryId ='$categoryId'";
	$result = mysql_query($query) or die ("Error in query");
	}

echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listProjects.php"';
echo "Number of Records Updated: ";
echo mysql_affected_rows();

mysql_close($connection);
include_once('footer.php');
?>
