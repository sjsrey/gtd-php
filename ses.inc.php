<?php
session_start();
if(isset($_SESSION['views']))
    $_SESSION['views']++;
else{
    $_SESSION['categoryId'] = $_SESSION['contextId'] = 0;
    $_SESSION['debug']=$_SESSION['sort']=$_SESSION['keys']=$_SESSION['config']=
        $_SESSION['hierarchy']=$_SESSION['message']=$_SESSION['addons']=array();
    $_SESSION['version'] = '';
    $_SESSION['views'] = 1;
    $_SESSION['uid']   = 0;
    $_SESSION['addonsdir']='addons/';
}
ignore_user_abort(false);
global $pagename;

$thisurl=parse_url($_SERVER[
    (empty($_SERVER['SCRIPT_NAME']))
        ? ( (empty($_SERVER['PHP_SELF']))
            ? 'REQUEST_URI'
            : 'PHP_SELF' )
        : 'SCRIPT_NAME'
    ]);
$pagename=basename($thisurl['path'],".php");
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
