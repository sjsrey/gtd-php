<?php
require_once('headerDB.inc.php');
if ($config['debug'] & _GTD_DEBUG)  include_once('header.php');
include_once('lists.inc.php');

$nextURL="reportLists.php?id={$values['id']}&$urlSuffix"; // default next action is to show the report for the current list

$action=$_REQUEST['action'];
if (isset($_REQUEST['delete']))
    $action=(($values['itemId'])?'item':'list').'delete';
else if (isset($_REQUEST['listclear']))
    $action='listclear';

switch ($action) {
    //-----------------------------------------------------------------------------------
    case 'itemcreate':
        $values['item']=$_POST['title'];
        $values['notes']=$_POST['notes'];
        $values['checked']='n';
        $values['dateCompleted']='NULL';
        $result = query("new{$check}listitem",$config,$values);
        if ($result) {
            $msg="Created";
            if (!empty($_REQUEST['again'])) $nextURL="editListItems.php?id={$values['id']}&$urlSuffix";
        } else {
            $msg="Failed to create";
            $nextURL="listLists.php?id={$values['id']}&$urlSuffix";
        }
        $_SESSION['message'][]="$msg {$check}list item: '{$values['item']}'";
        break;
    //-----------------------------------------------------------------------------------
    case 'itemdelete':
        $result=query("delete{$check}listitem",$config,$values);
        $_SESSION['message'][]="Deleted {$check}list item: '{$_POST['title']}'";
        break;
    //-----------------------------------------------------------------------------------
    case 'itemedit':
        $values['item']=$_POST['title'];
        $values['notes']=$_POST['notes'];
        if ($isChecklist)
            $values['checked']=(isset($_POST['checked']))?'y':'n';
        else
            $values['dateCompleted']=(empty($_POST['dateCompleted']))?'NULL':"'{$_POST['dateCompleted']}'";
        $result=query("update{$check}listitem",$config,$values);
        $msg=($result) ? "Updated" : "No changes needed to";
        $_SESSION['message'][]= "$msg {$check}list item: '{$values['item']}'";
        break;
    //-----------------------------------------------------------------------------------

    //-----------------------------------------------------------------------------------
    case 'listclear':
        if ($isChecklist) {
            query("clearchecklist",$config,$values);
            $_SESSION['message'][]='All checklist items have been unchecked';
        }
        break;
    //-----------------------------------------------------------------------------------
    case 'listcomplete':
        if ($isChecklist) {
            query("clearchecklist",$config,$values);
            if (empty($_POST['completed'])) {
                $_SESSION['message'][]='All checklist items have been unchecked';
                break;
            }
            $query='checkchecklistitem';
        } else {
            if (empty($_POST['completed'])) break;
            $query="completelistitem";
            if (!isset($values['dateCompleted'])) $values['dateCompleted']="'".date('Y-m-d')."'";
        }
        $sep='';
        $ids='';
        foreach ($_POST['completed'] as $id) {
            $ids.=$sep.(int) $id;
            $sep="','";
        }
        $values['itemfilterquery']="'$ids'";
        $cnt=query($query,$config,$values);
        $msg  = "$cnt {$check}list item";
        if ($cnt!==1) $msg .= 's';
        if ($isChecklist) {
            $msg .= ($cnt!==1) ? ' are' : ' is';
            $msg .= ' now';
        } else {
            $msg .= ($cnt!==1) ? ' have' : ' has';
            $msg .= ' been';
        }
        $msg .= " marked complete";
        $_SESSION['message'][]=$msg;
        break;
    //-----------------------------------------------------------------------------------
    case 'listcreate':
        $values['title'] = $_POST['title'];
        $values['description'] = $_POST['description'];
        //TOFIX datecompleted, completed
        $result= query("new{$check}list",$config,$values,$sort);
        if ($result) {
            $values['id']=$GLOBALS['lastinsertid'];
            $msg='You can now create items for your newly created';
            $nextURL="editListItems.php?id={$values['id']}&$urlSuffix";
        } else {
            $msg='Failed to create';
            $nextURL="listLists.php?$urlSuffix";
        }
        $_SESSION['message'][]="$msg {$check}list: '{$values['title']}'";
        break;
    //-----------------------------------------------------------------------------------
    case 'listdelete':
        query("delete{$check}list",$config,$values);
        $numDeleted=query("remove{$check}listitems",$config,$values);
        $msg="Deleted {$check}list '{$_REQUEST['title']}'";
        if ($numDeleted) {
            $msg.=" and its $numDeleted item";
            if ($numDeleted>1) $msg.='s';
        }
        $_SESSION['message'][]=$msg;
        $nextURL="listLists.php?$urlSuffix";
        break;
    //-----------------------------------------------------------------------------------
    case 'listedit':
        $values['title'] = $_POST['title'];
        $values['description'] = $_POST['description'];
        $result=query("update{$check}list",$config,$values);
        $msg=($result) ? "Updated" : "No changes needed to";
        $_SESSION['message'][]= "$msg {$check}list: '{$values['title']}'";
        break;
    //-----------------------------------------------------------------------------------
    default:
        break;
}

nextScreen($nextURL);
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
