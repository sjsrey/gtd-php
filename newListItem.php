<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values = array();
$values['listId'] = (int) $_GET['listId'];

//SQL CODE AREA
$lshtml = listselectbox($config,$values,$options,$sort);

//PAGE DISPLAY AREA
?>
<h2>New List Item</h2>
<form action="processListItem.php" method="post">
<table>
<tr>
    <td>List</td>
    <td><select name="listId">
        <?php echo $lshtml; ?>
		</select>
    </td>
</tr>
</table>

<table>
    <tr>
        <td>Item</td>
        <td><input type="text" name="item" value="<?php echo htmlspecialchars(stripslashes($values['item'])); ?>"/></td>
    </tr>
    <tr>
        <td>Notes</td>
        <td>
            <textarea cols="60" rows="3" name="notes" wrap="virtual"><?php echo htmlspecialchars(stripslashes($values['notes'])); ?></textarea>
        </td>
    </tr>
</table>

<br />
<input type="submit" class="button" value="Add List Item" name="submit" />
<input type="reset" class="button" value="Reset" />
</form>
<?php
include_once('footer.php');
?>
