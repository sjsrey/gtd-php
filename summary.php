<?php

//INCLUDES
include_once('header.php');

//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE FORM AND URL VARIABLES
	$values['pId'] = (int) $_GET['projectId'];
	$values['pName'] =(string) $_GET['projectName'];

	echo "<h2>GTD Summary</h2>\n";
	echo '<h4>Today is '.date("l, F jS, Y").'. (Week '.date("W").'/52 & Day '.date("z").'/'.(365+date("L")).')</h4>'."\n";

//SQL Code
	$values['isSomeday'] = "n";
	$pres = query("projectssummary",$config,$values,$options,$sort);
	$np = count($pres)-2;

	$values['isSomeday'] = "y";
	$sm = query("projectssummary",$config,$values,$options,$sort);
	$nsm = count($sm)-2;

	$result = query("spacecontextselectbox",$config,$values,$options,$sort);
	$ncon = count($result)-2;

//    $nNextActions=getNumberOfNextActions();
    $result=query("countnextactions",$config,$values);
    echo "<div class='reportsection'>\n";
	echo "<h3>Next Actions</h3>\n";
    if($result[0]['nnextactions']==1){
                echo '<p>There is ' .$result[0]['nnextactions']. ' <a href="listItems.php?type=n">Next Action</a> pending';
            }else{
                echo '<p>There are ' .$result[0]['nnextactions']. ' <a href="listItems.php?type=n">Next Actions</a> pending';
            }
    $result=query("countactiveitems",$config,$values);
//    $nActions=getNumberOfActions();
    echo ' out of a total of ' .$result[0]['nitems']. ' <a href="listItems.php?type=a">Actions</a>.';
	echo "</p>\n";
	echo "</div>\n";

    echo "<div class='reportsection'>\n";
	echo "<h3>Contexts</h3>\n";
    if($ncon==1){
        echo '<p>There is ' .$ncon. ' <a href="listItems.php?type=n">Spatial Context</a>.<p>'."\n";
    }else{
        echo '<p>There are ' .$ncon. ' <a href="listItems.php?type=n">Spatial Contexts</a>.<p>'."\n";
    }
	echo "</div>\n";

	$i=0;
	$w1=$np/3;
        if ($pres!=-1) {
	foreach($pres as $row) {
		if($i < $w1){
			$c1[]=stripslashes($row['name']);
			$i1[]=$row['projectId'];
                        $q1[]=stripslashes($row['description']);
		}
		elseif($i< 2*$w1){
			$c2[]=stripslashes($row['name']);
			$i2[]=$row['projectId'];
                        $q2[]=stripslashes($row['description']);
		}
		else{
			$c3[]=stripslashes($row['name']);
			$i3[]=$row['projectId'];
                        $q3[]=stripslashes($row['description']);
		}
		$i+=1;
	       }
        }

//Somedays
	$i=0;
        $w2=$nsm/3;
        if ($sm!=-1) {
	foreach($sm as $row) {
                if($i < $w2){
                        $d1[]=stripslashes($row['name']);
                        $j1[]=$row['projectId'];
                        $k1[]=stripslashes($row['description']);
                }
                elseif($i< 2*$w2){
                        $d2[]=stripslashes($row['name']);
                        $j2[]=$row['projectId'];
                        $k2[]=stripslashes($row['description']);
                }
                else{
                        $d3[]=stripslashes($row['name']);
                        $j3[]=$row['projectId'];
                        $k3[]=stripslashes($row['description']);
                }
                $i+=1;
            }
        }



    echo "<div class='reportsection'>\n";
	echo "<h3>Projects</h3>\n";

    if($np==1){
        echo '<p>There is ' .$np. ' <a href="listProjects.php?type=p">Project</a>.<p>'."\n";
    }else{
        echo '<p>There are ' .$np. ' <a href="listProjects.php?type=p">Projects</a>.<p>'."\n";
    }

	$s='<table>'."\n";
	$nr = count($c1);

	for($i=0;$i<$nr;$i+=1){
		#$s.='<tr><td><a href="projectReport.php?projectId=1">Test</a></td>';
		$s.="	<tr>\n";
		$s.='		<td><a href="projectReport.php?projectId='.$i1[$i].'" title="'.$q1[$i].'">'.$c1[$i]."</a></td>\n";
		if ($i2[$i]!="" || $nr>1) $s.='		<td><a href="projectReport.php?projectId='.$i2[$i].'" title="'.$q2[$i].'">'.$c2[$i]."</a></td>\n";
		if ($i3[$i]!="" || $nr>1) $s.='		<td><a href="projectReport.php?projectId='.$i3[$i].'" title="'.$q3[$i].'">'.$c3[$i]."</a></td>\n";
		$s.="	</tr>\n";
	}

	$s.="</table>\n";

	echo $s;
	echo "</div>\n";

    echo "<div class='reportsection'>\n";
	echo "<h3>Someday/Maybe</h3>\n";

    if($nsm==1){
        echo '<p>There is ' .$nsm. ' <a href="listProjects.php?type=s">Someday/Maybe</a>.</p>'."\n";
    }else{
        echo '<p>There are ' .$nsm. ' <a href="listProjects.php?type=s">Someday/Maybes</a>.</p>'."\n";
    }


	$t='<table>'."\n";
	$nr = count($d1);

	for($i=0;$i<$nr;$i+=1){
		#$t.='<tr><td><a href="projectReport.php?projectId=1">Test</a></td>';
		$t.="	<tr>\n";
		$t.='		<td><a href="projectReport.php?projectId='.$j1[$i].'" title="'.$k1[$i].'">'.$d1[$i]."</a></td>\n";
		if ($j2[$i]!="" || $nr>1) $t.='		<td><a href="projectReport.php?projectId='.$j2[$i].'" title="'.$k2[$i].'">'.$d2[$i]."</a></td>\n";
		if ($j3[$i]!="" || $nr>1) $t.='		<td><a href="projectReport.php?projectId='.$j3[$i].'" title="'.$k3[$i].'">'.$d3[$i]."</a></td>\n";
		$t.="	</tr>\n";
	}

	$t.="</table>\n";

	echo $t;
	echo "</div>\n";

	include_once('footer.php');
?>
