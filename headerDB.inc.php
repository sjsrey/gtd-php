<?php
require_once("ses.php");
require_once("config.php");
if ($config['debug'] & _GTD_NOTICE)
		error_reporting(E_ALL);
    else
		error_reporting(E_ALL ^ E_NOTICE);
//CONNECT TO DATABASE: this will need modification to connect to other dtabases (use SWITCH)
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect to MySQL server: check your host, user and pass settings in config.php!");
mysql_select_db($config['db']) or die ("Unable to select database '{$config['db']}' - check your db setting in config.php!");

require_once("gtdfuncs.php");
require_once("query.inc.php");
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
