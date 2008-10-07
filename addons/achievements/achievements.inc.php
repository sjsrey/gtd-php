<?php
$title='Achievements';
include_once 'header.inc.php';
$earliestdate=date('Y-m-d',strtotime('-15 months'));
$sql="SELECT its.`type`,
         interval(datediff(curdate(),its.`dateCompleted`),7,30,90,365) AS `daysago`,
         count(*) AS `numdone`
    FROM `{$_SESSION['prefix']}itemstatus` AS `its`
    WHERE its.`dateCompleted` > '$earliestdate'
    GROUP BY `type`,`daysago`";
$result=query($sql);
if(!$result) {
    echo '<p>You have not yet completed any items</p>';
    include_once 'footer.inc.php';
    exit;
}
//echo '<pre>',print_r($result,true),'</pre>';

$lastype='';
$tabrates=$tabvals=array();
$intervals=array('in last week','in last month','in last 3 months','in last 12 months');

$factors=array(7,30,90,365,0);
foreach ($result as $line) {
    if ($line['type']!==$lastype) {
        $lastype=$line['type'];
        $tabvals[$lastype]=array();
    }
    $tabvals[$lastype][$line['daysago']]=$line['numdone'];
}
// calculate equivalent weekly completion rates for each type
foreach ($tabvals as $type=>$line) {
    $runtot=0;
    for ($i=0;$i<5;$i++) {
        if (!empty($line[$i])) $runtot+=$line[$i];
        if ($factors[$i]) {
            $tabrates[$type][$i]=round(7*$runtot/$factors[$i],1);
            $suffix=" <em>({$tabrates[$type][$i]})</em>";
        } else {
            $suffix='';
        }
        $tabvals[$type][$i]= (empty($line[$i]) && $factors[$i]) ? '&nbsp;' : "<strong>$runtot</strong> $suffix";
    }
}

// if we've got graphing available, plot some graphs
$jpgraph=$addon[_GTD_ON_MENU.'-*']['jpgraphdir'].'jpgraph.php';
$cangraph=(   is_callable('imagecreatetruecolor')
           && @include_once $jpgraph);
if ($cangraph) {
    $sourcedata=array();
    $sql="SELECT its.`dateCompleted`,
            truncate(datediff(curdate(),its.`dateCompleted`)/7,0) AS `weeksago`,
            count(*) AS `numdone`
        FROM `{$_SESSION['prefix']}itemstatus` AS `its`
        WHERE its.`dateCompleted` > '$earliestdate'
            AND its.`type`='a'
        GROUP BY `weeksago` ORDER BY `dateCompleted` ASC";
    
    $result=query($sql);
    //echo '<pre>',print_r($result,true),'</pre>';
    if ($result) {
        $dates=$doneweeks=array();
        foreach ($result as $line) {
            $dates[]=strtotime($line['dateCompleted']);
            $doneweeks[]=$line['numdone'];
        }
        $sourcedata['xbar']=$dates;
        $sourcedata['ybar']=$doneweeks;
        $sourcedata['bartitle']='Per week';
    }
    
    // now add lines for averages
    $j=0;
    $today=time();
    $day=24*60*60;
    
    for ($i=4;$i>=0;$i--) if (isset($tabrates['a'][$i])) {
        $sourcedata["xline$j"]=array($today-$factors[$i]*$day,$today);
        $sourcedata["yline$j"]=array($tabrates['a'][$i],$tabrates['a'][$i]);
        $sourcedata["title$j"]="Ave. {$intervals[$i]}";
        $j++;
    }

    $_SESSION["addons-{$addon['id']}"]['graph']=$sourcedata;
}
/* ================================================
    HTML below
*/
?>
<h2>Number and rate of completed items</h2>
<table summary='rates of completed items'>
<thead><tr>
    <th>completed <em>(ave. per week)</em></th>
    <?php foreach ($intervals as $i) echo "<th>$i</th>"; ?>
    <th>all</th>
</tr></thead>
<tfoot><tr><td colspan='6'>Empty cells represent time intervals with no (extra) completed items</td></tr></tfoot>
<tbody>
<?php foreach ($tabvals as $type=>$line) { ?>
    <tr>
        <th><a href='listItems.php?completed=true&amp;liveparents=*&amp;type=<?php echo $type; ?>'><?php echo getTypes($type); ?></a></th>
        <?php for ($i=0;$i<5;$i++) { ?>
            <td><?php echo $line[$i]; ?></td>
        <?php } ?>
    </tr>
    <?php } ?>
</tbody>
</table>
<?php if ($cangraph) { ?>
    <h2>Number of completed actions per week</h2>
    <div>
    <img src='<?php echo htmlspecialchars($addon['urlprefix'],ENT_QUOTES); ?>graph.php' alt='chart of rate of completed actions by week' />
    </div>
<?php
}
include_once 'footer.inc.php';
?>
