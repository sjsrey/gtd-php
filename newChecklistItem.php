<?php

//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values = array();
$values['checklistId'] = (int) $_GET['checklistId'];

//SQL CODE AREA
$cshtml = checklistselectbox($config,$values,$options,$sort);
?>

<h2>New Checklist Item</h2>
<form action="processChecklistItem.php" method="post">
    <div class='form'>
	<div class='formrow'>
	    <label for='checklist' class='left first'>Checklist:</label>
            <select name='checklistId' id='checklist'>
            <?php echo $cshtml; ?>
            </select>
	</div>

    <div class='formrow'>
        <label for='item' class='left first'>Item:</label>
        <input type='text' name='item' id='item'>
        </div>

    <div class='formrow'>
    <label for='notes' class='left first'>Notes:</label>
    <textarea rows="3" name="notes" id="notes" wrap="virtual"></textarea>
    </div>
</div>
<div class='formbuttons'>
    <input type='submit' value='Add List Item' name='submit'>
    </div>

<?php

include_once('footer.php');
?>
