<?php
//INCLUDES
include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
$values['newitem']=mysql_real_escape_string($_POST['newitem']);
$values['newnotes']=mysql_real_escape_string($_POST['newnotes']);
$values['checklistId'] = (int) $_POST['checklistId'];
$values['newchecked'] = $_POST['completed']{0};
if($values['newchecked']!="y") $values['newchecked']='n';
$values['checklistItemId'] = (int) $_GET['checklistItemId'];
$values['delete']=$_POST['delete']{0};

//SQL CODE AREA
if($values['delete']=="y") {
        query("deletechecklistitem",$config,$values);
        echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$values['checklistId'].'">';
//        echo "<p>Number of Records Deleted: ";
//        echo mysql_affected_rows();
	}
else {
    query("updatechecklistitem",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$values['checklistId'].'">';
//	echo "Number of Records Updated: ";
//	echo mysql_affected_rows();
	}

mysql_close($connection);
include_once('footer.php');
?>


