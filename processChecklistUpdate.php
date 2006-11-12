<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values = array();
$values['date']=$_POST['date'];
$values['checklistItemId'] = (int) $_POST['checklistItemId'];
$values['checklistId'] = (int) $_GET['checklistId'];
$values['checkedClis'] = $_POST['checkedClis'];
$values['clear'] = $_POST['clear']{0};

//clear checklist before setting checked items; this is to allow unchecking of items
$result = query("clearchecklist",$config,$values);

if ((isset($values['checkedClis']))&&($values['clear']!="y")) {
	foreach ($values['checkedClis'] as $values['Cli']) {
            $result= query("checkchecklistitem",$config,$values);
            }
	}

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=checklistReport.php?checklistId='.$values['checklistId'].'">';

include_once('footer.php');
?>

