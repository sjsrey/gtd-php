<?php
include_once 'ses.inc.php';
$id                 = $_REQUEST['addonid'];
$addon              = (array_key_exists("addons-$id",$_SESSION)) ? $_SESSION["addons-$id"] : array();
$addon['id']        = $id;
$addon['dir']       = $_SESSION['addonsdir'].$id.'/';
$addon['urlprefix'] = "{$_SERVER['PHP_SELF']}?addonid=$id&amp;url=";
include_once "{$addon['dir']}{$_REQUEST['url']}";
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
