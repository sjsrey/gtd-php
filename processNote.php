<?php
//INCLUDES
include_once('header.php');

//RETRIVE FORM VARIABLES
$values=array();
$values['date'] = $_POST['date'];
$values['title'] = $_POST['title']);
$values['note'] = $_POST['note'];
$values['repeat'] = (int) $_POST['repeat'];
$values['suppressUntil'] = $_POST['suppressUntil'];
$referrer = $_POST['referrer']{0};
$type = $_POST['type']{0};

//CRUDE error checking
if ($values['date']=="") die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php?type='.$type.'&referrer='.$referrer.'"><p>No date choosen. Note NOT added.</p>');
if ($values['title']=="") die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php?type='.$type.'&referrer='.$referrer.'"><p>No title. Note NOT added.</p>');

//Insert note
$result = query("newnote",$config,$values);

if ($referrer=="s") echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=summaryAlone.php" />';
else echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listItems.php?type='.$type.'" />';

include_once('footer.php');
?>
