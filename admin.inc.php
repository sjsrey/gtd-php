<?php
/*
   ======================================================================================
   data cleaning functions
   ======================================================================================
*/
function checkErrors($prefix) {

    $q="SELECT COUNT(*) FROM `{$prefix}items`";
    $items=@mysql_fetch_row(send_query($q,false));
    if (empty($items)) return false;

    $q="SELECT COUNT(*) FROM `{$prefix}itemattributes` AS `ia`
            JOIN `{$prefix}itemstatus`     AS `its` USING (`itemId`)
            WHERE `its`.`dateCompleted` IS NULL
                AND ia.`nextaction`='y'";
    $na=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(*) FROM `{$prefix}itemstatus`
            WHERE `dateCompleted` IS NULL AND
            (
                (`type` NOT IN ('i','m','L','C')
                    AND `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}lookup`)
                )
            OR `type`='' OR `type` IS NULL
            )";
    $orphans=@mysql_fetch_row(send_query($q,false));

    $totals=array(
                     'items'=>$items[0]
                    ,'next actions'=>$na[0]
                    ,'orphans'=>$orphans[0]
                );

    $q="SELECT COUNT(*) FROM `{$prefix}items` where `title`=NULL OR `title`=''";
    $noTitle=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(*) FROM `{$prefix}lookup` WHERE 
            `parentId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)
           OR `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)";
    $redundantparent=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(version) FROM `{$prefix}version`";
    $excessVersions=@mysql_fetch_row(send_query($q,false));

    $errors=array(   'missing titles'=>$noTitle[0]
                    ,'redundant parent entries'=>$redundantparent[0]
                    ,'redundant version tags'=>-1+(int) $excessVersions[0]
                );

    // remove partial items from database
    $items1=array('itemstatus'=>'items','items'=>'itemstatus','itemattributes'=>'itemstatus','itemattributes'=>'items');
    foreach ($items1 as $t1=>$t2) {
        $q="SELECT COUNT(DISTINCT `itemId`) FROM `{$prefix}$t1` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}$t2`)";
        $val=@mysql_fetch_row(send_query($q,false));
        $errors["IDs are in $t1, but not in $t2"]=$val[0];
    }

    return array('totals'=>$totals,'errors'=>$errors);
}
/*
   ======================================================================================
*/
function backupData($prefix) {
    $sep="-- *******************************\n";
    $tables=array('categories','context','itemattributes','items','itemstatus','lookup','preferences','tagmap','timeitems','version');
    $data='';
    $header='';
    $creators='';
    foreach ($tables as $tab) {
        $table=$prefix.$tab;
        $data .=$sep;
        $header .="TRUNCATE TABLE `$table`;\n";
		$tableStructure = @mysql_fetch_assoc(send_query("SHOW CREATE TABLE $table"));
        $creators .= "DROP TABLE IF EXISTS `{$table}`; \n".$tableStructure['Create Table'].";\n";
        $rows = send_query("SELECT * FROM `$table`",false);
        while ($rec = @mysql_fetch_assoc($rows) ) {
        	$thisdata='';
        	foreach ($rec as $key => $value)
        		$thisdata .= ( ($value===NULL) ? 'NULL' : ("'".safeIntoDB($value)."'") ) . ',';
        	$thisdata = substr($thisdata,0,-1);
            $data .= "INSERT INTO `$table` VALUES ($thisdata);\n";
        }
    }
    //$data=htmlspecialchars($creators.$sep.$header.$sep.$data,ENT_NOQUOTES);
    $data=htmlspecialchars($header.$sep.$data,ENT_NOQUOTES,$_SESSION['config']['charset']);
    return $data;
}
/*
   ======================================================================================
*/
function fixData($prefix) {
    foreach ( array( 'deadline'=>'itemattributes'
                    ,'tickledate'=>'itemattributes'
                    ,'dateCompleted'=>'itemstatus'
                    ,'dateCreated'=>'itemstatus'
                    ,'lastModified'=>'itemstatus'
             ) as $field=>$table) {
        // change dates of "0000-00-00" to NULL
        $q="UPDATE `$prefix{$table}` SET `$field`=NULL where `$field`='0000-00-00'";
        send_query($q);
    }
    // remove duplicate version tags
    $q="CREATE TABLE `{$prefix}versiontemp`
            SELECT * FROM `{$prefix}version` WHERE `updated` >= ALL
                (SELECT `updated` FROM `{$prefix}version`)";
    send_query($q);
    $q="TRUNCATE `{$prefix}version`";
    send_query($q);
    $q="INSERT INTO `{$prefix}version` SELECT * FROM `{$prefix}versiontemp`";
    send_query($q);
    $q="DROP TABLE `{$prefix}versiontemp`";
    send_query($q);

    // it's possible that some legacy items might have no itemstatus: fix that now
    $q="INSERT INTO `{$prefix}itemstatus` (`itemId`)
            SELECT `itemId` from `{$prefix}items` WHERE `itemId` NOT IN
                (SELECT `itemId` FROM `{$prefix}itemstatus`)";
    send_query($q);

    // remove partial items from database 
    $items1=array('itemstatus'=>'items','items'=>'itemstatus','itemattributes'=>'itemstatus','itemattributes'=>'items');
    foreach ($items1 as $t1=>$t2) {
        $q="DELETE FROM `{$prefix}$t1` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}$t2`)";
        send_query($q);
    }

    // repair empty dates for fields where date should not be null
    $q="update `{$prefix}itemstatus` set `lastModified`=CURDATE() where `lastModified` IS NULL";
    send_query($q);
    $q="update `{$prefix}itemstatus` set `dateCreated`=CURDATE() where `dateCreated` IS NULL";
    send_query($q);
    // repair impossible dates - by default, MySQL v4.x allowed dates such as 2008-13-51
    $q="UPDATE `{$prefix}itemstatus`     AS its
          JOIN `{$prefix}itemattributes` AS ia USING (`itemId`)
             SET its.`dateCompleted`=its.`dateCompleted`+'0 DAY',
                  ia.`deadline`     = ia.`deadline`     +'0 DAY',
                  ia.`tickledate`   = ia.`tickledate`   +'0 DAY' ";
    send_query($q);

    // if any titles are blank, call them 'untitled'
    $q="update `{$prefix}items` set `title`='untitled' where `title`=NULL OR `title`=''";
    send_query($q);
    
    // now fix lookup
    $q="DELETE FROM `{$prefix}lookup` WHERE
            `parentId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)
           OR `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)";
    send_query($q);

    // TOFIX - if any itemIds in tagmap but not in items, remove
    // TOFIX - if any itemstatus type is null, make it 'i'
}
/*
   ======================================================================================
   Remove an installation
   ======================================================================================
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
/*
   ======================================================================================
   various auxiliary functions
   ======================================================================================
*/
function makeDeleteButton($prefix,$ver) {
    global $versions;
    $out="<input class='warning' type='submit' value='Delete=$prefix=$ver' name='Delete_$prefix' />";
    return $out;
}
/*
   ======================================================================================
*/
function offerToDeleteOne($prefix,$ver) {
    echo showDeleteWarning(true)
        ,"<form action='install.php' method='post'><div>\n"
        ,"You can delete the current GTD-PHP data set here:"
        ,makeDeleteButton($prefix,$ver)
        ,"</div></form>\n";
}
/*
   ======================================================================================
*/
function showNoUpgradeMsg($prefix,$ver) {
    echo "<div id='main'>\n"
        ,"<p class='warning'>Found an earlier GTD-PHP installation "
        ," (version $ver) with prefix '$prefix'<br />\n"
        ," The installation options that are currently set do "
        ," not allow you to upgrade the current installation in place: change the "
        ," prefix in config.inc.php to allow you to create a new installation.</p>\n";
}
/*
   ======================================================================================
*/
function tabulateOption($prefix,$key,$msg) {
	static $isChecked=' checked="checked" ';
	$result="<td>";
	if ($prefix==='!')
        $result.="X";
	else {
	   $result.="<input type='radio' name='installkey' value='$key' $isChecked />";
	   $isChecked='';
    }
    $result.="</td><td>$msg</td>"
        .((_DEBUG)?"<td class='debug'>Option: $key</td>":"")
        ."\n";

	return $result;
}
/*
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
function checkTables($ver,$prefix='',$casesensitive=false) {
	global $versions,$tablelist,$tablesByVersion;
	$doneOK=true;
	if ($casesensitive) {
        $needle=$tablesByVersion[$versions[$ver]['tables']];
        $haystack=$tablelist;
	} else {
    	$needle=array();
        $haystack=array();
    	foreach ($tablesByVersion[$versions[$ver]['tables']] as $table)
            $needle[]=strtolower($table);
        foreach ($tablelist as $table)
            $haystack[]=strtolower($table);
    }
   	if (empty($needle))
        $doneOK=false;
    else foreach ($needle as $table)
		if (!in_array($prefix.$table,$haystack,true)) {
			$doneOK=false;
			break;
		}
    return $doneOK;
}
/*
   ======================================================================================
*/
function checkPrefixedTables($prefix) {
	global $versions;
	if (_DEBUG) echo "<p class='debug'>Is there a current installation with prefix '$prefix'?</p>";
	$retval=checkVersion($prefix);
	if ($retval && checkTables($retval,$prefix,true)) {
        $doneOK=true;
    } else {
        $doneOK=checkTables('0.8rc-4',$prefix,false);
        if ($doneOK) {
            // check to see if it's really 0.8rc3 masquerading as 0.8rc4, by doing a case-sensitive table check
            if ($retval==='0.8rc-4' && !checkTables('0.8rc-4',$prefix,true)) {
                $retval='0.8rc-3';
            }
        } else {
            $retval=false;
        }
    }
    if (_DEBUG) echo "<p class='debug'>",(($doneOK)?'YES':'NO')," - resolved version number as: '$retval'</p>\n";
	return $retval;
}
/*
   ======================================================================================
*/
function checkVersion($prefix) {
	$q="SELECT `version` from `{$prefix}version`";
	$result = send_query($q,false);
	if (empty($result)) {
		$retval='0.8rc-2';
    } else {
        $last=array(0=>null);
        while ($out=mysql_fetch_row($result)) $last=$out;
        $retval=$last[0];
		if (_DEBUG) echo "<p class='debug'>Found Version field: $retval </p>";
	}

    return $retval;
}
/*
   ======================================================================================
*/
function getExistingDestinationTables($prefix) {
	global $tablelist,$versions,$tablesByVersion;
	if (_DEBUG) echo '<p class="debug">Checking availability of destination prefix "{$prefix}"</p>';
	$destInUse=array();
	foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $thisTable)
   		if (count(array_keys($prefix.$thisTable,$tablelist,true)))
			array_push($destInUse,$prefix.$thisTable);
	return $destInUse;
}
/*
   ======================================================================================
*/
function create_tables() {
	global $tablesByVersion,$versions;
	include_once 'gtd_constants.inc.php';
    foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $table)
        create_table($table);
}
/*
   ======================================================================================
*/
function drop_table($name){
	global $rollback;
	$q = "drop table if exists `$name`";
    send_query($q);
    unset($rollback[$name]);
}
/*
   ======================================================================================
*/
function send_query($q,$dieOnFail=true) {
    global $rollback;
   	if (_DEBUG) echo "<p class='debug'>{$q}</p>\n";
    if (_DRY_RUN)
        $result=true;
    else
		$result = mysql_query($q);

    if ($result) {
        if (_DEBUG) echo "<p class='debug'>",mysql_affected_rows()," rows affected</p>\n";
        if (stristr($q,'create table')!==FALSE) {
            $tmp=explode('`',$q);
            $newfile=$tmp[1];
            $rollback[$newfile] = "DROP TABLE IF EXISTS `$newfile`";
        } elseif (stristr($q,'rename table')!==FALSE) {
            $tmp=explode('`',$q);
            $oldfile=$tmp[1];
            $newfile=$tmp[3];
            $rollback[$newfile] = "DROP TABLE IF EXISTS `$newfile`";
            unset($rollback[$oldfile]);
        }
    } else {
        if($dieOnFail) {
            echo "<p class='error'>Fatal error: Failed to do MySQL query: '$q'<br />",mysql_error(),"</p>\n";
            die("<p class='error'>Installation terminated</p>");
        }elseif (_DEBUG)
            echo "<p class='warning debug'>Warning: Failed to do MySQL query: '$q'<br />",mysql_error(),"</p>\n";
    }
    return($result);
}
/*
   ======================================================================================
*/
function updateVersion() {
    $q="TRUNCATE `{$_SESSION['prefix']}version`";
    send_query($q,false);
    $q="INSERT INTO `{$_SESSION['prefix']}version` VALUES('"._GTD_VERSION."',NULL)";
    send_query($q,false);
}
/*
   ======================================================================================
*/
function cleanup($message='cleaning up the mess') {
	global $rollback,$install_success;
	if ($install_success) return $message;

	foreach($rollback as $query) send_query($query,false);
	echo "<p class='error'>Installation aborted, and cleanup done - <a href='install.php'>return to main install screen</a></p></div></body></html>";
	return $message;
}
/*
   ======================================================================================
*/
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
/*
   ======================================================================================
*/
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
/*
   ======================================================================================
*/
function failDuringCheck() {
    global $checkState,$config;
    switch ($checkState) {
        case 'ok':return; // reached end ok, so nothing to do
        case 'in': // barely started
            echo "<p class='error'>Unable to start the installation pre-flight checks</p>";
            break;
        case 'config': // no valid config.inc.php
    		echo "<p class='error'>No valid config.inc.php file found.<br />"
    			,"Copy the config.sample.php file to config.inc.php, and set the MySQL parameters</p>\n";
    		// TOFIX - link to config.inc.php documentation here

            break;
        case 'db': // failed during attempt to open database
            echo "<p class='error'>";
            if (empty($config['db']))
                echo "No database name was found in the config.inc.php file: you will need to add the "
                    ," database name, database user name, and password, to the config.inc.php file ";
            else
                echo "Please check your config.inc.php file. It's currently set to use the '{$config['db']}' MySQL database."
                ," If that is the correct name, it may be that the database is not yet created, "
                ," or that the database user name or password in the config.inc.php file are incorrect."
                ," Either create the database, adjust the user permissions, or set the username and password correctly,";

            echo " (contact your administrator if you don't know how to do this)"
                ," and then return to this page.</p>\n";
            break;
        case 'tables':
            echo "<p class='error'>Failed to get a list of the current tables in the database: check your MySQL database structure</p>\n";
            break;
        case 'prefix':
            echo "<p class='error'>Change the prefix value in config.inc.php file, then return to this page</p>\n";
            break;
        case 'installations':
            echo "<p class='error'>Failed while examining current installations</p>\n";
            break;
        case 'report':
            echo "<p class='error'>Failed while producing table of installation options</p>\n";
            break;
        default: // failed some other time
            echo "<p class='error'>Failed during check, at the '$checkState' stage</p>\n";
            break;
    }
    echo "</div></body></html>";
}
/*
   ======================================================================================
     Table Creation Queries
   ======================================================================================
*/
function create_table ($name) {
	global $temp;
	$tablename=$_SESSION['prefix'].$temp.$name;
    $q="CREATE TABLE `$tablename` (";
	switch ($name) {
	case "categories":
       $q.="`categoryId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`category` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`categoryId`), ";
       $q.=_FULLTEXT." KEY `category` (`category`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    break;
    case "context":
       $q.="`contextId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`name` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`contextId`), ";
       $q.=_FULLTEXT." KEY `name` (`name`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "itemattributes";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`isSomeday` enum('y','n') NOT NULL default 'n', ";
       $q.="`contextId` int(10) unsigned NOT NULL default '0', ";
       $q.="`timeframeId` int(10) unsigned NOT NULL default '0', ";
       $q.="`deadline` date default NULL, ";
       $q.="`tickledate` date default NULL, ";
       $q.="`nextaction` enum('y','n') NOT NULL DEFAULT 'n', ";
       $q.="PRIMARY KEY (`itemId`), ";
       $q.="KEY `contextId` (`contextId`), ";
       $q.="KEY `timeframeId` (`timeframeId`), ";
       $q.="KEY `isSomeday` (`isSomeday`) ) "._CREATESUFFIX;
	break;
	case "items":
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`description` longtext, ";
       $q.="`desiredOutcome` text, ";
       $q.="`recurdesc` text, ";
       $q.="`recur` text, ";
       $q.="PRIMARY KEY  (`itemId`), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `desiredOutcome` (`desiredOutcome`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "itemstatus":
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`dateCreated` date default '"._DEFAULTDATE."', ";
       $q.="`lastModified` timestamp default '"._DEFAULTDATE."' ,";
       $q.="`dateCompleted` date default NULL, ";
       $q.="`type` enum ('m','v','o','g','p','a','r','w','i','L','C','T') NOT NULL default 'i', ";
       $q.="`categoryId` int(11) unsigned NOT NULL default '0', ";
       $q.=" PRIMARY KEY  (`itemId`), ";
       $q.=" KEY `type` (`type`), ";
       $q.=" KEY `categoryId` (`categoryId`) ) ";
	break;
	case "lookup":
       $q.="`parentId` int(11) NOT NULL default '0', ";
       $q.="`itemId` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`itemId`) )";
    break;
	case "preferences":
       $q.="`id`  int(10) unsigned NOT NULL auto_increment,
            `uid` int(10)  NOT NULL default '0',
            `option` text NOT NULL,
            `value`  text,
            PRIMARY KEY (`id`),
            UNIQUE KEY(`uid`,`option`(10)) ) "._CREATESUFFIX;
	break;
    case "tagmap":
        $q.= "`itemId` int(10) unsigned NOT NULL,
            `tagname` text NOT NULL,
            PRIMARY KEY (`itemId`,`tagname`(20) ),"
            ._FULLTEXT." KEY `itemId` (`itemId`),"
            ._FULLTEXT." KEY `tagname` (`tagname`"._INDEXLEN.") ) "._CREATESUFFIX;
    break;
	case "timeitems":
       $q.="`timeframeId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`timeframe` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="`type` enum('v','o','g','p','a') NOT NULL default 'a', ";
       $q.="PRIMARY KEY  (`timeframeId`), ";
       $q.="KEY `type` (`type`), ";
       $q.=_FULLTEXT." KEY `timeframe` (`timeframe`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    break;
	case "version":
       $q.="`version` text NOT NULL, ";
       $q.="`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update ";
       $q.=" CURRENT_TIMESTAMP)";
    break;
    default:
    break;
    }
    send_query($q);
}
/*
   ======================================================================================
   ======================================================================================
*/
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
