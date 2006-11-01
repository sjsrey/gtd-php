<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values['checklistId'] = (int) $_GET['checklistId'];
$values['newchecklistTitle']=mysql_real_escape_string($_POST['newchecklistTitle']);
$values['newcategoryId']=(int) $_POST['newcategoryId'];
$values['newdescription']=mysql_real_escape_string($_POST['newdescription']);
$values['delete']=$_POST['delete']{0};

if($values['delete']=="y") {

    query("deletechecklist",$config,$values);
    //echo "<p>Number of checklists deleted: ";
    //echo mysql_affected_rows();

    query("removechecklistitems",$config,$values);
    //echo "<p>Number of checklist items deleted: ";
    //echo mysql_affected_rows();

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listChecklist.php?checklistId='.$values['checklistId'].'">';
    }

else {

    query("updatechecklist",$config,$values);
    //echo "<p>Number of Records Updated: ";
    //echo mysql_affected_rows();

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listChecklist.php?checklistId='.$values['checklistId'].'">';
    }

include_once('footer.php');
?>
