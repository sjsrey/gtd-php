<?php
//INCLUDES
include_once('header.php');

//FORM DATA COLLECTION AND PARSING
$values=array();
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['description'] = mysql_real_escape_string($_POST['description']);
$values['projectId'] = (int) $_POST['projectId'];
$values['contextId'] = (int) $_POST['contextId'];
$values['completed'] = $_POST['completed'];
$values['timeframeId'] = (int) $_POST['timeframeId'];
$values['dateCompleted'] = $_POST['dateCompleted'];
$values['delete'] = $_POST['delete']{0};
$values['itemId'] = (int) $_GET['itemId'];
$values['repeat'] = (int) $_POST['repeat'];
$values['deadline'] = $_POST['deadline'];
$values['suppress'] = $_POST['suppress']{0};
$values['suppressUntil'] = (int) $_POST['suppressUntil'];
$values['type']=$_POST['type']{0};
$values['nextAction']=$_POST['nextAction']{0};

if ($values['suppress']!="y") $values['suppress']="n";

//SQL CODE AREA
if($values['delete']=="y"){

    query("deleteitemstatus",$config,$values);
    query("deleteitemattributes",$config,$values);
    query("deleteitem",$config,$values);

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=projectReport.php?projectId='.$values['projectId'].'" />';
    if ($values['nextAction']=='y') query("deletenextaction",$config,$values);
    }

else {
    query("updateitemstatus",$config,$values);
    query("updateitemattributes",$config,$values);
    query("updateitem",$config,$values);

    if ($values['nextAction']=='y' && ($values['dateCompleted']==NULL || $values['dateCompleted']=="0000-00-00")) query("updatenextaction",$config,$values);
    else query("deletenextaction",$config,$values);

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=projectReport.php?projectId='.$values['projectId'].'" />';
    }

include_once('footer.php');
?>
