<?php
    //INCLUDES
    include_once 'header.inc.php';
    $values=array('itemId' => (int) $_GET['itemId']);
    $types=getTypes();
    $result = query("selectitemshort",$values);
    $type=$result[0]['type'];
    $typename=getTypes($type);
    $title=makeclean($result[0]['title']);
    unset($types[$type]);
    unset($types['s']);
?><h2>Change the Type of <?php echo "$typename: '$title'"; ?></h2>
<div class='submitbuttons'>
    <?php foreach ($types as $key=>$val) { ?>
        <a href='processItems.php?itemId=<?php
            echo $values['itemId'];
        ?>&amp;action=changeType&amp;type=<?php
            echo $key;
            if (!empty($_REQUEST['referrer']))
                echo "&amp;referrer=",$_REQUEST['referrer'];
        ?>'><?php echo $val; ?></a>
    <?php } ?>
</div>
<?php include 'footer.inc.php'; ?>
