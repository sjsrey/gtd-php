<?php
include_once('header.php');

//GET URL AND FORM DATA
$values=array();
$values['tcId'] = (int) $_GET['tcId'];
$values['name']=mysql_real_escape_string($_POST['timeframe']);
$values['description']=mysql_real_escape_string($_POST['description']);
$values['delete']=$_POST['delete']{0};
$values['ntcId']=(int) $_POST['ntcId'];

if ($values['delete']=="y") {
        query("reassigntimecontext",$config,$values);
        query("deletetimecontext",$config,$values);
        }

else query("updatetimecontext",$config,$values);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=reportContext.php" />';

mysql_close($connection);
include_once('footer.php');
?>
