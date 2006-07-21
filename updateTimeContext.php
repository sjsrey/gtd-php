<?php
include_once('header.php');
include_once('config.php');

$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//GET URL AND FORM DATA
$tcId = (int) $_GET['tcId'];
$name=mysql_real_escape_string($_POST['timeframe']);
$description=mysql_real_escape_string($_POST['description']);
$delete=$_POST['delete']{0};
$ntcId=(int) $_POST['ntcId'];

if ($delete=="y") {
	$reassignquery= "update itemattributes set timeframeId='$ntcId' where timeframeId='$tcId'";
	$reassignresult = mysql_query($reassignquery) or die ("Error in query: $reassignquery. ".mysql_error());
	$deletequery = "delete from timeitems where timeframeId='$tcId'";
	$deleteresult = mysql_query($deletequery) or die ("Error in query: $deletequery. ".mysql_error());
	}

else {
	$query = "update timeitems set timeframe ='$name', description='$description' where timeframeId ='$tcId'";
	$result = mysql_query($query) or die ("Error in query: $query. ".mysql_error());
	}

echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=reportContext.php"';
echo "Number of Records Updated: ";
echo mysql_affected_rows();

mysql_close($connection);
include_once('footer.php');
?>
