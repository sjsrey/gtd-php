<?php
include_once 'ses.inc.php';
$id                 = $_REQUEST['addonid'];
$addon              = (is_array($_SESSION["addons-$id"])) ? $_SESSION["addons-$id"] : array();
$addon['id']        = $id;
$addon['dir']       = "./addons/$id/";
$addon['urlprefix'] = "{$_SERVER['PHP_SELF']}?addonid=$id&amp;url=";
include_once "{$addon['dir']}{$_REQUEST['url']}";
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
