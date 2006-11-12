<?php
//INCLUDES
include_once('header.php');

//FORM DATA COLLECTION AND PARSING
$values=array();
$values['projectId'] = (int) $_GET['projectId'];
$values['name']=mysql_real_escape_string($_POST['name']);
$values['description']=mysql_real_escape_string($_POST['description']);
$values['desiredOutcome']=mysql_real_escape_string($_POST['outcome']);
$values['dateCreated']=$_POST['dateCreated'];
$values['dateCompleted']=$_POST['dateCompleted'];
$values['delete']=$_POST['delete']{0};
$values['categoryId']=(int) $_POST['categoryId'];
if ($_POST['isSomeday']{0}=='y') $values['isSomeday']='y';
else $values['isSomeday']='n';
$values['repeat'] = (int) $_POST['repeat'];
$values['deadline'] = $_POST['deadline'];
$values['suppress'] = $_POST['suppress']{0};
if ($values['suppress']!='y') $values['suppress']='n';
$values['suppressUntil'] = (int) $_POST['suppressUntil'];

//SQL CODE AREA
if($values['delete']=="y"){

    query("deleteproject",$config,$values);
    query("deleteprojectattributes",$config,$values);
    query("deleteprojectstatus",$config,$values);
    query("removeitems",$config,$values);
    query("removenextactions",$config,$values);

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listProjects.php" />';
//        echo "<p>Number of Items also deleted: ";
//        echo mysql_affected_rows();
    }

else {
    query("updateproject",$config,$values);
    query("updateprojectattributes",$config,$values);
    query("updateprojectstatus",$config,$values);

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=projectReport.php?projectId='.$values['projectId'].'" />';
    }

mysql_close($connection);
include_once('footer.php');
?>
