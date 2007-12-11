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

    $q="SELECT COUNT(DISTINCT `nextaction`) FROM `{$prefix}nextactions`";
    $na=@mysql_fetch_row(send_query($q,false));
    
    $q="SELECT COUNT(*) FROM `{$prefix}items` AS `i`
            JOIN `{$prefix}itemstatus`     AS `its` USING (`itemId`)
            WHERE `i`.`itemId` NOT IN (SELECT `itemId` FROM `{$prefix}lookup`)
                AND `its`.`dateCompleted` IS NULL";
    $orphans=@mysql_fetch_row(send_query($q,false));

    $totals=array(
                     'items'=>$items[0]
                    ,'next actions'=>$na[0]
                    ,'orphans'=>$orphans[0]
                );


    $q="SELECT COUNT(*) FROM `{$prefix}nextactions` WHERE ROW(`parentId`,`nextaction`) NOT IN
            (SELECT * FROM `{$prefix}lookup`)";
    $excessNA=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(*) FROM `{$prefix}lookup` WHERE
            ROW(`parentId`,`itemId`) NOT IN (SELECT * FROM `{$prefix}nextactions`)
            AND `itemID` IN (SELECT `nextaction` FROM `{$prefix}nextactions`)";
    $missingNA=@mysql_fetch_row(send_query($q,false));
    
    $q="SELECT COUNT(*) FROM `{$prefix}nextactions` AS `na`
            JOIN `{$prefix}itemstatus` AS `its` ON (na.`nextaction`=its.`itemId`)
            WHERE its.`dateCompleted` IS NOT NULL";
    $completedNA=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(*) FROM `{$prefix}itemattributes` WHERE `suppress`='y' AND `deadline`=NULL";
    $noTickleDate=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(*) FROM `{$prefix}items` where `title`=NULL OR `title`=''";
    $noTitle=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(*) FROM `{$prefix}lookup` WHERE 
            `parentId` NOT IN (SELECT `itemId` FROM `{$prefix}itemattributes`)
           OR `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}itemattributes`)";
    $redundantparent=@mysql_fetch_row(send_query($q,false));

    $q="SELECT COUNT(version) FROM `{$prefix}version`";
    $excessVersions=@mysql_fetch_row(send_query($q,false));

    $errors=array(
                     'redundant nextaction entries'=>$excessNA[0]
                    ,'missing nextaction entries'=>$missingNA[0]
                    ,'completed items marked as next actions'=>$completedNA[0]
                    ,'missing tickle dates'=>$noTickleDate[0]
                    ,'missing titles'=>$noTitle[0]
                    ,'redundant parent entries'=>$redundantparent[0]
                    ,'redundant version tags'=>-1+(int) $excessVersions[0]
                );

    // remove partial items from database
    $items1=array('items','itemstatus','itemattributes');
    $items2=$items1;
    foreach ($items1 as $t1) foreach ($items2 as $t2) if ($t1!=$t2) {
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
    global $config;
    require_once('mysql.funcs.inc.php');
    $sep="-- *******************************\n";
    $tables=array('categories','checklist','checklistitems','context','itemattributes','items','itemstatus','list','listitems','lookup','nextactions','tickler','timeitems','version','preferences');
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
    $data=htmlspecialchars($header.$sep.$data,ENT_NOQUOTES,$config['charset']);
    return $data;
}
/*
   ======================================================================================
*/
function recreateNextactions($prefix) { // recreate the nextactions table, removing all inconsistencies

    $q="DROP TABLE IF EXISTS {$prefix}tempNA";
    send_query($q);
    $q="CREATE TABLE {$prefix}tempNA SELECT * FROM {$prefix}nextactions";
    send_query($q);
    $q="TRUNCATE {$prefix}nextactions";
    send_query($q);
    $q="INSERT INTO {$prefix}nextactions (SELECT DISTINCTROW parentId, itemId AS nextaction
            FROM {$prefix}lookup WHERE itemId IN (SELECT nextaction FROM {$prefix}tempNA))";
    send_query($q);

    $q="SELECT COUNT(*) FROM {$prefix}nextactions";
    $tot=send_query($q,false);
    $q="SELECT COUNT(DISTINCT nextaction) FROM {$prefix}nextactions";
    $unique=send_query($q,false);
    $q="SELECT COUNT(*) FROM {$prefix}nextactions WHERE ROW(parentId,nextaction) NOT IN (SELECT * FROM {$prefix}tempNA)";
    $added=send_query($q,false);
    $q="SELECT COUNT(*) FROM {$prefix}tempNA WHERE ROW(parentId,nextaction) NOT IN (SELECT * FROM {$prefix}nextactions)";
    $removed=send_query($q,false);

    $q="DROP TABLE {$prefix}tempNA";
    send_query($q);
    
    $result=array('total_rows'=>$tot,'Number_of_Next_Actions'=>$unique,'added_rows'=>$added,'removed_rows'=>$removed);
    return $result;
}
/*
   ======================================================================================
*/
function fixDate($tableName,$columnName){
    global $config;
   // change dates of "0000-00-00" to NULL
   # fix date NULL versus 0000-00-00 issue
   $q=" update `{$config['prefix']}{$tableName}` set {$columnName}=NULL where `$columnName`='0000-00-00'";
   send_query($q);
}
/*
   ======================================================================================
*/
function fixAllDates($prefix) {
   fixDate('itemattributes','deadline');
   fixDate('itemstatus','dateCompleted');
   fixDate('itemstatus','dateCreated');
   fixDate('itemstatus','lastModified');
   fixDate('listitems','dateCompleted');
   fixDate('tickler','date');
   fixData($prefix);
}
/*
   ======================================================================================
*/
function fixData($prefix) {
    global $config;

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
    $items1=array('items','itemstatus','itemattributes');
    $items2=$items1;
    foreach ($items1 as $t1) foreach ($items2 as $t2) if ($t1!=$t2) {
        $q="DELETE FROM `{$prefix}$t1` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}$t2`)";
        send_query($q);
    }

    // if tickle flag is set with no deadline, remove tickle flag
    $q="update `{$prefix}itemattributes` set `suppress`='n' where `deadline`=NULL";
    send_query($q);

    // if any titles are blank, call them 'untitled'
    $q="update `{$prefix}items` set `title`='untitled' where `title`=NULL OR `title`=''";
    send_query($q);
    
    // now fix lookup
    $q="DELETE FROM `{$prefix}lookup` WHERE
            `parentId` NOT IN (SELECT `itemId` FROM `{$prefix}itemattributes`)
           OR `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}itemattributes`)";
    send_query($q);

    // remove next action flag from completed items
    $q="DELETE FROM `{$prefix}nextactions` WHERE nextaction IN (
            SELECT `itemId` FROM `{$prefix}itemstatus` WHERE dateCompleted IS NOT NULL
            )";
    send_query($q);
    
    // and finally, fix nextactions by recreating it completely
    recreateNextactions($prefix);
}
/*
   ======================================================================================
   Remove an installation
   ======================================================================================
*/
function deleteInstall($ver,$prefix) {
global $versions,$config,$tablesByVersion;
    require_once('headerDB.inc.php');
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
function fixLastModifiedColumn() {
    global $config;

   $q="UPDATE `{$config['prefix']}itemstatus` SET `lastModified`=`dateCreated` WHERE `lastModified`='"._DEFAULTDATE."'";
   send_query($q,false);
   $q="ALTER TABLE `{$config['prefix']}itemstatus` MODIFY `lastModified`
   timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP";
   send_query($q);
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
        ," prefix in config.php to allow you to create a new installation.</p>\n";
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
		echo '<p class="error">Prefix "',$prefix, '" is invalid - change config.php.'
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
   	foreach ($needle as $table)
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
	if (_DEBUG) echo '<p class="debug">Is there a current 0.8 installation with prefix "',$prefix,'"? ';
    $doneOK=checkTables('0.8rc-4',$prefix);
    if ($doneOK) {
        $retval=checkVersion($prefix);
        // check to see if it's really 0.8rc3 masquerading as 0.8rc4, by doing a case-sensitive table check
        if ($retval==='0.8rc-4' && !checkTables('0.8rc-4',$prefix,true))
            $retval='0.8rc-3';
    }else
        $retval=false;
    if (_DEBUG) echo (($doneOK)?'YES':'NO'),"</p><p class='debug'>Resolved version number as: '$retval'</p>\n";
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
        while ($out=mysql_fetch_row($result)) $last=$out;
		$retval=$last[0];
		if (_DEBUG) echo "<p class'debug'>Found Version field: $retval</p>";
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
	global $config,$install_success;
    // start creating new tables
	create_table('preferences');
	create_table("categories");
	create_table("checklist");
	create_table("checklistitems");
	create_table("context");
	create_table("itemattributes");
	create_table("items");
	create_table("itemstatus");
	create_table('list');
	create_table("listitems");
	create_table('lookup');
	create_table("nextactions");
	create_table("tickler");
	create_table("timeitems");
	createVersion();
}
/*
   ======================================================================================
*/
function amendIndexes() {
    global $config;
    $indexarray=array(
        'categories'    =>array('category','description'),
        'checklist'     =>array('description','title'),
        'checklistitems'=>array('notes','item'),
        'context'       =>array('name','description'),
    	'items'         =>array('title','desiredOutcome','description'),
    	'list'          =>array('description','title'),
    	'listitems'     =>array('notes','item'),
    	'tickler'       =>array('note','title'),
    	'timeitems'     =>array('timeframe','description') );

    $q="ALTER TABLE {$config['prefix']}tickler DROP INDEX `notes`";
    send_query($q,false);

    foreach ($indexarray as $table=>$indexes) {
        foreach ($indexes as $col) {
            $q="ALTER TABLE {$config['prefix']}$table DROP INDEX `$col`";
            send_query($q,false);
            $q="ALTER TABLE {$config['prefix']}$table ADD INDEX "._FULLTEXT." `$col` (`$col`"._INDEXLEN.")";
            send_query($q);
        }
    }
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
function createVersion()  {
    global $config,$temp;
    create_table('version');
    $q="INSERT INTO `{$config['prefix']}{$temp}version` (`version`) VALUES";
    $q.=" ('"._GTD_VERSION."')";
    send_query($q);
}
/*
   ======================================================================================
*/
function updateVersion() {
    global $config;
    $q="TRUNCATE `{$config['prefix']}version`";
    $q="INSERT INTO `{$config['prefix']}version` VALUES('"._GTD_VERSION."',NULL)";
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
        case 'config': // no valid config.php
    		echo "<p class='error'>No valid config.php file found.<br />"
    			,"Copy the config.sample.php file to config.php, and set the MySQL parameters</p>\n";
    		// TOFIX - link to config.php documentation here

            break;
        case 'db': // failed during attempt to open database
            echo "<p class='error'>";
            if ($config['db']=='')
                echo "No database name was found in the config.php file: you will need to add the "
                    ," database name, database user name, and password, to the config.php file ";
            else
                echo "Please check your config.php file. It's currently set to use the '{$config['db']}' MySQL database."
                ," If that is the correct name, it may be that the database is not yet created, "
                ," or that the database user name or password in the config.php file are incorrect."
                ," Either create the database, adjust the user permissions, or set the username and password correctly,";

            echo " (contact your administrator if you don't know how to do this)"
                ," and then return to this page.</p>\n";
            break;
        case 'tables':
            echo "<p class='error'>Failed to get a list of the current tables in the database: check your MySQL database structure</p>\n";
            break;
        case 'prefix':
            echo "<p class='error'>Change the prefix value in config.php file, then return to this page</p>\n";
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
	global $config, $temp;
	$tablename=$config['prefix'].$temp.$name;
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
    case "checklist":
       $q.="`checklistId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`checklistId`),    ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "checklistitems":
       $q.="`checklistItemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`item` text NOT NULL, ";
       $q.="`notes` text, ";
       $q.="`checklistId` int(10) unsigned NOT NULL default '0', ";
       $q.="`checked` enum ('y', 'n') NOT NULL default 'n', ";
       $q.="PRIMARY KEY (`checklistItemId`), KEY `checklistId` (`checklistId`),";
       $q.=_FULLTEXT." KEY `notes` (`notes`"._INDEXLEN."), "._FULLTEXT." KEY `item` (`item`"._INDEXLEN."))"._CREATESUFFIX;
    break;
	case "itemattributes";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`type` enum ('m','v','o','g','p','a','r','w','i') NOT NULL default 'i', ";
       $q.="`isSomeday` enum('y','n') NOT NULL default 'n', ";
       $q.="`categoryId` int(11) unsigned NOT NULL default '0', ";
       $q.="`contextId` int(10) unsigned NOT NULL default '0', ";
       $q.="`timeframeId` int(10) unsigned NOT NULL default '0', ";
       $q.="`deadline` date default NULL, ";
       $q.="`repeat` int(10) unsigned NOT NULL default '0', ";
       $q.="`suppress` enum('y','n') NOT NULL default 'n', ";
       $q.="`suppressUntil` int(10) unsigned default NULL, ";
       $q.="PRIMARY KEY (`itemId`), ";
       $q.="KEY `contextId` (`contextId`), ";
       $q.="KEY `suppress` (`suppress`), ";
       $q.="KEY `type` (`type`), ";
       $q.="KEY `timeframeId` (`timeframeId`), ";
       $q.="KEY `isSomeday` (`isSomeday`),    ";
       $q.="KEY `categoryId` (`categoryId`),  ";
       $q.="KEY `isSomeday_2` (`isSomeday`))";
	break;
    case "context":
       $q.="`contextId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`name` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`contextId`), ";
       $q.=_FULLTEXT." KEY `name` (`name`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "items":
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`description` longtext, ";
       $q.="`desiredOutcome` text, ";
       $q.="PRIMARY KEY  (`itemId`), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `desiredOutcome` (`desiredOutcome`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "itemstatus":
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`dateCreated` date  default NULL, ";
       $q.="`lastModified` timestamp default '"._DEFAULTDATE."' ,";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`itemId`))";
	break;
	case "list":
       $q.="`listId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`listId`), ";
       $q.="KEY `categoryId` (`categoryId`), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN.")) "._CREATESUFFIX;
	break;
	case "listitems":
       $q.="`listItemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`item` text NOT NULL, ";
       $q.="`notes` text, ";
       $q.="`listId` int(10) unsigned NOT NULL default '0', ";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`listItemId`), ";
       $q.="KEY `listId` (`listId`), ";
       $q.=_FULLTEXT." KEY `notes` (`notes`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `item` (`item`"._INDEXLEN.")) "._CREATESUFFIX;
	break;
	case "tickler":
       $q.="`ticklerId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`date` date  default NULL, ";
       $q.="`title` text NOT NULL, ";
       $q.="`note` longtext, ";
       $q.="`repeat` int(10) unsigned NOT NULL default '0', ";
       $q.="`suppressUntil` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`ticklerId`), ";
       $q.="KEY `date` (`date`), ";
       $q.=_FULLTEXT." KEY `note` (`note`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN.")) "._CREATESUFFIX;
	break;
	case "goals":
       $q.="`id` int(11) NOT NULL auto_increment, ";
       $q.="`goal`   longtext, ";
       $q.="`description`   longtext, ";
       $q.="`created` date default NULL, ";
       $q.="`deadline` date default NULL, ";
       $q.="`completed` date default NULL, ";
       $q.="`type` enum('weekly', 'quarterly') default NULL ,";
       $q.="`projectId` int(11) default NULL, PRIMARY KEY (`id`) )";
	break;
	case "lookup":
       $q.="`parentId` int(11) NOT NULL default '0', ";
       $q.="`itemId` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`itemId`) )";
    break;
	case "preferences":
       $q.="`id`  int(10) unsigned NOT NULL auto_increment, ";
       $q.="`uid` int(10)  NOT NULL default '0', ";
       $q.="`option`  text, ";
       $q.="`value`  text, ";
       $q.="PRIMARY KEY  (`id`)); ";
	break;
	case "nextactions":
       $q.="`parentId` int(10) unsigned NOT NULL default '0', ";
       $q.="`nextaction` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`nextaction`))";
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
