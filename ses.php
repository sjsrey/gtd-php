<?php
session_start();  
if(isset($_SESSION['views']))
    $_SESSION['views'] = $_SESSION['views']+ 1;
else{
    $_SESSION['views'] = 1;
    $_SESSION['categoryId'] = 0;
    $_SESSION['contextId'] = 0;
    $_SESSION['theme'] = 'default';
 }

//echo "views = ". $_SESSION['views']; 
//echo "<br>";
//echo "contextId = ". $_SESSION['contextId']; 
//echo "<br>";
//echo "categoryId = ". $_SESSION['categoryId']; 
?>

