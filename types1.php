<?php
$title='Edit item hierarchy: stage 1 - edit level names';
require_once 'header.inc.php';
?>
<form action='processTypes.php' method='post'>
<p>Please use the singular form - e.g. goal not goal<b>s</b></p>
<?php
foreach (array(
        array('m','text','Top level (default is value)'),
        array('v','text','2nd level (default is vision)'),
        array('o','text','3rd level (default is role)'),
        array('g','text','4th level, just above project (default is goal)')
        ) as $line) { ?>
    <div class='formrow'>
        <input type='text' name='label<?php
            echo $line[0];
        ?>' value='<?php
            echo getTypes($line[0]);
        ?>' />
        <label><?php echo $line[2]; ?></label>
    </div>
<?php } ?>
<p>Leave an entry empty to restore the default value</p>
<div class='formbuttons'>
    <input type='submit' value='Save and proceed to stage 2' name='types2' />
    <input type='submit' value='Save and return to summary' name='index' />
    <input type='reset'  value='Reset' name='reset'  />
</div>
</form>
<?php require_once 'footer.inc.php'; ?>
