<?php

$sqlparts = array(
    "activeparents"         =>  " (y.`pdateCompleted` IS NULL OR y.`pdateCompleted` = '0000-00-00') AND ((CURDATE()>=DATE_ADD(y.`pdeadline`, INTERVAL -(y.`psuppressUntil`) DAY)) OR y.`psuppress`!='y' OR y.`psuppress` IS NULL)",
    "issomeday-parents"     =>  " AND y.`pisSomeday` = '{$values['isSomeday']}' ",
    "activeitems"           =>  " AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') AND ((CURDATE()>=DATE_ADD(`itemattributes`.`deadline`, INTERVAL -(`itemattributes`.`suppressUntil`) DAY)) OR `itemattributes`.`suppress`!='y') ",
    "activelistitems"       =>  " AND (`listItems`.`dateCompleted` IS NOT NULL AND `listItems`.`dateCompleted` ='0000-00-00') ",
    "categoryfilter"        =>  " AND `itemattributes`.`categoryId` = '{$values['categoryId']}' ",
    "categoryfilter-parent" => " AND y.`pcategoryId` = '{$values['categoryId']}' ",
    "contextfilter"         =>  " AND `itemattributes`.`contextId` = '{$values['contextId']}' ",
    "completeditems"        =>  " AND  `itemstatus`.`dateCompleted` > 0 ",
    "completedlistitems"    =>  " AND (`listItems`.`dateCompleted`!='0000-00-00' AND `listItems`.`dateCompleted` IS NOT NULL) ",
    "getchecklists"         =>  " AND `checklist`.`categoryId`='{$values['categoryId']}' ",
    "getlists"              =>  " AND `list`.`categoryId`='{$values['categoryId']}' ",
    "issomeday"             =>  " AND `itemattributes`.`isSomeday` = '{$values['isSomeday']}' ",
    "notcategoryfilter"     =>  " AND `itemattributes`.`categoryId` != '{$values['categoryId']}' ",
    "notcontextfilter"      =>  " AND `itemattributes`.`contextId` != '{$values['contextId']}' ",
    "nottimeframefilter"    =>  " AND `itemattributes`.`timeframeId` !='{$values['timeframeId']}' ",
    "suppresseditems"       =>  " AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') AND (`itemattributes`.`suppress`='y') ",
    "timeframefilter"       =>  " AND `itemattributes`.`timeframeId` ='{$values['timeframeId']}' ",
    "timegoals"             =>  " WHERE `timeitems`.`type` = 'g' ", //FOR timecontextselectbox ONLY
    "timeitems"             =>  " WHERE `timeitems`.`type` !='g' ", //FOR timecontextselectbox ONLY
    "ptypefilter-w"         =>  " WHERE `itemattributes`.`type` = '{$values['ptype']}' ", //PLACE FIRST IN FILTER STRING
    "typefilter-w"          =>  " WHERE `itemattributes`.`type` = '{$values['type']}' ",  //PLACE FIRST IN FILTER STRING
    "typefilter"            =>  " AND `itemattributes`.`type` = '{$values['type']}' ",
    "repeats"               =>  " AND `itemattributes`.`repeat`>'0' ",
    "doesnotrepeat"         =>  " AND `itemattributes`.`repeat`='0' ",
    "deadline"              =>  " AND `itemattributes`.`deadline` IS NOT NULL ",
    "nodeadline"            =>  " AND `itemattributes`.`deadline` IS NULL OR `itemattributes`.`deadline` = '0000-00-00' ",
    "duetoday"              =>  " AND `itemattributes`.`deadline` = '{$values['today']}' ",
    "neglected"             =>  " AND CURDATE()>DATE_ADD(`itemstatus`.`lastModified`,INTERVAL {$values['neglected']} DAY) ",
    "selectitem"            =>  " AND `items`.`itemId` = '{$values['itemId']}' ",
    "incompleteitems"       =>  " AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') ",
    "notefilter"            =>  " WHERE (`date` IS NULL OR `date` = '0000-00-00') OR (CURDATE()>= `date`) ",
    );

//NEED TO FIX ANDS AND WHERES---  move to page logic when chaining filters-- not filters themselves (no "-w" etc.. confusing)
?>
