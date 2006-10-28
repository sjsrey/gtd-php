<?php
//INCLUDES
include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
$values['projectId'] = (int) $_POST['project'];
$values['goal'] = mysql_real_escape_string($_POST['goal']);
$values['description'] = mysql_real_escape_string($_POST['newdescription']);
$values['created'] = $_POST['date'];
$values['deadline'] = $_POST['deadline'];
$values['completed'] = $_POST['completed'];
$values['type'] = $_POST['type']{0};
$values['gid'] = (int) $_GET['goalId'];

//crude error checking.  Need to do better-- error-handling function and within javascript/ajax,etc...so more responsive.
if ($values['goal']=="") {
    echo '<META HTTP-EQUIV="Refresh" CONTENT="3; url=editGoal.php?goalId='.$values['gid'].'">';
    echo 'Error. Please enter goal name.';
    }

else {
    $result=query("updategoal",$config,$values);
    echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listGoals.php">';
    echo '<p>Goal updated at ';
    echo date('H:i, jS F');
    echo '</p>';
    }

mysql_close($connection);

include_once('footer.php');
?>
