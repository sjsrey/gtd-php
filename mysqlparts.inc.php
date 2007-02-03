<?php

$sqlparts = array(
    "activeitems"               =>  " ((CURDATE()>=DATE_ADD(`itemattributes`.`deadline`, INTERVAL -(`itemattributes`.`suppressUntil`) DAY)) OR `itemattributes`.`suppress`!='y') ",
    "activelistitems"           =>  " (`listItems`.`dateCompleted` IS NOT NULL AND `listItems`.`dateCompleted` ='0000-00-00') ",
    "activeparents"             =>  " ((CURDATE()>=DATE_ADD(y.`pdeadline`, INTERVAL -(y.`psuppressUntil`) DAY)) OR y.`psuppress`!='y' OR y.`psuppress` IS NULL)",
    "categoryfilter"            =>  " `itemattributes`.`categoryId` = '{$values['categoryId']}' ",
    "categoryfilter-parent"     =>  " y.`pcategoryId` = '{$values['categoryId']}' ",
    "checklistcategoryfilter"   =>  " `checklist`.`categoryId`='{$values['categoryId']}' ",
    "completedlistitems"        =>  " (`listItems`.`dateCompleted`!='0000-00-00' AND `listItems`.`dateCompleted` IS NOT NULL) ",
    "contextfilter"             =>  " `itemattributes`.`contextId` = '{$values['contextId']}' ",
    "due"                       =>  " (CURDATE()>=`itemattributes`.`deadline` AND `itemattributes`.`deadline` IS NOT NULL AND `itemattributes`.`deadline`!='0000-00-00') ",
    "isnotcompleteditem"            =>  " `itemstatus`.`dateCompleted` is  NULL",
    "issomeday"                 =>  " `itemattributes`.`isSomeday` = '{$values['isSomeday']}' ",
    "issomeday-parent"          =>  " y.`pisSomeday` = '{$values['isSomeday']}' OR y.`pisSomeday` IS NULL",
    "listcategoryfilter"        =>  " `list`.`categoryId`='{$values['categoryId']}' ",
    "pendingitems"              =>  " (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') ",
    "notcategoryfilter"         =>  " `itemattributes`.`categoryId` != '{$values['categoryId']}' ",
    "notcategoryfilter-parent"  =>  " y.`pcategoryId` != '{$values['categoryId']}' ",
    "notcontextfilter"          =>  " `itemattributes`.`contextId` != '{$values['contextId']}' ",
    "notefilter"                =>  " (`date` IS NULL OR `date` = '0000-00-00') OR (CURDATE()>= `date`) ",
    "nottimeframefilter"        =>  " `itemattributes`.`timeframeId` !='{$values['timeframeId']}' ",
    "repeating"                 =>  " `itemattributes`.`repeat` >0 ",
    "suppresseditems"           =>  " `itemattributes`.`suppress`='y' AND (CURDATE()<=DATE_ADD(`itemattributes`.`deadline`, INTERVAL -(`itemattributes`.`suppressUntil`) DAY)) ",
    "timeframefilter"           =>  " `itemattributes`.`timeframeId` ='{$values['timeframeId']}' ",
    "timegoals"                 =>  " `timeitems`.`type` = 'g' ",
    "timeitems"                 =>  " `timeitems`.`type` !='g' ",
    "typefilter"                =>  " `itemattributes`.`type` = '{$values['type']}' ",
/*
    "ptypefilter"         =>  " `itemattributes`.`type` = '{$values['ptype']}' ", 
*/
    );
?>
