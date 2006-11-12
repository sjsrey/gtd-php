<?php
//INCLUDES
include_once('header.php');

//RETRIVE FORM VARIABLES
$values=array();
$values['date'] = $_POST['date'];
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['note'] = mysql_real_escape_string($_POST['note']);

//CRUDE error checking
if ($values['date']=="") die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php"><p>No date choosen. Note NOT added.</p>');
if ($values['title']=="") die ('<META HTTP-EQUIV="Refresh" CONTENT="3;url=note.php"><p>No title. Note NOT added.</p>');

//Insert note
query("newnote",$config,$values);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=tickler.php">';

include_once('footer.php');
?>
