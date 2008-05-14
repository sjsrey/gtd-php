<?php
include_once 'ses.inc.php';
$addon=$_SESSION["addons-{$_REQUEST['addonid']}"];
$addon['id']=$_REQUEST['addonid'];
$addon['urlprefix']="{$_SERVER['PHP_SELF']}?addonid={$addon['id']}&amp;url=";
include_once "./addons/{$addon['id']}/{$_REQUEST['url']}";
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
