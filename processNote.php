<?php
//INCLUDES
include_once('headerDB.inc.php');

//RETRIEVE FORM VARIABLES
$values=array();
$values['date'] = $_POST['date'];
$values['title'] = $_POST['title'];
$values['note'] = $_POST['note'];
$values['repeat'] = (int) $_POST['repeat'];
$values['suppressUntil'] = (int) $_POST['suppressUntil'];
$referrer = $_POST['referrer']{0};

//Insert note
$result = query("newnote",$config,$values);

if ($referrer=="s")
    nextScreen('index.php');
else
    nextScreen("listItems.php?type=a&tickler=true");

include_once('footer.php');
?>
