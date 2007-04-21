<?php
	include_once('header.php');
	
	// for testing!  Set to true once tested, or if not using table prefixes.
	$drop = false;
	$debug = false; // use just to spit out the queries in this file
	
    // some reporting 
    // get server information for problem reports
    echo "<h2>gtd-php installation/upgrade</h2>\n";
    $v="<h3>Installation Info</h3>\n";
    $v.="<ul><li>";
    $v.="php: ".phpversion()."<br>";
    $v.="</li>\n<li>";
    $v.="mysql: ".mysql_get_server_info()."</li></ul>\n";
    echo $v;

    //check if db exists
    $msg='<font color="red">Unable to select gtd database.<br>Please create the ';
    $msg.=$config['db'].' mysql database and rerun this script.';
	mysql_select_db($config['db']) or die ($msg);


    // check if this is a new install or an upgrade
    // we only handle 1 pont upgrades, so users must be at 0.7 to upgrade to
    // 0.8.

    // check number of tables in db. 17 & 16 =0.7, 0=new, 15=no upgrade neededi
    $nt=0;
    $tables = mysql_list_tables($config['db']);
    while ($tbl = mysql_fetch_row($tables)){
       $nt++;
    }

    echo "Number of tables: $nt";
    if($nt==0){
       # new install
		create_tables();

       // give some direction about what happens next for the user.
       
       ?>
       
       <h2>Welcome to GTD-PHP</h2>
       
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
       <?php

       // end new install
    }else if($nt==17 || $nt==16){
    
    	// temp table prefix
		$temp =  "t_";

       //upgrading from 0.7
       echo "<br>Upgrading from 0.7";
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
       $q="create table ".$config['prefix']. $temp . "itemattributes (";
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

       $q="CREATE TABLE ".$config['prefix']. $temp . "itemstatus ( ";
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

       $q="CREATE TABLE ".$config['prefix']. $temp . "nextactions ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" `nextaction` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" PRIMARY KEY ( `projectId` , `nextaction` ) ) ";
       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "nextactions SELECT * FROM `nextactions`";
       send_query($q);

		move_temp("nextactions");

       $q="CREATE TABLE ".$config['prefix']. $temp . "projectattributes ( ";
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

       $q="CREATE TABLE ".$config['prefix']. $temp . "projects ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `name` text NOT NULL , `description` text, `desiredOutcome` text, ";
       $q.="PRIMARY KEY ( `projectId` ) , FULLTEXT KEY `desiredOutcome` (
          `desiredOutcome` ) , ";
       $q.=" FULLTEXT KEY `name` ( `name` ) , FULLTEXT KEY `description` (
          `description` ) ) ";
       send_query($q);

       $q="INSERT INTO ".$config['prefix']. $temp . "projects SELECT * FROM `projects` ";
       send_query($q);

		move_temp("projects");

       $q="CREATE TABLE ".$config['prefix']. $temp . "projectstatus ( ";
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

       $q="CREATE TABLE  ".$config['prefix']. $temp . "timeitems ( ";
       $q.="`timeframeId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `timeframe` text NOT NULL , `description` text, PRIMARY KEY (
          `timeframeId` ) )";
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


       $q="ALTER TABLE ".$config['prefix']."timeitems ADD FULLTEXT
       (`timeframe`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix']."timeitems ADD FULLTEXT
       (`description`)";
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
    }else if($nt==15){
       //has a 0.8 db
       echo "<br>No upgrade needed";
    }else{
       echo "<br>You must be at version 0.7 to upgrade to 0.8.";
    }
	include_once('footer.php');




function create_tables() {
	global $config;
       // start creating new tables
       echo "<br>New install";
       
		create_table("categories");
		create_table("checklist");
		create_table("checklistItems");
		create_table("context");


       $q="create table ".$config['prefix']."itemattributes (";
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
       $q.="KEY `isSomeday_2` (`isSomeday`));";
       send_query($q);

		create_table("items");

       $q="create table ".$config['prefix']."itemstatus (";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`dateCreated` date  default NULL, ";
       $q.="`lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, ";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`itemId`));";
       send_query($q);

		create_table('list');       

		create_table("listItems");
       createLookup();
       createPreferences();

       $q="CREATE TABLE ".$config['prefix']."nextactions (";
       $q.="`parentId` int(10) unsigned NOT NULL default '0', ";
       $q.="`nextaction` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`nextaction`));";
       send_query($q);

		create_table("tickler");
	
       $q="CREATE TABLE ".$config['prefix']."timeitems (";
       $q.="`timeframeId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`timeframe` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="`type` enum('v','o','g','p','a') NOT NULL default 'a', ";
       $q.="PRIMARY KEY  (`timeframeId`), ";
       $q.="KEY `type` (`type`), ";
       $q.="FULLTEXT KEY `timeframe` (`timeframe`), ";
       $q.="FULLTEXT KEY `description` (`description`));"; 
       send_query($q);

       createVersion();
	}

function drop_table($name){
		global $drop;
		if ($drop) drop_table("$name");
		$q = "drop table `$name`";
       send_query($q);
	}

function send_query($q) {
		global $debug;
		if ($debug) echo "<br />\n". $q . "<br />\n";
       $result = mysql_query($q);
       if (!$result) {
             echo "<br />" .$q;
             die('<br />Invalid query: ' . mysql_error());
       }
	}

function move_temp($name) {
	global $config, $temp, $drop;
		if ($drop) drop_table($name);
       // rename t_categories to categories
       $q="rename table ".$config['prefix']. $temp . $name . " to `".$config['prefix']. $name . "`";
       send_query($q);
}

    // new tables shared by upgrade and install paths
function createVersion()  {
    	global $config;
       $q="CREATE TABLE ".$config['prefix']."version (";
       $q.="`version` float unsigned NOT NULL, ";
       $q.="`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update ";
       $q.=" CURRENT_TIMESTAMP);";
       send_query($q);
       # do we want to keep version somewhere more central? just updating here in
       # the install script kinda smells funny to me.
       $q="INSERT INTO ".$config['prefix']."version (`version`) VALUES";
       $q.=" ('0.8rc-1');";
       send_query($q);
    }

    function createLookup() {
    	global $config;
       $q="CREATE TABLE ".$config['prefix']."lookup (";
       $q.="`parentId` int(11) NOT NULL default '0', ";
       $q.="`itemId` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`itemId`) );";
       send_query($q);
    }

      
    function createPreferences() {
    	global $config;
       $q="CREATE TABLE ".$config['prefix']."preferences (";
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
       $q=" update ".$config['prefix'].$tableName." set ".$columnName.'=NULL where ';
       $q.=$columnName."='0000-00-00'";
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
	
// Table Creation Queries
function create_table ($name) {
	global $config, $temp;

	switch ($name) {
	case "categories":
       $q="create table ".$config['prefix']. $temp . "categories (";
       $q.="`categoryId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`category` text NOT NULL, "; 
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`categoryId`), ";
       $q.="FULLTEXT KEY `category` (`category`), ";
       $q.="FULLTEXT KEY `description` (`description`));";
       send_query($q);
    break;
    case "checklist":
       $q="create table ".$config['prefix']. $temp . "checklist (";
       $q.="`checklistId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`title` text NOT NULL, "; 
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', "; 
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`checklistId`),    ";
       $q.="FULLTEXT KEY `description` (`description`), ";
       $q.="FULLTEXT KEY `title` (`title`)); ";
       send_query($q);
	break;
	case "checklistItems":
	   $q="create table ".$config['prefix']. $temp . "checklistItems (";
       $q.="`checklistItemId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`item` text NOT NULL, "; 
       $q.="`notes` text, "; 
       $q.="`checklistId` int(10) unsigned NOT NULL default '0', "; 
       $q.="`checked` enum ('y', 'n') NOT NULL default 'n', "; 
       $q.="PRIMARY KEY (`checklistItemId`), KEY `checklistId` (`checklistId`),"; 
       $q.="FULLTEXT KEY `notes` (`notes`), FULLTEXT KEY `item` (`item`))"; 
       send_query($q);
    break;
    case "context":
       $q="create table ".$config['prefix']. $temp . "context (";
       $q.="`contextId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`name` text NOT NULL, "; 
       $q.="`description` text, "; 
       $q.="PRIMARY KEY  (`contextId`), ";
       $q.="FULLTEXT KEY `name` (`name`), ";
       $q.="FULLTEXT KEY `description` (`description`)); ";
       send_query($q);
	break;
	case "items":
       $q="CREATE TABLE ".$config['prefix']. $temp . "items ( ";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, "; 
       $q.="`title` text NOT NULL, "; 
       $q.="`description` longtext, ";
       $q.="`desiredOutcome` text, ";
       $q.="PRIMARY KEY  (`itemId`), ";
       $q.="FULLTEXT KEY `title` (`title`), ";
       $q.="FULLTEXT KEY `desiredOutcome` (`desiredOutcome`), ";
       $q.="FULLTEXT KEY `description` (`description`));";
       send_query($q);
	break;
	case "list":
       $q="CREATE TABLE ".$config['prefix']. $temp . "list ( ";
       $q.="`listId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`listId`), ";
       $q.="KEY `categoryId` (`categoryId`), ";
       $q.="FULLTEXT KEY `description` (`description`), ";
       $q.="FULLTEXT KEY `title` (`title`));";
       send_query($q);
	break;
	case "listItems":
       $q="CREATE TABLE ".$config['prefix']. $temp . "listItems ( ";
       $q.="`listItemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`item` text NOT NULL, ";
       $q.="`notes` text, ";
       $q.="`listId` int(10) unsigned NOT NULL default '0', ";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`listItemId`), ";
       $q.="KEY `listId` (`listId`), ";
       $q.="FULLTEXT KEY `notes` (`notes`), ";
       $q.="FULLTEXT KEY `item` (`item`));"; 
       send_query($q);
	break;
	case "tickler":
       $q="CREATE TABLE ".$config['prefix']. $temp . "tickler ( ";
       $q.="`ticklerId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`date` date  default NULL, ";
       $q.="`title` text NOT NULL, ";
       $q.="`note` longtext, ";
       $q.="`repeat` int(10) unsigned NOT NULL default '0', ";
       $q.="`suppressUntil` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`ticklerId`), ";
       $q.="KEY `date` (`date`), ";
       $q.="FULLTEXT KEY `notes` (`note`), ";
       $q.="FULLTEXT KEY `title` (`title`));";
       send_query($q);
	break;
	case "goals":
	   $q="create table ".$config['prefix']. $temp . "goals (";
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