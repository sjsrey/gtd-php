<?php
include_once('header.php');
include_once('lists.inc.php');

if ($values['id']) {
    $row = query("select{$check}list",$config,$values,$sort);
    if (!$row) {
        echo "<p class='error'>That {$check}list does not exist</p>\n";
        include_once('footer.php');
        exit();
    }
    foreach (array('title','description','categoryId') as $field)
        $values[$field]=$row[0][$field];
    $action='listedit';
} else {
    $values['title']='';
    $values['description']='';
    $action='listcreate';
}
$cashtml = categoryselectbox($config,$values,$sort);
?>
<h2><?php echo ($values['id'])?'Edit':'Create'," $check"; ?>list</h2>
<form action='processLists.php' method='post' onsubmit="return validate(this);">
	<div class='form'>
	   <div class='formrow'><span class="error" id='errorMessage'></span></div>
		<div class='formrow'>
            <input type='hidden' name='required' value='title:notnull:Title cannot be blank' />
    	    <input type='hidden' name='dateformat' value='ccyy-mm-dd' />
			<label for='title' class='left first'>List Title:</label>
			<input type='text' id='title' name='title' value='<?php echo makeclean($values['title']); ?>' />
		</div>
		<div class='formrow'>
			<label for='categoryId' class='left first'>Category:</label>
			<select name='categoryId' id='categoryId'>
                <?php echo $cashtml; ?>
			</select>
		</div>
		<div class='formrow'>
			<label for='description' class='left first'>Description:</label>
			<textarea rows="10" name="description" id="description" cols="60"><?php echo makeclean($values['description']); ?></textarea>
		</div>
	</div>
	<div class='formbuttons'>
		<input type="submit" value="<?php echo ($values['id'])?'Update':'Create'; ?>" name="submit" />
		<input type="reset" class="button" value="Reset" />
		<?php if ($values['id']) { ?>
		  <input type="checkbox" name="delete" id='delete' class='notfirst' title="ALL items will be deleted!" value="y" />
		  <label for='delete'>Delete&nbsp;List</label>
		<?php } ?>
        <input type='hidden' name='type'      value='<?php echo $type;         ?>' />
        <input type='hidden' name='id'        value='<?php echo $values['id']; ?>' />
        <input type='hidden' name='action'    value='<?php echo $action;       ?>' />
	</div>
</form>
<?php include_once('footer.php'); ?>
