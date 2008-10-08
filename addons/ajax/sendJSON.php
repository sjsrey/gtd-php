<?php
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
include_once 'headerDB.inc.php';
require_once 'JSON.php';
// create a new instance of Services_JSON
$json = new Services_JSON();
$values = array();
if (!empty($_REQUEST['itemId'])) {
    // Request was for a single item, so do query, get data
    $values['itemId']= (int) $_REQUEST['itemId'];
    $values['filterquery']=' WHERE '.sqlparts('singleitem',$values);
    $result = query("selectitem",$values);
    if (!$result) exit('{}');
    echo $json->encode($result[0]);
    exit();
/*
} else if (!empty($_REQUEST['needle'])) {
    //  searching for a particular string
    $values['type']=$_REQUEST['type'];
    $values['needle']=$_REQUEST['needle'];
    $q=($_REQUEST['haystack']==='title')?'matchtitle':'matchall';
    //do query
    $values['filterquery']=sqlparts('typefilter',$values);
    $values['filterquery'].=' AND '.sqlparts($q,$values);
    $result= query('selectfind',$values);
*/
} else {
    // getting all items of a particular type
    $values['filterquery']='WHERE '.sqlparts("pendingitems",$values);
    $values['type']=empty($_REQUEST['type'])?'*':$_REQUEST['type'];
    if ($values['type']!=='*')
        $values['filterquery'] .= " AND ".sqlparts("typefilter",$values);
    $result= query('getitems',$values);
    $out=array();
    if ($result)
        foreach ($result as $line)
            $out[$line['itemId']]=$line['title'];
    echo $json->encode($out);
    exit();
}

 // php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
