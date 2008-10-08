<?php
global $taglist,$cashtml,$cshtml,$tshtml;
?>
<div id='multicontainer' class='formrow hidden'>
    <span class='multispan'>
    <select name='multiaction' id='multiaction'>
        <option value='complete'>complete</option>
        <!--option value='tag'>tag</option-->
        <option value='delete'>delete</option>
        <option value='category'> category</option>
        <option value='space'>space</option>
        <option value='time'>time</option>
    </select>
    <span id='multiprompt'>
    </span>
    <select name='multidummy' id='multidummy'>
        <option value=''>&nbsp;</option>
    </select>
    <select name='multitag' id='multitag' class='hidden'>
        <option value=''>--</option>
        <!--option value=','>Add a new tag</option-->
        <?php
            foreach ($taglist as $tag) { ?>
                <option value='<?php echo $tag; ?>'><?php echo $tag; ?></option>
        <?php } ?>
    </select>
    <select name='multicategory' id='multicategory' class='hidden'>
        <option value=''>--</option>
        <!--option value=','>Add new</option-->
        <?php echo $cashtml; ?>
    </select>
    <select name='multispace' id='multispace' class='hidden'>
        <option value=''>--</option>
        <!--option value=','>Add new</option-->
        <?php echo $cshtml; ?>
    </select>
    <select name='multitime' id='multitime' class='hidden'>
        <option value=''>--</option>
        <!--option value=','>Add new</option-->
        <?php echo $tshtml; ?>
    </select>
    </span>
</div>
