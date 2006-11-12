<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values=array();
$values['listId']=(int) $_POST['listId'];
$values['item']=mysql_real_escape_string($_POST['item']);
$values['notes']=mysql_real_escape_string($_POST['notes']);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'"';

$result = query("newlistitem",$config,$values);

include_once('footer.php');

?>
