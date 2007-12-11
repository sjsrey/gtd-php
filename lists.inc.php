<?php
$isChecklist=(isset($_REQUEST['type']) && $_REQUEST['type']==='C');
if ($isChecklist) {
    $type='C';
    $check='check';
} else {
    $type='L';
    $check='';
}
$values=array(
     'id'        => (isset($_REQUEST['id']))         ? (int) $_REQUEST['id'] : 0
    ,'itemId'    => (empty($_REQUEST['itemId']))     ? '' : $_REQUEST['itemId']
    ,'categoryId'=> (isset($_REQUEST['categoryId'])) ? (int)$_REQUEST['categoryId']:0
    );
$urlSuffix="type=$type";

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
