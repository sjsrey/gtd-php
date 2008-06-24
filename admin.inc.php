<?php
/*
   ======================================================================================
   Contents: Functions which are used by both the installer and the main package,
        which an administrator may wish to withhold from non-power users.
   ======================================================================================
*/
/*
   ======================================================================================
   Remove an installation
*/
function deleteInstall($ver,$prefix) {
    global $versions,$tablesByVersion;
    require_once 'headerDB.inc.php';
	echo "<h1>GTD-PHP - Deleting an installation</h1>\n<div id='main'>\n";
	if (isset($_POST['tablesToDelete'])) {
        echo "<p>Deleting temporary installation tables</p>\n<ol>\n";
        $tablelist=explode(' ',$_POST['tablesToDelete']);
        $prefix='';
	} else {
        echo "<p>Deleting installation version '$ver' with prefix '$prefix'</p>\n<ol>\n";
        $tablelist=$tablesByVersion[$versions[$ver]['tables']];
    }
    foreach ($tablelist as $thistable) {
        echo "<li>Deleting $prefix$thistable</li>\n";
        drop_table($prefix.$thistable);
    }
    echo "</ol><p>Finished - <a href='install.php'>return to install screen</a></p>\n";
}
// --------------------------------------------------------------------------------
function makeDeleteButton($prefix,$ver) {
    global $versions;
    $out="<input class='warning' type='submit' value='Delete=$prefix=$ver' name='Delete_$prefix' />";
    return $out;
}
// --------------------------------------------------------------------------------
function offerToDeleteOne($prefix,$ver) {
    echo showDeleteWarning(true)
        ,"<form action='install.php' method='post'><div>\n"
        ,"You can delete the current GTD-PHP data set here:"
        ,makeDeleteButton($prefix,$ver)
        ,"</div></form>\n";
}
// --------------------------------------------------------------------------------
function showDeleteWarning($noPrint=false) {
    static $alreadyShown=false;
    if ($alreadyShown)
        return '';
    else
        $alreadyShown=true;

    if (_ALLOWUPGRADEINPLACE || _ALLOWUNINSTALL) {
        $outStr="<p class='warning'>Warning: ";
        if (_ALLOWUNINSTALL) {
            $outStr.=' deletions ';
            if (_ALLOWUPGRADEINPLACE) $outStr.=' and ';
        }
        if (_ALLOWUPGRADEINPLACE) $outStr.=' over-writing ';
        $outStr.=" cannot be undone.<br /> \n"
            ." Make sure that any information you are about to remove is backed up "
            ." somewhere, and that you are sure it belongs to GTD-PHP and to you.</p>\n";
    } else $outStr='';
    if (!$noPrint) echo $outStr;
    return $outStr;
}
// --------------------------------------------------------------------------------
function getConfirmation($action,$prefix) {
    echo "<h1>GTD-PHP Installation</h1>\n"
        ,showDeleteWarning(true)
        ,"<div id='main'><form action='install.php' method='post'><div>\n"
        ,"<p class='warning'>\n Are you sure you wish to $action the installation "
        ," with prefix '$prefix'? This action is irreversible.</p>\n";
    foreach ($_POST as $var=>$val)
        echo "<input type='hidden' name='$var' value='$val' />\n";
    echo "<input type='submit' name='$action' value='Continue' />\n"
        ,"<input type='submit' name='cancel' value='Cancel' />\n"
        ,"</div></form>\n";
}
// --------------------------------------------------------------------------------
function drop_table($name){
	global $rollback;
	$q = "drop table if exists `$name`";
    rawQuery($q);
    unset($rollback[$name]);
}
/*  end of functions to assist in removing an installation
   ======================================================================================
*/
function checkPrefix($prefix) {
	// check that the proposed prefix is valid for a gtd-php installation.
	if (_DEBUG) echo '<p class="debug">Validating prefix '."'{$prefix}'</p>\n";
	$prefixOK=preg_match("/^[-_a-zA-Z0-9]*$/",$prefix);
	if (!$prefixOK)
		echo '<p class="error">Prefix "',$prefix, '" is invalid - change config.inc.php.'
			 ," The only valid characters are numbers, letters, _ (underscore) and - (hyphen):"
             ," for maximum compatibility and portability, we recommend using no upper case letters</p>\n";

	return $prefixOK;
}
/*
   ======================================================================================
*/
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
