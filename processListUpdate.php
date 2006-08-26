
<?php
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
$date=$_POST['date'];
$listItemId = (int) $_POST['listItemId'];
$listId = (int) $_GET['listId'];
$completedLis = $_POST['completedLis'];

if(isset($completedLis)){
	$date=date('Y-m-d');
        echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listReport.php?listId='.$listId.'&listTitle='.$listTitle.'">';
        foreach ($completedLis as $completedLi){
        //        echo "Updating List Item: ";
        //        echo $completedLi.'<br>';
                $query= "update listItems set dateCompleted='$date' where listItemId='$completedLi'";
	//	echo $query;
                $result = mysql_query($query) or die ("Error in query");
        }

        $query = "select listId from listItems where listItemId='$completedLi'";
        $result=mysql_query($query);
        $row=mysql_fetch_row($result);
        $listId=$row[0];
}
echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listReport.php?listId='.$listId.'&listTitle='.$listTitle.'">';
mysql_close($connection);
include_once('footer.php');
?>

