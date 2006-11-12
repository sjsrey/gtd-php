<?php
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values=array();
$values['listItemId'] = (int) $_POST['listItemId'];
$values['listId'] = (int) $_GET['listId'];
$completedLis = $_POST['completedLis'];

if(isset($completedLis)){
	$values['date']=date('Y-m-d');
        echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'">';
        foreach ($completedLis as $values['completedLi']) {
            $result = query("completelistitem",$config,$values);
            }
}

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listReport.php?listId='.$values['listId'].'">';
include_once('footer.php');
?>

