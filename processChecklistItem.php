<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values['checklistId']=(int) $_POST['checklistId'];
$values['item']=mysql_real_escape_string($_POST['item']);
$values['notes']=mysql_real_escape_string($_POST['notes']);

$result = query ("newchecklistitem",$config,$values);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$values['checklistId'].'"';

include_once('footer.php');
?>
