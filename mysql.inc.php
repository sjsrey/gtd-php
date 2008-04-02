<?php
/*
    MySQL extensions to standard SQL have been avoided where known & where practical
  ===============================================================

*/
function connectdb($config) {

    $connection = mysql_connect($config['host'], $config['user'], $config['pass'])
        or die ("Unable to connect to MySQL server: check your host, user and pass settings in config.php!");
        
    mysql_select_db($config['db'])
        or die ("Unable to select database '{$config['db']}' - check your db setting in config.php!");
        
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
function safeIntoDB(&$value,$key=NULL) {
	// don't clean arrays - clean individual strings/values
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
*/
//GENERAL RULES:
//"select" = query for something by its id; a single-row result
//"get" = query for something of a particular type; a multi-row result
//"new", "update", "delete" are self-explanatory
//"check"="complete" for checklistselectbox
//"complete" = set status to completed
//"remove" = remove by association Id (items associated with a project, etc)
//"Count" = # of a particular type in table
//"selectbox" = get results to create a selectbox- for assignment or filter
function getsql($config,$values,$sort,$querylabel) {

    if (is_array($values))
        foreach ($values as $key=>$value)
            $values[$key] = safeIntoDB($value, $key);

	switch ($querylabel) {
		case "categoryselectbox":
			$sql="SELECT c.`categoryId`, c.`category`, c.`description`
				FROM `". $config['prefix'] ."categories` as c
				ORDER BY {$sort['categoryselectbox']}";
			break;

		case "checkchecklistitem":
			$sql="UPDATE `". $config['prefix'] ."checklistitems`
				SET `checked` = 'y'
				WHERE `checklistItemId` IN ({$values['itemfilterquery']})";
			break;

		case "checklistselectbox":
			$sql="SELECT cl.`checklistId`, cl.`title`,
						cl.`description`, cl.`categoryId`, c.`category`
				FROM `". $config['prefix'] ."checklist` as cl
				LEFT OUTER JOIN `". $config['prefix'] ."categories` as c USING (`categoryId`)
				ORDER BY {$sort['checklistselectbox']}";
			break;

		case "clearchecklist":
			$sql="UPDATE `". $config['prefix'] ."checklistitems`
				SET `checked` = 'n'
				WHERE `checklistId` = '{$values['id']}'";
			break;

		case "completeitem":
			$sql="UPDATE `". $config['prefix'] ."itemstatus`
				SET `dateCompleted`=" . $values['dateCompleted'].
				", `lastModified` = NULL
				WHERE `itemId`=" . $values['itemId'];
			break;

		case "completelistitem":
			$sql="UPDATE `". $config['prefix'] ."listitems`
				SET `dateCompleted`={$values['dateCompleted']}
				WHERE `listItemId` IN ({$values['itemfilterquery']})";
			break;

		case "copynextaction":
			$sql="INSERT INTO `". $config['prefix'] ."nextactions` (`parentId`,`nextaction`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')
				ON DUPLICATE KEY UPDATE `nextaction`='{$values['newitemId']}'";
			break;

		case "countactions":
			$sql="SELECT COUNT(DISTINCT i.`itemId`) AS nactions
                    FROM `{$config['prefix']}items` as i
					JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
                    JOIN `{$config['prefix']}itemattributes` as ia USING (`itemId`)
                    LEFT OUTER JOIN `{$config['prefix']}lookup` AS lu USING (`itemId`)
                    LEFT OUTER JOIN ( SELECT
    						i.`itemId` AS parentId, ia.`isSomeday` AS pisSomeday,
    						ia.`deadline` AS pdeadline, ia.`suppress` AS psuppress,
    						ia.`suppressUntil` AS psuppressUntil,
    						its.`dateCompleted` AS pdateCompleted
					   FROM `{$config['prefix']}itemattributes` AS ia
							JOIN `{$config['prefix']}items` AS i USING (`itemId`)
							JOIN `{$config['prefix']}itemstatus` AS its  USING (`itemId`)
					) as y ON (y.`parentId` = lu.`parentId`) {$values['filterquery']}";
			break;

        case 'countactionsbycontext':
            $sql="SELECT cn.`name` AS cname,cn.`contextId`,COUNT(x.`itemId`) AS count
                    FROM `{$config['prefix']}itemattributes` as x
                    JOIN `{$config['prefix']}itemattributes` as ia USING (`itemId`)
                    JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
					LEFT OUTER JOIN `{$config['prefix']}context` AS cn
						ON (ia.`contextId` = cn.`contextId`)
                    JOIN (
                        SELECT DISTINCT nextAction FROM `{$config['prefix']}nextactions` AS na
                            JOIN (SELECT i.`itemId` AS parentId,
                                     ia.`isSomeday` AS pisSomeday,
                                     ia.`deadline` AS pdeadline,
						             ia.`suppress` AS psuppress,
						             ia.`suppressUntil` AS psuppressUntil,
						             its.`dateCompleted` AS pdateCompleted
            					   FROM `{$config['prefix']}itemattributes` as ia
            					   JOIN `{$config['prefix']}items` as i USING (`itemId`)
            					   JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
                                ) AS y USING (`parentId`) 
                    ) AS nat ON (x.`itemId`=nat.`nextAction`)
                     {$values['filterquery']}
                     GROUP BY ia.`contextId` ORDER BY cn.`name`";
            break;
            
		case "countnextactions":
			$sql="SELECT INTERVAL(DATEDIFF(CURDATE(),ia.`deadline`),-6,0,1) AS `duecategory`,
			           COUNT(DISTINCT i.`itemId`) AS nnextactions
                    FROM `{$config['prefix']}items` as i
					JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
                    JOIN `{$config['prefix']}itemattributes` as ia USING (`itemId`)
                    JOIN (
                        SELECT DISTINCT nextAction FROM `{$config['prefix']}nextactions` AS na
                            LEFT OUTER JOIN (SELECT i.`itemId` AS parentId,
                                     ia.`isSomeday` AS pisSomeday,
                                     ia.`deadline` AS pdeadline,
						             ia.`suppress` AS psuppress,
						             ia.`suppressUntil` AS psuppressUntil,
						             its.`dateCompleted` AS pdateCompleted
            					   FROM `{$config['prefix']}itemattributes` as ia
            					   JOIN `{$config['prefix']}items` as i USING (`itemId`)
            					   JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
                                ) AS y USING (`parentId`)
                    ) AS nat ON (i.`itemId`=nat.`nextAction`)
                    {$values['filterquery']}
					GROUP BY `duecategory`";
			break;
		case "countselected":
			$sql="SELECT FOUND_ROWS()";
			break;
		case "countspacecontexts":
			$sql="SELECT COUNT(*)
				FROM `". $config['prefix'] ."context`";
			break;
		case "deletecategory":
			$sql="DELETE FROM `". $config['prefix'] ."categories`
				WHERE `categoryId`='{$values['id']}'";
			break;
		case "deletechecklist":
			$sql="DELETE FROM `". $config['prefix'] ."checklist`
				WHERE `checklistId`='{$values['id']}'";
			break;
		case "deletechecklistitem":
			$sql="DELETE FROM `". $config['prefix'] ."checklistitems`
				WHERE `checklistItemId`='{$values['itemId']}'";
			break;
		case "deleteitem":
			$sql="DELETE FROM `". $config['prefix'] ."items`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deleteitemattributes":
			$sql="DELETE FROM `". $config['prefix'] ."itemattributes`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deleteitemstatus":
			$sql="DELETE FROM `". $config['prefix'] ."itemstatus`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deletelist":
			$sql="DELETE FROM `". $config['prefix'] ."list`
				WHERE `listId`='{$values['id']}'";
			break;
		case "deletelistitem":
			$sql="DELETE FROM `". $config['prefix'] ."listitems`
				WHERE `listItemId`='{$values['itemId']}'";
			break;
		case "deletelookup":
			$sql="DELETE FROM `". $config['prefix'] ."lookup`
				WHERE `itemId` ='{$values['itemId']}'";
			break;
		case "deletelookupparents":
			$sql="DELETE FROM `". $config['prefix'] ."lookup`
				WHERE `parentId` ='{$values['itemId']}'";
			break;
		case "deletenextaction":
			$sql="DELETE FROM `". $config['prefix'] ."nextactions`
				WHERE `nextAction`='{$values['itemId']}'";
			break;
		case "deletenextactionparents":
			$sql="DELETE FROM `". $config['prefix'] ."nextactions`
				WHERE `parentId` ='{$values['itemId']}'";
			break;
		case "deletenote":
			$sql="DELETE FROM `". $config['prefix'] ."tickler`
				WHERE `ticklerId`='{$values['noteId']}'";
			break;
		case "deletespacecontext":
			$sql="DELETE FROM `". $config['prefix'] ."context`
				WHERE `contextId`='{$values['id']}'";
			break;
		case "deletetimecontext":
			$sql="DELETE FROM `". $config['prefix'] ."timeitems`
				WHERE `timeframeId`='{$values['id']}'";
			break;


		case "getchecklistitems":
			$sql="SELECT `checklistitemId` AS `itemId`, `item`, `notes`,
						`checklistId` AS `id`, `checked`
				FROM `{$config['prefix']}checklistitems` AS cli
				WHERE `checklistId` = '{$values['id']}'
				ORDER BY {$sort['getchecklistitems']}";
			break;

		case "getchecklists":
			$sql="SELECT l.`checklistId` as id, l.`title`,
						l.`description`, l.`categoryId`, c.`category`
				FROM `". $config['prefix'] ."checklist` as l
				LEFT OUTER JOIN `{$config['prefix']}categories` as c USING (`categoryId`) "
				.$values['filterquery']." ORDER BY {$sort['getchecklists']}";
			break;
			
		case "getchildren":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`,
					i.`desiredOutcome`, ia.`type`,
					ia.`isSomeday`, ia.`deadline`, ia.`repeat`,
					ia.`suppress`, ia.`suppressUntil`,
					its.`dateCreated`, its.`dateCompleted`,
					its.`lastModified`, ia.`categoryId`,
					c.`category`, ia.`contextId`,
					cn.`name` AS cname, ia.`timeframeId`, ti.`timeframe`
					, na.nextaction as NA
				FROM `". $config['prefix'] . "itemattributes` as ia
					JOIN `{$config['prefix']}lookup` AS lu USING (`itemId`)
					JOIN `". $config['prefix'] . "items` AS i USING (`itemId`)
					JOIN `". $config['prefix'] . "itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `". $config['prefix'] . "context` AS cn
						ON (ia.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `". $config['prefix'] ."categories` AS c
						ON (ia.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `". $config['prefix'] . "timeitems` AS ti
						ON (ia.`timeframeId` = ti.`timeframeId`)
				LEFT JOIN (
						SELECT DISTINCT nextaction FROM {$config['prefix']}nextactions
					) AS na ON(na.nextaction=i.itemId)
				WHERE lu.`parentId`= '{$values['parentId']}' {$values['filterquery']}
				ORDER BY {$sort['getchildren']}";
			break;

		case "getgtdphpversion":
			$sql="SELECT `version` FROM `{$config['prefix']}version`";
			break;

		case "getitems":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`, ia.`deadline`
				FROM `". $config['prefix'] . "itemattributes` as ia
					JOIN `". $config['prefix'] . "items` as i
						ON (ia.`itemId` = i.`itemId`)
					JOIN `". $config['prefix'] . "itemstatus` as its
						ON (ia.`itemId` = its.`itemId`)
					LEFT OUTER JOIN `". $config['prefix'] . "context` as cn
						ON (ia.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `". $config['prefix'] ."categories` as c
						ON (ia.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `". $config['prefix'] . "timeitems` as ti
						ON (ia.`timeframeId` = ti.`timeframeId`) ".$values['filterquery']."
				ORDER BY {$sort['getitems']}";
			break;

		case "getitemsandparent":
			$sql="SELECT
    				x.`itemId`, x.`title`, x.`description`,
    				x.`desiredOutcome`, x.`type`, x.`isSomeday`,
    				x.`deadline`, x.`repeat`, x.`suppress`,
    				x.`suppressUntil`, x.`dateCreated`, x.`dateCompleted`,
    				x.`lastModified`, x.`categoryId`, x.`category`,
    				x.`contextId`, x.`cname`, x.`timeframeId`,
    				x.`timeframe`,
    				GROUP_CONCAT(DISTINCT y.`parentId` ORDER BY y.`ptitle`) as `parentId`,
    				GROUP_CONCAT(DISTINCT y.`ptitle` ORDER BY y.`ptitle` SEPARATOR '{$config['separator']}') AS `ptitle`
    				{$values['extravarsfilterquery']}
				FROM (
						SELECT
							i.`itemId`, i.`title`, i.`description`,
							i.`desiredOutcome`, ia.`type`, ia.`isSomeday`,
							ia.`deadline`, ia.`repeat`, ia.`suppress`,
							ia.`suppressUntil`, its.`dateCreated`,
							its.`dateCompleted`, its.`lastModified`,
							ia.`categoryId`, c.`category`, ia.`contextId`,
							cn.`name` AS cname, ia.`timeframeId`,
							ti.`timeframe`, lu.`parentId`
						FROM
								`". $config['prefix'] . "itemattributes` as ia
							JOIN `". $config['prefix'] . "items` as i
								ON (ia.`itemId` = i.`itemId`)
							JOIN `". $config['prefix'] . "itemstatus` as its
								ON (ia.`itemId` = its.`itemId`)
							LEFT OUTER JOIN `". $config['prefix'] . "context` as cn
								ON (ia.`contextId` = cn.`contextId`)
							LEFT OUTER JOIN `". $config['prefix'] ."categories` as c
								ON (ia.`categoryId` = c.`categoryId`)
							LEFT OUTER JOIN `". $config['prefix'] . "timeitems` as ti
								ON (ia.`timeframeId` = ti.`timeframeId`)
							LEFT OUTER JOIN `". $config['prefix'] . "lookup` as lu
								ON (ia.`itemId` = lu.`itemId`)".$values['childfilterquery']."
				) as x
					LEFT OUTER JOIN
					(
						SELECT
							i.`itemId` AS parentId, i.`title` AS ptitle,
							i.`description` AS pdescription,
							i.`desiredOutcome` AS pdesiredOutcome,
							ia.`type` AS ptype, ia.`isSomeday` AS pisSomeday,
							ia.`deadline` AS pdeadline, ia.`repeat` AS prepeat,
							ia.`suppress` AS psuppress,
							ia.`suppressUntil` AS psuppressUntil,
							its.`dateCompleted` AS pdateCompleted
						FROM
								`". $config['prefix'] . "itemattributes` as ia
							JOIN `". $config['prefix'] . "items` as i
								ON (ia.`itemId` = i.`itemId`)
							JOIN `". $config['prefix'] . "itemstatus` as its
								ON (ia.`itemId` = its.`itemId`)
					) as y ON (y.parentId = x.parentId)
				{$values['filterquery']} GROUP BY x.`itemId`
				ORDER BY {$sort['getitemsandparent']}";
			break;


		case "getitembrief":
			$sql="SELECT `title`, `description`, `desiredOutcome`
				FROM  `". $config['prefix'] . "items`
				WHERE `itemId` = {$values['itemId']}";
			break;

		case "getlistitems":
			$sql="SELECT li.`listItemId` as itemId, li.`item`, li.`notes`,
                         li.`listId` as id, li.`dateCompleted`
				FROM `". $config['prefix'] . "listitems` as li
					LEFT JOIN `". $config['prefix'] . "list` as l
						on li.`listId` = l.`listId`
				WHERE l.`listId` = '{$values['id']}' ".$values['filterquery']."
				ORDER BY {$sort['getlistitems']}";
			break;

		case "getlists":
			$sql="SELECT l.`listId` as id, l.`title`, l.`description`, l.`categoryId`, c.`category`
				FROM `". $config['prefix'] . "list` as l
				LEFT OUTER JOIN `{$config['prefix']}categories` as c USING (`categoryId`) "
				.$values['filterquery']." ORDER BY {$sort['getlists']}";
			break;

		case "getnotes":
			$sql="SELECT `ticklerId`, `title`, `note`, `date`
				FROM `". $config['prefix'] . "tickler`  as tk".$values['filterquery']."
				ORDER BY {$sort['getnotes']}";
			break;

		case "getorphaneditems":
			$sql="SELECT ia.`itemId`, ia.`type`, i.`title`, i.`description`, ia.`isSomeday`
				FROM `{$config['prefix']}itemattributes` AS ia
				JOIN `{$config['prefix']}items`		  AS i   USING (itemId)
				JOIN `{$config['prefix']}itemstatus`	 AS its USING (itemId)
				WHERE (its.`dateCompleted` IS NULL)
					AND (ia.`type` NOT IN ({$values['notOrphansfilterquery']})
					       AND (ia.`itemId` NOT IN
						(SELECT lu.`itemId` FROM `". $config['prefix'] . "lookup` as lu)
                           ) OR ia.`type` IS NULL OR ia.`type`='')
				ORDER BY {$sort['getorphaneditems']}";
			break;

		case "getspacecontexts":
			$sql="SELECT `contextId`, `name`
				FROM `". $config['prefix'] . "context` ORDER BY `name` ASC";
			break;

		case "gettimecontexts":
			$sql="SELECT `timeframeId`, `timeframe`, `description`
				FROM `". $config['prefix'] . "timeitems` AS ti
				{$values['timefilterquery']} ORDER BY `timeframeId` ASC";
			break;


		case "lookupparent":
			$sql="SELECT lu.`parentId`,i.`title` AS `ptitle`,ia.`isSomeday`,ia.`type` AS `ptype`
				FROM `". $config['prefix'] . "lookup` AS lu
				JOIN `{$config['prefix']}items` AS i ON (lu.`parentId` = i.`itemId`)
				JOIN `{$config['prefix']}itemattributes` AS ia ON (lu.`parentId` = ia.`itemId`)
				WHERE lu.`itemId`='{$values['itemId']}'";
			break;

		case "newcategory":
			$sql="INSERT INTO `". $config['prefix'] ."categories`
				VALUES (NULL, '{$values['name']}', '{$values['description']}')";
			break;

		case "newchecklist":
			$sql="INSERT INTO `". $config['prefix'] ."checklist`
				VALUES (NULL, '{$values['title']}',
						'{$values['categoryId']}', '{$values['description']}')";
			break;

		case "newchecklistitem":
			$sql="INSERT INTO `". $config['prefix'] . "checklistitems`
				VALUES (NULL, '{$values['item']}','{$values['notes']}',
                        '{$values['id']}', 'n')";
			break;

		case "newitem":
			$sql="INSERT INTO `". $config['prefix'] . "items`
						(`title`,`description`,`desiredOutcome`)
				VALUES ('{$values['title']}',
						'{$values['description']}','{$values['desiredOutcome']}')";
			break;

		case "newitemattributes":
			$sql="INSERT INTO `". $config['prefix'] . "itemattributes`
						(`itemId`,`type`,`isSomeday`,`categoryId`,`contextId`,
						`timeframeId`,`deadline`,`repeat`,`suppress`,`suppressUntil`)
				VALUES ('{$values['newitemId']}','{$values['type']}','{$values['isSomeday']}',
						'{$values['categoryId']}','{$values['contextId']}','{$values['timeframeId']}',
						{$values['deadline']},'{$values['repeat']}','{$values['suppress']}',
						'{$values['suppressUntil']}')";
			break;

		case "newitemstatus":
			$sql="INSERT INTO `". $config['prefix'] . "itemstatus`
						(`itemId`,`dateCreated`,`lastModified`,`dateCompleted`)
				VALUES ('{$values['newitemId']}',
						CURRENT_DATE,NULL,{$values['dateCompleted']})";
			break;

		case "newlist":
			$sql="INSERT INTO `". $config['prefix'] . "list`
				VALUES (NULL, '{$values['title']}',
						'{$values['categoryId']}', '{$values['description']}')";
			break;

		case "newlistitem":
			$sql="INSERT INTO `". $config['prefix'] . "listitems`
				VALUES (NULL, '{$values['item']}',
						'{$values['notes']}', '{$values['id']}', NULL)";
			break;

		case "newnextaction":
			$sql="INSERT INTO `". $config['prefix'] . "nextactions`
						(`parentId`,`nextaction`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')
				ON DUPLICATE KEY UPDATE `nextaction`='{$values['newitemId']}'";
			break;

		case "newnote":
			$sql="INSERT INTO `". $config['prefix'] . "tickler`
						(`date`,`title`,`note`,`repeat`,`suppressUntil`)
				VALUES ('{$values['date']}','{$values['title']}',
						'{$values['note']}','{$values['repeat']}',
						'{$values['suppressUntil']}')";
			break;

		case "newparent":
			$sql="INSERT INTO `". $config['prefix'] . "lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')";
			break;

		case "newspacecontext":
			$sql="INSERT INTO `". $config['prefix'] . "context`
						(`name`,`description`)
				VALUES ('{$values['name']}', '{$values['description']}')";
			break;

		case "newtimecontext":
			$sql="INSERT INTO `". $config['prefix'] . "timeitems`
						(`timeframe`,`description`,`type`)
				VALUES ('{$values['name']}', '{$values['description']}', '{$values['type']}')";
			break;

		case "parentselectbox":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, ia.`isSomeday`,ia.`type`
				FROM `". $config['prefix'] . "items` as i
				JOIN `{$config['prefix']}itemattributes` as ia USING (`itemId`)
				JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
				WHERE (its.`dateCompleted` IS NULL) {$values['ptypefilterquery']}
				ORDER BY ia.`type`,i.`title`";
				#ORDER BY {$sort['parentselectbox']}";
			break;


		case "reassigncategory":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `categoryId`='{$values['newId']}'
				WHERE `categoryId`='{$values['id']}'";
			break;

		case "reassignspacecontext":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `contextId`='{$values['newId']}'
				WHERE `contextId`='{$values['id']}'";
			break;

		case "reassigntimecontext":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `timeframeId`='{$values['newId']}'
				WHERE `timeframeId`='{$values['id']}'";
			break;


		case "removechecklistitems":
			$sql="DELETE
				FROM `". $config['prefix'] . "checklistitems`
				WHERE `checklistId`='{$values['id']}'";
			break;

		case "removelistitems":
			$sql="DELETE
				FROM `". $config['prefix'] . "listitems`
				WHERE `listId`='{$values['id']}'";
			break;

		case "repeatnote":
			$sql="UPDATE `". $config['prefix'] . "tickler`
				SET `date` = DATE_ADD(`date`, INTERVAL ".$values['repeat']." DAY),
					`note` = '{$values['note']}', `title` = '{$values['title']}',
					`repeat` = '{$values['repeat']}',
					`suppressUntil` = '{$values['suppressUntil']}'
				WHERE `ticklerId` = '{$values['noteId']}'";
			break;

		case "selectcategory":
			$sql="SELECT `categoryId`, `category`, `description`
				FROM `". $config['prefix'] ."categories`
				WHERE `categoryId` = '{$values['categoryId']}'";
			break;

		case "selectchecklist":
			$sql="SELECT cl.`checklistId` as id, cl.`title`,
						cl.`description`, cl.`categoryId`, c.`category`
				FROM `". $config['prefix'] ."checklist` as cl
				LEFT OUTER JOIN `{$config['prefix']}categories` AS c USING (`categoryId`)
				WHERE cl.`checklistId`='{$values['id']}'
				ORDER BY {$sort['selectchecklist']}";
			break;

		case "selectchecklistitem":
			$sql="SELECT `checklistItemId` as itemId,
						`item`,
						`notes`,
						`checklistId` as id,
						`checked`
				FROM `". $config['prefix'] . "checklistitems`
				WHERE `checklistItemId` = '{$values['itemId']}'";
			break;

		case "selectcontext":
			$sql="SELECT `contextId`, `name`, `description`
				FROM `". $config['prefix'] . "context`
				WHERE `contextId` = '{$values['contextId']}'";
			break;

		case "selectitem":
			$sql="SELECT i.`itemId`, ia.`type`, i.`title`,
					i.`description`, i.`desiredOutcome`,
					ia.`categoryId`, ia.`contextId`,
					ia.`timeframeId`, ia.`isSomeday`,
					ia.`deadline`, ia.`repeat`,
					ia.`suppress`, ia.`suppressUntil`,
					its.`dateCreated`, its.`dateCompleted`,
					its.`lastModified`, c.`category`, ti.`timeframe`,
					cn.`name` AS `cname`
				FROM `{$config['prefix']}items`          AS i
				JOIN `{$config['prefix']}itemattributes` AS ia  USING (`itemId`)
				JOIN `{$config['prefix']}itemstatus`     AS its USING (`itemId`)
					LEFT OUTER JOIN `". $config['prefix'] ."categories` as c
						ON (c.`categoryId` = ia.`categoryId`)
					LEFT OUTER JOIN `". $config['prefix'] . "context` as cn
						ON (cn.`contextId` = ia.`contextId`)
					LEFT OUTER JOIN `". $config['prefix'] . "timeitems` as ti
						ON (ti.`timeframeId` = ia.`timeframeId`)
				WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectitemshort":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, ia.`isSomeday`,ia.`type`
				FROM `". $config['prefix'] . "items` as i
				JOIN `{$config['prefix']}itemattributes` AS ia USING (`itemId`)
				JOIN `{$config['prefix']}itemstatus` AS its USING (`itemId`)
				WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectitemtitle":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`
				    FROM `". $config['prefix'] . "items` as i
				    WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectlist":
			$sql="SELECT `listId` as id, l.`title`, l.`description`, l.`categoryId`,c.`category`
				FROM `". $config['prefix'] . "list` AS l
                LEFT OUTER JOIN `{$config['prefix']}categories` AS c USING (`categoryId`)
				WHERE `listId` = '{$values['id']}'";
			break;

		case "selectlistitem":
			$sql="SELECT `listItemId` as itemId, `item`,
						`notes`, `listId` as id, `dateCompleted`
				FROM `". $config['prefix'] . "listitems`
				WHERE `listItemId` = {$values['itemId']}";
			break;

		case "selectnote":
			$sql="SELECT `ticklerId`, `title`, `note`,
						`date`, `repeat`, `suppressUntil`
				FROM `". $config['prefix'] . "tickler`
				WHERE `ticklerId` = '{$values['noteId']}'";
			break;

		case "selecttimecontext":
			$sql="SELECT `timeframeId`, `timeframe`, `description`, `type`
				FROM `". $config['prefix'] . "timeitems`
				WHERE `timeframeId` = '{$values['tcId']}'";
			break;

		case "spacecontextselectbox":
			$sql="SELECT `contextId`, `name`, `description`
				FROM `". $config['prefix'] . "context` as cn
				ORDER BY {$sort['spacecontextselectbox']}";
			break;

		case "testitemrepeat":
			$sql="SELECT ia.`repeat`,its.`dateCompleted`
				FROM `{$config['prefix']}itemattributes` as ia
                JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
				WHERE ia.`itemId`='{$values['itemId']}'";
			break;

		case "testnextaction":
			$sql="SELECT `parentId`, `nextaction`
				FROM `". $config['prefix'] . "nextactions`
				WHERE `nextaction`='{$values['itemId']}'";
			break;

		case "timecontextselectbox":
			$sql="SELECT `timeframeId`, `timeframe`, `description`, `type`
				FROM `". $config['prefix'] . "timeitems` as ti".$values['timefilterquery']."
				ORDER BY {$sort['timecontextselectbox']}";
			break;

		case "touchitem":
			$sql="UPDATE `". $config['prefix'] . "itemstatus`
				SET `lastModified` = NULL
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updatecategory":
			$sql="UPDATE `". $config['prefix'] ."categories`
				SET `category` ='{$values['name']}',
						`description` ='{$values['description']}'
				WHERE `categoryId` ='{$values['id']}'";
			break;

		case "updatechecklist":
			$sql="UPDATE `". $config['prefix'] ."checklist`
				SET `title` = '{$values['title']}',
						`description` = '{$values['description']}',
						`categoryId` = '{$values['categoryId']}'
				WHERE `checklistId` ='{$values['id']}'";
			break;

		case "updatechecklistitem":
			$sql="UPDATE `". $config['prefix'] . "checklistitems`
				SET `notes` = '{$values['notes']}', `item` = '{$values['item']}',
						`checklistId` = '{$values['id']}',
						`checked`='{$values['checked']}'
				WHERE `checklistItemId` ='{$values['itemId']}'";
			break;

		case "updatedeadline":
			$sql="UPDATE `{$config['prefix']}itemattributes`
				SET `deadline` ={$values['deadline']}
				WHERE `itemId` = '{$values['itemId']}'";
			break;
			
		case "updateitem":
			$sql="UPDATE `". $config['prefix'] . "items`
				SET `description` = '{$values['description']}',
						`title` = '{$values['title']}',
						`desiredOutcome` = '{$values['desiredOutcome']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemattributes":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `type` = '{$values['type']}',
						`isSomeday`= '{$values['isSomeday']}',
						`categoryId` = '{$values['categoryId']}',
						`contextId` = '{$values['contextId']}',
						`timeframeId` = '{$values['timeframeId']}',
						`deadline` ={$values['deadline']},
						`repeat` = '{$values['repeat']}',
						`suppress`='{$values['suppress']}',
						`suppressUntil`='{$values['suppressUntil']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemtype":
			$sql="UPDATE `{$config['prefix']}itemattributes`
				SET `type` = '{$values['type']}',
					`isSomeday`= '{$values['isSomeday']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updatelist":
			$sql="UPDATE `". $config['prefix'] . "list`
				SET `title` = '{$values['title']}',
						`description` = '{$values['description']}',
						`categoryId` = '{$values['categoryId']}'
				WHERE `listId` ='{$values['id']}'";
			break;

		case "updatelistitem":
			$sql="UPDATE `". $config['prefix'] . "listitems`
				SET `notes` = '{$values['notes']}', `item` = '{$values['item']}',
						`listId` = '{$values['id']}',
						`dateCompleted`={$values['dateCompleted']}
				WHERE `listItemId` ='{$values['itemId']}'";
			break;

		case "updateparent":
			$sql="INSERT INTO `". $config['prefix'] . "lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['itemId']}')
				ON DUPLICATE KEY UPDATE `parentId`='{$values['parentId']}'";
			break;

		case "updatenextaction":
			$sql="INSERT INTO `". $config['prefix'] . "nextactions`
						(`parentId`,`nextaction`)
				VALUES ('{$values['parentId']}','{$values['itemId']}')
				ON DUPLICATE KEY UPDATE `nextaction`='{$values['itemId']}'";
			break;

		case "updatenote":
			$sql="UPDATE `". $config['prefix'] . "tickler`
				SET `date` = '{$values['date']}',
					`note` = '{$values['note']}',
					`title` = '{$values['title']}',
					`repeat` = '{$values['repeat']}',
					`suppressUntil` = '{$values['suppressUntil']}'
				WHERE `ticklerId` = '{$values['noteId']}'";
			break;

		case "updatespacecontext":
			$sql="UPDATE `". $config['prefix'] . "context`
				SET `name` ='{$values['name']}',
						`description`='{$values['description']}'
				WHERE `contextId` ='{$values['id']}'";
			break;

		case "updatetimecontext":
			$sql="UPDATE `". $config['prefix'] . "timeitems`
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
function sqlparts($part,$config,$values) {

  if (is_array($values))
    foreach ($values as $key=>$value)
        $values[$key] = safeIntoDB($value, $key);
        
  switch ($part) {
	case "activeitems":
		$sqlpart = " ((CURDATE()>=DATE_ADD(ia.`deadline`, INTERVAL -(ia.`suppressUntil`) DAY)) OR ia.`suppress`!='y') ";
		break;
	case "activelistitems":
		$sqlpart = " li.`dateCompleted` IS NULL ";
		break;
	case "categoryfilter":
		$sqlpart = " ia.`categoryId` = '{$values['categoryId']}' ";
		break;
	case "categoryfilter-parent":
		$sqlpart = " y.`pcategoryId` = '{$values['categoryId']}' ";
		break;
	case "checkchildren":
		$sqlpart = " LEFT JOIN (
                                        SELECT parentId as itemId,COUNT(DISTINCT nextaction) AS numNA
                                            FROM {$config['prefix']}nextactions GROUP BY itemId
                                        ) AS na ON(na.itemId=x.itemId)

                                      LEFT JOIN (
                                        SELECT cl.parentId AS itemId,count(DISTINCT cl.itemId) as numChildren
                                            FROM {$config['prefix']}lookup         AS cl
                                            JOIN {$config['prefix']}itemstatus     AS chis ON (cl.itemId=chis.itemId)
                                            JOIN {$config['prefix']}itemattributes AS chia ON (cl.itemId=chia.itemId)
                                            WHERE chis.dateCompleted IS NULL AND chia.type IN ('a','p','g','m','v','o','i','w')
                                            GROUP BY cl.parentId
                                        ) AS act ON (act.itemId=x.itemId) ";
		break;
	case "checklistcategoryfilter":
		$sqlpart = " cl.`categoryId`='{$values['categoryId']}' ";
		break;
	case "completeditems":
		$sqlpart = " its.`dateCompleted` IS NOT NULL ";
		break;
	case "completedlistitems":
		$sqlpart = " li.`dateCompleted` IS NOT NULL ";
		break;
	case "contextfilter":
		$sqlpart = " ia.`contextId` = '{$values['contextId']}' ";
		break;
	case "countchildren":
		$sqlpart = " ,na.numNA, act.numChildren";
		break;
	case "due":
		$sqlpart = " (CURDATE()>=ia.`deadline` AND ia.`deadline` IS NOT NULL) ";
		break;
	case "getNA":
		$sqlpart = " , COUNT(DISTINCT na.nextaction) as NA ";
		break;
	case "hasparent":
		$sqlpart = " y.`parentId` = '{$values['parentId']}' ";
		break;
	case "isNA":
		$sqlpart = " LEFT JOIN ( SELECT nextaction FROM {$config['prefix']}nextactions
                               ) AS na ON(na.nextaction=x.itemId) ";
		break;
	case "isNAonly":
        $sqlpart = " INNER JOIN {$config['prefix']}nextactions AS na ON(na.nextaction=x.itemId) ";
		break;
	case "issomeday":
		$sqlpart = " ia.`isSomeday` = '{$values['isSomeday']}' ";
		break;
	case "limit":
		$sqlpart = " LIMIT {$values['maxItemsToSelect']} ";
		break;
	case "listcategoryfilter":
		$sqlpart = " l.`categoryId`='{$values['categoryId']}' ";
		break;
    case "liveparents":
        $sqlpart = "((CURDATE()>=DATE_ADD(y.`pdeadline`, INTERVAL -(y.`psuppressUntil`) DAY)) OR y.`psuppress`!='y' OR y.`psuppress` IS NULL)"
                    ." AND (y.`pdatecompleted` IS NULL) "
                    ." AND (y.`pisSomeday`='n' OR y.`pisSomeday` IS NULL)";
		break;
	case "matchall":
		$sqlpart = " (i.`title` LIKE '%{$values['needle']}%'
                                      OR i.`description` LIKE '%{$values['needle']}%'
                                      OR i.`desiredOutcome` LIKE '%{$values['needle']}%' )";
		break;
	case "notcategoryfilter":
		$sqlpart = " ia.`categoryId` != '{$values['categoryId']}' ";
		break;
	case "notcategoryfilter-parent":
		$sqlpart = " y.`pcategoryId` != '{$values['categoryId']}' ";
		break;
	case "notcontextfilter":
		$sqlpart = " ia.`contextId` != '{$values['contextId']}' ";
		break;
	case "notefilter":
		$sqlpart = " (`date` IS NULL) OR (CURDATE()>= `date`) ";
		break;
	case "nottimeframefilter":
		$sqlpart = " ia.`timeframeId` !='{$values['timeframeId']}' ";
		break;
	case "pendingitems":
		$sqlpart = " its.`dateCompleted` IS NULL ";
		break;
	case "repeating":
		$sqlpart = " ia.`repeat` >0 ";
		break;
	case "singleitem":
		$sqlpart = " i.`itemId`='{$values['itemId']}' ";
		break;
	case "suppresseditems":
		$sqlpart = " ia.`suppress`='y' AND (CURDATE()<=DATE_ADD(ia.`deadline`, INTERVAL -(ia.`suppressUntil`) DAY)) ";
		break;
	case "timeframefilter":
		$sqlpart = " ia.`timeframeId` ='{$values['timeframeId']}' ";
		break;
	case "timetype":
		$sqlpart = " ti.`type` = '{$values['type']}' ";
		break;
	case "typefilter":
		$sqlpart = " ia.`type` = '{$values['type']}' ";
		break;
/*
	case "ptypefilter":
		$sqlpart = " ia.`type` = '{$values['ptype']}' ";
		break;
*/
    default:
        if ($config['debug'] & _GTD_DEBUG) echo "<p class='error'>Failed to find sql component '$part'</p>'";
        $sqlpart=$part;
        break;
  }

  if ($config['debug'] & _GTD_DEBUG)
      echo "<pre>Sqlparts '$part': Result $sqlpart<br />Sanitised values in sqlparts: ",print_r($values,true),'</pre>';

  return $sqlpart;
}

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
