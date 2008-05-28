<?php
require_once 'editCat.inc.php';
require_once 'header.inc.php';
if ($_SESSION['debug']['debug']) echo "<pre>catlist:",print_r($catlist,true),'</pre>';
?>
<?php if ($thiscat || !$id) {
?><form action="processCat.php" method="post" onsubmit="return GTD.validate(this);">
<div class='formrow'><span class="error" id='errorMessage'></span></div>
<div>
<input type='hidden' name='field' value='<?php echo $field; ?>' />
<?php if ($thiscat['id']!==false) { ?>
    <input type='hidden' name='id' value=<?php echo "'{$thiscat['id']}'"; ?> />
<?php } ?>
</div>
<table class='datatable sortable' id='list' summary='<?php echo $field; ?> table'>
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <?php if ($showTypes) { ?>
                <th><label>Type:</label></th>
            <?php
            }
            if ($canDelete) { ?>
                <th>Delete?</th>
            <?php } ?>
        </tr>
    <?php
    if ($thiscat['id']!==false) { ?>
        <tr>
            <td><input type="text" name="name" value="<?php echo $thiscat['name']; ?>" />
                <input type='hidden' name='next' value="<?php echo $nextcat; ?>" />
                <input type='hidden' name='required' value='name:notnull:Name cannot be blank' />
                <input type='hidden' name='dateformat' value='ccyy-mm-dd' />
            </td>
            <td><textarea rows="2" cols="50" name="description"><?php echo $thiscat['description']; ?></textarea></td>
            <?php
            if ($showTypes) {
                echo "<td>\n";
                $cls='first';
                $alltypes=getTypes();
                foreach (array('v','o','g','p','a') as $key) {
                    $val=$alltypes[$key];
                    echo "<input type='radio' name='type' id='$val' value='$key'";
                    if ($thiscat['type']===$key) echo " checked='checked'";
                    echo " class='$cls' /><label for='$val' class='$cls'>$val</label> ";
                    $cls='notfirst';
                }
                echo "</td>\n";
            }
            if ($canDelete) echo "<td><input type='checkbox' name='delete' value='y' /></td>\n";
            ?>
        </tr>
        <tr>
            <td><input type="submit" class="button" value="Update" name="submit" /></td>
            <td><input type="reset" class="button" value="Reset" /></td>
            <?php if ($showTypes) { ?><td>&nbsp;</td><?php } ?>
            <?php if ($canDelete) { ?><td>and replace with</td><?php } ?>
        </tr>
    <?php } ?>
    </thead>
    <tbody>
    <?php foreach ($catlist as $row) { ?>
        <tr>
            <td><a href=<?php
                echo "'{$_SERVER['PHP_SELF']}?field=$field&amp;id={$row['id']}' title='Edit {$row['name']} {$field}'>{$row['name']}";
            ?></a></td>
            <td><?php echo $row['description']; ?></td>
            <?php if ($showTypes) { ?><td><?php echo getTypes($row['type']); ?></td><?php } ?>
            <?php if ($canDelete) { ?>
                <td><?php if (!$showTypes || $row['type']===$thiscat['type']) { ?>
                    <input type='radio' name='replacewith' value='<?php echo $row['id']; ?>'  />
                    <?php } else echo '&nbsp;' ?>
                </td>
            <?php } ?>
        </tr>
    <?php } ?>
    </tbody>
    <tbody>
        <?php if ($canDelete) { ?>
            <tr>
                <td>None</td>
                <td>&nbsp;</td>
                <?php if ($showTypes) { ?><td>&nbsp;</td><?php } ?>
                <td><input type="radio" name="replacewith" value="0" checked="checked" /></td>
            </tr>
        <?php } ?>
        <tr>
            <td><a href='editCat.php?field=<?php echo $field; ?>&amp;id=0'>Create new <?php echo $field; ?></a></td>
            <td>&nbsp;</td>
            <?php if ($showTypes) { ?><td>&nbsp;</td><?php } ?>
            <?php if ($canDelete) echo '<td>&nbsp;</td>'; ?>
        </tr>
    </tbody>
</table>
</form><?php
}
include_once 'footer.inc.php'; ?>
