<thead>
    <tr>
    <?php foreach ($dispArray as $key=>$val)
        echo '<th class="col-',$key
            ,($show[$key])?'':' hidden'
            ,'">',$val,"</th>\n";
    ?>
    </tr>
</thead>
<?php if (!empty($tfoot)) echo $tfoot; ?>
<tbody>
<?php
$showclass=array();
foreach ($dispArray as $key=>$val)
    $showclass[$key]=($show[$key])?'':' hidden';
foreach ($maintable as $row) {
    echo '<tr'
        ,(!empty($row['row.class']))?" class='{$row['row.class']}' ":''
        ,">\n";
    $idval="<input type='hidden' name='id' value='{$row['itemId']}' />";
    foreach ($dispArray as $key=>$val) {
        echo '<td class="col-',$key
            ,(empty($row[$key.'.class']))?'':' '.$row[$key.'.class']
            ,$showclass[$key],'"'
            ,(empty($row[$key.'.title']))?'':(' title="'.$row[$key.'.title'].'"')
            ,'>',$idval;
        $idval='';
        switch ($key) {
            case 'assignType':
                echo "<a href='assignType.php?itemId={$row['itemId']}";
                if (!empty($afterTypeChange))
                    echo "&amp;referrer=$afterTypeChange";
                elseif (!empty($referrer))
                    echo "&amp;referrer=$referrer";
                echo "'>Process</a>";
                break;
            case 'category':
                if (!empty($row[$key.'Id']))
                    echo "<a href='listItems.php?categoryId=",$row[$key.'Id'],"' title='Show all in this category'>{$row[$key]}</a>";
                else
                    echo '&nbsp;';
                break;
            case 'checkbox':
                if (!empty($row['checkboxname']))
                    echo "<input name='{$row['checkboxname']}' value='{$row['checkboxvalue']}' type='checkbox'"
                        ,(empty($row['checkboxchecked'])) ? '' : " checked='checked' "
                        ,"/>";
                break;
            case 'context':
                if ($row[$key]=='')
                    echo '&nbsp;';
                else
                    echo "<a href='reportContext.php#c",$row[$key.'Id'],"' title='Go to the ",$row[$key]," context report'>{$row[$key]}</a>";
                break;
            case 'dateCreated':    // flows through to case 'tickledate' deliberately
            case 'dateCompleted':  // flows through to case 'tickledate' deliberately
            case 'tickledate':
                if ($row[$key]) echo date($_SESSION['config']['datemask'],$row[$key]);
                break;
            case 'desiredOutcome': // flows through to case 'description' deliberately
            case 'shortdesc':      // flows through to case 'description' deliberately
            case 'shortoutcome':   // flows through to case 'description' deliberately
            case 'description':
                echo trimTaggedString($row[$key],$trimlength);
                break;
            case 'flags':
                if ($row[$key]==='')
                    echo '&nbsp;';
                else
                    echo "<a class='noNextAction' title='"
                        ,($row[$key]==='noNA')?
                            "No next action - click to assign one' href='itemReport.php?itemId="
                            :("No children - click to create one' href='item.php?type=".$row['childtype'].'&amp;parentId=')
                        ,$row['itemId'],"'>!"
                        ,($row[$key]==='noChild')?'!':'&nbsp;'
                        ,"</a>";
                break;
            case 'lastModified':
                if ($row[$key]) echo date($_SESSION['config']['datemask'].' H:i:s',$row[$key]);
                break;                
            case 'NA':
                if ($row[$key]!==null)
                    echo "<input name='isNAs[]' value='{$row['itemId']}'"
                        ,"type='",(empty($dispArray[$key.'.type']))?'checkbox':'radio',"'"
                        ,($row[$key])?" checked='checked' ":''
                        ,' />';
                break;
            case 'parent':
                if (empty($row[$key.'Id']))
                    echo '&nbsp;';
                else {
                    $out='';
                    $brk='';
                    $pids=explode(',',$row['parentId']);
                    $pnames=explode($_SESSION['config']['separator'],$row['ptitle']);
                    foreach ($pids as $pkey=>$pid) {
                        echo "$brk<a href='itemReport.php?itemId=$pid' title='View report'>"
                            ,makeclean($pnames[$pkey])
                            ,"</a> ";
                        $brk="<br />\n";
                    }
                }
                break;
            // for case:shortdesc and case:shortoutcome, see case:description, above
            case 'spatialcontext':
                if ($row[$key]=='')
                    echo '&nbsp;';
                else
                    echo "<a href='listItems.php?contextId=",$row[$key.'Id'],"' title='List all items in this spatial context'>{$row[$key]}</a>";
                break;
            case 'tags':
                if (empty($row[$key])) break;
                $tags=explode(',',$row[$key]);
                $sep='';
                foreach ($tags as $tag) {
                  echo "$sep<a href='listItems.php?tags=$tag'>$tag</a>";
                  $sep=', ';
                }
                break;
            case 'timeframe':
                if (!empty($row[$key.'Id']))
                    echo "<a href='reportContext.php#c{$row['contextId']}t{$row['timeframeId']}' title='Go to the context report'>{$row[$key]}</a>";
                else
                    echo '&nbsp;';
                break;
            case 'title':
                $cleaned=makeclean($row[$key]);
                if ($row['itemId']===null || (isset($row['type']) && $row['type']==='i')) {
                    echo $cleaned;
                } else {
                    if ($row['itemId'][0]!=='0')
                        echo "<a href='itemReport.php?itemId={$row['itemId']}'>"
                            ,"<img src='themes/{$_SESSION['theme']}/report.gif' class='noprint' alt='Report' title='View Report' /></a>";
                    echo "<a href='item.php?itemId={$row['itemId']}'>"
                    ,"<img src='themes/{$_SESSION['theme']}/edit.gif' class='noprint' alt='Edit ' title='Edit' /></a>"
                    ,"<a "
                    ,(empty($row['NA']))?'':"class='nextactionlink'"
                    ," title='"
                    ,(empty($row['doreport']))?"Edit' href='item":"View Report' href='itemReport"
                    ,".php?itemId={$row['itemId']}'>$cleaned</a>";
                }
                break;
            case 'type':
                if (empty($row[$key]))
                    echo "<a href='assignType.php?itemId={$row['itemId']}'>Set type</a>";
                elseif (isset($row['isSomeday']) && $row['isSomeday']==='y')
                    echo 'Someday';
                else
                    echo getTypes($row[$key]);
                break;
            default:
                echo $row[$key];
                break;
        }
        echo "</td>\n";
    }
    echo "</tr>\n";
} ?>
</tbody>
