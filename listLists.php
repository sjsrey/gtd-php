<?php
include_once('header.php');
include_once('lists.inc.php');

$cashtml=categoryselectbox($config,$values,$sort);
$values['filterquery']="";
if ($values['categoryId']) $values['filterquery']= " WHERE ".sqlparts("listcategoryfilter",$config,$values);
$result = query("get{$check}lists",$config,$values,$sort);
$createURL="editLists.php?$urlSuffix"
            .(($values['categoryId']) ? "&amp;categoryId={$values['categoryId']}" : '');
?>
<h2><a href="<?php echo $createURL; ?>" title="Add new list" ><?php echo $check; ?>lists</a></h2>
<form action="" method="post">
    <div id="filter">
        <label>Category:</label>
        <select name="categoryId" title="Filter lists by category">
            <?php echo $cashtml; ?>
        </select>
        <input type="submit" class="button" value="Filter" name="submit" title="Filter list by category" />
        <input type='hidden' name='type' value='<?php echo $type; ?>' />
    </div>
</form>

<?php if ($result) { ?>
    <p>Select list for report.</p>
    <table class="datatable sortable" id="categorytable" summary="table of categories">
        <thead><tr>
            <td>Category</td>
            <td>Title</td>
            <td>Description</td>
        </tr></thead>
        <tbody><?php  foreach ($result as $row) { ?>
            <tr>
                <td><?php
                    echo makeclean($row['category']);
                ?></td>
                <td><a href="reportLists.php?id=<?php echo $row['id'],'&amp;',$urlSuffix; ?>"><?php
                    echo makeclean($row['title']);
                ?></a></td>
                <td><?php
                     echo trimTaggedString($row['description'],$config['trimLength']);
                ?></td>
            </tr><?php } ?>
        </tbody>
    </table>
<?php } 
else {
    $message="You have not defined any lists yet.";
    $prompt="Would you like to create a new list?";
    nothingFound($message,$prompt,$createURL);
}

include_once('footer.php');
?>
