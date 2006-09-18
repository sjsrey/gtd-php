<?php

//INCLUDES
include_once('gtdfuncs.php');
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

//RETRIEVE FORM AND URL VARIABLES
	$pId = (int) $_GET['projectId'];
	$pName =(string) $_GET['projectName'];
 
	echo "<h2>GTD Summary</h2>\n";
	echo '<h4>Today is '.date("l, F jS, Y").'. (Week '.date("W").'/52 & Day '.date("z").'/'.(365+date("L")).')</h4>'."\n";
	
	//SJK altered to show only active projects	
	$query = "SELECT projects.projectId, projects.name, projects.description, projectattributes.categoryId, categories.category
                FROM projects, projectattributes, projectstatus, categories
                WHERE projectattributes.projectId=projects.projectId AND projectattributes.categoryId=categories.categoryId
                AND projectstatus.projectId=projects.projectId AND 
				(projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00') AND projectattributes.isSomeday='n'
                ORDER BY projects.name ASC";
	$result = doquery($query);
	$pres=$result;
	$np=mysql_num_rows($result);
	
	//SJK added someday/maybe
	$query = "SELECT projects.projectId, projects.name, projects.description, projectattributes.categoryId, categories.category
                FROM projects, projectattributes, projectstatus, categories
                WHERE projectattributes.projectId=projects.projectId AND projectattributes.categoryId=categories.categoryId
                AND projectstatus.projectId=projects.projectId AND 
				(projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00') AND projectattributes.isSomeday='y'
                ORDER BY projects.name ASC";
	$result = doquery($query);
	$sm=$result;
	$nsm=mysql_num_rows($result);

	$query = "Select * from context";
	$result = doquery($query);
	$ncon=mysql_num_rows($result);
	


//Currently shows all actions pending, not just nextactions

    // sjr moved nextAction queries to gtdfuncs.php to isolate date
    // wierdness
    $nNextActions=getNumberOfNextActions();
    echo "<div class='reportsection'>\n";
	echo "<h3>Next Actions</h3>\n";
    if($nNextActions==1){
                echo '<p>There is ' .$nNextActions. ' <a href="listItems.php?type=n">Next Action</a> pending';
            }else{
                echo '<p>There are ' .$nNextActions. ' <a href="listItems.php?type=n">Next Actions</a> pending';
            }
    $nActions=getNumberOfActions();
    echo ' out of a total of ' .$nActions. ' <a href="listItems.php?type=a">Actions</a>.';
	echo "</p>\n";
	echo "</div>\n";
	
    /* Do we need this anymore (sjr)?
    if($nCompleted==1){
	        echo " $nCompleted has been completed out of a total $nAllNextActions.";
        }else{
	        echo " $nCompleted have been completed out of a total $nAllNextActions.";
    }
	echo "<br /><br />";
    */
	
    echo "<div class='reportsection'>\n";
	echo "<h3>Contexts</h3>\n";
    if($ncon==1){
        echo '<p>There is ' .$ncon. ' <a href="listItems.php?type=n">Spatial Context</a>.<p>'."\n";
    }else{
        echo '<p>There are ' .$ncon. ' <a href="listItems.php?type=n">Spatial Contexts</a>.<p>'."\n";
    }
	echo "</div>\n";


	mysql_free_result($result);
	mysql_close($connection);
	$i=0;
	$w1=$np/3;
	while($row = mysql_fetch_row($pres)){
		if($i < $w1){
			$c1[]=stripslashes($row[1]);
			$i1[]=$row[0];
		}
		elseif($i< 2*$w1){
			$c2[]=stripslashes($row[1]);
			$i2[]=$row[0];
		}
		else{
			$c3[]=stripslashes($row[1]);
			$i3[]=$row[0];
		}
		$i+=1;
	}

//SJK duplicated for somedays
	$i=0;
        $w2=$nsm/3;
        while($row = mysql_fetch_row($sm)){
                if($i < $w2){
                        $d1[]=stripslashes($row[1]);
                        $j1[]=$row[0];
                }
                elseif($i< 2*$w2){
                        $d2[]=stripslashes($row[1]);
                        $j2[]=$row[0];
                }
                else{
                        $d3[]=stripslashes($row[1]);
                        $j3[]=$row[0];
                }
                $i+=1;
        }



    echo "<div class='reportsection'>\n";
	echo "<h3>Projects</h3>\n";

    if($np==1){
        echo '<p>There is ' .$np. ' <a href="listProjects.php?type=p">Project</a>.<p>'."\n";  //SJK changed to project report
    }else{
        echo '<p>There are ' .$np. ' <a href="listProjects.php?type=p">Projects</a>.<p>'."\n";  //SJK changed to project report
    }
	
	$s='<table>'."\n";
	$nr = count($c1);

	for($i=0;$i<$nr;$i+=1){
		#$s.='<tr><td><a href="projectReport.php?projectId=1">Test</a></td>';
		$s.="	<tr>\n";
		$s.='		<td><a href="projectReport.php?projectId='.$i1[$i].'">'.$c1[$i]."</a></td>\n";
		$s.='		<td><a href="projectReport.php?projectId='.$i2[$i].'">'.$c2[$i]."</a></td>\n";
		$s.='		<td><a href="projectReport.php?projectId='.$i3[$i].'">'.$c3[$i]."</a></td>\n";
		$s.="	</tr>\n";
	}
	
	$s.="</table>\n";
	
	echo $s;
	echo "</div>\n";

//SJK duplicated for Someday/Maybes

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
		$t.='		<td><a href="projectReport.php?projectId='.$j1[$i].'">'.$d1[$i]."</a></td>\n";
		$t.='		<td><a href="projectReport.php?projectId='.$j2[$i].'">'.$d2[$i]."</a></td>\n";
		$t.='		<td><a href="projectReport.php?projectId='.$j3[$i].'">'.$d3[$i]."</a></td>\n";
		$t.="	</tr>\n";
	}

	$t.="</table>\n";

	echo $t;
	echo "</div>\n";

	include_once('footer.php');
?>
