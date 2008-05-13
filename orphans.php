<?php
include_once 'header.inc.php';
$maintable = getOrphans();
$cnt=($maintable)?count($maintable):0;

$dispArray=array();
$thisrow=0;
$dispArray=array(
    'type'=>'Type'
    ,'title'=>'Name'
    ,'description'=>'Description'
    );
$show=array();
foreach ($dispArray as $key=>$val)
    $show[$key]=true;
if ($_SESSION['debug']['debug']) echo '<pre>Orphans:',print_r($maintable,true),'</pre>';
echo "<h2>$cnt Orphaned Item",($cnt===1)?'':'s',"</h2>";
if ($cnt) {
    $trimlength=$_SESSION['config']['trimLength'];
?>  <table class="datatable sortable" id="typetable" summary='table of orphans'>
        <?php require 'displayItems.inc.php'; ?>
    </table>
<?php } else { ?>
    <p>Congratulations: you have no orphaned items.</p>
<?php } include_once 'footer.inc.php'; ?>
