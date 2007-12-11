<?php
//INCLUDES
include_once('headerDB.inc.php');

//FORM DATA COLLECTION AND PARSING
$values=array();
$values['title'] = $_POST['title'];
$values['note'] = $_POST['note'];
$values['date'] = $_POST['date'];
$values['repeat'] = (int) $_POST['repeat'];
$values['suppressUntil'] = $_POST['suppressUntil'];
$values['delete'] = $_POST['delete']{0};
$values['noteId'] = (int) $_GET['noteId'];
$acknowledge = $_POST['acknowledge']{0};
$referrer = $_POST['referrer']{0};

if($values['delete']=="y")
    $q='deletenote';
elseif ($acknowledge=="y")
    $q='repeatnote';
else
    $q='updatenote';

$result=query($q,$config,$values);

if ($referrer=="s")
    nextScreen('index.php');
else
    nextScreen("listItems.php?type=a&tickler=true");

include_once('footer.php');
