<?php
/*---------------------------------------------------------------------------------
                            user-configurable options
---------------------------------------------------------------------------------*/

    /* _MAXKEYLENGTH = integer -
    sets the maximum length of indexes, used for sorting */
define("_MAXKEYLENGTH",10);

    /* _ALLOWUPGRADEINPLACE = false | true -
    allow the user to upgrade the current installation by over-writing it.
    If false, then the user should enter a new prefix in config.inc.php          */
define("_ALLOWUPGRADEINPLACE",true);

    /* _ALLOWUNINSTALL = false | true -
    allow the user to remove tables associated with a particular GTD installation */
define("_ALLOWUNINSTALL",true);


/*---------------------------------------
             Debugging options
---------------------------------------*/

    /* _DEBUG = false | true -
    show lots of debugging information during execution */
define("_DEBUG",false);

    /* _DRY_RUN = false | true - dry run won't change the database, but will
    mime all the actions that would be done: use _DEBUG true to see these */
define("_DRY_RUN",false);

/*---------------------------------------------------------------------------------
                            End of user options
---------------------------------------------------------------------------------*/


/* _USEFULLTEXT = false | true - use FULLTEXT indexes, which take up a lot of
space, but allow you to use MATCH ... AGAINST NB GTD-php does not currently use this */
define("_USEFULLTEXT",false);
include_once 'ses.inc.php';
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
$tablesByVersion=array( // NB the order of tables in these arrays is CRITICAL. they must be consistent across the 0.8 sub-versions
    // we don't offer an upgrade path from 0.5.  Any 0.5 installations should first upgrade to 0.7, then run this routine
    '0.5'     => array('context','goals','maybe','maybesomeday','nextactions','projects','reference','waitingon')
    // 0.7 is the earliest version that we can upgrade from, here
    ,'0.7'     => array('categories','checklist','checklistItems','context','goals','itemattributes','items','itemstatus','list','listItems','nextactions','projectattributes','projects','projectstatus','tickler','timeitems')
    // 0.8rc-1 was a major change, with goals, actions and projects all being merged into the items files
    ,'0.8rc-1' => array('categories','checklist','checklistItems','context','itemattributes','items','itemstatus','list','listItems','lookup','nextactions','tickler','timeitems','version')
    // 0.8rc-2 added the preferences table
    ,'0.8rc-3' => array('categories','checklist','checklistItems','context','itemattributes','items','itemstatus','list','listItems','lookup','nextactions','tickler','timeitems','version','preferences')
    // 0.8rc-4 saw all table names being standardised to lower case:
    ,'0.8rc-4' => array('categories','checklist','checklistitems','context','itemattributes','items','itemstatus','list','listitems','lookup','nextactions','tickler','timeitems','version','preferences')
    // 0.8z.04 - tagmap table introduced;     checklist,checklistitems,list,listitems,nextactions tables removed;   items,itemstatus,itemattributes reworked
    ,'0.8z.04'   => array('categories','context','itemattributes','items','itemstatus','lookup','tagmap','timeitems','version','preferences')
    // 0.8z.05 - preferences table revised, no change to list of tables
    // 0.8z.06 - perspectives and perspectivemap tables added
    ,'0.8z.06'   => array('categories','context','itemattributes','items','itemstatus','lookup','perspectivemap','perspectives','tagmap','timeitems','version','preferences')
    // 0.8z.07 - merged itemattributes into itemstatus, and dropped perspectives and perspectivemap
    ,'0.8z.07'   => array('categories','context','items','itemstatus','lookup','tagmap','timeitems','version','preferences')
    );

$versions=array(
    '0.5'=>      array(      'tables'=>'0.5',
                           'database'=>'0.5',
                        'upgradepath'=>'X')
                        
    ,'0.7'=>     array(      'tables'=>'0.7',
                           'database'=>'0.7',
                        'upgradepath'=>'0.7')
                        
    ,'0.8rc-1'=> array(      'tables'=>'0.8rc-1',
                           'database'=>'0.8rc-1',
                        'upgradepath'=>'0.8rc-1')
                        
    ,'0.8rc-3'=> array(      'tables'=>'0.8rc-3',
                           'database'=>'0.8rc-3',
                        'upgradepath'=>'0.8rc-3')
                        
    ,'0.8rc-4'=> array(      'tables'=>'0.8rc-4',
                           'database'=>'0.8rc-4',
                        'upgradepath'=>'0.8rc-4')
                        
    ,'0.8z.04'=> array(      'tables'=>'0.8z.04',
                           'database'=>'0.8z.04',
                        'upgradepath'=>'0.8z.04')
                        
    ,'0.8z.05'=> array(      'tables'=>'0.8z.04',
                           'database'=>'0.8z.05',
                        'upgradepath'=>'0.8z.05')
                        
    ,'0.8z.06'=> array(      'tables'=>'0.8z.06',
                           'database'=>'0.8z.06',
                        'upgradepath'=>'0.8z.06')
                        
    ,'0.8z.07'=> array(      'tables'=>'0.8z.07',
                           'database'=>'0.8z.07',
                        'upgradepath'=>'0.8z.07')
                        
    ,'0.8z.08'=> array(      'tables'=>'0.8z.07',
                           'database'=>'0.8z.08',
                        'upgradepath'=>'0.8z.08')

    ,'0.8z.09'=> array(      'tables'=>'0.8z.07',
                           'database'=>'0.8z.09',
                        'upgradepath'=>'copy')

    );
/*
  end of global variables
 ============================================================================*/

// initialise variables used for checking what this run is supposed to do
$pagename='install';
$onInstall=true;
$areUpdating=false;
$wantToDelete=false;
$areDeleting=false;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
		  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>gtd-php installer</title>
<?php if (_DEBUG) { ?>
    <style type='text/css'>pre,.debug {}</style>
    <script type='text/javascript'>
    GTD={debugKey:'h'};
    </script>
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="gtdfuncs.js"></script>
<?php } ?>
<link rel="stylesheet" href="themes/default/style.css" type="text/css"/>
<link rel="stylesheet" href="themes/default/style_screen.css" type="text/css" media="screen" />
</head>
<body>
<?php include 'showMessage.inc.php'; ?>
<h2>This is the gtd-php installer</h2>
<?php

if (_DEBUG) echo "<pre class='debug'>"
	,(_DRY_RUN)?'Executing Dry run - no tables will be amended in this run':'This is a <b>live</b> run'
	,'<br />POST variables: ',print_r($_POST,true),"</pre>\n";

if (isset($_POST['cancel']))
    ; // we've cancelled an over-write or a delete, so go back to the installation menu
elseif (isset($_POST['install'])) {
    $toPrefix=$_POST['prefix'];
    $toDB=$_POST['db'];
    // check to see whether the prefix in config.inc.php hsa been changed between POST and now
    require 'config.inc.php';
    if ($toPrefix===$config['prefix'] && $toDB===$config['db']) {
        $areUpdating=true; // ok, it's safe to update.
    } else {
        echo "<p class='error warning'>config.inc.php has changed during the installation process. "
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
?>

</div>
</body>
</html>
<?php
return;
/*
   ======================================================================================
   end of main output.


   Function to decide what installation action(s) to offer to the user:
   ======================================================================================
*/
function checkInstall() {
	global $versions,$tablelist,$checkState,$tablesByVersion;
    register_shutdown_function('failDuringCheck');
	$goodToGo=true; // assume we'll be able to upgrade, until we find something to stop us
    require_once 'gtdfuncs.inc.php';

    // check for register globals - instruct user to turn it off in .htaccess if it's on
	$checkState='preflight';
    
    echo "<p>Read the <a href='INSTALL'>INSTALL</a> file for information on using this install/upgrade program</p>\n",checkRegisterGlobals();

	if (_DEBUG) {
		$included_files = get_included_files();
		echo "<pre class='debug'>Included files:",print_r($included_files,true),'</pre>';
	}
    // check the config file
	$checkState='config';
	include_once 'config.inc.php';
	if (_DEBUG) {
        $configsav=$config;
        $configsav['pass']='********';
        echo "<p class='debug'>Got config.inc.php:</p><pre class='debug'>",print_r($configsav,true),'</pre>';
    }
	if (empty($config['db'])) {
        echo "<p class='warning'>Fatal Error: no valid config.inc.php file has been found. "
            ," you should update the config.inc.php file, based on the config.sample.php "
            ," file supplied with GTD-PHP, before using this installer.</p>\n";
        exit;
    }

    // try to open the database
    $checkState='db';
	require_once 'headerDB.inc.php';
	// got a database; now get a list of its tables
	$checkState='tables';
	$tablelist = getDBTables($config['db']);
	$nt=count($tablelist);
	if (_DEBUG) echo "<pre class='debug'>Number of tables: $nt<br />",print_r($tablelist,true),"</pre>";

	// validate the prefix
	$checkState='prefix';
	if (!checkPrefix($config['prefix'])) exit; // invalid prefix = fatal error

	/*
		Build an array of current installlations,
		and offer choice of upgrading from one of these, or doing a fresh install
	*/
	$checkState='installations';
	$gotVersions=array();
	$destInUse=false;
	$gotPrefixes=(preg_grep("/.*version$/",$tablelist));
	if (_DEBUG) echo "<pre class='debug'>Version tables:",print_r($gotPrefixes,true),'</pre>';
	foreach ($gotPrefixes as $thisVersionTable) {
		$thisPrefix=substr($thisVersionTable,0,-7);
		$thisVer=checkPrefixedTables($thisPrefix);
		if ($thisVer!='') $gotVersions["{$thisVer}={$thisPrefix}"]=$thisVer;
		if ($thisPrefix==$config['prefix']) { // we have an installation already using our target prefix
            $destInUse=true;
            if ($thisVer==_GTD_VERSION) {     // and it's the latest version - so no upgrade needed!
    			// this destination is already in use - let's go!
    			$title='Installation is already up to date';
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
            else echo "<p class='warning'>$msg<br />Change the installation prefix in config.inc.php, or consult your administrator, to fix the problem.</p>\n";
        }

    }

    $checkState='v0.5search';
	if (!count(array_diff($tablesByVersion['0.5'],$tablelist))) {
		echo '<p>Found what looks like a version of GTD-PHP earlier than 0.7: this install program cannot upgrade this</p>';
        if ($config['prefix']=='') {   // prefixes weren't used before 0.8, so a blank target prefix means we are trying to upgrade in place
            $goodToGo=false;
            $destInUse=true;
        }
		$gotVersions['0.5']=true;
	}

	if (_DEBUG) echo "<pre class='debug'>Versions found: ",print_r($gotVersions,true),"</pre>\n";

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
            echo "<tr>"
                ,tabulateOption('','1',"New install with sample data")
                ,(_ALLOWUNINSTALL && count($gotVersions)) ? "<td>&nbsp;</td>\n" : ''
                ,"</tr>\n"
                ,"<tr>"
                ,tabulateOption('','0',"New install with empty database")
                ,(_ALLOWUNINSTALL && count($gotVersions)) ? "<td>&nbsp;</td>\n" : ''
                ,"</tr>\n";
        }
		// and finally, close the table
		echo "</tbody></table>\n<div>\n"
            ,"<input type='hidden' name='prefix' value='{$config['prefix']}' />\n"
            ,"<input type='hidden' name='db' value='{$config['db']}' />\n"
            ,"<input type='submit' name='install' value='Install' />\n";
        if ($destInUse)
            echo "<span class='warning'>Warning: this will over-write your current installation! "
                ,"<br/>Make sure you have a "
                ," <a href='backup.php?prefix={$config['prefix']}'>backup</a> "
                ," of your data first! (click on the link to save a copy locally)<br/> "
                ," If you're not sure, change the prefix in config.inc.php, "
                ," to create a new installation, "
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
	global $temp,$install_success,$versions,$tablesByVersion;
	require 'config.inc.php';
    require_once "headerDB.inc.php";
    $toPrefix=$config['prefix'];
	$endMsg=$temp='';
	register_shutdown_function('cleanup');
	if (_DEBUG) echo "<pre class='debug'>Install type is: $installType<br />Source database has prefix $fromPrefix</pre>";
	if ($installType=='0' || $installType=='1')
        echo "<p>Installing ... please wait</p>\n";
    else
        echo "<p>Upgrading will take place in several stages ... please wait</p>\n";
    flushAndResetTimer();
	//---------------------------------------------------
    switch($installType){
		//---------------------------------------------------
	  case '0': // new install
		create_tables($toPrefix);
		updateVersion($toPrefix);
		importOldConfig();
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
	   
		//---------------------------------------------------
	 case '1': // new install with sample data
		create_tables($toPrefix);
		updateVersion($toPrefix);
		create_data($toPrefix);
        importOldConfig();
    	$install_success = true;
        // give some direction about what happens next for the user.
        $endMsg="<h2>Welcome to GTD-PHP</h2>\n"
               ."<p>You have just successfully installed GTD-PHP. "
               ."Sample data has been created as part of the installation.</p>\n";
		break;
		//---------------------------------------------------

	 case 'copy': // already at latest release
    	if ($fromPrefix===$toPrefix){
			$install_success = false;
			$endMsg="<p class='warning'>Cannot copy database to itself!</p>";
    	} else {
			create_tables($toPrefix);
			foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $table){
				$q = "INSERT INTO `$toPrefix$table` SELECT * FROM `$fromPrefix$table`";
				send_query($q);
			}
			updateVersion($toPrefix);
			$install_success = true;
			$endMsg='<p>Database copied.</p>';
		}
        break;
		//---------------------------------------------------

    case '0.7':     // ugprade from 0.7
        echo '<h2>Upgrading database from 0.7 to 0.8rc-4</h2>';
        up07TO08rc4('',$toPrefix);
        $fromPrefix=$toPrefix;
        // deliberately flows through to next case
		//---------------------------------------------------

	case '0.8rc-4': // ugprade from 0.8rc-4
        copyToNewPrefix('0.8rc-4','0.8z.04',$fromPrefix,$toPrefix);
        up08rc4TO08z04($fromPrefix,$toPrefix);
		$fromPrefix=$toPrefix; // must only copy to new prefix ONCE, so prevent it happening again
		// deliberately flows through to next case
		//---------------------------------------------------
		
	 case '0.8z.04': // ugprade from 0.8z.04
        copyToNewPrefix('0.8z.04','0.8z.05',$fromPrefix,$toPrefix);
        up08z04TO08z05($fromPrefix,$toPrefix);
		// deliberately flows through to next case
		//---------------------------------------------------

	 case '0.8z.05': // ugprade from 0.8z.05
        //copyToNewPrefix('0.8z.05','0.8z.06',$fromPrefix,$toPrefix);
		$skip08z06=true;
		// deliberately flows through to next case
		//---------------------------------------------------

	 case '0.8z.06': // ugprade from 0.8z.06
        copyToNewPrefix('0.8z.05','0.8z.07',$fromPrefix,$toPrefix);
		if (!empty($skip08z06)) {
            drop_table("{$toPrefix}perspectives");
            drop_table("{$toPrefix}perspectivemap");
        }
        up08z05TO08z07($fromPrefix,$toPrefix);
		// deliberately flows through to next case
		//---------------------------------------------------

	 case '0.8z.07': // ugprade from 0.8z.07
        copyToNewPrefix('0.8z.07','0.8z.08',$fromPrefix,$toPrefix);
        up08z07TO08z08($fromPrefix,$toPrefix);

		// deliberately flows through to next case
		//---------------------------------------------------

	 case '0.8z.08': // ugprade from 0.8z.08
        copyToNewPrefix('0.8z.08','0.8z.09',$fromPrefix,$toPrefix);
        up08z08TO08z09($fromPrefix,$toPrefix);

		//---------------------------------------------------
        // end of chained upgrade process
        updateVersion($toPrefix);
        $endMsg.="<p>GTD-PHP database upgraded to "._GTD_VERSION."</p>";
        $install_success=true;
        break;
		//---------------------------------------------------

	 default: // no idea what the current installation is
	 	$endMsg='<p class="error">The install script has not been able to work out'
	 		 .' whether this is an installation, or an upgrade;'
	  		 .' and if the latter, what version we are upgrading from.<br />'
			 .'Note that this installation script cannot upgrade'
			 .' an installation from gtd-php versions earlier than 0.7</p>';
		break;
    } // end of switch
	//---------------------------------------------------

	if ($install_success) {
        echo "<h2>Final stage: cleaning the data</h2>";
        flushAndResetTimer();
        fixData($toPrefix);
        $title='Installation Complete';
        require_once 'headerMenu.inc.php';
        echo "<div id='main'>";
        echo checkRegisterGlobals(),
            "<p>Installation completed:",
            "<a href='preferences.php'>Now check the preferences</a>,",
            " and make any necessary changes</p></div>";
        /*
            Force the session to restart, to ensure that
            session variables will be initialised correctly,
            for the new installation.
        */
        $_SESSION = array();
        session_destroy(); // TOFIX - seems to then go into next screen without a theme!
    } else echo "<div id='main'>";
    echo $endMsg;
}
/*
   ======================================================================================
    utility functions
   ======================================================================================
*/
function flushAndResetTimer() {
	@set_time_limit(120); // upgrades can take a long time!
	@ob_flush();
	flush();
}
/*
   ======================================================================================
*/
function create_data($toPrefix) {
	// a load of inserts here to create the sample data
	$sample=fopen('gtdsample.inc.sql','r');
    if ($sample) {
        while (!feof($sample)) {
            $insert = fgets($sample, 8192);
            if (!empty($insert) && $insert[0]!=='-') {
                $insert=str_replace('gtdsample_',$toPrefix,$insert);
                send_query($insert);
            }
        }
        fclose($sample);
    }
}
/*
   ======================================================================================
*/
function failDuringCheck() {
    ignore_user_abort(true); // don't want to abort, while already handling an abort!
    global $checkState;
    switch ($checkState) {
        case 'ok':return; // reached end ok, so nothing to do
        case 'in': // barely started
            echo "<p class='error'>Unable to start the installation pre-flight checks</p>";
            break;
        case 'config': // no valid config.inc.php
            echo "<p class='error'>No valid config.inc.php file found.<br />"
    			     ,"Copy the config.sample.php file to config.inc.php, and set the MySQL parameters. "
               ,"Here's the <a href='http://www.gtd-php.com/Users/Config09'>online help</a>.</p>\n";
            break;
        case 'db': // failed during attempt to open database
            echo "<p class='error'>"
                ,"Please check your config.inc.php file. gtd-php has been unable to connect to your database."
                ," It may be that the database is not yet created, "
                ," or that the database user name or password in the config.inc.php file are incorrect."
                ," Either create the database, adjust the user permissions, or set the username and password correctly, "
                ," (contact your administrator if you don't know how to do this)"
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
*/
function copyToNewPrefix($ver,$tover,&$fromPrefix,$toPrefix) {
    echo "<h2>Upgrading database from version $ver to $tover</h2>";
    flushAndResetTimer();
    if ($fromPrefix===$toPrefix) return false; // nothing to do!

    global $tablesByVersion,$versions;
	foreach ($tablesByVersion[$versions[$ver]['tables']] as $key=>$table) {
		send_query("CREATE TABLE `{$toPrefix}$table` LIKE `$fromPrefix$table`");
		send_query("INSERT INTO `{$toPrefix}$table` SELECT * FROM `$fromPrefix$table`");
	}
	$fromPrefix=$toPrefix; // must only copy to new prefix ONCE, so prevent it happening again
	return true; // completed successfully
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
function send_query($q,$dieOnFail=true) {
    global $rollback;
   	if (_DEBUG) echo "<p class='debug'>{$q}</p>\n";
    if (_DRY_RUN)
        $result=true;
    else
		$result = rawQuery($q);
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
            echo "<p class='error'>Fatal error: Failed to do database query: '$q'<br />",mysql_error(),"</p>\n";
            die("<p class='error'>Installation terminated</p>");
        }elseif (_DEBUG)
            echo "<p class='warning debug'>Warning: Failed to do database query: '$q'<br />",mysql_error(),"</p>\n";
    }
    return($result);
}
/*
   ======================================================================================
*/
function updateVersion($toPrefix) {
    $q="TRUNCATE `{$toPrefix}version`";
    send_query($q,false);
    $q="INSERT INTO `{$toPrefix}version` VALUES('"._GTD_VERSION."',NULL)";
    send_query($q,false);
}
/*
   ======================================================================================
*/
function cleanup($message='cleaning up the mess') {
    ignore_user_abort(true); // don't want to abort, while already handling an abort!
	global $rollback,$install_success;
	if ($install_success) return $message;

	foreach($rollback as $query) send_query($query,false);
	echo "<p class='error'>Installation aborted, and cleanup done - <a href='install.php'>return to main install screen</a></p></div></body></html>";
	return $message;
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
     Incremental upgraders
   ======================================================================================
*/
function up07TO08rc4($fromPrefix,$toPrefix) {
    flushAndResetTimer();
    global $tablesByVersion,$versions;
    // temp table prefix
    $temp =  _TEMPPREFIX;
    $tempPrefix=$toPrefix.$temp;

    // categories---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}categories` (
           `categoryId` int(10) unsigned NOT NULL auto_increment,
           `category` text NOT NULL,
           `description` text,
           PRIMARY KEY  (`categoryId`), ".
            _FULLTEXT." KEY `category` (`category`"._INDEXLEN."), ".
            _FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    send_query($q);
    $q="INSERT INTO {$tempPrefix}categories select * from `categories`";
    send_query($q);

    // checklist---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}checklist` (
            `checklistId` int(10) unsigned NOT NULL auto_increment,
            `title` text NOT NULL,
            `categoryId` int(10) unsigned NOT NULL default '0',
            `description` text,
            PRIMARY KEY  (`checklistId`))"; // no need to do additional keys, as we will be discarding this table later
    send_query($q);
    $q="INSERT INTO {$tempPrefix}checklist  SELECT * FROM `checklist`";
    send_query($q);

    // checklistitems---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}checklistitems` (
            `checklistItemId` int(10) unsigned NOT NULL auto_increment,
            `item` text NOT NULL,
            `notes` text,
            `checklistId` int(10) unsigned NOT NULL default '0',
            `checked` enum ('y', 'n') NOT NULL default 'n',
            PRIMARY KEY (`checklistItemId`))";  // no need to do additional keys, as we will be discarding this table later
    send_query($q);
    $q="INSERT INTO {$tempPrefix}checklistitems  SELECT * FROM `checklistItems`";
    send_query($q);

    // context---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}context` (
            `contextId` int(10) unsigned NOT NULL auto_increment,
           `name` text NOT NULL,
           `description` text,
           PRIMARY KEY  (`contextId`), ".
           _FULLTEXT." KEY `name` (`name`"._INDEXLEN."), ".
           _FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    send_query($q);
    $q="INSERT INTO {$tempPrefix}context SELECT * FROM `context`";
    send_query($q);

    // goals---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}goals` (
           `id` int(11) NOT NULL auto_increment,
           `goal`   longtext,
           `description`   longtext,
           `created` date default NULL,
           `deadline` date default NULL,
           `completed` date default NULL,
           `type` enum('weekly', 'quarterly') default NULL ,
           `projectId` int(11) default NULL, PRIMARY KEY (`id`) )";
    send_query($q);
    $q="INSERT INTO {$tempPrefix}goals  SELECT * FROM `goals`";
    send_query($q);

    // remove unwanted line breaks from title field - allowed in 0.7, but not in 0.8 or later
    $q="UPDATE `{$tempPrefix}goals` SET `goal`=replace(replace(`goal`,'\r',' '),'\n',' ')";
    send_query($q);

    // itemattributes---------------------------------------------
    $q="create table `{$tempPrefix}itemattributes` (";
    $q.="`itemId` int(10) NOT NULL auto_increment, ";
    $q.="`type` enum('a', 'r', 'w') NOT NULL default 'a' ,";
    $q.="`projectId` int(10) unsigned NOT NULL default '0', ";
    $q.="`contextId` int(10) unsigned NOT NULL default '0', ";
    $q.="`timeframeId` int(10) unsigned NOT NULL default '0', ";
    $q.="`deadline` date default NULL , ";
    $q.="`repeat` int( 10 ) unsigned NOT NULL default '0', ";
    $q.=" `suppress` enum( 'y', 'n' ) NOT NULL default 'n', ";
    $q.="`suppressUntil` int( 10 ) unsigned default NULL , ";
    $q.="PRIMARY KEY ( `itemId` ) , KEY `projectId` ( `projectId` ) ,";
    $q.="KEY `contextId` ( `contextId` ) , ";
    $q.="KEY `suppress` ( `suppress` ) , KEY `type` ( `type` ) ,";
    $q.=" KEY `timeframeId` ( `timeframeId` ) )";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}itemattributes  SELECT * FROM `itemattributes`";
    send_query($q);

    // items---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}items` (
            `itemId` int(10) unsigned NOT NULL auto_increment,
            `title` text NOT NULL,
            `description` longtext,
            `desiredOutcome` text,
            PRIMARY KEY  (`itemId`), ".
            _FULLTEXT." KEY `title` (`title`"._INDEXLEN."), ".
            _FULLTEXT." KEY `desiredOutcome` (`desiredOutcome`"._INDEXLEN."), ".
            _FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    send_query($q);
    $q="INSERT INTO {$tempPrefix}items (itemId,title,description) SELECT * from `items` ";
    send_query($q);

    // itemstatus---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}itemstatus` ( ";
    $q.="`itemId` int( 10 ) unsigned NOT NULL auto_increment ,";
    $q.=" `dateCreated` date default NULL, ";
    $q.="`lastModified` timestamp default '"._DEFAULTDATE."' ,";
    $q.="`dateCompleted` date default NULL , ";
    $q.=" `completed` int( 10 ) unsigned default NULL , ";
    $q.="PRIMARY KEY ( `itemId` ) ) ";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}itemstatus SELECT * FROM `itemstatus`";
    send_query($q);

    // list---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}list` (
            `listId` int(10) unsigned NOT NULL auto_increment,
            `title` text NOT NULL,
            `categoryId` int(10) unsigned NOT NULL default '0',
            `description` text,
            PRIMARY KEY  (`listId`) )";
    send_query($q);
    $q="INSERT INTO {$tempPrefix}list  SELECT * FROM `list` ";
    send_query($q);

    // listitems---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}listitems` (
            `listItemId` int(10) unsigned NOT NULL auto_increment,
            `item` text NOT NULL,
            `notes` text,
            `listId` int(10) unsigned NOT NULL default '0',
            `dateCompleted` date default NULL,
            PRIMARY KEY  (`listItemId`) )";
    send_query($q);
    
    $q="INSERT INTO {$tempPrefix}listitems SELECT * FROM `listItems`";
    send_query($q);

    // nextactions---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}nextactions` (
            `projectId` int( 10 ) unsigned NOT NULL default '0',
            `nextaction` int( 10 ) unsigned NOT NULL default '0',
            PRIMARY KEY ( `projectId` , `nextaction` ) )";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}nextactions SELECT * FROM `nextactions`";
    send_query($q);

    // -------------------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}projectattributes` ( ";
    $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
    $q.=" `categoryId` int( 10 ) unsigned NOT NULL default '1', ";
    $q.="`isSomeday` enum( 'y', 'n' ) NOT NULL default 'n', ";
    $q.=" `deadline` date default NULL , `repeat` int( 11 ) unsigned NOT NULL default '0', ";
    $q.="`suppress` enum( 'y', 'n' ) NOT NULL default 'n', ";
    $q.=" `suppressUntil` int( 10 ) unsigned default NULL , PRIMARY KEY (`projectId` ) ) ";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}projectattributes SELECT * FROM `projectattributes` ";
    send_query($q);

    // -------------------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}projects` ( ";
    $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
    $q.=" `name` text NOT NULL , `description` text, `desiredOutcome` text, ";
    $q.="PRIMARY KEY ( `projectId` ) ) ";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}projects SELECT * FROM `projects` ";
    send_query($q);

    // -------------------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}projectstatus` (
            `projectId` int( 10 ) unsigned NOT NULL auto_increment ,
            `dateCreated` date  default NULL, `lastModified` timestamp default '"._DEFAULTDATE."' ,
            `dateCompleted` date default NULL , PRIMARY KEY (`projectId` ) ) ";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}projectstatus SELECT * FROM `projectstatus`";
    send_query($q);

    // -------------------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}tickler` (
            `ticklerId` int(10) unsigned NOT NULL auto_increment,
            `date` date  default NULL,
            `title` text NOT NULL,
            `note` longtext,
            `repeat` int(10) unsigned NOT NULL default '0',
            `suppressUntil` int(10) unsigned NOT NULL default '0',
            PRIMARY KEY  (`ticklerId`) )";
    send_query($q);
    $q="INSERT INTO {$tempPrefix}tickler (ticklerId,date,title,note) SELECT * FROM `tickler`";
    send_query($q);

    // -------------------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}timeitems` ( ";
    $q.="`timeframeId` int( 10 ) unsigned NOT NULL auto_increment , ";
    $q.=" `timeframe` text NOT NULL , `description` text, PRIMARY KEY (
            `timeframeId` ) )" . _CREATESUFFIX;
    send_query($q);

    $q="INSERT INTO {$tempPrefix}timeitems SELECT * FROM `timeitems`";
    send_query($q);

    // lookup table---------------------------------------------
    $q="CREATE TABLE `{$tempPrefix}lookup` (
            `parentId` int(11) NOT NULL default '0',
            `itemId` int(10) unsigned NOT NULL default '0',
            PRIMARY KEY  (`parentId`,`itemId`) )";
    send_query($q);
    $q="INSERT INTO {$tempPrefix}lookup (`parentId`,`itemId`) SELECT `projectId`,`itemId`
            FROM `itemattributes`";
    send_query($q);

    // -------------------------------------------------------

    flushAndResetTimer();
    
    $q="ALTER TABLE {$tempPrefix}itemattributes DROP `projectId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes ADD `isSomeday`
            ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n' AFTER `type`, ADD `categoryId`
            INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `isSomeday` ";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes ADD INDEX (`isSomeday` )";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes ADD INDEX (`categoryId`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus DROP `completed`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes CHANGE `type`
            `type` ENUM( 'm', 'v', 'o', 'g', 'p', 'a', 'r', 'w', 'i' ) NOT NULL
            DEFAULT 'i'";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}projectattributes ADD `type` ENUM(
            'p' ) NOT NULL DEFAULT 'p' AFTER `projectId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}nextactions DROP PRIMARY KEY, ADD
            PRIMARY KEY ( `projectId` , `nextaction`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}nextactions CHANGE `projectId`
            `parentId` INT( 10 ) UNSIGNED NOT NULL DEFAULT'0'";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items ADD `prikey` INT UNSIGNED
            NOT NULL FIRST";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes ADD `prikey` INT
            UNSIGNED NOT NULL FIRST";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus ADD `prikey` INT
            UNSIGNED NOT NULL FIRST";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items CHANGE `itemId` `itemId`
            INT( 10 ) UNSIGNED NOT NULL";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes CHANGE `itemId`
            `itemId` INT( 10 ) UNSIGNED NOT NULL";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus CHANGE `itemId`
            `itemId` INT( 10 ) UNSIGNED NOT NULL";
    send_query($q);

    // migrate actions---------------------------------------------

    flushAndResetTimer();

    $maxnum = " +(
        CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
        ELSE (SELECT MAX(`id`) FROM `goals`)
        END
        )+(
        CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
        ELSE (SELECT MAX(`projectId`) FROM `projects`)
        END
        )";

    $q=" UPDATE {$tempPrefix}items SET `prikey`=`itemId`" . $maxnum;
    send_query($q);

    $q="UPDATE {$tempPrefix}itemattributes SET `prikey`=`itemId`" . $maxnum;
    send_query($q);

    $q="UPDATE `{$tempPrefix}itemstatus` SET `prikey`=`itemId`" . $maxnum;
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items DROP PRIMARY KEY, ADD
            PRIMARY KEY (`prikey`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes DROP PRIMARY KEY,
            ADD PRIMARY KEY (`prikey`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus DROP PRIMARY KEY, ADD
            PRIMARY KEY (`prikey`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items DROP `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes DROP `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus DROP `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items CHANGE `prikey` `itemId`
            INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes CHANGE `prikey`
            `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus CHANGE `prikey`
            `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
    send_query($q);

    $q="DELETE FROM {$tempPrefix}nextactions WHERE `nextaction`=0";
    send_query($q);

    $q="UPDATE `{$tempPrefix}nextactions` SET `nextaction`=`nextaction`" . $maxnum;
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}lookup ADD `prikey` INT UNSIGNED NOT NULL";
    send_query($q);

    $q="UPDATE `{$tempPrefix}lookup` SET `prikey` =`itemId`" . $maxnum;
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}lookup DROP PRIMARY KEY, ADD
            PRIMARY KEY (`parentId` , `prikey`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}lookup DROP `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}lookup CHANGE `prikey` `itemId`
            INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
    send_query($q);

    // Migrate Projects---------------------------------------------

    flushAndResetTimer();

    $q="INSERT INTO {$tempPrefix}items
            (`itemId`,`title`,`description`,`desiredOutcome`) SELECT
            `projectId`,`name`,`description`,`desiredOutcome` FROM `projects`";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}itemattributes(`itemId`,`type`,
            `categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil`)
            SELECT `projectId`,'p',`categoryId`,`isSomeday`,`deadline`,`repeat`,
            `suppress`,`suppressUntil` FROM `projectattributes`";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}itemstatus
            (`itemId`,`dateCreated`, `lastModified`, `dateCompleted`) SELECT
            `projectId`,`dateCreated`, `lastModified`, `dateCompleted` FROM
            `projectstatus`";
    send_query($q);

    // Migrate Goals---------------------------------------------
    flushAndResetTimer();
    $maxnum = "+(
        CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
        ELSE (SELECT MAX(`projectId`) FROM `projects`)
        END)";

    $q="ALTER TABLE {$tempPrefix}goals ADD `prikey` INT
            UNSIGNED NOT NULL FIRST";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}goals CHANGE `id` `id` INT( 10 )
            UNSIGNED NOT NULL";
    send_query($q);

    $q="UPDATE {$tempPrefix}goals SET `prikey`=`id`" . $maxnum;
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}goals DROP PRIMARY KEY, ADD
            PRIMARY KEY (`prikey`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}goals DROP `id`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}goals CHANGE `prikey` `id` INT( 10
            ) UNSIGNED NOT NULL DEFAULT '0'";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}timeitems ADD `type` ENUM( 'v',
            'o', 'g', 'p', 'a' ) NOT NULL DEFAULT 'a'";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}timeitems ADD INDEX ( `type` )";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}goals ADD `timeframeId` INT UNSIGNED NOT NULL";
    send_query($q);

    $q="UPDATE `{$tempPrefix}goals` SET `timeframeId`= (1 + (
            CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
            ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
            END )) WHERE `type`='weekly'";
    send_query($q);

    $q="UPDATE `{$tempPrefix}goals` SET `timeframeId`= (2 + (
            CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
            ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
            END )) WHERE `type`='quarterly'";
    send_query($q);

    flushAndResetTimer();

    $q="INSERT INTO {$tempPrefix}items
            (`itemId`,`title`,`description`) SELECT `id`,`goal`,`description`
            FROM `{$tempPrefix}goals`";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}itemattributes
            (`itemId`,`type`,`timeframeId`,`deadline`) SELECT
            `id`, 'g',`timeframeId`, `deadline` FROM `{$tempPrefix}goals`";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}itemstatus
            (`itemId`,`dateCreated`, `dateCompleted`) SELECT `id`, `created`,
            `completed` FROM `{$tempPrefix}goals`";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}lookup (`parentId`,`itemId`)
            SELECT `projectId`,`id` FROM `goals`";
    send_query($q);

    $q="INSERT INTO {$tempPrefix}timeitems ( `timeframeId` ,
            `timeframe` , `description` , `type` ) VALUES (NULL , 'Weekly', NULL,
            'g'), (NULL , 'Quarterly', NULL , 'g')";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items  ORDER BY `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes  ORDER BY `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus  ORDER BY `itemId`";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes ADD INDEX (`isSomeday`)";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}items CHANGE `itemId` `itemId`
            INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes CHANGE `itemId`
            `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemattributes CHANGE `itemId`
            `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}itemstatus CHANGE `itemId`
            `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}timeitems ADD "._FULLTEXT." KEY (`timeframe`"._INDEXLEN.")";
    send_query($q);

    $q="ALTER TABLE {$tempPrefix}timeitems ADD "._FULLTEXT." KEY (`description`"._INDEXLEN.")";
    send_query($q);

    foreach (array('projectattributes','projects','projectstatus','goals') as $temptable)
        drop_table($tempPrefix.$temptable);

    create_table($tempPrefix,'version');
    create_table($tempPrefix,'preferences');

    // this is the point of no return - if anything goes wrong after here, the database will be really badly broken!
    flushAndResetTimer();
    if ($toPrefix=='') {
    	drop_table('waitingOn');        // drop waitingOn, in case left over from 0.5
        foreach ($tablesByVersion[$versions[$installType]['tables']] as $temptable)
            drop_table($temptable);
    }

    foreach ($tablesByVersion[$versions['0.8rc-4']['tables']] as $temptable) {
        $q="RENAME TABLE `{$tempPrefix}{$temptable}` TO `{$toPrefix}{$temptable}`";
        send_query($q);
    }
    return true;
}
/*
   ======================================================================================
*/
function up08rc4TO08z04($fromPrefix,$toPrefix) {
	create_table($toPrefix,'tagmap');

    $q="ALTER TABLE `{$toPrefix}itemattributes` ADD COLUMN `nextaction` ENUM('y','n') NOT NULL DEFAULT 'n';";
	send_query($q);

    $q="UPDATE `{$toPrefix}itemattributes` SET `nextaction`='y' WHERE `itemId` IN (SELECT `nextaction` FROM `{$fromPrefix}nextactions`)";
	send_query($q);

    $q="drop table if exists `{$toPrefix}nextactions`";
	send_query($q);

    /*
        --------------------------------------------------------------------------
        new database fields for rfc2445 (iCal) recurrence patterns
    */
    flushAndResetTimer();
    $q="ALTER TABLE `{$toPrefix}items`
            ADD COLUMN (`recurdesc` text, `recur` text, `oldid` int(10) )";
    send_query($q);

    $q="ALTER TABLE `{$toPrefix}itemattributes`
            ADD COLUMN (`tickledate` date )";
    send_query($q);

    // turn suppressUntil displacements + deadlines into tickler dates
    $q="UPDATE {$toPrefix}itemattributes
            SET `tickledate`=SUBDATE(`deadline`,`suppressUntil`)
        WHERE `suppress`='y'";
    send_query($q);

    $q="UPDATE {$toPrefix}items AS i
            JOIN {$toPrefix}itemattributes AS ia USING (`itemId`)
        SET i.`recurdesc`=CONCAT('+',ia.`repeat`,'d'),
            i.`recur`=CONCAT('FREQ=DAILY;INTERVAL=',ia.`repeat`)
        WHERE ia.`repeat`>'0'";
    send_query($q);

    // move items from the tickler table into items

    $q="INSERT INTO `{$toPrefix}items`
        (`title`,`description`,`recurdesc`,`recur`,`oldid`)
        SELECT `title`,`note`,
            CONCAT('+',`repeat`,'d'),
            CONCAT('FREQ=DAILY;INTERVAL=',`repeat`),
            `ticklerId`
         FROM {$toPrefix}tickler";
    send_query($q);

    $q="INSERT INTO `{$toPrefix}itemattributes`
            (`itemId`,`tickledate`,`type`,`isSomeday`)
        SELECT i.`itemId`,SUBDATE(t.`date`,t.`suppressUntil`),'a','n'
        FROM `{$toPrefix}tickler`     AS t
        INNER JOIN `{$toPrefix}items` AS i ON (i.`oldid`=t.`ticklerId`)";
    send_query($q);

    $q="INSERT INTO `{$toPrefix}itemstatus` (`itemId`)
            SELECT i.`itemId`
        FROM `{$toPrefix}tickler`     AS t
        INNER JOIN `{$toPrefix}items` AS i ON (i.`oldid`=t.`ticklerId`)";
    send_query($q);

    // finishing work on recurrence fields: tidy up

    $q="ALTER TABLE `{$toPrefix}items` DROP COLUMN `oldid`";
    send_query($q);

    $q="ALTER TABLE `{$toPrefix}itemattributes`
            DROP COLUMN `suppress`,
            DROP COLUMN `repeat`,
            DROP COLUMN `suppressUntil`";
    send_query($q);

    drop_table("{$toPrefix}tickler");
    drop_table("{$toPrefix}notes");

    /*--------------------------------------------------------------------------
        move (check)list(item)s from separate tables, into the items and itemstatus tables
    */
    flushAndResetTimer();

    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD COLUMN (
            `type` enum('m','v','o','g','p','a','r','w','i','L','C','T','x') NOT NULL DEFAULT 'i',
            categoryId int(11) UNSIGNED NOT NULL DEFAULT '0',
            `oldid` int(10) UNSIGNED DEFAULT NULL,`oldparent` int(10) UNSIGNED DEFAULT NULL),
            ADD INDEX `type` (`type`), ADD INDEX `categoryId`(`categoryId`)";
	send_query($q);

	$q="UPDATE `{$toPrefix}itemstatus` AS its
          JOIN `{$toPrefix}itemattributes` AS ia USING (`itemId`)
            SET its.`type`=ia.`type`, its.`categoryId`=ia.`categoryId`";
	send_query($q);

    // create list-ids and categories
    $q="INSERT INTO `{$toPrefix}itemstatus` (`itemId`,`type`,`categoryId`,`oldid`,`dateCreated`)
        SELECT NULL,'L',`categoryId`,`listId`,CURDATE() FROM `{$toPrefix}list`";
    send_query($q);

    // create entries for lists in the table of text descriptions too
    $q="INSERT INTO `{$toPrefix}items` (`itemId`,`title`)
        SELECT `itemId`,'untitled' FROM `{$toPrefix}itemstatus` WHERE `type`='L' ";
    send_query($q);

    // id translation, and populate text fields
    $q="UPDATE   `{$toPrefix}items` AS i
            JOIN `{$toPrefix}itemstatus` AS its USING (`itemId`)
            JOIN `{$toPrefix}list` AS l ON (l.`listId`=its.`oldid`)
        SET i.`title`=  l.`title`, i.`description`=l.`description`
        WHERE its.`type`='L' ";
    send_query($q);

   // create listitem ids and links to parent Ids
    $q="INSERT INTO `{$toPrefix}itemstatus` (`itemId`,`type`,`oldid`,`oldparent`,`dateCompleted`,`dateCreated`)
        SELECT NULL,'T',`listitemId`,`listId`,`dateCompleted`,CURDATE() FROM `{$toPrefix}listitems`";
    send_query($q);

    // copy listitem ids into the items table
    $q="INSERT INTO `{$toPrefix}items` (`itemId`,`title`)
        SELECT `itemId`,'untitled' FROM `{$toPrefix}itemstatus` WHERE `type`='T' ";
    send_query($q);

    // populate text fields
    $q="UPDATE   `{$toPrefix}items` AS i
            JOIN `{$toPrefix}itemstatus` AS its USING (`itemId`)
            JOIN `{$toPrefix}listitems` AS l ON (l.`listitemId`=its.`oldid`)
        SET i.`title`=  l.`item`, i.`description`=l.`notes`
        WHERE its.`type`='T' ";
    send_query($q);

    // populate lookup with the list / listitem relationships
    $q="INSERT INTO `{$toPrefix}lookup` (`parentId`,`itemId`)
            SELECT itsp.`itemId` AS `parentId`, its.`itemId` AS `itemId`
            FROM `{$toPrefix}itemstatus` AS its
            JOIN `{$toPrefix}itemstatus` AS itsp ON (its.`oldparent`=itsp.`oldid`)
            WHERE its.`type`='T' AND itsp.`type`='L'";
    send_query($q);

    //---------------------------------------------------
    // almost the same again, for checklists
    // create checklist-ids and categories
    flushAndResetTimer();
    $q="INSERT INTO `{$toPrefix}itemstatus` (`itemId`,`type`,`categoryId`,`oldid`,`dateCreated`)
        SELECT NULL,'C',`categoryId`,`checklistId`,CURDATE() FROM `{$toPrefix}checklist`";
    send_query($q);

    // create entries for lists in the table of text descriptions too
    $q="INSERT INTO `{$toPrefix}items` (`itemId`,`title`)
        SELECT `itemId`,'untitled' FROM `{$toPrefix}itemstatus` WHERE `type`='C' ";
    send_query($q);

    // id translation, and populate text fields
    $q="UPDATE   `{$toPrefix}items` AS i
            JOIN `{$toPrefix}itemstatus` AS its USING (`itemId`)
            JOIN `{$toPrefix}checklist` AS c ON (c.`checklistId`=its.`oldid`)
        SET i.`title`=  c.`title`, i.`description`=c.`description`
        WHERE its.`type`='C' ";
    send_query($q);

   // create checklist item ids, and mark with dummy type x for now.  Also carry over old checklist item id, and old checklist id
    $q="INSERT INTO `{$toPrefix}itemstatus` (`itemId`,`type`,`oldid`,`oldparent`,`dateCompleted`,`dateCreated`)
        SELECT NULL,'x',`checklistitemId`,`checklistId`,IF(`checked`='y',CURDATE(),NULL),CURDATE() FROM `{$toPrefix}checklistitems`";
    send_query($q);

    // create entries for lists in the table of text descriptions too
    $q="INSERT INTO `{$toPrefix}items` (`itemId`,`title`)
        SELECT `itemId`,'untitled' FROM `{$toPrefix}itemstatus` WHERE `type`='x' ";
    send_query($q);

    // id translation, and populate text fields
    $q="UPDATE   `{$toPrefix}items` AS i
            JOIN `{$toPrefix}itemstatus` AS its USING (`itemId`)
            JOIN `{$toPrefix}checklistitems` AS l ON (l.`checklistitemId`=its.`oldid`)
        SET i.`title`=  l.`item`, i.`description`=l.`notes`,
            its.`dateCompleted`=IF(l.`checked`='y',CURDATE(),NULL)
        WHERE its.`type`='x' ";
    send_query($q);

    // populate lookup with the checklist /items relationships
    $q="INSERT INTO `{$toPrefix}lookup` (`parentId`,`itemId`)
            SELECT itsp.`itemId` AS `parentId`, its.`itemId` AS `itemId`
            FROM `{$toPrefix}itemstatus` AS its
            JOIN `{$toPrefix}itemstatus` AS itsp ON (its.`oldparent`=itsp.`oldid`)
            WHERE its.`type`='x' AND itsp.`type`='C'";
    send_query($q);

    // finally mark checklist items with their proper type, T, rather than dummy type x used during upgrade
    $q="UPDATE `{$toPrefix}itemstatus` SET `type`='T' WHERE `type`='x'";
    send_query($q);
    /*---------------------------------------------------------------------
             all done - now clean up
    */
    flushAndResetTimer();
    $q="ALTER TABLE `{$toPrefix}itemattributes` DROP COLUMN `type`,
            DROP COLUMN `categoryId`, DROP INDEX `type`, DROP INDEX `categoryId`";
	send_query($q);

	$q="ALTER TABLE `{$toPrefix}itemstatus` DROP COLUMN `oldid`,
            DROP COLUMN `oldparent`";
	send_query($q);

    $q="ALTER TABLE `{$toPrefix}itemstatus` MODIFY COLUMN
            `type` enum('m','v','o','g','p','a','r','w','i','L','C','T') NOT NULL DEFAULT 'i'";
	send_query($q);

	$q="DROP TABLE `{$toPrefix}list`,`{$toPrefix}checklist`,
            `{$toPrefix}listitems`,`{$toPrefix}checklistitems`";
	send_query($q);
	return true;
}
/*
   ======================================================================================
*/
function up08z04TO08z05($toPrefix) {
	drop_table("{$toPrefix}preferences");
	create_table($toPrefix,"preferences");
    importOldConfig();
    return true;
}
/*
   ======================================================================================
*/
function up08z05TO08z07($toPrefix) {
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD COLUMN (
            `isSomeday` enum('y','n') NOT NULL default 'n',
            `contextId` int(10) unsigned NOT NULL default '0',
            `timeframeId` int(10) unsigned NOT NULL default '0',
            `deadline` date default NULL,`tickledate` date default NULL,
            `nextaction` enum('y','n') NOT NULL DEFAULT 'n'
           ) ";
    send_query($q);
       
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD INDEX `contextId` (`contextId`)";
    send_query($q);
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD INDEX `timeframeId` (`timeframeId`)";
    send_query($q);
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD INDEX `isSomeday` (`isSomeday`)";
    send_query($q);

    $q="UPDATE `{$toPrefix}itemstatus` AS its
            JOIN `{$toPrefix}itemattributes` AS ia USING (`itemId`)
        SET its.`isSomeday`=  ia.`isSomeday`, its.`contextId`=ia.`contextId`,
            its.`timeframeId`=  ia.`timeframeId`, its.`deadline`=ia.`deadline`,
            its.`tickledate`=  ia.`tickledate`, its.`nextaction`=ia.`nextaction`";
    send_query($q);
    send_query("DROP TABLE `{$toPrefix}itemattributes`");
    
    return true;
}
/*
   ======================================================================================
*/
function up08z07TO08z08($toPrefix) {
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD INDEX `typeComp` (`type`,`dateCompleted`)";
    send_query($q);
    return true;
}
/*
   ======================================================================================
*/
function up08z08TO08z09($toPrefix) {
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD INDEX `dateCompleted` (`dateCompleted`)";
    send_query($q);
    $q="ALTER TABLE `{$toPrefix}itemstatus` ADD INDEX `tickledate` (`tickledate`)";
    send_query($q);
    return true;
}
/*
   ======================================================================================
     Table Creation Queries
   ======================================================================================
*/
function create_tables($prefix) {
	global $tablesByVersion,$versions;
	include_once 'gtd_constants.inc.php';
    foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $table)
        create_table($prefix,$table);
}
// --------------------------------------------------------------------------------------
function create_table ($prefix,$name) {
    $q="CREATE TABLE `$prefix$name` (";
	switch ($name) {
	case "categories":
       $q.="`categoryId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`category` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`categoryId`), ";
       $q.=_FULLTEXT." KEY `category` (`category`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN.")";
    break;
    case "context":
       $q.="`contextId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`name` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`contextId`), ";
       $q.=_FULLTEXT." KEY `name` (`name`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN.")";
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
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN.")";
	break;
	case "itemstatus";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment,
            `dateCreated` date default '"._DEFAULTDATE."',
            `lastModified` timestamp default '"._DEFAULTDATE."' ,
            `dateCompleted` date default NULL,
            `type` enum ('m','v','o','g','p','a','r','w','i','L','C','T') NOT NULL default 'i',
            `categoryId` int(11) unsigned NOT NULL default '0',
            `isSomeday` enum('y','n') NOT NULL default 'n',
            `contextId` int(10) unsigned NOT NULL default '0',
            `timeframeId` int(10) unsigned NOT NULL default '0',
            `deadline` date default NULL,
            `tickledate` date default NULL,
            `nextaction` enum('y','n') NOT NULL DEFAULT 'n',
             PRIMARY KEY (`itemId`),
             KEY `type` (`type`),
             KEY `categoryId` (`categoryId`),
             KEY `contextId` (`contextId`),
             KEY `timeframeId` (`timeframeId`),
             KEY `isSomeday` (`isSomeday`),
             KEY `typeComp` (`type`,`dateCompleted`),
             KEY `dateCompleted` (`dateCompleted`),
             KEY `tickledate` (`tickledate`)
        ";
	break;
	case "lookup":
       $q.="`parentId` int(11) NOT NULL DEFAULT '0', ";
       $q.="`itemId` int(10) UNSIGNED NOT NULL DEFAULT '0', ";
       $q.="PRIMARY KEY  (`parentId`,`itemId`) ";
    break;
	case "preferences":
       $q.="`id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `uid` int(10)  NOT NULL DEFAULT '0',
            `option` text NOT NULL,
            `value`  text,
            PRIMARY KEY (`id`),
            UNIQUE KEY(`uid`,`option`"._INDEXLEN.") ";
	break;
    case "tagmap":
        $q.= "`itemId` int(10) UNSIGNED NOT NULL,
            `tagname` text NOT NULL,
            PRIMARY KEY (`itemId`,`tagname`(20) ),
            KEY `itemId` (`itemId`),"
            ._FULLTEXT." KEY `tagname` (`tagname`"._INDEXLEN.") ";
    break;
	case "timeitems":
       $q.="`timeframeId` int(10) UNSIGNED NOT NULL auto_increment, ";
       $q.="`timeframe` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="`type` enum('v','o','g','p','a') NOT NULL default 'a', ";
       $q.="PRIMARY KEY  (`timeframeId`), ";
       $q.="KEY `type` (`type`), ";
       $q.=_FULLTEXT." KEY `timeframe` (`timeframe`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN.")";
    break;
	case "version":
       $q.="`version` text NOT NULL,
            `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP";
    break;
    default:
    break;
    }
    send_query($q.')'._CREATESUFFIX);
}
/*
   ======================================================================================
*/
