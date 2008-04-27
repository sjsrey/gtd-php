<?php
require_once('listItems.inc.php');
include_once('headerHtml.inc.php');
?>
<script type='text/javascript'>/* <![CDATA[ */
GTD.addEvent(window,'load',function() {
    GTD.filtertoggle();
    <?php if ($quickfind) echo "GTD.focusOnForm('needle');\n"; ?>
});
/* ]]> */ </script>
<?php
include_once('header.inc.php');
gtd_handleEvent(_GTD_ON_DATA,$pagename);
?>
<div id="filter">
    <form action="listItems.php" method="post">
        <div class="formrow">
            <label for='categoryId' class='left nomargin'>Category:</label>
            <select name="categoryId" id="categoryId" title="Filter items by parent category">
            <?php echo $cashtml; ?>
            </select>
            <input type="checkbox" id="notcategory" name="notcategory" title="Exclude category from list" value="true" <?php if ($filter['notcategory']=="true") echo 'checked="checked"'?> />
            <label for='notcategory' class='notfirst'>NOT</label>
            <label for='contextId' class='left'>Context:</label>
            <select name="contextId" id="contextId" title="Filter items by context">
            <?php echo $cshtml; ?>
            </select>
            <input type="checkbox" id="notspacecontext" name="notspacecontext" title="Exclude spatial context from list" value="true" <?php if ($filter['notspacecontext']=="true") echo 'checked="checked"'?> />
            <label for='notspacecontext' class='notfirst'>NOT</label>
            <label for='timeframeId' class='left'>Time:</label>
            <select name="timeframeId" id="timeframeId" title="Filter items by time context">
            <?php echo $tshtml; ?>
            </select>
            <input type="checkbox" name="nottimecontext" id="nottimecontext" title="Exclude time context from list" value="true" <?php if ($filter['nottimecontext']=="true") echo 'checked="checked"'?> />
            <label for='nottimecontext' class='notfirst'>NOT</label>
        </div>
        <div class="formrow">
            <input type='checkbox' name='completed' id='completed' value='true' class="left first" <?php if ($filter['completed']=="true") echo 'checked="checked"'?> title="Show achievements" /><label for='completed' class='right'>Completed</label>
            <input type='checkbox' name='tickler' id='suppressed' value='true' class="notfirst" <?php if ($filter['tickler']=="true") echo 'checked="checked"'?> title="Show tickler <?php echo $typename ?>s" /><label for='suppressed' class='right'>Tickler</label>
            <input type='checkbox' name='someday' id='someday' value='true' class="notfirst" <?php if ($filter['someday']=="true") echo 'checked="checked"'?> title="Show someday/maybe <?php echo $typename ?>s" /><label for='suppressed' class='right'>Someday</label>
            <input type="checkbox" name="nextonly" id="nextonly" class="notfirst" value="true" <?php if ($filter['nextonly']=="true") echo 'checked="checked"'?> title="Show only Next Actions" /><label for='nextonly' class='right'>Next Actions</label>
            <input type="checkbox" name="dueonly" id="dueonly" class="notfirst" value="true" <?php if ($filter['dueonly']=="true") echo 'checked="checked"'?> title="Show only <?php echo $typename ?>s with a due date" /><label for='dueonly' class='right'>Due</label>
            <input type="checkbox" name="repeatingonly" id="repeatingonly" class="notfirst" value="true" <?php if ($filter['repeatingonly']=="true") echo 'checked="checked"'?> title="Show only repeating <?php echo $typename ?>s" /><label for='repeatingonly' class='right'>Repeating</label>
            <input type="checkbox" name="everything" id="everything" class="notfirst" value="true" <?php if ($filter['everything']=="true") echo 'checked="checked"'?> title="Show all <?php echo $typename ?>s, regardless of status or labels" onclick='javascript:GTD.filtertoggle("toggle");' /><label for='everything'>Show all</label>
        </div>
        <div class="formbuttons">
           <label for="liveparents" class='left first' title="Not live means either completed, a someday/maybe, or it has not yet reached its tickler date">Status of parent:</label>
           <select name="liveparents" id="liveparents" title="Not live means either completed, a someday/maybe, or it has not yet reached its tickler date">
           <?php
                foreach (array('Live'=>'true','Any'=>'*','Not live'=>'false') as $key=>$val)
                    echo "<option value='$val'"
                        ,(($filter['liveparents']==$val)?" selected='selected'":'')
                        ,">$key</option>\n";
           ?>
           </select>
           <label for='type' class='left notfirst'>Type:</label>
            <select name="type" id="type" title="Filter items by type">
            <?php
                $types=array('*'=>'(any)')+getTypes();
                foreach($types as $key=>$thistype) if ($key!=='s'){
                    echo "<option value='$key'"
                        ,($filter['type']==$key)?" selected='selected' ":''
                        ,">$thistype</option>\n";
                }
            ?>
            </select>
           <label for='needle' class='notfirst'>Find:</label>
           <input type='text' name='needle' id='needle' value='<?php echo $values['needle']; ?>' />
           <label for='tags' class='notfirst'>Tag:</label>
           <input type='text' name='tags' id='tags' value='<?php echo $values['tags']; ?>' />
           <input type="submit" id="filtersubmit" class="button" value="Filter"
                name="submit" onclick="javascript:GTD.filtertoggle('all');"
                title="Filter <?php echo $typename ?>s by selected criteria" />
        </div>
    </form>
</div>
<h2><?php echo $sectiontitle; ?></h2>
<?php if (count($maintable)) { ?>
    <form action="processItems.php" method="post">
    <table class="datatable sortable" summary="Table of actions" id="actiontable">
        <?php require('displayItems.inc.php'); ?>
    </table>
    <div class='formrow'>
        <?php
        if ($show['NA'])
            echo "<input type='hidden' name='wasNAonEntry' value='",implode(' ',$wasNAonEntry),"' />\n";
        if ($showsubmit)
            echo "<input type='submit' class='button' value='Update marked {$typename}s' name='submit' />";
        ?>
        <input type="hidden" name="referrer" value="<?php echo $referrer; ?>" />
        <input type="hidden" name="type" value="<?php echo $values['type']; ?>" />
        <input type="hidden" name="multi" value="y" />
        <input type="hidden" name="action" value="complete" />
    </div>
    </form>
    <?php
}
if (isset($endmsg['header'])) echo "<h4>{$endmsg['header']}</h4>\n";
if (isset($endmsg['link'])) echo "<a href='{$endmsg['link']}'>{$endmsg['prompt']}</a>\n";
?>
<p class='noprint'>To bookmark this filtered list, bookmark <a href='<?php echo $referrer; ?>'>this link</a></p>
<?php include_once 'footer.inc.php'; ?>
