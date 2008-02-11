<?php

//query listing for MySQL database
//API Documentation available in __________
//MySQL extensions to standard SQL have been avoided where known
//Queries may be rewritten in future as SQL evolves and as other database types are supported

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
                    JOIN (
                        SELECT DISTINCT `itemId` FROM `{$config['prefix']}lookup` AS lu
                            JOIN (SELECT pi.`itemId` AS parentId,
                                     pia.`isSomeday` AS pisSomeday,
                                     pia.`deadline` AS pdeadline,
						             pia.`suppress` AS psuppress,
						             pia.`suppressUntil` AS psuppressUntil,
						             pits.`dateCompleted` AS pdateCompleted
            					   FROM `{$config['prefix']}itemattributes` as pia
            					   JOIN `{$config['prefix']}items` as pi USING (`itemId`)
            					   JOIN `{$config['prefix']}itemstatus` as pits USING (`itemId`)
                                ) AS y USING (`parentId`) {$values['parentfilterquery']}
                    ) AS lut ON (i.`itemId`=lut.`itemId`)
                    {$values['childfilterquery']}";
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
                            JOIN (SELECT pi.`itemId` AS parentId,
                                     pia.`isSomeday` AS pisSomeday,
                                     pia.`deadline` AS pdeadline,
						             pia.`suppress` AS psuppress,
						             pia.`suppressUntil` AS psuppressUntil,
						             pits.`dateCompleted` AS pdateCompleted
            					   FROM `{$config['prefix']}itemattributes` as pia
            					   JOIN `{$config['prefix']}items` as pi USING (`itemId`)
            					   JOIN `{$config['prefix']}itemstatus` as pits USING (`itemId`)
                                ) AS y USING (`parentId`) {$values['parentfilterquery']}
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
                            LEFT OUTER JOIN (SELECT pi.`itemId` AS parentId,
                                     pia.`isSomeday` AS pisSomeday,
                                     pia.`deadline` AS pdeadline,
						             pia.`suppress` AS psuppress,
						             pia.`suppressUntil` AS psuppressUntil,
						             pits.`dateCompleted` AS pdateCompleted
            					   FROM `{$config['prefix']}itemattributes` as pia
            					   JOIN `{$config['prefix']}items` as pi USING (`itemId`)
            					   JOIN `{$config['prefix']}itemstatus` as pits USING (`itemId`)
                                ) AS y USING (`parentId`) {$values['parentfilterquery']}
                    ) AS nat ON (i.`itemId`=nat.`nextAction`)
                    {$values['childfilterquery']}
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
						{$values['parentfilterquery']}
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
				FROM `". $config['prefix'] . "context` ORDER BY `contextId` ASC";
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
        default:
            $sql="Failed to find sql query $querylabel";
            break;
    }
	return $sql;
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
