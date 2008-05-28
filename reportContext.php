<?php
$title='Contexts Summary';
include_once 'reportContext.inc.php';
include_once 'header.inc.php';
gtd_handleEvent(_GTD_ON_DATA,$pagename);
?>
<h3>Spatial Context (row), Temporal Context (column)</h3>
<table class="datatable" summary="table of contexts" id="contexttable">
    <thead><tr>
        <td>Context</td>
        <?php
        $runningtotals=array();
        foreach ($timeframeNames as $tcId => $tname) {
            $runningtotals["t$tcId"]=0;
            ?>
            <td><a href='editCat.php?field=time-context&amp;id=<?php echo $tcId; ?>'
                    title='Edit time context'><?php echo makeclean($tname); ?>
                </a>
            </td>
        <?php } ?>
        <td>Total</td>
    </tr></thead>
    <tbody><?php foreach ($contextNames as $cid => $cname) {
        $runningtotals["c$cid"]=0;
        ?>
        <tr>
	       <td><a href='editCat.php?field=context&amp;id=<?php echo $cid; ?>'
                title='Edit context'><?php echo makeclean($cname); ?></a>
           </td>
           <?php foreach ($timeframeNames as $tid => $tname) {
        		if ($count=$matrixcount[$cid][$tid]) {
			         echo "<td><a href='#c{$cid}t{$tid}'>$count</a></td>\n";
			         $runningtotals["c$cid"]+=$count;
			         $runningtotals["t$tid"]+=$count;
                } else { ?>
                    <td>0</td>
                <?php
                }
            }
            ?>
            <td><?php
                echo ($count=$runningtotals["c$cid"])
                    ?"<a href='#c$cid'>$count</a>"
                    :0;
                ?>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td>Total</td>
            <?php
            $runningtotals['grand']=0;
            foreach ($timeframeNames as $tid => $tname) {
	            $runningtotals['grand']+=$runningtotals["t$tid"];
	            ?>
        	    <td><?php echo $runningtotals["t$tid"]; ?></td>
            <?php } ?>
            <td><?php echo $runningtotals['grand']; ?></td>
        </tr>
    </tbody>
</table>
<p>To move to a particular space-time context, select the number.<br />
To edit a context select the context name.</p>
<?php
foreach ($contextNames as $cid => $cname) {
    if (!$runningtotals["c$cid"]) continue;
    echo "<div id='dc$cid'>\n"
        ,"<a id='c$cid'></a>\n"
        ,"<h2><a href='editCat.php?field=context&amp;id=$cid' "
        ,"title='Edit the ".makeClean($cname)." context'>"
        ,"Context:&nbsp;".makeClean($cname)."</a></h2>\n";
   foreach ($timeframeNames as $tid => $tname) {
        if (isset($matrixout[$cid][$tid])) {
            echo "<div class='t{$tid}'>\n"
                ,"<a id='c{$cid}t{$tid}'></a>\n"
                ,"<h3><a href='editCat.php?field=time-context&amp;id=$tid' title='".makeClean($tname)."'>"
                ,"Time Context:&nbsp;".makeClean($tname)."</a></h3>\n";
            ?>
            <form action="processItems.php" method="post">
                <table class="datatable sortable" summary="table of actions"
                    id='actiontable<?php echo "C{$cid}T{$tid}"; ?>'>
                    <?php echo $matrixout[$cid][$tid]; ?>
                </table>
                <div>
                	<input type="hidden" name="referrer" value="<?php echo basename($thisurl['path']),"#c{$cid}t{$tid}"; ?>" />
                    <input type="hidden" name="multi" value="y" />
        		    <input type="hidden" name="wasNAonEntry" value="<?php echo implode(' ',$wasNAonEntry[$cid][$tid]); ?> " />
                    <input type="hidden" name="action" value="complete" />
                    <input type="submit" class="button" value="Update Actions" name="submit" />
                </div>
            </form>
            </div>
            <?php
        }
    }
    echo '</div>';
}
include_once 'footer.inc.php'; ?>
