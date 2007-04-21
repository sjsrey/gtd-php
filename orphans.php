<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values=array();

$result = query("getorphaneditems",$config,$values,$options,$sort);

$tablehtml="";

foreach ($result as $row) {
    switch ($row['type']) {
        case "v" : $typename="vision"; break;
        case "g" : $typename="goal"; break;
        case "o" : $typename="role"; break;
        case "p" : $typename="project"; break;
        case "a" : $typename="action"; break;
        case "w" : $typename="waiting"; break;
        case "r" : $typename="references"; break;
        }
    
                                $tablehtml .= " <tr>\n";
                                $tablehtml .= '         <td><a href = "listItems.php?type='.$row['type'].'"title="List '.$typename.'">'.$typename."</a></td>\n";
                                $tablehtml .= '            <td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.htmlspecialchars(stripslashes($row['title'])).'</td>';
                                $tablehtml .= '         <td>'.nl2br(trimTaggedString($row['description'],$config['trimLength']))."</td>\n";
                                $tablehtml .= " </tr>\n";
    }

//PAGE DISPLAY CODE
        echo "<h2>Orphaned Items</h2>\n";


if ($tablehtml!="") {
        echo '<table class="datatable sortable" id="typetable">'."\n";
        echo "  <thead><tr>\n";
        echo "          <td>Type</td>\n";
        echo "          <td>Title</td>\n";
        echo "          <td>Description</td>\n";
        echo "  </tr></thead>\n";
        echo $tablehtml;
        echo "</table>\n";
} else {
        $message="Nothing was found.";
        nothingFound($message);
}

include_once('footer.php');
?>