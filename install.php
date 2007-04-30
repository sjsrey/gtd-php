<?php
	require('header.php');
	define("_USEFULLTEXT",true); // false | true
	define("_DEBUG",true);       // false | true
	define("_DRY_RUN",false);     // false | true - dry run won't take any action, but will show the commands that would be done: use _DEBUG true to see these

	if (_USEFULLTEXT) {
	   define("_CREATESUFFIX",' ENGINE=MyISAM ');
	   define("_FULLTEXT",' FULLTEXT ');
 	}else{
	   define("_CREATESUFFIX",' ');
	   define("_FULLTEXT",' ');
	}
	
// global variables
	$tablelist=array();
	$tablesByVersion=array(); // NB these must all be lower case
	$tablesByVersion['0.6?']   = array('context','goals','maybe','maybesomeday','nextactions','projects','reference','waitingon');
	$tablesByVersion['0.7']    = array('categories','checklist','checklistitems','context','goals','itemattributes','items','itemstatus','list','listitems','nextactions','projectattributes','projects','projectstatus','tickler','timeitems');
	$tablesByVersion['0.8rc-1'] = array('categories','checklist','checklistitems','context','itemattributes','items','itemstatus','list','listitems','lookup','nextactions','tickler','timeitems','version');
	$tablesByVersion['0.8rc-2'] = $tablesByVersion['0.8rc-1'];
	array_push($tablesByVersion['0.8rc-2'],'preferences');
	
	if (_DEBUG) {
		echo '<pre>';
		if (_DRY_RUN) echo 'Executing Dry run - no tables should be amended in this run<br />';
		echo 'POST variables: ',print_r($_POST,true),"</pre>\n";
	}
	
	// are we doing the install, or the pre-install checks? Use the post variable to decide:
	if (isset($_POST['submit']))
		doInstall();
	else
		checkInstall();
	include_once('footer.php');

/*
   ======================================================================================
   end of output - all functions from here, down
   ======================================================================================
*/

function checkInstall() {
	global $config,$tablesByVersion,$tablelist;
	
	$goodToGo=true; // assume we'll be able to upgrade, until we find something to stop us

    // some reporting 
    // get server information for problem reports
    echo "<h1>gtd-php installation/upgrade</h1>\n<h2>Installation Info</h2>\n";

	if (_DEBUG) {
		$included_files = get_included_files();
		echo '<pre>',print_r($tablesByVersion,true),'<hr>',print_r($included_files,true),'</pre>';
	}
	
    echo '<p>php: ',phpversion(),"</p>\n";

	$gotConfigFile=(isset($config['db']) && $config['db']!='');
	// TOFIX - check to see whether config is 0.7 or 0.8 style: if the former, warn the installer
	
	if ($gotConfigFile) {
		// we have a config.php
		if (_DEBUG) echo '<p class="debug">Got config.php:</p><pre>',print_r($config,true),'</pre>';

		// got a database, now it's safe to open it
		echo '<p>mysql: ',mysql_get_server_info(),"</p>\n";
		//check if db exists
		$msg='<p class="error">Unable to select gtd database.<br />Please create the '.$config['db']." MySQL database and rerun this script.<p>\n";
		mysql_select_db($config['db']) or die ($msg);
		$tables = mysql_list_tables($config['db']);
		while ($tbl = mysql_fetch_row($tables))
		   array_push($tablelist,strtolower($tbl[0]));
		// validate the prefix
		if (!checkPrefix($config['prefix']))
			$goodToGo=false;
	} else {
		// no config.php - that will be a deal-breaker
		$goodToGo=false;
		echo '<p class="error">No valid config.php file found.<br />'
			,'Copy the config.sample.php file to config.php, and set the MySQL parameters</p>'
			// TOFIX - link to config.php documentation here
			,"\n";
	}
	
	$nt=count($tablelist);
	if (_DEBUG) echo "<pre>Number of tables: $nt<br />",print_r($tablelist,true),"</pre>";
	
	/*
		Build an array of current installlations,
		and offer choice of upgrading from one of these, or doing a fresh install
		TOFIX - cycle through the found tables, and wherever we match /.*preferences$/ , do a checkPrefixedTables
	*/
	$gotVersions=array();
	$gotPrefixes=(preg_grep("/.*preferences$/",$tablelist));
	if (_DEBUG) echo '<pre>Preference tables:',print_r($gotPrefixes,true),'</pre>';
	foreach ($gotPrefixes as $thisPreference) {
		$thisPrefix=substr($thisPreference,0,-11);
		$thisVer=checkPrefixedTables($thisPrefix);
		if ($thisVer)
			$gotVersions["0.8rc-{$thisVer}={$thisPrefix}"]=$thisVer;
	}
	// only check for 0.8rc-1 if there is no unprefixed 0.8rc-2/3 installation
	if (!array_key_exists("0.8rc-2=",$gotVersions) && !array_key_exists("0.8rc-3=",$gotVersions))
		if (!count(array_diff($tablesByVersion['0.8rc-1'],$tablelist))) {
			$gotVersions['0.8rc-1']=true;
	}
	
	if (!count(array_diff($tablesByVersion['0.7'],$tablelist)))
		$gotVersions['0.7']=true;
	
	if (!count(array_diff($tablesByVersion['0.6?'],$tablelist))) {
		echo '<p>Found what looks like a version of GTD-PHP earlier than 0.7: this install program cannot upgrade this</p>';
		// $gotVersions['0.6?']=true;
	}
			
	if (_DEBUG) echo '<pre>Versions found: ',print_r($gotVersions,true),"</pre>\n";
	
	if ($goodToGo) {
		echo '<form action="install.php" method="post">'
			,"\n<h2>Select an upgrade or installation</h2>\n"
			,"<h3>Creating installation with "
			,(($config['prefix']=='')?'no prefix':"prefix '{$config['prefix']}'")
			,"</h3>\n"
			,'<table summary="table of installation alternatives">',"\n";
		foreach ($gotVersions as $thisKey=>$thisVer) {
			$tmp=explode('=',$thisKey);
			$fromVer=$tmp[0];
			$fromSuffix=$tmp[1];
			$msg=( ($thisVer===3)?'Copy':'Update') . " current $fromVer installation" 
				   . (($fromSuffix=='')?'':" with prefix $fromSuffix");
			echo tabulateOption($thisKey,$msg);
		}
		echo tabulateOption('1',"New install with sample data");
		echo tabulateOption('0',"New install with empty database");
		// and finally, close the table
		echo "</table>\n";
		// Do we have a valid destination?
		$testDest='0.8rc-('.$config['prefix'].')';
		if (array_key_exists($testDest,$gotVersions) && $gotVersions[$testDest]!=2) {
			// destination already in use
			echo '<p class="error">Unable to use the current config.php file to create an installation, because 
				there is already a current GTD-PHP installation using the prefix ',$config['prefix'],"</p>\n";
		} else {
			// destination available
			echo '<input type="hidden" name="prefix" value="',$config['prefix'],"\" />\n";
			echo '<input type="submit" name="submit" value="Do it" />',"\n";
		}
	}
	echo '</form>';
}
/*
   ======================================================================================
*/
function doInstall() {
	global $config,$drop,$temp;
		
	// for testing!  Set to true once tested, or if not using table prefixes.
	$drop = false;
	$completedOK=true;
	
    //check if db exists
    $msg='<p class="error">Unable to select gtd database.<br />Please create the ';
    $msg.=$config['db'].' mysql database and rerun this script.</p>';
	mysql_select_db($config['db']) or die ($msg);
	
	$args=explode('=',$_POST['install']);
	$installType=$args[0];
	$fromPrefix=$args[1];
	if (_DEBUG) echo "<pre>Install type is: $installType<br />Source database has prefix $fromPrefix</pre>";

    switch($installType){
	  case '0': // new install =============================================================================
		create_tables();
       // give some direction about what happens next for the user.
       $endMsg=
<<<HTML1
       <h1>Welcome to GTD-PHP</h1>
       
       <p>You have just successfully installed GTD-PHP.
       There are some preliminary steps you should take to set up your
       installation for use and familiarize yourself with the system.</p>
       <p>
		   <ol>
              <li>You need to set up <a href="newContext.php" target="_blank">spatial</a> and
              <a href="newTimeContext.php" target="_blank">time contexts</a> that suit your situation.</li>
			   <li>You need to enter ....</li>
			   <li></li>
			   <li></li>
		   </ol>
       </p>
HTML1;
       // end new install
	   break;
	 case '1': // new install with sample data
		create_tables();
		create_data();
       // give some direction about what happens next for the user.
       $endMsg=
<<<HTML2
       <h1>Welcome to GTD-PHP</h1>
       
       <p>You have just successfully installed GTD-PHP. Sample data has been created as part of the installation.</p>
       <p>
		   <ol>
              <li>Check that the <a href="newContext.php" target="_blank">spatial</a> and
              <a href="newTimeContext.php" target="_blank">time contexts</a> suit your situation.</li>
		   </ol>
       </p>
HTML2;
		break;	 
	 case '0.8rc-3': // already at latest release ============================================================
		// if source prefix != install prefix, should copy tables. 
		create_tables();
		global $tablesByVersion;
		foreach ($tablesByVersion['0.8rc-2'] as $table){
			if ($table =="version") continue;
			$q = "INSERT INTO ".$config['prefix']. $table . " select * from `". $fromPrefix . $table ."`";
			send_query($q);
		}
		
	   $endMsg='<p>Database copied.  No upgrade needed</p>';
	   break;
	 case '0.8rc-2':  // upgrade from 0.8rc-2 =================================================================
		$q = "drop table `{$config['prefix']}version`";
		send_query($q);
	   createVersion();
	   $endMsg='<p>GTD-PHP 0.8 upgraded from rc2 to rc3 - thanks for your beta-testing</p>';
	   break;
	 case '0.8rc-1':  // upgrade from 0.8rc-1 =================================================================
		$q = "drop table `version`";
       send_query($q);
	   createVersion();
       createPreferences();	   
	   fixAllDates();
	   $endMsg='<p>GTD-PHP 0.8 upgraded from rc1 to rc3 - thanks for your beta-testing</p>';
	   break;
	  case '0.7': // upgrade from 0.7 =============================================================================
    
    	// temp table prefix
		$temp =  "t_";

       // update
       // keep a backup of the db?
       // move each of the old tables into the appropriate new tables

       // if they were using 0.7 they were not using prefixes. do we need them
       // here?
       // Crisses:  Yes, because people may want to MOVE to prefixed tables.
       // I just suggest that any time we're going to do the same thing over & over
       // we should put it into a function.

		// categories
		create_table("categories");
		$q="INSERT INTO ".$config['prefix']. $temp . "categories select * from `categories`";
		send_query($q);
		move_temp("categories");

		// checklist
		create_table("checklist");
		$q="INSERT INTO ".$config['prefix']. $temp . "checklist  SELECT * FROM `checklist`";
		send_query($q);
		move_temp("checklist");

		// checklistItems
		create_table("checklistItems");
		$q="INSERT INTO ".$config['prefix']. $temp . "checklistItems  SELECT * FROM `checklistItems`";
		send_query($q);
		move_temp("checklistItems");

		// context
		create_table("context");
		$q="INSERT INTO ".$config['prefix']. $temp . "context SELECT * FROM `context`";
		send_query($q);
		move_temp("context");

		// goals
		create_table("goals");
		$q="INSERT INTO ".$config['prefix']. $temp . "goals  SELECT * FROM `goals`";
		send_query($q);
		move_temp("goals");

       fixDate('goals','created');
       fixDate('goals','deadline');
       fixDate('goals','completed');

       // itemattributes
       $q="create table `{$config['prefix']}{$temp}itemattributes` (";
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
       $q="INSERT INTO ".$config['prefix']. $temp . "itemattributes  SELECT * FROM `itemattributes`";
       send_query($q);

		move_temp("itemattributes");

       // items
		create_table("items");

       $q="INSERT INTO ".$config['prefix']. $temp . "items (itemId,title,description) SELECT * from `items` ";
       send_query($q);

		move_temp("items");

       $q="CREATE TABLE `{$config['prefix']}{$temp}itemstatus` ( ";
       $q.="`itemId` int( 10 ) unsigned NOT NULL auto_increment ,";
       $q.=" `dateCreated` date default NULL, ";
       $q.="`lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP , ";
       $q.="`dateCompleted` date default NULL , ";
       $q.=" `completed` int( 10 ) unsigned default NULL , ";
       $q.="PRIMARY KEY ( `itemId` ) ) ";
       
       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "itemstatus SELECT * FROM `itemstatus`";
       send_query($q);


		move_temp("itemstatus");

       fixDate('itemstatus','dateCreated');
       fixDate('itemstatus','dateCompleted');

		create_table('list');       

       $q="INSERT INTO ".$config['prefix']. $temp . "list  SELECT * FROM `list` ";
       send_query($q);

		move_temp("list");


		create_table("listItems");

       $q="INSERT INTO ".$config['prefix']. $temp . "listItems SELECT * FROM `listItems`";
       send_query($q);

		move_temp("listItems");

       fixDate('listItems','dateCompleted');

       $q="CREATE TABLE `{$config['prefix']}{$temp}nextactions` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" `nextaction` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" PRIMARY KEY ( `projectId` , `nextaction` ) ) ";
       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "nextactions SELECT * FROM `nextactions`";
       send_query($q);

		move_temp("nextactions");

       $q="CREATE TABLE `{$config['prefix']}{$temp}projectattributes` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `categoryId` int( 10 ) unsigned NOT NULL default '1', ";
       $q.="`isSomeday` enum( 'y', 'n' ) NOT NULL default 'n', ";
       $q.=" `deadline` date default NULL , `repeat` int( 11 ) unsigned NOT
       NULL default '0', ";
       $q.="`suppress` enum( 'y', 'n' ) NOT NULL default 'n', ";
       $q.=" `suppressUntil` int( 10 ) unsigned default NULL , PRIMARY KEY (
          `projectId` ) ,";
       $q.=" KEY `categoryId` ( `categoryId` ) , KEY `isSomeday` (
          `isSomeday`) ,";
          $q.="KEY `suppress` ( `suppress` ) ) ";
       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "projectattributes SELECT * FROM `projectattributes` ";
       send_query($q);


		move_temp("projectattributes");

       fixDate('projectattributes','deadline');

       $q="CREATE TABLE `{$config['prefix']}{$temp}projects` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `name` text NOT NULL , `description` text, `desiredOutcome` text, ";
       $q.="PRIMARY KEY ( `projectId` ) , "._FULLTEXT." KEY `desiredOutcome` (
          `desiredOutcome` ) , ";
       $q.=_FULLTEXT." KEY `name` ( `name` ) , "._FULLTEXT." KEY `description` (
          `description` ) ) ". _CREATESUFFIX;
       send_query($q);

       $q="INSERT INTO ".$config['prefix']. $temp . "projects SELECT * FROM `projects` ";
       send_query($q);

		move_temp("projects");

       $q="CREATE TABLE `{$config['prefix']}{$temp}projectstatus` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment ,
       `dateCreated` date  default NULL, `lastModified`
       timestamp NOT NULL default CURRENT_TIMESTAMP on update
       CURRENT_TIMESTAMP , `dateCompleted` date default NULL , PRIMARY KEY (
          `projectId` ) ) ";
       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "projectstatus SELECT * FROM
       `projectstatus`";
       send_query($q);


		move_temp("projectstatus");

       fixDate('projectstatus','dateCreated');
       fixDate('projectstatus','dateCompleted');

		create_table("tickler");
       
       $q="INSERT INTO ".$config['prefix']. $temp . "tickler (ticklerId,date,title,note) SELECT * FROM `tickler`";
       send_query($q);

		move_temp("tickler");

       fixDate('tickler','date');

       $q="CREATE TABLE `{$config['prefix']}{$temp}timeitems` ( ";
       $q.="`timeframeId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `timeframe` text NOT NULL , `description` text, PRIMARY KEY (
          `timeframeId` ) )" . _CREATESUFFIX;
       send_query($q);

       $q="INSERT INTO ".$config['prefix']. $temp . "timeitems SELECT * FROM
       `timeitems`";
       send_query($q);

		move_temp("timeitems");


		// Dealing with the lookup table       
       createLookup();
       $q="INSERT INTO ".$config['prefix']."lookup (`parentId`,`itemId`) SELECT `projectId`,`itemId`
       FROM `itemattributes`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemattributes DROP `projectId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemattributes ADD `isSomeday`
       ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n' AFTER `type`, ADD `categoryId`
       INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `isSomeday` ";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemattributes ADD INDEX (
          `isSomeday` )";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes ADD INDEX (
          `categoryId`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemstatus DROP `completed`";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes CHANGE `type`
       `type` ENUM( 'm', 'v', 'o', 'g', 'p', 'a', 'r', 'w', 'i' ) NOT NULL
       DEFAULT 'i'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."projectattributes ADD `type` ENUM(
          'p' ) NOT NULL DEFAULT 'p' AFTER `projectId`";
       send_query($q);
       
       $q="ALTER TABLE ".$config['prefix']."nextactions DROP PRIMARY KEY, ADD
       PRIMARY KEY ( `projectId` , `nextaction`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."nextactions CHANGE `projectId`
       `parentId` INT( 10 ) UNSIGNED NOT NULL DEFAULT'0'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."items ADD `prikey` INT UNSIGNED
       NOT NULL FIRST";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes ADD `prikey` INT
       UNSIGNED NOT NULL FIRST";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemstatus ADD `prikey` INT
       UNSIGNED NOT NULL FIRST";
       send_query($q);


       $q="ALTER TABLE ".$config['prefix']."items CHANGE `itemId` `itemId`
       INT( 10 ) UNSIGNED NOT NULL";
       send_query($q);
       
       $q="ALTER TABLE ".$config['prefix']."itemattributes CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemstatus CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL";
       send_query($q);

	// migrate actions

$maxnum = " +( 
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	)";

		
		$q=" UPDATE ".$config['prefix']."items SET `prikey`=`itemId`" . $maxnum;
		send_query($q);
		
		$q="UPDATE ".$config['prefix']."itemattributes SET `prikey`=`itemId`" . $maxnum;
		send_query($q);
		
		$q="UPDATE `".$config['prefix']."itemstatus` SET `prikey`=`itemId`" . $maxnum;
		send_query($q);

       $q="ALTER TABLE ".$config['prefix']."items DROP PRIMARY KEY, ADD
       PRIMARY KEY (`prikey`)";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes DROP PRIMARY KEY,
       ADD PRIMARY KEY (`prikey`)";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemstatus DROP PRIMARY KEY, ADD
       PRIMARY KEY (`prikey`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."items DROP `itemId`";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes DROP `itemId`";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemstatus DROP `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."items CHANGE `prikey` `itemId`
       INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes CHANGE `prikey`
       `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemstatus CHANGE `prikey`
       `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);

       $q="DELETE FROM ".$config['prefix']."nextactions WHERE `nextaction`
       =0";
       send_query($q);

       $q="UPDATE `".$config['prefix']."nextactions` SET `nextaction`=`nextaction`" . $maxnum;
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."lookup ADD `prikey` INT UNSIGNED
       NOT NULL";
       send_query($q);

       $q="UPDATE `".$config['prefix']."lookup` SET `prikey` =`itemId`" . $maxnum;
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."lookup DROP PRIMARY KEY, ADD
       PRIMARY KEY (`parentId` , `prikey`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."lookup DROP `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."lookup CHANGE `prikey` `itemId`
       INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);

	// Migrate Projects


       $q="INSERT INTO ".$config['prefix']."items
       (`itemId`,`title`,`description`,`desiredOutcome`) SELECT
       `projectId`,`name`,`description`,`desiredOutcome` FROM `projects`";
       send_query($q);

       $q="INSERT INTO
       ".$config['prefix']."itemattributes(`itemId`,`type`,`categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil`)
       SELECT
       `projectId`,'p',`categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil`
       FROM `projectattributes`";
       send_query($q);


       $q="INSERT INTO ".$config['prefix']."itemstatus
       (`itemId`,`dateCreated`, `lastModified`, `dateCompleted`) SELECT
       `projectId`,`dateCreated`, `lastModified`, `dateCompleted` FROM
       `projectstatus`";
       send_query($q);

	// Migrate Goals
$maxnum = "+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	)";

       $q="ALTER TABLE ".$config['prefix']."goals ADD `prikey` INT
       UNSIGNED NOT NULL FIRST";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."goals CHANGE `id` `id` INT( 10 )
       UNSIGNED NOT NULL";
       send_query($q);

       $q="UPDATE ".$config['prefix']."goals SET `prikey`=`id`" . $maxnum;
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."goals DROP PRIMARY KEY, ADD
       PRIMARY KEY (`prikey`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."goals DROP `id`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."goals CHANGE `prikey` `id` INT( 10
    ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."timeitems ADD `type` ENUM( 'v',
       'o', 'g', 'p', 'a' ) NOT NULL DEFAULT 'a'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."timeitems ADD INDEX ( `type` )";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."goals ADD `timeframeId` INT
       UNSIGNED NOT NULL";
       send_query($q);

       $q="UPDATE `".$config['prefix']."goals` SET `timeframeId`= (1 + (
	CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
		ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
	END
	)) WHERE `type`='weekly'";
       send_query($q);

       $q="UPDATE `".$config['prefix']."goals` SET `timeframeId`= (2 + (
	CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
		ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
	END
	)) WHERE `type`='quarterly'";
       send_query($q);
       
       $q="INSERT INTO ".$config['prefix']."items
       (`itemId`,`title`,`description`) SELECT `id`,`goal`,`description` FROM
       `".$config['prefix']."goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix']."itemattributes
       (`itemId`,`type`,`timeframeId`,`deadline`) SELECT
       `id`, 'g',`timeframeId`, `deadline` FROM `".$config['prefix']."goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix']."itemstatus
       (`itemId`,`dateCreated`, `dateCompleted`) SELECT `id`, `created`,
       `completed` FROM `".$config['prefix']."goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix']."lookup (`parentId`,`itemId`)
       SELECT `projectId`,`id` FROM `goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix']."timeitems ( `timeframeId` ,
       `timeframe` , `description` , `type` ) VALUES (NULL , 'Weekly', NULL,
       'g'), (NULL , 'Quarterly', NULL , 'g')";
       send_query($q);

	drop_table ($config['prefix'].'projectattributes');
	drop_table ($config['prefix'].'projects');
	drop_table ($config['prefix'].'projectstatus');
	drop_table ($config['prefix'].'goals');

       $q="ALTER TABLE ".$config['prefix']."items  ORDER BY `itemId`";
       send_query($q);
       
       $q="ALTER TABLE ".$config['prefix']."itemattributes  ORDER BY `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemstatus  ORDER BY `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemattributes ADD INDEX (
          `isSomeday`)";
       send_query($q);


       $q="ALTER TABLE ".$config['prefix']."items CHANGE `itemId` `itemId`
       INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."itemattributes CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix']."itemattributes CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);

        $q="ALTER TABLE ".$config['prefix']."itemstatus CHANGE `itemId`
        `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);


       $q="ALTER TABLE ".$config['prefix']."timeitems ADD "._FULLTEXT."(`timeframe`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."timeitems ADD "._FULLTEXT."(`description`)";
       send_query($q);

       createPreferences();
       createVersion();
       
       fixDate('itemattributes','deadline');
       fixDate('itemstatus','dateCompleted');
       fixDate('itemstatus','lastModified');
       fixDate('itemstatus','dateCreated');

       // drop waitingOn
       // note this wasn't in database-upgrade-0.8.sql. do we need to move the
       // waitingOn's over?
		if ($drop) drop_table("waitingOn");
		$endMsg='<p>GTD-PHP upgraded from 0.7 to v0.8</p>';
	   break;
	 default: // no idea what the current installation is ==============================================
	 	$endMsg='<p class="error">The install script has not been able to work out'
	 		 .' whether this is an installation, or an upgrade;'
	  		 .' and if the latter, what version we are upgrading from.<br />'
			 .'Note that this installation script cannot upgrade'
			 .' an installation from gtd-php versions earlier than 0.7</p>';
		break;
    } // end of switch
	
	echo $endMsg;
}
/*
   ======================================================================================
*/

function fixAllDates() {
   fixDate('itemattributes','deadline');
   fixDate('itemstatus','dateCompleted');
   fixDate('itemstatus','dateCreated');
   fixDate('itemstatus','lastmodified');
   fixDate('listItems','dateCompleted');
   fixDate('tickler','date');
}

function tabulateOption($val,$from) {
	static $isChecked=' checked="checked" ';
	$result='<tr><td><input type="radio" name="install" value="'.$val."\" $isChecked /></td><td>$from</td><td>$to</td></tr>\n";
	$isChecked='';
	return $result;
}

function checkPrefix($prefix) {
	// check that the proposed prefix is valid for a gtd-php installation.
	if (_DEBUG) echo '<p class="debug">Validating prefix '."'{$prefix}'</p>\n";
	$prefixOK=preg_match("/^[-_a-zA-Z0-9]*$/",$prefix);
	if (!$prefixOK)
		echo '<p class="error">Prefix "',$prefix, '" is invalid - change config.php.'
			 ," The only valid characters are numbers, letters, _ (underscore) and - (hyphen)</p>\n";

	return $prefixOK;	 
}

function checkPrefixedTables($thisPrefix) {
	global $tablesByVersion,$tablelist;
	if (_DEBUG) echo '<p class="debug">Is there a current 0.8 installation with prefix "',$thisPrefix,'"? ';
	$thisPrefix=strtolower($thisPrefix);
	$doneOK=true;
	foreach ($tablesByVersion['0.8rc-2'] as $thisTable) 
		if (!in_array($thisPrefix.$thisTable,$tablelist,true)) {
			$doneOK=false;
			break;
		}
	if (_DEBUG) echo ($doneOK)?'YES':'NO',"</p>\n";
	if ($doneOK) {
		$q="SELECT `version` from `{$thisPrefix}version`";
		$result = mysql_query($q);
		if ($result) {
			$row = mysql_fetch_row($result);
			if (_DEBUG) echo '<p class="debug">Found Version field: "',$row[0],'"</p>';
			$retval=3;
		} else
			$retval=2;
		if (_DEBUG) echo '<p class="debug">Resolved version number as: "',$retval,'"</p>';
	} else $retval=0;
	return $retval;
}

function getExistingDestinationTables($prefix) {
	global $tablelist,$tablesByVersion;
	if (_DEBUG) echo '<p class="debug">Checking availability of destination prefix "{$prefix}"</p>';
	$destInUse=array();
	foreach ($tablesByVersion['0.8rc-2'] as $thisTable)
   		if (count(array_keys($prefix.$thisTable,$tablelist,true)))
			array_push($destInUse,$prefix.$thisTable);
	return $destInUse; 	
}
/*
   ======================================================================================
*/
function create_tables() {
	global $config;
       // start creating new tables
       
		create_table("categories");
		create_table("checklist");
		create_table("checklistItems");
		create_table("context");


       $q="create table `{$config['prefix']}itemattributes` (";
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
       send_query($q);

		create_table("items");

       $q="create table `{$config['prefix']}itemstatus` (";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`dateCreated` date  default NULL, ";
       $q.="`lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, ";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`itemId`))";
       send_query($q);

		create_table('list');       

		create_table("listItems");
       createLookup();
       createPreferences();

       $q="CREATE TABLE `{$config['prefix']}nextactions` (";
       $q.="`parentId` int(10) unsigned NOT NULL default '0', ";
       $q.="`nextaction` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`nextaction`))";
       send_query($q);

		create_table("tickler");
	
       $q="CREATE TABLE `{$config['prefix']}timeitems` (";
       $q.="`timeframeId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`timeframe` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="`type` enum('v','o','g','p','a') NOT NULL default 'a', ";
       $q.="PRIMARY KEY  (`timeframeId`), ";
       $q.="KEY `type` (`type`), ";
       $q.=_FULLTEXT." KEY `timeframe` (`timeframe`), ";
       $q.=_FULLTEXT." KEY `description` (`description`))"._CREATESUFFIX; 
       send_query($q);

       createVersion();
	}
/*
   ======================================================================================
*/
function drop_table($name){
		global $drop;
		if ($drop) drop_table("$name");
		$q = "drop table `$name`";
       send_query($q);
	}

function send_query($q) {
		if (_DEBUG) echo '<p class="debug">',"\n{$q}<br />\n";
       if (!_DRY_RUN) {
		   $result = mysql_query($q);
		   if (!$result) {
				 echo "<br />" .$q;
				 die('<br />Invalid query: ' . mysql_error());
				 $GLOBALS['completedOK']=false;
			}
       }
	}

function move_temp($name) {
	global $config, $temp, $drop;
		if ($drop) drop_table($name);
       // rename t_categories to categories
       $q="rename table `{$config['prefix']}{$temp}{$name}` to `{$config['prefix']}{$name}`";
       send_query($q);
}

    // new tables shared by upgrade and install paths
function createVersion()  {
    	global $config;
       $q="CREATE TABLE `{$config['prefix']}version` (";
       $q.="`version` text NOT NULL, ";
       $q.="`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update ";
       $q.=" CURRENT_TIMESTAMP)";
       send_query($q);
       # do we want to keep version somewhere more central? just updating here in
       # the install script kinda smells funny to me.
       $q="INSERT INTO `{$config['prefix']}version` (`version`) VALUES";
       $q.=" ('0.8rc-3')";
       send_query($q);
    }

function updateVersion() {
       $q="UPDATE `{$config['prefix']}version` SET `version`='0.8rc-3'";
       send_query($q);
}

    function createLookup() {
    	global $config;
       $q="CREATE TABLE `{$config['prefix']}lookup` (";
       $q.="`parentId` int(11) NOT NULL default '0', ";
       $q.="`itemId` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`itemId`) )";
       send_query($q);
    }

      
    function createPreferences() {
    	global $config;
       $q="CREATE TABLE `{$config['prefix']}preferences` (";
       $q.="`id`  int(10) unsigned NOT NULL auto_increment, ";
       $q.="`uid` int(10)  NOT NULL default '0', ";
       $q.="`option`  text, ";
       $q.="`value`  text, ";
       $q.="PRIMARY KEY  (`id`)); ";
       send_query($q);
    }
 
    function fixDate($tableName,$columnName){
    	global $config;
       // change dates of "0000-00-00" to NULL
       # fix date NULL versus 0000-00-00 issue
       $q=" update `{$config['prefix']}{$tableName}` set {$columnName}=NULL where `$columnName`='0000-00-00'";
       send_query($q);
	}

    //helper functions
	function report($tableName,$success){
		$html="<tr><td>$tableName</td><td>";
		if($success){
			$html .= '<font color="green">Success';
		}else{
			$html .= '<font color="red">Failure';
		}
		$html .= "</td></tr>\n";
		return $html;
	}
/*
   ======================================================================================
*/
// Table Creation Queries
function create_table ($name) {
	global $config, $temp;
	switch ($name) {
	case "categories":
       $q="create table `{$config['prefix']}{$temp}categories` (";
       $q.="`categoryId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`category` text NOT NULL, "; 
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`categoryId`), ";
       $q.=_FULLTEXT." KEY `category` (`category`), ";
       $q.=_FULLTEXT." KEY `description` (`description`))"._CREATESUFFIX;
       send_query($q);
    break;
    case "checklist":
       $q="create table `{$config['prefix']}{$temp}checklist` (";
       $q.="`checklistId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`title` text NOT NULL, "; 
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', "; 
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`checklistId`),    ";
       $q.=_FULLTEXT." KEY `description` (`description`), ";
       $q.=_FULLTEXT." KEY `title` (`title`))"._CREATESUFFIX;
       send_query($q);
	break;
	case "checklistItems":
	   $q="create table `{$config['prefix']}{$temp}checklistItems` (";
       $q.="`checklistItemId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`item` text NOT NULL, "; 
       $q.="`notes` text, "; 
       $q.="`checklistId` int(10) unsigned NOT NULL default '0', "; 
       $q.="`checked` enum ('y', 'n') NOT NULL default 'n', "; 
       $q.="PRIMARY KEY (`checklistItemId`), KEY `checklistId` (`checklistId`),"; 
       $q.=_FULLTEXT." KEY `notes` (`notes`), "._FULLTEXT." KEY `item` (`item`))"._CREATESUFFIX; 
       send_query($q);
    break;
    case "context":
       $q="create table `{$config['prefix']}{$temp}context` (";
       $q.="`contextId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`name` text NOT NULL, "; 
       $q.="`description` text, "; 
       $q.="PRIMARY KEY  (`contextId`), ";
       $q.=_FULLTEXT." KEY `name` (`name`), ";
       $q.=_FULLTEXT." KEY `description` (`description`))"._CREATESUFFIX;
       send_query($q);
	break;
	case "items":
       $q="CREATE TABLE `{$config['prefix']}{$temp}items` ( ";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`title` text NOT NULL, "; 
       $q.="`description` longtext, ";
       $q.="`desiredOutcome` text, ";
       $q.="PRIMARY KEY  (`itemId`), ";
       $q.=_FULLTEXT." KEY `title` (`title`), ";
       $q.=_FULLTEXT." KEY `desiredOutcome` (`desiredOutcome`), ";
       $q.=_FULLTEXT." KEY `description` (`description`))"._CREATESUFFIX;
       send_query($q);
	break;
	case "list":
       $q="CREATE TABLE `{$config['prefix']}{$temp}list` ( ";
       $q.="`listId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`listId`), ";
       $q.="KEY `categoryId` (`categoryId`), ";
       $q.=_FULLTEXT." KEY `description` (`description`), ";
       $q.=_FULLTEXT." KEY `title` (`title`)) "._CREATESUFFIX;
       send_query($q);
	break;
	case "listItems":
       $q="CREATE TABLE `{$config['prefix']}{$temp}listItems` ( ";
       $q.="`listItemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`item` text NOT NULL, ";
       $q.="`notes` text, ";
       $q.="`listId` int(10) unsigned NOT NULL default '0', ";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`listItemId`), ";
       $q.="KEY `listId` (`listId`), ";
       $q.=_FULLTEXT." KEY `notes` (`notes`), ";
       $q.=_FULLTEXT." KEY `item` (`item`)) "._CREATESUFFIX;
       send_query($q);
	break;
	case "tickler":
       $q="CREATE TABLE `{$config['prefix']}{$temp}tickler` ( ";
       $q.="`ticklerId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`date` date  default NULL, ";
       $q.="`title` text NOT NULL, ";
       $q.="`note` longtext, ";
       $q.="`repeat` int(10) unsigned NOT NULL default '0', ";
       $q.="`suppressUntil` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`ticklerId`), ";
       $q.="KEY `date` (`date`), ";
       $q.=_FULLTEXT." KEY `notes` (`note`), ";
       $q.=_FULLTEXT." KEY `title` (`title`)) "._CREATESUFFIX;
       send_query($q);
	break;
	case "goals":
	   $q="create table `{$config['prefix']}{$temp}goals` (";
       $q.="`id` int(11) NOT NULL auto_increment, "; 
       $q.="`goal`   longtext, ";
       $q.="`description`   longtext, ";
       $q.="`created` date default NULL, ";
       $q.="`deadline` date default NULL, ";
       $q.="`completed` date default NULL, ";
       $q.="`type` enum('weekly', 'quarterly') default NULL ,";
       $q.="`projectId` int(11) default NULL, PRIMARY KEY (`id`) )";
       send_query($q);
	break;
    default:
    break;
    }
}
/*
   ======================================================================================
*/
function create_data() {
	global $config;
	// a load of inserts here to create the sample data
}
/*
   ======================================================================================
*/
