<?php
include_once('header.php');
include_once('config.php');

$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//GET URL AND FORM DATA
$contextId = (int) $_GET['contextId'];
$name=mysql_real_escape_string($_POST['name']);
$description=mysql_real_escape_string($_POST['description']);
$delete=$_POST['delete']{0};
$newContextId=(int) $_POST['newContextId'];

if ($delete=="y") {
	$reassignquery= "update itemattributes set contextId='$newContextId' where contextId='$contextId'";
	$reassignresult = mysql_query($reassignquery) or die ("Error in query: $reassignquery. ".mysql_error());
	$deletequery = "delete from context where contextId='$contextId'";
	$deleteresult = mysql_query($deletequery) or die ("Error in query");
	}

else {
	$query = "update context set name ='$name', description='$description' where contextId ='$contextId'";
	$result = mysql_query($query) or die ("Error in query");
	}

echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=reportContext.php"';
echo "Number of Records Updated: ";
echo mysql_affected_rows();

mysql_close($connection);
include_once('footer.php');
?>
