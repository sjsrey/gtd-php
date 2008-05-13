<?php
include_once 'config.inc.php';
$id=$_REQUEST['addonid'];
$addon=$_SESSION['config']['addons'][$id];
$addon['id']=$id;
$addon['urlprefix']="{$_SERVER['PHP_SELF']}?addonid=$id&amp;url=".dirname($addon['link']).'/';
$url= (empty($_REQUEST['url'])) ? $addon['link'] : $_REQUEST['url'];
include_once $url;
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
