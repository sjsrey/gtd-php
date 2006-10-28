
<?php
include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
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
mysql_close($connection);
include_once('footer.php');
?>

