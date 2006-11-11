<?php

$sqlparts = array(
    "activeitems"           =>  " AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') ",
    "activeitemsandproject" =>  " AND (`projectstatus`.`dateCompleted` IS NULL OR `projectstatus`.`dateCompleted` = '0000-00-00')  AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') AND ((CURDATE() >= DATE_ADD(`itemattributes`.`deadline`, INTERVAL -(`itemattributes`.`suppressUntil`) DAY)) OR `itemattributes`.`suppress`='n' OR ((CURDATE() >= DATE_ADD(`projectattributes`.`deadline`, INTERVAL -(`projectattributes`.`suppressUntil`) DAY)))) ",
    "activelistitems"       =>  " AND (`listItems`.`dateCompleted` IS NOT NULL AND `listItems`.`dateCompleted` ='0000-00-00') ",
    "activeprojects"        =>  " AND (`projectstatus`.`dateCompleted` IS NULL OR `projectstatus`.`dateCompleted` = '0000-00-00') AND (((CURDATE()>=DATE_ADD(`projectattributes`.`deadline`, INTERVAL -(`projectattributes`.`suppressUntil`) DAY)) OR `projectattributes`.`suppress`='n')) ",
    "categoryfilter"        =>  " AND `projectattributes`.`categoryId` = '{$values['categoryId']}' ",
    "contextfilter"         =>  " AND `itemattributes`.`contextId` = '{$values['contextId']}' ",
    "completeditems"        =>  " AND  `itemstatus`.`dateCompleted` > 0 ",
    "completedlistitems"    =>  " AND (`listItems`.`dateCompleted`!='0000-00-00' AND `listItems`.`dateCompleted` IS NOT NULL) ",
    "completedprojects"     =>  " AND `projectstatus`.`dateCompleted` > 0 ",
    "getchecklists"         =>  " AND `checklist`.`categoryId`='{$values['categoryId']}' ",
    "getlists"              =>  " AND `list`.`categoryId`='{$values['categoryId']}' ",
    "issomeday"             =>  " AND `projectattributes`.`isSomeday` = '{$values['isSomeday']}' ",
    "notcategoryfilter"     =>  " AND `projectattributes`.`categoryId` != '{$values['categoryId']}' ",
    "notcontextfilter"      =>  " AND `itemattributes`.`contextId` != '{$values['contextId']}' ",
    "nottimeframefilter"    =>  " AND `itemattributes`.`timeframeId` !='{$values['timeframeId']}' ",
    "projectfilter"         =>  " AND `itemattributes`.`projectId` = '{$values['projectId']}' ",
    "suppresseditems"       =>  " AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') AND (`projectstatus`.`dateCompleted` IS NULL OR `projectstatus`.`dateCompleted` = '0000-00-00') AND (`itemattributes`.`suppress`='y') ",
    "suppressedprojects"    =>  " AND (`projectstatus`.`dateCompleted` IS NULL OR `projectstatus`.`dateCompleted` = '0000-00-00') AND (`projectattributes`.`suppress`='y') ",
    "timeframefilter"       =>  " AND `itemattributes`.`timeframeId` ='{$values['timeframeId']}' ",
    "typefilter"            =>  " AND `itemattributes`.`type` = '{$values['type']}' ",
    );
?>
