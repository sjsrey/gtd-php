<?php
//INCLUDES
	include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
	$values['listId']=(int) $_POST['listId'];
	$values['item']=mysql_real_escape_string($_POST['item']);
	$values['notes']=mysql_real_escape_string($_POST['notes']);


    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'"';

query("newlistitem",$config,$values);

	mysql_close($connection);
	include_once('footer.php');

?>
