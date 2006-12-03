<?php
//INCLUDES
include_once('header.php');

//FORM DATA COLLECTION AND PARSING
$values=array();
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['note'] = mysql_real_escape_string($_POST['note']);
$values['date'] = $_POST['date'];
$values['repeat'] = (int) $_POST['repeat'];
$values['suppressUntil'] = mysql_real_escape_string($_POST['suppressUntil']);
$values['delete'] = $_POST['delete']{0};
$values['noteId'] = (int) $_GET['noteId'];
$acknowledge = $_POST['acknowledge']{0};
$referrer = $_POST['referrer']{0};
$type = $_POST['type']{0};

//CRUDE error checking
if ($values['date']=="") die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php?type='.$type.'&referrer='.$referrer.'"><p>No date choosen. Note NOT added.</p>');
if ($values['title']=="") die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php?type='.$type.'&referrer='.$referrer.'"><p>No title. Note NOT added.</p>');
if (!ereg('[0-9\d]{4,4}[-][0-9\d]{2,2}[-][0-9\d]{2,2}',$values['date'])) die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php?type='.$type.'&referrer='.$referrer.'"><p>Date incorrectly formatted. Note NOT added.</p>');

//SQL CODE AREA
if($values['delete']=="y") $result=query("deletenote",$config,$values);
else if ($acknowledge=="y") $result=query("repeatnote",$config,$values);
else $result=query("updatenote",$config,$values);

if ($referrer=="s") echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=summaryAlone.php" />';
else echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listItems.php?type='.$type.'" />';

include_once('footer.php');
?>
