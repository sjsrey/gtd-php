<?php
/*
    MySQL extensions to standard SQL have been avoided where known & where practical
  ===============================================================

*/
function connectdb($config) {

    $connection = mysql_connect($config['host'], $config['user'], $config['pass'])
        or die ("Unable to connect to MySQL server: check your host, user and pass settings in config.inc.php!");
        
    mysql_select_db($config['db'])
        or die ("Unable to select database '{$config['db']}' - check your db setting in config.inc.php!");
        
    if (!empty($config['charset'])) {
        $charset=preg_replace('/\W/u','',$config['charset']);
        rawQuery('SET NAMES '.$charset);
    }

    return $connection;
}
/*
  ===============================================================
*/
function getDBVersion() {
    return mysql_get_server_info();
}
/*
  ===============================================================
*/
function getDBtables($db) {
    $tablelist=array();
    $tables=mysql_list_tables($db);
	while ($tbl = mysql_fetch_row($tables))
	   array_push($tablelist,$tbl[0]);
    return $tablelist;
}
/*
  ===============================================================
*/
function doQuery($query,$label=NULL) {
    // parse result into multitdimensional array $result[row#][field name] = field value
    $reply = rawQuery($query);
    if ($reply===false) {                       // failed query - return FALSE
        $result=false;
    } elseif ($reply===true) {                  // query was not a SELECT OR SHOW, so return number of rows affected
        $result=@mysql_affected_rows();
    } else if (@mysql_num_rows($reply)===0) {   // empty SELECT/SHOW - return zero
        $result=0;
    } else {                                    // successful SELECT/SHOW - return array of results
        $result=array();
        while ($mysql_result = mysql_fetch_assoc($reply))
            $result[]=$mysql_result;
    }

    /* get last autoincrement insert id:
        only valid for insert statements using autoincrement values;
        not updated when explicit value given for autoincrement field
        (MySQL "feature")
    */
    $GLOBALS['lastinsertid'] = mysql_insert_id();

    $error = mysql_errno();
    if ($error) $_SESSION['message'][]=
                "Error $error in query '$label': '".mysql_error()."'";
                
    return $result;
}
/*
  ===============================================================
*/
function rawQuery($query) {
    $reply = mysql_query($query);
    return $reply;
}
/*
  ===============================================================
*/
function safeIntoDB($value,$key=NULL) {
	if (is_array($value)) {
        // don't clean arrays - clean individual strings/values by calling self recursively
		foreach ($value as $key=>$string) $value[$key] = safeIntoDB($string,$key);
		return $value;
	} else {
		// don't clean filters - we've cleaned those separately in the sqlparts function
		if (strpos($key,'filterquery')===false
			&& !preg_match("/^'\d\d\d\d-\d\d-\d\d'$/",$value) ) // and don't clean dates
			{
			if ( get_magic_quotes_gpc() && !empty($value) && is_string($value) )
				$value = stripslashes($value);
			if(version_compare(phpversion(),"4.3.0",'<'))
				$value = mysql_escape_string($value);
			else
				$value = mysql_real_escape_string($value);
		} else { return $value;}
		return $value;
	}
}
/*
   ======================================================================================
*/
function backupData($prefix) {
    $sep="-- *******************************\n";
    $tables=array('categories','context','items','itemstatus','lookup','preferences','tagmap','timeitems','version');
    $data='';
    $header='';
    $creators='';
    foreach ($tables as $tab) {
        $table=$prefix.$tab;
        $data .=$sep;
        $header .="TRUNCATE TABLE `$table`;\n";
		$tableStructure = @mysql_fetch_assoc(rawQuery("SHOW CREATE TABLE $table"));
        $creators .= "DROP TABLE IF EXISTS `{$table}`; \n".$tableStructure['Create Table'].";\n";
        $rows = rawQuery("SELECT * FROM `$table`",false);
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
   data cleaning functions
   ======================================================================================
*/
function checkErrors($prefix) {

    $errors=$totals=array();
    
    $q="SELECT COUNT(*) FROM `{$prefix}items`";
    $val=@mysql_fetch_row(rawQuery($q));
    if (empty($val)) return false;
    $totals['items']=(int) $val[0];
    
    $q="SELECT COUNT(*) FROM `{$prefix}itemstatus`
            WHERE `dateCompleted` IS NULL AND `nextaction`='y'";
    $val=@mysql_fetch_row(rawQuery($q));
    $totals['next actions']=(int) $val[0];
    
    $q="SELECT COUNT(*) FROM `{$prefix}itemstatus`
            WHERE `dateCompleted` IS NULL AND
            (
                (`type` NOT IN ('i','m','L','C')
                    AND `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}lookup`)
                )
            OR `type`='' OR `type` IS NULL
            )";
    $val=@mysql_fetch_row(rawQuery($q));
    $totals['orphans']=(int) $val[0];

    /* -----------------------------------------------------
            count errors
    */
    $q="SELECT COUNT(*) FROM `{$prefix}items` where `title`=NULL OR `title`=''";
    $val=@mysql_fetch_row(rawQuery($q));
    $errors['missing titles']=(int) $val[0];
    
    $q="SELECT COUNT(*) FROM `{$prefix}lookup` WHERE
            `parentId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)
           OR `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)";
    $val=@mysql_fetch_row(rawQuery($q));
    $errors['redundant parent entries']=(int) $val[0];
    
    $q="SELECT COUNT(version) FROM `{$prefix}version`";
    $val=@mysql_fetch_row(rawQuery($q));
    $errors['redundant version tags']=-1+(int) $val[0];

    $q="SELECT COUNT(`itemId`) FROM `{$prefix}itemstatus` WHERE `type` IS NULL OR `type`=''";
    $val=@mysql_fetch_row(rawQuery($q));
    $errors['items with no type']=(int) $val[0];

    $q="SELECT COUNT(*) FROM `{$prefix}tagmap` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}itemstatus`)";
    $val=@mysql_fetch_row(rawQuery($q));
    $errors['orphaned tags']=(int) $val[0];
    
    // partial items, missing from one or more item tables
    $partialitems=0;
    $items1=array('itemstatus'=>'items','items'=>'itemstatus');
    foreach ($items1 as $t1=>$t2) {
        $q="SELECT COUNT(DISTINCT `itemId`) FROM `{$prefix}$t1` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}$t2`)";
        $val=@mysql_fetch_row(rawQuery($q));
        $partialitems+=(int) $val[0];
    }
    $errors["Item IDs are missing from some tables "]=$partialitems;

    // look for items that are ancestors of themselves
    $errors['Parent loops']='';
    $loops=scanforcircularparents();
    $sep='';
    if (count($loops)) foreach ($loops as $id) {
        $errors['Parent loops'].="$sep<a href='itemReport.php?itemId=$id'>$id</a>";
        $sep=', ';
    }
    if ($errors['Parent loops']==='') $errors['Parent loops']=0;
    
    return array('totals'=>$totals,'errors'=>$errors);
}
/*
   ======================================================================================
*/
function fixData($prefix) {
    foreach ( array( 'deadline','tickledate','dateCompleted','dateCreated',
                'lastModified') as $field) {
        // change dates of "0000-00-00" to NULL
        $q="UPDATE `{$prefix}itemstatus` SET `$field`=NULL where `$field`='0000-00-00'";
        rawQuery($q);
    }
    
    // remove duplicate version tags
    $q="CREATE TABLE `{$prefix}versiontemp`
            SELECT * FROM `{$prefix}version` WHERE `updated` >= ALL
                (SELECT `updated` FROM `{$prefix}version`)";
    rawQuery($q);
    
    $q="TRUNCATE `{$prefix}version`";
    rawQuery($q);

    $q="INSERT INTO `{$prefix}version` SELECT * FROM `{$prefix}versiontemp`";
    rawQuery($q);

    $q="DROP TABLE `{$prefix}versiontemp`";
    rawQuery($q);

	// remove unwanted line breaks from title field - allowed in 0.7 for goals, but not in 0.8 or later
    $q="UPDATE `{$prefix}items` SET `title`=replace(replace(`title`,'\r',' '),'\n',' ')";
    rawQuery($q);

    // it's possible that some legacy items might have no itemstatus: fix that now
    $q="INSERT INTO `{$prefix}itemstatus` (`itemId`)
            SELECT `itemId` from `{$prefix}items` WHERE `itemId` NOT IN
                (SELECT `itemId` FROM `{$prefix}itemstatus`)";
    rawQuery($q);

    // remove partial items from database
    $items1=array('itemstatus'=>'items','items'=>'itemstatus');
    foreach ($items1 as $t1=>$t2) {
        $q="DELETE FROM `{$prefix}$t1` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}$t2`)";
        rawQuery($q);
    }

    // repair empty types - make inbox items by default
    $q="UPDATE `{$prefix}itemstatus` SET `type`='i' WHERE `type` IS NULL OR `type`=''";
    rawQuery($q);
    
    // remove orphaned tags
    $q="DELETE FROM `{$prefix}tagmap` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}itemstatus`)";
    rawQuery($q);

    // repair empty dates for fields where date should not be null
    $q="UPDATE `{$prefix}itemstatus` SET `lastModified`=CURDATE() WHERE `lastModified` IS NULL";
    rawQuery($q);
    
    $q="UPDATE `{$prefix}itemstatus` SET `dateCreated`=CURDATE() WHERE `dateCreated` IS NULL";
    rawQuery($q);

    // repair impossible dates - by default, MySQL v4.x allowed dates such as 2008-13-51
    $q="UPDATE `{$prefix}itemstatus`
             SET `dateCompleted` = `dateCompleted`+'0 DAY',
                  `deadline`     = `deadline`     +'0 DAY',
                  `tickledate`   = `tickledate`   +'0 DAY' ";
    rawQuery($q);

    // if any titles are blank, call them 'untitled'
    $q="UPDATE `{$prefix}items` SET `title`='untitled' WHERE `title` IS NULL OR `title`=''";
    rawQuery($q);

    // now fix lookup
    $q="DELETE FROM `{$prefix}lookup` WHERE
            `parentId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)
           OR `itemId` NOT IN (SELECT `itemId` FROM `{$prefix}items`)";
    rawQuery($q);
}
/*
  ===============================================================
GENERAL RULES:
    "select*" = query for something by its id; a single-row result
    "get*" = query for something of a particular type; a multi-row result
    "new*", "update*", "delete*" are self-explanatory
    "complete*" = set status to completed
    "remove*" = remove by association Id (items associated with a project, etc)
    "Count*" = # of a particular type in table
    "*selectbox" = get results to create a selectbox- for assignment or filter
*/
function getsql($querylabel,$values,$sort) {

    $values = safeIntoDB($values);
    $prefix=$_SESSION['prefix'];

    switch ($querylabel) {
	
		case "categoryselectbox":
			$sql="SELECT c.`categoryId`, c.`category`, c.`description`
				FROM `{$prefix}categories` as c
				ORDER BY {$sort['categoryselectbox']}";
			break;

		case "completeitem":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `dateCompleted`={$values['dateCompleted']}, `lastModified` = NULL
				WHERE `itemId`='{$values['itemId']}'";
			break;

        case 'countactionsbycontext':
            $sql="SELECT cn.`name` AS cname,cn.`contextId`,COUNT(its.`itemId`) AS count
            FROM `{$prefix}itemstatus` AS its
			LEFT OUTER JOIN `{$prefix}context` AS cn
				ON (its.`contextId` = cn.`contextId`)
            JOIN (
                SELECT DISTINCT `itemId` FROM `{$prefix}lookup` AS lu
                    JOIN (SELECT i.`itemId` AS parentId,
                             its.`isSomeday` AS pisSomeday,
                             its.`deadline` AS pdeadline,
				             its.`tickledate` AS ptickledate,
				             its.`dateCompleted` AS pdateCompleted
    					   FROM `{$prefix}items` AS i
    					   JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
                        ) AS y USING (`parentId`)
            ) AS lut ON (its.`itemId`=lut.`itemId`)
             {$values['filterquery']}
             GROUP BY its.`contextId` ORDER BY cn.`name`";
            break;
            
        case 'countdoneactionsbyweek':
            $sql="SELECT `dateCompleted`,
                        truncate(datediff(curdate(),`dateCompleted`)/7,0) AS `weeksago`,
                        count(*) AS `numdone`
                  FROM `{$prefix}itemstatus`
                  WHERE `dateCompleted` IS NOT NULL AND `type`='a'
                  GROUP BY `weeksago` ORDER BY `dateCompleted` ASC";
            break;

        case 'countdonebyinterval':
            $sql="SELECT `type`,
                         interval(datediff(curdate(),`dateCompleted`),7,30,90,365) AS `daysago`,
                         count(*) AS `numdone`
                  FROM `{$prefix}itemstatus`
                  WHERE `dateCompleted` IS NOT NULL
                  GROUP BY `type`,`daysago`";
            break;
            
		case "countnextactions":
			$sql="SELECT INTERVAL(DATEDIFF(CURDATE(),x.`deadline`),-6,0,1) AS `duecategory`,
			        COUNT(DISTINCT x.`itemId`) AS nactions,
                    COUNT(DISTINCT IF(x.`nextaction`='y',x.`itemId`,NULL)) as nnextactions
                    FROM (
						SELECT
							its.`itemId`,its.`type`, its.`deadline`, its.`nextaction`,
                            its.`tickledate`, its.`dateCompleted`, lu.`parentId`
						FROM `{$prefix}itemstatus` AS its
						LEFT OUTER JOIN `{$prefix}lookup` AS lu USING (`itemId`)
						{$values['childfilterquery']}
				    ) as x
    				LEFT OUTER JOIN (
						SELECT
							`itemId` AS parentId, `isSomeday` AS pisSomeday,
							`tickledate` AS ptickledate,`dateCompleted` AS pdateCompleted
						FROM `{$prefix}itemstatus`
					) as y USING(`parentId`)
				{$values['filterquery']}
                GROUP BY `duecategory`";
			break;

		case "countspacecontexts":
			$sql="SELECT COUNT(*)
				FROM `{$prefix}context`";
			break;

        case 'counttype':
            $sql="SELECT COUNT(*) AS cnt FROM `{$prefix}itemstatus` AS its {$values['filterquery']}";
            break;

		case "deletecategory":
			$sql="DELETE FROM `{$prefix}categories` WHERE `categoryId`='{$values['id']}'";
			break;

		case "deleteitem":
			$sql="DELETE FROM `{$prefix}items` WHERE `itemId`='{$values['itemId']}'";
			break;
			
		case "deleteitemstatus":
			$sql="DELETE FROM `{$prefix}itemstatus` WHERE `itemId`='{$values['itemId']}'";
			break;
			
		case "deletelookup":
			$sql="DELETE FROM `{$prefix}lookup` WHERE `itemId` ='{$values['itemId']}'";
			break;
			
		case "deletelookupparents":
			$sql="DELETE FROM `{$prefix}lookup` WHERE `parentId` ='{$values['itemId']}'";
			break;
			
		case "deletespacecontext":
			$sql="DELETE FROM `{$prefix}context` WHERE `contextId`='{$values['id']}'";
			break;
			
		case "deletetimecontext":
			$sql="DELETE FROM `{$prefix}timeitems` WHERE `timeframeId`='{$values['id']}'";
			break;

		case "getchildren":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`,
					i.`desiredOutcome`, its.`type`,
					IF(its.`isSomeday`='y','y','n') AS isSomeday, 
                    UNIX_TIMESTAMP(its.`deadline`) AS `deadline`,
                    DATEDIFF(CURDATE(),its.`deadline`) AS `daysdue`,
                    i.`recurdesc`,i.`recur`,
					UNIX_TIMESTAMP(its.`tickledate`) AS `tickledate`,
                    its.`nextaction`,
					UNIX_TIMESTAMP(its.`dateCreated`) AS `dateCreated`, 
                    UNIX_TIMESTAMP(its.`dateCompleted`) AS `dateCompleted`,
					UNIX_TIMESTAMP(its.`lastModified`) AS `lastModified`,
                    its.`categoryId`,c.`category`, its.`contextId`,
					cn.`name` AS cname, its.`timeframeId`, ti.`timeframe`
				FROM `{$prefix}lookup` AS lu
					JOIN `{$prefix}items` AS i USING (`itemId`)
					JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}context` AS cn USING (`contextId`)
					LEFT OUTER JOIN `{$prefix}categories` AS c USING (`categoryId`)
					LEFT OUTER JOIN `{$prefix}timeitems` AS ti USING (`timeframeId`)
				WHERE lu.`parentId`= '{$values['parentId']}' {$values['filterquery']}
				ORDER BY {$sort['getchildren']}";
			break;

		case "getgtdphpversion":
			$sql="SELECT `version` FROM `{$prefix}version`";
			break;

		case "getitems":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`, its.`deadline`,
                    DATEDIFF(CURDATE(),its.`deadline`) AS `daysdue`
				FROM `{$prefix}items` AS i
					JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}context` as cn
						ON (its.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `{$prefix}categories` as c
						ON (its.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `{$prefix}timeitems` as ti
						ON (its.`timeframeId` = ti.`timeframeId`) "
                .$values['filterquery'].
				" ORDER BY {$sort['getitems']}";
			break;

		case "getitemsandparent":
			$sql="SELECT
    				x.`itemId`, x.`title`, x.`description`,
    				x.`desiredOutcome`, x.`type`, x.`isSomeday`,
    				UNIX_TIMESTAMP(x.`deadline`) AS `deadline`,
                    x.`recurdesc`, x.`recur`,
    				DATEDIFF(CURDATE(),x.`deadline`) AS `daysdue`,
    				UNIX_TIMESTAMP(x.`tickledate`) AS `tickledate`,
                    UNIX_TIMESTAMP(x.`dateCreated`) AS `dateCreated`,
                    UNIX_TIMESTAMP(x.`dateCompleted`) AS `dateCompleted`,
    				UNIX_TIMESTAMP(x.`lastModified`) AS `lastModified`,
                    x.`categoryId`, x.`category`,
    				x.`contextId`, x.`cname`, x.`timeframeId`,
    				x.`timeframe`,x.`nextaction`,
    				GROUP_CONCAT(DISTINCT y.`parentId` ORDER BY y.`ptitle`) as `parentId`,
    				GROUP_CONCAT(DISTINCT y.`ptype`    ORDER BY y.`ptitle`) as `ptype`,
    				GROUP_CONCAT(DISTINCT y.`ptitle` ORDER BY y.`ptitle` SEPARATOR '{$_SESSION['config']['separator']}') AS `ptitle`,
                    GROUP_CONCAT(DISTINCT tm.`tagname` ORDER BY `tagname` SEPARATOR ',') AS tags
    				{$values['extravarsfilterquery']}
				FROM (
						SELECT
							i.`itemId`, i.`title`, i.`description`,
							i.`desiredOutcome`, its.`type`, its.`isSomeday`,
							its.`deadline`, i.`recurdesc`, i.`recur`,its.`nextaction`,
							its.`tickledate`, its.`dateCreated`,
							its.`dateCompleted`, its.`lastModified`,
							its.`categoryId`, c.`category`, its.`contextId`,
							cn.`name` AS cname, its.`timeframeId`,
							ti.`timeframe`, lu.`parentId`
						FROM `{$prefix}items` AS i
							JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
							LEFT OUTER JOIN `{$prefix}lookup` as lu
								ON (i.`itemId` = lu.`itemId`)
							LEFT OUTER JOIN `{$prefix}context` as cn
								ON (its.`contextId` = cn.`contextId`)
							LEFT OUTER JOIN `{$prefix}categories` as c
								ON (its.`categoryId` = c.`categoryId`)
							LEFT OUTER JOIN `{$prefix}timeitems` as ti
								ON (its.`timeframeId` = ti.`timeframeId`)
                            {$values['childfilterquery']}
				) as x
				LEFT OUTER JOIN `{$prefix}tagmap` as tm
					ON (x.`itemId` = tm.`itemId`)
				LEFT OUTER JOIN (
						SELECT
							i.`itemId` AS parentId, i.`title` AS ptitle,
							i.`description` AS pdescription,
							i.`desiredOutcome` AS pdesiredOutcome,
							its.`type` AS ptype, its.`isSomeday` AS pisSomeday,
							its.`deadline` AS pdeadline, i.`recurdesc` AS precurdesc,
							its.`tickledate` AS ptickledate,
							its.`dateCompleted` AS pdateCompleted
						FROM `{$prefix}items` AS i
						JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					) as y ON (y.`parentId` = x.`parentId`)
				{$values['filterquery']} GROUP BY x.`itemId`
				ORDER BY {$sort['getitemsandparent']}";
			break;

		case "getitembrief":
			$sql="SELECT `title`, `description`, `desiredOutcome`
				FROM  `{$prefix}items`
				WHERE `itemId` = {$values['itemId']}";
			break;

        case 'getoptions':
            $sql="SELECT `option`,`value` FROM `{$prefix}preferences`
                    WHERE `uid`='{$values['uid']}' {$values['filterquery']}";
            break;

		case "getorphaneditems":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`, its.`type`, its.`isSomeday`
				FROM `{$prefix}items` AS i   
				JOIN `{$prefix}itemstatus` AS its USING (itemId)
				WHERE its.`dateCompleted` IS NULL
				AND ( ( its.`itemId` NOT IN
						  (SELECT lu.`itemId` FROM `{$prefix}lookup` as lu)
					    {$values['orphansfilterquery']}
                       ) OR its.`type` IS NULL OR its.`type`=''
                ) ORDER BY {$sort['getorphaneditems']}";
			break;

        case 'getparents':
            $sql="SELECT * FROM `{$prefix}lookup`";
            break;
            
		case "getspacecontexts":
			$sql="SELECT `contextId`, `name`
				FROM `{$prefix}context` ORDER BY `name` ASC";
			break;

		case "gettags":
			$sql="SELECT DISTINCT `tagname` FROM `{$prefix}tagmap` AS i
                    {$values['filterquery']} ORDER BY `tagname`";
			break;

		case "gettimecontexts":
			$sql="SELECT `timeframeId`, `timeframe`, `description`
				FROM `{$prefix}timeitems` AS ti
				{$values['timefilterquery']} ORDER BY `timeframeId` ASC";
			break;

		case "newcategory":
			$sql="INSERT INTO `{$prefix}categories`
				VALUES (NULL, '{$values['name']}', '{$values['description']}')";
			break;

		case "newitem":
			$sql="INSERT INTO `{$prefix}items`
						(`title`,`description`,`desiredOutcome`,`recurdesc`,`recur`)
				VALUES ('{$values['title']}','{$values['description']}',
						'{$values['desiredOutcome']}',
                        '{$values['recurdesc']}','{$values['recur']}')";
			break;

		case "newitemstatus":
			$sql="INSERT INTO `{$prefix}itemstatus`
						(`itemId`,`dateCreated`,`lastModified`,`dateCompleted`,
                        `type`,`categoryId`,`isSomeday`,`contextId`,
						`timeframeId`,`deadline`,`tickledate`,`nextaction`)
				VALUES ('{$values['newitemId']}',CURRENT_DATE,NULL,{$values['dateCompleted']},
                        '{$values['type']}','{$values['categoryId']}',
                        '{$values['isSomeday']}','{$values['contextId']}',
                        '{$values['timeframeId']}',{$values['deadline']},
                        {$values['tickledate']},'{$values['nextaction']}')";
			break;

		case "newparent":
			$sql="INSERT INTO `{$prefix}lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')";
			break;

/*        case 'newperspective':
            $sql="INSERT INTO `{$prefix}perspectives`
                    (`id`,`name`,`sort`,`columns`,`show`)
                VALUES (
                    SHA1('{$values['sort']}{$values['columns']}{$values['show']}'),
                    '{$values['name']}','{$values['sort']}','{$values['columns']}','{$values['show']}'
                )    ON DUPLICATE KEY UPDATE `name`='{$values['name']}' ";
            break;

        case 'newperspectivemap':
            $sql="INSERT INTO `{$prefix}perspectivemap` (`filter`,`id`)
                    VALUES ('{$values['uri']}','{$values['perspectiveid']}')
                    ON DUPLICATE KEY UPDATE `id`='{$values['perspectiveid']}'";
            break;
*/
		case "newspacecontext":
			$sql="INSERT INTO `{$prefix}context`
						(`name`,`description`)
				VALUES ('{$values['name']}', '{$values['description']}')";
			break;

        case "newtagmap":
            $sql="INSERT INTO `{$prefix}tagmap` (`itemId`,`tagname`) "
                ." VALUES ({$values['itemId']},'{$values['tagname']}') "
                ." ON DUPLICATE KEY UPDATE `tagname`='{$values['tagname']}'";
            break;

		case "newtimecontext":
			$sql="INSERT INTO `{$prefix}timeitems`
						(`timeframe`,`description`,`type`)
				VALUES ('{$values['name']}', '{$values['description']}', '{$values['type']}')";
			break;

		case "parentselectbox":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, its.`isSomeday`,its.`type`
				FROM `{$prefix}items` as i
				JOIN `{$prefix}itemstatus` as its USING (`itemId`)
				WHERE (its.`dateCompleted` IS NULL) {$values['ptypefilterquery']}
				ORDER BY its.`type`,i.`title`";
				#ORDER BY {$sort['parentselectbox']}";
			break;

		case "reassigncategory":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `categoryId`='{$values['newId']}'
				WHERE `categoryId`='{$values['id']}'";
			break;

		case "reassignspacecontext":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `contextId`='{$values['newId']}'
				WHERE `contextId`='{$values['id']}'";
			break;

		case "reassigntimecontext":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `timeframeId`='{$values['newId']}'
				WHERE `timeframeId`='{$values['id']}'";
			break;

        case "removeitemtags":
            $sql="DELETE FROM `{$prefix}tagmap` WHERE `itemId`='{$values['itemId']}'";
			break;

		case "selectcategory":
			$sql="SELECT `categoryId`, `category`, `description`
				FROM `{$prefix}categories`
				WHERE `categoryId` = '{$values['categoryId']}'";
			break;

		case "selectcontext":
			$sql="SELECT `contextId`, `name`, `description`
				FROM `{$prefix}context`
				WHERE `contextId` = '{$values['contextId']}'";
			break;

		case "selectitem":
			$sql="SELECT i.*,its.*,
				    c.`category`, ti.`timeframe`,cn.`name` AS `cname`,
                    GROUP_CONCAT(tm.`tagname` ORDER BY tm.`tagname` SEPARATOR ',') AS tagname
				FROM `{$prefix}items`      AS i
				JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
                LEFT OUTER JOIN `{$prefix}tagmap` AS tm USING (`itemId`)
				LEFT OUTER JOIN `{$prefix}categories` as c
					ON (c.`categoryId` = its.`categoryId`)
				LEFT OUTER JOIN `{$prefix}context` as cn
					ON (cn.`contextId` = its.`contextId`)
				LEFT OUTER JOIN `{$prefix}timeitems` as ti
					ON (ti.`timeframeId` = its.`timeframeId`)
                {$values['filterquery']}
                GROUP BY i.`itemId`
                ";
			break;

		case "selectitemshort":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, its.`isSomeday`,its.`type`
				FROM `{$prefix}items` as i
				JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
				WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectitemtitle":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`
				    FROM `{$prefix}items` as i
				    WHERE i.`itemId` = '{$values['itemId']}'";
			break;

        case 'selectlastmodified':
            $sql="SELECT UNIX_TIMESTAMP(`lastModified`) AS `lastModified`
                    ,`lastModified` AS `lmreal`
                    FROM `{$prefix}itemstatus`
                    WHERE `itemId` = '{$values['itemId']}'";
            break;
            
		case "selectparents":
			$sql="SELECT lu.`parentId`,i.`title` AS `ptitle`,its.`isSomeday`,its.`type` AS `ptype`
				FROM `{$prefix}lookup` AS lu
				JOIN `{$prefix}items` AS i ON (lu.`parentId` = i.`itemId`)
				JOIN `{$prefix}itemstatus` AS its ON (lu.`parentId` = its.`itemId`)
				WHERE lu.`itemId`='{$values['itemId']}'";
			break;

/*        case 'selectperspective':
            $sql="SELECT `sort`,`columns`,`show` FROM `{$prefix}perspectivemap`
                  JOIN `{$prefix}perspectives` USING (`id`) {$values['filterquery']}";
            break;
*/
		case "selecttimecontext":
			$sql="SELECT `timeframeId`, `timeframe`, `description`, `type`
				FROM `{$prefix}timeitems`
				WHERE `timeframeId` = '{$values['tcId']}'";
			break;

		case "spacecontextselectbox":
			$sql="SELECT `contextId`, `name`, `description`
				FROM `{$prefix}context` as cn
				ORDER BY {$sort['spacecontextselectbox']}";
			break;

		case "testitemrepeat":
			$sql="SELECT i.`recur`,its.`tickledate`,its.`deadline`
				FROM `{$prefix}itemstatus` AS its
                JOIN `{$prefix}items`          AS i   USING (`itemId`)
				WHERE its.`itemId`='{$values['itemId']}'";
			break;

		case "timecontextselectbox":
			$sql="SELECT `timeframeId`, `timeframe`, `description`, `type`
				FROM `{$prefix}timeitems` as ti".$values['timefilterquery']."
				ORDER BY {$sort['timecontextselectbox']}";
			break;

		case "touchitem":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `lastModified` = NULL
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updatecategory":
			$sql="UPDATE `{$prefix}categories`
				SET `category` ='{$values['name']}',
						`description` ='{$values['description']}'
				WHERE `categoryId` ='{$values['id']}'";
			break;

		case "updatechecklist":
			$sql="UPDATE `{$prefix}lookup` AS lu
                JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
				SET its.`dateCompleted` = IF(its.`itemId` IN ('{$values['itemfilterquery']}'),{$values['dateCompleted']},NULL),
                    its.`lastModified` = NULL
				WHERE lu.`parentId` = '{$values['parentId']}'";
			break;

        case "updateconfig":
            $sql="REPLACE INTO `{$prefix}preferences` (`uid`,`option`,`value`)
                    VALUES ('{$values['uid']}','config'   ,'{$values['config']}'   ),
                           ('{$values['uid']}','keys'     ,'{$values['keys']}'     ),
                           ('{$values['uid']}','hierarchy','{$values['hierarchy']}'),
                           ('{$values['uid']}','debug'    ,'{$values['debug']}'    ),
                           ('{$values['uid']}','sort'     ,'{$values['sort']}'     ),
                           ('{$values['uid']}','addons'   ,'{$values['addons']}'   )";
            break;

		case "updatedeadline":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `deadline` ={$values['deadline']},
				    `tickledate` ={$values['tickledate']}
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitem":
			$sql="UPDATE `{$prefix}items`
				SET `description` = '{$values['description']}',
						`title` = '{$values['title']}',
						`desiredOutcome` = '{$values['desiredOutcome']}',
						`recurdesc` = '{$values['recurdesc']}',
						`recur` = '{$values['recur']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemattributes":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `isSomeday`= '{$values['isSomeday']}',
					`contextId` = '{$values['contextId']}',
					`timeframeId` = '{$values['timeframeId']}',
					`deadline` ={$values['deadline']},
					`tickledate` = {$values['tickledate']},
					`nextaction` = '{$values['nextaction']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemcategory":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `categoryId`='{$values['categoryId']}'
				WHERE `itemId`='{$values['itemId']}'";
			break;

		case "updateitemcontext":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `contextId`='{$values['contextId']}'
				WHERE `itemId`='{$values['itemId']}'";
			break;

		case "updateitemstatus":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `type` = '{$values['type']}',
					`categoryId` = '{$values['categoryId']}',
					`lastModified` = NULL
				WHERE `itemId` = '{$values['itemId']}'";
			break;
			
		case "updateitemtext":
			$sql="UPDATE `{$prefix}items`
				SET `description` = '{$values['description']}',
						`title` = '{$values['title']}',
						`desiredOutcome` = '{$values['desiredOutcome']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemtimecontext":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `timeframeId`='{$values['timeframeId']}'
				WHERE `itemId`='{$values['itemId']}'";
			break;

		case "updateitemtype":
			$sql="UPDATE `{$prefix}itemstatus`
				SET `type` = '{$values['type']}',
					`dateCompleted`=NULL,
					`isSomeday`= '{$values['isSomeday']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updatenextaction":
			$sql="UPDATE `{$prefix}itemstatus`
                SET `nextaction`='{$values['nextaction']}', `lastModified`=NULL
                WHERE `itemId`='{$values['itemId']}'";
			break;

        case 'updateoptions':
           $sql="REPLACE INTO `{$prefix}preferences` (`uid`,`option`,`value`)
                VALUES ('{$values['uid']}','{$values['option']}','{$values['config']}' )";
            break;
            
		case "updateparent":
			$sql="INSERT INTO `{$prefix}lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['itemId']}')
				ON DUPLICATE KEY UPDATE `parentId`='{$values['parentId']}'";
			break;

		case "updatespacecontext":
			$sql="UPDATE `{$prefix}context`
				SET `name` ='{$values['name']}',
						`description`='{$values['description']}'
				WHERE `contextId` ='{$values['id']}'";
			break;

		case "updatetimecontext":
			$sql="UPDATE `{$prefix}timeitems`
				SET `timeframe` ='{$values['name']}',
						`description`='{$values['description']}',
						`type`='{$values['type']}'
				WHERE `timeframeId` ='{$values['id']}'";
			break;
			
        default: // default to assuming that the label IS the query
            $sql=$querylabel;
            break;
	}
	return $sql;
}
/*
  ===============================================================
*/
function sqlparts($part,$values) {
  $prefix=$_SESSION['prefix'];
  $values = safeIntoDB($values);

  switch ($part) {
	case "activeitems":
		$sqlpart = " (CURDATE()>=its.`tickledate` OR its.`tickledate` IS NULL) ";
		break;
	case "categoryfilter":
		$sqlpart = " its.`categoryId` = '{$values['categoryId']}' ";
		break;
	case "categoryfilter-parent":
		$sqlpart = " y.`pcategoryId` = '{$values['categoryId']}' ";
		break;
	case "checkchildren":
		$sqlpart = " LEFT JOIN (
            SELECT chp.`parentId` as itemId,
                COUNT(chp.`itemId`) AS numChildren,
                COUNT(IF(chits.`nextaction`='y',1,NULL)) as numNA
            FROM       `{$prefix}lookup`         AS chp
                  JOIN `{$prefix}itemstatus`     AS chits USING (`itemId`)
                WHERE chits.`dateCompleted` IS NULL AND chits.`type` <> 'r'
                GROUP BY (chp.`parentId`)
            ) AS act ON (act.itemId=x.itemId) ";
		break;
	case "completeditems":
		$sqlpart = " its.`dateCompleted` IS NOT NULL ";
		break;
	case "contextfilter":
		$sqlpart = " its.`contextId` = '{$values['contextId']}' ";
		break;
	case "countchildren":
		$sqlpart = " ,act.numNA, act.numChildren";
		break;
	case "due":
		$sqlpart = " (CURDATE()>=its.`deadline` AND its.`deadline` IS NOT NULL) ";
		break;
	case "hasparent":
		$sqlpart = " y.`parentId` = '{$values['parentId']}' ";
		break;
    case "hastags":
        $sqlpart = " i.`itemId` IN (SELECT DISTINCT `itemId`
                        FROM `{$prefix}tagmap` WHERE `tagname` IN (";
        $tags=explode(',',$values['tags']);
        $sep='';
        foreach ($tags as $tag) {
            $sqlpart .= "$sep'".trim($tag)."'";
            $sep=',';
        }
        $sqlpart .= ") GROUP BY `itemId` HAVING COUNT(`itemId`)=".count($tags).") ";
        break;
	case "isNAonly":
        $sqlpart = " its.`nextaction`='y' ";
		break;
	case "issomeday":
        //TODO - I'm fairly sure we don't need to use the IF function here any more - but that needs testing
		$sqlpart = " IF(its.`isSomeday`='y','y','n') = '{$values['isSomeday']}' ";
		break;
    case "iteminlist":
        $sqlpart='i.`itemId` IN (';
        $sep='';
        if (preg_match_all('/[0-9]+/',$values['itemId'],$tst)) {
            foreach ($tst[0] as $id) {
                $sqlpart .= "$sep'$id'";
                $sep=',';
            }
        }
        $sqlpart .=')';
        break;
	case "limit":
		$sqlpart = " LIMIT {$values['maxItemsToSelect']} ";
		break;
    case "liveparents":
        $sqlpart =   " x.`parentId` IS NULL "
                    ." OR ( (CURDATE()>=y.`ptickledate` OR y.`ptickledate` IS NULL)"
                    ."      AND (y.`pdatecompleted` IS NULL) "
                    ."      AND (IF(y.`pisSomeday`='y','y','n')='n') )";
		break;
	case "matchall":
		$sqlpart = " (i.`title` LIKE '%{$values['needle']}%'
                      OR i.`description` LIKE '%{$values['needle']}%'
                      OR i.`desiredOutcome` LIKE '%{$values['needle']}%' )";
		break;
	case "notcategoryfilter":
		$sqlpart = " its.`categoryId` != '{$values['categoryId']}' ";
		break;
	case "notcategoryfilter-parent":
		$sqlpart = " y.`pcategoryId` != '{$values['categoryId']}' ";
		break;
	case "notcontextfilter":
		$sqlpart = " its.`contextId` != '{$values['contextId']}' ";
		break;
	case "nottimeframefilter":
		$sqlpart = " its.`timeframeId` !='{$values['timeframeId']}' ";
		break;
	case "pendingitems":
		$sqlpart = " its.`dateCompleted` IS NULL ";
		break;
/*    case "perspectiveuri":
        $sqlpart = " `filter`='{$values['uri']}'";
        break;
*/
	case "repeating":
		$sqlpart = " i.`recur` !='' ";
		break;
	case "singleitem":
		$sqlpart = " i.`itemId`='{$values['itemId']}' ";
		break;
    case 'singleoption':
        $sqlpart = " `option`='{$values['option']}' ";
        break;
	case "suppresseditems":
		$sqlpart = " (CURDATE()<its.`tickledate`) ";
		break;
	case "timeframefilter":
		$sqlpart = " its.`timeframeId` ='{$values['timeframeId']}' ";
		break;
	case "timetype":
		$sqlpart = " ti.`type` = '{$values['type']}' ";
		break;
	case "typefilter":
		$sqlpart = " its.`type` = '{$values['type']}' ";
		break;
    case 'typeinlist':
        $sqlpart='its.`type` IN (';
        $sep='';
        if (preg_match_all('/[a-zA-Z0-9]/',$values['types'],$tst)) {
            foreach ($tst[0] as $type) {
                $sqlpart .= "$sep'$type'";
                $sep=',';
            }
        }
        $sqlpart .=')';
        break;
    default:
        log_array(array('tag'=>'p',"Failed to find sql component with that name; will use that as query string"=>$part));
        $sqlpart=$part;
        break;
  }

  log_array(array("Sqlparts '$part': Result"=>$sqlpart
            ,"Sanitised values in sqlparts"=>$values));

  return $sqlpart;
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
