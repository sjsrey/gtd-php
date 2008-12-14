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
    global $items,$map,$baditems,$haveshown,$addon,$showSomedays;
    $values=$items[$item];
    if ($values['dateCompleted']) {
        $liclass=' donehid';
        $spanclass=" class='treedone'";
    } else if ($values['isSomeday']==='y') {
        $liclass= ($showSomedays) ? '' : ' somedayhid';
        $spanclass=" class='someday'";
    } else {
        $liclass='';
        $spanclass = ($values['type']==='a' && $values['nextaction']==='n')
                      ? " class='treenotNA'" : '';
    }
    if (array_key_exists($item,$baditems)) {
        $liclass=' badloop';
        if ($baditems[$item])
            $baditems[$item]--; // count down number of times we are allowed to display this item's children
        else
            unset($map[$item]); // prevent any further display of its children
    }
    $haveshown[]=$item;

    $prefix="<li class='tree{$values['type']}$liclass category{$values['categoryId']} context{$values['contextId']}";

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
function makeRadio($direction,$type,$checked) {
    global $typenames;
    return
       "<label class='left' for='$direction$type'>{$typenames[$type]}s</label>\n"
      ."<input type='radio' name='show$direction' id='$direction$type' value='$type' "
      .( ($checked) ? " checked='checked' " : '' )
      ." />\n";
}

include_once 'headerDB.inc.php';

// get an id from GET or POST argument, if possible
$idlist= (array_key_exists('itemId',$_REQUEST)) ? array( (int) $_REQUEST['itemId']) : array();
$mainTypes=array('m','v','o','g','p','a');

// we'll need the table prefix for queries, so store it once now, for ease of access when constructing queries
$prefix=$_SESSION['prefix'];

// defaults: s is to display all types, not just a limited range
$suppressListing=$onlyTypes=false;  

$showingall=empty($idlist);
$typenames=getTypes();


if ($showingall) {
    $showSomedays=false;
    // test to see if we are only displaying a limited range of item types
    $from=(empty($_GET['showfrom'])) ? false : array_search($_GET['showfrom'],$mainTypes);
    $to = (empty($_GET['showto']))   ? false : array_search($_GET['showto']  ,$mainTypes);
    if ($from===false) {
        if ($to===false) {
            // we are initiating the tree, so only display options, don't show items
            $suppressListing=true;
        } else {
            $from=0;
        }
    } elseif ($to===false || $to<$from) {
        $to=count($mainTypes)-1;
    }
    if ($from!==false)
      $onlyTypes = array_slice($mainTypes,$from,$to-$from+1);


    if ($onlyTypes) {

        if (in_array('a',$onlyTypes)) {
            if (in_array($mainTypes[0],$onlyTypes))
                $onlyTypes=false; // got ALL types, so set $onlyTypes to indicate that
            else
                array_push($onlyTypes,'w','r','i'); // getting actions, so get all refs/waiting-ons/inbox too
        }

        if ($onlyTypes && in_array('p',$onlyTypes))
            array_push($onlyTypes,'L','C','T');

        if ($onlyTypes)
            $onlyTypes="'".implode("','",$onlyTypes)."'";

    }
    /*  no itemId given as argument, so get a list of all items without parents,
          and use them as seeds for the list
    */
    if (!$suppressListing) {
        $q="SELECT `itemId` FROM `{$prefix}itemstatus`
            WHERE `itemId` NOT IN (
              SELECT lu.`itemId` FROM `{$prefix}lookup` AS lu
                JOIN `{$prefix}itemstatus` AS pits ON (lu.`parentId`=pits.`itemId`) ".
            ( ($onlyTypes) ?
              " WHERE pits.`type` IN ($onlyTypes) ) AND `type` IN ($onlyTypes) "
              : ')' ).
            " ORDER BY `type` ASC";

        $result=query($q);
        if ($result) foreach($result as $line) $idlist[]=$line['itemId'];
    }
} else {
  $from=0;
  $to=count($mainTypes)-1;
}


if (!$suppressListing) {

    /* get the full list of parent-child relationships from the database,
        and sort by listed all incomplete items first, then completed ones.
        Within each of those two sections, group by item type.
    */
    $q="SELECT lu.* FROM `{$prefix}lookup` AS lu
        JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
        JOIN `{$prefix}itemstatus` AS pits USING (`itemId`) ".
        ( ($onlyTypes) ? " WHERE its.`type` IN ($onlyTypes) AND pits.`type` IN ($onlyTypes) " : '').
        " ORDER BY ISNULL(its.`dateCompleted`) DESC, its.`type`";

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
        //$values['filterquery']='';
        $values['filterquery']=($onlyTypes) ? " WHERE type IN ($onlyTypes) " : '';
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
    $q="SELECT i.*,its.*
    		FROM `{$prefix}items`      AS i
    		JOIN `{$prefix}itemstatus` AS its USING (`itemId`)
    		{$values['filterquery']}";
    $result=query($q);

    $gottypes=array();

    foreach ($typenames as $type=>$typename) $gottypes[$type]=false;
    if ($result) foreach ($result as $line) {
        $items[$line['itemId']]=$line; // store the item info in an associative array, using the itemId as the key
        $gottypes[$line['type']]=true; //record which item types are in use - we will use this to determine which radio buttons and checkboxes to show
    }

}
$title='Tree hierarchy';

$fromType=$toType=$toLiveType='';
foreach ($mainTypes as $type) {
    if ($type!=='a')
        $fromType.=makeRadio('from',$type,$type===$mainTypes[$from]);
    
    $toType.=makeRadio('to',$type,$type===$mainTypes[$to]);

    if (!$suppressListing && $gottypes[$type])
        $toLiveType.=makeRadio('LiveTo',$type,$type===$mainTypes[$to]);
}
$showSomedays=!$showingall && $items[$idlist[0]]['isSomeday']==='y';

/* ---------------------------------------------------------------------------
    finished processing - now produce the HTML
*/
include_once 'headerHtml.inc.php';
?>

<link rel='stylesheet' href='<?php echo $addon['dir']; ?>tree.css' type='text/css' />
<script type='text/javascript' src='<?php echo $addon['dir']; ?>tree.js'></script>
<script type="text/javascript">
$(document).ready(function treephp_ready() {
  GTD.tree.init(['<?php echo implode("','",$mainTypes); ?>']);
});
</script>


<?php
gtd_handleEvent(_GTD_ON_DATA,$pagename);
include_once 'header.inc.php';
?>



<form action='' method='get' id='filterform'>



<div class='form<?php if (!$suppressListing) echo ' hidden'; ?>' id='extendedTreeOptions'>

<div class='formrow'>
<label class='left first'>Show from</label>
<?php echo $fromType; ?>
</div>

<div class='formrow'><label class='left first'>Show to:</label>
<?php echo $toType; ?>
</div>

<div class='formbuttons'>
<input type='submit' name='filter' value='Retrieve this tree' />
<?php if (!$suppressListing) { ?>
<a href='#' onclick='return GTD.tree.toggleOptions(false);'>Cancel and return to live options</a>
<?php } ?>
</div>

</div>


<?php if (!$suppressListing) { ?>
<div class='form invisible' id='liveTreeOptions'>

<div class='formrow'><label class='left first'>Show down to:</label>
<?php echo $toLiveType; ?>
</div>

<div class='formrow'>
<label class='left first' for='showdone'>Completed items</label>
<input type='checkbox' id='showdone' onclick='return GTD.tree.showDone(this);' />

<?php if ($gottypes['a']) { ?>
<label class='left' for='shownext'>Only <b>next</b> actions</label>
<input type='checkbox' id='shownext' onclick='return GTD.tree.showNext(this);' />
<?php } ?>

<label class='left' for='categoryselect'>Category</label>
<select id='categoryselect' onchange='return GTD.tree.dofilter(this)' >
<?php echo str_replace('>--<','>(all)<',categoryselectbox(array('categoryId'=>''))); ?>
</select>

<label class='left' for='contextselect'>Context</label>
<select id='contextselect' onchange='return GTD.tree.dofilter(this)' >
<?php echo str_replace('>--<','>(all)<',contextselectbox(array('contextId'=>''))); ?>
</select>

<?php
if ($gottypes['p']) { ?>
<label class='left' for='showsomeday'>Someday</label>
<input type='checkbox' id='showsomeday' onclick='return GTD.tree.showSomeday(this);'<?php
  if ($showSomedays) echo " checked='checked' "; ?>/>
<?php }

foreach (array('L','C') as $type) if ($gottypes[$type])
    echo "<label class='left' for='show$type'>Expand {$typenames[$type]}s</label>\n"
        ,"<input type='checkbox' checked='checked' id='show$type' value='$type'"
        ,"onclick='return GTD.tree.expand(this,\"$type\")' />\n";
?>
</div>

<div class='formbuttons'>
<a href='#' onclick='return GTD.tree.toggleOptions(true);'>Show options to rebuild the tree with different types</a>
</div>

</div>
<?php } ?>


<div class='hidden'>
<?php foreach (array('addonid','url') as $field)
  echo "<input type='hidden' name='$field' value='{$_GET[$field]}' />\n";
?>
</div>

</form>



<?php if ($suppressListing) {
    // our work here is done
    include 'footer.inc.php';
    exit();
}
?>



<ul id='legend'>
<li>Legend:</li>
<li><span class='treedone'>Completed item</span></li>
<?php
foreach ($typenames as $type=>$typename) if ($gottypes[$type])
    if ($type==='a')
        echo "<li class='treea'><span>Next Actions</span></li>",
             "<li class='treea'><span class='treenotNA'>Actions</span></li>";
    else
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



<div id='treepopup' class='hidden'>
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


<?php include 'footer.inc.php'; ?>
