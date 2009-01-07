<?php
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
ob_start();

require_once 'headerDB.inc.php';

require_once 'JSONlib.inc.php';
$json = new Services_JSON();    // create a new instance of Services_JSON

$values = array();
$action=$_REQUEST['action'];
$output=array('result'=>false); // default result

switch ($action) {

    
    case 'get1': // Request was for a single item, so do query, get data
        $values['itemId']= (int) $_REQUEST['itemId'];
        $values['filterquery']=' WHERE '.sqlparts('singleitem',$values);
        $result = query("selectitem",$values);
        if ($result) $output=$result[0];
        break;

        
    case 'getTable': // get html table of next actions
        $_SESSION['config']['trimLength']=30;
        require_once 'listItems.inc.php';
        // some shenanigans to separate out the log from the table
        $log=ob_get_contents();
        ob_clean();

        $dispArray=array(
            'checkbox'=>'Done'
            ,'title'=>'Actions'
            ,'shortdesc'=>'Description'
            ,'parent'=>'parents'
        );
        require 'displayItems.inc.php';
        
        $output['table']=ob_get_contents();
        ob_clean();
        echo $log;
        break;


    case 'getrecur': // we want to know the next recurrence based on a pattern
        require_once 'gtdfuncs.inc.php';
        $values=array();
        foreach (array('deadline','tickledate','dateCompleted') as $field)
          $values[$field] = (empty($_REQUEST[$field])) ? '' : $_REQUEST[$field];
        list($values['recur'],$dummy,$vevent) = processRecurrence($values);
        $output=array('next'=>getNextRecurrence($values,$vevent));
        break;




    case 'list': // getting all items of a particular type
        $values['filterquery']='WHERE '.sqlparts("pendingitems",$values);
        $values['type']=empty($_REQUEST['type'])?'*':$_REQUEST['type'];
        if ($values['type']!=='*')
            $values['filterquery'] .= " AND ".sqlparts("typefilter",$values);
        $result= query('getitems',$values);
        
        if ($result) {
            $output=array();
            foreach ($result as $line)
                $output[$line['itemId']]=$line['title'];
        }
        break;

/*
    case 'findstring': //  searching for a particular string
      $values['type']=$_REQUEST['type'];
      $values['needle']=$_REQUEST['needle'];
      $q=($_REQUEST['haystack']==='title')?'matchtitle':'matchall';

      //do query
      $values['filterquery']=sqlparts('typefilter',$values);
      $values['filterquery'].=' AND '.sqlparts($q,$values);
      $result= query('selectfind',$values);
      if ($result) $output=$result[0];
      break;
*/
}
$output['log']=ob_get_flush();
echo $json->encode($output);
exit;
?>
