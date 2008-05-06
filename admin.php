<?php

/* _DRY_RUN = false | true - dry run won't change the database, but will
  mime all the actions that would be done: use _DEBUG true to see these */
define("_DRY_RUN",false);

define("_ALLOWUNINSTALL",false); // NOT YET ACTIVE

require_once 'headerDB.inc.php';
require_once 'admin.inc.php';

define("_DEBUG",true && ($config['debug'] & _GTD_DEBUG));

/*
TOFIX: scan for available installations
TOFIX: Use a javascript onsubmit for the delete verification, and fallback to POST if no javascript
TOFIX: move DELETE from install.php to here

------------------------------------------------------------
TOFIX: LOCK TABLES if possible, while doing admin.
NB: you cannot use a locked table multiple times in a single query.
Use aliases instead, in which case you must obtain a lock for each alias separately.
mysql> LOCK TABLE t WRITE, t AS t1 WRITE;
mysql> INSERT INTO t SELECT * FROM t;
ERROR 1100: Table 't' was not locked with LOCK TABLES
mysql> INSERT INTO t SELECT * FROM t AS t1;
------------------------------------------------------------

*/
$action=(isset($_REQUEST['action']))?$_REQUEST['action']:'validate';
$showInstallations=true;
$showCommands=true;
$prefix=(isset($_REQUEST['prefix']))?$_REQUEST['prefix']:$config['prefix'];
if (!checkPrefix($prefix)) $prefix='';
$availableActions=array('validate','repair','backup');
if (_ALLOWUNINSTALL) $availableActions[]='delete';

switch ($action) {
    case 'backup':
        $backup=backupData($prefix);
        break;
    case 'delete':
        if (!_ALLOWUNINSTALL) break;
        break;
    case 'none':
        break;
    case 'repair':
        $toterrs=0;
        $pre=checkErrors($prefix);
        fixData($prefix);
        $post=checkErrors($prefix);
        $repair="<h2>Results of repairs on installation with prefix '$prefix'</h2>\n";
        $repair.="<p>Repair complete.</p>";
        if ($post['totals']['orphans'])
            $repair.="<p>Now check <a href='orphans.php'>orphans</a>.</p>\n";
        $repair.="<p>Check for <a href='listItems.php?type=p'>projects</a> that have no actions, or no next actions.</p>\n";
        $repair.="<table summary='result of repairs'><thead>\n<tr><th>Before</th><th>After</th><th>&nbsp;</th></tr></thead><tbody>";
        foreach($post['totals'] as $key=>$val)
            $repair .="<tr><td>{$pre['totals'][$key]}</td><td>$val</td><th>$key</th></tr>\n";
        foreach($post['errors'] as $key=>$val) {
            $toterrs+=(int) $val;
            $preval=$pre['errors'][$key];
            $class1=($preval)?" class='warnresult' ":" class='goodresult' ";
            if ($val)
                $class2=" class='warnresult' ";
            else if ($preval)
                $class2=" class='goodresult' ";
            else {
                $class1='';
                $class2='';
            }
            $repair .= "<tr><td $class1>{$preval}</td><td $class2>$val</td><td $class2>$key</td></tr>\n";
        }
        $repair .="</tbody></table>\n";
        $action=($toterrs)?'repair':'backup';
        break;
    case 'validate':
        $result=checkErrors($prefix);
            $validate="<h2>Validation checks on installation with prefix $prefix</h2>";
        if ($result===false) {
            $validate.="<p class='error'>No database with prefix '$prefix'</p>\n";
            $prefix=$config['prefix'];
        } else {
            $toterrs=0;
            $validate.="<p>Number of inconsistencies in the gtd-php data-set. NB some errors may overlap.</p>\n"
                ."<table summary='validation checks'><thead>\n";
            foreach($result['totals'] as $key=>$val)
                $validate .="<tr><td>$val</td><th>$key</th></tr>\n";
            $validate .="</thead><tbody>\n";
            foreach($result['errors'] as $key=>$val) {
                $class=($val)?" class='warnresult' ":" class='goodresult' ";
                $validate .= "<tr><td $class>$val</td><td $class>$key</td></tr>\n";
                $toterrs+=(int) $val;
            }
            $validate .="</tbody></table>\n";
        }
        $action=($toterrs)?'repair':'backup';
        break;
}
/* ------------------------------------------------------------------------
    output begins here
 ------------------------------------------------------------------------*/
?>
<?php require_once 'headerHtml.inc.php'; ?>
</head><body><div id='container'>
<?php require_once 'headerMenu.inc.php'; ?>
<div id='main'>
<h1>gtd-php Admin Tasks</h1>
<?php if ($action==='delete') { ?>
    <h2>Delete installation</h2>
<?php }
if (!empty($validate)) echo $validate;

if ($showInstallations || $showCommands) { ?>
    <h2>Action</h2>
    <form action='admin.php'>
    <?php if ($showInstallations) { ?>
        <h3>Detected installations in this database</h3>
        <p>Pick one to operate on:</p>
        <div class='formrow'>
            <label class='left first' for='prefix'>prefix</label><input id='prefix' type='text' name='prefix'
            value='<?php echo $prefix; ?>' />
        </div>
    <?php } if ($showCommands) { ?>
        <h3>Action to take:</h3>
        <div class='formrow'>
            <?php foreach ($availableActions as $doit) { ?>
                <label class='notfirst left'><?php echo $doit; ?></label>
                <input type='radio' name='action' value=<?php echo "'$doit'",($doit===$action)?" checked='checked' ":''; ?> />
            <?php } ?>
            <input type='submit' name='submit' value='Go' />
        </div>
    <?php } ?>
    </form>
<?php
}
if (!empty($repair)) echo $repair;
if (!empty($backup)) {
    ?><h2>Backup of installation with prefix '<?php echo $prefix; ?>'</h2>
    <textarea cols="120" rows="10"><?php echo $backup; ?></textarea>
<?php } ?>
<h2>&nbsp;</h2>
<p>Note that because this report counts all items (of all types) without parents
 regardless of whether they'd normally appear in the orphans report, the
 orphan count in the table will rarely match the total shown on the <a href='orphans.php'>orphans report</a>.</p>
 <p>The count of next actions also includes items marked as next actions in the
 <a href='listItems.php?type=a&amp;tickler=true'>tickler file</a>, and on the
 <a href='listItems.php?type=w'>waiting-on list</a>, and so will rarely match the
  total shown on the <a href='listItems.php?type=a&amp;nextonly=true'>next-actions report</a>.</p>
<?php require_once 'footer.inc.php'; ?>
