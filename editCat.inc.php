<?php
require_once 'headerDB.inc.php';
$values = array();
$thiscat=array();
$field=$_GET['field'];

if (isset($_GET['id'])) {
    $id=(int) $_GET['id'];
    $thiscat['id']=$id;
    if ($id===0) {
        $title="Create $field";
        $canDelete=false;
        $thiscat['name']='';
        $thiscat['description']='';
        $thiscat['type']='a';
    } else {
        $title="Edit $field";
        $canDelete=true;
    }
} else {
    $id=0;
    $thiscat['id']=false;
    $title="$field List";
    $canDelete=false;
}

$keys=array('id','name','description');
switch ($field) {
    case 'category':
        $query='categoryselectbox';
        $showTypes=false;
        break;
    case 'context':
        $query='spacecontextselectbox';
        $showTypes=false;
        break;
    case 'time-context':
        $query='timecontextselectbox' ;
        $values['timefilterquery'] = '';
        $keys[]='type';
        $showTypes=$config['useTypesForTimeContexts'];
        break;
    default:
        $query='';
        $showTypes=false;
        break;
}
$result = query($query,$config,$values,$sort);
$catlist=array();
$count=0;
$thiscat=false;

if ($result) {
 	$firstcat=0;
 	$nextcat=-1;
    foreach ($result as $checkcat) {
    	$newcat=array();
    	$i=0;
        foreach ($checkcat as $item)
        	$newcat[$keys[$i++]]=$item;
        if (!$firstcat) $firstcat=$newcat['id'];
        if (!$nextcat) $nextcat=$newcat['id'];
        if ($newcat['id']==$id) {
            $thiscat=$newcat;
            $nextcat=0;
        } else $catlist[]=$newcat;
        $count++;
    }
    if (!$nextcat)
        $nextcat=$firstcat;
    else if ($nextcat===-1)
        $nextcat=0;
}
if ($thiscat)
    $title.=': '.$thiscat['name'];
else if ($id)
    $title=makeClean("Failed to find $field with id=$id");

