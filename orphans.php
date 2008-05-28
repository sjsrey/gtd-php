<?php
include_once 'headerDB.inc.php';
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

$title=$cnt.' Orphaned Item'.(($cnt===1)?'':'s');
include_once 'header.inc.php';

if ($_SESSION['debug']['debug']) echo '<pre>Orphans:',print_r($maintable,true),'</pre>';
if ($cnt) {
    $trimlength=$_SESSION['config']['trimLength'];
?>  <table class="datatable sortable" id="typetable" summary='table of orphans'>
        <?php require 'displayItems.inc.php'; ?>
    </table>
<?php } else { ?>
    <p>Congratulations: you have no orphaned items.</p>
<?php } include_once 'footer.inc.php'; ?>
