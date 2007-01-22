<?php
//INCLUDES
include_once('header.php');

//FORM DATA COLLECTION AND PARSING
$referrer=$_POST['referrer']{0};

$values=array();
$values['itemId'] = (int) $_GET['itemId'];
$values['type']=$_POST['type']{0};
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['description'] = mysql_real_escape_string($_POST['description']);
$values['desiredOutcome']=mysql_real_escape_string($_POST['desiredOutcome']);
$values['categoryId']=(int) $_POST['categoryId'];
$values['contextId'] = (int) $_POST['contextId'];
$values['timeframeId'] = (int) $_POST['timeframeId'];
$parents = $_POST['parentId']; //array
$values['deadline'] = $_POST['deadline'];
$values['repeat'] = (int) $_POST['repeat'];
$values['suppress'] = $_POST['suppress']{0};
$values['suppressUntil'] = (int) $_POST['suppressUntil'];
$values['nextAction']=$_POST['nextAction']{0};
$values['dateCompleted'] = $_POST['dateCompleted'];
$values['delete'] = $_POST['delete']{0};
if ($_POST['isSomeday']{0}=='y') $values['isSomeday']='y';
else $values['isSomeday']='n';

/*
The validity checks below, are shared between updateItem and processItem,
   as is much of the rest of the file.
 So much duplication suggests we either incorporate one into the other,
   or share a new .inc between them
*/   
if ($values['suppress']!="y") $values['suppress']="n";
if ($values['nextaction']!="y") $values['nextaction']="n";
if (!isset($values['title'])) die ("No title. Item NOT updated.");
if (isset($values['deadline']) && $values['deadline']) {
	$values['deadline']="'".$values['deadline']."'";
} else {
	$values['deadline']="NULL";
}	
if (isset($values['dateCompleted']) && $values['dateCompleted']) {
	$values['dateCompleted']="'".$values['dateCompleted']."'";
} else {
	$values['dateCompleted']="NULL";
}

//SQL CODE AREA
if($values['delete']=="y"){
    query("deleteitemstatus",$config,$values);
    query("deleteitemattributes",$config,$values);
    query("deleteitem",$config,$values);
    query("deletelookup",$config,$values);

    echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listItems.php?type='.$values['type'].'" />';
    if ($values['nextAction']=='y') query("deletenextaction",$config,$values);
    }

else {
    query("updateitemstatus",$config,$values);
    query("updateitemattributes",$config,$values);
    query("updateitem",$config,$values);
    query("deletelookup",$config,$values); //remove all parents before adding current ones
    if ($parents>0) {
        foreach ($parents as $values['parentId']) {
            if ($values['parentId']>0) {
                $result = query("updateparent",$config,$values);
                if ($values['nextAction']=='y' && ($values['dateCompleted']==NULL || $values['dateCompleted']=="0000-00-00")) foreach ($parents as $values['parentId']) $result = query("updatenextaction",$config,$values);
                else $result = query("deletenextaction",$config,$values);
                }
            else $result = query("deletenextaction",$config,$values);
            }
        }
    else $result = query("deletenextaction",$config,$values);
    }

//check to see if manually setting item completed
if (($values['dateCompleted'] != '0000-00-00' && $values['dateCompleted']!=NULL) && $values['repeat']>0) {
        $nextdue=strtotime("+".$values['repeat']."day");
        $values['nextduedate']=gmdate("Y-m-d", $nextdue);
        //copy data to tables with new id
        $result=query("newitem",$config,$values);
        $values['newitemId'] = $GLOBALS['lastinsertid'];
        $values['deadline']=$values['nextduedate'];
        $result=query("newitemattributes",$config,$values);
        $values['dateCompleted']="NULL";
        $result=query("newitemstatus",$config,$values);
        //copy parent information with new id
        if ($values['parentId']>0) $result=query("newparent",$config,$values);
        //make next action if necessary
        if ($values['nextAction']=="y") $result = query("copynextaction",$config,$values);
        }


echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listItems.php?type='.$referrer.'" />';

include_once('footer.php');
?>
