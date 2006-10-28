
<?php
//INCLUDES
include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE FORM URL VARIABLES
$values['listId'] = (int) $_GET['listId'];
$values['newlistTitle']=mysql_real_escape_string($_POST['newlistTitle']);
$values['newcategoryId']=(int) $_POST['newcategoryId'];
$values['newdescription']=mysql_real_escape_string($_POST['newdescription']);
$values['delete']=$_POST['delete']{0};

//SQL CODE AREA
if($values['delete']=="y") {
    query("deletelist",$config,$values);
        //echo "<p>Number of lists deleted: ";
        //echo mysql_affected_rows();
    query("removelistitems",$config,$values);
        //echo "<p>Number of list items deleted: ";
        //echo mysql_affected_rows();
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'">';
	}

else {
    query("updatelist",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'">';
	}

mysql_close($connection);
include_once('footer.php');
?>
