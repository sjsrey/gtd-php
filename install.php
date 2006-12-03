<?php
	include_once('header.php');

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
    echo "<h2>gtd-php installation/upgrade</h2>\n";

    // get server information for problem reports
    $v="<h3>Installation Info</h3>\n";
    $v.="<ul><li>";
    $v.="php: ".phpversion()."<br>";
    $v.="</li>\n<li>";
    $v.="mysql: ".mysql_get_server_info()."</li></ul>\n";
    echo $v;


    //check if gtd db has been created
    $msg='<font color="red">Unable to select gtd database.<br>Please create the '.$config['db'].' mysql database and rerun this script.';
	mysql_select_db($config['db']) or die ($msg);



    //check if we are doing a new install, an upgrade, or are we current
    $tprojects=0;
    $titemstatus=0;
    //echo $db;
    //checking for table itemstatus (new in 0.6)
    $tables = mysql_list_tables($config['db']);
    while (list($temp) = mysql_fetch_array($tables)){
        //echo "<br>";
        if($temp == 'itemstatus'){
            $titemstatus=1;
        }
        if($temp == 'projects'){
            $tprojects=1;
        }
    }
    //echo $tprojects;
    //echo $titemstatus;
    //upgrade, 0 = current, 1 = new install, 2 = upgrade
    if ($tprojects==$titemstatus){
        if($tprojects==1){
            $upgrade=0;
        }else{
            $upgrade=1;
        }
    }else{
        $upgrade=2;
    }
    echo "<br>";
    if ($upgrade==0){
        echo "<h3>No database upgrade necessary.</h3>";
        $table .="<p> Just click <a href=\"index.php\">here</a> to begin using the new system.";
        echo $table;
    }elseif($upgrade==1){
        echo "<h3>New installation.</h3>";

	$table="<table>\n";
	$table .="<tr>\n<th>Table</th>\n<th>Status</th>\n</tr>";

        //Using heredoc to create tables. last TEST cannot be indented.
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `categories` (
          `categoryId` int(10) unsigned NOT NULL auto_increment,
          `category` text NOT NULL,
          `description` text,
          PRIMARY KEY  (`categoryId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=1;
TEST;
        $result = mysql_query($query);
	if(!$result){
		$flag=0;
	}
        //add some default categories
        $query =<<<TEST
        insert into categories (category, description) values('Professional','Work related.');
TEST;
        $result = mysql_query($query);
	if(!$result){
		$flag=0;
	}
       	$query =<<<TEST
        insert into categories (category, description) values('Personal','Outside of work.');
TEST;
        $result = mysql_query($query);
	if(!$result){
		$flag=0;
	}
    	$table .= report('categories',$flag);
	$flag=1;
       	$query = <<<TEST
        CREATE TABLE `checklist` (
          `checklistId` int(10) unsigned NOT NULL auto_increment,
          `title` text NOT NULL,
          `categoryId` int(10) unsigned NOT NULL default '0',
          `description` text,
          PRIMARY KEY  (`checklistId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Reusable Checklists';
TEST;
        $result = mysql_query($query);
	if(!$result){
		$flag=0;
	}
    	$table .= report('checklist',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `checklistItems` (
          `checklistItemId` int(10) unsigned NOT NULL auto_increment,
          `item` text NOT NULL,
          `notes` text,
          `checklistId` int(10) unsigned NOT NULL default '0',
          `checked` enum('y','n') NOT NULL default 'n',
          PRIMARY KEY  (`checklistItemId`),
          KEY `checklistId` (`checklistId`),
          FULLTEXT KEY `notes` (`notes`),
          FULLTEXT KEY `item` (`item`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Individual Checklist items';
TEST;
        $result = mysql_query($query);
	if(!$result){
		$flag=0;
	}
    	$table .= report('checklistItems',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `context` (
          `contextId` int(10) unsigned NOT NULL auto_increment,
          `name` text NOT NULL,
          `description` text,
          PRIMARY KEY  (`contextId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Item Contexts';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
        //add some default spatial contexts
$query =<<<TEST
        insert into context (name, description) values('Computer','Sitting at a keyboard.');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
$query =<<<TEST
        insert into context (name, description) values('Office','At the office');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
$query =<<<TEST
        insert into context (name, description) values('Phone','Calls');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('context',$flag);
	$flag=1;
	$query = <<<TEST
        CREATE TABLE `goals` (
          `id` int(11) NOT NULL auto_increment,
          `goal` longtext,
          `description` longtext,
          `created` date default NULL,
          `deadline` date default NULL,
          `completed` date default NULL,
          `type` enum('weekly','quarterly') default NULL,
          `projectId` int(11) default NULL,
          PRIMARY KEY  (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('goals',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `itemattributes` (
          `itemId` int(10) unsigned NOT NULL auto_increment,
          `type` enum('a','r','w') NOT NULL default 'a',
          `projectId` int(10) unsigned NOT NULL default '0',
          `contextId` int(10) unsigned NOT NULL default '0',
          `timeframeId` int(10) unsigned NOT NULL default '0',
          `deadline` date default NULL,
          `repeat` int(10) unsigned NOT NULL default '0',
          `suppress` enum('y','n') NOT NULL default 'n',
          `suppressUntil` int(10) unsigned default NULL,
          PRIMARY KEY  (`itemId`),
          KEY `projectId` (`projectId`),
          KEY `contextId` (`contextId`),
          KEY `suppress` (`suppress`),
          KEY `type` (`type`),
          KEY `timeframeId` (`timeframeId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Characteristics of items (action, waiting, reference, etc)';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
	$query=<<<TEST
        insert into itemattributes (projectId, contextId, timeFrameId) values(1,1,1);
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('itemattributes',$flag);
	$flag=1;
	$query = <<<TEST
        CREATE TABLE `items` (
          `itemId` int(10) unsigned NOT NULL auto_increment,
          `title` text NOT NULL,
          `description` longtext,
          PRIMARY KEY  (`itemId`),
          FULLTEXT KEY `title` (`title`),
          FULLTEXT KEY `description` (`description`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='All individual items (runway)-- actions, references, waiting';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
        $query=<<<TEST
        insert into items (title, description) values('Add more projects','Populate my new system');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('items',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `itemstatus` (
          `itemId` int(10) unsigned NOT NULL auto_increment,
          `dateCreated` date NOT NULL default '0000-00-00',
          `lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
          `dateCompleted` date default NULL,
          `completed` int(10) unsigned default NULL,
          PRIMARY KEY  (`itemId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Status of items';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('itemstatus',$flag);
	$flag=1;
        $query=<<<TEST
        insert into itemstatus (dateCreated, lastModified) values(NOW(),NOW());
TEST;
        $result = mysql_query($query);

        $query = <<<TEST
        CREATE TABLE `list` (
          `listId` int(10) unsigned NOT NULL auto_increment,
          `title` text NOT NULL,
          `categoryId` int(10) unsigned NOT NULL default '0',
          `description` text,
          PRIMARY KEY  (`listId`),
          KEY `categoryId` (`categoryId`),
          FULLTEXT KEY `description` (`description`),
          FULLTEXT KEY `title` (`title`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Unordered lists';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('list',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `listItems` (
          `listItemId` int(10) unsigned NOT NULL auto_increment,
          `item` text NOT NULL,
          `notes` text,
          `listId` int(10) unsigned NOT NULL default '0',
          `dateCompleted` date default '0000-00-00',
          PRIMARY KEY  (`listItemId`),
          KEY `listId` (`listId`),
          FULLTEXT KEY `notes` (`notes`),
          FULLTEXT KEY `item` (`item`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Individual list items';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('listItems',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `nextactions` (
          `projectId` int(10) unsigned NOT NULL default '0',
          `nextaction` int(10) unsigned NOT NULL default '0',
          PRIMARY KEY  (`projectId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Identifies an item as a next action for a project';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
        // add a default next action
        $query=<<<TEST
        insert into nextactions (projectId, nextaction) values(1,1);
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('nextactions',$flag);
	$flag=1;
$query = <<<TEST
        CREATE TABLE `projects` (
          `projectId` int(10) unsigned NOT NULL auto_increment,
          `name` text NOT NULL,
          `description` text,
          `desiredOutcome` text,
          PRIMARY KEY  (`projectId`),
          FULLTEXT KEY `desiredOutcome` (`desiredOutcome`),
          FULLTEXT KEY `name` (`name`),
          FULLTEXT KEY `description` (`description`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Projects (10,000ft view)';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
        //add a default project
        $query=<<<TEST
        insert into projects (name, description, desiredOutcome) values('gtd','Getting Things Done','Mind like water.');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('projects',$flag);
        $query = <<<TEST
        CREATE TABLE `projectattributes` (
          `projectId` int(10) unsigned NOT NULL auto_increment,
          `categoryId` int(10) unsigned NOT NULL default '1',
          `isSomeday` enum('y','n') NOT NULL default 'n',
          `deadline` date default NULL,
          `repeat` int(11) unsigned NOT NULL default '0',
          `suppress` enum('y','n') NOT NULL default 'n',
          `suppressUntil` int(10) unsigned default NULL,
          PRIMARY KEY  (`projectId`),
          KEY `categoryId` (`categoryId`),
          KEY `isSomeday` (`isSomeday`),
          KEY `suppress` (`suppress`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Characteristics of projects';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
$query = <<<TEST
        insert into `projectattributes` (categoryId) values (1);
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('projectattributes',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `projectstatus` (
          `projectId` int(10) unsigned NOT NULL auto_increment,
          `dateCreated` date NOT NULL default '0000-00-00',
          `lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
          `dateCompleted` date default NULL,
          PRIMARY KEY  (`projectId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Status of projects';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
$query = <<<TEST
        insert into `projectstatus` (dateCreated, lastModified) values (NOW(),NOW());
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('projectstatus',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `tickler` (
          `ticklerId` int(10) unsigned NOT NULL auto_increment,
          `date` date NOT NULL default '0000-00-00',
          `title` text NOT NULL,
          `note` longtext,
          PRIMARY KEY  (`ticklerId`),
          KEY `date` (`date`),
          FULLTEXT KEY `notes` (`note`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Tickler file';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('tickler',$flag);
	$flag=1;
        $query = <<<TEST
        CREATE TABLE `timeitems` (
          `timeframeId` int(10) unsigned NOT NULL auto_increment,
          `timeframe` text NOT NULL,
          `description` text,
          PRIMARY KEY  (`timeframeId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Item timeframes';
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
        //add some default temporal contexts
$query =<<<TEST
        insert into timeitems (timeframe, description) values('Short','< 10 Minutes');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
$query =<<<TEST
        insert into timeitems (timeframe, description) values('Medium','< 10-30 Minutes');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
$query =<<<TEST
        insert into timeitems (timeframe, description) values('Long','> 30 Minutes');
TEST;
        $result = mysql_query($query);
	if(!$result){ $flag=0; }
    	$table .= report('timeitems',$flag);
	$flag=1;
	$table .="</table>";

    $table .="<p>There are 16 tables. If all were created with ";
    $table .='<font color="green">Success</font>';
    $table .=", then you are ready to start ";
    $table .="using gtd-php. Just click <a href=\"index.php\">here</a> to begin. If any of the tables reported ";
    $table .='<font color="red">Failure</font>';
    $table .=", then please report this to the <a href=\"http://toae.org/boards\">user forum.</a>";
	echo $table;

    }elseif($upgrade=2){
        echo "Upgrading to version 0.6";
        echo "<br>";
        //create three new tables from old nextActions table
        // items first
        echo "Creating table items<br>";
        $query = "select nextActionId, title, description from nextActions";
        $result = mysql_query($query);
        $query="create table items (itemId int(10) unsigned not null auto_increment, title text not null, description longtext, primary key (itemId))";
        $result = mysql_query($query);
        $query="insert into items(title, description) select  title,description from nextActions";
        $result = mysql_query($query);
        $query="select max(itemId) from items";
        $result=mysql_query($query);
        $tmp=mysql_fetch_row($result);
        $nacount=$tmp[0];
        //echo $nacount;
        // add reference items
        $query="insert into items(title, description) select title, description from reference";
        $result = mysql_query($query);
        echo "Creating table itemstatus<br>";
        $query="create table itemstatus(itemId int unsigned not null auto_increment, dateCreated date not null, lastModified timestamp, dateCompleted date, completed int(10) unsigned, primary key(itemId))";
        $result = mysql_query($query);

        $query="insert into itemstatus(dateCreated, dateCompleted, completed) select  dateCreated, dateCompleted, completed from nextActions";
        $result = mysql_query($query);
        //now add any references
        $query="insert into itemstatus(dateCreated) select  dateCreated from reference";
        $result = mysql_query($query);

        echo "Creating table itemattributes<br>";
        $query="create table itemattributes (itemId int unsigned not null auto_increment, type enum('a','r','w') not null default 'a',
                 projectId int(10) unsigned not null default 0,
                 contextId int(10)  unsigned not null default 1,
                 timeframeId int(10) unsigned not null default 1,
                 deadline date,
                 `repeat` int(10) unsigned not null default 0,
                 suppress enum('y','n') not null default 'n',
                 suppressUntil int(10) unsigned default NULL,
                 primary key(itemId))";
        $result = mysql_query($query);
                $query="insert into itemattributes(projectId, contextId, timeframeId, deadline, `repeat`) select  projectId, contextId, timeId, deadline, repeat from nextActions";
        $result = mysql_query($query);
        // add references
        $query="insert into itemattributes(projectId) select  projectId from reference";
        $result = mysql_query($query);
        //update reference type
        $query="update itemattributes set type='r' where itemId > '$nacount'";
        $result = mysql_query($query);

        // add waiting ons
        $query="select max(itemId) from items";
        $result = mysql_query($query);
        $tmp=mysql_fetch_row($result);
        $nacount=$tmp[0];
        $query="insert into items(title, description) select title, description from waitingOn";
        $result = mysql_query($query);
        $query="insert into itemstatus(dateCreated, dateCompleted) select  dateCreated, dateCompleted from waitingOn";
        $result = mysql_query($query);
        $query="insert into itemattributes(projectId) select  projectId from waitingOn";
        $result = mysql_query($query);
        $query="update itemattributes set type='w' where itemId > '$nacount'";
        $result = mysql_query($query);

        //get rid of 0 offset for timeId
        $query="update itemattributes set timeframeId = timeframeId+1";
        $result = mysql_query($query);

        //modify nextActions table
        echo "Modifying nextActions table<br>";
        $query =<<<TEST
        CREATE TABLE `nextaction` (
          `projectId` int(10) unsigned NOT NULL default '0',
          `nextaction` int(10) unsigned NOT NULL default '0',
          PRIMARY KEY  (`projectId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Identifies an item as a next action for a project';
TEST;
        $result = mysql_query($query);
$query = <<<TEST
        CREATE TABLE `tickler` (
          `ticklerId` int(10) unsigned NOT NULL auto_increment,
          `date` date NOT NULL default '0000-00-00',
          `title` text NOT NULL,
          `note` longtext,
          PRIMARY KEY  (`ticklerId`),
          KEY `date` (`date`),
          FULLTEXT KEY `notes` (`note`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Tickler file';
TEST;
        $result = mysql_query($query);

        $query='drop table nextActions';
        $result = mysql_query($query);
        $query='alter table nextaction rename nextactions';
        $result = mysql_query($query);

        //modify projects
        echo "Modifying projects table<br>";

        $query = <<<TEST
        CREATE TABLE `projectattributes` (
          `projectId` int(10) unsigned NOT NULL auto_increment,
          `categoryId` int(10) unsigned NOT NULL default '1',
          `isSomeday` enum('y','n') NOT NULL default 'n',
          `deadline` date default NULL,
          `repeat` int(11) unsigned NOT NULL default '0',
          `suppress` enum('y','n') NOT NULL default 'n',
          `suppressUntil` int(10) unsigned default NULL,
          PRIMARY KEY  (`projectId`),
          KEY `categoryId` (`categoryId`),
          KEY `isSomeday` (`isSomeday`),
          KEY `suppress` (`suppress`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Characteristics of projects';
TEST;
        $result = mysql_query($query);

        $query = <<<TEST
        CREATE TABLE `projectsTemp` (
          `projectId` int(10) unsigned NOT NULL auto_increment,
          `name` text NOT NULL,
          `description` text,
          `desiredOutcome` text,
          PRIMARY KEY  (`projectId`),
          FULLTEXT KEY `desiredOutcome` (`desiredOutcome`),
          FULLTEXT KEY `name` (`name`),
          FULLTEXT KEY `description` (`description`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Projects (10,000ft view)';
TEST;
        $result = mysql_query($query);
        $query = <<<TEST
        CREATE TABLE `projectstatus` (
          `projectId` int(10) unsigned NOT NULL auto_increment,
          `dateCreated` date NOT NULL default '0000-00-00',
          `lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
          `dateCompleted` date default NULL,
          PRIMARY KEY  (`projectId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Status of projects';
TEST;
        $result = mysql_query($query);

        $query="insert into projectattributes(projectId, isSomeday, categoryId) select projectId, isSomeday, categoryId from projects";
        $result = mysql_query($query);

        $query="insert into projectsTemp(projectId, name, description, desiredOutcome) select projectId, name, description, desiredOutcome from projects";
        $result = mysql_query($query);

        $query="insert into projectstatus(projectId, dateCreated, dateCompleted) select projectId, dateCreated, dateCompleted from projects";
        $result = mysql_query($query);
        $query='drop table projects';
        $result = mysql_query($query);
        $query='alter table projectsTemp rename projects';
        $result = mysql_query($query);

        $query='drop table maybe';
        $result = mysql_query($query);

        $query='drop table maybeSomeday';
        $result = mysql_query($query);

        $query='drop table reference';
        $result = mysql_query($query);

        echo "Creating timeitems table<br>";
        $query = <<<TEST
            CREATE TABLE `timeitems` (
              `timeframeId` int(10) unsigned NOT NULL auto_increment,
              `timeframe` text NOT NULL,
              `description` text,
              PRIMARY KEY  (`timeframeId`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Item timeframes';
TEST;
        $result = mysql_query($query);
        $query='insert into timeitems(timeframeid) select distinct timeframeId from itemattributes';
        $result = mysql_query($query);
        // loop over unique timefameIds and create timeframeNames: t1, t2,
        // t3, ...
        $query='select distinct timeframeId from itemattributes';
        $results=mysql_query($query);
        while ($id = mysql_fetch_assoc($results)){
            $label = $id['timeframeId'];
            $label="T $label";
            $tid=$id['timeframeId'];
            $query = "update timeitems set timeframe = '$label' where timeframeId = '$tid'";
            $result = mysql_query($query);
        }

    }
	include_once('footer.php');
?>
