<?php
include_once('header.php');
include_once('lists.inc.php');

$result = query("select{$check}list",$config,$values,$sort);

if ($result==1) {
    echo "<p class='error'>That {$check}list does not exist</p>\n";
    include_once('footer.php');
    exit();
}
$row=$result[0];

$values['filterquery']= " AND ".sqlparts("activelistitems",$config,$values);
$result1=query("get{$check}listitems",$config,$values,$sort);

if (!$isChecklist) {
    $values['filterquery']= " AND ".sqlparts("completedlistitems",$config,$sort);
    $result2=query("get{$check}listitems",$config,$values,$sort);
    if (!$result2) $result2=array();
} 
$createURL="editListItems.php?id={$row['id']}&amp;$urlSuffix";
?>
<h1>The <?php echo $row['title'],' ',$check; ?>list</h1>
<p><span class='editbar'>[
    <a href='editLists.php?id=<?php echo $row['id'],'&amp;',$urlSuffix; ?>'>Edit List</a>
]</span></p><?php
    if (!empty($row['category'])) echo '<p>Category: ',$row['category'],"</p>\n";
    if (!empty($row['description'])) echo '<p>',trimTaggedString($row['description']),"</p>\n";
?>
<h2><a href='<?php echo $createURL; ?>' title='add a new item'>Items</a></h2>
<?php if ($result1) { ?>
<form action='processLists.php' method='post'>
    <table class="datatable sortable" id="itemtable" summary="table of list items">
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Completed</th>
            </tr>
            <tr>
                <td><a href='<?php echo $createURL; ?>'>Add new item</a></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </thead>
        <tbody>
        <?php foreach($result1 as $row) { ?>
            <tr>
                <td><a href="editListItems.php?itemId=<?php
                    echo $row['itemId'],'&amp;',$urlSuffix;
                ?>" title="Edit"><?php echo makeclean($row['item']); ?></a></td>
                <td><?php echo trimTaggedString($row['notes']); ?></td>
                <td><input type="checkbox" name="completed[]" title="Complete" value="<?php
                    echo $row['itemId'],'"',($isChecklist && $row['checked']==='y')?" checked='checked' ":'';
                    ?> />
                </td>
            </tr><?php
        } ?>
        </tbody>
    </table>
    <div class='formbuttons'>
        <input type='submit' name='submit' value='update' />
        <?php if ($isChecklist) { ?>
            <input type='submit' name='listclear' value='Clear all checkmarks' />
        <?php } ?>
        <input type='hidden' name='id' value='<?php echo $row['id']; ?>' />
        <input type='hidden' name='action' value='listcomplete' />
        <input type='hidden' name='type' value='<?php echo $type; ?>' />
    </div>
</form>
<?php
} else {
?>
<p>There are no <?php
    echo ($isChecklist) ? 'check' : 'incomplete '
        ,"list items. <a href='$createURL'>"; ?>Create one</a></p>
<?php }
if (!$isChecklist && count($result2)) {  // it's an ordinary list, so split table into complete and incomplete
    ?>
    <h2>Completed List Items</h2>
    <table class="datatable sortable" id="donetable" summary="completed list items">
        <thead><tr>
            <th>Item</th>
            <th>Notes</th>
            <th>Completed</th>
        </tr></thead>
        <tbody>
            <?php foreach($result2 as $row) { ?>
                <tr>
                    <td><a href="editListItems.php?itemId=<?php
                        echo $row['itemId']; ?>" title="Edit"><?php
                            echo makeclean($row['item']);
                        ?></a>
                    </td>
                    <td><?php echo trimTaggedString($row['notes']); ?></td>
                    <td><?php echo $row['dateCompleted']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php }
include_once('footer.php'); ?>
