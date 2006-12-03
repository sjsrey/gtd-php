<?php

function nothingFound($message, $prompt=NULL, $yeslink=NULL, $nolink="index.php"){
        ///Give user ability to create a new entry, or go back to the index.
        echo "<h4>$message</h4>";
        if($prompt){
                echo $prompt;
                echo "<a href=$yeslink> Yes </a><a href=$nolink>No</a>\n";
        }
}

function sqlparts($part,$config,$values)  {
    //include correct SQL parts query library as chosen in config
    switch ($config['dbtype']) {
        case "frontbase":require("frontbaseparts.inc.php");
        break;
        case "msql":require("msqlparts.inc.php");
        break;
        case "mysql":require("mysqlparts.inc.php");
        break;
        case "mssql":require("mssqlparts.inc.php");
        break;
        case "postgres":require("postgresparts.inc.php");
        break;
        case "sqlite":require("sqliteparts.inc.php");
        break;
        }
    $queryfragment = $sqlparts[$part];
    return $queryfragment;
    }

function categoryselectbox($config,$values,$options,$sort) {
    $result = query("categoryselectbox",$config,$values,$options,$sort);
    $cashtml='<option value="0">--</option>'."\n";
    if ($result!="-1") {
        foreach($result as $row) {
            $cashtml .= '   <option value="'.$row['categoryId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
            if($row['categoryId']==$values['categoryId']) $cashtml .= ' SELECTED';
            $cashtml .= '>'.htmlspecialchars(stripslashes($row['category']))."</option>\n";
            }
        }
    return $cashtml;
    }

function contextselectbox($config,$values,$options,$sort) {
    $result = query("spacecontextselectbox",$config,$values,$options,$sort);
    $cshtml='<option value="0">--</option>'."\n";
    if ($result!="-1") {
            foreach($result as $row) {
            $cshtml .= '                    <option value="'.$row['contextId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
            if($row['contextId']==$values['contextId']) $cshtml .= ' SELECTED';
            $cshtml .= '>'.htmlspecialchars(stripslashes($row['name']))."</option>\n";
            }
        }
    return $cshtml;
    }

function timecontextselectbox($config,$values,$options,$sort) {
    $result = query("timecontextselectbox",$config,$values,$options,$sort);
    $tshtml='<option value="0">--</option>'."\n";
    if ($result!="-1") {
        foreach($result as $row) {
            $tshtml .= '                    <option value="'.$row['timeframeId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
            if($row['timeframeId']==$values['timeframeId']) $tshtml .= ' SELECTED';
            $tshtml .= '>'.htmlspecialchars(stripslashes($row['timeframe']))."</option>\n";
            }
        }
    return $tshtml;
    }

function parentselectbox($config,$values,$options,$sort) {
    $result = query("parentselectbox",$config,$values,$options,$sort);
    $pshtml='<option value="0">--</option>'."\n";
    if ($result!="-1") {
        foreach($result as $row) {
            $pshtml .= '                    <option value="'.$row['itemId'].'" title="'.htmlspecialchars(stripslashes($row['description']));
            if ($row['isSomeday']=="y") $pshtml .= ' (Someday)';
            $pshtml .= '"';
            if(in_array($row['itemId'],$values['parentId'])) $pshtml .= ' SELECTED';
            $pshtml .= '>'.htmlspecialchars(stripslashes($row['title']));
            if ($row['isSomeday']=="y") $pshtml .= ' (s)';
            $pshtml .="</option>\n";
            }
        }
    return $pshtml;
    }

function checklistselectbox($config,$values,$options,$sort) {
    $result = query("checklistselectbox",$config,$values,$options,$sort);
    $cshtml='<option value="0">--</option>'."\n";
    if ($result!="-1") {
        foreach($result as $row) {
            $cshtml .= '                    <option value="'.$row['checklistId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
            if($row['checklistId']==$values['checklistId']) $cshtml .= ' SELECTED';
            $cshtml .= '>'.htmlspecialchars(stripslashes($row['title']))."</option>\n";
            }
        }
    return $cshtml;
    }

function listselectbox($config,$values,$options,$sort) {
    $result = query("listselectbox",$config,$values,$options,$sort);
    $lshtml='<option value="0">--</option>'."\n";
    if ($result!="-1") {
        foreach($result as $row) {
            $lshtml .= '                    <option value="'.$row['listId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
            if($row['listId']==$values['listId']) $lshtml .= ' SELECTED';
            $lshtml .= '>'.htmlspecialchars(stripslashes($row['title']))."</option>\n";
            }
        }
    return $lshtml;
    }

?>