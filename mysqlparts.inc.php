<?php
function getsqlparts($part,$config,$values) {
switch ($part) {
	case "activeitems":
		$sqlpart = " ((CURDATE()>=DATE_ADD(ia.`deadline`, INTERVAL -(ia.`suppressUntil`) DAY)) OR ia.`suppress`!='y') ";
		break;
	case "activelistitems":
		$sqlpart = " li.`dateCompleted` IS NULL ";
		break;
	case "activeparents":
		$sqlpart = " ((CURDATE()>=DATE_ADD(y.`pdeadline`, INTERVAL -(y.`psuppressUntil`) DAY)) OR y.`psuppress`!='y' OR y.`psuppress` IS NULL)";
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
	case "completedparents":
		$sqlpart = " y.`pdatecompleted` IS NOT NULL ";
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
		$sqlpart = " JOIN ( SELECT nextaction FROM {$config['prefix']}nextactions
                          ) AS na ON(na.nextaction=x.itemId) ";
		break;
	case "issomeday":
		$sqlpart = " ia.`isSomeday` = '{$values['isSomeday']}' ";
		break;
	case "issomeday-parent":
		$sqlpart = " y.`pisSomeday` = '{$values['isSomeday']}' OR y.`pisSomeday` IS NULL";
		break;
	case "limit":
		$sqlpart = " LIMIT {$values['maxItemsToSelect']} ";
		break;
	case "listcategoryfilter":
		$sqlpart = " l.`categoryId`='{$values['categoryId']}' ";
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
	case "onlynextactions":
		$sqlpart = " INNER JOIN {$config['prefix']}nextactions AS na ON(na.nextaction=x.itemId) ";
		break;
	case "pendingitems":
		$sqlpart = " its.`dateCompleted` IS NULL ";
		break;
	case "pendingparents":
		$sqlpart = " y.`pdatecompleted` IS NULL ";
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
        break;
    }
    return $sqlpart;
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
