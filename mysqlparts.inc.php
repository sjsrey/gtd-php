<?php

$sqlparts = array(
    "contextfilter"     =>  " AND itemattributes.contextId = '{$values['contextId']}' ",
    "categoryfilter"    =>  " AND projectattributes.categoryId = '{$values['categoryId']}' ",
    "timeframefilter"   =>  " AND itemattributes.timeframeId ='{$values['timeframeId']}' ",

    );

?>