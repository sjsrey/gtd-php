<?php
//INCLUDES
include_once('header.php');


//seperate referrer values from copied item values
//check for repeating item--- if repeating, need to create new item...

//GET URL ND FORM VARIABLES
$values=array();
$values['type']=$_POST['type']{0};
$values['referrer']=$_POST['referrer']{0};
$values['categoryId'] = (int) $_POST['categoryId'];
$values['contextId']=(int) $_POST['contextId'];
$values['timeId']=(int) $_POST['timeId'];
$values['completedNas'] = $_POST['completedNas'];
$values['isNext'] = (int) $_POST['isNext'];

//filter data
$filter['type']=$_POST['type']{0};
$filter['categoryId'] = (int) $_POST['categoryId'];
$filter['contextId']=(int) $_POST['contextId'];
$filter['timeId']=(int) $_POST['timeId'];
$filter['nottimecontext'] = $_POST['nottimecontext'];
$filter['notspacecontext'] = $_POST['notspacecontext'];
$filter['notcategory'] = $_POST['notcategory'];
$filter['tickler'] = $_POST['tickler'];
$filter['someday'] = $_POST['someday'];
$filter['completed'] = $_POST['completed'];
$filter['nextonly'] = $_POST['nextonly'];
$filter['repeatingonly'] = $_POST['repeatingonly'];
$filter['dueonly'] = $_POST['dueonly'];

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
        $values['itemId']=$values['completedNa'];

        //lookup parent of item
        $parentresult = query("lookupparent",$config,$values);
        if ($parentresult!="-1") $values['parentId']=$parentresult[0]['parentId'];

        //test to see if item is a nextaction
        $nextactiontest=query("testnextaction",$config,$values);
        if ($nextactiontest!="-1") {
            if ($nextactiontest[0]['nextaction']==$values['completedNa']) $isna="true";
            }
        else $isna="false";

    //test to see if action is repeating
        $testrow = query("testitemrepeat",$config,$values);
        //if repeating, copy result row to new row (new action) with updated due date
        if ($testrow[0]['repeat']!=0) {
            $nextdue=strtotime("+".$testrow[0]['repeat']."day");
            $values['nextduedate']=gmdate("Y-m-d", $nextdue);

            //retrieve item details
            $copyresult = query("selectitem",$config,$values,$options,$sort);
            $values['contextId']=$copyresult[0]['contextId'];
            $values['categoryId']=$copyresult[0]['categoryId'];
            $values['timeframeId']=$copyresult[0]['timeframeId'];
            $values['type']=$copyresult[0]['type'];
            $values['title']=$copyresult[0]['title'];
            $values['description']=$copyresult[0]['description'];
            $values['desiredOutcome']=$copyresult[0]['desiredOutcome'];
            $values['categoryId']=$copyresult[0]['categoryId'];
            $values['isSomeday']=$copyresult[0]['isSomeday'];
            $values['repeat']=$copyresult[0]['repeat'];
            $values['suppress']=$copyresult[0]['suppress'];
            $values['suppressUntil']=$copyresult[0]['suppressUntil'];

            //copy data to projects tables with new id
            $result=query("newitem",$config,$values);
            $values['newitemId'] = $GLOBALS['lastinsertid'];
            $values['deadline']=$values['nextduedate'];
            $result=query("newitemattributes",$config,$values);
            $result=query("newitemstatus",$config,$values);

            //copy parent information with new id
            if ($values['parentId']>0) $result=query("newparent",$config,$values);


            //update nextactions list with new itemId if nextaction (may want to set user option for this later)

            if ($isna=="true") $result = query("copynextaction",$config,$values);
            }

        //in either case, set original row completed
        $result = query("completeitem",$config,$values);

        //remove original row from nextActions list
        if ($isna=="true") $result = query("deletenextaction",$config,$values);
        }
    }

// Check on user radio button reset of next action 
if (($values['isNext']>0)){
    $values['itemId'] = $values['isNext'];
    $result = query("updatenextaction",$config,$values);
    }

if ($values['referrer']=="i") {

//very kludgy-- needs to be in session variables!!!

	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listItems.php?type='.$filter['type'].
        '&contextId='.$filter['contextId'].
        '&timeId='.$filter['timeId'].
        '&categoryId='.$filter['categoryId'].
        '&tickler='.$filter['tickler'].
        '&someday='.$filter['someday'].
        '&completed='.$filter['completed'].
        '&nextonly='.$filter['nextonly'].
        '&repeatingonly='.$filter['repeatingonly'].
        '&dueonly='.$filter['dueonly'].
        '&notcategory='.$filter['notcategory'].
        '&notspacecontext='.$filter['notspacecontext'].
        '&nottimecontext='.$filter['nottimecontext'].
        '">';
	}

elseif ($values['referrer']=="p") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=itemReport.php?itemId='.$values['parentId'].'">';
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
