<?php

$sqlparts = array(
    "contextfilter"     =>  " AND itemattributes.contextId = '{$values['contextId']}' ",
    "categoryfilter"    =>  " AND projectattributes.categoryId = '{$values['categoryId']}' ",
    "timeframefilter"   =>  " AND itemattributes.timeframeId ='{$values['timeframeId']}' ",
    "notcontextfilter"     =>  " AND itemattributes.contextId != '{$values['contextId']}' ",
    "notcategoryfilter"    =>  " AND projectattributes.categoryId != '{$values['categoryId']}' ",
    "nottimeframefilter"   =>  " AND itemattributes.timeframeId !='{$values['timeframeId']}' "
    );

?>