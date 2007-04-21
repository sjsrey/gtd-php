<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values=array();
$values['listId']=(int) $_POST['listId'];
$values['item']=mysql_real_escape_string($_POST['item']);
$values['notes']=mysql_real_escape_string($_POST['notes']);

$nextURL='listReport.php?listId='.$values['listId'];
if ($config['debug']==='false') {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=',$nextURL,'" />';
} else {
	echo '<p>Next page is <a href="',$nextURL,'">&lt;',htmlspecialchars($nextURL),'&gt;</a> (would be auto-refresh in non-debug mode)</p>';
}

$result = query("newlistitem",$config,$values);

include_once('footer.php');

?>
