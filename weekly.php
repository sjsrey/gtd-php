<?php
$title='The Weekly Review';
include_once 'header.inc.php';

    $values=array();

    /* ------------------------------------------------------------
        select active projects
    */
    $values['isSomeday']="n";
    $values['type']='p';
    $values['childfilterquery']  = " WHERE " .sqlparts("typefilter",$values);
    $values['childfilterquery'] .= " AND ".sqlparts("issomeday",$values);
    $values['childfilterquery'] .= " AND ".sqlparts("activeitems",$values);
    $values['childfilterquery'] .= " AND ".sqlparts("pendingitems",$values);
    $values['filterquery'] = sqlparts("checkchildren",$values)
                            .' WHERE '.sqlparts("liveparents",$values);
    $values['extravarsfilterquery'] = sqlparts("countchildren",$values);;
    $result = query("getitemsandparent",$values,array('getitemsandparent'=>'title ASC'));
    $maintable=array();
    $noOutcomes=array();
    if ($result) {
        // scan list for projects without next actions, and projects without outcomes
        $numProjects=count($result);
        foreach ($result as $row) {
            if (empty($row['numNA'])) $maintable[]=$row;
            if (empty($row['desiredOutcome'])) $noOutcomes[]=$row;
        }
    } else
        $numProjects=0;
    $numNoNext=count($maintable);

    //count how many inbox items there are
    $nInbox=array('type'=>'i');
    $nInbox['filterquery']=' WHERE '.sqlparts('typefilter',$nInbox)
                   .' AND '.sqlparts('pendingitems',$nInbox);
    $nInbox = query("counttype",$nInbox);
    $nInbox= ($nInbox) ? $nInbox[0]['cnt'] : 0;
    
    // get list of orphans
	$orph_maintable = getOrphans();
	$orphancnt=($orph_maintable)?count($orph_maintable):0;

    // get custom list of weekly tasks
    $tmpval=array('uid'=>$_SESSION['uid']);
    $tmpval['filterquery']=' AND '.sqlparts('singleoption',array('option'=>'customreview'));
    $result=query('getoptions',$tmpval);
    if ($result) $custom_review=unserialize($result[0]['value']);
?>
<table class='weeklytable' summary='Table of weekly actions'>
	<thead><tr><td>Step</td><td>Description</td></tr></thead>
	<tbody>
	<tr><td>Gather all loose papers</td><td>Empty your paper inbox into your processing pile.</td></tr>
	<tr><td>Process all notes</td><td></td></tr>
	<tr><td>Check all voice mail</td><td>Note relevant information in GTD and delete.</td></tr>
	<tr><td>Review Email Inbox </td><td>Move each item to an action, waiting, or reference folder.</td></tr>
	<tr><td>Review Email action box </td><td>Put next action reminder into system for each one, delete completed emails, or move to waiting on or reference boxes as appropriate.</td></tr>
	<tr><td>Review Email waiting on box </td><td>Put waiting on reminder into system for each one, delete completed emails or move to reference box as appropriate.</td></tr>
	<tr><td>Review previous calendar</td><td>Transfer any missed actions into system</td></tr>
	<tr><td>Purge agendas</td><td>Review this week's meeting agendas and notes.  Capture any projects, actions, waiting ons, or references.  Ensure deadlines and deliverables are defined.</td></tr>
	<tr><td>Review upcoming calendar</td><td>Capture actions about arrangements and preparations for any upcoming events</td></tr>
	<tr><td>Empty your head</td>
        <td>Put in writing any new
            <a href="item.php?type=p" title="Add project">projects</a>,
            <a href="item.php?type=a" title="Add action">actions</a>,
            <a href="item.php?type=w" title="Add waitingOn">things you are waiting for</a>,
            <a href="item.php?type=r" title="Add reference">references</a>,
            and <a href="item.php?type=p&amp;someday=true" title="Add Someday/Maybe">someday/maybes</a> that are not yet in the system.</td></tr>
    <tr><?php
        if ($nInbox) {
            ?>
            <td>Empty your <a href='listItems.php?type=i'>gtd-php inbox</a></td>
            <td><?php echo "You have <a href='listItems.php?type=i'>",$nInbox,' item',($nInbox>1)?'s':'';
                ?> in your inbox</a>; use the 'Set Type' button to convert each one into a project, action, reference or waiting-on</td>
        <?php } else { ?>
            <td>Congratulations, you have no inbox items!</td>
            <td>&nbsp;</td>
        <?php } ?>
    </tr>
<?php if ($orphancnt) { ?>
    <tr><td>Assign parents to the <a href='orphans.php'>orphans</a></td>
        <td>You have <?php echo $orphancnt?> orphan<?php if ($orphancnt > 1) echo "s"; ?> that need<?php if ($orphancnt == 1) echo "s"; ?> to be addressed.
        
        <table summary='Orphans'>
                    <tbody>
                    <?php columnedTable(3,$orph_maintable); ?>
                    </tbody>
                </table></td>
    </tr>
<?php } ?>
    <tr>
        <td>Review <a href="listItems.php?type=p">Projects list</a></td>
        <td><?php
            if ($numNoNext) {
                echo "$numNoNext of your $numProjects ";
                ?> projects have no next action. Evaluate the status for each, consider the desired outcome,
                and work out what the next action is for each.  If you cannot devise the next action for a project, then
                either it should have a Waiting-On marked as a next action, or it should be classed as a someday/maybe project.
                <table summary='projects without next actions'>
                    <tbody>
                    <?php columnedTable(3,$maintable); ?>
                    </tbody>
                </table><?php
             } else echo "All $numProjects have next actions defined.";
        ?></td>
    </tr>
    <?php if ($_SESSION['config']['reviewProjectsWithoutOutcomes'] && count($noOutcomes)) { ?>
    <tr>
        <td>Review Projects with no outcomes</td>
        <td>All of your projects should have a clear statement of the desired outcome.
            The following <?php echo count($noOutcomes); ?> projects currently have no desired outcome specified:
            For each one, ask yourself: "What would wild success look like?".  What would
            the situation look like, in order for you to be able to mark this project as
            complete?  
        <table summary='projects with no outcome defined'>
        <tbody><?php
                columnedTable(3,$noOutcomes,'item.php');
             ?></tbody>
        </table></td>
    </tr>
    <?php } ?>
	<tr><td>Review <a href="listItems.php?type=a">Actions list</a></td><td>Mark off any completed actions, review for reminders of further actions to capture.</td></tr>
	<tr><td>Review <a href="listItems.php?type=w">WaitingOn list</a></td><td>Mark off any items which have now happened; for each such item's parent project, decide what the new next action is.</td></tr>
	<tr><td>Review <a href="listItems.php?type=L">Lists</a></td><td>Review relevant lists for actionable items or projects.</td></tr>
	<tr><td>Review <a href="listItems.php?type=C">Checklists</a></td><td>Review relevant Checklists for actionable items or projects.</td></tr>
	<tr><td>Review <a href="listItems.php?type=p&amp;someday=true">Someday/Maybe List</a></td><td>Add new fun things, move any existing items into Projects if they are ready to go</td></tr>
	<tr><td>Review support files</td><td></td></tr>
	<tr><td>Review <a href="listItems.php?type=g">Goals</a></td><td>Check off goals from this week. Define goals for upcoming week</td></tr>
	<tr><td>Brainstorm</td>
        <td>Get creative with any new <a href="item.php?type=p" title="Add project">projects</a> or
		<a href="item.php?type=p&amp;someday=true" title="Add Someday/Maybe">someday/maybes</a> that may further your values, visions, goals, or areas of responsibility.
        </td>
    </tr>
<?php
	if(isset($custom_review)) {
		foreach ($custom_review as $review_title=>$review_text) {
			echo "	<tr><td>{$review_title}</td><td>{$review_text}</td></tr>\n";
		}
	}
?>
</tbody>
</table>

<?php
    if (!empty($_SESSION['config']['show7'])) include_once 'sevenhabits.inc.php';
	include_once 'footer.inc.php';
?>
