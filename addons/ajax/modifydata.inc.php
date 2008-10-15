<?php
global $Amaintable,$AdispArray,$typename,$AcreateItemId,$item,$AnoEntries,$Athistableid,$Afootertext;
if (empty ($Amaintable['n'])) return;

include_once 'gtdfuncs.inc.php';

$sep=$onready='';
/*  we are in itemreport, and this item (potentially) has some child types,
    so loop over each type, and add a "create item" row to each table
*/
foreach ($Amaintable['n'] as $key=>$maintable) {
    $i=count($maintable);
    $isempty=($i===0);
    
    // create an extra row, hidden, and use that as a template for new items.
    $maintable[$i]=array('doreport'=>true,'row.class'=>'sortbottom hidden creatortemplate');
    foreach ($AdispArray['n'][$key] as $field=>$dummy)
        $maintable[$i][$field]='';
    $maintable[$i]['itemId']='0';
    $maintable[$i]['title']="title";
    
    // now create the next row, for the bit that triggers the item creation
    $i++;
    $maintable[$i]=array('categoryId'=>$item['categoryId'],'doreport'=>'');
    foreach ($AdispArray['n'][$key] as $field=>$dummy)
        $maintable[$i][$field]='';
    $maintable[$i]['itemId']=$AcreateItemId['n'][$key];
    $maintable[$i]['title']="Add new {$typename[$key]}";
    $maintable[$i]['row.class']='sortbottom creator';
    $maintable[$i]['NA']=null;
    
    // remove the existing creation row in the table footer
    $footertext=$Afootertext['n'][$key];
    foreach ($footertext as $ndx=>$foot) {
        if (strstr($foot,'Add new')!==false) array_splice($footertext,$ndx);
    }
    $Afootertext['n'][$key]=$footertext;
    
    // store our changes in the array of tables that are to be output
    $Amaintable['n'][$key]=$maintable;
    
    if (!$isempty) continue; // cycle onto next iteration if there are children of this type
    
    // now do something fancy with tables that would have been empty, but for our meddling
    $link=$AnoEntries['n'][$key];
    $tableid=$Athistableid['n'][$key];
    $onready .= $sep.$tableid.':"'
                .escapeforjavascript($link)
                .'"'."\n";
    $sep=',';
}
?>
<script type='text/javascript'>
/* <![CDATA[ */
$(document).ready(function(){
    GTD.ajax.setNoChildren({
    <?php echo $onready; ?>
    });
});
/* ]]> */
</script>
