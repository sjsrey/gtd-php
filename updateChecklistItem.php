<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values=array();
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
    }
else {
query("updatechecklistitem",$config,$values);
echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$values['checklistId'].'">';
    }

include_once('footer.php');
?>


