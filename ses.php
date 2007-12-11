<?php
session_start();  
if(isset($_SESSION['views']))
    $_SESSION['views']++;
else{
    $_SESSION['views'] = 1;
    $_SESSION['categoryId'] = 0;
    $_SESSION['contextId'] = 0;
    $_SESSION['message'] = array();
    $_SESSION['version'] = '';
    foreach ($_COOKIE as $key=>$val) $_SESSION[$key]=$val; // retrieve cookie values
}

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
