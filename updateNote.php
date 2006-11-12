<?php
//INCLUDES
include_once('header.php');

//FORM DATA COLLECTION AND PARSING
$values=array();
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['note'] = mysql_real_escape_string($_POST['note']);
$values['date'] = $_POST['date'];
$values['delete'] = $_POST['delete']{0};
$values['noteId'] = (int) $_GET['noteId'];

//SQL CODE AREA
if($values['delete']=="y"){
    query("deletenote",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=tickler.php" />';

} else {
    query("updatenote",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=tickler.php" />';
    }

mysql_close($connection);
include_once('footer.php');
?>
