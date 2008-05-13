<?php
$title='Change hierarchy levels';
require_once 'header.inc.php';
$levels=array('m','v','o','g','p');
?>
<form action='processTypes.php' method='post'>
<table class='datatable' summary='table showing which items can be children of other types'>
<thead>
<tr>
<th>Child \ PARENT</th>
<?php foreach ($levels as $ptype) { ?>
    <th><?php echo strtoupper(getTypes($ptype)); ?></th>
<?php } ?>
<th>Suppress from orphans report</th>
</tr>
</thead>
<tbody>
<?php
$ccount=0;
foreach (getTypes() as $ctype=>$ctypename) {
    if ($ctype==='i' || $ctype==='T' || $ctype==='s' || $ctype==='m') continue;
    ?>
    <tr>
        <th><?php echo $ctypename; ?></th>
        <?php
        $pcount=0;
        foreach ($levels as $ptype) { ?>
            <td><?php if ($pcount<=$ccount+1) {
                echo "<input type='checkbox' name='parentchild[]' "
                    ,"value='$ptype$ctype' "
                    ,"title='Mark if a $ctypename can be the child of a "
                    ,getTypes($ptype),"' ";
                if ( in_array($ptype ,$_SESSION['hierarchy']['parents'][$ctype] ) )
                    echo " checked='checked' ";
                if ($ptype==='p')
                    echo " disabled='disabled' ";
                echo " />";
            } else
                echo '&nbsp;';
            ?></td>
        <?php
            $pcount++;
        } ?>
        <td>
        <input type='checkbox' name='suppressAsOrphan[]' value='<?php
            echo $ctype;
        ?>' title='Mark if items of type <?php
            echo $ctypename;
        ?> should be suppressed from the list of orphans' <?php
            if (strpos($_SESSION['hierarchy']['suppressAsOrphans'],$ctype)!==false)
                echo " checked='checked' ";
            if ($ctype==='m') echo " disabled='disabled' ";
        ?> />
        </td>
    </tr>
<?php
    $ccount++;
} ?>
</tbody>
</table>
<div class='formbuttons'>
    <input type='submit' value='Apply changes' name='submit' />
    <input type='reset'  value='Reset form' name='reset'  />
    <input type='submit' value='Revert to original (pre-v0.9) level names and relationships' name='L0p8' />
    <?php foreach (array('pL','pC','pa','pr','pw','pp','LT','CT') as $PCpair) { ?>
        <input type='hidden' name='parentchild[]' value='<?php
            echo $PCpair;
        ?>' />
    <?php } ?>
</div>
</form>
<?php require_once 'footer.inc.php'; ?>
