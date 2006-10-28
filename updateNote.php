<?php
//INCLUDES
include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//FORM DATA COLLECTION AND PARSING
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['note'] = mysql_real_escape_string($_POST['note']);
$values['date'] = $_POST['date'];
$values['delete'] = $_POST['delete']{0};
$values['noteId'] = (int) $_GET['noteId'];

//SQL CODE AREA
if($values['delete']=="y"){
    query("deletenote",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=tickler.php" />';
//    echo "<p>Number of Records Deleted: ";
//    echo mysql_affected_rows();

} else {
    query("updatenote",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=tickler.php" />';
    }

mysql_close($connection);
include_once('footer.php');
?>
