<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values = array();
$values['checklistId']=(int) $_POST['checklistId'];
$values['item']=$_POST['item'];
$values['notes']=$_POST['notes'];

$result = query ("newchecklistitem",$config,$values);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$values['checklistId'].'"';

include_once('footer.php');
?>
