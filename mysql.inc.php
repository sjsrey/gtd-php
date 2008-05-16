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
    $reply = mysql_query($query);
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
function safeIntoDB($value,$key=NULL) {
	// don't clean arrays - clean individual strings/values. TOFIX: this looks very inefficient, and gets called A LOT
	if (is_array($value)) {
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
            $sql="SELECT cn.`name` AS cname,cn.`contextId`,COUNT(x.`itemId`) AS count
            FROM `{$_SESSION['prefix']}itemattributes` as x
            JOIN `{$_SESSION['prefix']}itemattributes` as ia USING (`itemId`)
            JOIN `{$_SESSION['prefix']}itemstatus` as its USING (`itemId`)
			LEFT OUTER JOIN `{$_SESSION['prefix']}context` AS cn
				ON (ia.`contextId` = cn.`contextId`)
            JOIN (
                SELECT DISTINCT `itemId` FROM `{$_SESSION['prefix']}lookup` AS lu
                    JOIN (SELECT i.`itemId` AS parentId,
                             ia.`isSomeday` AS pisSomeday,
                             ia.`deadline` AS pdeadline,
				             ia.`tickledate` AS ptickledate,
				             its.`dateCompleted` AS pdateCompleted
    					   FROM `{$_SESSION['prefix']}itemattributes` as ia
    					   JOIN `{$_SESSION['prefix']}items` as i USING (`itemId`)
    					   JOIN `{$_SESSION['prefix']}itemstatus` as its USING (`itemId`)
                        ) AS y USING (`parentId`)
            ) AS lut ON (x.`itemId`=lut.`itemId`)
             {$values['filterquery']}
             GROUP BY ia.`contextId` ORDER BY cn.`name`";
            break;
        case 'countdoneactionsbyweek':
            $sql="SELECT its.`dateCompleted`,
                        truncate(datediff(curdate(),its.`dateCompleted`)/7,0) AS `weeksago`,
                        count(*) AS `numdone`
                  FROM `{$prefix}itemstatus` AS `its`
                  WHERE its.`dateCompleted` IS NOT NULL AND its.`type`='a'
                  GROUP BY `weeksago` ORDER BY `dateCompleted` ASC";
            break;

        case 'countdonebyinterval':
            $sql="SELECT its.`type`,
                         interval(datediff(curdate(),its.`dateCompleted`),7,30,90,365) AS `daysago`,
                         count(*) AS `numdone`
                  FROM `{$prefix}itemstatus` AS `its` USING (`itemId`)
                  WHERE its.`dateCompleted` IS NOT NULL
                  GROUP BY `type`,`daysago`";
            break;
            
		case "countnextactions":
			$sql="SELECT INTERVAL(DATEDIFF(CURDATE(),x.`deadline`),-6,0,1) AS `duecategory`,
			        COUNT(DISTINCT x.`itemId`) AS nactions,
                    COUNT(DISTINCT IF(x.`nextaction`='y',x.`itemId`,NULL)) as nnextactions
                    FROM (
						SELECT
							ia.`itemId`,its.`type`, ia.`deadline`, ia.`nextaction`,
                            ia.`tickledate`, its.`dateCompleted`, lu.`parentId`
						FROM `{$prefix}itemattributes` AS ia
                        JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
						LEFT OUTER JOIN `{$prefix}lookup` AS lu USING (`itemId`)
						{$values['childfilterquery']}
				    ) as x
    				LEFT OUTER JOIN (
						SELECT
							ia.`itemId` AS parentId, ia.`isSomeday` AS pisSomeday,
							ia.`tickledate` AS ptickledate,
							its.`dateCompleted` AS pdateCompleted
						FROM `{$prefix}itemattributes` AS ia
						JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					) as y USING(`parentId`)
				{$values['filterquery']}
                GROUP BY `duecategory`";
			break;
		case "countspacecontexts":
			$sql="SELECT COUNT(*)
				FROM `{$prefix}context`";
			break;
        case 'counttype':
            $sql="SELECT COUNT(*) AS cnt FROM `{$prefix}itemstatus` WHERE `type`='{$values['type']}'";
            break;
		case "deletecategory":
			$sql="DELETE FROM `{$prefix}categories`
				WHERE `categoryId`='{$values['id']}'";
			break;
		case "deleteitem":
			$sql="DELETE FROM `{$prefix}items`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deleteitemattributes":
			$sql="DELETE FROM `{$prefix}itemattributes`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deleteitemstatus":
			$sql="DELETE FROM `{$prefix}itemstatus`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deletelookup":
			$sql="DELETE FROM `{$prefix}lookup`
				WHERE `itemId` ='{$values['itemId']}'";
			break;
		case "deletelookupparents":
			$sql="DELETE FROM `{$prefix}lookup`
				WHERE `parentId` ='{$values['itemId']}'";
			break;
		case "deletespacecontext":
			$sql="DELETE FROM `{$prefix}context`
				WHERE `contextId`='{$values['id']}'";
			break;
		case "deletetimecontext":
			$sql="DELETE FROM `{$prefix}timeitems`
				WHERE `timeframeId`='{$values['id']}'";
			break;

		case "getchildren":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`,
					i.`desiredOutcome`, its.`type`,
					IF(ia.`isSomeday`='y','y','n') AS isSomeday, ia.`deadline`,
                    DATEDIFF(CURDATE(),ia.`deadline`) AS `daysdue`,
                    i.`recurdesc`,i.`recur`,
					ia.`tickledate`,ia.`nextaction`,
					its.`dateCreated`, its.`dateCompleted`,
					its.`lastModified`, its.`categoryId`,
					c.`category`, ia.`contextId`,
					cn.`name` AS cname, ia.`timeframeId`, ti.`timeframe`
				FROM `{$prefix}lookup` AS lu
					JOIN `{$prefix}items` AS i USING (`itemId`)
					JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}itemattributes` AS ia USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}context` AS cn
						ON (ia.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `{$prefix}categories` AS c
						ON (its.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `{$prefix}timeitems` AS ti
						ON (ia.`timeframeId` = ti.`timeframeId`)
				WHERE lu.`parentId`= '{$values['parentId']}' {$values['filterquery']}
				ORDER BY {$sort['getchildren']}";
			break;

		case "getgtdphpversion":
			$sql="SELECT `version` FROM `{$prefix}version`";
			break;

		case "getitems":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`, ia.`deadline`,
                    DATEDIFF(CURDATE(),ia.`deadline`) AS `daysdue`
				FROM `{$prefix}items` AS i
					JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}itemattributes` AS ia USING (`itemId`)
					LEFT OUTER JOIN `{$prefix}context` as cn
						ON (ia.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `{$prefix}categories` as c
						ON (its.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `{$prefix}timeitems` as ti
						ON (ia.`timeframeId` = ti.`timeframeId`) "
                .$values['filterquery'].
				" ORDER BY {$sort['getitems']}";
			break;

		case "getitemsandparent":
			$sql="SELECT
    				x.`itemId`, x.`title`, x.`description`,
    				x.`desiredOutcome`, x.`type`, x.`isSomeday`,
    				x.`deadline`, x.`recurdesc`, x.`recur`,
    				DATEDIFF(CURDATE(),x.`deadline`) AS `daysdue`,
    				x.`tickledate`, x.`dateCreated`, x.`dateCompleted`,
    				x.`lastModified`, x.`categoryId`, x.`category`,
    				x.`contextId`, x.`cname`, x.`timeframeId`,
    				x.`timeframe`,x.`nextaction`,
    				GROUP_CONCAT(DISTINCT y.`parentId` ORDER BY y.`ptitle`) as `parentId`,
    				GROUP_CONCAT(DISTINCT y.`ptitle` ORDER BY y.`ptitle` SEPARATOR '{$_SESSION['config']['separator']}') AS `ptitle`,
                    GROUP_CONCAT(DISTINCT tm.`tagname` ORDER BY `tagname` SEPARATOR ',') AS tags
    				{$values['extravarsfilterquery']}
				FROM (
						SELECT
							i.`itemId`, i.`title`, i.`description`,
							i.`desiredOutcome`, its.`type`, ia.`isSomeday`,
							ia.`deadline`, i.`recurdesc`, i.`recur`,ia.`nextaction`,
							ia.`tickledate`, its.`dateCreated`,
							its.`dateCompleted`, its.`lastModified`,
							its.`categoryId`, c.`category`, ia.`contextId`,
							cn.`name` AS cname, ia.`timeframeId`,
							ti.`timeframe`, lu.`parentId`
						FROM `{$prefix}items` as i
							JOIN `{$prefix}itemstatus` as its
								ON (i.`itemId` = its.`itemId`)
							LEFT OUTER JOIN `{$prefix}lookup` as lu
								ON (i.`itemId` = lu.`itemId`)
							LEFT OUTER JOIN `{$prefix}itemattributes` AS ia
								ON (ia.`itemId` = i.`itemId`)
							LEFT OUTER JOIN `{$prefix}context` as cn
								ON (ia.`contextId` = cn.`contextId`)
							LEFT OUTER JOIN `{$prefix}categories` as c
								ON (its.`categoryId` = c.`categoryId`)
							LEFT OUTER JOIN `{$prefix}timeitems` as ti
								ON (ia.`timeframeId` = ti.`timeframeId`)
                            {$values['childfilterquery']}
				) as x
				LEFT OUTER JOIN `{$prefix}tagmap` as tm
					ON (x.`itemId` = tm.`itemId`)
				LEFT OUTER JOIN (
						SELECT
							i.`itemId` AS parentId, i.`title` AS ptitle,
							i.`description` AS pdescription,
							i.`desiredOutcome` AS pdesiredOutcome,
							its.`type` AS ptype, ia.`isSomeday` AS pisSomeday,
							ia.`deadline` AS pdeadline, i.`recurdesc` AS precurdesc,
							ia.`tickledate` AS ptickledate,
							its.`dateCompleted` AS pdateCompleted
						FROM `{$prefix}items` as i
							JOIN `{$prefix}itemstatus` as its
								ON (i.`itemId` = its.`itemId`)
							LEFT OUTER JOIN `{$prefix}itemattributes` AS ia
								ON (ia.`itemId` = i.`itemId`)
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
			$sql="SELECT i.`itemId`, i.`title`, i.`description`, its.`type`, ia.`isSomeday`
				FROM `{$prefix}items` AS i   
				JOIN `{$prefix}itemstatus` AS its USING (itemId)
				LEFT OUTER JOIN `{$prefix}itemattributes` AS ia USING (itemId)
				WHERE (its.`dateCompleted` IS NULL)
					AND (its.`type` NOT IN ({$values['orphansfilterquery']})
					       AND (its.`itemId` NOT IN
						(SELECT lu.`itemId` FROM `{$prefix}lookup` as lu)
                           ) OR its.`type` IS NULL OR its.`type`='')
				ORDER BY {$sort['getorphaneditems']}";
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

		case "newitemattributes":
			$sql="INSERT INTO `{$prefix}itemattributes`
						(`itemId`,`isSomeday`,`contextId`,
						`timeframeId`,`deadline`,`tickledate`,`nextaction`)
				VALUES ('{$values['newitemId']}','{$values['isSomeday']}',
						'{$values['contextId']}','{$values['timeframeId']}',
						{$values['deadline']},{$values['tickledate']},'{$values['nextaction']}')";
			break;

		case "newitemstatus":
			$sql="INSERT INTO `{$prefix}itemstatus`
						(`itemId`,`dateCreated`,`lastModified`,`dateCompleted`,
                        `type`,`categoryId`)
				VALUES ('{$values['newitemId']}',CURRENT_DATE,NULL,{$values['dateCompleted']},
                        '{$values['type']}','{$values['categoryId']}')";
			break;

		case "newparent":
			$sql="INSERT INTO `{$prefix}lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')";
			break;

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
						i.`description`, ia.`isSomeday`,its.`type`
				FROM `{$prefix}items` as i
				JOIN `{$prefix}itemstatus` as its USING (`itemId`)
				LEFT OUTER JOIN `{$prefix}itemattributes` as ia USING (`itemId`)
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
			$sql="UPDATE `{$prefix}itemattributes`
				SET `contextId`='{$values['newId']}'
				WHERE `contextId`='{$values['id']}'";
			break;

		case "reassigntimecontext":
			$sql="UPDATE `{$prefix}itemattributes`
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
			$sql="SELECT i.*,ia.*,its.*,
				    c.`category`, ti.`timeframe`,cn.`name` AS `cname`,
                    GROUP_CONCAT(tm.`tagname` ORDER BY tm.`tagname` SEPARATOR ',') AS tagname
				FROM `{$prefix}items`      AS i
				JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
				LEFT OUTER JOIN `{$prefix}itemattributes` AS ia  USING (`itemId`)
                LEFT OUTER JOIN `{$prefix}tagmap` AS tm USING (`itemId`)
				LEFT OUTER JOIN `{$prefix}categories` as c
					ON (c.`categoryId` = its.`categoryId`)
				LEFT OUTER JOIN `{$prefix}context` as cn
					ON (cn.`contextId` = ia.`contextId`)
				LEFT OUTER JOIN `{$prefix}timeitems` as ti
					ON (ti.`timeframeId` = ia.`timeframeId`)
                WHERE i.`itemId` = '{$values['itemId']}'
                GROUP BY i.`itemId` ";
			break;

		case "selectitemshort":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, ia.`isSomeday`,its.`type`
				FROM `{$prefix}items` as i
				JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
				LEFT OUTER JOIN `{$prefix}itemattributes` AS ia USING (`itemId`)
				WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectitemtitle":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`
				    FROM `{$prefix}items` as i
				    WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectparents":
			$sql="SELECT lu.`parentId`,i.`title` AS `ptitle`,ia.`isSomeday`,its.`type` AS `ptype`
				FROM `{$prefix}lookup` AS lu
				JOIN `{$prefix}items` AS i ON (lu.`parentId` = i.`itemId`)
				JOIN `{$prefix}itemstatus` AS its ON (lu.`parentId` = its.`itemId`)
				LEFT OUTER JOIN `{$prefix}itemattributes` AS ia ON (lu.`parentId` = ia.`itemId`)
				WHERE lu.`itemId`='{$values['itemId']}'";
			break;

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
			$sql="SELECT i.`recur`,ia.`tickledate`,ia.`deadline`
				FROM `{$prefix}itemattributes` AS ia
                JOIN `{$prefix}items`          AS i   USING (`itemId`)
				WHERE ia.`itemId`='{$values['itemId']}'";
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
                    VALUES ('0','config'   ,'{$values['config']}'   ),
                           ('0','keys'     ,'{$values['keys']}'     ),
                           ('0','hierarchy','{$values['hierarchy']}'),
                           ('0','debug'    ,'{$values['debug']}'    ),
                           ('0','sort'     ,'{$values['sort']}'     ),
                           ('0','installedaddons','{$values['installedaddons']}' )";
            break;

		case "updatedeadline":
			$sql="UPDATE `{$prefix}itemattributes`
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
			$sql="UPDATE `{$prefix}itemattributes`
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
			$sql="UPDATE `{$prefix}itemattributes`
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
			$sql="UPDATE `{$prefix}itemattributes`
				SET `timeframeId`='{$values['timeframeId']}'
				WHERE `itemId`='{$values['itemId']}'";
			break;

		case "updateitemtype":
			$sql="UPDATE `{$prefix}itemstatus` AS its
                    JOIN `{$prefix}itemattributes` AS ia USING (`itemId`)
				SET its.`type` = '{$values['type']}',
					its.`dateCompleted`=NULL,
					ia.`isSomeday`= '{$values['isSomeday']}'
				WHERE its.`itemId` = '{$values['itemId']}'";
			break;

		case "updatenextaction":
			$sql="UPDATE `{$prefix}itemattributes` AS ia
                JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
                SET ia.`nextaction`='{$values['nextaction']}', its.`lastModified`=NULL
                WHERE ia.`itemId`='{$values['itemId']}'";
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
		$sqlpart = " (CURDATE()>=ia.`tickledate` OR ia.`tickledate` IS NULL) ";
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
                COUNT(IF(chia.`nextaction`='y',1,NULL)) as numNA
            FROM       `{$prefix}lookup`         AS chp
                  JOIN `{$prefix}itemstatus`     AS chits USING (`itemId`)
       LEFT OUTER JOIN `{$prefix}itemattributes` AS chia  USING (`itemId`)
                WHERE chits.`dateCompleted` IS NULL AND chits.`type` <> 'r'
                GROUP BY (chp.`parentId`)
            ) AS act ON (act.itemId=x.itemId) ";
		break;
	case "completeditems":
		$sqlpart = " its.`dateCompleted` IS NOT NULL ";
		break;
	case "contextfilter":
		$sqlpart = " ia.`contextId` = '{$values['contextId']}' ";
		break;
	case "countchildren":
		$sqlpart = " ,act.numNA, act.numChildren";
		break;
	case "due":
		$sqlpart = " (CURDATE()>=ia.`deadline` AND ia.`deadline` IS NOT NULL) ";
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
            $sqlpart .= "'".trim($tag)."'";
            $sep=',';
        }
        $sqlpart .= ") GROUP BY `itemId` HAVING COUNT(`itemId`)=".count($tags).") ";
        break;
	case "isNAonly":
        $sqlpart = " ia.`nextaction`='y' ";
		break;
	case "issomeday":
		$sqlpart = " IF(ia.`isSomeday`='y','y','n') = '{$values['isSomeday']}' ";
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
		$sqlpart = " ia.`contextId` != '{$values['contextId']}' ";
		break;
	case "nottimeframefilter":
		$sqlpart = " ia.`timeframeId` !='{$values['timeframeId']}' ";
		break;
    case 'orphantypes':
        $sqlpart='';
        $sep='';
        if (preg_match_all('/[a-zA-Z0-9]/',$values['suppressAsOrphans'],$tst)) {
            foreach ($tst[0] as $type) {
                $sqlpart .= "$sep'$type'";
                $sep=',';
            }
        }
        break;
	case "pendingitems":
		$sqlpart = " its.`dateCompleted` IS NULL ";
		break;
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
		$sqlpart = " (CURDATE()<ia.`tickledate`) ";
		break;
	case "timeframefilter":
		$sqlpart = " ia.`timeframeId` ='{$values['timeframeId']}' ";
		break;
	case "timetype":
		$sqlpart = " ti.`type` = '{$values['type']}' ";
		break;
	case "typefilter":
		$sqlpart = " its.`type` IN ('{$values['type']}') ";
		break;
    default:
        if ($_SESSION['debug']['debug']) echo "<p class='error'>Failed to find sql component '$part'</p>'";
        $sqlpart=$part;
        break;
  }

  if ($_SESSION['debug']['debug'])
      echo "<pre>Sqlparts '$part': Result $sqlpart<br />Sanitised values in sqlparts: ",print_r($values,true),'</pre>';

  return $sqlpart;
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
