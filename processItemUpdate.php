<?php
//INCLUDES
include_once('header.php');

//GET URL VARIABLES
$values['itemId'] = (int) $_GET['itemId'];

//GET FORM VARIABLES
$values['contextId']=(int) $_POST['contextId'];
$values['type']=$_POST['type']{0};
$values['referrer']=$_POST['referrer']{0};
$values['projectId']=(int) $_GET['projectId'];
$values['timeId']=(int) $_POST['timeId'];
$values['completedNas'] = $_POST['completedNas'];
$values['isNext'] = (int) $_POST['isNext'];

//Check if Session Variables Should be Updated
if ($_GET['contextId']>0) $values['contextId']=(int) $_GET['contextId'];
else $values['contextId']=(int) $_POST['contextId'];
if ($values['contextId']>0) $_SESSION['contextId']=$values['contextId'];
if ($_GET['categoryId']>0) $values['categoryId']=(int) $_GET['categoryId'];
else $values['categoryId']=(int) $_POST['categoryId'];
if ($values['categoryId']>0) $_SESSION['categoryId']=$values['categoryId'];
else $values['categoryId']=$_SESSION['categoryId'];

//SQL CODE
if(isset($values['completedNas'])){
    $today=strtotime("now");
    $values['date']=date('Y-m-d');
    foreach ($values['completedNas'] as $values['completedNa']) {

    //test to see if action is repeating
        $testrow = query("testitemrepeat",$config,$values);
        //if repeating, copy result row to new row (new action) with updated due date
        if ($testrow[0]['repeat']!=0) {
            $nextdue=strtotime("+".$testrow[0]['repeat']."day");
            $values['nextduedate']=gmdate("Y-m-d", $nextdue);
            $values['itemId']=$values['completedNa'];

            //retrieve item details
            $copyresult = query("selectitem",$config,$values,$options,$sort);
            $values['projectId']=$copyresult[0]['projectId'];
            $values['contextId']=$copyresult[0]['contextId'];
            $values['timeframeId']=$copyresult[0]['timeframeId'];
            $values['type']=$copyresult[0]['type'];
            $values['title']=$copyresult[0]['title'];
            $values['description']=$copyresult[0]['description'];
            $values['categoryId']=$copyresult[0]['categoryId'];
            $values['isSomeday']=$copyresult[0]['isSomeday'];
            $values['repeat']=$copyresult[0]['repeat'];
            $values['suppress']=$copyresult[0]['suppress'];
            $values['suppressUntil']=$copyresult[0]['suppressUntil'];

            //copy data to projects tables with new id
            $result=query("newitem",$config,$values,$options,$sort);
            $values['newitemId'] = $GLOBALS['lastinsertid'];
            $values['deadline']=$values['nextduedate'];
            $result=query("newitemattributes",$config,$values,$options,$sort);
            $result=query("newitemstatus",$config,$values,$options,$sort);

            //test to see if item is a nextaction
            $nextactiontest=query("testnextaction",$config,$values);
            //update nextactions list with new itemId if nextaction (may want to set user option for this later)
            if ($nextactiontest[0]['nextaction']==$values['completedNa']) $result = query("copynextaction",$config,$values);
            }

        //in either case, set original row completed
        $result = query("completeitem",$config,$values);
    
        //remove original row from nextActions list
        $result = query("deletenextaction",$config,$values);
        } 
    }

// Check on user radio button reset of next action 
if (isset($values['isNext'])){
    $values['itemId'] = $values['isNext'];
    $result = query("updatenextaction",$config,$values);
    }

if ($values['referrer']=="i") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listItems.php?type='.$values['type'].'&contextId='.$values['contextId'].'&timeId='.$values['timeId'].'&categoryId='.$values['categoryId'].'">';
	}

elseif ($values['referrer']=="p") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=projectReport.php?projectId='.$values['projectId'].'">';
	}

elseif ($values['referrer']=="c") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=reportContext.php">';
	}

elseif ($values['referrer']=="t") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=tickler.php">';
}
else {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=reportContext.php">';
	}

include_once('footer.php');
?>
