<?php
//INCLUDES
include_once('header.php');

//GET URL AND VARIABLES
$calues['categoryId'] = (int) $_POST['categoryId'];
$values['type'] = $_POST['type']{0};
$values['referrer'] = $_POST['referrer']{0};
$values['projectId'] = (int) $_GET['projectId'];
$values['completedProj'] = $_POST['completedProj'];


//SQL CODE
if(isset($values['completedProj'])){
    $today=strtotime("now");
    $values['date']=date('Y-m-d');
    foreach ($values['completedProj'] as $values['completedPr']) {
        //test to see if project is repeating
        $testrow = query("testprojectrepeat",$config,$values,$options,$sort);
        //if repeating, copy result row to new row (new project) with updated due date
        if ($testrow[0]['repeat']!=0) {
            $nextdue=strtotime("+".$testrow[0]['repeat']."day");
            $values['nextduedate']=gmdate("Y-m-d", $nextdue);
            $values['projectId']=$values['completedPr'];

            //retrieve project details
            $copyresult = query("selectproject",$config,$values,$options,$sort);
            $values['name']=$copyresult[0]['name'];
            $values['description']=$copyresult[0]['description'];
            $values['desiredOutcome']=$copyresult[0]['desiredOutcome'];
            $values['categoryId']=$copyresult[0]['categoryId'];
            $values['isSomeday']=$copyresult[0]['isSomeday'];
            $values['deadline']=$copyresult[0]['deadline'];
            $values['repeat']=$copyresult[0]['repeat'];
            $values['suppress']=$copyresult[0]['suppress'];
            $values['suppressUntil']=$copyresult[0]['suppressUntil'];

            //copy data to projects tables with new id
            $result=query("newproject",$config,$values,$options,$sort);
            $values['newprojectId'] = $result['lastinsertid'];
            $result=query("newprojectattributes",$config,$values,$options,$sort);
            $result=query("newprojectstatus",$config,$values,$options,$sort);
            }

        //in either case, set original row completed
        $result = query("completeproject",$config,$values,$options,$sort);
        }
    }

if ($values['referrer']=="p") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="10; url=projectReport.php?projectId='.$values['projectId'].'">';
	}

elseif ($values['referrer']=="l") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="10; url=listProjects.php?pType='.$values['type'].'&categoryId='.$values['categoryId'].'">';
	}

elseif ($values['referrer']=="c") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="10; url=reportContext.php">';
	}

elseif ($values['referrer']=="t") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="10; url=tickler.php">';
	}

mysql_close($connection);
include_once('footer.php');
?>
