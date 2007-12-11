<?php
/*
    query function -  SQL abstraction layer

Return values:

    (boolean FALSE): indicates the query failed

    (integer 0):     query affected no rows, and returned no rows - e.g. an empty(SELECT)
    
    (integer >0):    indicates the number of rows affected by an INSERT, UPDATE or DELETE

    (array):         SELECT was successful, and has returned a number-indexed array of records,
                       each record is an associative array of field names=>field values.
*/
function query($querylabel,$config,$values=NULL,$sort=NULL) {

    //for developer testing only--- testing data handling
    //testing passed variables
    if ($config['debug'] & _GTD_DEBUG) {
        echo "<p class='debug'><b>Query label: ".$querylabel."</b></p>";
        echo "<pre>Config: ";
        print_r($config);
        echo "<br />Values: ";
        print_r($values);
        echo "<br />Sort: ";
        print_r($sort);
        echo "</pre>";
    }

    //include correct SQL query library as chosen in config
    switch ($config['dbtype']) {
        case "frontbase":require("frontbase.inc.php");
        break;
        case "msql":require("msql.inc.php");
        break;
        case "mysql":
			require_once("mysql.funcs.inc.php");
			if (is_array($values)) foreach ($values as $key=>$value) $values[$key] = safeIntoDB($value, $key);
		    if ($config['debug'] & _GTD_DEBUG)
		        echo '<pre>Sanitised values: ',print_r($values,true),'</pre>';
			require_once("mysql.inc.php");
	        break;
        case "mssql":require("mssql.inc.php");
        break;
        case "postgres":require("postgres.inc.php");
        break;
        case "sqlite":require("sqlite.inc.php");
        break;
        }

    //grab correct query string from query library array
    //values automatically inserted into array
    $query=getsql($config,$values,$sort,$querylabel);

    // for testing only: display fully-formed query
    if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>Query: ".$query."</p>";

    //perform query
	//parse result into multitdimensional array $result[row#][field name] = field value
    if($config['dbtype']=="mysql") {
        $reply = mysql_query($query);
        if ($reply===false) {                       // failed query - return FALSE
            $result=false;
        } elseif ($reply===true) {                  // query was not a SELECT OR SHOW, so return number of rows affected
            $result=@mysql_affected_rows();
        } else if (@mysql_num_rows($reply)===0) {   // empty SELECT/SHOW - return zero
            $result=0;
        } else {                                    // successful SELECT/SHOW - return array of results
            $result=array();
            $i = 0;
           while ($field = mysql_fetch_field($reply)) {
                /* Create an array $fields which contains all of the column names */
                $fields[$i] = $field->name;
                $i++;
                }
            $ii = 0;
            while ($mysql_result = mysql_fetch_array($reply)) {
                /*populate array with result data */
                foreach ($fields as $value) {
                    $result[$ii][$value] = $mysql_result[$value];
                    }
                $ii++;
                }
        }

        //get last autoincrement insert id--only valid for insert statements using autoincrement values; not updated when explicit value given for autoincrement field (MySQL "feature")
        $GLOBALS['lastinsertid'] = mysql_insert_id();

        $error = mysql_errno();
        if ($error) $_SESSION['message'][]=
                    "Error $error in query '$querylabel': '".mysql_error()."'";
    }

    elseif($config['dbtype']=="postgres") {
        $reply = pg_query($query) or die (($config['debug']) ? "Error in query: ". $querylabel."<br />".pg_error():"Error in query");
        echo ("Database not yet supported.");
         }

    elseif($config['dbtype']=="sqlite") {
        $reply = sqllite_query($query)  or die (($config['debug']) ? "Error in query: ". $querylabel."<br />".sqllite_error():"Error in query");
        echo ("Database not yet supported.");
        }

    elseif($config['dbtype']=="msql") {
        $reply = msql_query($query) or die (($config['debug']) ? "Error in query: ". $querylabel."<br />".msql_error():"Error in query");
        echo ("Database not yet supported.");
        }

    elseif($config['dbtype']=="mssql") {
        $reply = mssql_query($query) or die (($config['debug']) ? "Error in query: ". $querylabel."<br />".mssql_error():"Error in query");
        echo ("Database not yet supported.");
        }

    elseif($config['dbtype']=="frontbase") {
        $reply = fbsql_query($query) or die (($config['debug']) ? "Error in query: ". $querylabel."<br />".fbsql_error():"Error in query");
        echo ("Database not yet supported.");
        }

    else die("Database type not configured.  Please edit the config.php file.");

    //for developer testing only, print result array
    if ($config['debug'] & _GTD_DEBUG) {
        echo "<pre>Result: ";
        print_r($result);
        echo "</pre>";
        }
    return $result;
    }

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
