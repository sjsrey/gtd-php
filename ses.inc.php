<?php
session_start();
if(isset($_SESSION['views']))
    $_SESSION['views']++;
else{
    $_SESSION['categoryId'] = $_SESSION['contextId'] = 0;
    $_SESSION['debug']=$_SESSION['sort']=$_SESSION['keys']=$_SESSION['config']=
        $_SESSION['hierarchy']=$_SESSION['message'] = array();
    $_SESSION['theme'] = 'default';
    $_SESSION['version'] = '';
    $_SESSION['views'] = 1;
    foreach ($_COOKIE as $key=>$val) if (!empty($key)) $_SESSION[$key]=$val; // retrieve cookie values
}

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
