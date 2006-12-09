<?php

$sqlparts = array(


    "activeitems"           =>  " ((CURDATE()>=DATE_ADD(`itemattributes`.`deadline`, INTERVAL -(`itemattributes`.`suppressUntil`) DAY)) OR `itemattributes`.`suppress`!='y') ",
    "activeparents"         =>  " ((CURDATE()>=DATE_ADD(y.`pdeadline`, INTERVAL -(y.`psuppressUntil`) DAY)) OR y.`psuppress`!='y' OR y.`psuppress` IS NULL)",
    "categoryfilter"        =>  " `itemattributes`.`categoryId` = '{$values['categoryId']}' ",
    "categoryfilter-parent" =>  " y.`pcategoryId` = '{$values['categoryId']}' ",
    "completeditems"        =>  " `itemstatus`.`dateCompleted` > 0 ",
    "contextfilter"         =>  " `itemattributes`.`contextId` = '{$values['contextId']}' ",
    "due"                   =>  " (CURDATE()>=`itemattributes`.`deadline` AND `itemattributes`.`deadline` IS NOT NULL AND `itemattributes`.`deadline`!='0000-00-00') ",
    "issomeday"             =>  " `itemattributes`.`isSomeday` = '{$values['isSomeday']}' ",
    "issomeday-parent"      =>  " y.`pisSomeday` = '{$values['isSomeday']}' ",
    "pendingitems"          =>  " (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') ",
    "notcategoryfilter"     =>  " `itemattributes`.`categoryId` != '{$values['categoryId']}' ",
    "notcategoryfilter"     =>  " y.`pcategoryId` != '{$values['categoryId']}' ",
    "notcontextfilter"      =>  " `itemattributes`.`contextId` != '{$values['contextId']}' ",
    "nottimeframefilter"    =>  " `itemattributes`.`timeframeId` !='{$values['timeframeId']}' ",
    "repeating"             =>  " `itemattributes`.`repeat` >0 ",
    "suppresseditems"       =>  " `itemattributes`.`suppress`='y' AND (CURDATE()<=DATE_ADD(`itemattributes`.`deadline`, INTERVAL -(`itemattributes`.`suppressUntil`) DAY)) ",
    "timeframefilter"       =>  " `itemattributes`.`timeframeId` ='{$values['timeframeId']}' ",
    "typefilter"            =>  " `itemattributes`.`type` = '{$values['type']}' ",







    "activelistitems"       =>  " AND (`listItems`.`dateCompleted` IS NOT NULL AND `listItems`.`dateCompleted` ='0000-00-00') ",


    "completedlistitems"    =>  " AND (`listItems`.`dateCompleted`!='0000-00-00' AND `listItems`.`dateCompleted` IS NOT NULL) ",
    "getchecklists"         =>  " AND `checklist`.`categoryId`='{$values['categoryId']}' ",
    "getlists"              =>  " AND `list`.`categoryId`='{$values['categoryId']}' ",
    "timegoals"             =>  " WHERE `timeitems`.`type` = 'g' ", //FOR timecontextselectbox ONLY
    "timeitems"             =>  " WHERE `timeitems`.`type` !='g' ", //FOR timecontextselectbox ONLY
    "ptypefilter-w"         =>  " WHERE `itemattributes`.`type` = '{$values['ptype']}' ", //PLACE FIRST IN FILTER STRING


    "repeats"               =>  " AND `itemattributes`.`repeat`>'0' ",
    "doesnotrepeat"         =>  " AND `itemattributes`.`repeat`='0' ",
    "deadline"              =>  " AND `itemattributes`.`deadline` IS NOT NULL ",
    "nodeadline"            =>  " AND `itemattributes`.`deadline` IS NULL OR `itemattributes`.`deadline` = '0000-00-00' ",
    "duetoday"              =>  " AND `itemattributes`.`deadline` = '{$values['today']}' ",
    "neglected"             =>  " AND CURDATE()>DATE_ADD(`itemstatus`.`lastModified`,INTERVAL {$values['neglected']} DAY) ",
    "selectitem"            =>  " AND `items`.`itemId` = '{$values['itemId']}' ",

    "notefilter"            =>  " WHERE (`date` IS NULL OR `date` = '0000-00-00') OR (CURDATE()>= `date`) ",








    );

//NEED TO FIX ANDS AND WHERES---  move to page logic when chaining filters-- not filters themselves (no "-w" etc.. confusing)
?>
