<?php
include_once('header.php');

//GET URL AND FORM DATA
$values=array();
$values['tcId'] = (int) $_GET['tcId'];
$values['name']=$_POST['timeframe'];
$values['description']=$_POST['description'];
$values['type']=$_POST['type'];
$values['delete']=$_POST['delete']{0};
$values['ntcId']=(int) $_POST['ntcId'];

if ($values['delete']=="y") {
        query("reassigntimecontext",$config,$values);
        query("deletetimecontext",$config,$values);
        }

else query("updatetimecontext",$config,$values);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=reportContext.php" />';

include_once('footer.php');
?>
