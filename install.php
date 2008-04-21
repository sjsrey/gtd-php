<?php
/*---------------------------------------------------------------------------------
                            user-configurable options
---------------------------------------------------------------------------------*/

    /* _MAXKEYLENGTH = integer -
    sets the maximum length of indexes, used for sorting */
define("_MAXKEYLENGTH",10);

    /* _ALLOWUPGRADEINPLACE = false | true -
    allow the user to upgrade the current installation by over-writing it.
    If false, then the user should enter a new prefix in config.php          */
define("_ALLOWUPGRADEINPLACE",false);

    /* _ALLOWUNINSTALL = false | true -
    allow the user to remove tables associated with a particular GTD installation */
define("_ALLOWUNINSTALL",true);


/*---------------------------------------
             Debugging options
---------------------------------------*/

    /* _DEBUG = false | true -
    show lots of debugging information during execution */
define("_DEBUG",true);

    /* _DRY_RUN = false | true - dry run won't change the database, but will
    mime all the actions that would be done: use _DEBUG true to see these */
define("_DRY_RUN",false);

/*---------------------------------------------------------------------------------
                            End of user options
---------------------------------------------------------------------------------*/


/* _USEFULLTEXT = false | true - use FULLTEXT indexes, which take up a lot of
space, but allow you to use MATCH ... AGAINST NB GTD-php does not currently use this */
define("_USEFULLTEXT",false); 

require_once 'admin.inc.php';
require_once 'gtd_constants.inc.php';
define("_DEFAULTDATE","1990-01-01");
define ("_TEMPPREFIX","_gtdphp_temptable_");

if (_USEFULLTEXT) {
   define("_CREATESUFFIX",' ENGINE=MyISAM ');
   define("_FULLTEXT",' FULLTEXT ');
   define ("_INDEXLEN",'');
}else{
   define("_CREATESUFFIX",' ');
   define("_FULLTEXT",' ');
   define ("_INDEXLEN",'('._MAXKEYLENGTH.')');
}

/* ============================================================================
  global variables
*/
$config=array();

$tablesByVersion=array( // NB the order of tables in these arrays is CRITICAL. they must be consistent across the 0.8 sub-versions
    // we don't offer an upgrade path from 0.6.  Any 0.6 installations should first upgrade to 0.7, then run this routine
    '0.6'     => array('context','goals','maybe','maybesomeday','nextactions','projects','reference','waitingon'),
    // 0.7 is the earliest version that we can upgrade from, here
    '0.7'     => array('categories','checklist','checklistItems','context','goals','itemattributes','items','itemstatus','list','listItems','nextactions','projectattributes','projects','projectstatus','tickler','timeitems'),
    // 0.8rc-1 was a major change, with goals, actions and projects all being merged into the items files
    '0.8rc-1' => array('categories','checklist','checklistItems','context','itemattributes','items','itemstatus','list','listItems','lookup','nextactions','tickler','timeitems','version'),
    // 0.8rc-2 added the preferences table
    '0.8rc-3' => array('categories','checklist','checklistItems','context','itemattributes','items','itemstatus','list','listItems','lookup','nextactions','tickler','timeitems','version','preferences'),
    // 0.8rc-4 saw all table names being standardised to lower case:
    '0.8rc-4' => array('categories','checklist','checklistitems','context','itemattributes','items','itemstatus','list','listitems','lookup','nextactions','tickler','timeitems','version','preferences'),
    // 0.8z.03 - tags and tagmap tables introduced, nextactions table removed
    '0.8z.03'   => array('categories','checklist','checklistitems','context','itemattributes','items','itemstatus','list','listitems','lookup','tagmap','timeitems','version','preferences'),
    // 0.8z.04 - checklist, checklistitems, list, listitems tables removed.   items, itemstatus, itemattributes reworked
    '0.8z.04'   => array('categories','context','itemattributes','items','itemstatus','lookup','tagmap','timeitems','version','preferences')
    );

$versions=array(
    '0.6'=>     array(  'tables'=>'0.6',
                        'database'=>'0.6',
                        'upgradepath'=>'X'),
    '0.7'=>     array(  'tables'=>'0.7',
                        'database'=>'0.7',
                        'upgradepath'=>'0.7'),
    '0.8rc-1'=> array(  'tables'=>'0.8rc-1',
                        'database'=>'0.8rc-1',
                        'upgradepath'=>'0.8rc-1'),
    '0.8rc-3'=> array(  'tables'=>'0.8rc-3',
                        'database'=>'0.8rc-3',
                        'upgradepath'=>'0.8rc-3'),
    '0.8rc-4'=> array(  'tables'=>'0.8rc-4',
                        'database'=>'0.8rc-4',
                        'upgradepath'=>'0.8rc-4'),
    '0.8z.03'  => array(  'tables'=>'0.8z.03',
                        'database'=>'0.8z.03',
                        'upgradepath'=>'0.8z.03'),
    '0.8z.04'  => array(  'tables'=>'0.8z.04',
                        'database'=>'0.8z.04',
                        'upgradepath'=>'copy')
    );
/*
  end of global variables
 ============================================================================*/

// initialise variables used for checking what this run is supposed to do
$areUpdating=false;
$wantToDelete=false;
$areDeleting=false;

require_once 'headerHtml.inc.php';
echo "</head><body><div id='container'>";
include 'showMessage.inc.php';
echo "<h2>This is the gtd-php v0.8 installer</h2>\n";

if (_DEBUG) echo '<pre>'
	,(_DRY_RUN)?'Executing Dry run - no tables will be amended in this run':'This is a <b>live</b> run'
	,'<br />POST variables: ',print_r($_POST,true),"</pre>\n";

if (isset($_POST['cancel']))
    ; // we've cancelled an over-write or a delete, so go back to the installation menu
elseif (isset($_POST['install'])) {
    $toPrefix=$_POST['prefix'];
    $toDB=$_POST['db'];
    // check to see whether the prefix in config.php hsa been changed between POST and now
    if ($toPrefix===$config['prefix'] && $toDB===$config['db'])
        $areUpdating=true; // ok, it's safe to update.
    else {
        echo "<p class='error warning'>config.php has changed during the installation process. "
            ," The upgrade cannot continue. Please select your upgrade option again.";
    }
}elseif (_ALLOWUNINSTALL && !isset($_POST['check'])) {
    foreach ($_POST as $thiskey=>$thisval) {
        if (_DEBUG)echo "<p class='debug'>Is $thiskey is a delete key? ";
        if ($thiskey!==($tst=preg_replace('/^Delete_(.*)$/','$1',$thiskey))) {
            if (_DEBUG)echo "Yes</p>\n";
            $wantToDelete=true;
            $versionToDelete=$thisval;
            break;
        }
        if (_DEBUG)echo "No</p>\n";
    }
    if (isset($_POST['delete'])) $areDeleting=true;
}
if (isset($versionToDelete)) {
    $args=explode('=',$versionToDelete);
    $fromPrefix=$args[1];
    $installType=$args[2];
} elseif (isset($_POST['installkey'])) {
    $args=explode('=',$_POST['installkey']);
    $installType=$args[0];
    $fromPrefix=(count($args)>1)?$args[1]:null;
}

if ($areUpdating) {
    if ($fromPrefix===$toPrefix && !isset($_POST['upgrade']))
        getConfirmation('upgrade',$toPrefix);
    else {
        $install_success = false;
        $rollback = array();
    	doInstall($installType,$fromPrefix);
    }
}elseif ($areDeleting)
    deleteInstall($installType,$fromPrefix);
elseif ($wantToDelete)
    getConfirmation('delete',$fromPrefix);
else {
    $checkState='in';
	checkInstall();
}
include_once 'footer.php';
return;
/*
   ======================================================================================
   end of main output.


   Function to decide what installation action(s) to offer to the user:
   ======================================================================================
*/
function checkInstall() {
	global $config,$versions,$tablelist,$sort,$checkState,$tablesByVersion;

    register_shutdown_function('failDuringCheck');
	$goodToGo=true; // assume we'll be able to upgrade, until we find something to stop us

    echo "<p>Read the <a href='INSTALL'>INSTALL</a> file for information on using this install/upgrade program</p>\n";

	if (_DEBUG) {
		$included_files = get_included_files();
		echo '<pre>Included files:',print_r($included_files,true),'</pre>';
	}
    // check the config file
	$checkState='config';
	if (_DEBUG) echo '<p class="debug">Got config.php:</p><pre>',print_r($config,true),'</pre>';
	if (!isset($config['db'])) {
        echo "<p class='warning'>Fatal Error: no valid config.php file has been found. "
            ," you should update the config.php file, based on the config.sample.php "
            ," file supplied with GTD-PHP, before using this installer.</p>\n";
        exit();
    }

	// check to see whether config file is 0.7 or 0.8 style: if the former, warn the installer, and link to documentation
	$configFileIsOld=!(isset($config['firstDayOfWeek']) && is_array($sort));
	if ($configFileIsOld) {
        echo "<p class='warning'>Warning: your config.php file appears to be for "
            ," an earlier version of GTD-PHP! The installation may be able to "
            ," proceed successfully, but you should update the config.php file, "
            ," based on the config.sample.php file supplied, before using the "
            ," package.</p>\n";
	}

	// validate the prefix
	$checkState='prefix';
	if (!checkPrefix($config['prefix'])) exit(); // invalid prefix = fatal error

    // try to open the database
    $checkState='db';
	require_once 'headerDB.inc.php';

	// got a database; now get a list of its tables
	$checkState='tables';
	$tablelist = getDBTables($config['db']);
	$nt=count($tablelist);
	if (_DEBUG) echo "<pre>Number of tables: $nt<br />",print_r($tablelist,true),"</pre>";

	/*
		Build an array of current installlations,
		and offer choice of upgrading from one of these, or doing a fresh install
	*/
	$checkState='installations';
	$gotVersions=array();
	$destInUse=false;
	$gotPrefixes=(preg_grep("/.*version$/",$tablelist));
	if (_DEBUG) echo '<pre>Version tables:',print_r($gotPrefixes,true),'</pre>';
	foreach ($gotPrefixes as $thisVersionTable) {
		$thisPrefix=substr($thisVersionTable,0,-7);
		$thisVer=checkPrefixedTables($thisPrefix);
		if ($thisVer!='') $gotVersions["{$thisVer}={$thisPrefix}"]=$thisVer;
		if ($thisPrefix==$config['prefix']) { // we have an installation already using our target prefix
            $destInUse=true;
            if ($thisVer==_GTD_VERSION) {     // and it's the latest version - so no upgrade needed!
    			// this destination is already in use - let's go!
                require_once 'headerMenu.inc.php';
    			echo "<div id='main'>\n<h2>Installed Version is up to date</h2>\n"
                    ,"<p>There is already an installation of "
                    ,_GTDPHP_VERSION," with prefix '{$config['prefix']}'</p>"
                    ,"<p>It's ready for you to <a href='index.php'>start using it.</a></p>\n"
                    ,"<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
                $goodToGo=false;
                if (_ALLOWUNINSTALL)
                    offerToDeleteOne($thisPrefix,$thisVer);
            } else if (_ALLOWUPGRADEINPLACE || $versions[$thisVer]['database']===$versions[_GTD_VERSION]['database'])
                /* now reset the versions array, and quit this loop,
                   because if we already have an installation with this prefix,
                   we don't want to offer any other kind of upgrade */
                $gotVersions=array("{$thisVer}={$thisPrefix}"=>$thisVer);
            else { // not allowed to upgrade in place, but an upgrade is required, so abort
                $goodToGo=false;
                showNoUpgradeMsg($thisPrefix,$thisVer);
            }
            break;
		}
	}
	$checkState='report';
    // get server information for problem reports
    if ($goodToGo)
        echo "<div id='main'><h1>gtd-php installation/upgrade</h1>\n";

    echo "<h2>Installation Info</h2>\n"
        ,"<p>php: ",phpversion(),"</p>\n"
        ,"<p>database: ",getDBVersion(),"</p>\n";

	// check for 0.8rc-1
	if (!$destInUse && checkTables('0.8rc-1','',false) && checkVersion('')==='0.8rc-1') {
        if ($config['prefix']=='') { // prefixes weren't used in 0.8rc-1, so a blank target prefix means we are trying to upgrade in place, over the top of 0.8rc-1
            if (_ALLOWUPGRADEINPLACE) {
                $destInUse=true;
                /* now reset the versions array, because if we already have an
                 installation with this prefix, we don't want to offer any other
                 kind of upgrade */
                $gotVersions=array('0.8rc-1='=>'0.8rc-1');
            } else {
                $goodToGo=false;
                if (_ALLOWUNINSTALL)
                    offerToDeleteOne($config['prefix'],'0.8rc-1');
                else showNoUpgradeMsg($config['prefix'],'0.8rc-1');
            }
        } else $gotVersions['0.8rc-1=']='0.8rc-1';
    }

    $checkState='v0.7search';
	if (!$destInUse && checkTables('0.7','',false)) {

		if ($config['prefix']=='') {  // prefixes weren't used in 0.7, so a blank target prefix means we are trying to upgrade in place, over the top of 0.8rc-1
            if (_ALLOWUPGRADEINPLACE) {
                $gotVersions=array('0.7'=>'0.7');
                $destInUse=true;
            } else {
                $goodToGo=false;
                if (_ALLOWUNINSTALL)
                    offerToDeleteOne('','0.7');
                else showNoUpgradeMsg('','0.7');
            }
        } else $gotVersions['0.7']='0.7';

		// check to see if there are any tables with that prefix, left over from a failed upgrade
		$temp =  $config['prefix']._TEMPPREFIX;
		$tmptables=array();

		foreach ($tablelist as $table)
            if (strpos($table,$temp)===0)
                $tmptables[]=$table;

        if (count($tmptables)) {
            $gotVersions['0.7']='!';
            $msg="Some temporary files from a previous aborted upgrade of 0.7 have been "
                ." left over and these are preventing you from upgrading the current "
                ." installation of 0.7 to the latest version, using this prefix.";
            if (_ALLOWUNINSTALL) echo showDeleteWarning(true)
                ,"<form action='install.php' method='post'>\n"
                ,"<p class='warning'>$msg<br />\n"
                ,"You can delete these temporary files here:"
                ,makeDeleteButton('temporary','tables')
                ,"<input type='hidden' name='tablesToDelete' value='"
                ,implode(' ',$tmptables)
                ,"'></p></form>\n";
            else echo "<p class='warning'>$msg<br />Change the installation prefix in config.php, or consult your administrator, to fix the problem.</p>\n";
        }

    }
    
    $checkState='v0.6search';
	if (!count(array_diff($tablesByVersion['0.6'],$tablelist))) {
		echo '<p>Found what looks like a version of GTD-PHP earlier than 0.7: this install program cannot upgrade this</p>';
        if ($config['prefix']=='') {   // prefixes weren't used in 0.6, so a blank target prefix means we are trying to upgrade in place, over the top of 0.8rc-1
            $goodToGo=false;
            $destInUse=true;
        }
		$gotVersions['0.6']=true;
	}

	if (_DEBUG) echo '<pre>Versions found: ',print_r($gotVersions,true),"</pre>\n";

	if ($goodToGo) {
		echo '<form action="install.php" method="post">'
			,"\n<h2>Select an upgrade or installation</h2>\n"
			,"<h3>Creating "._GTDPHP_VERSION." installation with "
			,(($config['prefix']=='')?'no prefix':"prefix '{$config['prefix']}'")
			,"</h3>\n";
		if (($destInUse || _ALLOWUNINSTALL) && count($gotVersions)) showDeleteWarning();
		echo "<table summary='table of installation alternatives'>\n"
            ,"<thead><tr><th>Use</th><th>From</th>\n";
        if (_DEBUG) echo "<th class='debug'>name</th>\n";
        if (_ALLOWUNINSTALL && count($gotVersions)) echo "<th class='warning'>Press to delete: no installation will be done; the only action that will be taken is the removal of tables</th>\n";
        echo "</tr></thead><tbody>\n";
		foreach ($gotVersions as $thisKey=>$thisVer) {
			$tmp=explode('=',$thisKey);
			$fromVer=$tmp[0];
			$fromPrefix=(empty($tmp[1]))?'':$tmp[1];
			$isUpdate= ($fromPrefix==$config['prefix']);
			$action=($fromVer==_GTD_VERSION)?"Copy":'Update';
			$msg="$action current $fromVer installation"
				.(($fromPrefix=='')?' with no prefix':" with prefix $fromPrefix");
            $key=$versions[$fromVer]['upgradepath']."=$fromPrefix";
			echo '<tr>',tabulateOption($thisVer,$key,$msg);
			if (_ALLOWUNINSTALL)
                echo "<td>",makeDeleteButton($fromPrefix,$fromVer),"</td>\n";
            echo "</tr>\n";
		}
		if (!$destInUse) {
		      // TOFIX - sample data commented out until release
            //echo "<tr>",tabulateOption('','1',"New install with sample data");
			//if (_ALLOWUNINSTALL  && count($gotVersions)) echo "<td>&nbsp;</td>\n";
            //echo "</tr>\n";
            echo "<tr>",tabulateOption('','0',"New install with empty database");
			if (_ALLOWUNINSTALL && count($gotVersions)) echo "<td>&nbsp;</td>\n";
			echo "</tr>\n";
        }
		// and finally, close the table
		echo "</tbody></table>\n<div>\n"
            ,"<input type='hidden' name='prefix' value='{$config['prefix']}' />\n"
            ,"<input type='hidden' name='db' value='{$config['db']}' />\n"
            ,"<input type='submit' name='install' value='Install' />\n";
        if ($destInUse)
            echo "<span class='warning'>Warning: this will over-write your current installation! "
                ," Make sure you have a backup of your data first! If you're not sure, "
                ," change the prefix in config.php, to create a new installation, "
                ," rather than over-writing the current one.</span>\n";
        echo "</div>\n</form>\n";
	}
	$checkState='ok';
}
/*
   ======================================================================================
   
   Do an installation / upgrade:
   
   ======================================================================================
*/
function doInstall($installType,$fromPrefix) {
	global $config,$temp,$install_success,$versions,$tablesByVersion;

    require_once "headerDB.inc.php";

	$temp='';
	register_shutdown_function('cleanup');
	if (_DEBUG) echo "<pre>Install type is: $installType<br />Source database has prefix $fromPrefix</pre>";
	echo "<p>Installing ... please wait</p>\n";
	if (version_compare(PHP_VERSION, "4.2.0",'>=')) ob_flush();
	flush();
    switch($installType){
	  case '0': // new install =============================================================================
		create_tables();
		updateVersion();
    	$install_success = true;
       // give some direction about what happens next for the user.
       $endMsg="<h2>Welcome to GTD-PHP</h2>\n
<p>You have just successfully installed GTD-PHP.\n
There are some preliminary steps you should take to set up your\n
installation for use and familiarize yourself with the system.</p>\n
<ol>\n
  <li>You need to set up <a href='editCat.php?field=category&amp;id=0'>categories</a>,
  and <a href='editCat.php?field=context&amp;id=0'>spatial</a> and\n
  <a href='editCat.php?field=time-context&amp;id=0'>time contexts</a> that suit your situation.</li>\n
   <li>Then go to the <a href='weekly.php'>weekly review</a>, and follow it,
   transferring the contents of your inboxes into gtd-php</li>\n
</ol>\n";
       // end new install
	   break;
	 case '1': // new install with sample data
		create_tables();
		fixLastModifiedColumn();
		create_data();
    	$install_success = true;
       // give some direction about what happens next for the user.
       $endMsg="<h2>Welcome to GTD-PHP</h2>\n"
               ."<p>You have just successfully installed GTD-PHP. "
               ."Sample data has been created as part of the installation.</p>\n";
		break;
	 case 'copy': // already at latest release ============================================================
    	if ($fromPrefix===$config['prefix']){
			$install_success = false;
			$endMsg="<p class='warning'>Cannot copy database to itself!</p>";
    	} else {
			create_tables();
			foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $table){
				$q = "INSERT INTO ".$config['prefix']. $table . " select * from `". $fromPrefix . $table ."`";
				send_query($q);
			}
			updateVersion();
			$install_success = true;
			$endMsg='<p>Database copied.</p>';
		}
	   break;
	 case '0.8rc-4': // ugprade from 0.8rc-4          ============================================================
    	if ($fromPrefix!==$config['prefix']){ // updating to new prefix
			foreach ($tablesByVersion[$versions['0.8rc-4']['tables']] as $key=>$table) {
				$q = "CREATE TABLE `{$config['prefix']}$table` LIKE `$fromPrefix$table`";
				send_query($q);
				$q = "INSERT INTO `{$config['prefix']}$table` SELECT * FROM `$fromPrefix$table`";
				send_query($q);
			}
		}
		create_table('tags');
		create_table('tagmap');

        $q="alter table `{$config['prefix']}itemattributes` add column `nextaction` enum('y','n') NOT NULL DEFAULT 'n';";
		send_query($q);
		$q="update `{$config['prefix']}itemattributes` set `nextaction`='y' where `itemId` in (select `nextaction` from `{$fromPrefix}nextactions`)";
		send_query($q);
		$q="drop table if exists `{$config['prefix']}nextactions`";
		send_query($q);
		
	    createrecurfields();
	    
	    $fromPrefix=$config['prefix'];
    case '0.8z.03': // TOFIX - APS intermediate upgrade
    	if ($fromPrefix!==$config['prefix']){ // updating to new prefix
			foreach ($tablesByVersion[$versions['0.8z.03']['tables']] as $key=>$table) {
				$q = "CREATE TABLE `{$config['prefix']}$table` LIKE `$fromPrefix$table`";
				send_query($q);
				$q = "INSERT INTO `{$config['prefix']}$table` SELECT * FROM `$fromPrefix$table`";
				send_query($q);
			}
		}
        $q="ALTER TABLE `{$config['prefix']}itemstatus` ADD COLUMN (
                `type` enum('m','v','o','g','p','a','r','w','i','L','C','T','x') NOT NULL DEFAULT 'i',
                categoryId int(11) UNSIGNED NOT NULL DEFAULT '0',
                `oldid` int(10) UNSIGNED DEFAULT NULL,`oldparent` int(10) UNSIGNED DEFAULT NULL),
                ADD INDEX `type` (`type`), ADD INDEX `categoryId`(`categoryId`)";
		send_query($q);
		$q="UPDATE `{$config['prefix']}itemstatus` AS its
              JOIN `{$config['prefix']}itemattributes` AS ia USING (`itemId`)
                SET its.`type`=ia.`type`, its.`categoryId`=ia.`categoryId`";
		send_query($q);

      // create list-ids and categories
        $q="INSERT INTO `{$config['prefix']}itemstatus` (`itemId`,`type`,`categoryId`,`oldid`,`dateCreated`)
            SELECT NULL,'L',`categoryId`,`listId`,CURDATE() FROM `{$config['prefix']}list`";
        send_query($q);

        // create entries for lists in the table of text descriptions too
        $q="INSERT INTO `{$config['prefix']}items` (`itemId`)
            SELECT `itemId` FROM `{$config['prefix']}itemstatus` WHERE `type`='L' ";
        send_query($q);

        // id translation, and populate text fields
        $q="UPDATE   `{$config['prefix']}items` AS i
                JOIN `{$config['prefix']}itemstatus` AS its USING (`itemId`)
                JOIN `{$config['prefix']}list` AS l ON (l.`listId`=its.`oldid`)
            SET i.`title`=  l.`title`, i.`description`=l.`description`
            WHERE its.`type`='L' ";
        send_query($q);

       // create listitem ids and links to parent Ids
        $q="INSERT INTO `{$config['prefix']}itemstatus` (`itemId`,`type`,`oldid`,`oldparent`,`dateCompleted`,`dateCreated`)
            SELECT NULL,'T',`listitemId`,`listId`,`dateCompleted`,CURDATE() FROM `{$config['prefix']}listitems`";
        send_query($q);

        // copy listitem ids into the items table
        $q="INSERT INTO `{$config['prefix']}items` (`itemId`)
            SELECT `itemId` FROM `{$config['prefix']}itemstatus` WHERE `type`='T' ";
        send_query($q);

        // populate text fields
        $q="UPDATE   `{$config['prefix']}items` AS i
                JOIN `{$config['prefix']}itemstatus` AS its USING (`itemId`)
                JOIN `{$config['prefix']}listitems` AS l ON (l.`listitemId`=its.`oldid`)
            SET i.`title`=  l.`item`, i.`description`=l.`notes`
            WHERE its.`type`='T' ";
        send_query($q);

        // populate lookup with the list / listitem relationships
        $q="INSERT INTO `{$config['prefix']}lookup` (`parentId`,`itemId`)
                SELECT itsp.`itemId` AS `parentId`, its.`itemId` AS `itemId`
                FROM `{$config['prefix']}itemstatus` AS its
                JOIN `{$config['prefix']}itemstatus` AS itsp ON (its.`oldparent`=itsp.`oldid`)
                WHERE its.`type`='T' AND itsp.`type`='L'";
        send_query($q);

        //---------------------------------------------------
        // almost the same again, for checklists
        // create checklist-ids and categories
        $q="INSERT INTO `{$config['prefix']}itemstatus` (`itemId`,`type`,`categoryId`,`oldid`,`dateCreated`)
            SELECT NULL,'C',`categoryId`,`checklistId`,CURDATE() FROM `{$config['prefix']}checklist`";
        send_query($q);

        // create entries for lists in the table of text descriptions too
        $q="INSERT INTO `{$config['prefix']}items` (`itemId`)
            SELECT `itemId` FROM `{$config['prefix']}itemstatus` WHERE `type`='C' ";
        send_query($q);

        // id translation, and populate text fields
        $q="UPDATE   `{$config['prefix']}items` AS i
                JOIN `{$config['prefix']}itemstatus` AS its USING (`itemId`)
                JOIN `{$config['prefix']}checklist` AS c ON (c.`checklistId`=its.`oldid`)
            SET i.`title`=  c.`title`, i.`description`=c.`description`
            WHERE its.`type`='C' ";
        send_query($q);

       // create checklist item ids, and mark with dummy type x for now.  Also carry over old checklist item id, and old checklist id
        $q="INSERT INTO `{$config['prefix']}itemstatus` (`itemId`,`type`,`oldid`,`oldparent`,`dateCompleted`,`dateCreated`)
            SELECT NULL,'x',`checklistitemId`,`checklistId`,IF(`checked`='y',CURDATE(),NULL),CURDATE() FROM `{$config['prefix']}checklistitems`";
        send_query($q);

        // create entries for lists in the table of text descriptions too
        $q="INSERT INTO `{$config['prefix']}items` (`itemId`)
            SELECT `itemId` FROM `{$config['prefix']}itemstatus` WHERE `type`='x' ";
        send_query($q);

        // id translation, and populate text fields
        $q="UPDATE   `{$config['prefix']}items` AS i
                JOIN `{$config['prefix']}itemstatus` AS its USING (`itemId`)
                JOIN `{$config['prefix']}checklistitems` AS l ON (l.`checklistitemId`=its.`oldid`)
            SET i.`title`=  l.`item`, i.`description`=l.`notes`,
                its.`dateCompleted`=IF(l.`checked`='y',CURDATE(),NULL)
            WHERE its.`type`='x' ";
        send_query($q);

        // populate lookup with the checklist /items relationships
        $q="INSERT INTO `{$config['prefix']}lookup` (`parentId`,`itemId`)
                SELECT itsp.`itemId` AS `parentId`, its.`itemId` AS `itemId`
                FROM `{$config['prefix']}itemstatus` AS its
                JOIN `{$config['prefix']}itemstatus` AS itsp ON (its.`oldparent`=itsp.`oldid`)
                WHERE its.`type`='x' AND itsp.`type`='C'";
        send_query($q);

        // finally mark checklist items with their proper type, T, rather than dummy type x used during upgrade
        $q="UPDATE `{$config['prefix']}itemstatus` SET `type`='T' WHERE `type`='x'";
        send_query($q);
        //---------------------------------------------------
        // all done - now clean up
        $q="ALTER TABLE `{$config['prefix']}itemattributes` DROP COLUMN `type`,
                DROP COLUMN `categoryId`, DROP INDEX `type`, DROP INDEX `categoryId`";
		send_query($q);
		$q="ALTER TABLE `{$config['prefix']}itemstatus` DROP COLUMN `oldid`,
                DROP COLUMN `oldparent`";
		send_query($q);
        $q="ALTER TABLE `{$config['prefix']}itemstatus` MODIFY COLUMN
                `type` enum('m','v','o','g','p','a','r','w','i','L','C','T') NOT NULL DEFAULT 'i'";
		send_query($q);
		$q="DROP TABLE `{$config['prefix']}list`,`{$config['prefix']}checklist`,
                `{$config['prefix']}listitems`,`{$config['prefix']}checklistitems`";
		send_query($q);
		//---------------------------------------------------
        updateVersion();
        $endMsg="<p>GTD-PHP 0.8 upgraded from 0.8rc-4 to "._GTD_VERSION." - gosh, you're brave</p>";
        $install_success = true;
        break;
	 default: // no idea what the current installation is ==========================
	 	$endMsg='<p class="error">The install script has not been able to work out'
	 		 .' whether this is an installation, or an upgrade;'
	  		 .' and if the latter, what version we are upgrading from.<br />'
			 .'Note that this installation script cannot upgrade'
			 .' an installation from gtd-php versions earlier than 0.7</p>';
		break;
    } // end of switch

	if ($install_success) {
        require_once 'headerMenu.inc.php';
        echo "<div id='main'><p>Installation completed: <a href='index.php'>Let's begin</a></p>\n";
    } else echo "<div id='main'>\n";
	echo $endMsg;
}
/*
   ======================================================================================
*/
function create_data() {
    global $config;
	// a load of inserts here to create the sample data
	$sample=fopen('gtdsample.inc.sql','r');
    if ($sample) {
        while (!feof($sample)) {
            $insert = fgets($sample, 8192);
            if (!empty($insert) && $insert[0]!=='-') {
                $insert=str_replace('gtdsample_',$config['prefix'],$insert);
                send_query($insert);
            }
        }
        fclose($sample);
    }
}
/*
   ======================================================================================
*/
function createrecurfields() {
    global $config;

    $q="ALTER TABLE `{$config['prefix']}items`
            ADD COLUMN (`recurdesc` text, `recur` text, `oldid` int(10) )";
       send_query($q);

    $q="ALTER TABLE `{$config['prefix']}itemattributes`
            ADD COLUMN (`tickledate` date )";
       send_query($q);

    // now turn suppressUntil displacements + deadlines into tickler dates

    $q="UPDATE {$config['prefix']}itemattributes
            SET `tickledate`=SUBDATE(`deadline`,`suppressUntil`)
        WHERE `suppress`='y'";
    send_query($q);

    $q="UPDATE {$config['prefix']}items AS i
            JOIN {$config['prefix']}itemattributes AS ia USING (`itemId`)
        SET i.`recurdesc`=CONCAT('+',ia.`repeat`,'d'),
            i.`recur`=CONCAT('FREQ=DAILY;INTERVAL=',ia.`repeat`)
        WHERE ia.`repeat`>0";
    send_query($q);

    // now move items from the tickler table into items

    $q="INSERT INTO `{$config['prefix']}items`
        (`title`,`description`,`recurdesc`,`recur`,`oldid`)
        SELECT `title`,`note`,
            CONCAT('+',`repeat`,'d'),
            CONCAT('FREQ=DAILY;INTERVAL=',`repeat`),
            `ticklerId`
         FROM {$config['prefix']}tickler";
    send_query($q);

    $q="INSERT INTO `{$config['prefix']}itemattributes`
            (`itemId`,`tickledate`,`type`,`isSomeday`)
        SELECT i.`itemId`,SUBDATE(t.`date`,t.`suppressUntil`),'a','n'
        FROM `{$config['prefix']}tickler`     AS t
        INNER JOIN `{$config['prefix']}items` AS i ON (i.`oldid`=t.`ticklerId`)";
    send_query($q);

    $q="INSERT INTO `{$config['prefix']}itemstatus` (`itemId`)
            SELECT i.`itemId`
        FROM `{$config['prefix']}tickler`     AS t
        INNER JOIN `{$config['prefix']}items` AS i ON (i.`oldid`=t.`ticklerId`)";
    send_query($q);

    // and finally, tidy up

    $q="ALTER TABLE `{$config['prefix']}items` DROP COLUMN `oldid`";
    send_query($q);

    $q="ALTER TABLE `{$config['prefix']}itemattributes`
            DROP COLUMN `suppress`,
            DROP COLUMN `repeat`,
            DROP COLUMN `suppressUntil`";
    send_query($q);

    drop_table("{$config['prefix']}tickler");
    drop_table("{$config['prefix']}notes");
}
/*
   ======================================================================================
*/
