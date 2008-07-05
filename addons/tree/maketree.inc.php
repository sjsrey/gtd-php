<?php
/*
TODO:  Send bulk of data by ajax, in batches.
Build basic tree in html and send, but with only IDs in ULs,
and titles for the *top-level* items only.
Have hidden ULs for children, just containing itemIds,
and with cursor icon=waiting, click disabled.
As titles and types arrive by AJAX, make ULs available for viewing.
Batch stuff up with ob_flush;flush;

TODO - probably need to change server timeout for big trees! - add register_shutdown_function to catch time-outs
TODO - maybe include links to parents current node too, when doing a rooted tree. Allow user to climb up tree.
TODO - if root node is completed, show completed items by default
*/
//------------------------------------------------------------------------------
function show1($item) {  // recursive function to list an item, and to kick off listing of its children
    global $items,$map,$baditems,$haveshown,$addon;
    $values=$items[$item];
    if ($values['dateCompleted']) {
        $liclass=' donehid';
        $spanclass=" class='treedone'";
    } else {
        $liclass='';
        $spanclass='';
    }
    if (array_key_exists($item,$baditems)) {
        $liclass=' badloop';
        if ($baditems[$item])
            $baditems[$item]--; // count down number of times we are allowed to display this item's children
        else
            unset($map[$item]); // prevent any further display of its children
    }
    $haveshown[]=$item;
    $prefix="<li class='tree{$values['type']}$liclass";
    $mid1="'>\n<img src='{$addon['dir']}cog.png' class='noprint' alt='Menu' title='Menu of actions' />
            <input type='hidden' name='id' value='{$values['itemId']}' />" ;
    $mid2="<span$spanclass>".makeclean($values['title'])."</span>";
    $suffix="</li>\n";
    if (array_key_exists($item,$map)) {
        echo "$prefix treeexpand$mid1$mid2<ul>\n";
        foreach ($map[$item] as $child) show1($child);
        echo "</ul>\n",$suffix;
    } else {
        echo $prefix,$mid1,$mid2,$suffix;
    }
}
//------------------------------------------------------------------------------
function showtree($id) {
    echo "<ul class='clickabletree'>\n";
    show1($id);
    echo "</ul>\n";
}
//------------------------------------------------------------------------------
include_once 'headerDB.inc.php';
// get an id from GET or POST argument, if possible
$idlist= (array_key_exists('itemId',$_REQUEST)) ? array( (int) $_REQUEST['itemId']) : array();

// we'll need the table prefix for queries, so store it once now, for ease of access when constructing queries
$prefix=$_SESSION['prefix'];

$showingall=empty($idlist);
if ($showingall) {
    /* no itemId given as argument, so get a list of all items without parents,
        and use them as seeds for the list
    */
    $q="SELECT `itemId` FROM `{$prefix}itemstatus` WHERE `itemId` NOT IN
			(SELECT `itemId` FROM `{$prefix}lookup`) ORDER BY `type` ASC";
    $result=query($q);
    if ($result) foreach($result as $line) $idlist[]=$line['itemId'];
}
/* get the full list of parent-child relationships from the database,
    and sort by listed all incomplete items first, then completed ones.
    Within each of those two sections, group by item type.
*/
$q="SELECT lu.* FROM `{$prefix}lookup` AS lu
    JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
    ORDER BY ISNULL(`dateCompleted`) DESC, `type`";
$parents=query($q);
if (!$parents) exit('No data in lookup table');

/*
    $map      = parent child relationships, in the form $map[$parent]=>$child
    $ids      = all itemIds in this tree
    $badloops = a list of all items that are in loops, i.e. items that are ancestors of themselves
*/
$ids=$values=$items=$map=$haveshown=array();
$seed=($showingall) ? null : $idlist;
$badloops=scanforcircularandmap($parents,$map,$ids,$seed);

// allow each bad item (i.e. item is an ancestor of itself)
// to show its children exactly once, to prevent looping while displaying the tree
$baditems=array();
foreach($badloops as $id) $baditems[$id]=1; 
    
if ($showingall) {
    // if we are showing the entire tree, then we want the select query to retrieve all items
    $values['filterquery']='';
} else {
    // we are showing a tree rooted on a particular node,
    //    so we only want to retrieve that item and its descendants
    // TODO - include parents of current item too - is it quicker to get these in PHP or SQL?

    // Build a query filter to retrieve only the items of interest
    $values['filterquery']='WHERE '.sqlparts(
        'iteminlist'
        ,array('itemId'=>implode(',',$ids) )
        );
}
// retrieve item info about all the items we are interested in
$q="SELECT i.*,ia.*,its.*
		FROM `{$prefix}items`      AS i
		JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
		LEFT OUTER JOIN `{$prefix}itemattributes` AS ia  USING (`itemId`)
		{$values['filterquery']}";
$result=query($q);

$typenames=getTypes();
$gottypes=array();

foreach ($typenames as $type=>$typename) $gottypes[$type]=false;
if ($result) foreach ($result as $line) {
    $items[$line['itemId']]=$line; // store the item info in an associative array, using the itemId as the key
    $gottypes[$line['type']]=true; //record which item types are in use - we will use this to determine which radio buttons and checkboxes to show
}

$title='Tree hierarchy';
if ($showingall) $title.=' of all items';
/* ---------------------------------------------------------------------------
    finished processing
*/
include_once 'headerHtml.inc.php';
echo "<link rel='stylesheet' href='{$addon['dir']}tree.css' type='text/css' />";
gtd_handleEvent(_GTD_ON_DATA,$pagename);
?>

<script type='text/javascript' src='<?php echo $addon['dir']; ?>tree.js'></script>
<?php include_once 'header.inc.php'; ?>
<form action='' method='get' onsubmit='return false;'>
<div class='form formrow'>
<label class='left first' for='showdone'>Show completed items</label>
<input type='checkbox' id='showdone' onclick='return GTD.tree.showdone(this);' />
Show all items at and above:
<?php
foreach (array('m','v','o','g','p','a') as $type) if ($gottypes[$type])
    echo "<label class='' for='show$type'>{$typenames[$type]}s</label>\n"
        ,"<input type='radio' name='showat' id='show$type' "
        ,    "onclick='return GTD.tree.show(\"$type\")' />\n";
foreach (array('L','C') as $type) if ($gottypes[$type])
    echo "<label class='' for='show$type'>Expand {$typenames[$type]}s</label>\n"
        ,"<input type='checkbox' checked='checked' id='show$type' "
        ,    "onclick='return GTD.tree.expand(this,\"$type\")' />\n";
?>
</div>
</form>
<?php
/* ---------------------------------------------------------------------------
    now display the tree itself
*/
?>
<ul id='legend'>
<li>Legend:</li>
<li><span class='treedone'>Completed item</span></li>
<?php
foreach ($typenames as $type=>$typename) if ($gottypes[$type])
    echo "<li class='tree$type'><span>$typename</span></li>";
?>
<?php if (count($badloops)) { ?>
<li class='badloop' title='Item trapped in a parent loop - that is, it is an ancestors of itself! This is a bad thing.  Change it by breaking one of the parent-child links in the loop'><span>parent-loop</span></li>
<?php } ?>
</ul>
<div id='trees'>
<?php
foreach ($idlist as $id) showtree($id);

// finally, check to see if any closed loops would be omitted from the hierarchy,
// and if so, force their inclusion
if ($showingall)
    foreach($badloops as $id)
        if (!in_array($id,$haveshown))
            showtree($id);
?>
</div>
<div id='treepopup'>
<a href='itemReport.php?itemId=0'>View</a>
<a href='item.php?itemId=0'>Edit</a>
<a href='#' onclick='return GTD.tree.prune()'>Prune</a>
</div>
<form class='hidden' method='post' action='processItems.php' id='pruningform'>
<div>
<input type='hidden' name='action' value='delete' />
<input type='hidden' name='multi' value='y' />
<input type='hidden' name='referrer' value='' />
</div>
</form>
<script type="text/javascript">
document.getElementById('treepopup').style.display='none';
</script>

<?php include 'footer.inc.php'; ?>
