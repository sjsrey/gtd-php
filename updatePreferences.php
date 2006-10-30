<?php
//INCLUDES
include_once('header.php');

//Theme
$theme=$_POST['theme'];
$_SESSION['theme']=$theme;


echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=index.php"';
include_once('footer.php');
?>
