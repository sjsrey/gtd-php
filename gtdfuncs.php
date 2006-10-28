
<?php



    function DateDropDown($size=90,$datevar="dateCompleted",$default="DropDate") {
       // $size = the number of days to display in the drop down
       // $default = string for variable name to hold date selected
       // $skip = if set then the program will skip Sundays and Saturdays
       //
       // Notes
       // sjr adapted this from an original implementation by Kenneth Brill. 
       // Original header follows:
        /**
        * This file creates a simple date picking drop down box prefilled with the
        * next 60 days dates and days of the week.
        *
        * This function is provided 'AS IS' and is therefore not liable for any damage
        * caused to a system using it.
        *
        * This code may be used by anyone aslong as the header is kept and recognition
        * given.
        *
        *
        * @author Kenneth Brill
        * @version 1.0
        * #email kbrill@multi.com
        */

       $skip=0;
       
       echo "<select name=$datevar STYLE=\"font-family: monospace;\">\n";
       echo "<option value=\"None\" $selected>$option</option>\n";
       for ($i = 0; $i <= $size; $i++) {
          $theday = mktime (0,0,0,date("m") ,date("d")+$i ,date("Y"));
          $option=date("D M j, Y",$theday);
          //$value=date("m:d:Y",$theday);
          $value=date("Y-m-d",$theday);
          $dow=date("D",$theday);
          if ($dow=="Mon") {
             echo "<option disabled>&nbsp;</option>\n";
          }
          if ($value == $default) {
             $selected="SELECTED";
          } else {
             $selected="";
          }
          if (($dow!="Sun" and $dow!="Sat") or !$skip) {
             echo '<option value="'.$value.'" '.$selected.' title="In '.$i.' days">'.$option.'</option>';
          }
       }
       echo "</select>\n";
    }

/* DEPRECIATED
    function getNextActions(){

       // Get all next actions, distinguishing between regular actions and 
        // next actions.
        // Argument:
        //      none
        // Return:
        // list: associative array with two elements
        //       n: count of next actions
        //       result: mysql result object 
        //
        $query = "SELECT nextaction  FROM nextactions";
        $result = mysql_query($query) or die ("Error in query");
        $nr=mysql_numrows($result);
        $list['n']=$nr;
        $list['result']=$result;
        return $list;
    }
*/

/* DEPRECIATED
    function getActions(){
        // Get all (completed and pending) of the next actions 
        // Argument:
        //   none
        // Return:
        // list: associative array with two elements
        //       n: count of next actions
        //       result: mysql result object 
        $query = "SELECT itemattributes.itemid FROM itemattributes WHERE type = 'a'";
        $result = mysql_query($query) or die ("Error in query");
        $nr=mysql_numrows($result);
        $list['n']=$nr;
        $list['result']=$result;
        return $list;
    }

*/

    function getCompletedNextActions(){
    $connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
    mysql_select_db($config['db']) or die ("Unable to select database!");

        // Get completed next actions 
        // Argument:
        //   none
        // Return:
        // list: associative array with two elements
        //       n: count of next actions
        //       result: mysql result object 
        $result = mysql_query("SELECT itemstatus.itemId FROM itemstatus, itemattributes 
				WHERE itemattributes.itemId=itemstatus.itemId AND itemattributes.type='a' AND dateCompleted >0");
        $nr=mysql_numrows($result);
        $list['n']=$nr;
        $list['result']=$result;
        return $list;
    }


    function getNumberOfAllNextActions(){
        // Get the number of all next actions (completed and pending)
        // Argument:
        //  none
        // Return:
        //  n: integer
        $result=getAllNextActions();
        return $result['n'];
    }

    function getNumberOfNextActions(){
        // Get the number of pending next actions 
        // Argument:
        //  none
        // Return:
        //  n: integer
        $result=getNextActions();
        return $result['n'];
    }

    function getNumberOfActions(){
        // Get the number of pending  actions 
        // Argument:
        //  none
        // Return:
        //  n: integer count of all actions (next and regular) that are 
        //  pending.
        $result=getActions();
        $ntotal=$result['n'];
        $result=getCompletedNextActions();
        $ncomp=$result['n'];
        $npending = $ntotal-$ncomp;
        return $npending;
    }

    function getProjectTitle($projectId){
    $connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
    mysql_select_db($config['db']) or die ("Unable to select database!");

        // Get the title of a project
        // Argument:
        //  projectId: int project id 
        // Return:
        //  projectTitle: string
        $query="select name from projects where projectId='$projectId'";
        $result=mysql_query($query) or die ("Error in query");
        $row=mysql_fetch_row($result);
        $projectTitle=stripslashes($row[0]);
        return $projectTitle;
    }

	function projectName($projectId){
		// Get project name
		// Argument:
		//	projectId: int
		// Return:
		//	name: string
		//	-1: if projectId not in result set not found
		$projectRes = mysql_query("select * from projects");
		$names = array();
		$flag=1;
		while($row = mysql_fetch_array($projectRes,MYSQL_ASSOC)){
			$id = $row['projectId'];
			$name = stripslashes($row['name']);
			if($id==$projectId){
				$flag = 0;
				return $name;
			}
		}
		return -1;
	}

	function nonext($projectId) {
     $connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
    mysql_select_db($config['db']) or die ("Unable to select database!");
       
		$query = "SELECT projectId, nextaction FROM nextactions WHERE projectId='$projectId'";
		$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
		if (mysql_num_rows($result)>0) $nonext="false";
		else $nonext="true";
		return $nonext;
		}

	function doquery($query){
		$result = mysql_query($query); 
       		if ($result) return $result;
		else{
			die ("Error in query: $query. MySQL Error: ".mysql_error());
		}
	}

	function nothingFound($message, $prompt, $yeslink, $nolink="index.php"){
		///Give user ability to create a new entry, or go back to the index.
		echo "<h4>$message</h4>";
		if($prompt){
			echo $prompt;
			echo "<a href=$yeslink> Yes </a><a href=$nolink>No</a>\n";
		}
	}

?>
