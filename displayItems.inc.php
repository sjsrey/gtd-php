<thead>
    <tr>
    <?php foreach ($dispArray as $key=>$val) if ($show[$key]) echo "<th class='col-$key'>$val</th>"; ?>
    </tr>
</thead>
<?php if (!empty($tfoot)) echo $tfoot; ?>
<tbody>
<?php
foreach ($maintable as $row) {
    echo '<tr'
        ,(!empty($row['row.class']))?" class='{$row['row.class']}' ":''
        ,">\n";
    foreach ($dispArray as $key=>$val) if ($show[$key]) {
        echo "<td class='col-$key"
            ,(isset($row["$key.class"]))?" ".$row["$key.class"]:''
            ,"'"
            ,(isset($row["$key.title"]))?(' title="'.$row["$key.title"].'"'):''
            ,'>';
        switch ($key) {
            case 'title':
                $cleaned=makeclean($row[$key]);
                echo "<a href='itemReport.php?itemId={$row['itemId']}'>"
                    ,"<img src='themes/{$config['theme']}/report.gif' class='noprint' alt='Report' title='View Report' /></a>"
                    ,"<a href='item.php?itemId={$row['itemId']}'>"
                    ,"<img src='themes/{$config['theme']}/edit.gif' class='noprint' alt='Edit ' title='Edit' /></a>"
                    ,"<a ",(empty($row['NA']))?'':"class='nextactionlink'"
                    ," title='"
                    ,(empty($row['doreport']))?'Edit':'View Report'
                    ,"' href='item"
                    ,(empty($row['doreport']))?'':'Report'
                    ,".php?itemId={$row['itemId']}'>$cleaned</a>";
                break;
            case 'assignType':
                echo "<a href='assignType.php?itemId={$row['itemId']}'>Set type</a>";
                break;
            case 'checkbox':
                echo "<input name='{$row['checkboxname']}' value='{$row['checkboxvalue']}' type='checkbox' />";
                break;
            case 'NA':
                echo "<input name='isNAs[]' value='{$row['itemId']}'"
                    ,"type='",(empty($dispArray[$key.'.type']))?'checkbox':'radio',"'"
                    ,($row[$key])?" checked='checked' ":''
                    ,' />';
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
            case 'category':
                if ($row[$key.'Id'])
                    echo "<a href='editCat.php?field=category&amp;id=",$row[$key.'Id'],"' title='Edit the {$row[$key]} category'>{$row[$key]}</a>";
                else
                    echo '&nbsp;';
                break;
            case 'parent':
                if (empty($row[$key.'Id']))
                    echo '&nbsp;';
                else {
                    $out='';
                    $brk='';
                    $pids=explode(',',$row['parentId']);
                    $pnames=explode($config['separator'],$row['ptitle']);
                    foreach ($pids as $pkey=>$pid) {
                        echo "$brk<a href='itemReport.php?itemId=$pid' title='View report'>"
                            ,makeclean($pnames[$pkey])
                            ,"</a> ";
                        $brk="<br />\n";
                    }
                }
                break;
            case 'context':
                if ($row[$key]=='')
                    echo '&nbsp;';
                else
                    echo "<a href='reportContext.php#c",$row[$key.'Id'],"' title='Go to the ",$row[$key]," context report'>{$row[$key]}</a>";
                break;
            case 'spatialcontext':
                if ($row[$key]=='')
                    echo '&nbsp;';
                else
                    echo "<a href='editCat.php?field=context&amp;id=",$row[$key.'Id'],"' title='Go to the ",$row[$key]," context report'>{$row[$key]}</a>";
                break;
            case 'timeframe':
                if ($row[$key.'Id'])
                    echo "<a href='editCat.php?field=time-context&amp;id=",$row[$key.'Id'],"' title='Edit the {$row[$key]} time context'>{$row[$key]}</a>";
                else
                    echo '&nbsp;';
                break;
            case 'type': // TOFIX - if type is blank, offer 'assign type' link
                if (empty($row[$key]))
                    echo "<a href='assignType.php?itemId={$row['itemId']}'>Set type</a>";
                elseif (isset($row['isSomeday']) && $row['isSomeday']==='y')
                    echo 'Someday';
                else
                    echo getTypes($row[$key]);
                break;
            case 'description': // flows through to case 'outcome' deliberately
            case 'desiredOutcome':
                echo trimTaggedString($row[$key],$config['trimLength']);
                break;
            case 'fulldesc': // flows through to case 'fulloutcome' deliberately
            case 'fulloutcome':
                echo trimTaggedString($row[$key],$config["trimLengthInReport"]);
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
