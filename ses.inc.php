<?php
session_start();
if(isset($_SESSION['views']))
    $_SESSION['views']++;
else{
    $_SESSION['categoryId'] = $_SESSION['contextId'] = 0;
    $_SESSION['debug']=$_SESSION['sort']=$_SESSION['keys']=$_SESSION['config']=
        $_SESSION['hierarchy']=$_SESSION['message']=$_SESSION['addons']=array();
    $_SESSION['theme'] = 'default';
    $_SESSION['version'] = '';
    $_SESSION['views'] = 1;
    $_SESSION['uid']   = 0;
    $_SESSION['addonsdir']='addons/';
    foreach (array('theme','useLiveEnhancements') as $key)
        if (array_key_exists($key,$_COOKIE))
            $_SESSION[$key]=$_COOKIE[$key]; // retrieve cookie values
}
global $pagename;
$thisurl=parse_url($_SERVER['PHP_SELF']);
$pagename=basename($thisurl['path'],".php");
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
