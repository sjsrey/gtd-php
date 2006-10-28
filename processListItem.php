<?php
//INCLUDES
	include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
	$values['listId']=(int) $_POST['listId'];
	$values['item']=mysql_real_escape_string($_POST['item']);
	$values['notes']=mysql_real_escape_string($_POST['notes']);


    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'"';

query("newlistitem",$config,$values);

	mysql_close($connection);
	include_once('footer.php');

?>
